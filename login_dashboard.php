<?php
session_start();

// --- DATABASE CONNECTION ---
$conn = new mysqli("localhost", "root", "", "catering_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // -------------------------------------
    // ADMIN LOGIN CHECK
    // -------------------------------------
    $stmt_admin = $conn->prepare("SELECT AdminID AS id, Name, Email, Password FROM admin WHERE Email = ? LIMIT 1");
    $stmt_admin->bind_param("s", $email);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();

    if ($result_admin->num_rows === 1) {

        $row = $result_admin->fetch_assoc();
        $db_pass = $row['Password'];

        if (password_verify($password, $db_pass) || $password === $db_pass) {

            // Regenerate session ID to avoid duplicate session keys
            session_regenerate_id(true);
            $sessionID = session_id();

            // SESSION VALUES
            $_SESSION['user_role'] = 'admin';
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_name'] = $row['Name'];
            $_SESSION['admin_email'] = $row['Email'];
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['login_time'] = time();

            // Save session in database
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60 * 8)); // 8 hrs

            $insertSession = $conn->prepare(
                "INSERT INTO sessions (SessionID, UserID, UserType, IPAddress, UserAgent, ExpiresAt)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            if ($insertSession) {
                $userType = 'admin';
                $insertSession->bind_param("sissss",
                    $sessionID,
                    $row['id'],
                    $userType,
                    $ipAddress,
                    $userAgent,
                    $expiresAt
                );
                $insertSession->execute();
            }

            header("Location: web/admin/dashboard.php");
            exit();
        }
    }

    // -------------------------------------
    // CLIENT LOGIN CHECK
    // -------------------------------------
    $stmt_client = $conn->prepare("SELECT ClientID AS id, Name, Email, Password FROM client WHERE Email = ? LIMIT 1");
    $stmt_client->bind_param("s", $email);
    $stmt_client->execute();
    $result_client = $stmt_client->get_result();

    if ($result_client->num_rows === 1) {

        $row = $result_client->fetch_assoc();
        $db_pass = $row['Password'];

        if (password_verify($password, $db_pass) || $password === $db_pass) {

            // Regenerate session ID to avoid duplicate sessions
            session_regenerate_id(true);
            $sessionID = session_id();

            // SESSION VALUES
            $_SESSION['user_role'] = 'client';
            $_SESSION['client_id'] = $row['id'];
            $_SESSION['client_name'] = $row['Name'];
            $_SESSION['client_email'] = $row['Email'];
            $_SESSION['client_logged_in'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['show_welcome'] = true;

            // Save session in DB
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60 * 8)); // 8 hrs

            $insertSession = $conn->prepare(
                "INSERT INTO sessions (SessionID, UserID, UserType, IPAddress, UserAgent, ExpiresAt)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            if ($insertSession) {
                $userType = 'client';
                $insertSession->bind_param("sissss",
                    $sessionID,
                    $row['id'],
                    $userType,
                    $ipAddress,
                    $userAgent,
                    $expiresAt
                );
                $insertSession->execute();
            }

            header("Location: index.php");
            exit();
        }
    }

    // INVALID LOGIN
    $error = "Invalid email or password.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ellen's Catering Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      margin: 0;
      background: #f8f9fa;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    
    .portal-wrapper {
      display: flex;
      flex-wrap: wrap;
      max-width: 1000px;
      width: 100%;
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 12px 35px rgba(0,0,0,0.15);
    }

    .left-panel {
      flex: 1 1 400px;
      background: linear-gradient(135deg, #000000, #212529);
      color: #ffffff;
      padding: 60px 40px;
      text-align: center;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .left-panel h1 { 
      font-size: clamp(2rem, 3vw, 2.8rem); 
      font-weight: 700; 
      margin-bottom: 20px;
      letter-spacing: 1px;
    }

    .left-panel p { 
      font-size: 1.1rem; 
      color: #dee2e6;
      line-height: 1.6;
    }

    .right-panel {
      flex: 1 1 400px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
      background: #ffffff;
    }

    .login-card { 
      width: 100%; 
      max-width: 380px; 
    }

    .login-card h3 { 
      font-weight: 700; 
      margin-bottom: 25px; 
      color: #000000; 
      text-align: center; 
    }

    .input-group-text { 
      background: #000000; 
      border: 1px solid #000000;
      color: #ffffff; 
      font-weight: 500;
    }

    .form-control { 
      border-radius: 0 10px 10px 0;
      padding: 12px;
      border: 1px solid #dee2e6;
    }

    .form-control:focus {
      border-color: #000000;
      box-shadow: 0 0 0 0.2rem rgba(0,0,0,0.15);
    }

    .form-label {
      color: #212529;
      font-weight: 500;
      margin-bottom: 8px;
    }

    .btn-primary {
      border-radius: 10px;
      padding: 12px;
      font-weight: 600;
      background: #000000;
      color: #ffffff;
      border: 2px solid #000000;
      transition: all 0.3s ease;
    }

    .btn-primary:hover { 
      background-color: #212529;
      border-color: #212529;
      transform: translateY(-2px); 
      box-shadow: 0px 5px 15px rgba(0,0,0,0.3); 
    }

    .login-links a {
      color: #212529;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    .login-links a:hover {
      color: #000000;
      text-decoration: underline;
    }

    .alert-danger {
      background-color: #f8d7da;
      border-color: #f5c2c7;
      color: #842029;
      border-radius: 10px;
    }

    @media (max-width: 768px) {
      .left-panel {
        padding: 40px 30px;
      }
      
      .right-panel {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>

<div class="portal-wrapper">
  <div class="left-panel">
    <h1>Ellen's Catering</h1>
    <p>Bring your next celebration to life with professional catering services.</p>
  </div>

  <div class="right-panel">
    <div class="login-card">
      <h3><i class="bi bi-person-circle me-2"></i>Login</h3>

      <?php if($error): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
          </div>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 mt-2">Login</button>

        <div class="d-flex justify-content-between mt-3 login-links">
          <a href="#">Forgot password?</a>
          <a href="signup_dashboard.php">Sign Up</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Auto-merge cart after successful login
window.addEventListener('DOMContentLoaded', function() {
    // Check if redirecting to cart after login
    const urlParams = new URLSearchParams(window.location.search);
    const redirectTo = urlParams.get('redirect');
    
    // Store the redirect parameter for after login
    if (redirectTo) {
        sessionStorage.setItem('login_redirect', redirectTo);
    }
});

// After successful login form submission, add this:
// (Place this in your login form success handler)
function handleLoginSuccess() {
    const guestCart = localStorage.getItem('guest_cart');
    const redirectTo = sessionStorage.getItem('login_redirect') || 'profile_management.php';
    
    if (guestCart && redirectTo === 'view_cart.php') {
        // Send cart to merge endpoint
        const formData = new FormData();
        formData.append('merge_cart', '1');
        formData.append('cart_data', guestCart);
        
        fetch('view_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                localStorage.removeItem('guest_cart');
                sessionStorage.removeItem('login_redirect');
            }
            // Redirect after merge
            window.location.href = redirectTo;
        })
        .catch(error => {
            console.error('Merge error:', error);
            window.location.href = redirectTo;
        });
    } else {
        // Normal redirect
        window.location.href = redirectTo;
    }
}
</script>
</body>
</html>