<?php
require_once 'config/database.php';

try {
    $conn = getDB();

    // Check if column already exists
    $result = $conn->query("SHOW COLUMNS FROM booking LIKE 'GCashReferenceNumber'");
    if ($result && $result->num_rows == 0) {
        // Add the column if it doesn't exist
        if ($conn->query("ALTER TABLE booking ADD COLUMN GCashReferenceNumber VARCHAR(50) NULL DEFAULT NULL AFTER PaymentMethod")) {
            echo "✓ Column 'GCashReferenceNumber' added successfully to booking table\n";
        } else {
            echo "Error adding column: " . $conn->error . "\n";
        }
    } else {
        echo "✓ Column 'GCashReferenceNumber' already exists\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
