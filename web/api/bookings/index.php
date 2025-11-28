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

// Check if this is a customer payment method request (doesn't require admin auth)
$isPaymentMethodRequest = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save_payment_method');

// Only require admin authentication for non-payment-method requests
if (!$isPaymentMethodRequest) {
    requireAuth();
}

// Get database connection
$conn = getDbConnection();

if (!$conn) {
    sendResponse(false, 'Database connection failed', null, 500);
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle POST method for special actions
if ($method === 'POST' && isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'save_payment_method') {
        handleSavePaymentMethod($conn);
    } elseif ($action === 'confirm_payment') {
        handleConfirmPayment($conn);
    } else {
        sendResponse(false, 'Invalid action', null, 400);
    }
    exit;
}

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
        // Check if requesting pending payments (admin only)
        if (isset($_GET['pending']) && $_GET['pending'] === 'true') {
            handleGetPendingPayments($conn);
        } else {
            handleGetBookings($conn);
        }
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
 * POST: Confirm payment for a booking
 * Admin only - Updates PaymentStatus to 'Paid' and sets PaymentDate
 */
function handleConfirmPayment($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['booking_id']) || empty($data['booking_id'])) {
            sendResponse(false, 'Booking ID is required', null, 400);
        }
        
        $bookingID = (int)$data['booking_id'];
        
        // Check if booking exists
        $checkQuery = "SELECT BookingID, PaymentStatus, PaymentMethod FROM booking WHERE BookingID = :bookingID LIMIT 1";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([':bookingID' => $bookingID]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            sendResponse(false, 'Booking not found', null, 404);
        }
        
        // Check if payment method was selected
        if (!$booking['PaymentMethod']) {
            sendResponse(false, 'Payment method has not been selected by customer yet', null, 400);
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Update booking with paid status and date
            $updateQuery = "UPDATE booking 
                           SET PaymentStatus = 'Paid',
                               PaymentDate = NOW()
                           WHERE BookingID = :bookingID";
            $stmt = $conn->prepare($updateQuery);
            $stmt->execute([':bookingID' => $bookingID]);
            
            // Get booking details for agreement creation
            $bookingDetailQuery = "SELECT BookingID, ClientID FROM booking WHERE BookingID = :bookingID LIMIT 1";
            $stmtBooking = $conn->prepare($bookingDetailQuery);
            $stmtBooking->execute([':bookingID' => $bookingID]);
            $bookingDetail = $stmtBooking->fetch();
            
            if (!$bookingDetail) {
                throw new Exception('Could not retrieve booking details');
            }
            
            // Create agreement record if it doesn't exist
            $checkAgreementQuery = "SELECT AgreementID FROM agreement WHERE BookingID = :bookingID LIMIT 1";
            $stmtCheckAgreement = $conn->prepare($checkAgreementQuery);
            $stmtCheckAgreement->execute([':bookingID' => $bookingID]);
            
            if ($stmtCheckAgreement->rowCount() === 0) {
                // Agreement doesn't exist, create it
                // First, get the booking details to generate contract
                $bookingDetailsQuery = "
                    SELECT b.BookingID, b.ClientID, b.EventType, b.EventDate, b.EventLocation, b.NumberOfGuests, b.TotalAmount,
                           c.Name, c.Email, c.ContactNumber
                    FROM booking b
                    INNER JOIN client c ON b.ClientID = c.ClientID
                    WHERE b.BookingID = :bookingID LIMIT 1
                ";
                $bookingStmt = $conn->prepare($bookingDetailsQuery);
                $bookingStmt->execute([':bookingID' => $bookingDetail['BookingID']]);
                $bookingData = $bookingStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($bookingData) {
                    // Generate contract HTML
                    $contractDate = date('F d, Y');
                    $eventDate = date('M d, Y', strtotime($bookingData['EventDate']));
                    $totalAmount = number_format($bookingData['TotalAmount'], 2);
                    $clientName = htmlspecialchars($bookingData['Name']);
                    $clientEmail = htmlspecialchars($bookingData['Email']);
                    $clientPhone = htmlspecialchars($bookingData['ContactNumber']);
                    $eventType = htmlspecialchars($bookingData['EventType']);
                    $eventLocation = htmlspecialchars($bookingData['EventLocation']);
                    $numGuests = htmlspecialchars($bookingData['NumberOfGuests']);
                    
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
                    
                    $contractFileBase64 = base64_encode($contractHTML);
                    
                    // Get Ellen's signature from system_config
                    $ellensSignature = null;
                    $ellensSignatureQuery = "SELECT config_value FROM system_config WHERE config_key = 'elma_signature' LIMIT 1";
                    $ellensSignatureStmt = $conn->prepare($ellensSignatureQuery);
                    $ellensSignatureStmt->execute();
                    $ellensSignatureRow = $ellensSignatureStmt->fetch(PDO::FETCH_ASSOC);
                    if ($ellensSignatureRow) {
                        $ellensSignature = $ellensSignatureRow['config_value'];
                    }
                }
                
                // Create agreement with contract and Ellen's signature
                $createAgreementQuery = "INSERT INTO agreement 
                                        (BookingID, ClientID, ContractFile, CateringSignature, Status, CreatedAt, UpdatedAt) 
                                        VALUES 
                                        (:bookingID, :clientID, :contractFile, :cateringSignature, 'unsigned', NOW(), NOW())";
                
                $stmtCreateAgreement = $conn->prepare($createAgreementQuery);
                $stmtCreateAgreement->execute([
                    ':bookingID' => $bookingDetail['BookingID'],
                    ':clientID' => $bookingDetail['ClientID'],
                    ':contractFile' => $contractFileBase64 ?? null,
                    ':cateringSignature' => $ellensSignature ?? null
                ]);
            }
            
            $conn->commit();
            
            // Log activity
            logActivity($conn, getCurrentAdminId(), 'admin', 'payment_confirmed', 
                       "Confirmed payment for booking #$bookingID via {$booking['PaymentMethod']}", $_SERVER['REMOTE_ADDR']);
            
            sendResponse(true, 'Payment confirmed successfully', [
                'booking_id' => $bookingID,
                'payment_status' => 'Paid',
                'payment_date' => date('Y-m-d H:i:s')
            ], 200);
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $e;
        }
        
    } catch (Exception $e) {
        if ($conn && $conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Confirm Payment Error: " . $e->getMessage());
        sendResponse(false, 'Error confirming payment: ' . $e->getMessage(), null, 500);
    }
}

/**
 * GET: Retrieve pending payments for admin
 * Returns bookings with PaymentStatus = 'Processing'
 */
function handleGetPendingPayments($conn) {
    try {
        // Get query parameters
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        $paymentMethod = $_GET['payment_method'] ?? null;
        
        // Base query for pending payments
        $query = "SELECT 
                    b.BookingID,
                    b.ClientID,
                    c.Name as ClientName,
                    c.Email as ClientEmail,
                    c.ContactNumber,
                    b.EventType,
                    b.EventDate,
                    b.EventLocation,
                    b.NumberOfGuests,
                    b.Status as BookingStatus,
                    b.TotalAmount,
                    b.PaymentMethod,
                    b.PaymentStatus,
                    b.GCashReference,
                    b.BankReferenceNumber,
                    b.BankSenderName,
                    b.CreatedAt,
                    q.QuotationID,
                    q.Status as QuotationStatus
                  FROM booking b
                  LEFT JOIN client c ON b.ClientID = c.ClientID
                  LEFT JOIN quotation q ON b.BookingID = q.BookingID
                  WHERE b.PaymentStatus = 'Processing'
                  AND b.Status = 'Confirmed'";
        
        $params = [];
        
        // Filter by payment method if specified
        if ($paymentMethod && in_array($paymentMethod, ['Cash', 'GCash', 'Bank Transfer'])) {
            $query .= " AND b.PaymentMethod = :paymentMethod";
            $params[':paymentMethod'] = $paymentMethod;
        }
        
        // Order by newest first (most recent)
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
        $pendingPayments = $stmt->fetchAll();
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM booking b 
                       WHERE b.PaymentStatus = 'Processing' AND b.Status = 'Confirmed'";
        
        $countParams = [];
        if ($paymentMethod && in_array($paymentMethod, ['Cash', 'GCash', 'Bank Transfer'])) {
            $countQuery .= " AND b.PaymentMethod = :paymentMethod";
            $countParams[':paymentMethod'] = $paymentMethod;
        }
        
        $stmtCount = $conn->prepare($countQuery);
        foreach ($countParams as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $totalCount = $stmtCount->fetch()['total'] ?? 0;
        
        sendResponse(true, 'Pending payments retrieved successfully', [
            'payments' => $pendingPayments,
            'total' => $totalCount,
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ], 200);
        
    } catch (PDOException $e) {
        error_log("Get Pending Payments Error: " . $e->getMessage());
        sendResponse(false, 'Error retrieving pending payments', null, 500);
    }
}

/**
 * POST: Save payment method when customer selects payment option
 * Sets PaymentStatus to 'Processing' and stores the payment method
 * Does NOT require admin authentication
 */
function handleSavePaymentMethod($conn) {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['booking_id']) || empty($data['booking_id'])) {
            sendResponse(false, 'Booking ID is required', null, 400);
        }
        
        if (!isset($data['payment_method']) || empty($data['payment_method'])) {
            sendResponse(false, 'Payment method is required', null, 400);
        }
        
        if (!isset($data['client_id']) || empty($data['client_id'])) {
            sendResponse(false, 'Client ID is required', null, 400);
        }
        
        $bookingID = (int)$data['booking_id'];
        $clientID = (int)$data['client_id'];
        $paymentMethod = sanitizeInput($data['payment_method']);
        
        // Validate payment method
        if (!in_array($paymentMethod, ['Cash', 'GCash', 'Bank Transfer'])) {
            sendResponse(false, 'Invalid payment method', null, 400);
        }
        
        // Check if booking exists AND belongs to the requesting client
        $checkQuery = "SELECT BookingID, ClientID, Status FROM booking WHERE BookingID = :bookingID AND ClientID = :clientID LIMIT 1";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([':bookingID' => $bookingID, ':clientID' => $clientID]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            sendResponse(false, 'Booking not found or you do not have permission to update it', null, 404);
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Update booking with payment method and status
            $updateQuery = "UPDATE booking 
                           SET PaymentMethod = :paymentMethod,
                               PaymentStatus = 'Processing'
                           WHERE BookingID = :bookingID";
            $stmt = $conn->prepare($updateQuery);
            $stmt->execute([
                ':paymentMethod' => $paymentMethod,
                ':bookingID' => $bookingID
            ]);
            
            $conn->commit();
            
            // Log activity
            logActivity($conn, $clientID, 'client', 'payment_method_selected', 
                       "Selected payment method: $paymentMethod for booking #$bookingID", $_SERVER['REMOTE_ADDR']);
            
            sendResponse(true, 'Payment method saved successfully', [
                'booking_id' => $bookingID,
                'payment_method' => $paymentMethod,
                'payment_status' => 'Processing'
            ], 200);
            
        } catch (PDOException $e) {
            $conn->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Save Payment Method Error: " . $e->getMessage());
        sendResponse(false, 'Error saving payment method', null, 500);
    }
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
                    b.PaymentStatus,
                    b.PaymentMethod,
                    b.PaymentDate,
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

        // **NEW CODE - ADD THIS**
        // If trying to confirm booking, check if quotation is approved first
        if (isset($data['status']) && $data['status'] === 'Confirmed') {
            $checkQuotation = "SELECT Status FROM quotation WHERE BookingID = :bookingID LIMIT 1";
            $stmtCheck = $conn->prepare($checkQuotation);
            $stmtCheck->execute([':bookingID' => $bookingID]);
            $quotation = $stmtCheck->fetch();
            
            if (!$quotation) {
                sendResponse(false, 'No quotation found for this booking. Please create a quotation first before confirming.', null, 400);
            }
            
            if ($quotation['Status'] !== 'Approved') {
                $statusMessages = [
                    'Pending' => 'The quotation is still pending approval. Please approve the quotation first.',
                    'Rejected' => 'The quotation has been rejected. Cannot confirm this booking.',
                    'Cancelled' => 'The quotation has been cancelled. Cannot confirm this booking.'
                ];
                
                $message = $statusMessages[$quotation['Status']] ?? 'Quotation status is ' . $quotation['Status'] . '. Only approved quotations can be confirmed.';
                sendResponse(false, $message, null, 400);
            }
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
        
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Update booking
            $query = "UPDATE booking SET " . implode(', ', $updateFields) . " WHERE BookingID = :bookingID";
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            
            $conn->commit();
            
            // Log activity
            logActivity($conn, getCurrentAdminId(), 'admin', 'booking_updated', "Updated booking #$bookingID", $_SERVER['REMOTE_ADDR']);
            
            sendResponse(true, 'Booking updated successfully', null, 200);
            
        } catch (PDOException $e) {
            $conn->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
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