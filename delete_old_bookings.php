<?php
/**
 * Delete old format bookings (2-page format)
 * Run this once to clean up old bookings
 */

require_once __DIR__ . '/config/database.php';

$conn = getDB();

// First, let's see what agreements exist and their content
$query = "SELECT AgreementID, BookingID, LENGTH(ContractFile) as ContentLength, 
                 SUBSTRING(ContractFile, 1, 200) as FirstChars
          FROM agreement 
          ORDER BY CreatedAt ASC";

$result = $conn->query($query);

echo "<h2>Current Agreements:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>AgreementID</th><th>BookingID</th><th>Content Length</th><th>First 200 chars</th></tr>";

$oldBookingIds = [];

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['AgreementID'] . "</td>";
    echo "<td>" . $row['BookingID'] . "</td>";
    echo "<td>" . $row['ContentLength'] . "</td>";
    echo "<td>" . htmlspecialchars(substr($row['FirstChars'], 0, 100)) . "...</td>";
    echo "</tr>";
    
    // Old format had "TERMS & CONDITIONS:" in the contract
    if (strpos($row['FirstChars'], 'TERMS & CONDITIONS:') !== false || $row['ContentLength'] > 10000) {
        $oldBookingIds[] = $row['BookingID'];
    }
}

echo "</table>";

echo "<h2>Bookings to Delete:</h2>";
echo "<p>The following booking IDs will be deleted (old 2-page format):</p>";
echo "<ul>";
foreach ($oldBookingIds as $id) {
    echo "<li>Booking ID: " . $id . "</li>";
}
echo "</ul>";

if (empty($oldBookingIds)) {
    echo "<p><strong>No old format bookings found!</strong></p>";
    exit;
}

// Now delete them
echo "<h2>Deleting old bookings...</h2>";

foreach ($oldBookingIds as $bookingId) {
    echo "<p>Deleting Booking ID: $bookingId...</p>";
    
    // Delete in order of FK dependencies
    $conn->query("DELETE FROM agreement WHERE BookingID = $bookingId");
    echo "  - Deleted agreement records<br>";
    
    $conn->query("DELETE FROM booking_menu WHERE BookingID = $bookingId");
    echo "  - Deleted booking_menu records<br>";
    
    $conn->query("DELETE FROM booking_package WHERE BookingID = $bookingId");
    echo "  - Deleted booking_package records<br>";
    
    $conn->query("DELETE FROM notification WHERE BookingID = $bookingId");
    echo "  - Deleted notification records<br>";
    
    $conn->query("DELETE FROM payment WHERE BookingID = $bookingId");
    echo "  - Deleted payment records<br>";
    
    $conn->query("DELETE FROM quotation WHERE BookingID = $bookingId");
    echo "  - Deleted quotation records<br>";
    
    $conn->query("DELETE FROM booking WHERE BookingID = $bookingId");
    echo "  - Deleted booking record<br>";
}

echo "<h2>✓ Deletion complete!</h2>";
$conn->close();
?>
