<?php
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $conn = getDbConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Fetch all menu items
    $query = "SELECT MenuID, DishName, Description, MenuPrice 
              FROM menu 
              ORDER BY DishName ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize menus by category
    // For now, we'll categorize based on keywords in the dish name
    $categorized = [
        'beef' => [],
        'pork' => [],
        'chicken' => [],
        'pancit' => []
    ];
    
    // Default images for categories
    $defaultImages = [
        'beef' => 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=800',
        'pork' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=800',
        'chicken' => 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=800',
        'pancit' => 'https://images.unsplash.com/photo-1612874742237-6526221588e3?w=800'
    ];
    
    foreach ($menus as $menu) {
        $dishName = strtolower($menu['DishName']);
        $menuItem = [
            'id' => $menu['MenuID'],
            'name' => $menu['DishName'],
            'prices' => [
                'small' => floatval($menu['MenuPrice']),
                'medium' => floatval($menu['MenuPrice']) * 1.4,  // 40% more
                'large' => floatval($menu['MenuPrice']) * 1.95   // 95% more
            ],
            'description' => $menu['Description'] ?: 'Delicious dish prepared with care',
            'image' => null
        ];
        
        // Categorize based on keywords
        if (strpos($dishName, 'beef') !== false) {
            $menuItem['image'] = $defaultImages['beef'];
            $categorized['beef'][] = $menuItem;
        } elseif (strpos($dishName, 'pork') !== false || strpos($dishName, 'lechon') !== false) {
            $menuItem['image'] = $defaultImages['pork'];
            $categorized['pork'][] = $menuItem;
        } elseif (strpos($dishName, 'chicken') !== false) {
            $menuItem['image'] = $defaultImages['chicken'];
            $categorized['chicken'][] = $menuItem;
        } elseif (strpos($dishName, 'pancit') !== false || strpos($dishName, 'noodle') !== false || strpos($dishName, 'lomi') !== false) {
            $menuItem['image'] = $defaultImages['pancit'];
            $categorized['pancit'][] = $menuItem;
        } else {
            // Default to beef category if no match
            $menuItem['image'] = $defaultImages['beef'];
            $categorized['beef'][] = $menuItem;
        }
    }
    
    // Build the response in the same format as the hardcoded data
    $response = [
        'beef' => [
            'title' => 'Beef',
            'items' => $categorized['beef']
        ],
        'pork' => [
            'title' => 'Pork',
            'items' => $categorized['pork']
        ],
        'chicken' => [
            'title' => 'Chicken',
            'items' => $categorized['chicken']
        ],
        'pancit' => [
            'title' => 'Pancit',
            'items' => $categorized['pancit']
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $response
    ]);
    
} catch (Exception $e) {
    error_log("Get Menu Data Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching menu data'
    ]);
}