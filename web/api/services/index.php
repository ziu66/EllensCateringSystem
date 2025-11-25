<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Point to the MAIN config folder (not web/config)
require_once(__DIR__ . '/../../../config/database.php');

// Get database connection using the correct method
$conn = getDB();

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($conn);
        break;
    case 'POST':
        handlePost($conn);
        break;
    case 'PUT':
        handlePut($conn);
        break;
    case 'DELETE':
        handleDelete($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

// GET - Fetch services
function handleGet($conn) {
    $serviceID = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($serviceID > 0) {
        // Get specific service
        $query = "SELECT * FROM services WHERE ServiceID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $serviceID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'services' => $services,
                'total' => count($services)
            ]
        ]);
    } else {
        // Get all services
        $query = "SELECT * FROM services ORDER BY DisplayOrder ASC, CreatedAt DESC";
        $result = $conn->query($query);
        
        if ($result) {
            $services = [];
            while ($row = $result->fetch_assoc()) {
                $services[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'services' => $services,
                    'total' => count($services)
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch services: ' . $conn->error
            ]);
        }
    }
}

// POST - Create new service
function handlePost($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $serviceName = $data['service_name'] ?? '';
    $serviceType = $data['service_type'] ?? 'other';
    $pricePerPerson = floatval($data['price_per_person'] ?? 0);
    $minimumGuests = intval($data['minimum_guests'] ?? 30);
    $description = $data['description'] ?? '';
    $iconClass = $data['icon_class'] ?? 'bi-star-fill';
    $inclusions = $data['inclusions'] ?? '[]';
    $isPopular = intval($data['is_popular'] ?? 0);
    $isActive = intval($data['is_active'] ?? 1);
    $displayOrder = intval($data['display_order'] ?? 0);
    
    $query = "INSERT INTO services (
        ServiceName, ServiceType, PricePerPerson, MinimumGuests, 
        Description, IconClass, Inclusions, IsPopular, IsActive, DisplayOrder
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssdisssiii",
        $serviceName, $serviceType, $pricePerPerson, $minimumGuests,
        $description, $iconClass, $inclusions, $isPopular, $isActive, $displayOrder
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Service created successfully',
            'data' => ['service_id' => $stmt->insert_id]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create service: ' . $stmt->error
        ]);
    }
}

// PUT - Update service
function handlePut($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $serviceID = intval($data['service_id'] ?? 0);
    $serviceName = $data['service_name'] ?? '';
    $serviceType = $data['service_type'] ?? 'other';
    $pricePerPerson = floatval($data['price_per_person'] ?? 0);
    $minimumGuests = intval($data['minimum_guests'] ?? 30);
    $description = $data['description'] ?? '';
    $iconClass = $data['icon_class'] ?? 'bi-star-fill';
    $inclusions = $data['inclusions'] ?? '[]';
    $isPopular = intval($data['is_popular'] ?? 0);
    $isActive = intval($data['is_active'] ?? 1);
    $displayOrder = intval($data['display_order'] ?? 0);
    
    if ($serviceID == 0) {
        echo json_encode(['success' => false, 'message' => 'Service ID is required']);
        return;
    }
    
    $query = "UPDATE services SET 
        ServiceName = ?, ServiceType = ?, PricePerPerson = ?, MinimumGuests = ?,
        Description = ?, IconClass = ?, Inclusions = ?, IsPopular = ?, IsActive = ?, DisplayOrder = ?,
        UpdatedAt = CURRENT_TIMESTAMP
        WHERE ServiceID = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssdisssiiii",
        $serviceName, $serviceType, $pricePerPerson, $minimumGuests,
        $description, $iconClass, $inclusions, $isPopular, $isActive, $displayOrder, $serviceID
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Service updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update service: ' . $stmt->error
        ]);
    }
}

// DELETE - Delete service
function handleDelete($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $serviceID = intval($data['service_id'] ?? 0);
    
    if ($serviceID == 0) {
        echo json_encode(['success' => false, 'message' => 'Service ID is required']);
        return;
    }
    
    $query = "DELETE FROM services WHERE ServiceID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $serviceID);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Service deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete service: ' . $stmt->error
        ]);
    }
}
?>