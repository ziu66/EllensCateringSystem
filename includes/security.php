<?php
// includes/security.php
// Security functions for the application

require_once __DIR__ . '/../config/database.php';

// Start secure session
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
        ini_set('session.cookie_samesite', 'Strict');
        session_start();
    }
}

// Regenerate session ID
function regenerateSession() {
    session_regenerate_id(true);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_role']) && 
           (isset($_SESSION['client_id']) || isset($_SESSION['admin_id']));
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && 
           $_SESSION['user_role'] === 'admin' && 
           isset($_SESSION['admin_id']);
}

// Check if user is client
function isClient() {
    return isset($_SESSION['user_role']) && 
           $_SESSION['user_role'] === 'client' && 
           isset($_SESSION['client_id']);
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login_dashboard.php");
        exit();
    }
}

// Require admin
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: index.php");
        exit();
    }
}

// Sanitize input
function sanitizeInput($data) {
    if ($data === null) return null;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate password strength
function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

// Log activity
function logActivity($userID, $userType, $action, $description = '') {
    try {
        $conn = getDB();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $stmt = $conn->prepare("INSERT INTO activity_log (UserID, UserType, Action, Description, IPAddress) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $userID, $userType, $action, $description, $ipAddress);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

// Prevent SQL injection
function escapeString($string) {
    $conn = getDB();
    return $conn->real_escape_string($string);
}

// Rate limiting
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    if (!isset($_SESSION['rate_limit'][$identifier])) {
        $_SESSION['rate_limit'][$identifier] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    $data = $_SESSION['rate_limit'][$identifier];
    $timePassed = time() - $data['first_attempt'];
    
    if ($timePassed > $timeWindow) {
        $_SESSION['rate_limit'][$identifier] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    $_SESSION['rate_limit'][$identifier]['attempts']++;
    return true;
}

// Get client ID safely
function getClientID() {
    return isset($_SESSION['client_id']) ? (int)$_SESSION['client_id'] : null;
}

// Get admin ID safely
function getAdminID() {
    return isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
}

// Get user name safely
function getUserName() {
    if (isClient()) {
        return $_SESSION['client_name'] ?? 'Guest';
    } elseif (isAdmin()) {
        return $_SESSION['admin_name'] ?? 'Admin';
    }
    return 'Guest';
}

// Format date for display
function formatDate($date, $format = 'F d, Y') {
    return date($format, strtotime($date));
}

// Format currency
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

// Redirect with message
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

// Get and clear flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Display flash message HTML
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $icon = $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle';
        echo '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show" role="alert">';
        echo '<i class="bi bi-' . $icon . ' me-2"></i>' . htmlspecialchars($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}
?>