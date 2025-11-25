<?php
/**
 * Clients API
 * Handles all client-related operations (CRUD)
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

// Route to appropriate handler
switch ($method) {
    case 'GET':
        handleGetClients($conn);
        break;
    case 'POST':
        handleCreateClient($conn);
        break;
    case 'PUT':
        handleUpdateClient($conn);
        break;
    case 'DELETE':
        handleDeleteClient($conn);
        break;
    default:
        sendResponse(false, 'Method not allowed', null, 405);
}

/**
 * GET: Retrieve clients
 */
function handleGetClients($conn) {
    try {
        // Get query parameters
        $clientID = $_GET['id'] ?? null;
        $email = $_GET['email'] ?? null;
        $search = $_GET['search'] ?? null;
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        
        // Base query with booking statistics
        $query = "SELECT 
                    c.ClientID,
                    c.Name,
                    c.Email,
                    c.ContactNumber,
                    c.Address,
                    c.IsEmailVerified,
                    c.CreatedAt,
                    c.UpdatedAt,
                    COUNT(DISTINCT b.BookingID) as TotalBookings,
                    COALESCE(SUM(b.TotalAmount), 0) as TotalSpent,
                    MAX(b.EventDate) as LastEventDate
                  FROM client c
                  LEFT JOIN booking b ON c.ClientID = b.ClientID
                  WHERE 1=1";
        
        $params = [];
        
        // Filter by specific client ID
        if ($clientID) {
            $query .= " AND c.ClientID = :clientID";
            $params[':clientID'] = $clientID;
        }
        
        // Filter by email (exact match)
        if ($email) {
            $query .= " AND c.Email = :email";
            $params[':email'] = $email;
        }
        
        // Search by name, email, or contact number
        if ($search) {
            $query .= " AND (c.Name LIKE :search OR c.Email LIKE :search OR c.ContactNumber LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        // Group by client
        $query .= " GROUP BY c.ClientID";
        
        // Order by most recent first
        $query .= " ORDER BY c.CreatedAt DESC";
        
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
        $clients = $stmt->fetchAll();
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM client WHERE 1=1";
        $countParams = [];
        
        if ($search) {
            $countQuery .= " AND (Name LIKE :search OR Email LIKE :search OR ContactNumber LIKE :search)";
            $countParams[':search'] = "%$search%";
        }
        
        $stmtCount = $conn->prepare($countQuery);
        foreach ($countParams as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $totalCount = $stmtCount->fetch()['total'] ?? 0;
        
        sendResponse(true, 'Clients retrieved successfully', [
            'clients' => $clients,
            'total' => $totalCount,
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ], 200);
        
    } catch (PDOException $e) {
        error_log("Get Clients Error: " . $e->getMessage());
        sendResponse(false, 'Error retrieving clients', null, 500);
    }
}

/**
 * POST: Create new client
 */
function handleCreateClient($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required fields
        $requiredFields = ['name', 'email', 'contact_number'];
        $missingFields = validateRequiredFields($data, $requiredFields);
        
        if (!empty($missingFields)) {
            sendResponse(false, 'Missing required fields: ' . implode(', ', $missingFields), null, 400);
        }
        
        // Validate email format
        if (!isValidEmail($data['email'])) {
            sendResponse(false, 'Invalid email format', null, 400);
        }
        
        // Check if email already exists
        $checkEmail = "SELECT ClientID FROM client WHERE Email = :email LIMIT 1";
        $stmt = $conn->prepare($checkEmail);
        $stmt->execute([':email' => $data['email']]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse(false, 'Email already registered', null, 409);
        }
        
        // Sanitize inputs
        $name = sanitizeInput($data['name']);
        $email = strtolower(sanitizeInput($data['email']));
        $contactNumber = sanitizeInput($data['contact_number']);
        $address = isset($data['address']) ? sanitizeInput($data['address']) : null;
        
        // Generate a temporary password (client should change it later)
        $tempPassword = password_hash('temp123', PASSWORD_DEFAULT);
        
        // Insert client
        $query = "INSERT INTO client 
                  (Name, Email, Password, ContactNumber, Address, IsEmailVerified) 
                  VALUES 
                  (:name, :email, :password, :contactNumber, :address, 0)";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $tempPassword,
            ':contactNumber' => $contactNumber,
            ':address' => $address
        ]);
        
        $clientID = $conn->lastInsertId();
        
        // Log activity
        logActivity($conn, getCurrentAdminId(), 'admin', 'client_created', "Created client #$clientID", $_SERVER['REMOTE_ADDR']);
        
        sendResponse(true, 'Client created successfully', ['client_id' => $clientID], 201);
        
    } catch (PDOException $e) {
        error_log("Create Client Error: " . $e->getMessage());
        sendResponse(false, 'Error creating client', null, 500);
    }
}

/**
 * PUT: Update existing client
 */
function handleUpdateClient($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['client_id']) || empty($data['client_id'])) {
            sendResponse(false, 'Client ID is required', null, 400);
        }
        
        $clientID = (int)$data['client_id'];
        
        // Check if client exists
        $checkQuery = "SELECT ClientID FROM client WHERE ClientID = :clientID LIMIT 1";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([':clientID' => $clientID]);
        
        if ($stmt->rowCount() === 0) {
            sendResponse(false, 'Client not found', null, 404);
        }
        
        // Build update query dynamically based on provided fields
        $updateFields = [];
        $params = [':clientID' => $clientID];
        
        $allowedFields = [
            'name' => 'Name',
            'email' => 'Email',
            'contact_number' => 'ContactNumber',
            'address' => 'Address'
        ];
        
        foreach ($allowedFields as $dataKey => $dbField) {
            if (isset($data[$dataKey])) {
                if ($dataKey === 'email' && !isValidEmail($data[$dataKey])) {
                    sendResponse(false, 'Invalid email format', null, 400);
                }
                $updateFields[] = "$dbField = :$dataKey";
                $params[":$dataKey"] = sanitizeInput($data[$dataKey]);
            }
        }
        
        if (empty($updateFields)) {
            sendResponse(false, 'No fields to update', null, 400);
        }
        
        $query = "UPDATE client SET " . implode(', ', $updateFields) . " WHERE ClientID = :clientID";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        // Log activity
        logActivity($conn, getCurrentAdminId(), 'admin', 'client_updated', "Updated client #$clientID", $_SERVER['REMOTE_ADDR']);
        
        sendResponse(true, 'Client updated successfully', null, 200);
        
    } catch (PDOException $e) {
        error_log("Update Client Error: " . $e->getMessage());
        sendResponse(false, 'Error updating client', null, 500);
    }
}

/**
 * DELETE: Delete client
 */
function handleDeleteClient($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['client_id']) || empty($data['client_id'])) {
            sendResponse(false, 'Client ID is required', null, 400);
        }
        
        $clientID = (int)$data['client_id'];
        
        // Check if client exists
        $checkQuery = "SELECT ClientID FROM client WHERE ClientID = :clientID LIMIT 1";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([':clientID' => $clientID]);
        
        if ($stmt->rowCount() === 0) {
            sendResponse(false, 'Client not found', null, 404);
        }
        
        // Check if client has bookings
        $checkBookings = "SELECT COUNT(*) as booking_count FROM booking WHERE ClientID = :clientID";
        $stmt = $conn->prepare($checkBookings);
        $stmt->execute([':clientID' => $clientID]);
        $bookingCount = $stmt->fetch()['booking_count'];
        
        if ($bookingCount > 0) {
            sendResponse(false, 'Cannot delete client with existing bookings', null, 400);
        }
        
        // Delete the client
        $query = "DELETE FROM client WHERE ClientID = :clientID";
        $stmt = $conn->prepare($query);
        $stmt->execute([':clientID' => $clientID]);
        
        // Log activity
        logActivity($conn, getCurrentAdminId(), 'admin', 'client_deleted', "Deleted client #$clientID", $_SERVER['REMOTE_ADDR']);
        
        sendResponse(true, 'Client deleted successfully', null, 200);
        
    } catch (PDOException $e) {
        error_log("Delete Client Error: " . $e->getMessage());
        sendResponse(false, 'Error deleting client', null, 500);
    }
}