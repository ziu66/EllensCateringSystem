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
    $gcashReference = trim($_POST['gcash_reference']);
    $clientId = getClientID();
    
    if (!$orderId || !$gcashReference) {
        echo json_encode(['success' => false, 'message' => 'Missing required information']);
        exit;
    }
    
    // validate: digits only, length 5-20
    if (!preg_match('/^\d{5,20}$/', $gcashReference)) {
        echo json_encode(['success' => false, 'message' => 'Reference must be numeric and 5-20 digits long']);
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
        echo json_encode(['success' => false, 'message' => 'Cannot add payment reference to cancelled order']);
        exit;
    }
    
    // Update the booking with GCash reference
    $updateStmt = $conn->prepare("
        UPDATE booking 
        SET GCashReference = ?,
            PaymentMethod = 'GCash',
            PaymentStatus = 'Processing',
            PaymentDate = NOW()
        WHERE BookingID = ?
    ");
    $updateStmt->bind_param("si", $gcashReference, $bookingId);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        
        // Log activity
        if (function_exists('logActivity')) {
            logActivity($clientId, 'client', 'payment_reference_added', 
                "Added GCash reference for order #$orderId (Booking #$bookingId)");
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'GCash reference number saved successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save reference number']);
    }
    $conn->close();
    
} catch (Exception $e) {
    error_log("Cart GCash Reference Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>