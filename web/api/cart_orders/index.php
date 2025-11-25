<?php
/**
 * Cart Orders API
 * Handles all cart order-related operations for admin
 * Create this file at: admin/api/cart_orders/index.php
 */

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

require_once '../../../config/database.php';
require_once '../../middleware/auth.php';

requireAuth();

$conn = getDbConnection();

if (!$conn) {
    sendResponse(false, 'Database connection failed', null, 500);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetOrders($conn);
        break;
    case 'PUT':
        handleUpdateOrder($conn);
        break;
    default:
        sendResponse(false, 'Method not allowed', null, 405);
}

/**
 * GET: Retrieve cart orders with details
 */
function handleGetOrders($conn) {
    try {
        $orderID = $_GET['id'] ?? null;
        $status = $_GET['status'] ?? null;
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        
        $query = "SELECT 
                    co.OrderID,
                    co.ClientID,
                    co.BookingID,
                    co.QuotationID,
                    co.OrderDate,
                    co.TotalAmount,
                    co.Status,
                    co.Notes,
                    c.Name as ClientName,
                    c.Email as ClientEmail,
                    c.ContactNumber,
                    b.EventDate,
                    b.EventLocation,
                    q.EstimatedPrice as QuotationAmount,
                    q.Status as QuotationStatus
                  FROM cart_orders co
                  LEFT JOIN client c ON co.ClientID = c.ClientID
                  LEFT JOIN booking b ON co.BookingID = b.BookingID
                  LEFT JOIN quotation q ON co.QuotationID = q.QuotationID
                  WHERE 1=1";
        
        $params = [];
        
        if ($orderID) {
            $query .= " AND co.OrderID = :orderID";
            $params[':orderID'] = $orderID;
        }
        
        if ($status) {
            $query .= " AND co.Status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY co.OrderDate DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $orders = $stmt->fetchAll();
        
        // Get order items for each order
        foreach ($orders as &$order) {
            $itemsQuery = "SELECT * FROM cart_order_items WHERE OrderID = :orderID";
            $itemsStmt = $conn->prepare($itemsQuery);
            $itemsStmt->execute([':orderID' => $order['OrderID']]);
            $order['items'] = $itemsStmt->fetchAll();
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM cart_orders WHERE 1=1";
        if ($status) {
            $countQuery .= " AND Status = :status";
        }
        $stmtCount = $conn->prepare($countQuery);
        if ($status) {
            $stmtCount->bindValue(':status', $status);
        }
        $stmtCount->execute();
        $totalCount = $stmtCount->fetch()['total'] ?? 0;
        
        sendResponse(true, 'Orders retrieved successfully', [
            'orders' => $orders,
            'total' => $totalCount,
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ], 200);
        
    } catch (PDOException $e) {
        error_log("Get Orders Error: " . $e->getMessage());
        sendResponse(false, 'Error retrieving orders', null, 500);
    }
}

/**
 * PUT: Update order status
 */
function handleUpdateOrder($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['order_id'])) {
            sendResponse(false, 'Order ID is required', null, 400);
        }
        
        $orderID = (int)$data['order_id'];
        $status = $data['status'] ?? null;
        
        if ($status && !in_array($status, ['Pending', 'Confirmed', 'Completed', 'Cancelled'])) {
            sendResponse(false, 'Invalid status value', null, 400);
        }
        
        $query = "UPDATE cart_orders SET Status = :status WHERE OrderID = :orderID";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':status' => $status,
            ':orderID' => $orderID
        ]);
        
        // Also update related booking status
        $updateBooking = "UPDATE booking b 
                         INNER JOIN cart_orders co ON b.BookingID = co.BookingID 
                         SET b.Status = :status 
                         WHERE co.OrderID = :orderID";
        $stmt = $conn->prepare($updateBooking);
        $stmt->execute([
            ':status' => $status,
            ':orderID' => $orderID
        ]);
        
        sendResponse(true, 'Order updated successfully', null, 200);
        
    } catch (PDOException $e) {
        error_log("Update Order Error: " . $e->getMessage());
        sendResponse(false, 'Error updating order', null, 500);
    }
}
?>