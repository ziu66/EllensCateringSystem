<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();

$isLoggedIn = isLoggedIn();
$clientName = getUserName();

// Fetch services from database
$conn = getDB();
$servicesQuery = "SELECT * FROM services WHERE IsActive = 1 ORDER BY DisplayOrder ASC, ServiceID ASC";
$servicesResult = $conn->query($servicesQuery);

$services = [];
if ($servicesResult && $servicesResult->num_rows > 0) {
    while ($row = $servicesResult->fetch_assoc()) {
        $services[] = $row;
    }
}
$cartCount = isset($_SESSION['food_cart']) ? count($_SESSION['food_cart']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catering Services & Menu - Ellen's Catering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #000000;
            --secondary-dark: #1a1a1a;
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

        /* Cart Badge */
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
            height: 60vh;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                        url('Catering Service S&S.jpg') center/cover no-repeat;
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

        /* Services Section */
        .services-section {
            padding: 80px 0;
            background: white;
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

        /* Package Cards */
        .package-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s;
            height: 100%;
            border: 3px solid var(--border-gray);
            margin-bottom: 30px;
        }

        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-dark);
        }

        .package-card.featured {
            border-color: var(--primary-dark);
            position: relative;
            background: var(--primary-dark);
            color: white;
        }

        .package-card.featured::before {
            content: "MOST POPULAR";
            position: absolute;
            top: -15px;
            right: 20px;
            background: white;
            color: var(--primary-dark);
            padding: 5px 20px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .package-card.featured h4,
        .package-card.featured .package-price {
            color: white;
        }

        .package-card.featured .description,
        .package-card.featured p {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        .package-icon {
            width: 80px;
            height: 80px;
            background: var(--light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 2px solid var(--border-gray);
        }

        .package-card.featured .package-icon {
            background: white;
            border-color: white;
        }

        .package-icon i {
            font-size: 2.5rem;
            color: var(--primary-dark);
        }

        .package-card h4 {
            color: var(--primary-dark);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-align: center;
        }

        .package-price {
            font-size: 2.5rem;
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 10px;
            text-align: center;
        }

        .package-price span {
            font-size: 1rem;
            color: var(--medium-gray);
        }

        .package-card.featured .package-price span {
            color: rgba(255, 255, 255, 0.8);
        }

        .cta-button {
            display: inline-block;
            padding: 15px 40px;
            background: var(--primary-dark);
            color: white;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 1.1rem;
            border: 2px solid var(--primary-dark);
            width: 100%;
            text-align: center;
        }

        .cta-button:hover {
            background: white;
            color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .package-card.featured .cta-button {
            background: white;
            color: var(--primary-dark);
            border-color: white;
        }

        .package-card.featured .cta-button:hover {
            background: transparent;
            color: white;
        }

        /* Food Menu Section */
        .food-menu-section {
            padding: 80px 0;
            background: var(--light-gray);
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

        .category-nav .nav-link {
            color: white !important;
            font-weight: 600;
            padding: 10px 25px;
            border-radius: 50px;
            margin: 5px;
            transition: all 0.3s;
            background: transparent;
            border: 2px solid transparent;
        }

        .category-nav .nav-link:hover,
        .category-nav .nav-link.active {
            background: white;
            color: var(--primary-dark) !important;
            border-color: white;
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
            font-size: 1.2rem;
            color: var(--primary-dark);
            font-weight: 700;
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

        /* Inclusion Boxes */
        .inclusion-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            height: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 2px solid var(--border-gray);
        }

        .inclusion-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-dark);
        }

        .inclusion-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--light-gray);
        }

        .inclusion-header i {
            font-size: 2.5rem;
            color: var(--primary-dark);
            margin-right: 15px;
        }

        .inclusion-header h3 {
            color: var(--primary-dark);
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }

        .inclusion-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .inclusion-list li {
            padding: 12px 0;
            color: var(--text-dark);
            display: flex;
            align-items: flex-start;
            font-size: 1.05rem;
        }

        .inclusion-list li i {
            color: var(--primary-dark);
            margin-right: 12px;
            margin-top: 3px;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        /* Info Section */
        .info-section {
            background: white;
            padding: 80px 0;
        }

        .info-box {
            background: var(--primary-dark);
            color: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .info-box h3 {
            font-size: 2rem;
            margin-bottom: 20px;
            color: white;
        }

        .info-box p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .info-box ul {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .info-box ul li {
            padding: 10px 0;
            color: rgba(255, 255, 255, 0.95);
            display: flex;
            align-items: center;
        }

        .info-box ul li i {
            color: white;
            font-size: 1.3rem;
            margin-right: 15px;
        }

        /* Modal Styles */
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
        }

    .package-card {
    background: white;
    border-radius: 20px;
    padding: 40px 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.4s;
    height: 100%;
    border: 3px solid var(--border-gray);
    margin-bottom: 30px;
    display: flex;
    flex-direction: column;
}

.package-card h4 {
    color: var(--primary-dark);
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 15px;
    text-align: center;
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.package-price {
    font-size: 2.5rem;
    color: var(--primary-dark);
    font-weight: 700;
    margin-bottom: 10px;
    text-align: center;
}

.cta-button {
    display: inline-block;
    padding: 15px 40px;
    background: var(--primary-dark);
    color: white;
    font-weight: 600;
    border-radius: 50px;
    text-decoration: none;
    transition: all 0.3s;
    font-size: 1.1rem;
    border: 2px solid var(--primary-dark);
    width: 100%;
    text-align: center;
    margin-top: auto;
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
                    <a href="index.php" class="nav-link active">Home</a>
                </li>

                <li class="nav-item">
                    <a href="services.php" class="nav-link">Catering Services</a>
                </li>

                <li class="nav-item">
                    <a href="food_menu.php" class="nav-link">Food Menu</a>
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
            <h1>Catering Services</h1>
            <p>Creating Unforgettable Memories for Every Celebration</p>
        </div>
    </section>

    <!-- Package Pricing Section -->
    <section class="services-section">
        <div class="container">
            <div class="section-header">
                <h2>Our Event Packages</h2>
                <p>Complete catering solutions for every occasion</p>
            </div>

            <!-- What's Included in All Packages (Compact Version) -->
            <div class="row" style="margin-bottom: 60px; margin-top: 40px;">
                <div class="col-12">
                    <div style="background: var(--primary-dark); color: white; border-radius: 20px; padding: 50px 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
                        <div class="text-center" style="margin-bottom: 35px;">
                            <h3 style="font-size: 2rem; font-weight: 700; margin-bottom: 15px;">
                                <i class="bi bi-check-circle-fill me-2"></i>What's Included in All Packages
                            </h3>
                            <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem; margin: 0;">
                                Every package comes with complete food menu and professional setup
                            </p>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div style="background: rgba(255,255,255,0.1); border-radius: 15px; padding: 30px; height: 100%;">
                                    <h5 style="color: white; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center;">
                                        <i class="bi bi-egg-fried me-2" style="font-size: 1.5rem;"></i> Food Menu
                                    </h5>
                                    <p style="color: rgba(255,255,255,0.95); font-size: 0.95rem; line-height: 1.8; margin: 0;">
                                        Steamed Rice, 1 Pork Dish, 1 Chicken Dish, 1 Seafood Dish, 1 Beef Dish, 1 Vegetables Dish, 1 Pasta, 1 Dessert, 2 Drinks
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div style="background: rgba(255,255,255,0.1); border-radius: 15px; padding: 30px; height: 100%;">
                                    <h5 style="color: white; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center;">
                                        <i class="bi bi-stars me-2" style="font-size: 1.5rem;"></i> Services & Setup
                                    </h5>
                                    <p style="color: rgba(255,255,255,0.95); font-size: 0.95rem; line-height: 1.8; margin: 0;">
                                        Complete Buffet Setup, Chinawares & Utensils, Tables & Chairs w/ Centerpiece, Tables for Cakes/Giveaways, Tables for Drinks, Photo Backdrop, Decors & Styling w/ Theme Color, Professional Waiters
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pricing Cards - Dynamic from Database -->
<div class="row g-4 mb-5">
    <?php if (!empty($services)): ?>
        <?php foreach ($services as $index => $service): ?>
            <?php 
                $isPopular = $service['IsPopular'] == 1;
                $cardClass = $isPopular ? 'package-card featured' : 'package-card';
            ?>
            <div class="col-lg-3 col-md-6">
                <div class="<?= $cardClass ?>">
                    <div class="package-icon">
                        <i class="<?= htmlspecialchars($service['IconClass']) ?>"></i>
                    </div>
                    <h4><?= htmlspecialchars($service['ServiceName']) ?></h4>
                    <div class="package-price">
                        ₱<?= number_format($service['PricePerPerson'], 0) ?> 
                        <span>/ person</span>
                    </div>
                    <p style="text-align: center; <?= !$isPopular ? 'color: var(--medium-gray);' : '' ?> font-size: 0.9rem; margin-top: -10px;">
                        Minimum <?= number_format($service['MinimumGuests']) ?> guests
                    </p>
                    
                    <a href="manage_booking.php?package=<?= urlencode($service['ServiceName']) ?>" class="cta-button">Book Now</a>
                </div>
            </div>
            
            <?php 
                // Add row break after every 4 items
                if (($index + 1) % 4 == 0 && ($index + 1) < count($services)): 
            ?>
                </div><div class="row g-4 mb-5">
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2"></i>
                No services available at the moment. Please check back later.
            </div>
        </div>
    <?php endif; ?>
</div>

    <!-- Why Choose Us Section -->
    <section class="info-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="info-box text-center">
                        <h3><i class="bi bi-star-fill me-2"></i>Why Choose Ellen's Catering?</h3>
                        <p>
                            We specialize in creating memorable experiences for all types of celebrations. 
                            From intimate gatherings to grand celebrations, our team is dedicated to making 
                            your event extraordinary.
                        </p>
                        <ul>
                            <li><i class="bi bi-check-circle-fill"></i> Customizable menus to suit your preferences</li>
                            <li><i class="bi bi-check-circle-fill"></i> Professional and experienced staff</li>
                            <li><i class="bi bi-check-circle-fill"></i> High-quality fresh ingredients</li>
                            <li><i class="bi bi-check-circle-fill"></i> Complete event coordination</li>
                            <li><i class="bi bi-check-circle-fill"></i> Flexible payment terms</li>
                            <li><i class="bi bi-check-circle-fill"></i> Free consultation and planning</li>
                        </ul>
                        <div class="mt-4">
                            <a href="contact.php" class="cta-button" style="background: white; color: var(--primary-dark); max-width: 300px; margin: 0 auto; display: block; border-color: white;">
                                <i class="bi bi-telephone me-2"></i>Contact Us Today
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                            <h4 id="modalPrice" style="color: var(--primary-dark); font-weight: 700; margin-bottom: 20px;"></h4>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Number of Pax (Optional)</label>
                                <input type="number" class="form-control" id="modalPax" min="1" placeholder="Enter number of people">
                                <small class="text-muted">Leave empty if ordering by piece</small>
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

        // Open order modal with item details
        function openOrderModal(name, description, priceRange, image, category) {
            currentItemData = {
                name: name,
                description: description,
                priceRange: priceRange,
                image: image,
                category: category
            };
            
            document.getElementById('modalItemName').textContent = name;
            document.getElementById('modalDescription').textContent = description;
            document.getElementById('modalPrice').textContent = priceRange;
            document.getElementById('modalImage').src = image;
            document.getElementById('modalImage').alt = name;
            document.getElementById('modalQuantity').value = 1;
            document.getElementById('modalPax').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('orderModal'));
            modal.show();
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
            <?php if (!$isLoggedIn): ?>
                alert('Please login to add items to cart');
                window.location.href = 'login_dashboard.php';
                return;
            <?php else: ?>
                const quantity = parseInt(document.getElementById('modalQuantity').value) || 1;
                const pax = document.getElementById('modalPax').value ? parseInt(document.getElementById('modalPax').value) : null;
                
                // Get the button that was clicked
                const modalElement = document.getElementById('orderModal');
                const addButton = modalElement.querySelector('.btn-primary-custom');
                
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('item_name', currentItemData.name);
                formData.append('item_description', currentItemData.description);
                formData.append('price_range', currentItemData.priceRange);
                formData.append('quantity', quantity);
                if (pax) {
                    formData.append('number_of_pax', pax);
                }
                
                // Show loading state
                const originalText = addButton.innerHTML;
                addButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Adding...';
                addButton.disabled = true;
                
                fetch('view_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('orderModal'));
                        modal.hide();
                        
                        // Show success message
                        showNotification('Item added to cart successfully!', 'success');
                        
                        // Update cart count
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
            fetch('cart_handler.php?action=count')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.count > 0) {
                        // Add a badge to the cart link
                        const cartLink = document.querySelector('a[href="view_cart.php"]');
                        if (cartLink) {
                            let badge = cartLink.querySelector('.badge');
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'badge bg-danger rounded-pill ms-1';
                                cartLink.appendChild(badge);
                            }
                            badge.textContent = data.count;
                        }
                    }
                })
                .catch(error => console.error('Error updating cart count:', error));
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
                
                // Remove active class from all links
                document.querySelectorAll('.category-nav .nav-link').forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Smooth scroll to section
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
        <?php if ($isLoggedIn): ?>
            document.addEventListener('DOMContentLoaded', updateCartCount);
        <?php endif; ?>
    </script>
</body>
</html>