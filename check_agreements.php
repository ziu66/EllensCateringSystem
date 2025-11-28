<?php
$conn = new mysqli('localhost', 'root', '', 'catering_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check the latest agreements
$result = $conn->query('SELECT AgreementID, BookingID, CateringSignature FROM agreement ORDER BY AgreementID DESC LIMIT 5');
echo "Latest 5 agreements:\n\n";

$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "[$count] AgreementID: {$row['AgreementID']}, BookingID: {$row['BookingID']}\n";
    echo "    CateringSignature length: " . strlen($row['CateringSignature']) . " chars\n";
    
    if (strlen($row['CateringSignature']) > 0) {
        echo "    First 80 chars: " . substr($row['CateringSignature'], 0, 80) . "\n\n";
    } else {
        echo "    ⚠️  CateringSignature is EMPTY!\n\n";
    }
}

$conn->close();
?>
