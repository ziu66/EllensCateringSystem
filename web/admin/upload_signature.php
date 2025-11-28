<?php
/**
 * One-time script to upload Elma's signature to database
 * Place in: C:\xampp1\htdocs\EllensCateringSystem\web\admin\upload_signature.php
 * Access via: http://localhost/ellenscateringsystem/web/admin/upload_signature.php
 */

// Correct paths from web/admin/ to root
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/signature_helper.php';
require_once __DIR__ . '/../../includes/security.php';

startSecureSession();

// Security check - only admin can access
if (!isAdmin()) {
    header('Location: ../../login_dashboard.php');
    exit();
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
            
            $adminID = getAdminID();
            logActivity($adminID, 'admin', 'signature_uploaded', 'Uploaded Elma signature to system');
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
    <title>Upload Elma's Signature - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            padding: 50px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            min-height: 100vh;
        }
        .signature-preview {
            max-width: 400px;
            max-height: 200px;
            border: 2px solid #dee2e6;
            padding: 20px;
            margin: 20px 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .signature-preview img {
            max-width: 100%;
            height: auto;
        }
        .card {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 15px;
        }
        .card-header {
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .btn-primary {
            background: #000;
            border: none;
        }
        .btn-primary:hover {
            background: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-pen me-2"></i>Upload Elma's Signature
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                                <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($currentSignature && $currentSignature !== 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjYwIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik0gMzAgNDAgUSAxNSAyNSAzNSAxNSBRIDU1IDUgNzUgMjUgUSA5NSA0NSA4MCA1NSBRIDY1IDY1IDQ1IDU1IFEgMjUgNDUgMzAgNDAgWiBNIDk1IDMwIEwgMTEwIDE1IEwgMTEwIDYwIE0gMTI1IDI1IEwgMTQ1IDQ1IEwgMTI1IDY1IiBzdHJva2U9IiMwMDAiIHN0cm9rZS13aWR0aD0iMiIgZmlsbD0ibm9uZSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+PC9zdmc+'): ?>
                            <div class="mb-4">
                                <h5><i class="bi bi-check-circle-fill text-success me-2"></i>Current Signature:</h5>
                                <div class="signature-preview">
                                    <img src="<?= htmlspecialchars($currentSignature) ?>" alt="Current Signature">
                                </div>
                                <small class="text-muted">This signature is currently being used on all agreement PDFs</small>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>No signature uploaded yet!</strong> Using default placeholder signature.
                            </div>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        
                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-upload me-2"></i>Upload New Signature Image
                                </label>
                                <input type="file" name="signature" class="form-control" 
                                       accept="image/png,image/jpeg,image/jpg,image/svg+xml" 
                                       required id="signatureFile" onchange="previewSignature(this)">
                                <small class="text-muted">Accepted formats: PNG, JPG, SVG (Max 5MB)</small>
                            </div>
                            
                            <div id="previewContainer" style="display: none;" class="mb-4">
                                <h6>Preview:</h6>
                                <div class="signature-preview">
                                    <img id="signaturePreview" alt="Preview">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6><i class="bi bi-info-circle me-2"></i>Instructions:</h6>
                                <ul class="list-unstyled ms-3">
                                    <li><i class="bi bi-check2 text-success me-2"></i>Scan your signature or create a digital signature</li>
                                    <li><i class="bi bi-check2 text-success me-2"></i>Save as PNG, JPG, or SVG with transparent background (recommended)</li>
                                    <li><i class="bi bi-check2 text-success me-2"></i>Recommended size: 200x80 pixels</li>
                                    <li><i class="bi bi-check2 text-success me-2"></i>This will be used on all agreement PDFs</li>
                                </ul>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload me-2"></i>Upload Signature
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-shield-exclamation me-2"></i>
                            <strong>Security Notice:</strong> After uploading the signature, 
                            delete or secure this file to prevent unauthorized changes.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewSignature(input) {
            const preview = document.getElementById('signaturePreview');
            const container = document.getElementById('previewContainer');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    container.style.display = 'block';
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>