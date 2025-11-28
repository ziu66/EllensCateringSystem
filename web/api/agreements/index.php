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
ini_set('log_errors_max_len', 0);

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
        @require_once $rootConfigPath;
        $configOutput = ob_get_clean();
        
        if (!empty($configOutput)) {
            error_log("Config file output: " . $configOutput);
        }
        
        if (function_exists('getDB')) {
            $conn = getDB();
            $usingPDO = false;
        }
    }
    
    // If no connection, try web config (PDO)
    if (!$conn && file_exists($webConfigPath)) {
        ob_start();
        @require_once $webConfigPath;
        $configOutput = ob_get_clean();
        
        if (!empty($configOutput)) {
            error_log("Web config file output: " . $configOutput);
        }
        
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
        @session_start();
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
                a.CateringSignature,
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
        
        // Create agreement HTML - Single Page Format
        $contractDate = date('F d, Y');
        $eventDate = date('M d, Y', strtotime($booking['EventDate']));
        $totalAmount = number_format($booking['TotalAmount'], 2);
        $clientName = htmlspecialchars($booking['Name']);
        $clientEmail = htmlspecialchars($booking['Email']);
        $clientPhone = htmlspecialchars($booking['ContactNumber']);
        $eventType = htmlspecialchars($booking['EventType']);
        $eventLocation = htmlspecialchars($booking['EventLocation']);
        $numGuests = htmlspecialchars($booking['NumberOfGuests']);
        
        $contractHTML = <<<HTML
        <div style="font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; font-size: 13px; line-height: 1.4;">
            <div style="text-align: center; margin-bottom: 15px;">
                <h2 style="color: #000; margin: 0 0 5px 0; font-size: 18px;">CATERING SERVICE AGREEMENT</h2>
                <p style="color: #666; margin: 0; font-size: 11px;">Date: {$contractDate}</p>
            </div>
            
            <hr style="border: none; border-top: 1px solid #000; margin: 10px 0;">
            
            <div style="margin-bottom: 12px;">
                <p style="margin: 0 0 5px 0; font-weight: bold; font-size: 12px;">CLIENT: {$clientName} | EMAIL: {$clientEmail} | PHONE: {$clientPhone}</p>
            </div>
            
            <table style="width: 100%; margin-bottom: 12px; font-size: 12px;">
                <tr>
                    <td style="padding: 3px; border: 1px solid #ccc;"><strong>Event Type:</strong> {$eventType}</td>
                    <td style="padding: 3px; border: 1px solid #ccc;"><strong>Date:</strong> {$eventDate}</td>
                </tr>
                <tr>
                    <td style="padding: 3px; border: 1px solid #ccc;"><strong>Location:</strong> {$eventLocation}</td>
                    <td style="padding: 3px; border: 1px solid #ccc;"><strong>Guests:</strong> {$numGuests} pax</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 3px; border: 1px solid #ccc; background: #f5f5f5; font-weight: bold; text-align: center;">TOTAL AMOUNT: PHP {$totalAmount}</td>
                </tr>
            </table>
            
            <div style="margin-bottom: 12px; font-size: 12px;">
                <p style="margin: 0 0 5px 0; font-weight: bold;">TERMS & CONDITIONS:</p>
                <ol style="margin: 0; padding-left: 20px; color: #333;">
                    <li style="margin: 2px 0;">Payment must be completed before event date.</li>
                    <li style="margin: 2px 0;">Cancellations require 7 days notice.</li>
                    <li style="margin: 2px 0;">Menu items subject to availability.</li>
                    <li style="margin: 2px 0;">Client provides accurate event information.</li>
                    <li style="margin: 2px 0;">Additional services must be confirmed in writing.</li>
                </ol>
            </div>
            
            <div style="margin-top: 20px; padding-top: 15px;">
                <table style="width: 100%; font-size: 11px;">
                    <tr>
                        <td style="width: 50%; text-align: center; padding: 0 10px;">
                            <div id="client-signature-placeholder" style="height: 50px; margin-bottom: 8px; display: flex; align-items: center; justify-content: center;"></div>
                            <div style="border-top: 2px solid #000; padding-top: 8px;">
                                <p style="margin: 2px 0 0 0; font-weight: bold;">Client Signature</p>
                                <p style="margin: 2px 0 0 0; color: #666; font-size: 10px;">{$clientName}</p>
                            </div>
                        </td>
                        <td style="width: 50%; text-align: center; padding: 0 10px;">
                            <div id="catering-signature-placeholder" style="height: 50px; margin-bottom: 8px; display: flex; align-items: center; justify-content: center;"></div>
                            <div style="border-top: 2px solid #000; padding-top: 8px;">
                                <p style="margin: 2px 0 0 0; font-weight: bold;">Catering Representative</p>
                                <p style="margin: 2px 0 0 0; color: #666; font-size: 10px;">Ellen Barcelona</p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
HTML;
        
        // Verify contract HTML is not empty
        if (strlen($contractHTML) < 100) {
            sendJsonResponse(500, false, 'Contract content is too short or empty');
        }
        
        $contractFileBase64 = base64_encode($contractHTML);
        
        // Verify base64 encoding worked
        if (empty($contractFileBase64)) {
            sendJsonResponse(500, false, 'Failed to encode contract content');
        }
        
        $clientID = $booking['ClientID'];
        $adminID = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : null;
        
        // Get Ellen's signature from system_config
        $ellensSignature = null;
        
        if ($usingPDO) {
            // PDO version
            $ellensSignatureStmt = $conn->prepare("SELECT config_value FROM system_config WHERE config_key = 'elma_signature' LIMIT 1");
            $ellensSignatureStmt->execute();
            $ellensSignatureRow = $ellensSignatureStmt->fetch(PDO::FETCH_ASSOC);
            if ($ellensSignatureRow) {
                $ellensSignature = $ellensSignatureRow['config_value'];
            }
        } else {
            // mysqli version
            $ellensSignatureQuery = "SELECT config_value FROM system_config WHERE config_key = 'elma_signature' LIMIT 1";
            $ellensSignatureResult = $conn->query($ellensSignatureQuery);
            if ($ellensSignatureResult && $ellensSignatureResult->num_rows > 0) {
                $sigRow = $ellensSignatureResult->fetch_assoc();
                $ellensSignature = $sigRow['config_value'];
            }
        }
        
        // Insert agreement record with Ellen's signature
        $insertQuery = "INSERT INTO agreement (BookingID, ClientID, ContractFile, CateringSignature, Status, CreatedAt, UpdatedAt) VALUES (?, ?, ?, ?, 'unsigned', NOW(), NOW())";
        
        if ($usingPDO) {
            try {
                $stmt = $conn->prepare($insertQuery);
                $result = $stmt->execute([$bookingID, $clientID, $contractFileBase64, $ellensSignature]);
                if (!$result) {
                    sendJsonResponse(500, false, 'PDO execute failed: ' . implode(', ', $stmt->errorInfo()));
                }
                $agreementID = $conn->lastInsertId();
            } catch (Exception $e) {
                sendJsonResponse(500, false, 'PDO Error: ' . $e->getMessage());
            }
        } else {
            $stmt = $conn->prepare($insertQuery);
            if (!$stmt) {
                sendJsonResponse(500, false, 'Database prepare error: ' . $conn->error);
            }
            
            $stmt->bind_param("iiss", $bookingID, $clientID, $contractFileBase64, $ellensSignature);
            
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                sendJsonResponse(500, false, 'Failed to insert agreement: ' . $error);
            }
            
            $agreementID = $conn->insert_id;
            $stmt->close();
        }
        
        sendJsonResponse(201, true, 'Agreement created successfully', [
            'agreement_id' => $agreementID,
            'booking_id' => $bookingID
        ]);
    }
    
    // ===== GET AGREEMENT (for clients) =====
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_agreement') {
        
        $bookingID = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
        $clientID = isset($_GET['client_id']) ? intval($_GET['client_id']) : intval($_SESSION['client_id'] ?? 0);
        
        if ($bookingID <= 0) {
            sendJsonResponse(400, false, 'Invalid booking ID');
        }
        
        // Get agreement with authorization check
        $query = "
            SELECT 
                a.AgreementID,
                a.BookingID,
                a.ClientID,
                a.ContractFile,
                a.Status,
                a.CustomerSignature,
                a.CateringSignature,
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
            WHERE a.BookingID = ? AND a.ClientID = ?
            LIMIT 1
        ";
        
        if ($usingPDO) {
            $stmt = $conn->prepare($query);
            $stmt->execute([$bookingID, $clientID]);
            $agreement = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $bookingID, $clientID);
            $stmt->execute();
            $result = $stmt->get_result();
            $agreement = $result->fetch_assoc();
            $stmt->close();
        }
        
        if (!$agreement) {
            sendJsonResponse(404, false, 'Agreement not found or unauthorized');
        }
        
        // Check if contract file is empty
        if (empty($agreement['ContractFile'])) {
            sendJsonResponse(400, false, 'Agreement found but contract content is missing. Please contact support.', [
                'agreement_id' => $agreement['AgreementID'],
                'has_content' => false
            ]);
        }
        
        sendJsonResponse(200, true, 'Agreement retrieved successfully', [
            'agreement' => $agreement
        ]);
    }
    
    // ===== SAVE SIGNATURE =====
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save_signature') {
        
        $input = json_decode(file_get_contents('php://input'), true);
        $bookingID = isset($input['booking_id']) ? intval($input['booking_id']) : 0;
        $clientID = isset($input['client_id']) ? intval($input['client_id']) : intval($_SESSION['client_id'] ?? 0);
        $signature = isset($input['signature']) ? $input['signature'] : null;
        
        if ($bookingID <= 0 || $clientID <= 0) {
            sendJsonResponse(400, false, 'Invalid booking or client ID');
        }
        
        if (!$signature) {
            sendJsonResponse(400, false, 'Signature data is required');
        }
        
        // Check if agreement exists and belongs to this client
        $checkQuery = "SELECT AgreementID FROM agreement WHERE BookingID = ? AND ClientID = ?";
        
        if ($usingPDO) {
            $stmt = $conn->prepare($checkQuery);
            $stmt->execute([$bookingID, $clientID]);
            $agreement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$agreement) {
                sendJsonResponse(404, false, 'Agreement not found or unauthorized');
            }
            
            // Update agreement with signature
            $updateQuery = "UPDATE agreement SET Status = 'signed', CustomerSignature = ?, SignedDate = NOW(), UpdatedAt = NOW() WHERE BookingID = ? AND ClientID = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->execute([$signature, $bookingID, $clientID]);
            
            sendJsonResponse(200, true, 'Signature saved successfully', [
                'agreement_id' => $agreement['AgreementID'],
                'booking_id' => $bookingID
            ]);
        } else {
            // mysqli version
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("ii", $bookingID, $clientID);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                sendJsonResponse(404, false, 'Agreement not found or unauthorized');
            }
            
            $agreement = $result->fetch_assoc();
            $stmt->close();
            
            // Update agreement with signature
            $updateQuery = "UPDATE agreement SET Status = 'signed', CustomerSignature = ?, SignedDate = NOW(), UpdatedAt = NOW() WHERE BookingID = ? AND ClientID = ?";
            $stmt = $conn->prepare($updateQuery);
            
            if (!$stmt) {
                sendJsonResponse(500, false, 'Database prepare error: ' . $conn->error);
            }
            
            $stmt->bind_param("sii", $signature, $bookingID, $clientID);
            
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                sendJsonResponse(500, false, 'Failed to save signature: ' . $error);
            }
            
            $stmt->close();
            
            sendJsonResponse(200, true, 'Signature saved successfully', [
                'agreement_id' => $agreement['AgreementID'],
                'booking_id' => $bookingID
            ]);
        }
    }
    
    // ===== SAVE CATERING SIGNATURE (Admin Only) =====
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save_catering_signature') {
        
        // Check if admin is logged in
        if (!isset($_SESSION['admin_id'])) {
            sendJsonResponse(401, false, 'Unauthorized - Admin access required');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $bookingID = isset($input['booking_id']) ? intval($input['booking_id']) : 0;
        $cateringSignature = isset($input['signature']) ? $input['signature'] : null;
        
        if (!$cateringSignature) {
            sendJsonResponse(400, false, 'Catering signature is required');
        }
        
        if ($bookingID > 0) {
            // Update specific agreement with catering signature
            $updateQuery = "UPDATE agreement SET CateringSignature = ? WHERE BookingID = ?";
            
            if ($usingPDO) {
                $stmt = $conn->prepare($updateQuery);
                if ($stmt->execute([$cateringSignature, $bookingID])) {
                    sendJsonResponse(200, true, 'Catering signature saved successfully');
                } else {
                    sendJsonResponse(500, false, 'Failed to save catering signature');
                }
            } else {
                $stmt = $conn->prepare($updateQuery);
                if (!$stmt) {
                    sendJsonResponse(500, false, 'Database prepare error: ' . $conn->error);
                }
                
                $stmt->bind_param("si", $cateringSignature, $bookingID);
                
                if (!$stmt->execute()) {
                    $error = $stmt->error;
                    $stmt->close();
                    sendJsonResponse(500, false, 'Failed to save catering signature: ' . $error);
                }
                
                $stmt->close();
                sendJsonResponse(200, true, 'Catering signature saved successfully');
            }
        } else {
            sendJsonResponse(400, false, 'Booking ID is required');
        }
    }
    
    // Default response
    sendJsonResponse(400, false, 'Invalid action or method');
    
} catch (Exception $e) {
    sendJsonResponse(500, false, 'Error: ' . $e->getMessage());
}