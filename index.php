<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();

$welcomeModal = '';

if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome'] === true) {
    $clientName = sanitizeInput($_SESSION['client_name']);
    $welcomeModal = <<<HTML
    <div class="modal fade" id="welcomeModal" tabindex="-1" aria-labelledby="welcomeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4" style="border-radius: 20px;">
                <h4 class="mb-3 text-dark">Welcome, {$clientName}!</h4>
                <p>We're happy to see you.</p>
                <button class="btn btn-dark px-4" data-bs-dismiss="modal">Continue</button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const welcomeModal = new bootstrap.Modal(document.getElementById('welcomeModal'));
            welcomeModal.show();
        });
    </script>
    HTML;
    unset($_SESSION['show_welcome']); 
}

$isLoggedIn = isLoggedIn();
$clientName = $isLoggedIn ? getUserName() : null;
$cartCount = isset($_SESSION['food_cart']) ? count($_SESSION['food_cart']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Ellen's Catering - Professional catering services for weddings, debuts, corporate events, and celebrations" />
    <title>Ellen's Catering & Event Management</title>

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
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        /* Navbar Styles */
        .navbar {
            background: transparent !important;
            position: fixed;
            width: 100%;
            z-index: 1000;
            top: 0;
            padding: 1.2rem 0;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98) !important;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
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
            width: auto;
            top: -20px;
            left: 0;
            object-fit: contain;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .navbar.scrolled .navbar-brand img {
            height: 150px;
            width: 150px;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--primary-dark);
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            top: -10px;
        }

        .navbar-toggler {
            border-color: var(--light-gray);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(222, 226, 230, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .nav-link {
            position: relative;
            color: var(--light-gray) !important;
            font-weight: 500;
            margin: 0 14px;
            transition: color 0.3s ease;
            font-size: 1.05rem;
            padding-top: 8px;
        }

        .navbar.scrolled .nav-link {
            color: var(--primary-dark) !important;
        }

        /* Underline effect for regular nav links */
        .nav-link:not(.dropdown-toggle)::after {
            content: "";
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #ffffff;
            transition: width 0.3s ease;
        }

        .navbar.scrolled .nav-link:not(.dropdown-toggle)::after {
            background-color: var(--primary-dark);
        }

        .nav-link:not(.dropdown-toggle):hover::after {
            width: 100%;
        }

        /* Underline effect for dropdown toggles - animated like progress bar */
        .nav-link.dropdown-toggle {
            position: relative;
        }

        .nav-link.dropdown-toggle::before {
            content: "";
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #ffffff;
            transition: width 0.3s ease;
        }

        .navbar.scrolled .nav-link.dropdown-toggle::before {
            background-color: var(--primary-dark);
        }

        .nav-link.dropdown-toggle:hover::before {
            width: 100%;
        }

        .nav-link:hover {
            color: #ffffff !important;
        }

        .navbar.scrolled .nav-link:hover {
            color: var(--secondary-dark) !important;
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

        /* Hero Section */
        .hero {
            position: relative;
            height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                        url('bg.jpg') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 900px;
            animation: fadeInUp 1s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero h1 {
            font-family: "Georgia", serif;
            font-size: clamp(2.5rem, 6vw, 5rem);
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero h1 span {
            display: block;
        }

        .hero p {
            font-size: 1.3rem;
            color: #f0f0f0;
            margin-top: 20px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .hero .btn {
            margin-top: 30px;
            background-color: var(--primary-dark);
            color: #ffffff;
            font-weight: 600;
            border-radius: 50px;
            padding: 14px 40px;
            transition: all 0.3s;
            border: 2px solid var(--primary-dark);
            font-size: 1.1rem;
        }

        .hero .btn:hover {
            background-color: #ffffff;
            border-color: #ffffff;
            color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.3);
        }

        /* Features Section */
        .features-section {
            padding: 80px 0;
            background: linear-gradient(to bottom, var(--light-gray), #fff);
        }

        .section-title {
            margin-bottom: 60px;
        }

        .section-title h2 {
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .section-title p {
            color: var(--medium-gray);
            font-size: 1.1rem;
        }

        .feature-card {
            border: 1px solid var(--border-gray);
            border-radius: 12px;
            background: #fff;
            padding: 2.5rem 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            border-color: var(--primary-dark);
        }

        .feature-card i {
            color: var(--primary-dark);
            font-size: 3.5rem;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .feature-card:hover i {
            transform: scale(1.1);
        }

        .feature-card h5 {
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 15px;
        }

        .feature-card p {
            color: var(--medium-gray);
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Footer */
        footer {
            background: var(--primary-dark);
            color: var(--light-gray);
            text-align: center;
            padding: 30px 0;
            margin-top: 50px;
        }

        footer p {
            margin: 0;
            font-size: 1rem;
        }

        footer a {
            color: var(--light-gray);
            text-decoration: none;
            transition: color 0.3s;
        }

        footer a:hover {
            color: #ffffff;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 12px;
            border: 1px solid var(--border-gray);
        }

        .modal-header {
            background-color: var(--primary-dark);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .btn-warning {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
            font-weight: 600;
        }

        .btn-warning:hover {
            background-color: var(--secondary-dark);
            border-color: var(--secondary-dark);
            color: white;
        }

        .btn-outline-warning {
            border-color: var(--primary-dark);
            color: var(--primary-dark);
            font-weight: 600;
        }

        .btn-outline-warning:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
        }

        .btn-dark {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-dark:hover {
            background-color: var(--secondary-dark);
            border-color: var(--secondary-dark);
        }

        .text-success {
            color: var(--primary-dark) !important;
        }

        .text-cream {
            color: var(--light-gray);
        }

        /* Responsive */
        @media (max-width: 991px) {
            .navbar-brand {
                margin-left: 0;
            }

            .navbar-brand img {
                height: 180px;
                top: -50px;
            }

            .nav-link {
                color: var(--primary-dark) !important;
                padding: 10px 0;
            }

            .nav-link:not(.dropdown-toggle)::after {
                background-color: var(--primary-dark);
            }
            
            .nav-link.dropdown-toggle::before {
                background-color: var(--primary-dark);
            }
            
            .nav-link.dropdown-toggle:hover::before {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .feature-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <!-- Navigation (Replace the existing nav section in index.php) -->
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
            <h1>
                MADE <span>with CARE</span> <span>FOR PEOPLE</span>
            </h1>
            <p>Turning your moments into unforgable experiences with our catering expertise.</p>
            <a href="manage_booking.php" class="btn">Book Now</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-title text-center">
                <h2>Why Choose Ellen's Catering</h2>
                <p>Professional, Affordable, and Reliable Event Management</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="bi bi-heart-fill"></i>
                        <h5>Personalized Events</h5>
                        <p>We tailor each event to match your unique vision and style, ensuring your celebration is exactly as you imagined.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="bi bi-people-fill"></i>
                        <h5>Expert Team</h5>
                        <p>Our experienced team ensures every detail is handled perfectly, from setup to cleanup.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="bi bi-cash-stack"></i>
                        <h5>Affordable Packages</h5>
                        <p>Choose from flexible packages that suit your budget without compromising on quality.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="bi bi-calendar-check"></i>
                        <h5>Easy Booking</h5>
                        <p>Book online anytime and track your reservation status in real-time through our platform.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="bi bi-star-fill"></i>
                        <h5>Quality Food</h5>
                        <p>Fresh ingredients and delicious recipes prepared by experienced chefs for every occasion.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="bi bi-shield-check"></i>
                        <h5>Secure Payments</h5>
                        <p>Safe and secure online payment options with transparent pricing and instant confirmation.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 Ellen's Catering. All rights reserved.</p>
            <p class="mt-2">
                <a href="privacy.php" class="text-cream me-3">Privacy Policy</a>
                <a href="terms.php" class="text-cream">Terms of Service</a>
            </p>
        </div>
    </footer>

    <!-- Idle Modal (for non-logged-in users) -->
    <?php if (!$isLoggedIn): ?>
    <div class="modal fade" id="idleModal" tabindex="-1" aria-labelledby="idleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4">
                <h5 class="modal-title mb-3" id="idleModalLabel">Do you have an account?</h5>
                <p class="text-muted mb-4">Sign up to enjoy exclusive benefits and track your bookings!</p>
                <div class="d-flex justify-content-center gap-3">
                    <button class="btn btn-dark px-4" id="loginBtn">Login</button>
                    <button class="btn btn-outline-dark px-4" id="signupBtn">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="formModal" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="formFrame" src="" width="100%" height="500px" style="border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Welcome Modal -->
    <?= $welcomeModal; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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

        <?php if (!$isLoggedIn): ?>
        // Idle detection for guest users
        const isLoggedIn = false;
        const idleTimeLimit = 15000; // 15 seconds
        let idleTimer;
        let hasInteracted = false;
        let modalShown = false;

        function showIdleModal() {
            if (!hasInteracted && !modalShown) {
                modalShown = true;
                const idleModal = new bootstrap.Modal(document.getElementById('idleModal'));
                idleModal.show();
            }
        }

        function resetIdleTimer() {
            clearTimeout(idleTimer);
            hasInteracted = true;
            if (!modalShown) {
                idleTimer = setTimeout(showIdleModal, idleTimeLimit);
            }
        }

        // Track user interaction
        ['click', 'scroll', 'keypress', 'mousemove'].forEach(event => {
            document.addEventListener(event, resetIdleTimer, { once: false });
        });

        // Start idle timer on page load
        window.onload = () => {
            idleTimer = setTimeout(showIdleModal, idleTimeLimit);
        };

        // Modal button handlers
        document.addEventListener("DOMContentLoaded", () => {
            const loginBtn = document.getElementById("loginBtn");
            const signupBtn = document.getElementById("signupBtn");
            const idleModalEl = document.getElementById("idleModal");
            const formFrame = document.getElementById("formFrame");
            const formModal = new bootstrap.Modal(document.getElementById("formModal"));
            const idleModal = bootstrap.Modal.getOrCreateInstance(idleModalEl);

            loginBtn.addEventListener("click", () => {
                idleModal.hide();
                formFrame.src = "login_dashboard.php";
                formModal.show();
            });

            signupBtn.addEventListener("click", () => {
                idleModal.hide();
                formFrame.src = "signup_dashboard.php";
                formModal.show();
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>