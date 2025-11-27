<?php
require_once '../../../config/database.php';
require_once '../../../includes/security.php';

startSecureSession();

header('Content-Type: application/json');

$conn = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Save customer signature and generate agreement PDF
if ($action === 'save_signature' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $bookingID = intval($data['booking_id'] ?? 0);
    $clientID = intval($data['client_id'] ?? 0);
    $signature = $data['signature'] ?? ''; // base64 string
    
    if (!$bookingID || !$clientID || !$signature) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        // Verify booking belongs to client
        $verifyStmt = $conn->prepare("SELECT b.BookingID FROM booking b WHERE b.BookingID = ? AND b.ClientID = ?");
        $verifyStmt->bind_param("ii", $bookingID, $clientID);
        $verifyStmt->execute();
        $result = $verifyStmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            exit;
        }
        
        $verifyStmt->close();
        
        // Generate PDF with booking details and signature
        require_once '../../../includes/pdf_generator.php';
        $pdfContent = generateAgreementPDF($bookingID, $clientID, $conn);
        
        if (!$pdfContent) {
            throw new Exception('Failed to generate PDF');
        }
        
        // Update agreement with signature and PDF
        $updateStmt = $conn->prepare("
            UPDATE agreement 
            SET CustomerSignature = ?, 
                ContractFile = ?, 
                Status = 'signed', 
                SignedDate = NOW()
            WHERE BookingID = ?
        ");
        
        $updateStmt->bind_param("ssi", $signature, $pdfContent, $bookingID);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to save agreement: ' . $updateStmt->error);
        }
        
        $updateStmt->close();
        
        logActivity($clientID, 'client', 'agreement_signed', "Signed agreement for booking #$bookingID");
        
        echo json_encode([
            'success' => true,
            'message' => 'Agreement signed successfully',
            'agreement_id' => $conn->insert_id
        ]);
        
    } catch (Exception $e) {
        error_log('Agreement save error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit;
}

// Get agreement for a booking
if ($action === 'get_agreement' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $bookingID = intval($_GET['booking_id'] ?? 0);
    $clientID = intval($_GET['client_id'] ?? 0);
    
    if (!$bookingID || !$clientID) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        // First check if agreement exists
        $checkStmt = $conn->prepare("
            SELECT a.AgreementID, a.Status, a.SignedDate, a.CustomerSignature, a.ContractFile
            FROM agreement a
            JOIN booking b ON a.BookingID = b.BookingID
            WHERE a.BookingID = ? AND b.ClientID = ?
        ");
        
        $checkStmt->bind_param("ii", $bookingID, $clientID);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            // Agreement record doesn't exist, return error
            echo json_encode(['success' => false, 'message' => 'Agreement not found']);
            $checkStmt->close();
            exit;
        }
        
        $agreement = $result->fetch_assoc();
        $checkStmt->close();
        
        // If ContractFile is empty, generate it on-the-fly
        if (empty($agreement['ContractFile'])) {
            file_put_contents('php://stderr', "API: ContractFile is empty for booking $bookingID, generating on-the-fly...\n");
            error_log("DEBUG: ContractFile is empty for booking $bookingID, generating on-the-fly...");
            require_once '../../../includes/pdf_generator.php';
            $pdfContent = generateAgreementPDF($bookingID, $clientID, $conn);
            
            file_put_contents('php://stderr', "API: PDF generation returned: " . ($pdfContent ? "SUCCESS (" . strlen($pdfContent) . " bytes)" : "FALSE") . "\n");
            error_log("DEBUG: PDF generation returned: " . ($pdfContent ? "SUCCESS (" . strlen($pdfContent) . " bytes)" : "FALSE"));
            
            if ($pdfContent) {
                file_put_contents('php://stderr', "API: Successfully generated PDF for booking $bookingID\n");
                error_log("DEBUG: Successfully generated PDF for booking $bookingID");
                // Update the agreement with the generated content
                $updateStmt = $conn->prepare("
                    UPDATE agreement 
                    SET ContractFile = ?
                    WHERE BookingID = ?
                ");
                
                if ($updateStmt) {
                    $updateStmt->bind_param("si", $pdfContent, $bookingID);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    // Use the generated PDF in response
                    $agreement['ContractFile'] = $pdfContent;
                } else {
                    file_put_contents('php://stderr', "API: Error preparing update statement: " . $conn->error . "\n");
                    error_log("ERROR: Error preparing update statement: " . $conn->error);
                    // Still return the generated PDF even if we can't save it
                    $agreement['ContractFile'] = $pdfContent;
                }
            } else {
                file_put_contents('php://stderr', "API: PDF generation FAILED for booking $bookingID, client $clientID\n");
                error_log("ERROR: PDF generation FAILED for booking $bookingID, client $clientID - returning error to client");
                echo json_encode(['success' => false, 'message' => 'Failed to generate agreement. Check server logs.']);
                exit;
            }
        }
        
        echo json_encode([
            'success' => true,
            'agreement' => $agreement
        ]);
        
    } catch (Exception $e) {
        error_log('Agreement fetch error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit;
}

// Admin: Get all agreements for a booking
if ($action === 'admin_get_agreement' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Verify admin session
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $bookingID = intval($_GET['booking_id'] ?? 0);
    
    if (!$bookingID) {
        echo json_encode(['success' => false, 'message' => 'Missing booking ID']);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT a.AgreementID, a.Status, a.SignedDate, a.CustomerSignature, a.ContractFile, 
                   c.FirstName, c.LastName, b.EventDate
            FROM agreement a
            JOIN booking b ON a.BookingID = b.BookingID
            JOIN client c ON b.ClientID = c.ClientID
            WHERE a.BookingID = ?
        ");
        
        $stmt->bind_param("i", $bookingID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Agreement not found']);
            exit;
        }
        
        $agreement = $result->fetch_assoc();
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'agreement' => $agreement
        ]);
        
    } catch (Exception $e) {
        error_log('Admin agreement fetch error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit;
}

// Download/View PDF
if ($action === 'download_pdf' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $agreementID = intval($_GET['agreement_id'] ?? 0);
    $clientID = intval($_GET['client_id'] ?? 0);
    
    if (!$agreementID || !$clientID) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT a.ContractFile, b.BookingID
            FROM agreement a
            JOIN booking b ON a.BookingID = b.BookingID
            WHERE a.AgreementID = ? AND b.ClientID = ?
        ");
        
        $stmt->bind_param("ii", $agreementID, $clientID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Agreement not found']);
            exit;
        }
        
        $row = $result->fetch_assoc();
        $stmt->close();
        
        // Decode base64 PDF
        $pdfContent = base64_decode($row['ContractFile']);
        
        if (!$pdfContent) {
            throw new Exception('Invalid PDF data');
        }
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Agreement_Booking_' . $row['BookingID'] . '.pdf"');
        echo $pdfContent;
        
    } catch (Exception $e) {
        error_log('PDF download error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
