<?php
// PDF Generator for Agreements
// Generates HTML that can be printed or converted to PDF

// Helper function to log to file for debugging
function logPDF($message) {
    $logFile = __DIR__ . '/../pdf_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    file_put_contents('php://stderr', "[$timestamp] $message\n");
    error_log($message);
}

/**
 * Generate agreement PDF/HTML with booking details
 * Works with both PDO and MySQLi connections
 */
function generateAgreementPDF($bookingID, $clientID, $conn) {
    try {
        logPDF("Starting PDF generation for Booking $bookingID, Client $clientID");
        
        $booking = null;
        
        // Check if it's PDO
        if ($conn instanceof PDO) {
            logPDF("Using PDO connection");
            // PDO connection
            $bookingStmt = $apiConn->prepare("
                SELECT 
                    b.BookingID,
                    b.EventType,
                    b.EventDate,
                    b.EventLocation,
                    b.NumberOfGuests,
                    b.SpecialRequests,
                    b.TotalAmount,
                    c.Name as FirstName,
                    '' as LastName,
                    c.Email as EmailAddress
                FROM booking b
                JOIN client c ON b.ClientID = c.ClientID
                WHERE b.BookingID = :bookingID AND b.ClientID = :clientID
            ");
            
            $bookingStmt->execute([':bookingID' => $bookingID, ':clientID' => $clientID]);
            $booking = $bookingStmt->fetch();
        } else {
            logPDF("Using MySQLi connection");
            // MySQLi connection - use root database connection if api connection fails
            $apiConn = $conn;
            
            // Try with the passed connection first
            $bookingStmt = $apiConn->prepare("
                SELECT 
                    b.BookingID,
                    b.EventType,
                    b.EventDate,
                    b.EventLocation,
                    b.NumberOfGuests,
                    b.SpecialRequests,
                    b.TotalAmount,
                    c.Name as FirstName,
                    '' as LastName,
                    c.Email as EmailAddress
                FROM booking b
                JOIN client c ON b.ClientID = c.ClientID
                WHERE b.BookingID = ? AND b.ClientID = ?
            ");
            
            if (!$bookingStmt) {
                logPDF("prepare() failed with passed connection, trying getDB() fallback");
                // Connection failed, try getDB() fallback
                $apiConn = getDB();
                $bookingStmt = $apiConn->prepare("
                    SELECT 
                        b.BookingID,
                        b.EventType,
                        b.EventDate,
                        b.EventLocation,
                        b.NumberOfGuests,
                        b.SpecialRequests,
                        b.TotalAmount,
                        c.Name as FirstName,
                        '' as LastName,
                        c.Email as EmailAddress
                    FROM booking b
                    JOIN client c ON b.ClientID = c.ClientID
                    WHERE b.BookingID = ? AND b.ClientID = ?
                ");
                
                if (!$bookingStmt) {
                    throw new Exception('Prepare failed with both connections: ' . $apiConn->error);
                }
            }
            
            $bookingStmt->bind_param("ii", $bookingID, $clientID);
            if (!$bookingStmt->execute()) {
                throw new Exception('Execute failed: ' . $bookingStmt->error);
            }
            
            $result = $bookingStmt->get_result();
            if (!$result) {
                throw new Exception('Get result failed: ' . $apiConn->error);
            }
            
            $booking = $result->fetch_assoc();
            $bookingStmt->close();
        }
        
        if (!$booking) {
            logPDF("Booking not found for ID=$bookingID, ClientID=$clientID");
            throw new Exception("Booking #$bookingID for client #$clientID not found");
        }
        
        logPDF("Found booking - {$booking['EventType']} on {$booking['EventDate']}");
        
        // Generate HTML content for agreement
        $html = generateAgreementHTML($booking);
        
        // Store HTML as base64 for PDF-like storage
        $htmlBase64 = base64_encode($html);
        
        if (empty($htmlBase64)) {
            logPDF("HTML encoding resulted in empty base64");
            throw new Exception('HTML encoding failed');
        }
        
        logPDF("Successfully created base64 PDF of " . strlen($htmlBase64) . " bytes");
        return $htmlBase64;
        
    } catch (Exception $e) {
        logPDF("ERROR: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate HTML content for agreement based on booking details
 */
function generateAgreementHTML($booking) {
    $formattedDate = date('F d, Y', strtotime($booking['EventDate']));
    $totalAmount = number_format($booking['TotalAmount'], 2);
    $clientName = htmlspecialchars($booking['FirstName'] . ' ' . $booking['LastName']);
    $eventLocation = htmlspecialchars($booking['EventLocation']);
    $eventType = htmlspecialchars($booking['EventType']);
    $numberOfGuests = intval($booking['NumberOfGuests']);
    $specialRequests = htmlspecialchars($booking['SpecialRequests'] ?? 'None');
    
    $html = <<<EOD
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Catering Agreement - Booking #{$booking['BookingID']}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 28px;
            margin: 0;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .content {
            margin-bottom: 20px;
            text-align: justify;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-top: 25px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .section-content {
            margin-bottom: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        
        table td:first-child {
            font-weight: bold;
            width: 50%;
        }
        
        .total-section {
            text-align: center;
            margin: 30px 0;
        }
        
        .total-amount {
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0;
        }
        
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        li {
            margin: 8px 0;
        }
        
        .signature-section {
            margin-top: 50px;
        }
        
        .signature-block {
            display: inline-block;
            width: 45%;
            text-align: center;
            margin: 20px 2.5%;
            vertical-align: top;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            height: 80px;
            margin-bottom: 10px;
        }
        
        .signature-name {
            font-weight: bold;
            margin: 5px 0;
            font-size: 12px;
        }
        
        .signature-title {
            font-size: 11px;
            margin: 0;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        @media print {
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Catering Agreement</h1>
    </div>
    
    <div class="content">
        <p>This Catering Agreement (hereinafter referred to as the <strong>"Agreement"</strong>) is entered into on <strong>$formattedDate</strong>, (the <strong>"Effective date"</strong>), by and between <strong>$clientName</strong> (hereinafter referred to as a <strong>"Client"</strong>) and <strong>Elma M. Barcelon</strong>, with an address of <strong>Narra 1 st, Malaruhatan, Lian, Batangas</strong> (hereinafter referred to as the <strong>"Caterer"</strong>) collectively referred to as the <strong>"Parties"</strong>, both of whom agree to be bound by this agreement.</p>
        
        <p>The <strong>Caterer</strong> guarantees that all food will be prepared, stored, and served in compliance with all applicable health and safety regulations to prevent contamination or foodborne illness caused by the food provided. <strong>The Caterer agrees to assume full responsibility and indemnify the Client for any resulting medical cost or liabilities.</strong></p>
    </div>
    
    <div class="section-title">EVENT DATE AND LOCATION</div>
    <div class="section-content">
        <p>The event will occur on <strong>$formattedDate</strong>. It will be located at <strong>$eventLocation</strong>.</p>
    </div>
    
    <div class="section-title">EVENT DETAILS</div>
    <table>
        <tr>
            <td>Event Type:</td>
            <td>$eventType</td>
        </tr>
        <tr>
            <td>Number of Guests:</td>
            <td>$numberOfGuests</td>
        </tr>
        <tr>
            <td>Special Requests:</td>
            <td>$specialRequests</td>
        </tr>
    </table>
    
    <div class="total-section">
        <div class="section-title">OVERALL TOTAL</div>
        <div class="total-amount">₱$totalAmount</div>
    </div>
    
    <div class="section-title">TERMS AND CONDITIONS</div>
    <ul>
        <li>We require <strong>50% down payment</strong> at least two (2) weeks before the event to confirm your booking; <strong>NON-REFUNDABLE</strong>.</li>
        <li>Balance to be paid before/after the event. None payment of the full agreed amount reserves the right for the caterer to cancel all its services.</li>
    </ul>
    
    <div class="signature-section">
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-name">CLIENT SIGNATURE</div>
            <div class="signature-title">$clientName</div>
        </div>
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-name">ELMA BARCELON</div>
            <div class="signature-title">CATERER</div>
        </div>
    </div>
</body>
</html>
EOD;

    return $html;
}

?>

