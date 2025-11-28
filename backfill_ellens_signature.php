<?php
// Backfill existing agreements with Ellen's signature from system_config

$conn = new mysqli('localhost', 'root', '', 'catering_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get Ellen's signature
$query = "SELECT config_value FROM system_config WHERE config_key = 'elma_signature' LIMIT 1";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    die("Ellen's signature not found in system_config\n");
}

$row = $result->fetch_assoc();
$ellensSignature = $row['config_value'];

echo "Ellen's signature found. Length: " . strlen($ellensSignature) . " characters\n\n";

// Update all agreements that have no CateringSignature
$updateQuery = "UPDATE agreement SET CateringSignature = ? WHERE CateringSignature IS NULL OR CateringSignature = ''";
$stmt = $conn->prepare($updateQuery);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $ellensSignature);

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$affectedRows = $conn->affected_rows;
$stmt->close();

echo "✅ Updated $affectedRows agreements with Ellen's signature\n";

// Verify
$verifyQuery = "SELECT COUNT(*) as count FROM agreement WHERE CateringSignature IS NOT NULL AND CateringSignature != ''";
$verifyResult = $conn->query($verifyQuery);
$verifyRow = $verifyResult->fetch_assoc();

echo "Total agreements with CateringSignature: " . $verifyRow['count'] . "\n";

$conn->close();
?>
