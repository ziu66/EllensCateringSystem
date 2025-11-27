<?php
/**
 * Debug Agreement System
 * Check what's happening with agreement creation
 */

require_once 'config/database.php';

$conn = getDB();

if (!$conn) {
    die("Database connection failed");
}

echo "<h1>🔍 Agreement System Debug</h1>";

// Test 1: Check if there are any paid bookings
echo "<h2>Step 1: Check Paid Bookings</h2>";
$paidBookings = $conn->query("SELECT BookingID, ClientID, PaymentStatus FROM booking WHERE PaymentStatus = 'Paid' LIMIT 5");

if ($paidBookings->num_rows === 0) {
    echo "<p style='color: red;'><strong>❌ NO PAID BOOKINGS FOUND!</strong></p>";
    echo "<p>You must confirm a payment first before testing.</p>";
    
    // Show processing bookings instead
    $processingBookings = $conn->query("SELECT BookingID, ClientID, PaymentStatus, PaymentMethod FROM booking WHERE PaymentStatus = 'Processing' LIMIT 3");
    echo "<p>Processing bookings available to confirm:</p>";
    echo "<pre>";
    while ($row = $processingBookings->fetch_assoc()) {
        echo "Booking #{$row['BookingID']} - Client #{$row['ClientID']} - Method: {$row['PaymentMethod']}\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color: green;'><strong>✓ Found Paid Bookings:</strong></p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>BookingID</th><th>ClientID</th><th>PaymentStatus</th></tr>";
    
    $paidBookings = $conn->query("SELECT BookingID, ClientID, PaymentStatus FROM booking WHERE PaymentStatus = 'Paid'");
    while ($row = $paidBookings->fetch_assoc()) {
        echo "<tr>";
        echo "<td>#" . $row['BookingID'] . "</td>";
        echo "<td>" . $row['ClientID'] . "</td>";
        echo "<td>" . $row['PaymentStatus'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 2: Check if agreement records exist
echo "<h2>Step 2: Check Agreement Records</h2>";
$allAgreements = $conn->query("SELECT AgreementID, BookingID, ClientID, Status, ContractFile FROM agreement");

if ($allAgreements->num_rows === 0) {
    echo "<p style='color: red;'><strong>❌ NO AGREEMENT RECORDS EXIST!</strong></p>";
    echo "<p>This means when admin confirmed payment, the agreement wasn't created.</p>";
} else {
    echo "<p style='color: green;'><strong>✓ Agreement Records Found:</strong></p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>AgreementID</th><th>BookingID</th><th>ClientID</th><th>Status</th><th>ContractFile</th></tr>";
    
    while ($row = $allAgreements->fetch_assoc()) {
        $contractFileStatus = empty($row['ContractFile']) ? "❌ EMPTY" : "✓ EXISTS (" . strlen($row['ContractFile']) . " bytes)";
        echo "<tr>";
        echo "<td>" . $row['AgreementID'] . "</td>";
        echo "<td>#" . $row['BookingID'] . "</td>";
        echo "<td>" . $row['ClientID'] . "</td>";
        echo "<td>" . $row['Status'] . "</td>";
        echo "<td>" . $contractFileStatus . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 3: Test manual agreement creation
echo "<h2>Step 3: Test Manual Agreement Creation</h2>";

$testBooking = $conn->query("SELECT BookingID, ClientID FROM booking LIMIT 1");
if ($testBooking->num_rows > 0) {
    $booking = $testBooking->fetch_assoc();
    $testBookingID = $booking['BookingID'];
    $testClientID = $booking['ClientID'];
    
    echo "<p>Testing with Booking #{$testBookingID}, Client #{$testClientID}</p>";
    
    // Try to insert a test agreement
    $testStmt = $conn->prepare("INSERT INTO agreement (BookingID, ClientID, Status, CreatedAt, UpdatedAt) VALUES (?, ?, 'unsigned', NOW(), NOW())");
    $testStmt->bind_param("ii", $testBookingID, $testClientID);
    
    if ($testStmt->execute()) {
        echo "<p style='color: green;'><strong>✓ TEST INSERT SUCCESS!</strong> Agreement created with ID: {$conn->insert_id}</p>";
        
        // Verify it was created
        $verify = $conn->query("SELECT * FROM agreement WHERE BookingID = $testBookingID ORDER BY AgreementID DESC LIMIT 1");
        if ($verify->num_rows > 0) {
            $created = $verify->fetch_assoc();
            echo "<pre>";
            print_r($created);
            echo "</pre>";
        }
    } else {
        echo "<p style='color: red;'><strong>❌ TEST INSERT FAILED!</strong> Error: {$testStmt->error}</p>";
    }
}

// Test 4: Check if PDF generator works
echo "<h2>Step 4: Test PDF Generator</h2>";

require_once 'includes/pdf_generator.php';

$testBooking = $conn->query("SELECT * FROM booking LIMIT 1");
if ($testBooking->num_rows > 0) {
    $booking = $testBooking->fetch_assoc();
    
    echo "<p>Testing PDF generation for Booking #{$booking['BookingID']}...</p>";
    
    $pdf = generateAgreementPDF($booking['BookingID'], $booking['ClientID'], $conn);
    
    if ($pdf) {
        echo "<p style='color: green;'><strong>✓ PDF GENERATED!</strong> Size: " . strlen($pdf) . " bytes</p>";
        
        // Try to decode it
        $decoded = @base64_decode($pdf);
        if ($decoded && strpos($decoded, 'DOCTYPE') !== false) {
            echo "<p style='color: green;'><strong>✓ PDF IS VALID HTML!</strong></p>";
            echo "<details><summary>Click to see first 500 chars:</summary><pre>" . htmlspecialchars(substr($decoded, 0, 500)) . "...</pre></details>";
        } else {
            echo "<p style='color: orange;'>⚠️ PDF decoded but may not be valid HTML</p>";
        }
    } else {
        echo "<p style='color: red;'><strong>❌ PDF GENERATION FAILED!</strong></p>";
    }
}

$conn->close();
?>
