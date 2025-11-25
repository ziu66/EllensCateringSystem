<?php
session_start();
$conn = new mysqli("localhost", "root", "", "catering_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect kung hindi naka-login
if (!isset($_SESSION['client_name'])) {
    header("Location: login_dashboard.php");
    exit();
}

$client_name = $_SESSION['client_name'];
$message = '';

// Fetch client info
$stmt = $conn->prepare("SELECT Name, Email, ContactNumber, Address, Password FROM client WHERE Name = ?");
$stmt->bind_param("s", $client_name);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $new_contact = trim($_POST['contact']);
    $new_address = trim($_POST['address']);
    $new_password = trim($_POST['password']);

    // Only update password if provided
    if (!empty($new_password)) {
        $update = $conn->prepare("UPDATE client SET Name=?, Email=?, ContactNumber=?, Address=?, Password=? WHERE Name=?");
        $update->bind_param("ssssss", $new_name, $new_email, $new_contact, $new_address, $new_password, $client_name);
    } else {
        $update = $conn->prepare("UPDATE client SET Name=?, Email=?, ContactNumber=?, Address=? WHERE Name=?");
        $update->bind_param("sssss", $new_name, $new_email, $new_contact, $new_address, $client_name);
    }

    if ($update->execute()) {
        $_SESSION['client_name'] = $new_name;
        $message = '<div class="alert alert-success">Profile updated successfully!</div>';
        $client = ['Name'=>$new_name, 'Email'=>$new_email, 'ContactNumber'=>$new_contact, 'Address'=>$new_address];
    } else {
        $message = '<div class="alert alert-danger">Update failed. Please try again.</div>';
    }
    $update->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Client Profile - Ellen's Catering</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
  :root {
    --primary-dark: #000000;
    --secondary-dark: #212529;
    --light-gray: #f8f9fa;
    --border-gray: #dee2e6;
    --text-dark: #212529;
    --accent-gray: #495057;
    --medium-gray: #6c757d;
  }

  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background: linear-gradient(135deg, var(--light-gray) 0%, #ffffff 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  .profile-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 40px;
    width: 100%;
    max-width: 600px;
    border: 1px solid var(--border-gray);
  }

  .profile-header {
    text-align: center;
    margin-bottom: 40px;
  }

  .profile-header h3 {
    color: var(--primary-dark);
    font-weight: 700;
    font-size: 2rem;
    margin-bottom: 10px;
  }

  .profile-header p {
    color: var(--medium-gray);
    font-size: 1rem;
  }

  .info-label {
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 8px;
    display: block;
    font-size: 0.9rem;
  }

  .info-box {
    background: var(--light-gray);
    border: 2px solid var(--border-gray);
    border-radius: 10px;
    padding: 12px 15px;
    margin-bottom: 20px;
    color: var(--text-dark);
    font-size: 1rem;
    transition: all 0.3s;
  }

  .info-box:hover {
    background: white;
    border-color: var(--primary-dark);
  }

  .form-control, .form-select, textarea {
    border-radius: 10px;
    padding: 12px 15px;
    border: 2px solid var(--border-gray);
    transition: all 0.3s;
    margin-bottom: 20px;
  }

  .form-control:focus, .form-select:focus, textarea:focus {
    border-color: var(--primary-dark);
    box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.1);
  }

  .btn-primary-custom {
    background: var(--primary-dark);
    border: none;
    color: white;
    font-weight: 600;
    padding: 12px 30px;
    border-radius: 50px;
    font-size: 1rem;
    transition: all 0.3s;
    width: 100%;
  }

  .btn-primary-custom:hover {
    background: var(--secondary-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
  }

  .btn-secondary-custom {
    background: var(--medium-gray);
    border: none;
    color: white;
    font-weight: 600;
    padding: 12px 30px;
    border-radius: 50px;
    font-size: 1rem;
    transition: all 0.3s;
    width: 100%;
  }

  .btn-secondary-custom:hover {
    background: var(--accent-gray);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
  }

  .btn-edit {
    background: linear-gradient(135deg, var(--primary-dark), var(--secondary-dark));
    border: none;
    color: white;
    font-weight: 600;
    padding: 12px 30px;
    border-radius: 50px;
    font-size: 1rem;
    transition: all 0.3s;
    width: 100%;
  }

  .btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
  }

  .alert {
    border-radius: 15px;
    border: none;
    padding: 15px 20px;
    margin-bottom: 30px;
    text-align: center;
  }

  .alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
  }

  .alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
  }

  .profile-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-dark), var(--secondary-dark));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: white;
    font-size: 2.5rem;
  }

  .form-group {
    margin-bottom: 20px;
  }

  .password-hint {
    font-size: 0.85rem;
    color: var(--medium-gray);
    margin-top: 5px;
  }

  @media (max-width: 576px) {
    .profile-card {
      padding: 30px 20px;
    }

    .profile-header h3 {
      font-size: 1.5rem;
    }

    .profile-icon {
      width: 60px;
      height: 60px;
      font-size: 2rem;
    }
  }
</style>
</head>

<body>
  <div class="profile-card">
    <div class="profile-header">
      <div class="profile-icon">
        <i class="bi bi-person-fill"></i>
      </div>
      <h3>Client Profile</h3>
      <p>Manage your account information</p>
    </div>

    <?= $message; ?>

    <!-- View Mode -->
    <div id="viewMode">
      <div class="form-group">
        <label class="info-label"><i class="bi bi-person me-2"></i>Full Name</label>
        <div class="info-box"><?= htmlspecialchars($client['Name']); ?></div>
      </div>

      <div class="form-group">
        <label class="info-label"><i class="bi bi-envelope me-2"></i>Email Address</label>
        <div class="info-box"><?= htmlspecialchars($client['Email']); ?></div>
      </div>

      <div class="form-group">
        <label class="info-label"><i class="bi bi-telephone me-2"></i>Contact Number</label>
        <div class="info-box"><?= htmlspecialchars($client['ContactNumber']); ?></div>
      </div>

      <div class="form-group">
        <label class="info-label"><i class="bi bi-geo-alt me-2"></i>Address</label>
        <div class="info-box"><?= htmlspecialchars($client['Address']); ?></div>
      </div>

      <div class="d-grid gap-3 mt-4">
        <button class="btn btn-edit" id="editBtn">
          <i class="bi bi-pencil-square me-2"></i>Edit Profile
        </button>
        <a href="index.php" class="btn btn-secondary-custom">
          <i class="bi bi-arrow-left me-2"></i>Back to Home
        </a>
      </div>
    </div>

    <!-- Edit Mode -->
    <form id="editForm" method="POST" style="display:none;">
      <div class="form-group">
        <label class="info-label"><i class="bi bi-person me-2"></i>Full Name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($client['Name']); ?>" required>
      </div>

      <div class="form-group">
        <label class="info-label"><i class="bi bi-envelope me-2"></i>Email Address</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($client['Email']); ?>" required>
      </div>

      <div class="form-group">
        <label class="info-label"><i class="bi bi-telephone me-2"></i>Contact Number</label>
        <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($client['ContactNumber']); ?>" required>
      </div>

      <div class="form-group">
        <label class="info-label"><i class="bi bi-geo-alt me-2"></i>Address</label>
        <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($client['Address']); ?></textarea>
      </div>

      <div class="form-group">
        <label class="info-label"><i class="bi bi-lock me-2"></i>New Password (optional)</label>
        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
        <small class="password-hint">Only fill this if you want to change your password</small>
      </div>

      <div class="d-grid gap-3 mt-4">
        <button type="submit" class="btn btn-primary-custom">
          <i class="bi bi-check-circle me-2"></i>Save Changes
        </button>
        <button type="button" id="cancelBtn" class="btn btn-secondary-custom">
          <i class="bi bi-x-circle me-2"></i>Cancel
        </button>
      </div>
    </form>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const editBtn = document.getElementById('editBtn');
  const viewMode = document.getElementById('viewMode');
  const editForm = document.getElementById('editForm');
  const cancelBtn = document.getElementById('cancelBtn');

  editBtn.addEventListener('click', () => {
    viewMode.style.display = 'none';
    editForm.style.display = 'block';
  });

  cancelBtn.addEventListener('click', () => {
    editForm.style.display = 'none';
    viewMode.style.display = 'block';
  });
</script>
</body>
</html>