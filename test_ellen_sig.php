<?php
// Quick test to verify Ellen's signature can be retrieved

$conn = new mysqli('localhost', 'root', '', 'catering_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Testing Ellen's signature retrieval:\n\n";

// Test the query
$query = "SELECT config_value FROM system_config WHERE config_key = 'elma_signature' LIMIT 1";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "✅ Ellen's signature found!\n";
    echo "Length: " . strlen($row['config_value']) . " characters\n";
    echo "First 100 chars: " . substr($row['config_value'], 0, 100) . "\n";
} else {
    echo "❌ Ellen's signature NOT found\n";
}

$conn->close();
?>
