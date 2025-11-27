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
    $orderId = intval($_POST['order_id']);
    $bankReference = trim($_POST['bank_reference']);
    $senderName = trim($_POST['sender_name']);
    $clientId = getClientID();
    
    if (!$orderId || !$bankReference || !$senderName) {
        echo json_encode(['success' => false, 'message' => 'Missing required information']);
        exit;
    }
    
    if (strlen($bankReference) < 5 || strlen($bankReference) > 50) {
        echo json_encode(['success' => false, 'message' => 'Reference number must be between 5 and 50 characters']);
        exit;
    }
    
    if (strlen($senderName) < 3 || strlen($senderName) > 100) {
        echo json_encode(['success' => false, 'message' => 'Sender name must be between 3 and 100 characters']);
        exit;
    }
    
    $conn = getDB();
    
    // Verify the order belongs to the client
    $verifyStmt = $conn->prepare("
        SELECT co.OrderID, co.BookingID, co.Status 
        FROM cart_orders co 
        WHERE co.OrderID = ? AND co.ClientID = ?
    ");
    $verifyStmt->bind_param("ii", $orderId, $clientId);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found or unauthorized']);
        exit;
    }
    
    $order = $result->fetch_assoc();
    $bookingId = $order['BookingID'];
    $verifyStmt->close();
    
    if ($order['Status'] === 'Cancelled') {
        echo json_encode(['success' => false, 'message' => 'Cannot add payment details to cancelled order']);
        exit;
    }
    
    // Update the booking with bank transfer details
    $updateStmt = $conn->prepare("
        UPDATE booking 
        SET BankReferenceNumber = ?,
            BankSenderName = ?,
            PaymentMethod = 'Bank Transfer',
            PaymentStatus = 'Processing',
            PaymentDate = NOW()
        WHERE BookingID = ?
    ");
    $updateStmt->bind_param("ssi", $bankReference, $senderName, $bookingId);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        
        // Log activity
        if (function_exists('logActivity')) {
            logActivity($clientId, 'client', 'payment_reference_added', 
                "Added Bank Transfer reference for order #$orderId (Booking #$bookingId)");
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Bank transfer details saved successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save bank transfer details']);
    }
    $conn->close();
    
} catch (Exception $e) {
    error_log("Cart Bank Transfer Reference Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>