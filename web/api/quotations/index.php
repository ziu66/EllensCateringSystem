<?php
/**
 * Quotations API
 * Handles all quotation-related operations (CRUD)
 */

// Start session
session_start();

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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

// Handle special actions
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update_items') {
    handleSpecialRequestItems($conn);
    exit;
}

// Route to appropriate handler
switch ($method) {
    case 'GET':
        handleGetQuotations($conn);
        break;
    case 'POST':
        handleCreateQuotation($conn);
        break;
    case 'PUT':
        handleUpdateQuotation($conn);
        break;
    case 'DELETE':
        handleDeleteQuotation($conn);
        break;
    default:
        sendResponse(false, 'Method not allowed', null, 405);
}

/**
 * GET: Retrieve quotations
 */
function handleGetQuotations($conn) {
    try {
        // Get query parameters
        $quotationID = $_GET['id'] ?? null;
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? null;
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        
        // Base query - quotations are linked to bookings which are linked to clients
        $query = "SELECT 
                    q.QuotationID,
                    q.BookingID,
                    q.AdminID,
                    q.SpecialRequest,
                    q.EstimatedPrice,
                    q.SpecialRequestPrice,
                    q.SpecialRequestItems,
                    (q.EstimatedPrice + IFNULL(q.SpecialRequestPrice, 0)) as TotalPrice,
                    q.Status,
                    b.ClientID,
                    c.Name as ClientName,
                    c.Email as ClientEmail,
                    c.ContactNumber,
                    b.EventType,
                    b.EventDate,
                    b.EventLocation,
                    b.NumberOfGuests
                  FROM quotation q
                  LEFT JOIN booking b ON q.BookingID = b.BookingID
                  LEFT JOIN client c ON b.ClientID = c.ClientID
                  WHERE 1=1";
        
        $params = [];
        
        // Filter by specific quotation ID
        if ($quotationID) {
            $query .= " AND q.QuotationID = :quotationID";
            $params[':quotationID'] = $quotationID;
        }
        
        // Filter by status
        if ($status && in_array($status, ['Pending', 'Approved', 'Rejected'])) {
            $query .= " AND q.Status = :status";
            $params[':status'] = $status;
        }
        
        // Search by client name or email
        if ($search) {
            $query .= " AND (c.Name LIKE :search OR c.Email LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        // Order by most recent first
        $query .= " ORDER BY q.QuotationID DESC";
        
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
        $quotations = $stmt->fetchAll();
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM quotation q 
                       LEFT JOIN booking b ON q.BookingID = b.BookingID
                       LEFT JOIN client c ON b.ClientID = c.ClientID
                       WHERE 1=1";
        
        $countParams = [];
        if ($status) {
            $countQuery .= " AND q.Status = :status";
            $countParams[':status'] = $status;
        }
        if ($search) {
            $countQuery .= " AND (c.Name LIKE :search OR c.Email LIKE :search)";
            $countParams[':search'] = "%$search%";
        }
        
        $stmtCount = $conn->prepare($countQuery);
        foreach ($countParams as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $totalCount = $stmtCount->fetch()['total'] ?? 0;
        
        sendResponse(true, 'Quotations retrieved successfully', [
            'quotations' => $quotations,
            'total' => $totalCount,
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ], 200);
        
    } catch (PDOException $e) {
        error_log("Get Quotations Error: " . $e->getMessage());
        sendResponse(false, 'Error retrieving quotations', null, 500);
    }
}

/**
 * POST: Create new quotation
 * This creates both a booking and a quotation in one operation
 */
function handleCreateQuotation($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required fields
        $requiredFields = ['client_name', 'email', 'contact_number', 'event_type', 'event_date', 'estimated_price'];
        $missingFields = validateRequiredFields($data, $requiredFields);
        
        if (!empty($missingFields)) {
            sendResponse(false, 'Missing required fields: ' . implode(', ', $missingFields), null, 400);
        }
        
        // Validate email format
        if (!isValidEmail($data['email'])) {
            sendResponse(false, 'Invalid email format', null, 400);
        }
        
        // Sanitize inputs
        $clientName = sanitizeInput($data['client_name']);
        $email = strtolower(sanitizeInput($data['email']));
        $contactNumber = sanitizeInput($data['contact_number']);
        $eventType = sanitizeInput($data['event_type']);
        $eventDate = sanitizeInput($data['event_date']);
        $estimatedPrice = (float)$data['estimated_price'];
        $details = isset($data['details']) ? sanitizeInput($data['details']) : null;
        $eventLocation = isset($data['event_location']) ? sanitizeInput($data['event_location']) : 'TBD';
        $numberOfGuests = isset($data['number_of_guests']) ? (int)$data['number_of_guests'] : 0;
        
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Step 1: Check if client exists, create if not
            $checkClient = "SELECT ClientID FROM client WHERE Email = :email LIMIT 1";
            $stmt = $conn->prepare($checkClient);
            $stmt->execute([':email' => $email]);
            
            if ($stmt->rowCount() > 0) {
                $clientID = $stmt->fetch()['ClientID'];
            } else {
                // Create new client
                $tempPassword = password_hash('temp123', PASSWORD_DEFAULT);
                $insertClient = "INSERT INTO client 
                                (Name, Email, Password, ContactNumber, IsEmailVerified) 
                                VALUES 
                                (:name, :email, :password, :contactNumber, 0)";
                
                $stmt = $conn->prepare($insertClient);
                $stmt->execute([
                    ':name' => $clientName,
                    ':email' => $email,
                    ':password' => $tempPassword,
                    ':contactNumber' => $contactNumber
                ]);
                
                $clientID = $conn->lastInsertId();
            }
            
            // Step 2: Create booking
            $insertBooking = "INSERT INTO booking 
                             (ClientID, EventType, DateBooked, EventDate, EventLocation, NumberOfGuests, SpecialRequests, Status, TotalAmount) 
                             VALUES 
                             (:clientID, :eventType, CURDATE(), :eventDate, :eventLocation, :numberOfGuests, :specialRequests, 'Pending', :totalAmount)";
            
            $stmt = $conn->prepare($insertBooking);
            $stmt->execute([
                ':clientID' => $clientID,
                ':eventType' => $eventType,
                ':eventDate' => $eventDate,
                ':eventLocation' => $eventLocation,
                ':numberOfGuests' => $numberOfGuests,
                ':specialRequests' => $details,
                ':totalAmount' => $estimatedPrice
            ]);
            
            $bookingID = $conn->lastInsertId();
            
            // Step 3: Create quotation
            $insertQuotation = "INSERT INTO quotation 
                               (BookingID, AdminID, SpecialRequest, EstimatedPrice, Status) 
                               VALUES 
                               (:bookingID, :adminID, :specialRequest, :estimatedPrice, 'Pending')";
            
            $stmt = $conn->prepare($insertQuotation);
            $stmt->execute([
                ':bookingID' => $bookingID,
                ':adminID' => getCurrentAdminId(),
                ':specialRequest' => $details,
                ':estimatedPrice' => $estimatedPrice
            ]);
            
            $quotationID = $conn->lastInsertId();
            
            // Commit transaction
            $conn->commit();
            
            // Log activity
            logActivity($conn, getCurrentAdminId(), 'admin', 'quotation_created', "Created quotation #$quotationID for booking #$bookingID", $_SERVER['REMOTE_ADDR']);
            
            sendResponse(true, 'Quotation created successfully', [
                'quotation_id' => $quotationID,
                'booking_id' => $bookingID,
                'client_id' => $clientID
            ], 201);
            
        } catch (PDOException $e) {
            $conn->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Create Quotation Error: " . $e->getMessage());
        sendResponse(false, 'Error creating quotation: ' . $e->getMessage(), null, 500);
    }
}

/**
 * PUT: Update existing quotation
 */
function handleUpdateQuotation($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['quotation_id']) || empty($data['quotation_id'])) {
            sendResponse(false, 'Quotation ID is required', null, 400);
        }
        
        $quotationID = (int)$data['quotation_id'];
        
        // Check if quotation exists
        $checkQuery = "SELECT QuotationID FROM quotation WHERE QuotationID = :quotationID LIMIT 1";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([':quotationID' => $quotationID]);
        
        if ($stmt->rowCount() === 0) {
            sendResponse(false, 'Quotation not found', null, 404);
        }
        
        // Build update query dynamically based on provided fields
        $updateFields = [];
        $params = [':quotationID' => $quotationID];
        
        $allowedFields = [
            'special_request' => 'SpecialRequest',
            'estimated_price' => 'EstimatedPrice',
            'special_request_price' => 'SpecialRequestPrice',
            'special_request_items' => 'SpecialRequestItems',
            'status' => 'Status'
        ];
        
        foreach ($allowedFields as $dataKey => $dbField) {
            if (isset($data[$dataKey])) {
                // Validate status if provided
                if ($dataKey === 'status' && !in_array($data[$dataKey], ['Pending', 'Approved', 'Rejected'])) {
                    sendResponse(false, 'Invalid status value', null, 400);
                }
                
                // Validate numeric fields
                if (in_array($dataKey, ['estimated_price', 'special_request_price'])) {
                    if (!is_numeric($data[$dataKey]) || (float)$data[$dataKey] < 0) {
                        sendResponse(false, 'Price fields must be valid positive numbers', null, 400);
                    }
                }
                
                // Convert special_request_items to JSON if it's an array
                if ($dataKey === 'special_request_items' && is_array($data[$dataKey])) {
                    $updateFields[] = "$dbField = :$dataKey";
                    $params[":$dataKey"] = json_encode($data[$dataKey]);
                } else {
                    $updateFields[] = "$dbField = :$dataKey";
                    $params[":$dataKey"] = $data[$dataKey];
                }
            }
        }
        
        if (empty($updateFields)) {
            sendResponse(false, 'No fields to update', null, 400);
        }
        
        $query = "UPDATE quotation SET " . implode(', ', $updateFields) . " WHERE QuotationID = :quotationID";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);

        // Get the updated quotation to calculate total
        $getQuotation = "SELECT EstimatedPrice, SpecialRequestPrice, BookingID FROM quotation WHERE QuotationID = :quotationID";
        $stmtGet = $conn->prepare($getQuotation);
        $stmtGet->execute([':quotationID' => $quotationID]);
        $quotationData = $stmtGet->fetch();
        
        if ($quotationData) {
            $totalPrice = (float)$quotationData['EstimatedPrice'] + (float)($quotationData['SpecialRequestPrice'] ?? 0);
            $bookingID = $quotationData['BookingID'];
            
            // Update booking total amount
            $updateBookingAmount = "UPDATE booking SET TotalAmount = :totalAmount WHERE BookingID = :bookingID";
            $stmtUpdate = $conn->prepare($updateBookingAmount);
            $stmtUpdate->execute([
                ':totalAmount' => $totalPrice,
                ':bookingID' => $bookingID
            ]);
        }

        // If status is changed to Approved, update booking status to Confirmed
        if (isset($data['status']) && $data['status'] === 'Approved') {
            $updateBooking = "UPDATE booking b 
                            INNER JOIN quotation q ON b.BookingID = q.BookingID 
                            SET b.Status = 'Confirmed' 
                            WHERE q.QuotationID = :quotationID";
            $stmt = $conn->prepare($updateBooking);
            $stmt->execute([':quotationID' => $quotationID]);
            
            // Log the auto-confirmation
            logActivity($conn, getCurrentAdminId(), 'admin', 'booking_auto_confirmed', 
                    "Auto-confirmed booking when quotation #$quotationID was approved", $_SERVER['REMOTE_ADDR']);
        }

        // If status is changed to Rejected, update booking status to Cancelled
        if (isset($data['status']) && $data['status'] === 'Rejected') {
            $updateBooking = "UPDATE booking b 
                            INNER JOIN quotation q ON b.BookingID = q.BookingID 
                            SET b.Status = 'Cancelled' 
                            WHERE q.QuotationID = :quotationID";
            $stmt = $conn->prepare($updateBooking);
            $stmt->execute([':quotationID' => $quotationID]);
        }
        
        // Log activity
        logActivity($conn, getCurrentAdminId(), 'admin', 'quotation_updated', "Updated quotation #$quotationID", $_SERVER['REMOTE_ADDR']);
        
        sendResponse(true, 'Quotation updated successfully', null, 200);
        
    } catch (PDOException $e) {
        error_log("Update Quotation Error: " . $e->getMessage());
        sendResponse(false, 'Error updating quotation', null, 500);
    }
}

/**
 * Handle special request items for dynamic pricing
 */
function handleSpecialRequestItems($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['quotation_id']) || empty($data['quotation_id'])) {
            sendResponse(false, 'Quotation ID is required', null, 400);
        }
        
        $quotationID = (int)$data['quotation_id'];
        $items = $data['items'] ?? [];
        
        // Verify quotation exists
        $checkQuery = "SELECT q.QuotationID, q.BookingID, q.EstimatedPrice 
                       FROM quotation q 
                       WHERE q.QuotationID = :quotationID LIMIT 1";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([':quotationID' => $quotationID]);
        
        if ($stmt->rowCount() === 0) {
            sendResponse(false, 'Quotation not found', null, 404);
        }
        
        $quotation = $stmt->fetch();
        $bookingID = $quotation['BookingID'];
        $basePrice = (float)$quotation['EstimatedPrice'];
        
        // Calculate total from items
        $itemsTotal = 0;
        foreach ($items as $item) {
            if (isset($item['price'])) {
                $itemsTotal += (float)$item['price'];
            }
        }
        
        // New total = base price + items total
        $newTotal = $basePrice + $itemsTotal;
        
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Update quotation with new total
            $updateQuotation = "UPDATE quotation 
                               SET EstimatedPrice = :newTotal 
                               WHERE QuotationID = :quotationID";
            $stmt = $conn->prepare($updateQuotation);
            $stmt->execute([
                ':newTotal' => $newTotal,
                ':quotationID' => $quotationID
            ]);
            
            // Update booking total amount
            $updateBooking = "UPDATE booking 
                             SET TotalAmount = :newTotal 
                             WHERE BookingID = :bookingID";
            $stmt = $conn->prepare($updateBooking);
            $stmt->execute([
                ':newTotal' => $newTotal,
                ':bookingID' => $bookingID
            ]);
            
            $conn->commit();
            
            // Log activity
            logActivity($conn, getCurrentAdminId(), 'admin', 'quotation_price_updated', 
                       "Updated quotation #$quotationID price to $$newTotal (Base: $$basePrice + Items: $$itemsTotal)", 
                       $_SERVER['REMOTE_ADDR']);
            
            sendResponse(true, 'Quotation price updated successfully', [
                'quotation_id' => $quotationID,
                'base_price' => $basePrice,
                'items_total' => $itemsTotal,
                'new_total' => $newTotal
            ], 200);
            
        } catch (PDOException $e) {
            $conn->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Update Special Request Items Error: " . $e->getMessage());
        sendResponse(false, 'Error updating special request items', null, 500);
    }
}

/**
 * DELETE: Delete quotation
 */
function handleDeleteQuotation($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['quotation_id']) || empty($data['quotation_id'])) {
            sendResponse(false, 'Quotation ID is required', null, 400);
        }
        
        $quotationID = (int)$data['quotation_id'];
        
        // Check if quotation exists
        $checkQuery = "SELECT QuotationID, BookingID FROM quotation WHERE QuotationID = :quotationID LIMIT 1";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([':quotationID' => $quotationID]);
        
        if ($stmt->rowCount() === 0) {
            sendResponse(false, 'Quotation not found', null, 404);
        }
        
        $quotation = $stmt->fetch();
        $bookingID = $quotation['BookingID'];
        
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Delete the quotation
            $deleteQuotation = "DELETE FROM quotation WHERE QuotationID = :quotationID";
            $stmt = $conn->prepare($deleteQuotation);
            $stmt->execute([':quotationID' => $quotationID]);
            
            // Optionally delete the associated booking if it's still pending
            $deleteBooking = "DELETE FROM booking WHERE BookingID = :bookingID AND Status = 'Pending'";
            $stmt = $conn->prepare($deleteBooking);
            $stmt->execute([':bookingID' => $bookingID]);
            
            $conn->commit();
            
            // Log activity
            logActivity($conn, getCurrentAdminId(), 'admin', 'quotation_deleted', "Deleted quotation #$quotationID", $_SERVER['REMOTE_ADDR']);
            
            sendResponse(true, 'Quotation deleted successfully', null, 200);
            
        } catch (PDOException $e) {
            $conn->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Delete Quotation Error: " . $e->getMessage());
        sendResponse(false, 'Error deleting quotation', null, 500);
    }
}