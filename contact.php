<?php
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $subject = sanitizeInput($_POST['subject']);
    $messageText = sanitizeInput($_POST['message']);

    if (empty($name) || empty($email) || empty($subject) || empty($messageText)) {
        $message = 'All fields are required.';
        $messageType = 'danger';
    } elseif (!validateEmail($email)) {
        $message = 'Invalid email address.';
        $messageType = 'danger';
    } else {
        // Here you would typically send an email or save to database
        // For now, we'll just show a success message
        $message = 'Thank you for contacting us! We will get back to you soon.';
        $messageType = 'success';
        
        // Log the contact attempt
        if (isLoggedIn()) {
            $userID = isClient() ? getClientID() : getAdminID();
            $userType = isClient() ? 'client' : 'admin';
            logActivity($userID, $userType, 'contact_form', "Submitted contact form: $subject");
        }
    }
}

$isLoggedIn = isLoggedIn();
$clientName = getUserName();

// Initialize cart count
if (!isset($_SESSION['food_cart'])) {
    $_SESSION['food_cart'] = [];
}
$cartCount = isset($_SESSION['food_cart']) ? count($_SESSION['food_cart']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Ellen's Catering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

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

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, var(--light-gray) 0%, #ffffff 100%);
            min-height: 100vh;
        }

        /* Navbar styles */
        .navbar {
            background: rgba(255, 255, 255, 0.98) !important;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            z-index: 1000;
            top: 0;
            padding: 1.2rem 0;
            transition: all 0.3s ease;
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
            transition: all 0.3s ease;
        }

        .nav-link {
            color: var(--primary-dark) !important;
            font-weight: 500;
            margin: 0 14px;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--medium-gray) !important;
        }

        .dropdown-menu {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border: 1px solid var(--border-gray);
        }

        .dropdown-item {
            padding: 10px 20px;
            transition: all 0.3s;
        }

        .dropdown-item:hover {
            background-color: var(--light-gray);
            color: var(--primary-dark);
        }

        .dropdown-divider {
            margin: 0.5rem 0;
        }

        .dropdown-item i {
            width: 20px;
        }

        .dropdown-item.text-danger:hover {
            background-color: #f8d7da;
            color: #dc3545 !important;
        }

        /* Contact Section */
        .contact-section {
            padding: 150px 0 50px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .page-header h1 {
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 2.8rem;
            margin-bottom: 15px;
        }

        .page-header p {
            color: var(--medium-gray);
            font-size: 1.2rem;
        }

        .contact-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 30px;
            border: 1px solid var(--border-gray);
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-box {
            text-align: center;
            padding: 25px 15px;
            background: var(--light-gray);
            border-radius: 15px;
            transition: all 0.3s;
            border: 2px solid var(--border-gray);
        }

        .info-box:hover {
            background: white;
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border-color: var(--primary-dark);
        }

        .info-box i {
            font-size: 2rem;
            color: var(--primary-dark);
            margin-bottom: 12px;
        }

        .info-box h5 {
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .info-box p {
            color: var(--medium-gray);
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 15px;
            overflow: hidden;
            border: 2px solid var(--border-gray);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 8px;
        }

        .form-control, textarea {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid var(--border-gray);
            transition: all 0.3s;
        }

        .form-control:focus, textarea:focus {
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.1);
        }

        .btn-submit {
            background: var(--primary-dark);
            border: none;
            color: white;
            font-weight: 600;
            padding: 14px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            background: var(--secondary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .social-links h5 {
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 20px;
        }

        .btn-social {
            background: white;
            border: 2px solid var(--primary-dark);
            color: var(--primary-dark);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            transition: all 0.3s;
        }

        .btn-social:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .alert {
            border-radius: 15px;
            border: none;
            padding: 20px;
            margin-bottom: 30px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        footer {
            background: var(--primary-dark);
            color: white;
            text-align: center;
            padding: 30px 0;
            margin-top: 50px;
        }

        footer a {
            color: var(--light-gray);
            text-decoration: none;
            transition: all 0.3s;
        }

        footer a:hover {
            color: white;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }

            .contact-card {
                padding: 25px;
            }

            .info-box {
                padding: 20px 15px;
            }

            .contact-info {
                grid-template-columns: 1fr;
            }

            .navbar-brand {
                margin-left: 0;
            }

            .navbar-brand img {
                height: 100px;
                width: 100px;
                top: -5px;
            }

            .map-container {
                height: 300px;
            }
        }

        /* Cart Badge */
        .nav-link .badge {
            position: absolute;
            top: -5px;
            right: -10px;
            font-size: 0.7rem;
            padding: 3px 6px;
        }

        .nav-link .badge:not([style*="display: none"]) {
            animation: pulse 1s ease-in-out;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
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
                    <a href="food_menu.php" class="nav-link">Food Menu</a>
                </li>

                <li class="nav-item"><a href="manage_booking.php" class="nav-link">Book Now</a></li>
                <li class="nav-item"><a href="contact.php" class="nav-link active">Contact</a></li>

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

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="page-header">
                <h1><i class="bi bi-telephone me-3"></i>Get In Touch</h1>
                <p>We'd love to hear from you! Send us a message and we'll respond as soon as possible.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-5 mb-4">
                    <div class="contact-card">
                        <h3 class="mb-4" style="color: var(--primary-dark); font-weight: 700;">Contact Information</h3>
                        
                        <div class="contact-info">
                            <div class="info-box">
                                <i class="bi bi-geo-alt-fill"></i>
                                <h5>Address</h5>
                                <p>Lian, Batangas<br>Philippines 4216</p>
                            </div>

                            <div class="info-box">
                                <i class="bi bi-telephone-fill"></i>
                                <h5>Phone</h5>
                                <p>+63 916 789 8776<br>+63 916 707 4350</p>
                            </div>

                            <div class="info-box">
                                <i class="bi bi-envelope-fill"></i>
                                <h5>Email</h5>
                                <p>info@ellenscatering.com<br>bookings@ellenscatering.com</p>
                            </div>

                            <div class="info-box">
                                <i class="bi bi-clock-fill"></i>
                                <h5>Business Hours</h5>
                                <p>Mon - Sat: 8:00 AM - 6:00 PM<br>Sunday: By Appointment</p>
                            </div>
                        </div>

                        <div class="map-container mb-4">
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!4v1731636000000!6m8!1m7!1sG2u4Hdouzilqcl8lwVY1GA!2m2!1d14.03919520000001!2d120.6556328!3f15.75!4f-29.19!5f0.7820865974627469" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>

                        <div class="social-links text-center">
                            <h5>Follow Us</h5>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="https://www.facebook.com/profile.php?id=100063713990943" target="_blank" class="btn-social"><i class="bi bi-facebook"></i></a>
                                <a href="#" class="btn-social"><i class="bi bi-instagram"></i></a>
                                <a href="#" class="btn-social"><i class="bi bi-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="contact-card">
                        <h3 class="mb-4" style="color: var(--primary-dark); font-weight: 700;">Send Us a Message</h3>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Your Name *</label>
                                    <input type="text" name="name" class="form-control" placeholder="Juan Dela Cruz" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="email" class="form-control" placeholder="juan@example.com" required>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Subject *</label>
                                    <input type="text" name="subject" class="form-control" placeholder="Inquiry about catering services" required>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Message *</label>
                                    <textarea name="message" class="form-control" rows="6" placeholder="Tell us more about your event or inquiry..." required></textarea>
                                </div>

                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-submit">
                                        <i class="bi bi-send me-2"></i>Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
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
                <a href="privacy.php" class="me-3">Privacy Policy</a>
                <a href="terms.php">Terms of Service</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Update cart count function
    function updateCartCount() {
        <?php if ($isLoggedIn): ?>
            fetch('cart_handler.php?action=count')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.count > 0) {
                        const badge = document.getElementById('cartBadge');
                        if (badge) {
                            badge.textContent = data.count;
                            badge.style.display = 'inline-block';
                        }
                    }
                })
                .catch(error => console.error('Error updating cart count:', error));
        <?php endif; ?>
    }

    // Update cart count on page load
    <?php if ($isLoggedIn): ?>
        document.addEventListener('DOMContentLoaded', updateCartCount);
    <?php endif; ?>
</script>

</body>
</html>