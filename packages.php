<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();

$isLoggedIn = isLoggedIn();
$clientName = $isLoggedIn ? getUserName() : null;

// Get package type from URL parameter
$packageType = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'celebration';

// Validate package type
$validTypes = ['celebration', 'bento', 'packed'];
if (!in_array($packageType, $validTypes)) {
    $packageType = 'celebration';
}

// Package data
$packages = [
    'celebration' => [
        'title' => 'Package Meal',
        'subtitle' => 'Perfect for Celebrations & Events',
        'description' => 'Complete meal packages designed for parties, gatherings, and special celebrations.',
        'icon' => 'bi-gift',
        'items' => [
            [
                'name' => 'Bronze Package',
                'price' => '₱15,000',
                'pax' => '50 persons',
                'image' => 'https://images.unsplash.com/photo-1555244162-803834f70033?w=800',
                'features' => [
                    '5 Main Dishes',
                    '2 Desserts',
                    'Unlimited Rice',
                    'Beverages Included',
                    'Basic Table Setup',
                    'Disposable Plates & Utensils'
                ]
            ],
            [
                'name' => 'Silver Package',
                'price' => '₱28,000',
                'pax' => '100 persons',
                'image' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800',
                'features' => [
                    '7 Main Dishes',
                    '3 Desserts',
                    'Unlimited Rice',
                    'Premium Beverages',
                    'Elegant Table Setup',
                    'Reusable Dinnerware',
                    'Basic Decoration'
                ],
                'popular' => true
            ],
            [
                'name' => 'Gold Package',
                'price' => '₱50,000',
                'pax' => '150 persons',
                'image' => 'https://images.unsplash.com/photo-1555244162-803834f70033?w=800',
                'features' => [
                    '10 Main Dishes',
                    '4 Desserts',
                    'Unlimited Rice',
                    'Premium Beverages & Welcome Drinks',
                    'Luxury Table Setup',
                    'Premium Dinnerware',
                    'Full Decoration',
                    'Service Staff Included'
                ]
            ]
        ]
    ],
    'bento' => [
        'title' => 'Bento Meal',
        'subtitle' => 'Individual Boxed Meals',
        'description' => 'Convenient and hygienic individual meal boxes perfect for corporate events, seminars, and meetings.',
        'icon' => 'bi-box',
        'items' => [
            [
                'name' => 'Classic Bento',
                'price' => '₱150',
                'pax' => 'per box',
                'image' => 'https://images.unsplash.com/photo-1617196034183-421b4917c92d?w=800',
                'features' => [
                    'Main Dish (Choice of Chicken, Pork, or Fish)',
                    'Rice',
                    'Side Vegetables',
                    'Bottled Water',
                    'Eco-friendly Box'
                ]
            ],
            [
                'name' => 'Premium Bento',
                'price' => '₱250',
                'pax' => 'per box',
                'image' => 'https://images.unsplash.com/photo-1564834724105-918b73d1b9e0?w=800',
                'features' => [
                    '2 Main Dishes',
                    'Rice or Pasta',
                    'Fresh Salad',
                    'Dessert',
                    'Beverage of Choice',
                    'Premium Packaging'
                ],
                'popular' => true
            ],
            [
                'name' => 'Executive Bento',
                'price' => '₱350',
                'pax' => 'per box',
                'image' => 'https://images.unsplash.com/photo-1625944525533-473f1a3d54e7?w=800',
                'features' => [
                    'Premium Protein (Beef or Seafood)',
                    'Gourmet Rice',
                    'Mixed Vegetables',
                    'Premium Dessert',
                    'Imported Beverage',
                    'Luxury Packaging',
                    'Cutlery Set Included'
                ]
            ]
        ]
    ],
    'packed' => [
        'title' => 'Packed Meal',
        'subtitle' => 'Ready-to-Eat Meals',
        'description' => 'Budget-friendly packed meals ideal for office lunches, picnics, and casual gatherings.',
        'icon' => 'bi-bag',
        'items' => [
            [
                'name' => 'Basic Packed Meal',
                'price' => '₱80',
                'pax' => 'per pack',
                'image' => 'https://images.unsplash.com/photo-1604152135912-04a022e23696?w=800',
                'features' => [
                    'Main Dish',
                    'Rice',
                    'Side Dish',
                    'Packed in Food-grade Container'
                ]
            ],
            [
                'name' => 'Standard Packed Meal',
                'price' => '₱120',
                'pax' => 'per pack',
                'image' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800',
                'features' => [
                    'Choice of Protein',
                    'Rice',
                    '2 Side Dishes',
                    'Bottled Water',
                    'Sealed Container'
                ],
                'popular' => true
            ],
            [
                'name' => 'Deluxe Packed Meal',
                'price' => '₱180',
                'pax' => 'per pack',
                'image' => 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=800',
                'features' => [
                    'Premium Protein',
                    'Rice or Alternative',
                    'Mixed Vegetables',
                    'Fresh Fruit',
                    'Beverage',
                    'Quality Container'
                ]
            ]
        ]
    ]
];

$currentPackage = $packages[$packageType];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?= htmlspecialchars($currentPackage['description']) ?>" />
    <title><?= htmlspecialchars($currentPackage['title']) ?> - Ellen's Catering</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet" />

    <style>
        :root {
            --primary-dark: #000000;
            --secondary-dark: #212529;
            --light-gray: #f8f9fa;
            --border-gray: #dee2e6;
            --text-dark: #212529;
            --accent-gray: #495057;
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

        /* Navbar Styles */
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

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-dark) 100%);
            color: white;
            padding: 140px 0 80px;
            margin-top: 76px;
            text-align: center;
        }

        .page-header h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .page-header .subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .page-header .description {
            font-size: 1.1rem;
            opacity: 0.8;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Package Type Selector */
        .package-selector {
            background: white;
            padding: 30px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 76px;
            z-index: 100;
        }

        .package-selector .btn-group {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 50px;
            overflow: hidden;
        }

        .package-selector .btn {
            padding: 12px 30px;
            font-weight: 600;
            border: none;
            background: white;
            color: var(--medium-gray);
            transition: all 0.3s;
        }

        .package-selector .btn.active {
            background: var(--primary-dark);
            color: white;
        }

        .package-selector .btn:hover:not(.active) {
            background: var(--light-gray);
            color: var(--primary-dark);
        }

        /* Packages Section */
        .packages-section {
            padding: 80px 0;
        }

        .package-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }

        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .package-card.popular {
            border: 3px solid var(--primary-dark);
        }

        .popular-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--primary-dark);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            z-index: 10;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .package-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .package-content {
            padding: 30px;
        }

        .package-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 15px;
        }

        .package-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }

        .package-pax {
            color: var(--medium-gray);
            font-size: 1.1rem;
            margin-bottom: 25px;
        }

        .package-features {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
        }

        .package-features li {
            padding: 10px 0;
            border-bottom: 1px solid var(--border-gray);
            color: var(--text-dark);
            display: flex;
            align-items: center;
        }

        .package-features li:last-child {
            border-bottom: none;
        }

        .package-features li i {
            color: var(--primary-dark);
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .btn-book {
            width: 100%;
            padding: 14px;
            background: var(--primary-dark);
            color: white;
            border: 2px solid var(--primary-dark);
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .btn-book:hover {
            background: white;
            color: var(--primary-dark);
            transform: scale(1.05);
        }

        /* Info Section */
        .info-section {
            background: linear-gradient(to bottom, white, var(--light-gray));
            padding: 60px 0;
            text-align: center;
        }

        .info-section h3 {
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 20px;
        }

        .info-section p {
            color: var(--medium-gray);
            font-size: 1.1rem;
            max-width: 800px;
            margin: 0 auto 30px;
        }

        .info-section .btn-contact {
            background: var(--primary-dark);
            color: white;
            padding: 14px 40px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .info-section .btn-contact:hover {
            background: var(--secondary-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Footer */
        footer {
            background: var(--primary-dark);
            color: var(--light-gray);
            text-align: center;
            padding: 30px 0;
        }

        footer a {
            color: var(--light-gray);
            text-decoration: none;
            transition: color 0.3s;
        }

        footer a:hover {
            color: #ffffff;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .navbar-brand {
                margin-left: 0;
            }

            .page-header {
                padding: 120px 0 60px;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .package-selector {
                top: 70px;
            }
        }

        @media (max-width: 768px) {
            .package-card {
                margin-bottom: 30px;
            }

            .package-selector .btn-group {
                flex-direction: column;
                width: 100%;
            }

            .package-selector .btn {
                border-radius: 0 !important;
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
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">Home</a>
                    </li>

                    <li class="nav-item dropdown">
                         <a href="services.php" class="nav-link">Catering Services</a>

                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="packagesDropdown" role="button" data-bs-toggle="dropdown">
                            Packages
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item <?= $packageType === 'celebration' ? 'active' : '' ?>" href="packages.php?type=celebration"><i class="bi bi-gift me-2"></i>Package Meal</a></li>
                            <li><a class="dropdown-item <?= $packageType === 'bento' ? 'active' : '' ?>" href="packages.php?type=bento"><i class="bi bi-box me-2"></i>Bento Meal</a></li>
                            <li><a class="dropdown-item <?= $packageType === 'packed' ? 'active' : '' ?>" href="packages.php?type=packed"><i class="bi bi-bag me-2"></i>Packed Meal</a></li>
                        </ul>
                    </li>

                    <li class="nav-item"><a href="manage_booking.php" class="nav-link">Book Now</a></li>
                    <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>

                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($clientName) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile_management.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="my_bookings.php"><i class="bi bi-calendar-check me-2"></i> My Bookings</a></li>
                                <li><a class="dropdown-item" href="profile_settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <i class="bi <?= htmlspecialchars($currentPackage['icon']) ?>" style="font-size: 4rem; margin-bottom: 20px;"></i>
            <h1><?= htmlspecialchars($currentPackage['title']) ?></h1>
            <p class="subtitle"><?= htmlspecialchars($currentPackage['subtitle']) ?></p>
            <p class="description"><?= htmlspecialchars($currentPackage['description']) ?></p>
        </div>
    </section>

    <!-- Package Type Selector -->
    <div class="package-selector">
        <div class="container text-center">
            <div class="btn-group" role="group">
                <a href="packages.php?type=celebration" class="btn <?= $packageType === 'celebration' ? 'active' : '' ?>">
                    <i class="bi bi-gift me-2"></i>Package Meal
                </a>
                <a href="packages.php?type=bento" class="btn <?= $packageType === 'bento' ? 'active' : '' ?>">
                    <i class="bi bi-box me-2"></i>Bento Meal
                </a>
                <a href="packages.php?type=packed" class="btn <?= $packageType === 'packed' ? 'active' : '' ?>">
                    <i class="bi bi-bag me-2"></i>Packed Meal
                </a>
            </div>
        </div>
    </div>

    <!-- Packages Section -->
    <section class="packages-section">
        <div class="container">
            <div class="row g-4">
                <?php foreach ($currentPackage['items'] as $item): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="package-card <?= isset($item['popular']) && $item['popular'] ? 'popular' : '' ?>">
                        <?php if (isset($item['popular']) && $item['popular']): ?>
                            <div class="popular-badge">Most Popular</div>
                        <?php endif; ?>
                        
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="package-image">
                        
                        <div class="package-content">
                            <h3 class="package-name"><?= htmlspecialchars($item['name']) ?></h3>
                            <div class="package-price"><?= htmlspecialchars($item['price']) ?></div>
                            <p class="package-pax"><?= htmlspecialchars($item['pax']) ?></p>
                            
                            <ul class="package-features">
                                <?php foreach ($item['features'] as $feature): ?>
                                    <li>
                                        <i class="bi bi-check-circle-fill"></i>
                                        <?= htmlspecialchars($feature) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <a href="manage_booking.php?package=<?= urlencode($item['name']) ?>&type=<?= urlencode($packageType) ?>" class="btn btn-book">
                                Book Now
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Info Section -->
    <section class="info-section">
        <div class="container">
            <h3>Need a Custom Package?</h3>
            <p>We understand that every event is unique. If our standard packages don't quite fit your needs, we'd be happy to create a customized solution tailored specifically for your occasion.</p>
            <a href="contact.php" class="btn-contact">Contact Us for Custom Quotes</a>
        </div>
    </section>

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

        // Smooth scroll to packages
        document.addEventListener('DOMContentLoaded', () => {
            if (window.location.hash) {
                const target = document.querySelector(window.location.hash);
                if (target) {
                    setTimeout(() => {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100);
                }
            }
        });
    </script>
</body>
</html>