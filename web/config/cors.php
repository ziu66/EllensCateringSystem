<?php
/**
 * CORS Configuration
 * Properly handle Cross-Origin Resource Sharing
 */

// Get the origin of the request
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// List of allowed origins (update these to your actual domains)
$allowedOrigins = [
    'http://localhost',
    'http://localhost:3000',
    'http://localhost:8080',
    'http://127.0.0.1',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:8080'
    // Add your production domain here when deploying
    // 'https://yourdomain.com'
];

// Check if origin is allowed
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // For development, allow all origins
    // COMMENT OUT THIS LINE IN PRODUCTION!
    header("Access-Control-Allow-Origin: *");
}

// Required headers
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // 24 hours
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
    http_response_code(200);
    exit();
}