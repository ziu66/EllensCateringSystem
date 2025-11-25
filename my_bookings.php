<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();
requireLogin();

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
    $verifyStmt = $conn->prepare("SELECT BookingID, SpecialRequests, TotalAmount FROM booking WHERE BookingID = ? AND ClientID = ?");
    $verifyStmt->bind_param("ii", $bookingID, $clientID);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    $verifyStmt->close();
    
    // Update SpecialRequests with payment method
    $currentRequests = $booking['SpecialRequests'];
    
    // Remove existing payment method line if present
    $currentRequests = preg_replace('/Payment Method:.*?\n/i', '', $currentRequests);
    
    // Build updated special requests
    $updatedRequests = "Payment Method: $paymentMethod\n";
    
    // Handle Bank Transfer details
    if ($paymentMethod === 'Bank Transfer') {
        $cardNumber = sanitizeInput($_POST['card_number'] ?? '');
        $cardholderName = sanitizeInput($_POST['cardholder_name'] ?? '');
        
        if (empty($cardNumber) || empty($cardholderName)) {
            echo json_encode(['success' => false, 'message' => 'Please provide card details for bank transfer']);
            exit;
        }
        
        $cardNumber = preg_replace('/\s+/', '', $cardNumber);
        if (!preg_match('/^\d{16}$/', $cardNumber)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid 16-digit card number']);
            exit;
        }
        
        $updatedRequests .= "Bank Transfer Details:\n";
        $updatedRequests .= "Cardholder: $cardholderName\n";
        $updatedRequests .= "Card (last 4): " . substr($cardNumber, -4) . "\n";
    }
    
    $updatedRequests .= "\n" . trim($currentRequests);
    
    // Update booking
    $updateStmt = $conn->prepare("UPDATE booking SET SpecialRequests = ? WHERE BookingID = ? AND ClientID = ?");
    $updateStmt->bind_param("sii", $updatedRequests, $bookingID, $clientID);
    
    if ($updateStmt->execute()) {
        // Store for GCash modal if needed
        if ($paymentMethod === 'GCash') {
            $_SESSION['show_gcash_modal'] = true;
            $_SESSION['gcash_amount'] = $booking['TotalAmount'];
            $_SESSION['gcash_booking_id'] = $bookingID;
        }
        
        if (function_exists('logActivity')) {
            logActivity($clientID, 'client', 'payment_method_updated', "Updated payment method to $paymentMethod for booking #$bookingID");
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Payment method updated successfully',
            'show_gcash' => ($paymentMethod === 'GCash'),
            'total_amount' => $booking['TotalAmount'],
            'booking_id' => $bookingID
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update payment method']);
    }
    
    $updateStmt->close();
    exit;
}

$conn = getDB();
$client_id = getClientID();

// Get messages from session (for cancel booking feedback)
$message = isset($_SESSION['booking_message']) ? $_SESSION['booking_message'] : '';
$messageType = isset($_SESSION['booking_message_type']) ? $_SESSION['booking_message_type'] : '';
unset($_SESSION['booking_message'], $_SESSION['booking_message_type']);

// Fetch all bookings for the client
$bookingsQuery = "
    SELECT 
        b.*,
        p.PackageName,
        p.PackPrice,
        GROUP_CONCAT(DISTINCT m.DishName SEPARATOR ', ') AS MenuItems
    FROM booking b
    LEFT JOIN booking_package bp ON b.BookingID = bp.BookingID
    LEFT JOIN package p ON bp.PackageID = p.PackageID
    LEFT JOIN booking_menu bm ON b.BookingID = bm.BookingID
    LEFT JOIN menu m ON bm.MenuID = m.MenuID
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
        .gcash-modal {
            display: none;
            position: fixed;
            z-index: 99999;  /* ← SUPER HIGH z-index */
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
            position: relative;  /* ← ADD THIS */
            z-index: 100000;    /* ← ADD THIS */
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
                    </div>

                    <div class="action-buttons">
                        <?php if ($booking['Status'] === 'Pending'): ?>
                            <button class="btn btn-action btn-cancel" onclick="cancelBooking(<?= $booking['BookingID'] ?>)">
                                <i class="bi bi-x-circle me-2"></i>Cancel Booking
                            </button>
                        <?php elseif ($booking['Status'] === 'Confirmed'): ?>
                            <button class="btn btn-action btn-pay" onclick="showPaymentModal(<?= $booking['BookingID'] ?>, <?= $booking['TotalAmount'] ?>)">
                                <i class="bi bi-credit-card me-2"></i>Pay Now
                            </button>
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
        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?\n\nThis action cannot be undone and the booking will be permanently cancelled.')) {
                // Send cancellation request
                window.location.href = `cancel_booking.php?id=${bookingId}`;
            }
        }
    </script>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-header" style="background: var(--primary-dark); color: white; border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title"><i class="bi bi-credit-card me-2"></i>Select Payment Method</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="paymentBookingId">
                    <input type="hidden" id="paymentAmount">
                    
                    <div class="payment-option-modal mb-3" id="cashOption">
                        <i class="bi bi-cash-stack me-3" style="font-size: 2rem;"></i>
                        <div>
                            <strong>Cash</strong>
                            <p class="mb-0 text-muted">Pay with cash on the event day</p>
                        </div>
                    </div>

                    <div class="payment-option-modal mb-3" id="gcashOption">
                        <i class="bi bi-phone me-3" style="font-size: 2rem;"></i>
                        <div>
                            <strong>GCash</strong>
                            <p class="mb-0 text-muted">Scan QR code to pay instantly</p>
                        </div>
                    </div>

                    <div class="payment-option-modal" id="bankOption">
                        <i class="bi bi-bank me-3" style="font-size: 2rem;"></i>
                        <div>
                            <strong>Bank Transfer</strong>
                            <p class="mb-0 text-muted">Transfer via online banking</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    </style>

<script>
// Show payment modal
function showPaymentModal(bookingId, amount) {
    document.getElementById('paymentBookingId').value = bookingId;
    document.getElementById('paymentAmount').value = amount;
    
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
    
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}

// Format card number with spaces
function formatCardNumber(input) {
    let value = input.value.replace(/\D/g, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    input.value = formattedValue;
}

// Main payment method selection handler
function selectPaymentMethod(bookingId, method, amount) {
    // Bank Transfer - show details modal first
    if (method === 'Bank Transfer') {
        showBankDetailsModal(bookingId, method, amount);
        return;
    }
    
    // Cash - show confirmation dialog
    if (method === 'Cash') {
        if (!confirm(`Confirm payment with Cash?\n\nBooking ID: #${bookingId}\nAmount: ₱${parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}`)) {
            return;
        }
    }
    
    // GCash - NO confirmation, proceed directly to process
    // (the GCash QR modal itself serves as the confirmation)
    
    const formData = new FormData();
    formData.append('update_payment_method', '1');
    formData.append('booking_id', bookingId);
    formData.append('payment_method', method);
    
    // Show loading state if it's a clickable element
    let button = null;
    if (event && event.target) {
        button = event.target.closest('.payment-option-modal') || event.target;
        const originalHTML = button.innerHTML;
        button.innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split"></i> Processing...</div>';
        button.style.pointerEvents = 'none';
    }
    
    fetch('my_bookings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.show_gcash) {
                // Show GCash modal immediately - NO alert
                showGCashModal(data.total_amount, data.booking_id);
            } else {
                // For Cash payment - show success message
                alert(data.message);
                location.reload();
            }
        } else {
            alert(data.message || 'Failed to update payment method');
            if (button) {
                button.innerHTML = originalHTML;
                button.style.pointerEvents = 'auto';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        if (button) {
            button.innerHTML = originalHTML;
            button.style.pointerEvents = 'auto';
        }
    });
}

// Handle payment selection from modal (closes modal first)
function selectPaymentMethodFromModal(bookingId, method, amount) {
    // Close the payment modal first
    const paymentModal = document.querySelector('#paymentModal');
    if (paymentModal) {
        const bootstrapModal = bootstrap.Modal.getInstance(paymentModal);
        if (bootstrapModal) {
            bootstrapModal.hide();
        }
    }
    
    // Small delay to let modal close animation finish
    setTimeout(() => {
        selectPaymentMethod(bookingId, method, amount);
    }, 300);
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
    
    const formData = new FormData();
    formData.append('update_payment_method', '1');
    formData.append('booking_id', bookingId);
    formData.append('payment_method', method);
    formData.append('card_number', cardNumber);
    formData.append('cardholder_name', cardholderName);
    
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
    submitBtn.disabled = true;
    
    fetch('my_bookings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        closeBankModal();
        if (data.success) {
            alert(data.message + '\n\nCardholder: ' + cardholderName + '\nCard ending in: ' + cardNumber.slice(-4));
            location.reload();
        } else {
            alert(data.message || 'Failed to update payment method');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
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

    <!-- GCash QR Code Modal -->
    <div id="gcashModal" class="gcash-modal" style="display: none;">
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
            
            <button onclick="closeGCashModal()" type="button" style="background: var(--primary-dark); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                <i class="bi bi-check-circle me-2"></i>I've Completed Payment
            </button>
        </div>
    </div>

</body>
</html>