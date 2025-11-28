<?php
// PDF Generator for Agreements
// Place in: C:\xampp1\htdocs\EllensCateringSystem\includes\pdf_generator.php

require_once __DIR__ . '/signature_helper.php';

function logPDF($message) {
    $logFile = __DIR__ . '/../pdf_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    file_put_contents('php://stderr', "[$timestamp] $message\n");
    error_log($message);
}

/**
 * Generate agreement PDF/HTML with booking details
 */
function generateAgreementPDF($bookingID, $clientID, $conn) {
    try {
        // Fetch booking details
        $stmt = $conn->prepare("
            SELECT b.*, c.Name as ClientName, c.Email, c.ContactNumber, c.Address,
                   q.EstimatedPrice, q.SpecialRequest, q.SpecialRequestItems
            FROM booking b
            JOIN client c ON b.ClientID = c.ClientID
            LEFT JOIN quotation q ON b.BookingID = q.BookingID
            WHERE b.BookingID = ? AND b.ClientID = ?
        ");
        
        $stmt->bind_param("ii", $bookingID, $clientID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            error_log("No booking found for ID: $bookingID, Client: $clientID");
            return false;
        }
        
        $booking = $result->fetch_assoc();
        $stmt->close();
        
        // Format dates
        $eventDate = date('F d, Y', strtotime($booking['EventDate']));
        $effectiveDate = date('F d, Y', strtotime($booking['EventDate']));
        
        // Parse special request items
        $specialRequestItems = '';
        if (!empty($booking['SpecialRequestItems'])) {
            $items = json_decode($booking['SpecialRequestItems'], true);
            if ($items) {
                foreach ($items as $item) {
                    $specialRequestItems .= "- " . htmlspecialchars($item['name']) . "\n";
                }
            }
        }
        
        // Clean special requests
        $specialRequests = htmlspecialchars($booking['SpecialRequests'] ?? $booking['SpecialRequest'] ?? 'None');
        
        // Get Elma's signature from database
        $elmaSignature = getElmaSignature($conn);
        
        // Generate HTML with signature
        $html = generateAgreementHTML($booking, $eventDate, $effectiveDate, $specialRequests, $elmaSignature);
        
        // Encode to base64
        $pdfBase64 = base64_encode($html);
        
        error_log("PDF generated successfully for booking $bookingID - Size: " . strlen($pdfBase64) . " bytes");
        
        return $pdfBase64;
        
    } catch (Exception $e) {
        error_log("PDF Generation Error: " . $e->getMessage());
        return false;
    }
}

function generateAgreementHTML($booking, $eventDate, $effectiveDate, $specialRequests, $elmaSignature) {
    $clientName = htmlspecialchars($booking['ClientName']);
    $eventType = htmlspecialchars($booking['EventType']);
    $location = htmlspecialchars($booking['EventLocation']);
    $guests = intval($booking['NumberOfGuests']);
    $totalAmount = number_format($booking['TotalAmount'], 2);
    
    return <<<HTML
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .signature-image {
            max-width: 200px;
            max-height: 60px;
            margin: 10px auto;
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
        <p>This Catering Agreement (hereinafter referred to as the <strong>"Agreement"</strong>) is entered into on <strong>$effectiveDate</strong>, (the <strong>"Effective date"</strong>), by and between <strong>$clientName </strong> (hereinafter referred to as a <strong>"Client"</strong>) and <strong>Elma M. Barcelon</strong>, with an address of <strong>Narra 1 st, Malaruhatan, Lian, Batangas</strong> (hereinafter referred to as the <strong>"Caterer"</strong>) collectively referred to as the <strong>"Parties"</strong>, both of whom agree to be bound by this agreement.</p>
        
        <p>The <strong>Caterer</strong> guarantees that all food will be prepared, stored, and served in compliance with all applicable health and safety regulations to prevent contamination or foodborne illness caused by the food provided. <strong>The Caterer agrees to assume full responsibility and indemnify the Client for any resulting medical cost or liabilities.</strong></p>
    </div>
    
    <div class="section-title">EVENT DATE AND LOCATION</div>
    <div class="section-content">
        <p>The event will occur on <strong>$eventDate</strong>. It will be located at <strong>$location</strong>.</p>
    </div>
    
    <div class="section-title">EVENT DETAILS</div>
    <table>
        <tr>
            <td>Event Type:</td>
            <td>$eventType</td>
        </tr>
        <tr>
            <td>Number of Guests:</td>
            <td>$guests</td>
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
            <div class="signature-title">$clientName </div>
        </div>
        <div class="signature-block">
            <div class="signature-line">
                <img src="$elmaSignature" class="signature-image" alt="Elma Barcelon Signature">
            </div>
            <div class="signature-name">ELMA BARCELON</div>
            <div class="signature-title">CATERER</div>
        </div>
    </div>
</body>
</html>
HTML;
}
?>