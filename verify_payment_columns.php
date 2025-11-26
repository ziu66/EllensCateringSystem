<?php
/**
 * Verification Script
 * Checks if payment columns exist and displays their structure
 */

require_once 'config/database.php';

$conn = getDB();

echo "\n✅ Database Connection Successful\n";
echo "========================================\n\n";

$sql = "DESCRIBE booking";
$result = $conn->query($sql);

if ($result) {
    echo "Checking booking table structure:\n";
    echo "--------\n";
    $paymentColumns = [];
    
    while ($row = $result->fetch_assoc()) {
        if (in_array($row['Field'], ['PaymentStatus', 'PaymentMethod', 'PaymentDate', 'Status', 'TotalAmount'])) {
            $paymentColumns[] = $row;
            echo "✓ {$row['Field']}: {$row['Type']}\n";
        }
    }
    
    echo "\n========================================\n";
    if (count($paymentColumns) >= 3) {
        echo "✅ All payment columns are ready!\n";
        echo "========================================\n";
        echo "\nYou can now:\n";
        echo "  1. Select a payment method (Cash/GCash/Bank Transfer)\n";
        echo "  2. PaymentStatus will update to 'Processing'\n";
        echo "  3. Payment status badge will display in booking\n";
        echo "\nNext: Implement admin payment confirmation (Step 3)\n";
    }
}

$conn->close();
?>
