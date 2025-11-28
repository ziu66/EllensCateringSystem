<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();

// Initialize database connection
$conn = getDB();

if (!$conn) {
    error_log("Database connection failed in food_menu.php");
    // Set empty menu as fallback
    $foodMenu = [
        'beef' => ['title' => 'Beef', 'items' => []],
        'pork' => ['title' => 'Pork', 'items' => []],
        'chicken' => ['title' => 'Chicken', 'items' => []],
        'pancit' => ['title' => 'Pancit', 'items' => []]
    ];
}

// Initialize cart in session if not exists
if (!isset($_SESSION['food_cart'])) {
    $_SESSION['food_cart'] = [];
}

$isLoggedIn = isLoggedIn();
$clientName = getUserName();

// Food Menu Data with size-based pricing
// Fetch menu data from database
// Fetch menu data from database
$foodMenu = [];
if ($conn) {
    try {
        $query = "SELECT MenuID, DishName, Description, MenuPrice, Category, ImageURL 
                  FROM menu 
                  ORDER BY Category ASC, DishName ASC";
        
        $result = $conn->query($query);
        
        if ($result) {
            $menus = $result->fetch_all(MYSQLI_ASSOC);
            
            // Organize menus by category
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
                $basePrice = floatval($menu['MenuPrice']);
                $category = strtolower($menu['Category'] ?: 'beef');
                
                // Use uploaded image if available, otherwise use default
                $imageUrl = !empty($menu['ImageURL']) ? $menu['ImageURL'] : ($defaultImages[$category] ?? $defaultImages['beef']);
                
                $menuItem = [
                    'name' => $menu['DishName'],
                    'prices' => [
                        'small' => $basePrice,
                        'medium' => round($basePrice * 1.4, 2),
                        'large' => round($basePrice * 1.95, 2)
                    ],
                    'description' => $menu['Description'] ?: 'Delicious dish prepared with care',
                    'image' => $imageUrl
                ];
                
                // Add to the correct category based on database Category field
                if (isset($categorized[$category])) {
                    $categorized[$category][] = $menuItem;
                } else {
                    // Default to beef if category doesn't exist
                    $categorized['beef'][] = $menuItem;
                }
            }
            
            // Build the final menu structure
            $foodMenu = [
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
        }
        
    } catch (Exception $e) {
        error_log("Food Menu Error: " . $e->getMessage());
        // Fallback to empty categories if database fails
        $foodMenu = [
            'beef' => ['title' => 'Beef', 'items' => []],
            'pork' => ['title' => 'Pork', 'items' => []],
            'chicken' => ['title' => 'Chicken', 'items' => []],
            'pancit' => ['title' => 'Pancit', 'items' => []]
        ];
    }
} else {
    // No connection, set empty menu
    $foodMenu = [
        'beef' => ['title' => 'Beef', 'items' => []],
        'pork' => ['title' => 'Pork', 'items' => []],
        'chicken' => ['title' => 'Chicken', 'items' => []],
        'pancit' => ['title' => 'Pancit', 'items' => []]
    ];
}
$cartCount = isset($_SESSION['food_cart']) ? count($_SESSION['food_cart']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Menu - Ellen's Catering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #000000;
            --secondary-dark: #1a1a1a;
            --light-gray: #f8f9fa;
            --border-gray: #dee2e6;
            --text-dark: #212529;
            --medium-gray: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-gray);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.98) !important;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            z-index: 1000;
            top: 0;
            padding: 1.2rem 0;
        }

        .navbar-brand {
            position: relative;
            display: flex;
            align-items: center;
            height: 80px;
            overflow: visible;
            margin-left: -50px;
        }

        .navbar-brand img {
            position: absolute;
            height: 150px;
            width: 150px;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--primary-dark);
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            top: -10px;
            left: 0;
            object-fit: contain;
            z-index: 2;
        }

        .nav-link {
            color: var(--primary-dark) !important;
            font-weight: 500;
            margin: 0 14px;
            transition: color 0.3s ease;
            font-size: 1.05rem;
            position: relative;
        }

        .nav-link:not(.dropdown-toggle)::after {
            content: "";
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--primary-dark);
            transition: width 0.3s ease;
        }

        .nav-link:not(.dropdown-toggle):hover::after {
            width: 100%;
        }

        .nav-link.dropdown-toggle::before {
            content: "";
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--primary-dark);
            transition: width 0.3s ease;
        }

        .nav-link.dropdown-toggle:hover::before {
            width: 100%;
        }

        .dropdown-menu {
            background-color: #ffffff;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            padding: 10px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-top: 10px;
        }

        .dropdown-item {
            color: var(--text-dark);
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background-color: var(--primary-dark);
            color: #fff;
            padding-left: 25px;
        }

        .nav-link .badge {
            position: absolute;
            top: -5px;
            right: -10px;
            font-size: 0.7rem;
            padding: 3px 6px;
            animation: pulse 1s ease-in-out;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Hero Section */
        .hero {
            position: relative;
            height: 50vh;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                        url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=1600') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            margin-top: 80px;
        }

        .hero-content h1 {
            font-family: "Georgia", serif;
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero-content p {
            font-size: 1.2rem;
            color: #f0f0f0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        /* Food Menu Section */
        .food-menu-section {
            padding: 80px 0;
            background: var(--light-gray);
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            color: var(--primary-dark);
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .section-header h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary-dark);
            border-radius: 2px;
        }

        .section-header p {
            color: var(--medium-gray);
            font-size: 1.2rem;
            margin-top: 25px;
        }

        /* Category Navigation */
        .category-nav {
            background: var(--primary-dark);
            padding: 20px 0;
            position: sticky;
            top: 106px;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .category-nav .nav {
            justify-content: center;
            flex-wrap: wrap;
        }

        .category-nav .nav-link::after,
        .category-nav .nav-link::before {
            display: none;
        }

        /* Food Card */
        .food-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            height: 100%;
            border: 2px solid var(--border-gray);
            position: relative;
        }

        .food-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-dark);
        }

        .food-card-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--primary-dark);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.75rem;
            z-index: 10;
        }

        .food-card-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .food-card-body {
            padding: 25px;
        }

        .food-card-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 10px;
        }

        .food-card-price {
            font-size: 0.9rem;
            color: var(--medium-gray);
            margin-bottom: 15px;
        }

        .food-card-description {
            color: var(--medium-gray);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .order-button {
            width: 100%;
            padding: 12px;
            background: var(--primary-dark);
            color: white;
            border: 2px solid var(--primary-dark);
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }

        .order-button:hover {
            background: white;
            color: var(--primary-dark);
        }

        /* Category Section */
        .category-section {
            padding: 60px 0;
            border-bottom: 2px solid var(--border-gray);
        }

        .category-section:last-child {
            border-bottom: none;
        }

        .category-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            text-align: center;
            margin-bottom: 50px;
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .category-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--primary-dark);
            border-radius: 2px;
        }

        /* Modal Styles */
        .size-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .size-option {
            flex: 1;
            padding: 15px;
            border: 2px solid var(--border-gray);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .size-option:hover {
            border-color: var(--primary-dark);
            background: var(--light-gray);
        }

        .size-option.selected {
            border-color: var(--primary-dark);
            background: var(--primary-dark);
            color: white;
        }

        .size-label {
            font-weight: 700;
            font-size: 1.1rem;
            display: block;
            margin-bottom: 5px;
        }

        .size-price {
            font-size: 1rem;
            font-weight: 600;
        }

        .quantity-control {
            display: flex;
            align-items: center;
        }

        .quantity-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: var(--primary-dark);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .quantity-btn:hover {
            background: var(--secondary-dark);
            transform: scale(1.05);
        }

        .btn-primary-custom {
            background: var(--primary-dark);
            color: white;
            border: 2px solid var(--primary-dark);
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary-custom:hover {
            background: white;
            color: var(--primary-dark);
        }

        /* Footer */
        footer {
            background: var(--primary-dark);
            color: var(--light-gray);
            text-align: center;
            padding: 30px 0;
            margin-top: 50px;
        }

        footer a {
            color: var(--light-gray);
            text-decoration: none;
            transition: color 0.3s;
        }

        footer a:hover {
            color: #ffffff;
        }

        /* Notification Animation */
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Responsive */
        @media (max-width: 991px) {
            .navbar-brand {
                margin-left: 0;
            }

            .category-nav {
                top: 76px;
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .food-card {
                margin-bottom: 20px;
            }

            .size-selector {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="ellenLogo_removebg-preview.png" alt="Ellen's Catering Logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">Home</a>
                </li>

                <li class="nav-item">
                    <a href="services.php" class="nav-link">Catering Services</a>
                </li>

                <li class="nav-item">
                    <a href="food_menu.php" class="nav-link active">Food Menu</a>
                </li>

                <li class="nav-item"><a href="manage_booking.php" class="nav-link">Book Now</a></li>
                <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>

                <li class="nav-item">
    <a href="view_cart.php" class="nav-link position-relative">
        <i class="bi bi-cart3"></i> Cart
        <span class="badge bg-danger rounded-pill ms-1" id="cart-badge" 
              <?= ($cartCount == 0) ? 'style="display: none;"' : '' ?>>
            <?= $cartCount ?>
        </span>
    </a>
</li>
            </ul>

            <ul class="navbar-nav mb-2 mb-lg-0">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($clientName) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile_management.php"><i class="bi bi-speedometer2 me-2"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="view_cart.php"><i class="bi bi-cart3 me-2"></i> My Cart</a></li>
                            <li><a class="dropdown-item" href="my_bookings.php"><i class="bi bi-calendar-check me-2"></i> My Bookings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a href="login_dashboard.php" class="nav-link">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Our Food Menu</h1>
            <p>Delicious Dishes Crafted with Love</p>
        </div>
    </section>

    <!-- Food Menu Section -->
    <section class="food-menu-section">
        <div class="container">
            <div class="section-header">
                <h2>Browse Our Menu</h2>
                <p>Fresh ingredients, authentic flavors</p>
            </div>
        </div>

        <!-- Category Navigation -->
        <div class="category-nav">
            <div class="container">
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="#beef">Beef</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pork">Pork</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#chicken">Chicken</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pancit">Pancit</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Food Categories -->
        <?php foreach ($foodMenu as $categoryId => $category): ?>
        <div class="container category-section" id="<?= $categoryId ?>">
            <h3 class="category-title"><?= htmlspecialchars($category['title']) ?></h3>
            
            <div class="row g-4">
                <?php foreach ($category['items'] as $item): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="food-card">
                        <?php if (isset($item['badge'])): ?>
                            <div class="food-card-badge"><?= htmlspecialchars($item['badge']) ?></div>
                        <?php endif; ?>
                        
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="food-card-image">
                        
                        <div class="food-card-body">
                            <h5 class="food-card-title"><?= htmlspecialchars($item['name']) ?></h5>
                            <div class="food-card-price">
                                S: ₱<?= number_format($item['prices']['small'], 2) ?> | 
                                M: ₱<?= number_format($item['prices']['medium'], 2) ?> | 
                                L: ₱<?= number_format($item['prices']['large'], 2) ?>
                            </div>
                            <p class="food-card-description"><?= htmlspecialchars($item['description']) ?></p>
                            <button class="order-button" onclick='openOrderModal(<?= json_encode($item['name']) ?>, <?= json_encode($item['description']) ?>, <?= json_encode($item['prices']) ?>, <?= json_encode($item['image']) ?>, <?= json_encode($categoryId) ?>)'>
                                <i class="bi bi-cart-plus me-2"></i>Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- Order Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: 2px solid var(--border-gray);">
                <div class="modal-header" style="background: var(--primary-dark); color: white; border-radius: 18px 18px 0 0;">
                    <h5 class="modal-title" id="orderModalLabel">
                        <i class="bi bi-cart-plus me-2"></i>Add to Cart
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-5">
                            <img id="modalImage" src="" alt="" class="img-fluid" style="border-radius: 15px; width: 100%; height: 250px; object-fit: cover;">
                        </div>
                        <div class="col-md-7">
                            <h3 id="modalItemName" style="color: var(--primary-dark); font-weight: 700; margin-bottom: 15px;"></h3>
                            <p id="modalDescription" style="color: var(--medium-gray); margin-bottom: 15px;"></p>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Select Size</label>
                                <div class="size-selector" id="sizeSelector">
                                    <!-- Size options will be inserted here -->
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Quantity</label>
                                <div class="quantity-control">
                                    <button class="quantity-btn" onclick="adjustQuantity(-1)">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <input type="number" class="form-control text-center mx-2" id="modalQuantity" value="1" min="1" style="width: 80px; font-weight: 700; font-size: 1.2rem;">
                                    <button class="quantity-btn" onclick="adjustQuantity(1)">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 2px solid var(--border-gray);">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal" style="border-radius: 50px; padding: 10px 30px; border-width: 2px; font-weight: 600;">
                        Cancel
                    </button>
                    <button type="button" class="btn-primary-custom" onclick="addToCart()" style="padding: 10px 30px;">
                        <i class="bi bi-cart-plus me-2"></i>Add to Cart
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 Ellen's Catering. All rights reserved.</p>
            <p class="mt-2">
                <a href="privacy.php" class="me-3">Privacy Policy</a>
                <a href="terms.php">Terms of Service</a>
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Store current item data
    let currentItemData = {};
    let selectedSize = null;

    // ===== CART STORAGE FUNCTIONS =====
    
    // Get cart from localStorage or session
    function getCart() {
        <?php if ($isLoggedIn): ?>
            // If logged in, rely on server-side session
            return [];
        <?php else: ?>
            // If not logged in, use localStorage
            const cartData = localStorage.getItem('guest_cart');
            return cartData ? JSON.parse(cartData) : [];
        <?php endif; ?>
    }

    // Save cart to localStorage
    function saveCartToStorage(cart) {
        <?php if (!$isLoggedIn): ?>
            localStorage.setItem('guest_cart', JSON.stringify(cart));
        <?php endif; ?>
    }

    // Open order modal with item details
    function openOrderModal(name, description, prices, image, category) {
        currentItemData = {
            name: name,
            description: description,
            prices: prices,
            image: image,
            category: category
        };
        
        document.getElementById('modalItemName').textContent = name;
        document.getElementById('modalDescription').textContent = description;
        document.getElementById('modalImage').src = image;
        document.getElementById('modalImage').alt = name;
        document.getElementById('modalQuantity').value = 1;
        
        // Create size options
        const sizeSelector = document.getElementById('sizeSelector');
        sizeSelector.innerHTML = '';
        
        const sizes = [
            { key: 'small', label: 'Small', price: prices.small },
            { key: 'medium', label: 'Medium', price: prices.medium },
            { key: 'large', label: 'Large', price: prices.large }
        ];
        
        sizes.forEach((size, index) => {
            const sizeOption = document.createElement('div');
            sizeOption.className = 'size-option' + (index === 0 ? ' selected' : '');
            sizeOption.onclick = () => selectSize(size.key, size.price);
            sizeOption.innerHTML = `
                <span class="size-label">${size.label}</span>
                <span class="size-price">₱${size.price.toFixed(2)}</span>
            `;
            sizeSelector.appendChild(sizeOption);
        });
        
        // Set default selected size
        selectedSize = { key: 'small', price: prices.small };
        
        const modal = new bootstrap.Modal(document.getElementById('orderModal'));
        modal.show();
    }

    // Select size
    function selectSize(sizeKey, price) {
        selectedSize = { key: sizeKey, price: price };
        
        // Update UI
        document.querySelectorAll('.size-option').forEach(option => {
            option.classList.remove('selected');
        });
        event.target.closest('.size-option').classList.add('selected');
    }

    // Adjust quantity
    function adjustQuantity(change) {
        const quantityInput = document.getElementById('modalQuantity');
        let currentValue = parseInt(quantityInput.value) || 1;
        let newValue = currentValue + change;
        
        if (newValue >= 1) {
            quantityInput.value = newValue;
        }
    }

    // Add to cart
    function addToCart() {
        if (!selectedSize) {
            showNotification('Please select a size', 'error');
            return;
        }
        
        const quantity = parseInt(document.getElementById('modalQuantity').value) || 1;
        const modalElement = document.getElementById('orderModal');
        const addButton = modalElement.querySelector('.btn-primary-custom');
        
        <?php if (!$isLoggedIn): ?>
            // GUEST USER - Add to localStorage
            const cartItem = {
                item_name: currentItemData.name,
                item_description: currentItemData.description,
                size: selectedSize.key,
                price: selectedSize.price,
                quantity: quantity,
                subtotal: selectedSize.price * quantity,
                added_at: new Date().toISOString()
            };
            
            let cart = getCart();
            
            // Check if item exists
            let itemExists = false;
            cart = cart.map(item => {
                if (item.item_name === cartItem.item_name && item.size === cartItem.size) {
                    item.quantity += cartItem.quantity;
                    item.subtotal = item.price * item.quantity;
                    itemExists = true;
                }
                return item;
            });
            
            if (!itemExists) {
                cart.push(cartItem);
            }
            
            saveCartToStorage(cart);
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('orderModal'));
            modal.hide();
            
            showNotification('Item added to cart! Login to checkout.', 'success');
            updateCartCount();
            
        <?php else: ?>
            // LOGGED IN USER - Send to server
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('item_name', currentItemData.name);
            formData.append('item_description', currentItemData.description);
            formData.append('size', selectedSize.key);
            formData.append('price', selectedSize.price);
            formData.append('quantity', quantity);
            
            const originalText = addButton.innerHTML;
            addButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Adding...';
            addButton.disabled = true;
            
            fetch('view_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('orderModal'));
                    modal.hide();
                    
                    showNotification('Item added to cart successfully!', 'success');
                    updateCartCount();
                } else {
                    showNotification(data.message || 'Failed to add item to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                addButton.innerHTML = originalText;
                addButton.disabled = false;
            });
        <?php endif; ?>
    }

    // Show notification
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
        notification.style.cssText = 'top: 120px; right: 20px; z-index: 9999; min-width: 300px; animation: slideIn 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.2);';
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'x-circle-fill'} me-2"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Update cart count in navbar
    function updateCartCount() {
        <?php if ($isLoggedIn): ?>
            fetch('view_cart.php?action=count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartBadge(data.count);
                    }
                })
                .catch(error => console.error('Error updating cart count:', error));
        <?php else: ?>
            const cart = getCart();
            updateCartBadge(cart.length);
        <?php endif; ?>
    }

    function updateCartBadge(count) {
    const cartLink = document.querySelector('a[href="view_cart.php"], a[onclick="promptLogin()"]');
    if (cartLink) {
        let badge = cartLink.querySelector('.badge') || cartLink.querySelector('#cart-badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge bg-danger rounded-pill ms-1';
                badge.id = 'cart-badge';
                cartLink.appendChild(badge);
            }
            badge.textContent = count;
            badge.style.display = '';
        } else if (badge) {
            badge.style.display = 'none';
        }
    }
}

// ADD THIS NEW FUNCTION
function promptLogin() {
    const cart = getCart();
    if (cart.length > 0) {
        if (confirm('You have ' + cart.length + ' item(s) in your cart. Login to checkout?')) {
            // Save cart to localStorage before redirecting
            saveCartToStorage(cart);
            window.location.href = 'login_dashboard.php?redirect=view_cart.php';
        }
    } else {
        alert('Your cart is empty. Add some items first!');
    }
}
    // ADD THIS NEW FUNCTION
function promptLogin() {
    const cart = getCart();
    if (cart.length > 0) {
        if (confirm('You have ' + cart.length + ' item(s) in your cart. Login to checkout?')) {
            // Save cart to localStorage before redirecting
            saveCartToStorage(cart);
            window.location.href = 'login_dashboard.php?redirect=view_cart.php';
        }
    } else {
        alert('Your cart is empty. Add some items first!');
    }
}

    // Dropdown hover effect for desktop
    document.querySelectorAll('.nav-item.dropdown').forEach(dropdown => {
        dropdown.addEventListener('mouseenter', () => {
            if (window.innerWidth >= 992) {
                dropdown.classList.add('show');
                dropdown.querySelector('.dropdown-menu').classList.add('show');
            }
        });

        dropdown.addEventListener('mouseleave', () => {
            if (window.innerWidth >= 992) {
                dropdown.classList.remove('show');
                dropdown.querySelector('.dropdown-menu').classList.remove('show');
            }
        });
    });

    // Category navigation smooth scroll and active state
    document.querySelectorAll('.category-nav .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            document.querySelectorAll('.category-nav .nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            const navHeight = document.querySelector('.navbar').offsetHeight;
            const categoryNavHeight = document.querySelector('.category-nav').offsetHeight;
            
            window.scrollTo({
                top: targetSection.offsetTop - navHeight - categoryNavHeight + 10,
                behavior: 'smooth'
            });
        });
    });

    // Update active category on scroll
    window.addEventListener('scroll', () => {
        const navHeight = document.querySelector('.navbar').offsetHeight;
        const categoryNavHeight = document.querySelector('.category-nav').offsetHeight;
        const sections = document.querySelectorAll('.category-section');
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop - navHeight - categoryNavHeight - 50;
            const sectionBottom = sectionTop + section.offsetHeight;
            const scrollPosition = window.scrollY;
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                const sectionId = section.getAttribute('id');
                document.querySelectorAll('.category-nav .nav-link').forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#' + sectionId) {
                        link.classList.add('active');
                    }
                });
            }
        });
    });

    // Update cart count on page load
    document.addEventListener('DOMContentLoaded', updateCartCount);
    
    // ADD THIS NEW CODE - Auto-merge cart after login
    <?php if ($isLoggedIn): ?>
    // Check if user just logged in and has localStorage cart
    window.addEventListener('DOMContentLoaded', function() {
        const guestCart = localStorage.getItem('guest_cart');
        
        if (guestCart) {
            console.log('Found guest cart, merging...');
            
            // Send cart data to server to merge
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
                    console.log('Cart merged successfully');
                    // Clear localStorage cart after successful merge
                    localStorage.removeItem('guest_cart');
                    // Update cart count
                    updateCartCount();
                    // Show notification
                    showNotification('Your cart items have been restored!', 'success');
                }
            })
            .catch(error => {
                console.error('Cart merge error:', error);
            });
        }
    });
    <?php endif; ?>
</script>

<script>
    // Navbar scroll effect
    window.addEventListener("scroll", function() {
        const navbar = document.querySelector(".navbar");
        navbar.classList.toggle("scrolled", window.scrollY > 50);
    });

    // Dropdown hover effect for desktop
    document.querySelectorAll('.nav-item.dropdown').forEach(dropdown => {
        dropdown.addEventListener('mouseenter', () => {
            if (window.innerWidth >= 992) {
                dropdown.classList.add('show');
                dropdown.querySelector('.dropdown-menu').classList.add('show');
            }
        });

        dropdown.addEventListener('mouseleave', () => {
            if (window.innerWidth >= 992) {
                dropdown.classList.remove('show');
                dropdown.querySelector('.dropdown-menu').classList.remove('show');
            }
        });
    });
</script>

</body>
</html>