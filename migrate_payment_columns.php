<?php
/**
 * Database Migration Script
 * Adds PaymentStatus, PaymentMethod, and PaymentDate columns to booking table
 * 
 * RUN THIS SCRIPT ONCE: php migrate_payment_columns.php
 */

require_once 'config/database.php';

echo "Starting database migration...\n";
echo "========================================\n\n";

$conn = getDB();

if (!$conn) {
    echo "❌ ERROR: Could not connect to database\n";
    exit(1);
}

// SQL migration command
$sql = "ALTER TABLE booking 
ADD COLUMN `PaymentStatus` enum('Pending Payment','Processing','Paid','Failed') DEFAULT 'Pending Payment',
ADD COLUMN `PaymentMethod` enum('Cash','GCash','Bank Transfer') DEFAULT NULL,
ADD COLUMN `PaymentDate` datetime DEFAULT NULL";

echo "Executing migration SQL:\n";
echo "------------------------\n";
echo $sql . "\n\n";

try {
    if ($conn->query($sql) === TRUE) {
        echo "✅ SUCCESS: Columns added successfully!\n\n";
        echo "Details:\n";
        echo "  • PaymentStatus: Added with default value 'Pending Payment'\n";
        echo "  • PaymentMethod: Added (Cash, GCash, Bank Transfer)\n";
        echo "  • PaymentDate: Added for tracking payment confirmation date\n\n";
        
        // Verify the columns were added
        $checkSql = "DESCRIBE booking";
        $result = $conn->query($checkSql);
        
        if ($result) {
            echo "Current booking table structure:\n";
            echo "--------\n";
            while ($row = $result->fetch_assoc()) {
                if (in_array($row['Field'], ['PaymentStatus', 'PaymentMethod', 'PaymentDate'])) {
                    echo "  ✓ {$row['Field']}: {$row['Type']}\n";
                }
            }
        }
        
        echo "\n========================================\n";
        echo "Migration completed successfully! 🎉\n";
        echo "========================================\n";
        exit(0);
        
    } else {
        // Check if columns already exist
        if (strpos($conn->error, "Duplicate column name") !== false) {
            echo "⚠️  WARNING: Columns already exist in the database.\n";
            echo "This is normal if you've already run this migration.\n\n";
            
            // Verify columns exist
            $checkSql = "DESCRIBE booking";
            $result = $conn->query($checkSql);
            
            if ($result) {
                echo "Verification - Existing payment columns:\n";
                echo "--------\n";
                $found = 0;
                while ($row = $result->fetch_assoc()) {
                    if (in_array($row['Field'], ['PaymentStatus', 'PaymentMethod', 'PaymentDate'])) {
                        echo "  ✓ {$row['Field']}: {$row['Type']}\n";
                        $found++;
                    }
                }
                
                if ($found === 3) {
                    echo "\n✅ All payment columns are already present and ready!\n";
                    exit(0);
                }
            }
        } else {
            echo "❌ ERROR: " . $conn->error . "\n";
            exit(1);
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>
