<?php
session_start();
require_once('../includes/db_connect.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get package ID
$packageID = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($packageID === 0) {
    header("Location: manage_packages.php?error=Invalid package ID");
    exit();
}

// Check if package exists
$checkQuery = "SELECT ImageURL FROM package WHERE PackageID = $packageID";
$checkResult = mysqli_query($conn, $checkQuery);

if (mysqli_num_rows($checkResult) === 0) {
    header("Location: manage_packages.php?error=Package not found");
    exit();
}

$package = mysqli_fetch_assoc($checkResult);

// Delete associated image file if exists
if (!empty($package['ImageURL'])) {
    $imagePath = "../uploads/packages/" . $package['ImageURL'];
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}

// Delete package from database
$deleteQuery = "DELETE FROM package WHERE PackageID = $packageID";

if (mysqli_query($conn, $deleteQuery)) {
    header("Location: manage_packages.php?success=Package deleted successfully!");
} else {
    header("Location: manage_packages.php?error=Failed to delete package: " . mysqli_error($conn));
}

mysqli_close($conn);
exit();
?>