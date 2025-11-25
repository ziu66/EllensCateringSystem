<?php
/**
 * Bookings API
 * Handles all booking-related operations (CRUD)
 */

// Start session
session_start();

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Include required files
require_once '../../config/database.php';
require_once '../middleware/auth.php';

// Require authentication
requireAuth();

// Get database connection
$conn = getDbConnection();

if (!$conn) {
    sendResponse(false, 'Database connection failed', null, 500);
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle PATCH method for cancel operation
if ($method === 'PATCH') {
    $action = $_GET['action'] ?? null;
    if ($action === 'cancel') {
        handleCancelBooking($conn);
    } else {
        sendResponse(false, 'Invalid action', null, 400);
    }
    exit;
}

// Route to appropriate handler
switch ($method) {
    case 'GET':
        handleGetBookings($conn);
        break;
    case 'POST':
        handleCreateBooking($conn);
        break;
    case 'PUT':
        handleUpdateBooking($conn);
        break;
    case 'DELETE':
        handleDeleteBooking($conn);
        break;
    default:
        sendResponse(false, 'Method not allowed', null, 405);
}

/**
 * PATCH: Cancel a booking (admin action)
 */
function handleCancelBooking($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['booking_id']) || empty($data['booking_id'])) {
            sendResponse(false, 'Booking ID is required', null, 400);
        }
        
        $bookingID = (int)$data['booking_id'];
        $cancelReason = isset($data['cancel_reason']) ? sanitizeInput($data['cancel_reason']) : 'Cancelled by admin';
        
        // Check if booking exists and get current status
        $checkQuery = "SELECT BookingID, Status, EventDate FROM booking WHERE BookingID = :bookingID LIMIT 1";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([':bookingID' => $bookingID]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            sendResponse(false, 'Booking not found', null, 404);
        }
        
        // Check if already cancelled
        if ($booking['Status'] === 'Cancelled') {
            sendResponse(false, 'Booking is already cancelled', null, 400);
        }
        
        // Cannot cancel completed bookings
        if ($booking['Status'] === 'Completed') {
            sendResponse(false, 'Cannot cancel completed bookings', null, 400);
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        // Update booking status
        $updateQuery = "UPDATE booking 
                       SET Status = 'Cancelled',
                           SpecialRequests = CONCAT(IFNULL(SpecialRequests, ''), '\n\n--- CANCELLATION ---\nReason: ', :cancelReason, '\nCancelled by: Admin\nDate: ', NOW())
                       WHERE BookingID = :bookingID";
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute([
            ':bookingID' => $bookingID,
            ':cancelReason' => $cancelReason
        ]);
        
        // Update related quotation status
        $updateQuotation = "UPDATE quotation SET Status = 'Cancelled' WHERE BookingID = :bookingID";
        $stmt = $conn->prepare($updateQuotation);
        $stmt->execute([':bookingID' => $bookingID]);
        
        $conn->commit();
        
        // Log activity
        logActivity($conn, getCurrentAdminId(), 'admin', 'booking_cancelled', "Cancelled booking #$bookingID. Reason: $cancelReason", $_SERVER['REMOTE_ADDR']);
        
        sendResponse(true, 'Booking cancelled successfully', ['booking_id' => $bookingID], 200);
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Cancel Booking Error: " . $e->getMessage());
        sendResponse(false, 'Error cancelling booking', null, 500);
    }
}

/**
 * GET: Retrieve bookings
 * Supports filtering by status, date range, and search
 */
function handleGetBookings($conn) {
    try {
        // Get query parameters
        $bookingID = $_GET['id'] ?? null;
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        
        // Base query with JOIN to get client info
        $query = "SELECT 
                    b.BookingID,
                    b.ClientID,
                    c.Name as ClientName,
                    c.Email as ClientEmail,
                    c.ContactNumber,
                    b.EventType,
                    b.DateBooked,
                    b.EventDate,
                    b.EventLocation,
                    b.NumberOfGuests,
                    b.SpecialRequests,
                    b.Status,
                    b.TotalAmount,
                    b.CreatedAt,
                    b.UpdatedAt
                  FROM booking b
                  LEFT JOIN client c ON b.ClientID = c.ClientID
                  WHERE 1=1";
        
        $params = [];
        
        // Filter by specific booking ID
        if ($bookingID) {
            $query .= " AND b.BookingID = :bookingID";
            $params[':bookingID'] = $bookingID;
        }
        
        // Filter by status - UPDATED to include Cancelled
        if ($status && in_array($status, ['Pending', 'Confirmed', 'Completed', 'Cancelled'])) {
            $query .= " AND b.Status = :status";
            $params[':status'] = $status;
        }
        
        // Filter by date range
        if ($startDate) {
            $query .= " AND b.EventDate >= :startDate";
            $params[':startDate'] = $startDate;
        }
        
        if ($endDate) {
            $query .= " AND b.EventDate <= :endDate";
            $params[':endDate'] = $endDate;
        }
        
        // Search by client name, email, or event type
        if ($search) {
            $query .= " AND (c.Name LIKE :search OR c.Email LIKE :search OR b.EventType LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        // Order by most recent first
        $query .= " ORDER BY b.CreatedAt DESC";
        
        // Add pagination
        $query .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $conn->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $bookings = $stmt->fetchAll();
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM booking b LEFT JOIN client c ON b.ClientID = c.ClientID WHERE 1=1";
        
        $countParams = [];
        if ($status) {
            $countQuery .= " AND b.Status = :status";
            $countParams[':status'] = $status;
        }
        if ($search) {
            $countQuery .= " AND (c.Name LIKE :search OR c.Email LIKE :search OR b.EventType LIKE :search)";
            $countParams[':search'] = "%$search%";
        }
        if ($startDate) {
            $countQuery .= " AND b.EventDate >= :startDate";
            $countParams[':startDate'] = $startDate;
        }
        if ($endDate) {
            $countQuery .= " AND b.EventDate <= :endDate";
            $countParams[':endDate'] = $endDate;
        }
        
        $stmtCount = $conn->prepare($countQuery);
        foreach ($countParams as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $totalCount = $stmtCount->fetch()['total'] ?? 0;
        
        // Get status counts for dashboard
        $statusCountsQuery = "SELECT 
                                Status,
                                COUNT(*) as count 
                              FROM booking 
                              GROUP BY Status";
        $stmtStatus = $conn->prepare($statusCountsQuery);
        $stmtStatus->execute();
        $statusCounts = $stmtStatus->fetchAll(PDO::FETCH_KEY_PAIR);
        
        sendResponse(true, 'Bookings retrieved successfully', [
            'bookings' => $bookings,
            'total' => $totalCount,
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'status_counts' => $statusCounts
        ], 200);
        
    } catch (PDOException $e) {
        error_log("Get Bookings Error: " . $e->getMessage());
        sendResponse(false, 'Error retrieving bookings', null, 500);
    }
}

/**
 * POST: Create new booking
 */
function handleCreateBooking($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required fields
        $requiredFields = ['client_id', 'event_type', 'event_date', 'event_location', 'number_of_guests'];
        $missingFields = validateRequiredFields($data, $requiredFields);
        
        if (!empty($missingFields)) {
            sendResponse(false, 'Missing required fields: ' . implode(', ', $missingFields), null, 400);
        }
        
        // Sanitize inputs
        $clientID = (int)$data['client_id'];
        $eventType = sanitizeInput($data['event_type']);
        $eventDate = sanitizeInput($data['event_date']);
        $eventLocation = sanitizeInput($data['event_location']);
        $numberOfGuests = (int)$data['number_of_guests'];
        $specialRequests = isset($data['special_requests']) ? sanitizeInput($data['special_requests']) : null;
        $totalAmount = isset($data['total_amount']) ? (float)$data['total_amount'] : null;
        
        // Verify client exists
        $checkClient = "SELECT ClientID FROM client WHERE ClientID = :clientID LIMIT 1";
        $stmt = $conn->prepare($checkClient);
        $stmt->execute([':clientID' => $clientID]);
        
        if ($stmt->rowCount() === 0) {
            sendResponse(false, 'Client not found', null, 404);
        }
        
        // Insert booking
        $query = "INSERT INTO booking 
                  (ClientID, EventType, DateBooked, EventDate, EventLocation, NumberOfGuests, SpecialRequests, Status, TotalAmount) 
                  VALUES 
                  (:clientID, :eventType, CURDATE(), :eventDate, :eventLocation, :numberOfGuests, :specialRequests, 'Pending', :totalAmount)";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':clientID' => $clientID,
            ':eventType' => $eventType,
            ':eventDate' => $eventDate,
            ':eventLocation' => $eventLocation,
            ':numberOfGuests' => $numberOfGuests,
            ':specialRequests' => $specialRequests,
            ':totalAmount' => $totalAmount
        ]);
        
        $bookingID = $conn->lastInsertId();

        // Create quotation automatically for this booking
        if (isset($data['create_quotation']) && $data['create_quotation'] === true) {
            $adminID = getCurrentAdminId();
            $specialRequest = $data['special_requests'] ?? '';
            $estimatedPrice = $data['total_amount'];
            
            $insertQuotation = "INSERT INTO quotation 
                               (BookingID, AdminID, SpecialRequest, EstimatedPrice, Status) 
                               VALUES 
                               (:bookingID, :adminID, :specialRequest, :estimatedPrice, 'Pending')";
            
            $stmtQuotation = $conn->prepare($insertQuotation);
            $stmtQuotation->execute([
                ':bookingID' => $bookingID,
                ':adminID' => $adminID,
                ':specialRequest' => $specialRequest,
                ':estimatedPrice' => $estimatedPrice
            ]);
        }
        
        // Log activity
        logActivity($conn, getCurrentAdminId(), 'admin', 'booking_created', "Created booking #$bookingID", $_SERVER['REMOTE_ADDR']);
        
        sendResponse(true, 'Booking created successfully', ['booking_id' => $bookingID], 201);
        
    } catch (PDOException $e) {
        error_log("Create Booking Error: " . $e->getMessage());
        sendResponse(false, 'Error creating booking', null, 500);
    }
}

/**
 * PUT: Update existing booking
 */
function handleUpdateBooking($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['booking_id']) || empty($data['booking_id'])) {
            sendResponse(false, 'Booking ID is required', null, 400);
        }
        
        $bookingID = (int)$data['booking_id'];
        
        // Check if booking exists
        $checkQuery = "SELECT BookingID, Status FROM booking WHERE BookingID = :bookingID LIMIT 1";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([':bookingID' => $bookingID]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            sendResponse(false, 'Booking not found', null, 404);
        }
        
        // Prevent updating cancelled bookings (except to reactivate)
        if ($booking['Status'] === 'Cancelled' && (!isset($data['status']) || $data['status'] === 'Cancelled')) {
            sendResponse(false, 'Cannot update cancelled booking. Use reactivate function instead.', null, 400);
        }
        
        // Build update query dynamically based on provided fields
        $updateFields = [];
        $params = [':bookingID' => $bookingID];
        
        $allowedFields = [
            'event_type' => 'EventType',
            'event_date' => 'EventDate',
            'event_location' => 'EventLocation',
            'number_of_guests' => 'NumberOfGuests',
            'special_requests' => 'SpecialRequests',
            'status' => 'Status',
            'total_amount' => 'TotalAmount'
        ];
        
        foreach ($allowedFields as $dataKey => $dbField) {
            if (isset($data[$dataKey])) {
                $updateFields[] = "$dbField = :$dataKey";
                $params[":$dataKey"] = $data[$dataKey];
            }
        }
        
        if (empty($updateFields)) {
            sendResponse(false, 'No fields to update', null, 400);
        }
        
        $query = "UPDATE booking SET " . implode(', ', $updateFields) . " WHERE BookingID = :bookingID";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        // Log activity
        logActivity($conn, getCurrentAdminId(), 'admin', 'booking_updated', "Updated booking #$bookingID", $_SERVER['REMOTE_ADDR']);
        
        sendResponse(true, 'Booking updated successfully', null, 200);
        
    } catch (PDOException $e) {
        error_log("Update Booking Error: " . $e->getMessage());
        sendResponse(false, 'Error updating booking', null, 500);
    }
}

/**
 * DELETE: Delete booking (hard delete - use with caution)
 */
function handleDeleteBooking($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['booking_id']) || empty($data['booking_id'])) {
            sendResponse(false, 'Booking ID is required', null, 400);
        }
        
        $bookingID = (int)$data['booking_id'];
        
        // Check if booking exists
        $checkQuery = "SELECT BookingID, Status FROM booking WHERE BookingID = :bookingID LIMIT 1";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([':bookingID' => $bookingID]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            sendResponse(false, 'Booking not found', null, 404);
        }
        
        // Warn if trying to delete non-cancelled bookings
        if ($booking['Status'] !== 'Cancelled') {
            sendResponse(false, 'Please cancel the booking before deleting. Only cancelled bookings can be deleted.', null, 400);
        }
        
        // Start transaction for cascading deletes
        $conn->beginTransaction();
        
        // Delete related quotations first
        $deleteQuotation = "DELETE FROM quotation WHERE BookingID = :bookingID";
        $stmt = $conn->prepare($deleteQuotation);
        $stmt->execute([':bookingID' => $bookingID]);
        
        // Delete related records
        $deleteBookingMenu = "DELETE FROM booking_menu WHERE BookingID = :bookingID";
        $stmt = $conn->prepare($deleteBookingMenu);
        $stmt->execute([':bookingID' => $bookingID]);
        
        $deleteBookingPackage = "DELETE FROM booking_package WHERE BookingID = :bookingID";
        $stmt = $conn->prepare($deleteBookingPackage);
        $stmt->execute([':bookingID' => $bookingID]);
        
        // Delete the booking
        $query = "DELETE FROM booking WHERE BookingID = :bookingID";
        $stmt = $conn->prepare($query);
        $stmt->execute([':bookingID' => $bookingID]);
        
        $conn->commit();
        
        // Log activity
        logActivity($conn, getCurrentAdminId(), 'admin', 'booking_deleted', "Permanently deleted booking #$bookingID", $_SERVER['REMOTE_ADDR']);
        
        sendResponse(true, 'Booking deleted successfully', null, 200);
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Delete Booking Error: " . $e->getMessage());
        sendResponse(false, 'Error deleting booking', null, 500);
    }
}