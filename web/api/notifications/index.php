<?php
require_once '../../config/database.php';
require_once '../../includes/security.php';

startSecureSession();
requireLogin();

header('Content-Type: application/json');

// GET notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Check if user is admin
        if (!isAdmin()) {
            sendResponse(false, 'Unauthorized access', null, 403);
        }
        
        $conn = getDB();
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        
        $query = "
            SELECT 
                n.NotificationID,
                n.Type,
                n.Message,
                n.BookingID,
                n.ClientID,
                n.IsRead,
                n.CreatedAt,
                c.FirstName,
                c.LastName,
                b.EventType,
                b.EventDate
            FROM notifications n
            LEFT JOIN client c ON n.ClientID = c.ClientID
            LEFT JOIN booking b ON n.BookingID = b.BookingID
            WHERE 1=1
            ORDER BY n.CreatedAt DESC, n.IsRead ASC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Get total count
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications");
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get unread count
        $unreadStmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE IsRead = 0");
        $unreadStmt->execute();
        $unreadResult = $unreadStmt->get_result();
        $unread = $unreadResult->fetch_assoc()['unread'];
        $unreadStmt->close();
        
        sendResponse(true, 'Notifications retrieved', [
            'notifications' => $notifications,
            'total' => $total,
            'unread' => $unread
        ], 200);
        
    } catch (Exception $e) {
        error_log('Notifications GET error: ' . $e->getMessage());
        sendResponse(false, $e->getMessage(), null, 500);
    }
}

// Mark notification as read (PUT)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        // Check if user is admin
        if (!isAdmin()) {
            sendResponse(false, 'Unauthorized access', null, 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['notification_id'])) {
            sendResponse(false, 'Missing notification_id', null, 400);
        }
        
        $notificationID = intval($data['notification_id']);
        
        $conn = getDB();
        $stmt = $conn->prepare("UPDATE notifications SET IsRead = 1 WHERE NotificationID = ?");
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $notificationID);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update notification: ' . $stmt->error);
        }
        
        $stmt->close();
        
        sendResponse(true, 'Notification marked as read', null, 200);
        
    } catch (Exception $e) {
        error_log('Notifications PUT error: ' . $e->getMessage());
        sendResponse(false, $e->getMessage(), null, 500);
    }
}

// Mark all as read (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if user is admin
        if (!isAdmin()) {
            sendResponse(false, 'Unauthorized access', null, 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check if this is a mark all request
        if (isset($data['mark_all_read']) && $data['mark_all_read'] === true) {
            $conn = getDB();
            $stmt = $conn->prepare("UPDATE notifications SET IsRead = 1 WHERE IsRead = 0");
            
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update notifications: ' . $stmt->error);
            }
            
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            sendResponse(true, "Marked $affected notifications as read", null, 200);
        } else {
            sendResponse(false, 'Invalid request', null, 400);
        }
        
    } catch (Exception $e) {
        error_log('Notifications POST error: ' . $e->getMessage());
        sendResponse(false, $e->getMessage(), null, 500);
    }
}

// DELETE notification
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // Check if user is admin
        if (!isAdmin()) {
            sendResponse(false, 'Unauthorized access', null, 403);
        }
        
        parse_str(file_get_contents('php://input'), $data);
        
        if (!isset($data['notification_id'])) {
            sendResponse(false, 'Missing notification_id', null, 400);
        }
        
        $notificationID = intval($data['notification_id']);
        
        $conn = getDB();
        $stmt = $conn->prepare("DELETE FROM notifications WHERE NotificationID = ?");
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $notificationID);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete notification: ' . $stmt->error);
        }
        
        $stmt->close();
        
        sendResponse(true, 'Notification deleted', null, 200);
        
    } catch (Exception $e) {
        error_log('Notifications DELETE error: ' . $e->getMessage());
        sendResponse(false, $e->getMessage(), null, 500);
    }
}

function sendResponse($success, $message, $data, $code) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>