<?php
session_start();
require_once('../includes/db_connect.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch all packages from database
$query = "SELECT * FROM package ORDER BY CreatedAt DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Packages - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .btn-add {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-add:hover {
            background: #45a049;
        }
        .packages-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .package-img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-popular {
            background: #fff3cd;
            color: #856404;
        }
        .actions {
            display: flex;
            gap: 8px;
        }
        .btn-edit, .btn-delete {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
        }
        .btn-edit {
            background: #007bff;
            color: white;
        }
        .btn-edit:hover {
            background: #0056b3;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .price {
            font-weight: 600;
            color: #28a745;
        }
    </style>
</head>
<body>
    <?php include('../includes/sidebar.php'); ?>
    
    <div class="container">
        <div class="header">
            <h1>Manage Packages</h1>
            <a href="add_package.php" class="btn-add">
                <i class="fas fa-plus"></i> Add New Package
            </a>
        </div>

        <div class="packages-table">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Package Name</th>
                        <th>Type</th>
                        <th>Price/Person</th>
                        <th>Min Pax</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($package = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($package['ImageURL'])): ?>
                                        <img src="../uploads/packages/<?php echo htmlspecialchars($package['ImageURL']); ?>" 
                                             alt="<?php echo htmlspecialchars($package['PackageName']); ?>" 
                                             class="package-img">
                                    <?php else: ?>
                                        <img src="../uploads/packages/default.jpg" alt="No Image" class="package-img">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($package['PackageName']); ?></strong>
                                    <?php if ($package['IsPopular']): ?>
                                        <span class="badge badge-popular">Popular</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo ucfirst(htmlspecialchars($package['PackageType'])); ?></td>
                                <td class="price">₱<?php echo number_format($package['PricePerPerson'], 2); ?></td>
                                <td><?php echo htmlspecialchars($package['MinimumPax']); ?> pax</td>
                                <td>
                                    <?php if ($package['IsActive']): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="edit_package.php?id=<?php echo $package['PackageID']; ?>" class="btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="delete_package.php?id=<?php echo $package['PackageID']; ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this package?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <i class="fas fa-box-open" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                <p>No packages found. Click "Add New Package" to create one.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Show success message if redirected from add/edit/delete
        <?php if (isset($_GET['success'])): ?>
            alert('<?php echo htmlspecialchars($_GET['success']); ?>');
        <?php endif; ?>
    </script>
</body>
</html>

<?php mysqli_close($conn); ?>