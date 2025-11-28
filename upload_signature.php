<?php
/**
 * One-time script to upload Elma's signature to database
 * Place in: C:\xampp1\htdocs\EllensCateringSystem\admin\upload_signature.php
 * Run this ONCE, then delete or secure this file
 */

require_once '../config/database.php';
require_once '../includes/signature_helper.php';
require_once '../includes/security.php';

startSecureSession();

// Security check - only admin can access
if (!isAdmin()) {
    die('Unauthorized access');
}

$conn = getDB();
$message = '';
$messageType = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['signature'])) {
    $file = $_FILES['signature'];
    
    // Validate file
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        $message = 'Invalid file type. Please upload PNG, JPG, or SVG.';
        $messageType = 'danger';
    } elseif ($file['size'] > $maxSize) {
        $message = 'File too large. Maximum size is 5MB.';
        $messageType = 'danger';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Upload error occurred.';
        $messageType = 'danger';
    } else {
        // Convert to base64
        $imageData = file_get_contents($file['tmp_name']);
        $mimeType = $file['type'];
        $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        
        // Save to database
        $stmt = $conn->prepare("
            INSERT INTO system_config (config_key, config_value, description) 
            VALUES ('elma_signature', ?, 'Elma Barcelon signature for agreements')
            ON DUPLICATE KEY UPDATE config_value = ?, updated_at = NOW()
        ");
        
        $stmt->bind_param("ss", $base64, $base64);
        
        if ($stmt->execute()) {
            $message = 'Signature uploaded successfully!';
            $messageType = 'success';
            logActivity(getAdminID(), 'admin', 'signature_uploaded', 'Uploaded Elma signature to system');
        } else {
            $message = 'Database error: ' . $stmt->error;
            $messageType = 'danger';
        }
        
        $stmt->close();
    }
}

// Get current signature
$currentSignature = getElmaSignature($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Elma's Signature</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 50px; }
        .signature-preview {
            max-width: 400px;
            max-height: 200px;
            border: 2px solid #dee2e6;
            padding: 20px;
            margin: 20px 0;
            background: white;
        }
        .signature-preview img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Upload Elma's Signature</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($currentSignature): ?>
                            <div class="mb-4">
                                <h5>Current Signature:</h5>
                                <div class="signature-preview">
                                    <img src="<?= htmlspecialchars($currentSignature) ?>" alt="Current Signature">
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Upload New Signature Image</label>
                                <input type="file" name="signature" class="form-control" 
                                       accept="image/png,image/jpeg,image/jpg,image/svg+xml" required>
                                <small class="text-muted">Accepted formats: PNG, JPG, SVG (Max 5MB)</small>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Instructions:</h6>
                                <ul>
                                    <li>Scan your signature or create a digital signature</li>
                                    <li>Save as PNG, JPG, or SVG with transparent background (recommended)</li>
                                    <li>Recommended size: 200x80 pixels</li>
                                    <li>This will be used on all agreement PDFs</li>
                                </ul>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Upload Signature
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="alert alert-warning">
                            <strong>Security Notice:</strong> After uploading the signature, 
                            delete or secure this file to prevent unauthorized changes.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>