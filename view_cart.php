<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();

// Define login status FIRST before using it anywhere
$isLoggedIn = isLoggedIn();
$clientName = $isLoggedIn ? getUserName() : '';
$clientID = $_SESSION['client_id'] ?? null;

// Initialize cart in session if not exists
if (!isset($_SESSION['food_cart'])) {
    $_SESSION['food_cart'] = [];
}

// Handle cart merge from localStorage BEFORE checking login
if (isset($_POST['merge_cart']) && $isLoggedIn) {
    header('Content-Type: application/json');
    
    $localCart = json_decode($_POST['cart_data'], true);
    
    if (is_array($localCart) && !empty($localCart)) {
        // Merge each item from localStorage
        foreach ($localCart as $localItem) {
            $itemExists = false;
            
            // Check if item already exists in session cart
            foreach ($_SESSION['food_cart'] as &$sessionItem) {
                if ($sessionItem['item_name'] === $localItem['item_name'] && 
                    $sessionItem['size'] === $localItem['size']) {
                    // Item exists, add quantities
                    $sessionItem['quantity'] += $localItem['quantity'];
                    $sessionItem['subtotal'] = $sessionItem['price'] * $sessionItem['quantity'];
                    $itemExists = true;
                    break;
                }
            }
            
            // If item doesn't exist, add it
            if (!$itemExists) {
                $_SESSION['food_cart'][] = $localItem;
            }
        }
        
        // Set merge success flag
        $_SESSION['cart_merged'] = true;
        
        echo json_encode(['success' => true, 'message' => 'Cart merged successfully']);
        exit;
    }
}

// Handle AJAX requests for cart operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                // Add item to cart (SESSION only)
                $itemName = $_POST['item_name'] ?? '';
                $itemDescription = $_POST['item_description'] ?? '';
                $size = $_POST['size'] ?? '';
                $price = floatval($_POST['price'] ?? 0);
                $quantity = intval($_POST['quantity'] ?? 1);
                
                if (empty($itemName) || empty($size) || $price <= 0 || $quantity < 1) {
                    echo json_encode(['success' => false, 'message' => 'Invalid item data']);
                    exit;
                }
                
                // Check if item with same name and size already exists in cart
                $itemExists = false;
                foreach ($_SESSION['food_cart'] as &$cartItem) {
                    if ($cartItem['item_name'] === $itemName && $cartItem['size'] === $size) {
                        $cartItem['quantity'] += $quantity;
                        $cartItem['subtotal'] = $price * $cartItem['quantity'];
                        $itemExists = true;
                        break;
                    }
                }
                
                // If item doesn't exist, add new item
                if (!$itemExists) {
                    $_SESSION['food_cart'][] = [
                        'item_name' => $itemName,
                        'item_description' => $itemDescription,
                        'size' => $size,
                        'price' => $price,
                        'quantity' => $quantity,
                        'subtotal' => $price * $quantity,
                        'added_at' => date('Y-m-d H:i:s')
                    ];
                }
                
                echo json_encode(['success' => true, 'message' => 'Item added to cart']);
                break;
                
            case 'update':
                // Update cart item quantity
                $itemIndex = intval($_POST['item_index'] ?? -1);
                $quantity = intval($_POST['quantity'] ?? 1);
                
                if ($itemIndex < 0 || !isset($_SESSION['food_cart'][$itemIndex]) || $quantity < 1) {
                    echo json_encode(['success' => false, 'message' => 'Invalid data']);
                    exit;
                }
                
                $_SESSION['food_cart'][$itemIndex]['quantity'] = $quantity;
                $_SESSION['food_cart'][$itemIndex]['subtotal'] = $_SESSION['food_cart'][$itemIndex]['price'] * $quantity;
                
                echo json_encode(['success' => true, 'message' => 'Cart updated']);
                break;
                
            case 'remove':
                // Remove item from cart
                $itemIndex = intval($_POST['item_index'] ?? -1);
                
                if ($itemIndex < 0 || !isset($_SESSION['food_cart'][$itemIndex])) {
                    echo json_encode(['success' => false, 'message' => 'Invalid item']);
                    exit;
                }
                
                array_splice($_SESSION['food_cart'], $itemIndex, 1);
                
                echo json_encode(['success' => true, 'message' => 'Item removed']);
                break;
                
            case 'clear':
                // Clear all cart items
                $_SESSION['food_cart'] = [];
                
                echo json_encode(['success' => true, 'message' => 'Cart cleared']);
                break;
                
            case 'confirm':
                // Save cart items to database and create booking + quotation
                if (empty($_SESSION['food_cart'])) {
                    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
                    exit;
                }
                
                // Validate payment method
                $paymentMethod = $_POST['payment_method'] ?? '';
                if (empty($paymentMethod) || !in_array($paymentMethod, ['Cash', 'GCash', 'Bank Transfer'])) {
                    echo json_encode(['success' => false, 'message' => 'Please select a payment method']);
                    exit;
                }
                
                // Validate Bank Transfer details if selected
                if ($paymentMethod === 'Bank Transfer') {
                    $cardNumber = $_POST['card_number'] ?? '';
                    $cardholderName = $_POST['cardholder_name'] ?? '';
                    
                    if (empty($cardNumber) || empty($cardholderName)) {
                        echo json_encode(['success' => false, 'message' => 'Please provide card number and cardholder name for bank transfer']);
                        exit;
                    }
                    
                    // Validate card number (basic check - should be 16 digits)
                    $cardNumber = preg_replace('/\s+/', '', $cardNumber);
                    if (!preg_match('/^\d{16}$/', $cardNumber)) {
                        echo json_encode(['success' => false, 'message' => 'Please enter a valid 16-digit card number']);
                        exit;
                    }
                }
                
                $conn = getDB();
                if (!$conn) {
                    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                    exit;
                }
                
                // Calculate total amount
                $totalAmount = 0;
                foreach ($_SESSION['food_cart'] as $item) {
                    $totalAmount += $item['subtotal'];
                }
                
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Step 1: Insert cart items into cart table
                    $checkColumn = $conn->query("SHOW COLUMNS FROM cart LIKE 'Size'");
                    $sizeColumnExists = $checkColumn->num_rows > 0;
                    
                    if ($sizeColumnExists) {
                        $insertStmt = $conn->prepare("INSERT INTO cart (ClientID, ItemType, ItemName, ItemDescription, Size, PricePerUnit, Quantity, Subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        
                        foreach ($_SESSION['food_cart'] as $item) {
                            $itemType = 'menu';
                            
                            $insertStmt->bind_param(
                                "issssdid",
                                $clientID,
                                $itemType,
                                $item['item_name'],
                                $item['item_description'],
                                $item['size'],
                                $item['price'],
                                $item['quantity'],
                                $item['subtotal']
                            );
                            
                            if (!$insertStmt->execute()) {
                                throw new Exception('Failed to insert cart item: ' . $insertStmt->error);
                            }
                        }
                        $insertStmt->close();
                    }
                    
                    // Step 2: Create a booking for this order
                    $eventType = 'Food Order';
                    $eventDate = date('Y-m-d', strtotime('+7 days'));
                    $eventLocation = 'To Be Determined';
                    $numberOfGuests = 0;
                    
                    // Build special requests from cart items with payment method
                    $specialRequests = "Food Menu Order (Payment: $paymentMethod):\n";
                    foreach ($_SESSION['food_cart'] as $item) {
                        $specialRequests .= "- {$item['item_name']} ({$item['size']}) x{$item['quantity']}\n";
                    }
                    
                    // Add bank details to special requests if Bank Transfer
                    if ($paymentMethod === 'Bank Transfer') {
                        $specialRequests .= "\nBank Transfer Details:\n";
                        $specialRequests .= "Cardholder: " . $cardholderName . "\n";
                        $specialRequests .= "Card (last 4): " . substr($cardNumber, -4);
                    }
                    
                    $insertBooking = $conn->prepare("INSERT INTO booking (ClientID, EventType, DateBooked, EventDate, EventLocation, NumberOfGuests, SpecialRequests, Status, TotalAmount) VALUES (?, ?, NOW(), ?, ?, ?, ?, 'Pending', ?)");
                    $insertBooking->bind_param("isssisd", $clientID, $eventType, $eventDate, $eventLocation, $numberOfGuests, $specialRequests, $totalAmount);
                    
                    if (!$insertBooking->execute()) {
                        throw new Exception('Failed to create booking: ' . $insertBooking->error);
                    }
                    
                    $bookingID = $conn->insert_id;
                    $insertBooking->close();
                    
                    // Step 3: Create a quotation for this booking
                    $adminQuery = $conn->query("SELECT AdminID FROM admin LIMIT 1");
                    $adminResult = $adminQuery->fetch_assoc();
                    $adminID = $adminResult ? intval($adminResult['AdminID']) : null;
                    
                    if ($adminID) {
                        $insertQuotation = $conn->prepare("INSERT INTO quotation (BookingID, AdminID, SpecialRequest, EstimatedPrice, Status) VALUES (?, ?, ?, ?, 'Pending')");
                        $insertQuotation->bind_param("iisd", $bookingID, $adminID, $specialRequests, $totalAmount);
                        
                        if (!$insertQuotation->execute()) {
                            throw new Exception('Failed to create quotation: ' . $insertQuotation->error);
                        }
                        
                        $quotationID = $conn->insert_id;
                        $insertQuotation->close();
                    } else {
                        throw new Exception('No admin found to create quotation');
                    }
                    
                    // Step 4: Create cart order record with payment method
                    $insertOrder = $conn->prepare("INSERT INTO cart_orders (ClientID, BookingID, QuotationID, TotalAmount, Status, Notes) VALUES (?, ?, ?, ?, 'Pending', ?)");
                    $notes = "Food menu order with " . count($_SESSION['food_cart']) . " items. Payment: " . $paymentMethod;
                    $insertOrder->bind_param("iiids", $clientID, $bookingID, $quotationID, $totalAmount, $notes);
                    
                    if (!$insertOrder->execute()) {
                        throw new Exception('Failed to create order record: ' . $insertOrder->error);
                    }
                    
                    $orderID = $conn->insert_id;
                    $insertOrder->close();
                    
                    // Step 5: Insert order items
                    $insertOrderItem = $conn->prepare("INSERT INTO cart_order_items (OrderID, ItemType, ItemName, ItemDescription, Size, PricePerUnit, Quantity, Subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    foreach ($_SESSION['food_cart'] as $item) {
                        $itemType = 'menu';
                        $insertOrderItem->bind_param(
                            "issssdid",
                            $orderID,
                            $itemType,
                            $item['item_name'],
                            $item['item_description'],
                            $item['size'],
                            $item['price'],
                            $item['quantity'],
                            $item['subtotal']
                        );
                        
                        if (!$insertOrderItem->execute()) {
                            throw new Exception('Failed to insert order item: ' . $insertOrderItem->error);
                        }
                    }
                    $insertOrderItem->close();
                    
                    // Store payment method and total in session
                    $_SESSION['last_payment_method'] = $paymentMethod;
                    $_SESSION['last_order_total'] = $totalAmount;
                    $_SESSION['last_order_id'] = $orderID;
                    
                    // Store bank details if Bank Transfer
                    if ($paymentMethod === 'Bank Transfer') {
                        $_SESSION['last_card_number'] = substr($cardNumber, -4);
                        $_SESSION['last_cardholder_name'] = $cardholderName;
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Log activity
                    if (function_exists('logActivity')) {
                        logActivity($clientID, 'client', 'order_created', "Created food order #$orderID with booking #$bookingID and quotation #$quotationID. Payment: $paymentMethod");
                    }
                    
                    // Clear session cart after successful save
                    $_SESSION['food_cart'] = [];
                    
                    // Response based on payment method
                    if ($paymentMethod === 'GCash') {
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Order confirmed! Please scan the QR code to complete payment.',
                            'show_gcash_qr' => true,
                            'order_id' => $orderID,
                            'booking_id' => $bookingID,
                            'quotation_id' => $quotationID
                        ]);
                    } else if ($paymentMethod === 'Bank Transfer') {
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Order confirmed! Bank transfer details saved.',
                            'show_bank_confirmation' => true,
                            'cardholder_name' => $cardholderName,
                            'order_id' => $orderID,
                            'booking_id' => $bookingID,
                            'quotation_id' => $quotationID
                        ]);
                    } else {
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Order confirmed successfully! Order ID: #' . $orderID . '. Total: ₱' . number_format($totalAmount, 2) . '. Payment: ' . $paymentMethod,
                            'order_id' => $orderID,
                            'booking_id' => $bookingID,
                            'quotation_id' => $quotationID
                        ]);
                    }
                    
                } catch (Exception $e) {
                    // Rollback on error
                    $conn->rollback();
                    error_log("Order confirmation error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Failed to save order: ' . $e->getMessage()]);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    
    exit;
}

// Handle GET request for cart count
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'count') {
    header('Content-Type: application/json');
    
    $count = count($_SESSION['food_cart'] ?? []);
    
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

// Get cart items from session OR localStorage
if ($isLoggedIn) {
    // Logged in - use session cart
    $cartItems = $_SESSION['food_cart'] ?? [];
} else {
    // Guest - cart will be loaded from localStorage via JavaScript
    $cartItems = [];
}

$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['subtotal'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Cart - Ellen's Catering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #000000;
            --secondary-dark: #1a1a1a;
            --light-gray: #f8f9fa;
            --border-gray: #dee2e6;
            --text-dark: #212529;
            --medium-gray: #6c757d;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-gray);
            padding-top: 120px;
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

        /* Back Button */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-dark);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .back-button:hover {
            background-color: var(--light-gray);
            color: var(--primary-dark);
        }

        .cart-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .cart-header {
            margin-bottom: 30px;
        }

        .cart-header h1 {
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 5px;
        }

        /* Cart Item - Simplified */
        .cart-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--border-gray);
        }

        .item-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }

        .item-size {
            display: inline-block;
            padding: 3px 12px;
            background: var(--light-gray);
            color: var(--text-dark);
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .item-price {
            color: var(--medium-gray);
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        /* Summary Box - Simplified */
        .cart-summary {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--border-gray);
            position: sticky;
            top: 120px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 2px solid var(--border-gray);
            margin-top: 15px;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        /* Payment Method - Simplified */
        .payment-option {
            background: white;
            border: 2px solid var(--border-gray);
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .payment-option:hover {
            border-color: var(--primary-dark);
        }

        .payment-option.selected {
            border-color: var(--primary-dark);
            background: #f8f8f8;
        }

        .payment-option input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .payment-icon {
            font-size: 1.3rem;
            color: var(--primary-dark);
        }

        /* Buttons - Simplified */
        .btn-primary-custom {
            background: var(--primary-dark);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-primary-custom:hover:not(:disabled) {
            background: var(--secondary-dark);
        }

        .btn-primary-custom:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-outline-custom {
            background: white;
            color: var(--primary-dark);
            border: 2px solid var(--primary-dark);
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-outline-custom:hover {
            background: var(--primary-dark);
            color: white;
        }

        .btn-danger-custom {
            background: transparent;
            color: #dc3545;
            border: 1px solid #dc3545;
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-danger-custom:hover {
            background: #dc3545;
            color: white;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .empty-cart i {
            font-size: 4rem;
            color: var(--medium-gray);
            margin-bottom: 15px;
        }

        /* Quantity Control - Simplified */
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quantity-btn {
            width: 32px;
            height: 32px;
            border: 1px solid var(--border-gray);
            background: white;
            color: var(--primary-dark);
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .quantity-btn:hover {
            background: var(--light-gray);
        }

        /* Bank Details Form */
        #bankDetailsForm {
            margin-top: 15px;
            padding: 18px;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid var(--border-gray);
        }

        /* GCash Modal - Simplified */
        .gcash-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
        }

        .gcash-modal-content {
            background-color: white;
            margin: 8% auto;
            padding: 35px;
            border-radius: 16px;
            width: 90%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .gcash-logo {
            color: #007dfe;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .qr-code-container {
            background: white;
            padding: 15px;
            border-radius: 12px;
            display: inline-block;
            margin: 15px 0;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
        }

        .close-modal {
            color: var(--medium-gray);
            float: right;
            font-size: 26px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: var(--primary-dark);
        }

        @media (max-width: 991px) {
            .navbar-brand {
                margin-left: 0;
            }

            body {
                padding-top: 80px;
            }

            .cart-summary {
                position: static;
                margin-top: 20px;
            }
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
                    <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                    <li class="nav-item"><a href="services.php" class="nav-link">Catering Services</a></li>
                    <li class="nav-item"><a href="food_menu.php" class="nav-link">Food Menu</a></li>
                    <li class="nav-item"><a href="manage_booking.php" class="nav-link">Book Now</a></li>
                    <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
                    <li class="nav-item">
                        <a href="view_cart.php" class="nav-link position-relative">
                            <i class="bi bi-cart3"></i> Cart
                            <?php if (count($cartItems) > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-1"><?= count($cartItems) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($clientName) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile_management.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                            <li><a class="dropdown-item" href="my_bookings.php"><i class="bi bi-calendar-check me-2"></i> My Bookings</a></li>
                            <li><a class="dropdown-item" href="profile_settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="cart-container">
        <!-- Back Button -->
        <a href="food_menu.php" class="back-button">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Menu</span>
        </a>

        <?php
        // For logged-in users, show message if cart was merged
        if ($isLoggedIn && isset($_SESSION['cart_merged'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Your cart items have been merged successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
            unset($_SESSION['cart_merged']);
        }
        ?>
        
        <div class="cart-header">
            <h1><i class="bi bi-cart3 me-2"></i>Your Cart</h1>
            <p style="color: var(--medium-gray); margin: 0;">Review your order</p>
        </div>

        <?php if ($isLoggedIn): ?>
            <?php if (empty($cartItems)): ?>
                <div class="empty-cart">
                    <i class="bi bi-cart-x"></i>
                    <h3 style="color: var(--text-dark); margin-bottom: 10px;">Your cart is empty</h3>
                    <p style="color: var(--medium-gray); margin-bottom: 25px;">Add items from our menu to get started</p>
                    <a href="food_menu.php" class="btn-outline-custom">
                        Browse Menu
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-lg-7">
                        <?php foreach ($cartItems as $index => $item): ?>
                            <div class="cart-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <div class="item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                        <span class="item-size"><?= ucfirst(htmlspecialchars($item['size'])) ?></span>
                                        <div class="item-price">₱<?= number_format($item['price'], 2) ?> each</div>
                                    </div>
                                    <button class="btn-danger-custom" onclick="removeItem(<?= $index ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="quantity-control">
                                        <button class="quantity-btn" onclick="updateQuantity(<?= $index ?>, <?= $item['quantity'] - 1 ?>)">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <span style="font-weight: 600; min-width: 30px; text-align: center;"><?= $item['quantity'] ?></span>
                                        <button class="quantity-btn" onclick="updateQuantity(<?= $index ?>, <?= $item['quantity'] + 1 ?>)">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--primary-dark);">
                                        ₱<?= number_format($item['subtotal'], 2) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="col-lg-5">
                        <div class="cart-summary">
                            <h4 style="color: var(--primary-dark); font-weight: 700; margin-bottom: 20px;">
                                Order Summary
                            </h4>
                            
                            <div class="summary-row">
                                <span style="color: var(--medium-gray);">Subtotal</span>
                                <span style="font-weight: 600;">₱<?= number_format($cartTotal, 2) ?></span>
                            </div>
                            <div class="summary-row">
                                <span style="color: var(--medium-gray);">Items</span>
                                <span style="font-weight: 600;"><?= count($cartItems) ?></span>
                            </div>
                            
                            <div class="summary-total">
                                <span>Total</span>
                                <span>₱<?= number_format($cartTotal, 2) ?></span>
                            </div>
                            
                            <!-- Payment Method Selection -->
                            <div style="margin-top: 25px; margin-bottom: 20px;">
                                <h6 style="color: var(--text-dark); font-weight: 600; margin-bottom: 12px;">
                                    Payment Method
                                </h6>
                                
                                <div class="payment-option" onclick="selectPayment('Cash')">
                                    <input type="radio" name="payment_method" id="payment_cash" value="Cash">
                                    <i class="bi bi-cash-stack payment-icon"></i>
                                    <div style="flex-grow: 1;">
                                        <strong style="font-size: 0.95rem;">Cash</strong>
                                    </div>
                                </div>
                                
                                <div class="payment-option" onclick="selectPayment('GCash')">
                                    <input type="radio" name="payment_method" id="payment_gcash" value="GCash">
                                    <i class="bi bi-phone payment-icon"></i>
                                    <div style="flex-grow: 1;">
                                        <strong style="font-size: 0.95rem;">GCash</strong>
                                    </div>
                                </div>
                                
                                <div class="payment-option" onclick="selectPayment('Bank Transfer')">
                                    <input type="radio" name="payment_method" id="payment_bank" value="Bank Transfer">
                                    <i class="bi bi-bank payment-icon"></i>
                                    <div style="flex-grow: 1;">
                                        <strong style="font-size: 0.95rem;">Bank Transfer</strong>
                                    </div>
                                </div>
                                
                                <!-- Bank Transfer Details Form -->
                                <div id="bankDetailsForm" style="display: none;">
                                    <h6 style="color: var(--primary-dark); font-weight: 600; margin-bottom: 12px; font-size: 0.9rem;">
                                        Card Details
                                    </h6>
                                    <div class="mb-3">
                                        <label for="cardNumber" style="display: block; color: var(--text-dark); font-weight: 500; margin-bottom: 5px; font-size: 0.85rem;">
                                            Card Number <span style="color: red;">*</span>
                                        </label>
                                        <input type="text" id="cardNumber" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19" 
                                               style="border: 1px solid var(--border-gray); border-radius: 6px; padding: 8px; font-size: 0.9rem;"
                                               oninput="formatCardNumber(this)">
                                    </div>
                                    <div class="mb-0">
                                        <label for="cardholderName" style="display: block; color: var(--text-dark); font-weight: 500; margin-bottom: 5px; font-size: 0.85rem;">
                                            Cardholder Name <span style="color: red;">*</span>
                                        </label>
                                        <input type="text" id="cardholderName" class="form-control" placeholder="JOHN DOE" 
                                               style="border: 1px solid var(--border-gray); border-radius: 6px; padding: 8px; font-size: 0.9rem; text-transform: uppercase;">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" onclick="confirmOrder()" class="btn-primary-custom w-100 mb-2" id="confirmOrderBtn" disabled>
                                <i class="bi bi-check-circle me-2"></i>Confirm Order
                            </button>
                            
                            <button onclick="clearCart()" class="btn btn-outline-secondary w-100" style="border-radius: 8px; font-weight: 500; font-size: 0.9rem;">
                                <i class="bi bi-trash me-2"></i>Clear Cart
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Guest users - cart loaded via JavaScript -->
            <div id="guestCartContainer"></div>
        <?php endif; ?>
    </div>

    <!-- GCash QR Code Modal (Replace existing modal in view_cart.php) -->
<div id="gcashModal" class="gcash-modal" style="display: none;">
    <div class="gcash-modal-content">
        <span class="close-modal" onclick="closeGCashModal()">&times;</span>
        <div class="gcash-logo">
            <i class="bi bi-phone-fill"></i>
        </div>
        <h2 style="color: var(--primary-dark); font-weight: 700; margin-bottom: 8px; font-size: 1.5rem;">GCash Payment</h2>
        <p style="color: var(--medium-gray); margin-bottom: 15px; font-size: 0.95rem;">Scan the QR code below to complete your payment</p>
        
        <div class="qr-code-container">
            <img id="gcashQRCode" src="gcash_qr.jpg" alt="GCash QR Code" style="width: 220px; height: 220px;">
        </div>
        
        <div style="background: #f0f8ff; padding: 12px; border-radius: 10px; margin: 15px 0; font-size: 0.9rem;">
            <p style="margin: 0; color: var(--text-dark); font-weight: 600;">
                <i class="bi bi-info-circle me-2"></i>Account: Ellen's Catering
            </p>
            <p style="margin: 5px 0 0 0; color: var(--text-dark); font-weight: 600;">
                <i class="bi bi-receipt me-2"></i>Order ID: #<span id="gcashOrderId"></span>
            </p>
            <p style="margin: 8px 0 0 0; color: var(--text-dark); font-weight: 700; font-size: 1.2rem;">
                Amount: ₱<span id="gcashAmount">0.00</span>
            </p>
        </div>
        
        <div style="margin: 15px 0;">
            <label style="display: block; margin-bottom: 8px; color: var(--text-dark); font-weight: 600; font-size: 0.95rem;">
                <i class="bi bi-key me-1"></i>GCash Reference Number <span style="color: red;">*</span>
            </label>
            <input type="text" id="gcashRefNumber" placeholder="Enter your GCash reference number" 
                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem;" maxlength="20">
            <small style="color: #666; display: block; margin-top: 5px;">
                <i class="bi bi-info-circle me-1"></i>You can find this in your GCash app after payment
            </small>
        </div>
        
        <p style="color: var(--medium-gray); font-size: 0.85rem; margin-bottom: 15px;">
            <i class="bi bi-shield-check me-1"></i>
            Keep your reference number for verification.
        </p>
        
        <button onclick="completeGCashPayment()" type="button" style="background: var(--primary-dark); color: white; border: none; padding: 10px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.95rem; width: 100%;">
            <i class="bi bi-check-circle me-2"></i>I've Completed Payment
        </button>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedPaymentMethod = null;
        let currentOrderId = null;
        let currentOrderTotal = 0;


        function formatCardNumber(input) {
            let value = input.value.replace(/\D/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            input.value = formattedValue;
        }

        function generateGCashQR(amount) {
            return 'gcash_qr.jpg';
        }

        function showGCashModal(amount) {
            const modal = document.getElementById('gcashModal');
            const qrCode = document.getElementById('gcashQRCode');
            const amountDisplay = document.getElementById('gcashAmount');
            
            amountDisplay.textContent = parseFloat(amount).toFixed(2);
            qrCode.src = generateGCashQR(amount);
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeGCashModal() {
            const modal = document.getElementById('gcashModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            location.reload();
        }

        window.onclick = function(event) {
            const modal = document.getElementById('gcashModal');
            if (event.target == modal) {
                closeGCashModal();
            }
        }

        function selectPayment(method) {
            selectedPaymentMethod = method;
            
            document.getElementById('payment_cash').checked = (method === 'Cash');
            document.getElementById('payment_gcash').checked = (method === 'GCash');
            document.getElementById('payment_bank').checked = (method === 'Bank Transfer');
            
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            const bankDetailsForm = document.getElementById('bankDetailsForm');
            if (method === 'Bank Transfer') {
                bankDetailsForm.style.display = 'block';
            } else {
                bankDetailsForm.style.display = 'none';
            }
            
            document.getElementById('confirmOrderBtn').disabled = false;
        }

        function updateQuantity(itemIndex, newQuantity) {
            if (newQuantity < 1) {
                removeItem(itemIndex);
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('item_index', itemIndex);
            formData.append('quantity', newQuantity);

            fetch('view_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update quantity');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        function removeItem(itemIndex) {
            if (!confirm('Remove this item from cart?')) return;

            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('item_index', itemIndex);

            fetch('view_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to remove item');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        function clearCart() {
            if (!confirm('Clear your entire cart?')) return;

            const formData = new FormData();
            formData.append('action', 'clear');

            fetch('view_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to clear cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        function confirmOrder() {
    if (!selectedPaymentMethod) {
        alert('Please select a payment method');
        return;
    }

    if (selectedPaymentMethod === 'Bank Transfer') {
        const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
        const cardholderName = document.getElementById('cardholderName').value.trim();
        
        if (!cardNumber || !cardholderName) {
            alert('Please fill in all card details');
            return;
        }
        
        if (cardNumber.length !== 16) {
            alert('Please enter a valid 16-digit card number');
            return;
        }
    }

    if (!confirm('Confirm order with ' + selectedPaymentMethod + '?')) return;
    
    const formData = new FormData();
    formData.append('action', 'confirm');
    formData.append('payment_method', selectedPaymentMethod);
    
    if (selectedPaymentMethod === 'Bank Transfer') {
        formData.append('card_number', document.getElementById('cardNumber').value.replace(/\s/g, ''));
        formData.append('cardholder_name', document.getElementById('cardholderName').value.trim());
    }
    
    const confirmBtn = document.getElementById('confirmOrderBtn');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
    confirmBtn.disabled = true;

    fetch('view_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Store order details
            currentOrderId = data.order_id;
            currentOrderTotal = '<?= $cartTotal ?>';
            
            if (data.show_gcash_qr) {
                showGCashReferenceModal(data.order_id, currentOrderTotal);
            } else if (data.show_bank_confirmation) {
                const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
                showBankReferenceModal(data.order_id, cardNumber, data.cardholder_name);
            } else {
                alert(data.message);
                location.reload();
            }
        } else {
            alert(data.message || 'Failed to confirm order');
            confirmBtn.innerHTML = originalText;
            confirmBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    });
}

// GCash Reference Modal
function showGCashReferenceModal(orderId, amount) {
    const modal = document.getElementById('gcashModal');
    if (!modal) {
        console.error('GCash modal not found');
        return;
    }
    
    const amountDisplay = document.getElementById('gcashAmount');
    const orderIdDisplay = document.getElementById('gcashOrderId');
    const qrCode = document.getElementById('gcashQRCode');
    
    if (amountDisplay) {
        amountDisplay.textContent = parseFloat(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    if (orderIdDisplay) {
        orderIdDisplay.textContent = orderId;
    }
    
    if (qrCode) {
        qrCode.src = 'gcash_qr.jpg';
    }
    
    // Clear previous reference number
    document.getElementById('gcashRefNumber').value = '';
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeGCashModal() {
    const modal = document.getElementById('gcashModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        location.reload();
    }
}

function completeGCashPayment() {
    const refNumber = document.getElementById('gcashRefNumber').value.trim();
    
    if (!refNumber) {
        alert('Please enter your GCash reference number to proceed.');
        return;
    }
    
    if (refNumber.length < 5) {
        alert('Please enter a valid reference number (at least 5 characters).');
        return;
    }
    
    if (!currentOrderId) {
        alert('Error: Order ID not found. Please refresh and try again.');
        return;
    }
    
    const formData = new FormData();
    formData.append('order_id', currentOrderId);
    formData.append('gcash_reference', refNumber);
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
    btn.disabled = true;
    
    fetch('process_cart_gcash_reference.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ GCash payment recorded!\n\nYour reference number has been saved. We will verify your payment shortly.');
            closeGCashModal();
        } else {
            alert(data.message || 'Failed to save reference number');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again or contact support.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Bank Transfer Reference Modal
function showBankReferenceModal(orderId, cardNumber, senderName) {
    const modal = document.getElementById('bankReferenceModal');
    if (!modal) {
        // Create modal if it doesn't exist
        const modalHTML = `
            <div id="bankReferenceModal" class="gcash-modal" style="display: none;">
                <div class="gcash-modal-content">
                    <span class="close-modal" onclick="closeBankReferenceModal()">&times;</span>
                    <div class="gcash-logo" style="color: #6f42c1;">
                        <i class="bi bi-bank"></i>
                    </div>
                    <h2 style="color: var(--primary-dark); font-weight: 700; margin-bottom: 8px; font-size: 1.5rem;">Bank Transfer Details</h2>
                    <p style="color: var(--medium-gray); margin-bottom: 15px; font-size: 0.95rem;">Please enter your bank transfer reference number</p>
                    
                    <div style="background: #f0f8ff; padding: 12px; border-radius: 10px; margin: 15px 0; font-size: 0.9rem;">
                        <p style="margin: 0; color: var(--text-dark); font-weight: 600;">
                            <i class="bi bi-info-circle me-2"></i>Transfer Method: Bank Transfer
                        </p>
                        <p style="margin: 5px 0 0 0; color: var(--text-dark); font-weight: 600;">
                            <i class="bi bi-receipt me-2"></i>Order ID: #<span id="bankOrderId"></span>
                        </p>
                        <p style="margin: 8px 0 0 0; color: var(--text-dark); font-weight: 600;">
                            <i class="bi bi-person me-2"></i>Sender: <span id="bankSenderName"></span>
                        </p>
                        <p style="margin: 8px 0 0 0; color: var(--text-dark); font-weight: 600;">
                            <i class="bi bi-credit-card me-2"></i>Card: **** **** **** <span id="bankCardLast4"></span>
                        </p>
                    </div>
                    
                    <div style="margin: 15px 0;">
                        <label style="display: block; margin-bottom: 8px; color: var(--text-dark); font-weight: 600; font-size: 0.95rem;">
                            <i class="bi bi-key me-1"></i>Bank Reference Number <span style="color: red;">*</span>
                        </label>
                        <input type="text" id="bankRefNumber" placeholder="Enter your bank reference number" 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem;" maxlength="50">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            <i class="bi bi-info-circle me-1"></i>You can find this in your bank app or transaction receipt
                        </small>
                    </div>
                    
                    <p style="color: var(--medium-gray); font-size: 0.85rem; margin-bottom: 15px;">
                        <i class="bi bi-shield-check me-1"></i>
                        Keep your reference number for verification.
                    </p>
                    
                    <button onclick="completeBankTransfer()" type="button" style="background: var(--primary-dark); color: white; border: none; padding: 10px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.95rem; width: 100%;">
                        <i class="bi bi-check-circle me-2"></i>I've Completed Transfer
                    </button>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    // Populate modal data
    document.getElementById('bankOrderId').textContent = orderId;
    document.getElementById('bankSenderName').textContent = senderName;
    document.getElementById('bankCardLast4').textContent = cardNumber.slice(-4);
    document.getElementById('bankRefNumber').value = '';
    
    // Store data for submission
    window.currentBankTransfer = {
        orderId: orderId,
        cardNumber: cardNumber,
        senderName: senderName
    };
    
    // Show modal
    const bankModal = document.getElementById('bankReferenceModal');
    bankModal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeBankReferenceModal() {
    const modal = document.getElementById('bankReferenceModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        location.reload();
    }
}

function completeBankTransfer() {
    const refNumber = document.getElementById('bankRefNumber').value.trim();
    
    if (!refNumber) {
        alert('Please enter your bank reference number to proceed.');
        return;
    }
    
    if (refNumber.length < 5) {
        alert('Please enter a valid reference number (at least 5 characters).');
        return;
    }
    
    if (!window.currentBankTransfer) {
        alert('Error: Transfer data not found. Please try again.');
        return;
    }
    
    const formData = new FormData();
    formData.append('order_id', window.currentBankTransfer.orderId);
    formData.append('bank_reference', refNumber);
    formData.append('sender_name', window.currentBankTransfer.senderName);
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
    btn.disabled = true;
    
    fetch('process_cart_bank_reference.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Bank transfer recorded!\n\nYour reference number has been saved. We will verify your payment shortly.');
            closeBankReferenceModal();
        } else {
            alert(data.message || 'Failed to save reference number');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again or contact support.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Close modals when clicking outside
window.onclick = function(event) {
    const gcashModal = document.getElementById('gcashModal');
    const bankModal = document.getElementById('bankReferenceModal');
    
    if (event.target == gcashModal) {
        closeGCashModal();
    }
    if (event.target == bankModal) {
        closeBankReferenceModal();
    }
}


        <?php if (!$isLoggedIn): ?>
        function loadGuestCart() {
            const guestCart = localStorage.getItem('guest_cart');
            if (!guestCart) {
                showEmptyCart();
                return;
            }
            
            const cartItems = JSON.parse(guestCart);
            if (cartItems.length === 0) {
                showEmptyCart();
                return;
            }
            
            let total = 0;
            cartItems.forEach(item => {
                total += item.subtotal;
            });
            
            const itemsHTML = cartItems.map((item, index) => `
                <div class="cart-item">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <div class="item-name">${item.item_name}</div>
                            <span class="item-size">${item.size.charAt(0).toUpperCase() + item.size.slice(1)}</span>
                            <div class="item-price">₱${parseFloat(item.price).toFixed(2)} each</div>
                        </div>
                        <button class="btn-danger-custom" onclick="removeGuestItem(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="quantity-control">
                            <button class="quantity-btn" onclick="updateGuestQuantity(${index}, ${item.quantity - 1})">
                                <i class="bi bi-dash"></i>
                            </button>
                            <span style="font-weight: 600; min-width: 30px; text-align: center;">${item.quantity}</span>
                            <button class="quantity-btn" onclick="updateGuestQuantity(${index}, ${item.quantity + 1})">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--primary-dark);">
                            ₱${parseFloat(item.subtotal).toFixed(2)}
                        </div>
                    </div>
                </div>
            `).join('');
            
            const summaryHTML = `
                <div class="cart-summary">
                    <h4 style="color: var(--primary-dark); font-weight: 700; margin-bottom: 20px;">Order Summary</h4>
                    <div class="summary-row">
                        <span style="color: var(--medium-gray);">Subtotal</span>
                        <span style="font-weight: 600;">₱${total.toFixed(2)}</span>
                    </div>
                    <div class="summary-row">
                        <span style="color: var(--medium-gray);">Items</span>
                        <span style="font-weight: 600;">${cartItems.length}</span>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span>₱${total.toFixed(2)}</span>
                    </div>
                    <button type="button" onclick="promptLoginToCheckout()" class="btn-primary-custom w-100 mt-3 mb-2">
                        <i class="bi bi-lock me-2"></i>Login to Checkout
                    </button>
                    <button onclick="clearGuestCart()" class="btn btn-outline-secondary w-100" style="border-radius: 8px; font-weight: 500; font-size: 0.9rem;">
                        <i class="bi bi-trash me-2"></i>Clear Cart
                    </button>
                </div>
            `;
            
            document.getElementById('guestCartContainer').innerHTML = `
                <div class="row">
                    <div class="col-lg-7">${itemsHTML}</div>
                    <div class="col-lg-5">${summaryHTML}</div>
                </div>
            `;
        }
        
        function showEmptyCart() {
            document.getElementById('guestCartContainer').innerHTML = `
                <div class="empty-cart">
                    <i class="bi bi-cart-x"></i>
                    <h3 style="color: var(--text-dark); margin-bottom: 10px;">Your cart is empty</h3>
                    <p style="color: var(--medium-gray); margin-bottom: 25px;">Add items from our menu to get started</p>
                    <a href="food_menu.php" class="btn-outline-custom">Browse Menu</a>
                </div>
            `;
        }
        
        function updateGuestQuantity(index, newQuantity) {
            if (newQuantity < 1) {
                removeGuestItem(index);
                return;
            }
            
            const guestCart = JSON.parse(localStorage.getItem('guest_cart'));
            guestCart[index].quantity = newQuantity;
            guestCart[index].subtotal = guestCart[index].price * newQuantity;
            localStorage.setItem('guest_cart', JSON.stringify(guestCart));
            loadGuestCart();
        }
        
        function removeGuestItem(index) {
            if (!confirm('Remove this item?')) return;
            
            const guestCart = JSON.parse(localStorage.getItem('guest_cart'));
            guestCart.splice(index, 1);
            localStorage.setItem('guest_cart', JSON.stringify(guestCart));
            
            if (guestCart.length === 0) {
                showEmptyCart();
            } else {
                loadGuestCart();
            }
        }
        
        function clearGuestCart() {
            if (!confirm('Clear your entire cart?')) return;
            localStorage.removeItem('guest_cart');
            showEmptyCart();
        }
        
        function promptLoginToCheckout() {
            const guestCart = JSON.parse(localStorage.getItem('guest_cart') || '[]');
            if (guestCart.length > 0) {
                if (confirm('Login to complete your order?')) {
                    window.location.href = 'login_dashboard.php?redirect=view_cart.php';
                }
            } else {
                alert('Your cart is empty!');
            }
        }
        
        document.addEventListener('DOMContentLoaded', loadGuestCart);
        <?php endif; ?>
    </script>
</body>
</html>