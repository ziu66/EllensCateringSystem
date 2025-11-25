<?php
session_start();
require_once('../includes/db_connect.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Package - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container {
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
        }
        .form-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        .image-upload {
            border: 2px dashed #ddd;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .image-upload:hover {
            border-color: #4CAF50;
            background: #f8f9fa;
        }
        .image-preview {
            max-width: 300px;
            margin: 10px auto;
            display: none;
        }
        .image-preview img {
            width: 100%;
            border-radius: 4px;
        }
        .features-input {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }
        .features-input input {
            flex: 1;
        }
        .btn-add-feature {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .feature-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-submit {
            background: #4CAF50;
            color: white;
            padding: 12px 32px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        .btn-submit:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <?php include('../includes/sidebar.php'); ?>
    
    <div class="container">
        <div class="form-card">
            <div class="form-header">
                <h1>Add New Package</h1>
                <a href="manage_packages.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>

            <form id="addPackageForm" action="package_handler.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label for="packageName">Package Name *</label>
                    <input type="text" id="packageName" name="packageName" required>
                </div>

                <div class="form-group">
                    <label for="packageType">Package Type *</label>
                    <select id="packageType" name="packageType" required>
                        <option value="">Select Type</option>
                        <option value="celebration">Celebration Package</option>
                        <option value="bento">Bento Package</option>
                        <option value="packed">Packed Meals</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="pricePerPerson">Price Per Person (₱) *</label>
                    <input type="number" id="pricePerPerson" name="pricePerPerson" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="minimumPax">Minimum Pax *</label>
                    <input type="number" id="minimumPax" name="minimumPax" min="1" required>
                </div>

                <div class="form-group">
                    <label>Features/Inclusions</label>
                    <div class="features-input">
                        <input type="text" id="featureInput" placeholder="Enter a feature">
                        <button type="button" class="btn-add-feature" onclick="addFeature()">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                    <div id="featuresList"></div>
                    <input type="hidden" id="featuresData" name="features">
                </div>

                <div class="form-group">
                    <label for="packageImage">Package Image</label>
                    <div class="image-upload" onclick="document.getElementById('packageImage').click()">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #ccc;"></i>
                        <p>Click to upload image</p>
                        <small>Supported: JPG, PNG, GIF (Max 5MB)</small>
                    </div>
                    <input type="file" id="packageImage" name="packageImage" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    <div class="image-preview" id="imagePreview">
                        <img id="preview" src="" alt="Preview">
                    </div>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="isPopular" name="isPopular" value="1">
                        <label for="isPopular" style="margin: 0;">Mark as Popular</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="isActive" name="isActive" value="1" checked>
                        <label for="isActive" style="margin: 0;">Active</label>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Create Package
                </button>
            </form>
        </div>
    </div>

    <script>
        let features = [];

        function addFeature() {
            const input = document.getElementById('featureInput');
            const feature = input.value.trim();
            
            if (feature) {
                features.push(feature);
                updateFeaturesList();
                input.value = '';
            }
        }

        function removeFeature(index) {
            features.splice(index, 1);
            updateFeaturesList();
        }

        function updateFeaturesList() {
            const list = document.getElementById('featuresList');
            const dataInput = document.getElementById('featuresData');
            
            list.innerHTML = features.map((feature, index) => `
                <div class="feature-item">
                    <span>${feature}</span>
                    <button type="button" class="btn-remove" onclick="removeFeature(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');
            
            dataInput.value = JSON.stringify(features);
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Allow Enter key to add features
        document.getElementById('featureInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addFeature();
            }
        });
    </script>
</body>
</html>