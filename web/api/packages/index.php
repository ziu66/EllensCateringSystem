<?php
/**
 * Packages API
 * Handles catering package management (CRUD)
 * 
 * Endpoints:
 * GET    /api/packages/index.php           - Get all packages
 * GET    /api/packages/index.php?id=1      - Get specific package
 * GET    /api/packages/index.php?search=wedding - Search packages
 * POST   /api/packages/index.php           - Create new package
 * PUT    /api/packages/index.php           - Update package
 * DELETE /api/packages/index.php           - Delete package
 */

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

require_once '../../config/database.php';
require_once '../middleware/auth.php';

// Protect this endpoint - require admin authentication
requireAuth();

$conn = getDbConnection();

if (!$conn) {
    sendResponse(false, 'Database connection failed', null, 500);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetPackages($conn);
        break;
    case 'POST':
        handleCreatePackage($conn);
        break;
    case 'PUT':
        handleUpdatePackage($conn);
        break;
    case 'DELETE':
        handleDeletePackage($conn);
        break;
    default:
        sendResponse(false, 'Method not allowed', null, 405);
}

/**
 * GET: Retrieve packages
 * Query Parameters:
 * - id: Get specific package by ID
 * - search: Search by package name or description
 * - limit: Results per page (default: 50)
 * - offset: Pagination offset (default: 0)
 */
function handleGetPackages($conn) {
    try {
        $packageID = $_GET['id'] ?? null;
        $search = $_GET['search'] ?? null;
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        
        $query = "SELECT 
                    PackageID,
                    PackageName,
                    Description,
                    PackPrice
                  FROM package
                  WHERE 1=1";
        
        $params = [];
        
        if ($packageID) {
            $query .= " AND PackageID = :packageID";
            $params[':packageID'] = $packageID;
        }
        
        if ($search) {
            $query .= " AND (PackageName LIKE :search OR Description LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $query .= " ORDER BY PackageName ASC LIMIT :limit OFFSET :offset";
        
        $stmt = $conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $packages = $stmt->fetchAll();
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM package WHERE 1=1";
        if ($search) {
            $countQuery .= " AND (PackageName LIKE :search OR Description LIKE :search)";
        }
        
        $stmtCount = $conn->prepare($countQuery);
        if ($search) {
            $stmtCount->bindValue(':search', "%$search%");
        }
        $stmtCount->execute();
        $totalCount = $stmtCount->fetch()['total'];
        
        sendResponse(true, 'Packages retrieved successfully', [
            'packages' => $packages,
            'total' => $totalCount,
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ], 200);
        
    } catch (PDOException $e) {
        error_log("Get Packages Error: " . $e->getMessage());
        sendResponse(false, 'Error retrieving packages', null, 500);
    }
}

/**
 * POST: Create new package
 * Required fields: package_name, pack_price
 * Optional fields: description
 */
function handleCreatePackage($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $requiredFields = ['package_name', 'pack_price'];
        $missingFields = validateRequiredFields($data, $requiredFields);
        
        if (!empty($missingFields)) {
            sendResponse(false, 'Missing required fields: ' . implode(', ', $missingFields), null, 400);
        }
        
        $packageName = sanitizeInput($data['package_name']);
        $description = isset($data['description']) ? sanitizeInput($data['description']) : null;
        $packPrice = (float)$data['pack_price'];
        
        // Validate price
        if ($packPrice <= 0) {
            sendResponse(false, 'Price must be greater than 0', null, 400);
        }
        
        // Check if package name already exists
        $checkName = "SELECT PackageID FROM package WHERE PackageName = :packageName LIMIT 1";
        $stmt = $conn->prepare($checkName);
        $stmt->execute([':packageName' => $packageName]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse(false, 'Package name already exists', null, 409);
        }
        
        // Insert package
        $query = "INSERT INTO package (PackageName, Description, PackPrice) 
                  VALUES (:packageName, :description, :packPrice)";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':packageName' => $packageName,
            ':description' => $description,
            ':packPrice' => $packPrice
        ]);
        
        $packageID = $conn->lastInsertId();
        
        logActivity($conn, getCurrentAdminId(), 'admin', 'package_created', "Created package #$packageID - $packageName", $_SERVER['REMOTE_ADDR']);
        
        sendResponse(true, 'Package created successfully', ['package_id' => $packageID], 201);
        
    } catch (PDOException $e) {
        error_log("Create Package Error: " . $e->getMessage());
        sendResponse(false, 'Error creating package', null, 500);
    }
}

/**
 * PUT: Update package
 * Required field: package_id
 * Optional fields: package_name, description, pack_price
 */
function handleUpdatePackage($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['package_id']) || empty($data['package_id'])) {
            sendResponse(false, 'Package ID is required', null, 400);
        }
        
        $packageID = (int)$data['package_id'];
        
        // Check if package exists
        $checkQuery = "SELECT PackageID FROM package WHERE PackageID = :packageID LIMIT 1";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([':packageID' => $packageID]);
        
        if ($stmt->rowCount() === 0) {
            sendResponse(false, 'Package not found', null, 404);
        }
        
        // Build update query
        $updateFields = [];
        $params = [':packageID' => $packageID];
        
        if (isset($data['package_name'])) {
            // Check if new name already exists (excluding current package)
            $checkName = "SELECT PackageID FROM package WHERE PackageName = :packageName AND PackageID != :packageID LIMIT 1";
            $stmtCheck = $conn->prepare($checkName);
            $stmtCheck->execute([
                ':packageName' => sanitizeInput($data['package_name']),
                ':packageID' => $packageID
            ]);
            
            if ($stmtCheck->rowCount() > 0) {
                sendResponse(false, 'Package name already exists', null, 409);
            }
            
            $updateFields[] = "PackageName = :packageName";
            $params[':packageName'] = sanitizeInput($data['package_name']);
        }
        
        if (isset($data['description'])) {
            $updateFields[] = "Description = :description";
            $params[':description'] = sanitizeInput($data['description']);
        }
        
        if (isset($data['pack_price'])) {
            $price = (float)$data['pack_price'];
            if ($price <= 0) {
                sendResponse(false, 'Price must be greater than 0', null, 400);
            }
            $updateFields[] = "PackPrice = :packPrice";
            $params[':packPrice'] = $price;
        }
        
        if (empty($updateFields)) {
            sendResponse(false, 'No fields to update', null, 400);
        }
        
        $query = "UPDATE package SET " . implode(', ', $updateFields) . " WHERE PackageID = :packageID";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        logActivity($conn, getCurrentAdminId(), 'admin', 'package_updated', "Updated package #$packageID", $_SERVER['REMOTE_ADDR']);
        
        sendResponse(true, 'Package updated successfully', null, 200);
        
    } catch (PDOException $e) {
        error_log("Update Package Error: " . $e->getMessage());
        sendResponse(false, 'Error updating package', null, 500);
    }
}

/**
 * DELETE: Delete package
 * Required field: package_id
 * Note: Cannot delete packages that are used in bookings
 */
function handleDeletePackage($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['package_id']) || empty($data['package_id'])) {
            sendResponse(false, 'Package ID is required', null, 400);
        }
        
        $packageID = (int)$data['package_id'];
        
        // Check if package is used in bookings
        $checkBookings = "SELECT COUNT(*) as total FROM booking_package WHERE PackageID = :packageID";
        $stmt = $conn->prepare($checkBookings);
        $stmt->execute([':packageID' => $packageID]);
        $bookingCount = $stmt->fetch()['total'];
        
        if ($bookingCount > 0) {
            sendResponse(false, "Cannot delete package with existing bookings ($bookingCount bookings found)", null, 409);
        }
        
        // Delete package
        $query = "DELETE FROM package WHERE PackageID = :packageID";
        $stmt = $conn->prepare($query);
        $stmt->execute([':packageID' => $packageID]);
        
        if ($stmt->rowCount() === 0) {
            sendResponse(false, 'Package not found', null, 404);
        }
        
        logActivity($conn, getCurrentAdminId(), 'admin', 'package_deleted', "Deleted package #$packageID", $_SERVER['REMOTE_ADDR']);
        
        sendResponse(true, 'Package deleted successfully', null, 200);
        
    } catch (PDOException $e) {
        error_log("Delete Package Error: " . $e->getMessage());
        sendResponse(false, 'Error deleting package', null, 500);
    }
}