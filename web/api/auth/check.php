<?php
session_start();

header('Content-Type: application/json');

// Check if admin session exists
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode([
        'success' => false,
        'message' => 'No active session',
        'authenticated' => false
    ]);
    http_response_code(401);
    exit();
}

// Return session info
echo json_encode([
    'success' => true,
    'message' => 'Session valid',
    'authenticated' => true,
    'admin_id' => $_SESSION['admin_id'],
    'name' => $_SESSION['admin_name'],
    'email' => $_SESSION['admin_email'],
    'role' => $_SESSION['user_role']
]);