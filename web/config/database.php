<?php
/**
 * Database Configuration and Connection
 * Handles database connection with error handling and security
 * 
 * IMPROVED VERSION WITH BETTER ERROR HANDLING
 */

class Database {
    // Database credentials
    private $host = "localhost";  // Use localhost for XAMPP
    private $db_name = "catering_db";
    private $username = "root";
    private $password = "";
    private $conn;
    
    /**
     * Get database connection
     * @return PDO|null
     */
    public function connect() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            // Don't expose database errors to users
            return null;
        }
        
        return $this->conn;
    }
    
    /**
     * Close database connection
     */
    public function disconnect() {
        $this->conn = null;
    }
}

/**
 * Helper function to get database connection
 * @return PDO|null
 */
function getDbConnection() {
    $database = new Database();
    return $database->connect();
}

/**
 * Helper function to send JSON response
 * @param bool $status Success status
 * @param string $message Response message
 * @param mixed $data Optional data to include
 * @param int $httpCode HTTP status code
 */
function sendResponse($status, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    
    // Ensure we haven't sent headers yet
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    
    $response = [
        'success' => $status,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Helper function to validate required fields
 * @param array $data Input data
 * @param array $requiredFields List of required field names
 * @return array List of missing fields
 */
function validateRequiredFields($data, $requiredFields) {
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && empty(trim($data[$field])))) {
            $missingFields[] = $field;
        }
    }
    
    return $missingFields;
}

/**
 * Helper function to sanitize input
 * @param string $data Input string
 * @return string Sanitized string
 */
function sanitizeInput($data) {
    if ($data === null) {
        return null;
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Log activity to database
 * @param PDO $conn Database connection
 * @param int|null $userID User ID (can be null for failed login attempts)
 * @param string $userType User type (admin/client)
 * @param string $action Action performed
 * @param string $description Action description
 * @param string $ipAddress User's IP address
 * @return bool Success status
 */
function logActivity($conn, $userID, $userType, $action, $description, $ipAddress) {
    try {
        $query = "INSERT INTO activity_log (UserID, UserType, Action, Description, IPAddress) 
                  VALUES (:userID, :userType, :action, :description, :ipAddress)";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':userID' => $userID,
            ':userType' => $userType,
            ':action' => $action,
            ':description' => $description,
            ':ipAddress' => $ipAddress
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate email format
 * @param string $email Email to validate
 * @return bool Valid or not
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate secure random token
 * @param int $length Token length
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}