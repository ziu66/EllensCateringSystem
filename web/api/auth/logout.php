<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode([
        'success' => false,
        'message' => 'No active session found'
    ]);
    http_response_code(401);
    exit();
}

// Include database connection
$conn = new mysqli("localhost", "root", "", "tuklas_nasugbu");

if (!$conn->connect_error) {
    // Delete session from database
    $sessionID = session_id();
    $deleteQuery = $conn->prepare("DELETE FROM sessions WHERE SessionID = ?");
    if ($deleteQuery) {
        $deleteQuery->bind_param("s", $sessionID);
        $deleteQuery->execute();
    }
    $conn->close();
}

// Destroy session
$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'Logout successful'
]);