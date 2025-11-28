<?php
// filepath: C:\xampp1\htdocs\EllensCateringSystem\web\api\agreements\index.php

// Clean output buffer to prevent any output before JSON
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header FIRST
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Function to send JSON response and exit cleanly
function sendJsonResponse($statusCode, $success, $message, $data = null) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Try to load the root config first (mysqli version)
    $rootConfigPath = __DIR__ . '/../../../config/database.php';
    $webConfigPath = __DIR__ . '/../../config/database.php';
    
    $conn = null;
    $usingPDO = false;
    
    // Try root config first (mysqli)
    if (file_exists($rootConfigPath)) {
        ob_start();
        require_once $rootConfigPath;
        ob_end_clean();
        
        if (function_exists('getDB')) {
            $conn = getDB();
            $usingPDO = false;
        }
    }
    
    // If no connection, try web config (PDO)
    if (!$conn && file_exists($webConfigPath)) {
        ob_start();
        require_once $webConfigPath;
        ob_end_clean();
        
        if (function_exists('getDbConnection')) {
            $conn = getDbConnection();
            $usingPDO = true;
        }
    }
    
    // Last resort - manual connection
    if (!$conn) {
        $conn = new mysqli('localhost', 'root', '', 'catering_db');
        $usingPDO = false;
        
        if ($conn->connect_error) {
            sendJsonResponse(500, false, 'Database connection failed: ' . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    
    // Session handling
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check authentication
    $isAuthenticated = isset($_SESSION['user_id']) || isset($_SESSION['admin_id']) || isset($_SESSION['client_id']);
    
    if (!$isAuthenticated) {
        sendJsonResponse(401, false, 'Unauthorized - Please login');
    }
    
    // ===== GET AGREEMENT =====
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'admin_get_agreement') {
        
        $bookingID = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
        
        if ($bookingID <= 0) {
            sendJsonResponse(400, false, 'Invalid booking ID');
        }
        
        // Check if booking exists (works with both mysqli and PDO)
        if ($usingPDO) {
            // PDO version
            $stmt = $conn->prepare("SELECT BookingID, Status, PaymentStatus FROM booking WHERE BookingID = ?");
            $stmt->execute([$bookingID]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                sendJsonResponse(404, false, 'Booking not found with ID: ' . $bookingID);
            }
        } else {
            // mysqli version
            $stmt = $conn->prepare("SELECT BookingID, Status, PaymentStatus FROM booking WHERE BookingID = ?");
            if (!$stmt) {
                sendJsonResponse(500, false, 'Database prepare error: ' . $conn->error);
            }
            
            $stmt->bind_param("i", $bookingID);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                sendJsonResponse(404, false, 'Booking not found with ID: ' . $bookingID);
            }
            
            $booking = $result->fetch_assoc();
            $stmt->close();
        }
        
        // Check if agreement exists
        $query = "
            SELECT 
                a.AgreementID,
                a.BookingID,
                a.ClientID,
                a.ContractFile,
                a.Status,
                a.CustomerSignature,
                a.SignedDate,
                a.CreatedAt,
                a.UpdatedAt,
                b.EventType,
                b.EventDate,
                b.TotalAmount,
                b.Status as BookingStatus,
                b.PaymentStatus,
                c.Name as ClientName,
                c.Email as ClientEmail,
                c.ContactNumber as ClientPhone
            FROM agreement a
            INNER JOIN booking b ON a.BookingID = b.BookingID
            INNER JOIN client c ON a.ClientID = c.ClientID
            WHERE a.BookingID = ?
            LIMIT 1
        ";
        
        if ($usingPDO) {
            // PDO version
            $stmt = $conn->prepare($query);
            $stmt->execute([$bookingID]);
            $agreement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$agreement) {
                sendJsonResponse(404, false, 'Agreement has not been created yet for this booking', [
                    'booking_exists' => true,
                    'booking_status' => $booking['Status'],
                    'payment_status' => $booking['PaymentStatus'],
                    'booking_id' => $bookingID,
                    'can_create_agreement' => ($booking['Status'] === 'Confirmed' && $booking['PaymentStatus'] === 'Paid')
                ]);
            }
        } else {
            // mysqli version
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                sendJsonResponse(500, false, 'Database prepare error: ' . $conn->error);
            }
            
            $stmt->bind_param("i", $bookingID);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                sendJsonResponse(404, false, 'Agreement has not been created yet for this booking', [
                    'booking_exists' => true,
                    'booking_status' => $booking['Status'],
                    'payment_status' => $booking['PaymentStatus'],
                    'booking_id' => $bookingID,
                    'can_create_agreement' => ($booking['Status'] === 'Confirmed' && $booking['PaymentStatus'] === 'Paid')
                ]);
            }
            
            $agreement = $result->fetch_assoc();
            $stmt->close();
        }
        
        sendJsonResponse(200, true, 'Agreement retrieved successfully', [
            'agreement' => $agreement
        ]);
    }
    
    // ===== CREATE AGREEMENT =====
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create_agreement') {
        
        $input = json_decode(file_get_contents('php://input'), true);
        $bookingID = isset($input['booking_id']) ? intval($input['booking_id']) : 0;
        
        if ($bookingID <= 0) {
            sendJsonResponse(400, false, 'Invalid booking ID');
        }
        
        // Get booking details
        $bookingQuery = "
            SELECT 
                b.*,
                c.ClientID,
                c.Name,
                c.Email,
                c.ContactNumber
            FROM booking b
            INNER JOIN client c ON b.ClientID = c.ClientID
            WHERE b.BookingID = ?
        ";
        
        if ($usingPDO) {
            // PDO version
            $stmt = $conn->prepare($bookingQuery);
            $stmt->execute([$bookingID]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                sendJsonResponse(404, false, 'Booking not found');
            }
        } else {
            // mysqli version
            $stmt = $conn->prepare($bookingQuery);
            if (!$stmt) {
                sendJsonResponse(500, false, 'Database prepare error: ' . $conn->error);
            }
            
            $stmt->bind_param("i", $bookingID);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                sendJsonResponse(404, false, 'Booking not found');
            }
            
            $booking = $result->fetch_assoc();
            $stmt->close();
        }
        
        // Check if agreement already exists
        $checkQuery = "SELECT AgreementID FROM agreement WHERE BookingID = ?";
        
        if ($usingPDO) {
            $stmt = $conn->prepare($checkQuery);
            $stmt->execute([$bookingID]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                sendJsonResponse(400, false, 'Agreement already exists for this booking', [
                    'agreement_id' => $existing['AgreementID']
                ]);
            }
        } else {
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("i", $bookingID);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $existing = $result->fetch_assoc();
                $stmt->close();
                sendJsonResponse(400, false, 'Agreement already exists for this booking', [
                    'agreement_id' => $existing['AgreementID']
                ]);
            }
            $stmt->close();
        }
        
        // Create agreement HTML
        $contractHTML = "
        <div style='font-family: Arial, sans-serif; padding: 30px; max-width: 800px; margin: 0 auto;'>
            <div style='text-align: center; margin-bottom: 30px;'>
                <h1 style='color: #000; margin-bottom: 10px;'>CATERING SERVICE AGREEMENT</h1>
                <hr style='border: 2px solid #000; width: 100px;'>
            </div>
            
            <p style='text-align: right; color: #666;'><strong>Date:</strong> " . date('F d, Y') . "</p>
            <hr style='border-top: 1px solid #ddd;'>
            
            <div style='margin: 30px 0;'>
                <h3 style='color: #000; border-bottom: 2px solid #000; padding-bottom: 10px;'>CLIENT INFORMATION</h3>
                <table style='width: 100%; margin-top: 15px;'>
                    <tr><td style='padding: 8px 0; width: 150px;'><strong>Name:</strong></td><td>" . htmlspecialchars($booking['Name']) . "</td></tr>
                    <tr><td style='padding: 8px 0;'><strong>Email:</strong></td><td>" . htmlspecialchars($booking['Email']) . "</td></tr>
                    <tr><td style='padding: 8px 0;'><strong>Phone:</strong></td><td>" . htmlspecialchars($booking['ContactNumber']) . "</td></tr>
                </table>
            </div>
            
            <div style='margin: 30px 0;'>
                <h3 style='color: #000; border-bottom: 2px solid #000; padding-bottom: 10px;'>EVENT DETAILS</h3>
                <table style='width: 100%; margin-top: 15px;'>
                    <tr><td style='padding: 8px 0; width: 150px;'><strong>Event Type:</strong></td><td>" . htmlspecialchars($booking['EventType']) . "</td></tr>
                    <tr><td style='padding: 8px 0;'><strong>Event Date:</strong></td><td>" . date('F d, Y', strtotime($booking['EventDate'])) . "</td></tr>
                    <tr><td style='padding: 8px 0;'><strong>Number of Guests:</strong></td><td>" . htmlspecialchars($booking['NumberOfGuests']) . "</td></tr>
                    <tr><td style='padding: 8px 0;'><strong>Location:</strong></td><td>" . htmlspecialchars($booking['EventLocation']) . "</td></tr>
                </table>
            </div>
            
            <div style='margin: 30px 0;'>
                <h3 style='color: #000; border-bottom: 2px solid #000; padding-bottom: 10px;'>FINANCIAL TERMS</h3>
                <div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin-top: 15px;'>
                    <p style='font-size: 18px; margin: 0;'><strong>Total Amount:</strong> <span style='color: #000; font-size: 24px;'>₱" . number_format($booking['TotalAmount'], 2) . "</span></p>
                </div>
            </div>
            
            <div style='margin: 30px 0;'>
                <h3 style='color: #000; border-bottom: 2px solid #000; padding-bottom: 10px;'>TERMS AND CONDITIONS</h3>
                <ol style='line-height: 1.8; color: #333;'>
                    <li>This agreement is subject to the terms and conditions outlined herein.</li>
                    <li>The client agrees to provide accurate information about the event.</li>
                    <li>Payment must be completed before the event date.</li>
                    <li>Cancellations must be made at least 7 days before the event date.</li>
                    <li>The catering service reserves the right to modify menu items based on availability.</li>
                    <li>Any additional services requested must be confirmed in writing.</li>
                </ol>
            </div>
            
            <div style='margin-top: 60px; padding-top: 20px; border-top: 1px solid #ddd;'>
                <p style='text-align: center; color: #666; font-style: italic;'>
                    This agreement will be signed electronically by the client.
                </p>
            </div>
        </div>
        ";
        
        $contractFileBase64 = base64_encode($contractHTML);
        $adminID = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : null;
        
        // Insert agreement
        if ($usingPDO) {
            // PDO version
            if ($adminID) {
                $insertQuery = "INSERT INTO agreement (BookingID, ClientID, AdminID, ContractFile, Status, CreatedAt) VALUES (?, ?, ?, ?, 'unsigned', NOW())";
                $stmt = $conn->prepare($insertQuery);
                $stmt->execute([$bookingID, $booking['ClientID'], $adminID, $contractFileBase64]);
            } else {
                $insertQuery = "INSERT INTO agreement (BookingID, ClientID, ContractFile, Status, CreatedAt) VALUES (?, ?, ?, 'unsigned', NOW())";
                $stmt = $conn->prepare($insertQuery);
                $stmt->execute([$bookingID, $booking['ClientID'], $contractFileBase64]);
            }
            
            $agreementID = $conn->lastInsertId();
        } else {
            // mysqli version
            if ($adminID) {
                $insertQuery = "INSERT INTO agreement (BookingID, ClientID, AdminID, ContractFile, Status, CreatedAt) VALUES (?, ?, ?, ?, 'unsigned', NOW())";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("iiis", $bookingID, $booking['ClientID'], $adminID, $contractFileBase64);
            } else {
                $insertQuery = "INSERT INTO agreement (BookingID, ClientID, ContractFile, Status, CreatedAt) VALUES (?, ?, ?, 'unsigned', NOW())";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("iis", $bookingID, $booking['ClientID'], $contractFileBase64);
            }
            
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                sendJsonResponse(500, false, 'Failed to create agreement: ' . $error);
            }
            
            $agreementID = $stmt->insert_id;
            $stmt->close();
        }
        
        sendJsonResponse(201, true, 'Agreement created successfully', [
            'agreement_id' => $agreementID,
            'booking_id' => $bookingID
        ]);
    }
    
    // Default response
    sendJsonResponse(400, false, 'Invalid action or method');
    
} catch (Exception $e) {
    error_log("Agreements API Error: " . $e->getMessage());
    sendJsonResponse(500, false, $e->getMessage());
}
?>