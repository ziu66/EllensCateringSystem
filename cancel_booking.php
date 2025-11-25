<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();
requireLogin();

$conn = getDB();
$client_id = getClientID();

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id <= 0) {
    $_SESSION['booking_message'] = 'Invalid booking ID.';
    $_SESSION['booking_message_type'] = 'danger';
    header('Location: my_bookings.php');  // ✅ Changed from view_bookings.php
    exit();
}

// Verify booking belongs to this client and is in Pending status
$verifyQuery = "SELECT BookingID, Status, EventDate FROM booking WHERE BookingID = ? AND ClientID = ?";
$stmt = $conn->prepare($verifyQuery);
$stmt->bind_param("ii", $booking_id, $client_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    $_SESSION['booking_message'] = 'Booking not found or you do not have permission to cancel it.';
    $_SESSION['booking_message_type'] = 'danger';
    header('Location: my_bookings.php');  // ✅ Changed from view_bookings.php
    exit();
}

// Check if booking can be cancelled (only Pending status)
if ($booking['Status'] !== 'Pending') {
    $_SESSION['booking_message'] = 'Only pending bookings can be cancelled. Current status: ' . htmlspecialchars($booking['Status']);
    $_SESSION['booking_message_type'] = 'warning';
    header('Location: my_bookings.php');  // ✅ Changed from view_bookings.php
    exit();
}

// Check if event date hasn't passed
if (strtotime($booking['EventDate']) < strtotime('today')) {
    $_SESSION['booking_message'] = 'Cannot cancel bookings for past dates.';
    $_SESSION['booking_message_type'] = 'danger';
    header('Location: my_bookings.php');  // ✅ Changed from view_bookings.php
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Update booking status to Cancelled
    $updateBooking = $conn->prepare("UPDATE booking SET Status = 'Cancelled' WHERE BookingID = ?");
    $updateBooking->bind_param("i", $booking_id);
    
    if (!$updateBooking->execute()) {
        throw new Exception("Failed to update booking status");
    }
    $updateBooking->close();
    
    // Update related quotation status (if exists)
    $updateQuotation = $conn->prepare("UPDATE quotation SET Status = 'Cancelled' WHERE BookingID = ?");
    $updateQuotation->bind_param("i", $booking_id);
    $updateQuotation->execute();
    $updateQuotation->close();
    
    // Log activity
    logActivity($client_id, 'client', 'booking_cancelled', "Cancelled booking #$booking_id");
    
    $conn->commit();
    
    $_SESSION['booking_message'] = "Booking #$booking_id has been successfully cancelled.";
    $_SESSION['booking_message_type'] = 'success';
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Booking cancellation error: " . $e->getMessage());
    
    $_SESSION['booking_message'] = 'An error occurred while cancelling your booking. Please try again or contact support.';
    $_SESSION['booking_message_type'] = 'danger';
}

header('Location: my_bookings.php');  // ✅ Changed from view_bookings.php
exit();
?>