<?php
/**
 * Signature Helper Functions
 * Place this in: C:\xampp1\htdocs\EllensCateringSystem\includes\signature_helper.php
 */

/**
 * Convert signature image file to base64 data URI
 */
function convertSignatureToBase64($imagePath) {
    if (!file_exists($imagePath)) {
        return false;
    }
    
    $imageData = file_get_contents($imagePath);
    $mimeType = mime_content_type($imagePath);
    
    return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
}

/**
 * Save Elma's signature to database (run this once to store the default signature)
 */
function saveDefaultElmaSignature($conn) {
    // Your signature image path
    $signaturePath = __DIR__ . '/../assets/images/elma_signature.png'; // Adjust path as needed
    
    if (!file_exists($signaturePath)) {
        error_log("Signature file not found: $signaturePath");
        return false;
    }
    
    $signatureBase64 = convertSignatureToBase64($signaturePath);
    
    // Store in a config table or use as constant
    $stmt = $conn->prepare("
        INSERT INTO system_config (config_key, config_value) 
        VALUES ('elma_signature', ?) 
        ON DUPLICATE KEY UPDATE config_value = ?
    ");
    
    $stmt->bind_param("ss", $signatureBase64, $signatureBase64);
    return $stmt->execute();
}

/**
 * Get Elma's signature from database
 */
function getElmaSignature($conn) {
    // First check if system_config table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'system_config'");
    
    if ($tableCheck->num_rows === 0) {
        error_log("system_config table does not exist - using default signature");
        // Fallback to default SVG signature
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjYwIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik0gMzAgNDAgUSAxNSAyNSAzNSAxNSBRIDU1IDUgNzUgMjUgUSA5NSA0NSA4MCA1NSBRIDY1IDY1IDQ1IDU1IFEgMjUgNDUgMzAgNDAgWiBNIDk1IDMwIEwgMTEwIDE1IEwgMTEwIDYwIE0gMTI1IDI1IEwgMTQ1IDQ1IEwgMTI1IDY1IiBzdHJva2U9IiMwMDAiIHN0cm9rZS13aWR0aD0iMiIgZmlsbD0ibm9uZSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+PC9zdmc+';
    }
    
    $stmt = $conn->prepare("SELECT config_value FROM system_config WHERE config_key = 'elma_signature'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['config_value'];
    }
    
    // Fallback to default SVG signature
    return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjYwIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik0gMzAgNDAgUSAxNSAyNSAzNSAxNSBRIDU1IDUgNzUgMjUgUSA5NSA0NSA4MCA1NSBRIDY1IDY1IDQ1IDU1IFEgMjUgNDUgMzAgNDAgWiBNIDk1IDMwIEwgMTEwIDE1IEwgMTEwIDYwIE0gMTI1IDI1IEwgMTQ1IDQ1IEwgMTI1IDY1IiBzdHJva2U9IiMwMDAiIHN0cm9rZS13aWR0aD0iMiIgZmlsbD0ibm9uZSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+PC9zdmc+';
}

/**
 * Validate signature data URI
 */
function isValidSignature($signatureData) {
    if (empty($signatureData)) {
        return false;
    }
    
    // Check if it's a valid data URI
    if (!preg_match('/^data:image\/(png|jpg|jpeg|svg\+xml);base64,/', $signatureData)) {
        return false;
    }
    
    return true;
}

/**
 * Extract base64 data from data URI
 */
function extractBase64FromDataURI($dataURI) {
    $parts = explode(',', $dataURI, 2);
    return isset($parts[1]) ? $parts[1] : '';
}

/**
 * Get MIME type from data URI
 */
function getMimeTypeFromDataURI($dataURI) {
    if (preg_match('/^data:([^;]+);/', $dataURI, $matches)) {
        return $matches[1];
    }
    return 'image/png'; // default
}
?>