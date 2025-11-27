<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();
requireLogin();

$clientID = getClientID(); 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_method'])) {
    header('Content-Type: application/json');
    
    $bookingID = intval($_POST['booking_id']);
    $paymentMethod = sanitizeInput($_POST['payment_method']);
    
    // Validate payment method
    if (!in_array($paymentMethod, ['Cash', 'GCash', 'Bank Transfer'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
        exit;
    }
    
    $conn = getDB();
    
    // Verify booking belongs to client
    $verifyStmt = $conn->prepare("SELECT BookingID FROM booking WHERE BookingID = ? AND ClientID = ?");
    $verifyStmt->bind_param("ii", $bookingID, $clientID);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    $verifyStmt->close();
    
    // Call the API endpoint via cURL or file_get_contents to save payment method
    $apiData = [
        'booking_id' => $bookingID,
        'payment_method' => $paymentMethod,
        'client_id' => $clientID
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/web/api/bookings/index.php?action=save_payment_method");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $apiResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $apiResponse) {
        $apiResult = json_decode($apiResponse, true);
        if ($apiResult['success']) {
            // Store for GCash modal if needed
            if ($paymentMethod === 'GCash') {
                $_SESSION['show_gcash_modal'] = true;
                $_SESSION['gcash_amount'] = $_POST['total_amount'] ?? 0;
                $_SESSION['gcash_booking_id'] = $bookingID;
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Payment method updated successfully',
                'show_gcash' => ($paymentMethod === 'GCash')
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $apiResult['message'] ?? 'Failed to update payment method']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save payment method. Please try again.']);
    }
    
    exit;
}

$conn = getDB();
$client_id = getClientID();

// Get messages from session (for cancel booking feedback)
$message = isset($_SESSION['booking_message']) ? $_SESSION['booking_message'] : '';
$messageType = isset($_SESSION['booking_message_type']) ? $_SESSION['booking_message_type'] : '';
unset($_SESSION['booking_message'], $_SESSION['booking_message_type']);

// Replace the bookingsQuery section with:
$bookingsQuery = "
    SELECT 
        b.*,
        b.GCashReference,
        b.BankReferenceNumber,
        b.BankSenderName,
        b.PaymentMethod,
        b.PaymentStatus,
        b.PaymentDate,
        p.PackageName,
        p.PackPrice,
        q.QuotationID,
        q.EstimatedPrice as QuotationPrice,
        q.SpecialRequestPrice,
        q.SpecialRequestItems,
        (q.EstimatedPrice + IFNULL(q.SpecialRequestPrice, 0)) as TotalQuotationPrice,
        q.Status as QuotationStatus,
        q.SpecialRequest as QuotationDetails,
        GROUP_CONCAT(DISTINCT m.DishName SEPARATOR ', ') AS MenuItems
    FROM booking b
    LEFT JOIN booking_package bp ON b.BookingID = bp.BookingID
    LEFT JOIN package p ON bp.PackageID = p.PackageID
    LEFT JOIN booking_menu bm ON b.BookingID = bm.BookingID
    LEFT JOIN menu m ON bm.MenuID = m.MenuID
    LEFT JOIN quotation q ON b.BookingID = q.BookingID
    WHERE b.ClientID = ?
    GROUP BY b.BookingID
    ORDER BY b.DateBooked DESC
";
$stmt = $conn->prepare($bookingsQuery);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Ellen's Catering</title>
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
            padding: 50px 0;
        }

        .container {
            max-width: 1200px;
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
            padding: 30px;
            margin-bottom: 25px;
            transition: all 0.3s;
            border: 1px solid var(--border-gray);
            border-left: 5px solid var(--primary-dark);
        }

        .booking-card:hover {
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            transform: translateY(-5px);
        }

        /* Cancelled booking styling */
        .booking-card.cancelled {
            opacity: 0.7;
            border-left-color: #dc3545;
            background: linear-gradient(to right, #fff5f5, white);
        }

        .booking-card.cancelled:hover {
            transform: translateY(0);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .booking-id {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .status-badge {
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
            border: 2px solid #17a2b8;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .booking-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            align-items: start;
            gap: 10px;
        }

        .info-item i {
            color: var(--primary-dark);
            font-size: 1.2rem;
            margin-top: 3px;
        }

        .cancelled .info-item i {
            color: #6c757d;
        }

        .info-label {
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-value {
            color: var(--text-dark);
            font-size: 1rem;
        }

        .booking-details {
            background: var(--light-gray);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid var(--border-gray);
        }

        .booking-details h6 {
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-gray);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--medium-gray);
        }

        .detail-value {
            color: var(--text-dark);
            text-align: right;
        }

        .total-amount {
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary-dark));
            color: white;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .cancelled .total-amount {
            background: linear-gradient(135deg, #6c757d, #495057);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn-action {
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            border: none;
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
        }

        .btn-cancel:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220,53,69,0.3);
        }

        .cancelled-notice {
            background: #f8d7da;
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            color: #721c24;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid var(--border-gray);
        }

        .empty-state i {
            font-size: 5rem;
            color: var(--medium-gray);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: var(--primary-dark);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .empty-state p {
            color: var(--medium-gray);
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .btn-primary-custom {
            background: var(--primary-dark);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px 40px;
            border-radius: 50px;
            transition: all 0.3s;
            font-size: 1.1rem;
        }

        .btn-primary-custom:hover {
            background: var(--secondary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .btn-back {
            background: var(--medium-gray);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px 40px;
            border-radius: 50px;
            transition: all 0.3s;
            font-size: 1.1rem;
        }

        .btn-back:hover {
            background: var(--accent-gray);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        @media (max-width: 768px) {
            .booking-card {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .booking-header {
                flex-direction: column;
                align-items: start;
            }

            .booking-info {
                grid-template-columns: 1fr;
            }

            .detail-row {
                flex-direction: column;
                gap: 5px;
            }

            .detail-value {
                text-align: left;
            }

            .btn-primary-custom,
            .btn-back {
                width: 100%;
            }
        }

        .btn-pay {
            background: #28a745;
            color: white;
        }

        .btn-pay:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        }

         /* Bank Details Modal */
        .bank-details-modal .modal {
            z-index: 1055;
        }

        .bank-details-modal .modal-content {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        /* GCash Modal */
/* GCash Modal */
.gcash-modal {
    display: none;
    position: fixed;
    z-index: 99999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    animation: fadeIn 0.3s;
}

.gcash-modal-content {
    background-color: white;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 30px;
    border-radius: 20px;
    width: 90%;
    max-width: 450px;
    max-height: 90vh;
    overflow-y: auto;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s;
    z-index: 100000;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { transform: translate(-50%, -40%); opacity: 0; }
    to { transform: translate(-50%, -50%); opacity: 1; }
}

.gcash-logo {
    color: #007dfe;
    font-size: 2.5rem;
    margin-bottom: 15px;
}

.qr-code-container {
    background: white;
    padding: 15px;
    border-radius: 15px;
    display: inline-block;
    margin: 15px 0;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

.qr-code-container img {
    width: 200px !important;
    height: 200px !important;
    display: block;
}

.close-modal {
    color: var(--medium-gray);
    position: absolute;
    right: 20px;
    top: 15px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s;
    line-height: 1;
}

.close-modal:hover {
    color: var(--primary-dark);
}

@media (max-width: 576px) {
    .gcash-modal-content {
        width: 95%;
        padding: 20px;
    }
    
    .gcash-logo {
        font-size: 2rem;
    }
    
    .qr-code-container img {
        width: 180px !important;
        height: 180px !important;
    }
}

    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="bi bi-calendar-check me-3"></i>My Bookings</h1>
            <p>View and manage your catering reservations</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'exclamation-circle') ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($bookings->num_rows > 0): ?>
            <?php while ($booking = $bookings->fetch_assoc()): ?>
                <div class="booking-card <?= $booking['Status'] === 'Cancelled' ? 'cancelled' : '' ?>">
                    <div class="booking-header">
                        <div class="booking-id">
                            <i class="bi bi-receipt me-2"></i>Booking #<?= $booking['BookingID'] ?>
                        </div>
                        <span class="status-badge status-<?= strtolower($booking['Status']) ?>">
                            <?php if ($booking['Status'] === 'Cancelled'): ?>
                                <i class="bi bi-x-circle"></i>
                            <?php elseif ($booking['Status'] === 'Pending'): ?>
                                <i class="bi bi-clock"></i>
                            <?php elseif ($booking['Status'] === 'Confirmed'): ?>
                                <i class="bi bi-check-circle"></i>
                            <?php elseif ($booking['Status'] === 'Completed'): ?>
                                <i class="bi bi-check-all"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($booking['Status']) ?>
                        </span>
                    </div>

                    <?php if ($booking['Status'] === 'Cancelled'): ?>
                        <div class="cancelled-notice">
                            <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem;"></i>
                            <span>This booking has been cancelled and cannot be modified.</span>
                        </div>
                    <?php endif; ?>

                    <div class="booking-info">
                        <div class="info-item">
                            <i class="bi bi-calendar-event"></i>
                            <div>
                                <div class="info-label">Event Date</div>
                                <div class="info-value"><?= date('F d, Y', strtotime($booking['EventDate'])) ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="bi bi-tag"></i>
                            <div>
                                <div class="info-label">Event Type</div>
                                <div class="info-value"><?= htmlspecialchars($booking['EventType']) ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="bi bi-geo-alt"></i>
                            <div>
                                <div class="info-label">Location</div>
                                <div class="info-value"><?= htmlspecialchars($booking['EventLocation']) ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="bi bi-people"></i>
                            <div>
                                <div class="info-label">Number of Guests</div>
                                <div class="info-value"><?= number_format($booking['NumberOfGuests']) ?> pax</div>
                            </div>
                        </div>
                    </div>

                    <div class="booking-details">
                        <h6><i class="bi bi-list-check me-2"></i>Booking Details</h6>
                        
                        <div class="detail-row">
                            <span class="detail-label">Date Booked:</span>
                            <span class="detail-value"><?= date('F d, Y', strtotime($booking['DateBooked'])) ?></span>
                        </div>

                        <?php if ($booking['PackageName']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Package:</span>
                                <span class="detail-value"><?= htmlspecialchars($booking['PackageName']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($booking['MenuItems']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Menu Items:</span>
                                <span class="detail-value"><?= htmlspecialchars($booking['MenuItems']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($booking['SpecialRequests']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Special Requests:</span>
                                <span class="detail-value"><?= nl2br(htmlspecialchars($booking['SpecialRequests'])) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="total-amount">
                            <span>Total Amount:</span>
                            <span>₱<?= number_format($booking['TotalAmount'], 2) ?></span>
                        </div>
                        
                        <!-- Payment Status Section -->
                        <?php if ($booking['PaymentStatus']): ?>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-gray);">
                                <div class="detail-row">
                                    <span class="detail-label">
                                        <i class="bi bi-wallet2 me-1"></i>Payment Status:
                                    </span>
                                    <span class="detail-value">
                                        <?php 
                                        $paymentStatusClasses = [
                                            'Pending Payment' => 'badge bg-warning text-dark',
                                            'Processing' => 'badge bg-info',
                                            'Paid' => 'badge bg-success',
                                            'Failed' => 'badge bg-danger'
                                        ];
                                        $statusClass = $paymentStatusClasses[$booking['PaymentStatus']] ?? 'badge bg-secondary';
                                        ?>
                                        <span class="<?= $statusClass ?>" style="font-size: 0.9rem; padding: 6px 12px;">
                                            <?php if ($booking['PaymentStatus'] === 'Paid'): ?>
                                                <i class="bi bi-check-circle me-1"></i>
                                            <?php elseif ($booking['PaymentStatus'] === 'Processing'): ?>
                                                <i class="bi bi-hourglass-split me-1"></i>
                                            <?php elseif ($booking['PaymentStatus'] === 'Failed'): ?>
                                                <i class="bi bi-exclamation-circle me-1"></i>
                                            <?php else: ?>
                                                <i class="bi bi-clock me-1"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($booking['PaymentStatus']) ?>
                                        </span>
                                    </span>
                                </div>
                                
                                <?php if ($booking['PaymentMethod']): ?>
                                    <div class="detail-row" style="margin-top: 8px;">
                                        <span class="detail-label">
                                            <i class="bi bi-credit-card me-1"></i>Method:
                                        </span>
                                        <span class="detail-value"><?= htmlspecialchars($booking['PaymentMethod']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($booking['PaymentDate']): ?>
                                    <div class="detail-row" style="margin-top: 8px;">
                                        <span class="detail-label">
                                            <i class="bi bi-calendar me-1"></i>Paid On:
                                        </span>
                                        <span class="detail-value"><?= date('F d, Y @ g:i A', strtotime($booking['PaymentDate'])) ?></span>
                                    </div>
                                <?php endif; ?>
                                    <!-- ADD YOUR BANK REFERENCE FIELDS HERE -->
                                    <?php if ($booking['BankReferenceNumber']): ?>
                                        <div class="detail-row" style="margin-top: 8px;">
                                            <span class="detail-label">
                                                <i class="bi bi-hash me-1"></i>Bank Reference:
                                            </span>
                                            <span class="detail-value" style="font-family: monospace;"><?= htmlspecialchars($booking['BankReferenceNumber']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($booking['BankSenderName']): ?>
                                        <div class="detail-row" style="margin-top: 8px;">
                                            <span class="detail-label">
                                                <i class="bi bi-person me-1"></i>Sender Name:
                                            </span>
                                            <span class="detail-value"><?= htmlspecialchars($booking['BankSenderName']) ?></span>
                                        </div>
                                    <?php endif; ?>

                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="action-buttons">
                        <?php if ($booking['Status'] === 'Pending'): ?>
                            <button class="btn btn-action btn-cancel" onclick="cancelBooking(<?= $booking['BookingID'] ?>)">
                                <i class="bi bi-x-circle me-2"></i>Cancel Booking
                            </button>
                        <?php elseif ($booking['Status'] === 'Confirmed'): ?>
                            <?php if ($booking['PaymentStatus'] === 'Paid'): ?>
                                <div class="alert alert-success mb-0" style="border-radius: 8px; padding: 12px 16px;">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>Payment Confirmed</strong>
                                    <br><small>Your payment has been received. Thank you!</small>
                                </div>
                            <?php else: ?>
                                <button class="btn btn-action btn-pay" 
                                    data-booking-id="<?= $booking['BookingID'] ?>"
                                    data-amount="<?= $booking['TotalAmount'] ?>"
                                    data-event-date="<?= $booking['EventDate'] ?>"
                                    data-quotation-id="<?= $booking['QuotationID'] ?>"
                                    data-quotation-price="<?= $booking['QuotationPrice'] ?>"
                                    data-special-request-price="<?= $booking['SpecialRequestPrice'] ?? 0 ?>"
                                    data-special-request-items="<?= htmlspecialchars($booking['SpecialRequestItems'] ?? '[]') ?>"
                                    data-quotation-details="<?= htmlspecialchars($booking['QuotationDetails'] ?? '') ?>"
                                    onclick="showPaymentModalSafe(this)">
                                <i class="bi bi-credit-card me-2"></i>Pay Now
                            </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>

            <div class="text-center mt-4">
                <a href="manage_booking.php" class="btn btn-primary-custom me-2">
                    <i class="bi bi-plus-circle me-2"></i>New Booking
                </a>
                <a href="index.php" class="btn btn-back">
                    <i class="bi bi-house me-2"></i>Back to Home
                </a>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <h3>No Bookings Yet</h3>
                <p>You haven't made any bookings yet. Start planning your event today!</p>
                <a href="manage_booking.php" class="btn btn-primary-custom">
                    <i class="bi bi-plus-circle me-2"></i>Make Your First Booking
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>

// Show payment modal with quotation details
function showPaymentModal(bookingId, amount, quotationId, quotationPrice, quotationDetails, specialRequestPrice, specialRequestItems) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('paymentModalWithQuotation');
    
    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'paymentModalWithQuotation';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 8px 32px rgba(0,0,0,0.12);">
                    <div class="modal-header" style="background: linear-gradient(135deg, #000000, #212529); color: white; border-radius: 16px 16px 0 0; padding: 20px 24px; border: none;">
                        <h5 class="modal-title" style="font-size: 1.1rem; font-weight: 600;"><i class="bi bi-receipt me-2"></i>Payment</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 20px 24px;">
                        <!-- Quotation Section -->
                        <div id="quotationSection" class="mb-3" style="display: none;">
                            <div class="alert alert-success" style="border-radius: 12px; border-left: 4px solid #28a745; padding: 16px; margin-bottom: 0;">
                                <h6 class="mb-3" style="font-size: 0.95rem; font-weight: 600;">
                                    <i class="bi bi-check-circle me-2"></i>Approved Quotation
                                </h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <strong style="font-size: 0.85rem;">Quotation ID:</strong> 
                                        <span style="font-size: 0.85rem;">#<span id="modalQuotationId"></span></span>
                                    </div>
                                    <div class="col-6">
                                        <strong style="font-size: 0.85rem;">Status:</strong> 
                                        <span class="badge bg-success" style="font-size: 0.75rem;">Approved</span>
                                    </div>
                                    
                                    <!-- Price Breakdown -->
                                    <div class="col-12 mt-3 pt-2 border-top">
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong style="font-size: 0.85rem;">Base Price:</strong>
                                            <span style="font-size: 0.85rem;">₱<span id="modalBasePriceValue">0.00</span></span>
                                        </div>
                                        
                                        <!-- Special Request Items List -->
                                        <div id="specialRequestItemsList"></div>
                                        
                                        <div class="d-flex justify-content-between" style="border-top: 1px solid rgba(0,0,0,0.1); padding-top: 8px;">
                                            <strong style="font-size: 0.95rem;">Total Amount:</strong>
                                            <span style="font-size: 1.2rem; color: #28a745; font-weight: 700;">₱<span id="modalQuotationPrice">0.00</span></span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12 mt-2" id="quotationDetailsContainer">
                                        <strong style="font-size: 0.85rem;">Details:</strong>
                                        <p class="mb-0 mt-1" id="modalQuotationDetails" style="white-space: pre-wrap; font-size: 0.85rem; background: rgba(255,255,255,0.5); padding: 10px; border-radius: 8px;"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Booking Info -->
                        <div style="background: #e7f3ff; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px; border-left: 4px solid #007dfe;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="color: #0056b3; font-size: 0.9rem; font-weight: 600;">Booking #<span id="modalBookingId"></span></span>
                                <span style="color: #0056b3; font-size: 1.2rem; font-weight: 700;">₱<span id="modalAmount"></span></span>
                            </div>
                        </div>
                        
                        <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-dark); margin-bottom: 12px;">
                            <i class="bi bi-credit-card me-1"></i>Select Payment Method
                        </div>

                        <input type="hidden" id="paymentBookingId">
                        <input type="hidden" id="paymentAmount">

                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 10px;">
                            <div class="payment-option-compact" id="cashOption">
                                <i class="bi bi-cash-stack" style="font-size: 1.5rem; color: #28a745;"></i>
                                <div style="flex: 1;">
                                    <strong style="font-size: 0.95rem;">Cash</strong>
                                    <p class="mb-0 text-muted" style="font-size: 0.75rem;">Pay on event day</p>
                                </div>
                                <i class="bi bi-chevron-right" style="color: #ccc; font-size: 0.9rem;"></i>
                            </div>

                            <div class="payment-option-compact" id="gcashOption">
                                <i class="bi bi-phone-fill" style="font-size: 1.5rem; color: #007dfe;"></i>
                                <div style="flex: 1;">
                                    <strong style="font-size: 0.95rem;">GCash</strong>
                                    <p class="mb-0 text-muted" style="font-size: 0.75rem;">Scan QR instantly</p>
                                </div>
                                <i class="bi bi-chevron-right" style="color: #ccc; font-size: 0.9rem;"></i>
                            </div>
                        </div>

                        <div class="payment-option-compact" id="bankOption">
                            <i class="bi bi-bank" style="font-size: 1.5rem; color: #6f42c1;"></i>
                            <div style="flex: 1;">
                                <strong style="font-size: 0.95rem;">Bank Transfer</strong>
                                <p class="mb-0 text-muted" style="font-size: 0.75rem;">Online banking</p>
                            </div>
                            <i class="bi bi-chevron-right" style="color: #ccc; font-size: 0.9rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Populate modal data
    document.getElementById('paymentBookingId').value = bookingId;
    document.getElementById('paymentAmount').value = amount;
    document.getElementById('modalBookingId').textContent = bookingId;
    document.getElementById('modalAmount').textContent = parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2});
    
    // Show quotation section if available
    const quotationSection = document.getElementById('quotationSection');
    if (quotationId && quotationPrice) {
        document.getElementById('modalQuotationId').textContent = quotationId;
        document.getElementById('modalQuotationPrice').textContent = parseFloat(quotationPrice).toLocaleString('en-PH', {minimumFractionDigits: 2});
        
        // Display base price
        const basePrice = parseFloat(quotationPrice) || 0;
        const specialPrice = parseFloat(specialRequestPrice) || 0;
        document.getElementById('modalBasePriceValue').textContent = basePrice.toLocaleString('en-PH', {minimumFractionDigits: 2});
        
        // Display special request items if they exist
        const itemsList = document.getElementById('specialRequestItemsList');
        itemsList.innerHTML = '';
        
        try {
            const items = JSON.parse(specialRequestItems || '[]');
            if (items.length > 0) {
                items.forEach(item => {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'd-flex justify-content-between mb-2';
                    itemDiv.innerHTML = `
                        <strong style="font-size: 0.85rem;">• ${item.name}:</strong>
                        <span style="font-size: 0.85rem;">₱${parseFloat(item.price).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                    `;
                    itemsList.appendChild(itemDiv);
                });
                // Add separator
                const separator = document.createElement('div');
                separator.style.borderTop = '1px solid rgba(0,0,0,0.1)';
                separator.style.marginTop = '8px';
                separator.style.marginBottom = '8px';
                itemsList.appendChild(separator);
            }
        } catch (e) {
            console.error('Error parsing special request items:', e);
        }
        
        // Always show quotation details container
        const quotationDetailsContainer = document.getElementById('quotationDetailsContainer');
        const quotationDetailsElement = document.getElementById('modalQuotationDetails');
        
        if (quotationDetails && quotationDetails.trim() !== '') {
            quotationDetailsElement.textContent = quotationDetails;
        } else {
            quotationDetailsElement.textContent = 'No additional details provided.';
        }
        
        quotationSection.style.display = 'block';
    } else {
        quotationSection.style.display = 'none';
    }
    
    // Add click handlers to payment options
    document.getElementById('cashOption').onclick = function() {
        selectPaymentMethodFromModal(bookingId, 'Cash', amount);
    };
    
    document.getElementById('gcashOption').onclick = function() {
        selectPaymentMethodFromModal(bookingId, 'GCash', amount);
    };
    
    document.getElementById('bankOption').onclick = function() {
        selectPaymentMethodFromModal(bookingId, 'Bank Transfer', amount);
    };
    
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}

// Add this NEW function for safe modal opening
function showPaymentModalSafe(button) {
    const bookingId = button.dataset.bookingId;
    const amount = button.dataset.amount;
    const quotationId = button.dataset.quotationId || null;
    const quotationPrice = button.dataset.quotationPrice || null;
    const specialRequestPrice = button.dataset.specialRequestPrice || 0;
    const specialRequestItems = button.dataset.specialRequestItems || '[]';
    const quotationDetails = button.dataset.quotationDetails || null;
    
    showPaymentModal(bookingId, amount, quotationId, quotationPrice, quotationDetails, specialRequestPrice, specialRequestItems);
}

// Format card number with spaces
function formatCardNumber(input) {
    let value = input.value.replace(/\D/g, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    input.value = formattedValue;
}

// Handle payment selection from modal (closes modal first)
function selectPaymentMethodFromModal(bookingId, method, amount) {
    // Close the payment modal first - FIX THE ID HERE
    const paymentModal = document.querySelector('#paymentModalWithQuotation');
    if (paymentModal) {
        const bootstrapModal = bootstrap.Modal.getInstance(paymentModal);
        if (bootstrapModal) {
            bootstrapModal.hide();
        }
    }
    
    // Small delay to let modal close animation finish
    setTimeout(() => {
        if (method === 'Bank Transfer') {
            showBankDetailsModal(bookingId, method, amount);
        } else if (method === 'Cash') {
            showCashConfirmationModal(bookingId, method, amount);
        } else if (method === 'GCash') {
            showGCashConfirmationModal(bookingId, method, amount);
        }
    }, 300);
}

// Show Cash Confirmation Modal
function showCashConfirmationModal(bookingId, method, amount) {
    const existingModal = document.querySelector('.cash-confirmation-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Calculate days until event - get event date from button data
    const button = document.querySelector(`[data-booking-id="${bookingId}"]`);
    const eventDate = button ? button.dataset.eventDate : null;
    let daysWarning = '';
    
    if (eventDate) {
        const today = new Date();
        const eventDateObj = new Date(eventDate);
        const daysUntilEvent = Math.ceil((eventDateObj - today) / (1000 * 60 * 60 * 24));
        
        if (daysUntilEvent <= 3 && daysUntilEvent > 0) {
            daysWarning = `
                <div class="alert alert-danger" style="border-radius: 10px; margin-bottom: 15px;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>⚠️ Important Notice:</strong><br>
                    Payment must be made <strong>at least 3 days before</strong> your event date, or the booking will be cancelled.
                    Your event is in <strong>${daysUntilEvent} day${daysUntilEvent !== 1 ? 's' : ''}</strong>.
                </div>
            `;
        }
    }
    
    const modal = document.createElement('div');
    modal.className = 'cash-confirmation-modal';
    modal.innerHTML = `
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px; border: none;">
                    <div class="modal-header" style="background: var(--primary-dark); color: white; border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title"><i class="bi bi-cash-stack me-2"></i>Cash Payment Confirmation</h5>
                        <button type="button" class="btn-close btn-close-white" onclick="closeCashModal()"></button>
                    </div>
                    <div class="modal-body" style="padding: 25px;">
                        <div class="text-center mb-4">
                            <i class="bi bi-cash-stack" style="font-size: 4rem; color: #28a745;"></i>
                        </div>
                        
                        ${daysWarning}
                        
                        <div class="alert alert-info" style="border-radius: 10px;">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Booking ID:</strong> #${bookingId}<br>
                            <strong>Amount:</strong> ₱${parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                        </div>
                        
                        <div class="alert alert-success" style="border-radius: 10px;">
                            <h6><i class="bi bi-check-circle me-2"></i>Cash Payment Instructions</h6>
                            <ul class="mb-0">
                                <li>Payment will be collected before the event starts</li>
                                <li>A receipt will be provided upon payment</li>
                                <li><strong>Payment must be made at least 3 days before your event or booking will be cancelled</strong></li>
                            </ul>
                        </div>
                        
                        <p class="text-center text-muted mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Please ensure you have the full amount ready on your event date.
                        </p>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 20px;">
                        <button class="btn btn-secondary" onclick="closeCashModal()" style="border-radius: 8px; padding: 10px 20px;">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                        <button class="btn btn-success" onclick="submitCashPayment(${bookingId}, '${method}')" 
                                style="border: none; border-radius: 8px; padding: 10px 20px;">
                            <i class="bi bi-check-circle me-1"></i>Confirm Cash Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Close Cash modal
function closeCashModal() {
    const modal = document.querySelector('.cash-confirmation-modal');
    if (modal) {
        modal.remove();
    }
}

// Submit Cash payment
function submitCashPayment(bookingId, method) {
    const formData = {
        booking_id: bookingId,
        payment_method: method,
        client_id: <?= getClientID() ?>
    };
    
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
    submitBtn.disabled = true;
    
// For submitCashPayment function
fetch('./web/api/bookings/index.php?action=save_payment_method', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'include',
    body: JSON.stringify(formData)
})
    .then(response => response.json())
    .then(data => {
        closeCashModal();
        if (data.success) {
            alert('✓ Cash payment method confirmed!\n\nPlease prepare the exact amount on your event day.');
            location.reload();
        } else {
            alert(data.message || 'Failed to update payment method');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Show GCash Confirmation Modal
function showGCashConfirmationModal(bookingId, method, amount) {
    const existingModal = document.querySelector('.gcash-confirmation-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.className = 'gcash-confirmation-modal';
    modal.innerHTML = `
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px; border: none;">
                    <div class="modal-header" style="background: #007dfe; color: white; border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title"><i class="bi bi-phone me-2"></i>GCash Payment Confirmation</h5>
                        <button type="button" class="btn-close btn-close-white" onclick="closeGCashConfirmationModal()"></button>
                    </div>
                    <div class="modal-body" style="padding: 25px;">
                        <div class="text-center mb-4">
                            <i class="bi bi-phone-fill" style="font-size: 4rem; color: #007dfe;"></i>
                        </div>
                        
                        <div class="alert alert-info" style="border-radius: 10px;">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Booking ID:</strong> #${bookingId}<br>
                            <strong>Amount:</strong> ₱${parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                        </div>
                        
                        <div class="alert alert-primary" style="border-radius: 10px; background: #e7f3ff; border-color: #007dfe;">
                            <h6><i class="bi bi-qr-code me-2"></i>GCash Payment Process</h6>
                            <ol class="mb-0">
                                <li>Click "Proceed to GCash QR" below</li>
                                <li>Scan the QR code with your GCash app</li>
                                <li>Complete the payment in GCash</li>
                                <li>Save your reference number for verification</li>
                            </ol>
                        </div>
                        
                        <p class="text-center text-muted mb-0">
                            <i class="bi bi-shield-check me-1"></i>
                            Your QR code will be displayed after confirmation.
                        </p>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 20px;">
                        <button class="btn btn-secondary" onclick="closeGCashConfirmationModal()" style="border-radius: 8px; padding: 10px 20px;">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                        <button class="btn" onclick="submitGCashPayment(${bookingId}, '${method}', ${amount})" 
                                style="background: #007dfe; color: white; border: none; border-radius: 8px; padding: 10px 20px;">
                            <i class="bi bi-qr-code me-1"></i>Proceed to GCash QR
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Close GCash Confirmation modal
function closeGCashConfirmationModal() {
    const modal = document.querySelector('.gcash-confirmation-modal');
    if (modal) {
        modal.remove();
    }
}

// Submit GCash payment
function submitGCashPayment(bookingId, method, amount) {
    const formData = {
        booking_id: bookingId,
        payment_method: method,
        client_id: <?= getClientID() ?>
    };
    
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
    submitBtn.disabled = true;
    
fetch('./web/api/bookings/index.php?action=save_payment_method', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'include',
    body: JSON.stringify(formData)
})
    .then(response => response.json())
    .then(data => {
        closeGCashConfirmationModal();
        
        if (data.success) {
            // Show GCash QR modal directly without page reload
            showGCashModal(amount, bookingId);
        } else {
            alert(data.message || 'Failed to update payment method');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Show bank transfer details modal
function showBankDetailsModal(bookingId, method, amount) {
    const existingModal = document.querySelector('.bank-details-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.className = 'bank-details-modal';
    modal.innerHTML = `
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px; border: none;">
                    <div class="modal-header" style="background: var(--primary-dark); color: white; border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title"><i class="bi bi-bank me-2"></i>Bank Transfer Details</h5>
                        <button type="button" class="btn-close btn-close-white" onclick="closeBankModal()"></button>
                    </div>
                    <div class="modal-body" style="padding: 25px;">
                        <div class="alert alert-info" style="border-radius: 10px;">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Booking ID:</strong> #${bookingId}<br>
                            <strong>Amount:</strong> ₱${parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600;">
                                <i class="bi bi-credit-card me-1"></i>Card Number <span style="color: red;">*</span>
                            </label>
                            <input type="text" id="modalCardNumber" class="form-control" 
                                   placeholder="1234 5678 9012 3456" maxlength="19" 
                                   oninput="formatCardNumber(this)"
                                   style="border-radius: 10px; padding: 12px;">
                            <small class="text-muted">Enter 16-digit card number</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600;">
                                <i class="bi bi-person me-1"></i>Cardholder Name <span style="color: red;">*</span>
                            </label>
                            <input type="text" id="modalCardholderName" class="form-control" 
                                   placeholder="JOHN DOE" 
                                   style="text-transform: uppercase; border-radius: 10px; padding: 12px;">
                            <small class="text-muted">Name as it appears on card</small>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 20px;">
                        <button class="btn btn-secondary" onclick="closeBankModal()" style="border-radius: 8px; padding: 10px 20px;">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                        <button class="btn btn-primary" onclick="submitBankTransfer(${bookingId}, '${method}')" 
                                style="background: var(--primary-dark); border: none; border-radius: 8px; padding: 10px 20px;">
                            <i class="bi bi-check-circle me-1"></i>Confirm Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    setTimeout(() => {
        document.getElementById('modalCardNumber').focus();
    }, 100);
}

// Close bank transfer modal
function closeBankModal() {
    const modal = document.querySelector('.bank-details-modal');
    if (modal) {
        modal.remove();
    }
}

// Submit bank transfer payment
function submitBankTransfer(bookingId, method) {
    const cardNumber = document.getElementById('modalCardNumber').value.replace(/\s/g, '');
    const cardholderName = document.getElementById('modalCardholderName').value.trim();
    
    if (!cardNumber || !cardholderName) {
        alert('Please fill in all card details');
        return;
    }
    
    if (cardNumber.length !== 16) {
        alert('Please enter a valid 16-digit card number');
        return;
    }
    
    if (cardholderName.length < 3) {
        alert('Please enter a valid cardholder name');
        return;
    }
    
    const formData = {
        booking_id: bookingId,
        payment_method: method,
        client_id: <?= getClientID() ?>
    };
    
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
    submitBtn.disabled = true;
    
    fetch('./web/api/bookings/index.php?action=save_payment_method', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include',
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        closeBankModal();
        
        if (data.success) {
            // Show bank reference modal instead of just reloading
            showBankReferenceModal(bookingId, cardNumber, cardholderName);
        } else {
            alert(data.message || 'Failed to update payment method');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// NEW FUNCTION: Show Bank Reference Modal (similar to GCash)
function showBankReferenceModal(bookingId, cardNumber, senderName) {
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
                            <i class="bi bi-receipt me-2"></i>Booking ID: #<span id="bankBookingId"></span>
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
    document.getElementById('bankBookingId').textContent = bookingId;
    document.getElementById('bankSenderName').textContent = senderName;
    document.getElementById('bankCardLast4').textContent = cardNumber.slice(-4);
    document.getElementById('bankRefNumber').value = '';
    
    // Store data for submission
    window.currentBankTransfer = {
        bookingId: bookingId,
        cardNumber: cardNumber,
        senderName: senderName
    };
    
    // Show modal
    const bankModal = document.getElementById('bankReferenceModal');
    bankModal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Close Bank Reference Modal
function closeBankReferenceModal() {
    const modal = document.getElementById('bankReferenceModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        location.reload();
    }
}

// Complete Bank Transfer with Reference
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
    formData.append('action', 'save_bank_reference');
    formData.append('booking_id', window.currentBankTransfer.bookingId);
    formData.append('bank_reference', refNumber);
    formData.append('sender_name', window.currentBankTransfer.senderName);
    formData.append('payment_method', 'Bank Transfer');
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
    btn.disabled = true;
    
    fetch('process_bank_reference.php', {
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

// Show GCash QR modal
function showGCashModal(amount, bookingId) {
    const modal = document.getElementById('gcashModal');
    if (!modal) {
        console.error('GCash modal not found');
        alert('Payment method updated! Please refresh the page.');
        location.reload();
        return;
    }
    
    const amountDisplay = document.getElementById('gcashAmount');
    const bookingIdDisplay = document.getElementById('gcashBookingId');
    const qrCode = document.getElementById('gcashQRCode');
    
    if (amountDisplay) {
        amountDisplay.textContent = parseFloat(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    if (bookingIdDisplay) {
        bookingIdDisplay.textContent = bookingId;
    }
    
    if (qrCode) {
        qrCode.src = 'gcash_qr.jpg';
    }
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Close GCash modal
function closeGCashModal() {
    const modal = document.getElementById('gcashModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        location.reload();
    }
}

// Update the completeGCashPayment function
function completeGCashPayment() {
    const refNumber = document.getElementById('gcashRefNumber').value.trim();
    const bookingIdSpan = document.getElementById('gcashBookingId');
    const bookingId = bookingIdSpan ? bookingIdSpan.textContent : '';
    
    if (!refNumber) {
        alert('Please enter your GCash reference number to proceed.');
        return;
    }
    
    if (!bookingId) {
        alert('Error: Booking ID not found. Please refresh and try again.');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'save_gcash_reference');
    formData.append('booking_id', bookingId);
    formData.append('gcash_reference', refNumber);
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
    btn.disabled = true;
    
    fetch('process_gcash_reference.php', {
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

// Close GCash modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('gcashModal');
    if (event.target == modal) {
        closeGCashModal();
    }
}

// Auto-show GCash modal if payment was processed
<?php if (isset($_SESSION['show_gcash_modal']) && $_SESSION['show_gcash_modal']): ?>
document.addEventListener('DOMContentLoaded', function() {
    showGCashModal(
        <?= $_SESSION['gcash_amount'] ?? 0 ?>, 
        '<?= $_SESSION['gcash_booking_id'] ?? '' ?>'
    );
    
    <?php 
    unset($_SESSION['show_gcash_modal']);
    unset($_SESSION['gcash_amount']);
    unset($_SESSION['gcash_booking_id']);
    ?>
});
<?php endif; ?>

// Cancel booking function
function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?\n\nThis action cannot be undone and the booking will be permanently cancelled.')) {
        window.location.href = `cancel_booking.php?id=${bookingId}`;
    }
}
</script>

    <style>
    .payment-option-modal {
        border: 2px solid var(--border-gray);
        border-radius: 15px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
    }

    .payment-option-modal:hover {
        border-color: var(--primary-dark);
        background: var(--light-gray);
        transform: translateX(5px);
    }

    .payment-option-modal i {
        color: var(--primary-dark);
    }

    .payment-option-compact {
    border: 1.5px solid #e0e0e0;
    border-radius: 12px;
    padding: 12px 14px;
    cursor: pointer;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    background: white;
}

    .payment-option-compact:hover {
        border-color: var(--primary-dark);
        background: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .payment-option-compact:active {
        transform: translateY(0px);
    }

    </style>

    <!-- GCash QR Code Modal -->
    <div id="gcashModal" class="gcash-modal" style="display: none;">
        <div class="gcash-modal-content">
            <span class="close-modal" onclick="closeGCashModal()">&times;</span>
            <div class="gcash-logo">
                <i class="bi bi-phone-fill"></i>
            </div>
            <h2 style="color: var(--primary-dark); font-weight: 700; margin-bottom: 8px; font-size: 1.5rem;">GCash Payment</h2>
            <p style="color: var(--medium-gray); margin-bottom: 15px; font-size: 0.95rem;">Scan the QR code below to complete your payment</p>
            
            <div class="qr-code-container">
                <img id="gcashQRCode" src="gcash_qr.jpg" alt="GCash QR Code">
            </div>
            
            <div style="background: #f0f8ff; padding: 12px; border-radius: 10px; margin: 15px 0; font-size: 0.9rem;">
                <p style="margin: 0; color: var(--text-dark); font-weight: 600;">
                    <i class="bi bi-info-circle me-2"></i>Account: Ellen's Catering
                </p>
                <p style="margin: 5px 0 0 0; color: var(--text-dark); font-weight: 600;">
                    <i class="bi bi-receipt me-2"></i>Booking ID: #<span id="gcashBookingId"></span>
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

</body>
</html>