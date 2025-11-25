<?php
/**
 * Authentication Middleware
 * Include this file in all protected API endpoints
 * Verifies admin session before allowing access
 * 
 * USAGE: require_once '../middleware/auth.php';
 *        requireAuth(); // Call at start of protected endpoints
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is authenticated as admin
 * Call this function at the start of protected endpoints
 * 
 * @return bool Returns true if authenticated, exits with error if not
 */
function requireAuth() {
    // Check if admin is logged in
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        sendResponse(false, 'Authentication required', ['authenticated' => false], 401);
        exit();
    }
    
    // Check if session has essential data
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['user_role'])) {
        sendResponse(false, 'Invalid session data', ['authenticated' => false], 401);
        exit();
    }
    
    // Check if user is admin
    if ($_SESSION['user_role'] !== 'admin') {
        sendResponse(false, 'Unauthorized access - Admin role required', ['authenticated' => false], 403);
        exit();
    }
    
    // Optional: Check session timeout (8 hours)
    $sessionTimeout = 8 * 60 * 60; // 8 hours in seconds
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $sessionTimeout) {
        // Session expired
        session_destroy();
        sendResponse(false, 'Session expired', ['authenticated' => false], 401);
        exit();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Get current admin ID from session
 * 
 * @return int|null Admin ID or null if not logged in
 */
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Get current admin name from session
 * 
 * @return string|null Admin name or null if not logged in
 */
function getCurrentAdminName() {
    return $_SESSION['admin_name'] ?? null;
}

/**
 * Get current admin email from session
 * 
 * @return string|null Admin email or null if not logged in
 */
function getCurrentAdminEmail() {
    return $_SESSION['admin_email'] ?? null;
}

/**
 * Get current admin info from session
 * 
 * @return array|null Array with admin info or null if not logged in
 */
function getCurrentAdmin() {
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'name' => $_SESSION['admin_name'] ?? null,
        'email' => $_SESSION['admin_email'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'login_time' => $_SESSION['login_time'] ?? null
    ];
}

/**
 * Verify admin session with database
 * Use this for critical operations that need extra security
 * 
 * @param PDO $conn Database connection
 * @return bool True if session is valid in database, false otherwise
 */
function verifySessionInDatabase($conn) {
    $sessionID = session_id();
    
    try {
        $query = "SELECT UserID, ExpiresAt 
                  FROM sessions 
                  WHERE SessionID = :sessionID 
                  AND UserType = 'admin' 
                  LIMIT 1";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':sessionID' => $sessionID]);
        
        if ($stmt->rowCount() === 0) {
            return false;
        }
        
        $session = $stmt->fetch();
        
        // Check if expired
        if (strtotime($session['ExpiresAt']) < time()) {
            // Clean up expired session
            $deleteQuery = "DELETE FROM sessions WHERE SessionID = :sessionID";
            $stmtDelete = $conn->prepare($deleteQuery);
            $stmtDelete->execute([':sessionID' => $sessionID]);
            return false;
        }
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Session Verification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if current user is authenticated (without exiting)
 * Useful for conditional logic
 * 
 * @return bool True if authenticated, false otherwise
 */
function isAuthenticated() {
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        return false;
    }
    
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    
    if ($_SESSION['user_role'] !== 'admin') {
        return false;
    }
    
    return true;
}

/**
 * Refresh session expiration time
 * Call this on user activity to extend session
 * 
 * @param PDO $conn Database connection
 * @return bool True if updated successfully
 */
function refreshSession($conn) {
    $sessionID = session_id();
    $newExpiresAt = date('Y-m-d H:i:s', time() + (60 * 60 * 8)); // 8 hours from now
    
    try {
        $query = "UPDATE sessions 
                  SET ExpiresAt = :expiresAt 
                  WHERE SessionID = :sessionID";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':expiresAt' => $newExpiresAt,
            ':sessionID' => $sessionID
        ]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Session Refresh Error: " . $e->getMessage());
        return false;
    }
}