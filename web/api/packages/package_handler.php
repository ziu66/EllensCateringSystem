<?php
session_start();
require_once('../includes/db_connect.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            addPackage($conn);
            break;
        case 'edit':
            editPackage($conn);
            break;
        default:
            header("Location: manage_packages.php");
            exit();
    }
}

// Function to add a new package
function addPackage($conn) {
    // Get form data
    $packageName = mysqli_real_escape_string($conn, $_POST['packageName']);
    $packageType = mysqli_real_escape_string($conn, $_POST['packageType']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $pricePerPerson = floatval($_POST['pricePerPerson']);
    $minimumPax = intval($_POST['minimumPax']);
    $features = mysqli_real_escape_string($conn, $_POST['features']);
    $isPopular = isset($_POST['isPopular']) ? 1 : 0;
    $isActive = isset($_POST['isActive']) ? 1 : 0;
    
    // Handle image upload
    $imageURL = '';
    if (isset($_FILES['packageImage']) && $_FILES['packageImage']['error'] === 0) {
        $imageURL = uploadImage($_FILES['packageImage']);
    }
    
    // Insert into database
    $query = "INSERT INTO package (
        PackageName, 
        PackageType, 
        Description, 
        PricePerPerson, 
        MinimumPax, 
        ImageURL, 
        Features, 
        IsPopular, 
        IsActive, 
        CreatedAt, 
        UpdatedAt
    ) VALUES (
        '$packageName', 
        '$packageType', 
        '$description', 
        $pricePerPerson, 
        $minimumPax, 
        '$imageURL', 
        '$features', 
        $isPopular, 
        $isActive, 
        NOW(), 
        NOW()
    )";
    
    if (mysqli_query($conn, $query)) {
        header("Location: manage_packages.php?success=Package added successfully!");
    } else {
        header("Location: add_package.php?error=Failed to add package: " . mysqli_error($conn));
    }
    exit();
}

// Function to edit an existing package
function editPackage($conn) {
    $packageID = intval($_POST['packageID']);
    $packageName = mysqli_real_escape_string($conn, $_POST['packageName']);
    $packageType = mysqli_real_escape_string($conn, $_POST['packageType']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $pricePerPerson = floatval($_POST['pricePerPerson']);
    $minimumPax = intval($_POST['minimumPax']);
    $features = mysqli_real_escape_string($conn, $_POST['features']);
    $isPopular = isset($_POST['isPopular']) ? 1 : 0;
    $isActive = isset($_POST['isActive']) ? 1 : 0;
    
    // Get current image
    $currentImageQuery = "SELECT ImageURL FROM package WHERE PackageID = $packageID";
    $currentImageResult = mysqli_query($conn, $currentImageQuery);
    $currentImage = mysqli_fetch_assoc($currentImageResult)['ImageURL'];
    
    // Handle image upload
    $imageURL = $currentImage;
    if (isset($_FILES['packageImage']) && $_FILES['packageImage']['error'] === 0) {
        // Delete old image if exists
        if (!empty($currentImage) && file_exists("../uploads/packages/" . $currentImage)) {
            unlink("../uploads/packages/" . $currentImage);
        }
        $imageURL = uploadImage($_FILES['packageImage']);
    }
    
    // Update database
    $query = "UPDATE package SET 
        PackageName = '$packageName',
        PackageType = '$packageType',
        Description = '$description',
        PricePerPerson = $pricePerPerson,
        MinimumPax = $minimumPax,
        ImageURL = '$imageURL',
        Features = '$features',
        IsPopular = $isPopular,
        IsActive = $isActive,
        UpdatedAt = NOW()
        WHERE PackageID = $packageID";
    
    if (mysqli_query($conn, $query)) {
        header("Location: manage_packages.php?success=Package updated successfully!");
    } else {
        header("Location: edit_package.php?id=$packageID&error=Failed to update package: " . mysqli_error($conn));
    }
    exit();
}

// Function to handle image upload
function uploadImage($file) {
    $targetDir = "../uploads/packages/";
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Get file info
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Validate file type
    if (!in_array($fileExtension, $allowedExtensions)) {
        header("Location: add_package.php?error=Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.");
        exit();
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5242880) {
        header("Location: add_package.php?error=File size too large. Maximum 5MB allowed.");
        exit();
    }
    
    // Generate unique filename
    $uniqueFileName = uniqid('package_', true) . '.' . $fileExtension;
    $targetFile = $targetDir . $uniqueFileName;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return $uniqueFileName;
    } else {
        header("Location: add_package.php?error=Failed to upload image.");
        exit();
    }
}

mysqli_close($conn);
?>