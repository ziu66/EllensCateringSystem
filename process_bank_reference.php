<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $bookingId = intval($_POST['booking_id']);
    $bankReference = trim($_POST['bank_reference']);
    $senderName = trim($_POST['sender_name']);
    $clientId = getClientID();
    
    if (!$bookingId || !$bankReference || !$senderName) {
        echo json_encode(['success' => false, 'message' => 'Missing required information']);
        exit;
    }
    
    if (strlen($bankReference) < 5 || strlen($bankReference) > 50) {
        echo json_encode(['success' => false, 'message' => 'Invalid reference number format']);
        exit;
    }
    
    if (strlen($senderName) < 3 || strlen($senderName) > 100) {
        echo json_encode(['success' => false, 'message' => 'Invalid sender name']);
        exit;
    }
    
    $conn = getDB();
    
    $verifyStmt = $conn->prepare("SELECT BookingID, Status FROM booking WHERE BookingID = ? AND ClientID = ?");
    $verifyStmt->bind_param("ii", $bookingId, $clientId);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found or unauthorized']);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    $verifyStmt->close();
    
    if ($booking['Status'] === 'Cancelled') {
        echo json_encode(['success' => false, 'message' => 'Cannot add payment details to cancelled booking']);
        exit;
    }
    
    // ✅ FIX: Add PaymentMethod and PaymentDate
    $updateStmt = $conn->prepare("
        UPDATE booking 
        SET BankReferenceNumber = ?,
            BankSenderName = ?,
            PaymentMethod = 'Bank Transfer',
            PaymentStatus = 'Processing',
            PaymentDate = NOW()
        WHERE BookingID = ? AND ClientID = ?
    ");
    $updateStmt->bind_param("ssii", $bankReference, $senderName, $bookingId, $clientId);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        echo json_encode([
            'success' => true, 
            'message' => 'Bank transfer details saved successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save bank transfer details']);
    }
    $conn->close();
    
} catch (Exception $e) {
    error_log("Bank Transfer Reference Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>