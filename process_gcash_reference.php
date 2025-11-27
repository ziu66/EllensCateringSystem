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
    $gcashReference = trim($_POST['gcash_reference']);
    $clientId = getClientID();
    
    if (!$bookingId || !$gcashReference) {
        echo json_encode(['success' => false, 'message' => 'Missing required information']);
        exit;
    }
    
    if (strlen($gcashReference) < 5 || strlen($gcashReference) > 50) {
        echo json_encode(['success' => false, 'message' => 'Invalid reference number format']);
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
        echo json_encode(['success' => false, 'message' => 'Cannot add payment reference to cancelled booking']);
        exit;
    }
    
    // ✅ FIX: Use GCashReference (not GCashReferenceNumber)
    $updateStmt = $conn->prepare("
        UPDATE booking 
        SET GCashReference = ?,
            PaymentMethod = 'GCash',
            PaymentStatus = 'Processing',
            PaymentDate = NOW()
        WHERE BookingID = ? AND ClientID = ?
    ");
    $updateStmt->bind_param("sii", $gcashReference, $bookingId, $clientId);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        echo json_encode([
            'success' => true, 
            'message' => 'GCash reference number saved successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save reference number']);
    }
    $conn->close();
    
} catch (Exception $e) {
    error_log("GCash Reference Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>