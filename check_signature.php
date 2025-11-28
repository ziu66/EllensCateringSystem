<?php
$conn = new mysqli('localhost', 'root', '', 'catering_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if Ellen's signature exists
$query = "SELECT config_key, config_value FROM system_config WHERE config_key = 'elma_signature'";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Ellen's signature EXISTS in system_config\n";
    echo "Value length: " . strlen($row['config_value']) . " characters\n";
    echo "First 100 chars: " . substr($row['config_value'], 0, 100) . "\n";
} else {
    echo "Ellen's signature NOT FOUND in system_config\n";
    
    // List all config keys
    $all = $conn->query("SELECT config_key FROM system_config");
    echo "\nAll config keys in system_config:\n";
    while ($r = $all->fetch_assoc()) {
        echo "- " . $r['config_key'] . "\n";
    }
}

$conn->close();
?>
