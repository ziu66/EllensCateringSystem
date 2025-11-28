    <?php
    require_once 'config/database.php';
    require_once 'includes/security.php';

    startSecureSession();

    // Define login status - allow guests to view but not submit
    $isLoggedIn = isLoggedIn();
    $clientName = $isLoggedIn ? getUserName() : '';
    $client_id = $isLoggedIn ? getClientID() : null;
    $cartCount = isset($_SESSION['food_cart']) ? count($_SESSION['food_cart']) : 0;

    $conn = getDB();
    $client_id = getClientID();
    $message = '';
    $messageType = '';

    // Initialize cart in session if not exists
    if (!isset($_SESSION['food_cart'])) {
        $_SESSION['food_cart'] = [];
    }

    // Fetch booked dates (Confirmed bookings only, future dates only)
    $today = date('Y-m-d');
    $bookedDatesQuery = "SELECT DISTINCT EventDate FROM booking WHERE Status = 'Confirmed' AND EventDate >= ? ORDER BY EventDate";
    $stmt = $conn->prepare($bookedDatesQuery);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $bookedDatesResult = $stmt->get_result();
    $bookedDates = [];
    while ($row = $bookedDatesResult->fetch_assoc()) {
        $bookedDates[] = $row['EventDate'];
    }
    $stmt->close();
    $bookedDatesJson = json_encode($bookedDates);

    // Fetch services from database
    $packageConfigs = [];
    $servicesQuery = "SELECT ServiceName, PricePerPerson, MinimumGuests, IconClass, IsActive 
                    FROM services 
                    WHERE IsActive = 1 
                    ORDER BY DisplayOrder ASC, ServiceName ASC";
    $servicesResult = $conn->query($servicesQuery);

    if ($servicesResult && $servicesResult->num_rows > 0) {
        while ($row = $servicesResult->fetch_assoc()) {
            // Clean up icon class - remove 'bi bi-' prefix if present
            $iconClass = $row['IconClass'];
            $iconClass = str_replace('bi bi-', '', $iconClass);
            $iconClass = str_replace('bi-', '', $iconClass);
            
            $packageConfigs[$row['ServiceName']] = [
                'price' => floatval($row['PricePerPerson']),
                'min_guests' => intval($row['MinimumGuests']),
                'icon' => $iconClass
            ];
        }
    }

    // Debug: Log what we fetched (remove after fixing)
    error_log("Fetched services: " . print_r($packageConfigs, true));

    // Fallback to default packages if no services found
    if (empty($packageConfigs)) {
        $packageConfigs = [
            'Wedding' => ['price' => 550, 'min_guests' => 100, 'icon' => 'heart-fill'],
            'Birthday' => ['price' => 450, 'min_guests' => 50, 'icon' => 'balloon-heart-fill'],
            'Christening' => ['price' => 450, 'min_guests' => 50, 'icon' => 'gift-fill']
        ];
        error_log("Using fallback services");
    }

    // Check if a package was pre-selected from services page
    $preSelectedPackage = isset($_GET['package']) ? sanitizeInput($_GET['package']) : null;
    if ($preSelectedPackage && !isset($packageConfigs[$preSelectedPackage])) {
        $preSelectedPackage = null;
    }

    // Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    // Check if user is logged in before processing
    if (!$isLoggedIn) {
        $message = 'You must be logged in to submit a booking request.';
        $messageType = 'danger';
        
        // Save form data to session for restoration after login
        $_SESSION['pending_booking'] = [
            'event_type' => $_POST['event_type'] ?? '',
            'event_date' => $_POST['event_date'] ?? '',
            'event_location' => $_POST['event_location'] ?? '',
            'number_of_guests' => $_POST['number_of_guests'] ?? '',
            'special_requests' => $_POST['special_requests'] ?? ''
        ];
    } else {
        $eventType = sanitizeInput($_POST['event_type']);
        $eventDate = sanitizeInput($_POST['event_date']);
        $eventLocation = sanitizeInput($_POST['event_location']);
        $numberOfGuests = (int)$_POST['number_of_guests'];
        $specialRequests = sanitizeInput($_POST['special_requests']);
        
        // Validation
        if (empty($eventType) || empty($eventDate) || empty($eventLocation) || $numberOfGuests < 1) {
            $message = 'Please fill in all required fields.';
            $messageType = 'danger';
        } elseif (strtotime($eventDate) < strtotime('today')) {
            $message = 'Event date must be in the future.';
            $messageType = 'danger';
        } elseif (in_array($eventDate, $bookedDates)) {
            $message = 'This date is already booked. Please select another date.';
            $messageType = 'danger';
        } elseif (!isset($packageConfigs[$eventType])) {
            $message = 'Invalid event type selected. Please refresh the page and try again.';
            $messageType = 'danger';
            error_log("Invalid event type: $eventType. Available: " . implode(', ', array_keys($packageConfigs)));
        } elseif ($numberOfGuests < $packageConfigs[$eventType]['min_guests']) {
            $message = "Minimum {$packageConfigs[$eventType]['min_guests']} guests required for {$eventType} package.";
            $messageType = 'danger';
        } else {
            // All validations passed, proceed with booking
            $pricePerPerson = floatval($packageConfigs[$eventType]['price']);
            $totalAmount = $pricePerPerson * $numberOfGuests;
            
            if ($totalAmount <= 0) {
                $message = 'Unable to calculate total amount. Please contact support.';
                $messageType = 'danger';
                error_log("Total amount is zero. Price: $pricePerPerson, Guests: $numberOfGuests");
            } else {
                // Insert booking
                $conn->begin_transaction();
                
                try {
                    // Insert booking (without payment method in special requests)
                    $insertBooking = $conn->prepare("INSERT INTO booking (ClientID, EventType, DateBooked, EventDate, EventLocation, NumberOfGuests, SpecialRequests, Status, TotalAmount) VALUES (?, ?, NOW(), ?, ?, ?, ?, 'Pending', ?)");
                    $insertBooking->bind_param("isssisd", $client_id, $eventType, $eventDate, $eventLocation, $numberOfGuests, $specialRequests, $totalAmount);
                    
                    if (!$insertBooking->execute()) {
                        throw new Exception("Failed to insert booking: " . $insertBooking->error);
                    }
                    
                    $bookingID = $conn->insert_id;
                    $insertBooking->close();
                    
                    // Create corresponding quotation for this booking
                    $adminQuery = $conn->query("SELECT AdminID FROM admin LIMIT 1");
                    $adminResult = $adminQuery->fetch_assoc();
                    $adminID = $adminResult ? intval($adminResult['AdminID']) : null;
                    
                    if ($adminID) {
                        $insertQuotation = $conn->prepare("INSERT INTO quotation (BookingID, AdminID, SpecialRequest, EstimatedPrice, Status) VALUES (?, ?, ?, ?, 'Pending')");
                        $insertQuotation->bind_param("iisd", $bookingID, $adminID, $specialRequests, $totalAmount);
                        
                        if (!$insertQuotation->execute()) {
                            throw new Exception("Failed to insert quotation: " . $insertQuotation->error);
                        }
                        
                        $quotationID = $conn->insert_id;
                        $insertQuotation->close();
                    }
                    
                    $conn->commit();
                    
                    logActivity($client_id, 'client', 'booking_created', "Created booking #$bookingID with quotation #$quotationID");
                    
                    $message = "Booking submitted successfully! Your booking ID is #$bookingID. Total amount: ₱" . number_format($totalAmount, 2) . ". We will review your request and send you a quotation shortly.";
                    $messageType = 'success';
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    error_log("Booking creation error: " . $e->getMessage());
                    $message = 'An error occurred while creating your booking. Please try again.';
                    $messageType = 'danger';
                }
            }
        }
    }
}
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Book Event - Ellen's Catering</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

        <style>
            :root {
                --primary-dark: #000000;
                --secondary-dark: #212529;
                --light-gray: #f8f9fa;
                --border-gray: #dee2e6;
                --text-dark: #212529;
                --accent-gray: #495057;
                --medium-gray: #6c757d;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                background: linear-gradient(135deg, var(--light-gray) 0%, #ffffff 100%);
                min-height: 100vh;
                padding-top: 200px;
                padding-bottom: 50px;
            }

            .booking-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 20px;
            }

            .page-header {
                text-align: center;
                margin-bottom: 50px;
            }

            .page-header h1 {
                color: var(--primary-dark);
                font-weight: 700;
                font-size: 2.5rem;
                margin-bottom: 10px;
            }

            .page-header p {
                color: var(--medium-gray);
                font-size: 1.1rem;
            }

            .booking-card {
                background: white;
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                padding: 40px;
                margin-bottom: 30px;
                border: 1px solid var(--border-gray);
            }

            .section-title {
                color: var(--primary-dark);
                font-weight: 700;
                font-size: 1.5rem;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid var(--primary-dark);
            }

            .form-label {
                font-weight: 600;
                color: var(--primary-dark);
                margin-bottom: 8px;
            }

            .form-control, .form-select, textarea {
                border-radius: 10px;
                padding: 12px 15px;
                border: 2px solid var(--border-gray);
                transition: all 0.3s;
            }

            .form-control:focus, .form-select:focus, textarea:focus {
                border-color: var(--primary-dark);
                box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.1);
            }

            .form-control.is-invalid {
                border-color: #dc3545;
            }

            .package-card {
                border: 2px solid var(--border-gray);
                border-radius: 15px;
                padding: 20px;
                margin-bottom: 20px;
                transition: all 0.3s;
                cursor: pointer;
                position: relative;
                background: white;
                height: 100%;
            }

            .package-card:hover {
                border-color: var(--primary-dark);
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                transform: translateY(-3px);
            }

            .package-card.selected {
                border-color: var(--primary-dark);
                background: var(--primary-dark);
                color: white;
            }

            .package-card.selected .package-name,
            .package-card.selected .package-price {
                color: white;
            }

            .package-icon {
                width: 60px;
                height: 60px;
                background: var(--light-gray);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 15px;
            }

            .package-card.selected .package-icon {
                background: white;
            }

            .package-card.disabled {
        opacity: 0.4;
        cursor: not-allowed;
        background: #e9ecef;
    }

    .locked-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: var(--primary-dark);
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
    }

            .package-icon i {
                font-size: 2rem;
                color: var(--primary-dark);
            }

            .package-name {
                font-weight: 700;
                color: var(--primary-dark);
                font-size: 1.3rem;
                margin-bottom: 10px;
                text-align: center;
            }

            .package-price {
                color: var(--primary-dark);
                font-size: 1.5rem;
                font-weight: 700;
                margin-bottom: 10px;
                text-align: center;
            }

            .package-info {
                text-align: center;
                font-size: 0.9rem;
                color: var(--medium-gray);
            }

            .package-card.selected .package-info {
                color: rgba(255,255,255,0.9);
            }

            .booked-dates-list {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                max-height: 200px;
                overflow-y: auto;
                padding: 10px;
            }

            .booked-date-badge {
                background: #856404;
                color: white;
                padding: 5px 12px;
                border-radius: 20px;
                font-size: 0.85rem;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }

            .total-section {
                background: linear-gradient(135deg, var(--primary-dark), var(--secondary-dark));
                color: white;
                padding: 30px;
                border-radius: 15px;
                text-align: center;
                margin: 30px 0;
            }

            .total-section h3 {
                margin: 0 0 10px;
                font-size: 1.3rem;
            }

            .total-amount {
                font-size: 2.5rem;
                font-weight: 700;
            }

            .btn-submit {
                background: var(--primary-dark);
                border: none;
                color: white;
                font-weight: 600;
                padding: 15px 40px;
                border-radius: 50px;
                font-size: 1.1rem;
                transition: all 0.3s;
                width: 100%;
            }

            .btn-submit:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.2);
                background: var(--secondary-dark);
            }

            .btn-back {
                background: var(--medium-gray);
                border: none;
                color: white;
                font-weight: 600;
                padding: 12px 30px;
                border-radius: 50px;
                transition: all 0.3s;
            }

            .btn-back:hover {
                background: var(--accent-gray);
                transform: translateY(-2px);
            }

            .alert {
                border-radius: 15px;
                border: none;
                padding: 20px;
                margin-bottom: 30px;
            }

            .alert-success {
                background-color: #d4edda;
                color: #155724;
                border-left: 4px solid #28a745;
            }

            .alert-danger {
                background-color: #f8d7da;
                color: #721c24;
                border-left: 4px solid #dc3545;
            }

            .alert-warning {
                background-color: #fff3cd;
                color: #856404;
                border-left: 4px solid #ffc107;
            }

            @media (max-width: 768px) {
                .booking-card {
                    padding: 25px;
                }

                .page-header h1 {
                    font-size: 2rem;
                }

                .total-amount {
                    font-size: 2rem;
                }
            }

            .alert-warning {
                background-color: #fff3cd;
                color: #856404;
                border-left: 4px solid #ffc107;
            }

            .payment-option {
                background: white;
                border: 2px solid var(--border-gray);
                border-radius: 15px;
                padding: 20px;
                cursor: pointer;
                transition: all 0.3s;
                text-align: center;
                height: 100%;
                position: relative;
            }

            .payment-option:hover {
                border-color: var(--primary-dark);
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                transform: translateY(-3px);
            }

            .payment-option.selected {
                border-color: var(--primary-dark);
                background: var(--primary-dark);
                color: white;
            }

            .payment-option input[type="radio"] {
                position: absolute;
                top: 15px;
                right: 15px;
                width: 20px;
                height: 20px;
                cursor: pointer;
            }

            .payment-content {
                margin-top: 10px;
            }

            .payment-icon {
                font-size: 2.5rem;
                color: var(--primary-dark);
                margin-bottom: 10px;
                display: block;
            }

            .payment-option.selected .payment-icon {
                color: white;
            }

            .payment-option strong {
                display: block;
                font-size: 1.2rem;
                margin-bottom: 5px;
            }

            .payment-option p {
                margin: 0;
                font-size: 0.85rem;
                color: var(--medium-gray);
            }

            .payment-option.selected p {
                color: rgba(255,255,255,0.9);
            }

            @media (max-width: 768px) {
            }

            /* GCash QR Modal Styles */
    .gcash-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        animation: fadeIn 0.3s;
    }

    .gcash-modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 40px;
        border-radius: 20px;
        width: 90%;
        max-width: 500px;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { transform: translateY(50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .gcash-logo {
        color: #007dfe;
        font-size: 3rem;
        margin-bottom: 20px;
    }

    .qr-code-container {
        background: white;
        padding: 20px;
        border-radius: 15px;
        display: inline-block;
        margin: 20px 0;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .close-modal {
        color: var(--medium-gray);
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.3s;
    }

    .close-modal:hover {
        color: var(--primary-dark);
    }

    @media (max-width: 991px) {
        .gcash-modal-content {
            margin: 20% auto;
            padding: 30px 20px;
        }
    }

    /* Navbar */
    .navbar {
        background: rgba(255, 255, 255, 0.98) !important;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
        position: fixed;
        width: 100%;
        z-index: 1000;
        top: 0;
        padding: 1.2rem 0;
    }

    .navbar-brand {
        position: relative;
        display: flex;
        align-items: center;
        height: 80px;
        overflow: visible;
        margin-left: -50px;
    }

    .navbar-brand img {
        position: absolute;
        height: 150px;
        width: 150px;
        border-radius: 50%;
        background: white;
        border: 3px solid var(--primary-dark);
        padding: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        top: -10px;
        left: 0;
        object-fit: contain;
        z-index: 2;
    }

    .nav-link {
        color: var(--primary-dark) !important;
        font-weight: 500;
        margin: 0 14px;
        transition: color 0.3s ease;
        font-size: 1.05rem;
        position: relative;
    }

    .nav-link:not(.dropdown-toggle)::after {
        content: "";
        position: absolute;
        bottom: -6px;
        left: 0;
        width: 0;
        height: 2px;
        background-color: var(--primary-dark);
        transition: width 0.3s ease;
    }

    .nav-link:not(.dropdown-toggle):hover::after {
        width: 100%;
    }

    .nav-link.dropdown-toggle::before {
        content: "";
        position: absolute;
        bottom: -6px;
        left: 0;
        width: 0;
        height: 2px;
        background-color: var(--primary-dark);
        transition: width 0.3s ease;
    }

    .nav-link.dropdown-toggle:hover::before {
        width: 100%;
    }

    .dropdown-menu {
        background-color: #ffffff;
        border: 1px solid var(--border-gray);
        border-radius: 8px;
        padding: 10px 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        margin-top: 10px;
    }

    .dropdown-item {
        color: var(--text-dark);
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .dropdown-item:hover {
        background-color: var(--primary-dark);
        color: #fff;
        padding-left: 25px;
    }

    .nav-link .badge {
        position: absolute;
        top: -5px;
        right: -10px;
        font-size: 0.7rem;
        padding: 3px 6px;
        animation: pulse 1s ease-in-out;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
        </style>
    </head>

    <body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="ellenLogo_removebg-preview.png" alt="Ellen's Catering Logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">Home</a>
                </li>

                <li class="nav-item">
                    <a href="services.php" class="nav-link">Catering Services</a>
                </li>

                <li class="nav-item">
                    <a href="food_menu.php" class="nav-link">Food Menu</a>
                </li>

                <li class="nav-item"><a href="manage_booking.php" class="nav-link active">Book Now</a></li>
                <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>

                <li class="nav-item">
    <a href="view_cart.php" class="nav-link position-relative">
        <i class="bi bi-cart3"></i> Cart
        <span class="badge bg-danger rounded-pill ms-1" id="cart-badge" 
              <?= ($cartCount == 0) ? 'style="display: none;"' : '' ?>>
            <?= $cartCount ?>
        </span>
    </a>
</li>
            </ul>

            <ul class="navbar-nav mb-2 mb-lg-0">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($clientName) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile_management.php"><i class="bi bi-speedometer2 me-2"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="view_cart.php"><i class="bi bi-cart3 me-2"></i> My Cart</a></li>
                            <li><a class="dropdown-item" href="my_bookings.php"><i class="bi bi-calendar-check me-2"></i> My Bookings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a href="login_dashboard.php" class="nav-link">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

        <div class="booking-container">
            <div class="page-header">
                <h1><i class="bi bi-calendar-check me-3"></i>Book Your Event</h1>
                <p>Choose your package and fill in the details to reserve Ellen's Catering</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="bookingForm">
                <!-- Package Selection -->
                <div class="booking-card">
                    <h3 class="section-title"><i class="bi bi-box-seam me-2"></i>Select Your Package</h3>
                    <p class="text-muted mb-4">Choose the perfect package for your event</p>

                    <?php if (empty($packageConfigs)): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No services are currently available. Please contact the administrator.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($packageConfigs as $packageName => $config): ?>
        <?php 
            $isDisabled = $preSelectedPackage && $preSelectedPackage !== $packageName;
            $isSelected = $preSelectedPackage === $packageName;
        ?>
        <div class="col-md-4 mb-3">
            <div class="package-card <?= $isDisabled ? 'disabled' : '' ?> <?= $isSelected ? 'selected' : '' ?>" 
                onclick="<?= $isDisabled ? '' : "selectPackage('" . htmlspecialchars($packageName) . "', {$config['price']}, {$config['min_guests']})" ?>">
                <?php if ($isSelected): ?>
                    <span class="locked-badge"><i class="bi bi-lock-fill me-1"></i>Locked</span>
                <?php endif; ?>
                                        <div class="package-icon">
                                            <i class="bi bi-<?= htmlspecialchars($config['icon']) ?>"></i>
                                        </div>
                                        <div class="package-name"><?= htmlspecialchars($packageName) ?></div>
                                        <div class="package-price">₱<?= number_format($config['price']) ?> <small>/person</small></div>
                                        <div class="package-info">Minimum <?= $config['min_guests'] ?> guests</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <input type="hidden" name="event_type" id="selectedPackage" value="<?= $preSelectedPackage ? htmlspecialchars($preSelectedPackage) : '' ?>" required>
    <input type="hidden" id="pricePerPerson" value="<?= $preSelectedPackage ? $packageConfigs[$preSelectedPackage]['price'] : '0' ?>">
    <input type="hidden" id="minGuests" value="<?= $preSelectedPackage ? $packageConfigs[$preSelectedPackage]['min_guests'] : '0' ?>">
                </div>

                <!-- Booked Dates Section -->
                <?php if (!empty($bookedDates)): ?>
                    <?php
                    // Filter for future dates only
                    $today = date('Y-m-d');
                    $futureBookedDates = array_filter($bookedDates, function($date) use ($today) {
                        return $date >= $today;
                    });
                    ?>
                    
                    <?php if (!empty($futureBookedDates)): ?>
                        <div class="booking-card" style="background: linear-gradient(to right, #fff9e6, white); border-left: 5px solid #ffc107;">
                            <h3 class="section-title" style="color: #856404; border-bottom-color: #ffc107;">
                                <i class="bi bi-calendar-x me-2"></i>Already Booked Dates
                            </h3>
                            <div class="alert alert-warning mb-3" style="border-left: 4px solid #ffc107;">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong><?= count($futureBookedDates) ?> upcoming date<?= count($futureBookedDates) > 1 ? 's are' : ' is' ?></strong> already booked and unavailable for new reservations.
                            </div>
                            <div class="booked-dates-list">
                                <?php 
                                $maxDisplay = 15; // Show first 15 dates
                                $displayDates = array_slice($futureBookedDates, 0, $maxDisplay);
                                foreach ($displayDates as $date): 
                                ?>
                                    <span class="booked-date-badge">
                                        <i class="bi bi-x-circle"></i><?= date('M d, Y', strtotime($date)) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($futureBookedDates) > $maxDisplay): ?>
                                    <span class="booked-date-badge" style="background: #6c757d;">
                                        <i class="bi bi-three-dots"></i>+<?= count($futureBookedDates) - $maxDisplay ?> more
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Event Details -->
                <div class="booking-card">
                    <h3 class="section-title"><i class="bi bi-info-circle me-2"></i>Event Details</h3>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Event Date *</label>
                            <input type="date" name="event_date" id="eventDate" class="form-control" min="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                            <small class="text-muted">Minimum 7 days advance booking. Dates shown above are unavailable.</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Number of Guests *</label>
                            <input type="number" name="number_of_guests" id="numberOfGuests" class="form-control" min="<?= $preSelectedPackage ? $packageConfigs[$preSelectedPackage]['min_guests'] : '1' ?>" placeholder="Enter number" required oninput="calculateTotal()">
                            <small class="text-muted" id="minGuestsText">
        <?php if ($preSelectedPackage): ?>
            Minimum <?= $packageConfigs[$preSelectedPackage]['min_guests'] ?> guests required for <?= htmlspecialchars($preSelectedPackage) ?>
        <?php endif; ?>
    </small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Event Location *</label>
                            <input type="text" name="event_location" class="form-control" placeholder="Full venue address" required>
                        </div>

                        <!-- ADD THE INCLUSIONS HERE -->
                        <div class="col-md-12 mb-3">
                            <div style="background: #f8f9fa; border-radius: 10px; padding: 15px; border: 1px solid var(--border-gray);">
                                <h6 style="color: var(--primary-dark); font-weight: 700; font-size: 0.95rem; margin-bottom: 10px;">
                                    <i class="bi bi-check-circle me-2"></i>All Packages Include:
                                </h6>
                                <div class="row" style="font-size: 0.85rem;">
                                    <div class="col-md-6">
                                        <strong>Food:</strong> Steamed Rice, 1 Pork Dish, 1 Chicken Dish, 1 Seafood Dish, 1 Beef Dish, 1 Vegetables Dish, 1 Pasta, 1 Dessert, 2 Drinks
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Services:</strong> Complete Buffet Setup, Chinawares & Utensils, Tables & Chairs w/ Centerpiece, Tables for Cakes/Giveaways, Tables for Drinks, Photo Backdrop, Decors & Styling w/ Theme Color, Professional Waiters
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- END OF INCLUSIONS -->
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Special Requests</label>
                            <textarea name="special_requests" class="form-control" rows="4" placeholder="Any special dietary requirements, setup preferences, theme color, or other requests..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Total Section -->
                <div class="total-section">
                    <h3>Estimated Total Amount</h3>
                    <div class="total-amount">₱<span id="totalAmount">0.00</span></div>
                    <small>Base package price × number of guests</small>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <?php if ($isLoggedIn): ?>
                        <button type="submit" name="submit_booking" class="btn btn-submit">
                            <i class="bi bi-check-circle me-2"></i>Submit Booking Request
                        </button>
                    <?php else: ?>
                        <button type="button" onclick="promptLoginToBook()" class="btn btn-submit">
                            <i class="bi bi-lock me-2"></i>Login to Submit Booking
                        </button>
                    <?php endif; ?>
                    <a href="services.php" class="btn btn-back">
                        <i class="bi bi-arrow-left me-2"></i>Back to Services
                    </a>
                </div>
            </form>
        </div>

        <!-- GCash QR Code Modal -->
    <div id="gcashModal" class="gcash-modal">
        <div class="gcash-modal-content">
            <span class="close-modal" onclick="closeGCashModal()">&times;</span>
            <div class="gcash-logo">
                <i class="bi bi-phone-fill"></i>
            </div>
            <h2 style="color: var(--primary-dark); font-weight: 700; margin-bottom: 10px;">GCash Payment</h2>
            <p style="color: var(--medium-gray); margin-bottom: 20px;">Scan the QR code below to complete your payment</p>
            
            <div class="qr-code-container">
                <img id="gcashQRCode" src="gcash_qr.jpg" alt="GCash QR Code" style="width: 250px; height: 250px;">
            </div>
            
            <div style="background: #f0f8ff; padding: 15px; border-radius: 10px; margin: 20px 0;">
                <p style="margin: 0; color: var(--text-dark); font-weight: 600;">
                    <i class="bi bi-info-circle me-2"></i>Account Name: Ellen's Catering
                </p>
                <p style="margin: 5px 0 0 0; color: var(--text-dark); font-weight: 600;">
                    <i class="bi bi-receipt me-2"></i>Booking ID: #<span id="gcashBookingId"></span>
                </p>
                <p style="margin: 10px 0 0 0; color: var(--text-dark); font-weight: 700; font-size: 1.3rem;">
                    Amount: ₱<span id="gcashAmount">0.00</span>
                </p>
            </div>
            
            <p style="color: var(--medium-gray); font-size: 0.9rem; margin-bottom: 20px;">
                <i class="bi bi-shield-check me-1"></i>
                After payment, please keep your reference number for verification.
            </p>
            
            <button onclick="closeGCashModal()" class="btn-submit" type="button">
                <i class="bi bi-check-circle me-2"></i>I've Completed Payment
            </button>
        </div>
    </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            // Booked dates from PHP
            const bookedDates = <?= $bookedDatesJson ?>;
            
            // Disable booked dates in date picker
            document.addEventListener('DOMContentLoaded', function() {
                const dateInput = document.getElementById('eventDate');
                
                dateInput.addEventListener('input', function() {
                    const selectedDate = this.value;
                    
                    if (bookedDates.includes(selectedDate)) {
                        alert('This date is already booked! Please select another date.');
                        this.value = '';
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }

                    // Initialize calculation if pre-selected
    const preSelectedPackage = document.getElementById('selectedPackage').value;
    if (preSelectedPackage) {
        calculateTotal();
    }
                });
            });



            function showClearButton() {
        // Check if button already exists
        let clearBtn = document.getElementById('clearSelectionBtn');
        
        if (!clearBtn) {
            // Create clear button
            clearBtn = document.createElement('button');
            clearBtn.id = 'clearSelectionBtn';
            clearBtn.type = 'button';
            clearBtn.className = 'btn btn-outline-danger mt-3';
            clearBtn.style.cssText = 'border-radius: 50px; border-width: 2px; font-weight: 600; padding: 10px 25px;';
            clearBtn.innerHTML = '<i class="bi bi-x-circle me-2"></i>Clear Selection';
            clearBtn.onclick = clearPackageSelection;
            
            // Insert after the package cards row
            const packageSection = document.querySelector('.booking-card');
            const hiddenInputs = document.querySelector('#selectedPackage').parentElement;
            hiddenInputs.parentElement.insertBefore(clearBtn, hiddenInputs);
        }
    }

    function clearPackageSelection() {
        // Remove selected and disabled classes
        document.querySelectorAll('.package-card').forEach(card => {
            card.classList.remove('selected', 'disabled');
            card.style.cursor = 'pointer';
            
            // Remove locked badge
            const badge = card.querySelector('.locked-badge');
            if (badge) {
                badge.remove();
            }
            
            // Restore original onclick
            const packageName = card.querySelector('.package-name').textContent;
            const priceText = card.querySelector('.package-price').textContent;
            const price = parseFloat(priceText.replace(/[^\d.]/g, ''));
            const minGuestsText = card.querySelector('.package-info').textContent;
            const minGuests = parseInt(minGuestsText.match(/\d+/)[0]);
            
            card.onclick = function() {
                selectPackage(packageName, price, minGuests);
            };
        });
        
        // Clear hidden fields
        document.getElementById('selectedPackage').value = '';
        document.getElementById('pricePerPerson').value = '0';
        document.getElementById('minGuests').value = '0';
        
        // Reset min guests
        document.getElementById('numberOfGuests').min = '1';
        document.getElementById('minGuestsText').textContent = '';
        
        // Reset total
        document.getElementById('totalAmount').textContent = '0.00';
        
        // Remove clear button
        const clearBtn = document.getElementById('clearSelectionBtn');
        if (clearBtn) {
            clearBtn.remove();
        }
    }


            function selectPackage(packageName, pricePerPerson, minGuests) {
        // Check if a package is pre-selected and locked
        const preSelectedPackage = document.getElementById('selectedPackage').value;
        
        if (preSelectedPackage && preSelectedPackage !== packageName) {
            alert('This package is locked. Please go back to services page to select a different package.');
            return false;
        }
        
        // Remove selected class and locked badge from all packages
        document.querySelectorAll('.package-card:not(.disabled)').forEach(card => {
            card.classList.remove('selected');
            const existingBadge = card.querySelector('.locked-badge');
            if (existingBadge) {
                existingBadge.remove();
            }
        });

        // Add selected class to clicked package
        event.currentTarget.classList.add('selected');
        
        // Add locked badge to selected package
        const lockedBadge = document.createElement('span');
        lockedBadge.className = 'locked-badge';
        lockedBadge.innerHTML = '<i class="bi bi-lock-fill me-1"></i>Locked';
        event.currentTarget.appendChild(lockedBadge);
        
        // Disable other packages visually
        document.querySelectorAll('.package-card').forEach(card => {
            if (!card.classList.contains('selected')) {
                card.classList.add('disabled');
                // Remove onclick to prevent clicking
                card.style.cursor = 'not-allowed';
                card.onclick = function(e) {
                    e.stopPropagation();
                    alert('This package is locked. Click "Clear Selection" to choose a different package.');
                    return false;
                };
            }
        });

        // Set hidden fields
        document.getElementById('selectedPackage').value = packageName;
        document.getElementById('pricePerPerson').value = pricePerPerson;
        document.getElementById('minGuests').value = minGuests;

        // Update min guests input and text
        document.getElementById('numberOfGuests').min = minGuests;
        document.getElementById('minGuestsText').textContent = `Minimum ${minGuests} guests required for ${packageName}`;

        // Show clear selection button
        showClearButton();

        // Calculate total
        calculateTotal();
    }

            function calculateTotal() {
                const pricePerPerson = parseFloat(document.getElementById('pricePerPerson').value) || 0;
                const numberOfGuests = parseInt(document.getElementById('numberOfGuests').value) || 0;

                const total = pricePerPerson * numberOfGuests;

                document.getElementById('totalAmount').textContent = total.toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            // Form validation
            document.getElementById('bookingForm').addEventListener('submit', function(e) {
                const packageSelected = document.getElementById('selectedPackage').value;
                const numberOfGuests = parseInt(document.getElementById('numberOfGuests').value) || 0;
                const minGuests = parseInt(document.getElementById('minGuests').value) || 0;
                const selectedDate = document.getElementById('eventDate').value;

                if (!packageSelected) {
                    e.preventDefault();
                    alert('Please select a package.');
                    return false;
                }

                if (bookedDates.includes(selectedDate)) {
                    e.preventDefault();
                    alert('This date is already booked! Please select another date.');
                    return false;
                }

                if (numberOfGuests < minGuests) {
                    e.preventDefault();
                    alert(`Minimum ${minGuests} guests required for the selected package.`);
                    return false;
                }
            });

            // ADD THESE NEW FUNCTIONS HERE
            function promptLoginToBook() {
                if (confirm('You need to login to submit a booking request. Login now?')) {
                    // Save current form data to session via AJAX
                    const formData = new FormData(document.getElementById('bookingForm'));
                    
                    fetch('save_pending_booking.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(() => {
                        window.location.href = 'login_dashboard.php?redirect=manage_booking.php';
                    })
                    .catch(() => {
                        // If save fails, still redirect to login
                        window.location.href = 'login_dashboard.php?redirect=manage_booking.php';
                    });
                }
            }

            // Restore form data after login
            window.addEventListener('DOMContentLoaded', function() {
                <?php if (isset($_SESSION['pending_booking'])): ?>
                    const pending = <?= json_encode($_SESSION['pending_booking']) ?>;
                    
                    if (pending.event_type) {
                        const pricePerPerson = parseFloat(document.getElementById('pricePerPerson').value);
                        const minGuests = parseInt(document.getElementById('minGuests').value);
                        if (pricePerPerson && minGuests) {
                            selectPackage(pending.event_type, pricePerPerson, minGuests);
                        }
                    }
                    if (pending.event_date) document.getElementById('eventDate').value = pending.event_date;
                    if (pending.event_location) document.querySelector('[name="event_location"]').value = pending.event_location;
                    if (pending.number_of_guests) {
                        document.getElementById('numberOfGuests').value = pending.number_of_guests;
                        calculateTotal();
                    }
                    if (pending.special_requests) document.querySelector('[name="special_requests"]').value = pending.special_requests;
                    
                    <?php unset($_SESSION['pending_booking']); ?>
                    
                    alert('Welcome back! Your booking form has been restored.');
                <?php endif; ?>
            });

            // GCash Modal Functions
    function showGCashModal(amount, bookingId) {
        const modal = document.getElementById('gcashModal');
        const amountDisplay = document.getElementById('gcashAmount');
        const bookingIdDisplay = document.getElementById('gcashBookingId');
        
        // Set amount and booking ID
        amountDisplay.textContent = parseFloat(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        bookingIdDisplay.textContent = bookingId;
        
        // Show modal
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeGCashModal() {
        const modal = document.getElementById('gcashModal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('gcashModal');
        if (event.target == modal) {
            closeGCashModal();
        }
    }

    // Check if GCash modal should be shown on page load
    <?php if (isset($_SESSION['show_gcash_modal']) && $_SESSION['show_gcash_modal']): ?>
    document.addEventListener('DOMContentLoaded', function() {
        showGCashModal(
            <?= $_SESSION['gcash_amount'] ?? 0 ?>, 
            '<?= $_SESSION['gcash_booking_id'] ?? '' ?>'
        );
        
        <?php 
        // Clear session variables after displaying
        unset($_SESSION['show_gcash_modal']);
        unset($_SESSION['gcash_amount']);
        unset($_SESSION['gcash_booking_id']);
        ?>
    });
    <?php endif; ?>
        
        </script>

    <script>
        // Dropdown hover effect for desktop
        document.querySelectorAll('.nav-item.dropdown').forEach(dropdown => {
            dropdown.addEventListener('mouseenter', () => {
                if (window.innerWidth >= 992) {
                    dropdown.classList.add('show');
                    dropdown.querySelector('.dropdown-menu').classList.add('show');
                }
            });

            dropdown.addEventListener('mouseleave', () => {
                if (window.innerWidth >= 992) {
                    dropdown.classList.remove('show');
                    dropdown.querySelector('.dropdown-menu').classList.remove('show');
                }
            });
        });
    </script>
    </body>
    </html>