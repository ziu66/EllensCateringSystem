<?php
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'email_functions.php';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$message = '';
$messageType = '';
$step = isset($_POST['step']) ? $_POST['step'] : 1;
$conn = getDB();

// Step 1: Initial Registration Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    if (!checkRateLimit('signup_request', 3, 600)) {
        $message = 'Too many registration attempts. Please try again in 10 minutes.';
        $messageType = 'danger';
    } else {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $contact = sanitizeInput($_POST['contact']);
        $address = sanitizeInput($_POST['address']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        // Validation
        if (empty($name) || empty($email) || empty($password) || empty($contact) || empty($address)) {
            $message = 'All fields are required.';
            $messageType = 'danger';
        } elseif (!validateEmail($email)) {
            $message = 'Invalid email format.';
            $messageType = 'danger';
        } elseif ($password !== $confirmPassword) {
            $message = 'Passwords do not match.';
            $messageType = 'danger';
        } elseif (!validatePassword($password)) {
            $message = 'Password must be at least 8 characters with uppercase, lowercase, and numbers.';
            $messageType = 'danger';
        } else {
            // Check if email already exists
            $check = $conn->prepare("SELECT Email FROM client WHERE Email=? LIMIT 1");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $message = 'Email already registered! Please login instead.';
                $messageType = 'danger';
            } else {
                // Generate and send verification code
                $verificationCode = generateVerificationCode();
                
                if (storeVerificationCode($email, $verificationCode)) {
                    // Try to send email
                    if (sendVerificationEmail($email, $name, $verificationCode)){
                        // Store temporary registration data in session
                        $_SESSION['temp_registration'] = [
                            'name' => $name,
                            'email' => $email,
                            'contact' => $contact,
                            'address' => $address,
                            'password' => $password
                        ];
                        
                        $step = 2;
                        $message = 'Verification code sent to your email! Please check your inbox.';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to send verification email. Please try again.';
                        $messageType = 'danger';
                    }
                } else {
                    $message = 'Error generating verification code. Please try again.';
                    $messageType = 'danger';
                }
            }
            $check->close();
        }
    }
}

// Step 2: Verify Code and Complete Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    $verificationCode = isset($_POST['verification_code']) ? sanitizeInput($_POST['verification_code']) : '';    
    if (empty($verificationCode)) {
        $message = 'Please enter the verification code.';
        $messageType = 'danger';
        $step = 2;
    } elseif (!isset($_SESSION['temp_registration'])) {
        $message = 'Session expired. Please start registration again.';
        $messageType = 'danger';
        $step = 1;
    } else {
        $tempData = $_SESSION['temp_registration'];
        
        if (verifyCode($tempData['email'], $verificationCode)) {
            // Code is valid, create account
            $hashedPassword = hashPassword($tempData['password']);
            
            $stmt = $conn->prepare("INSERT INTO client (Name, Email, Password, ContactNumber, Address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", 
                $tempData['name'], 
                $tempData['email'], 
                $hashedPassword, 
                $tempData['contact'], 
                $tempData['address']
            );
            
            if ($stmt->execute()) {
                $clientID = $conn->insert_id;
                logActivity($clientID, 'client', 'registration', 'New client registered with email verification');
                
                // Clear temporary data
                unset($_SESSION['temp_registration']);
                
                $message = 'Registration successful! Redirecting to login...';
                $messageType = 'success';
                $step = 3; // Success step
            } else {
                $message = 'Error creating account. Please try again.';
                $messageType = 'danger';
                $step = 2;
            }
            $stmt->close();
        } else {
            $message = 'Invalid or expired verification code. Please try again.';
            $messageType = 'danger';
            $step = 2;
        }
    }
}

// Resend verification code
if (isset($_POST['resend_code']) && isset($_SESSION['temp_registration'])) {
    $tempData = $_SESSION['temp_registration'];
    $verificationCode = generateVerificationCode();
    
    if (storeVerificationCode($tempData['email'], $verificationCode)) {
        if (sendVerificationEmail($tempData['email'], $tempData['name'], $verificationCode)) {
            $message = 'New verification code sent to your email!';
            $messageType = 'success';
        } else {
            $message = 'Failed to send verification code. Please try again.';
            $messageType = 'danger';
        }
    }
    $step = 2;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Ellen's Catering</title>
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
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .register-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        padding: 40px;
        width: 100%;
        max-width: 550px;
        animation: slideUp 0.5s ease;
        border: 1px solid var(--border-gray);
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .register-card h3 {
        text-align: center;
        color: var(--primary-dark);
        font-weight: 700;
        margin-bottom: 10px;
    }

    .register-card .subtitle {
        text-align: center;
        color: var(--medium-gray);
        margin-bottom: 30px;
        font-size: 0.95rem;
    }

    .step-indicator {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 30px;
        gap: 15px;
    }

    .step {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--border-gray);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: var(--medium-gray);
        transition: all 0.3s;
    }

    .step.active {
        background: var(--primary-dark);
        color: white;
        transform: scale(1.1);
    }

    .step.completed {
        background: #28a745;
        color: white;
    }

    .step-line {
        width: 50px;
        height: 3px;
        background: var(--border-gray);
    }

    .step-line.active {
        background: var(--primary-dark);
    }

    .form-label {
        font-weight: 600;
        color: var(--primary-dark);
        margin-bottom: 8px;
    }

    .form-control, .form-select, textarea {
        border-radius: 10px;
        padding: 12px 15px;
        border: 2px solid var(--border-gray);
        transition: all 0.3s;
    }

    .form-control:focus, .form-select:focus, textarea:focus {
        border-color: var(--primary-dark);
        box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.1);
    }

    .input-group-text {
        background: var(--primary-dark);
        border: 2px solid var(--primary-dark);
        color: white;
        font-weight: bold;
        border-radius: 10px 0 0 10px;
    }

    .verification-input {
        font-size: 24px;
        letter-spacing: 10px;
        text-align: center;
        font-weight: 700;
    }

    .btn-primary {
        background: var(--primary-dark);
        border: 2px solid var(--primary-dark);
        color: white;
        font-weight: 600;
        padding: 12px;
        border-radius: 50px;
        transition: all 0.3s ease;
        width: 100%;
    }

    .btn-primary:hover {
        background: white;
        color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    .btn-secondary {
        background: var(--medium-gray);
        border: 2px solid var(--medium-gray);
        color: white;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 50px;
        transition: all 0.3s;
    }

    .btn-secondary:hover {
        background: var(--accent-gray);
        border-color: var(--accent-gray);
    }

    .btn-outline-secondary {
        border: 2px solid var(--border-gray);
        color: var(--text-dark);
        border-radius: 0 10px 10px 0;
    }

    .btn-outline-secondary:hover {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
        color: white;
    }

    .login-link {
        text-align: center;
        margin-top: 20px;
        font-size: 0.95rem;
    }

    .login-link a {
        text-decoration: none;
        color: var(--primary-dark);
        font-weight: 600;
        transition: color 0.3s;
    }

    .login-link a:hover {
        color: var(--medium-gray);
    }

    .password-strength {
        height: 5px;
        border-radius: 3px;
        margin-top: 5px;
        transition: all 0.3s;
    }

    .strength-weak { background: #dc3545; width: 33%; }
    .strength-medium { background: #ffc107; width: 66%; }
    .strength-strong { background: #28a745; width: 100%; }

    .alert {
        border-radius: 15px;
        margin-bottom: 20px;
        border: none;
        padding: 15px 20px;
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

    .success-animation {
        text-align: center;
        padding: 40px 20px;
    }

    .success-animation i {
        font-size: 80px;
        color: #28a745;
        animation: scaleIn 0.5s ease;
    }

    @keyframes scaleIn {
        from { transform: scale(0); }
        to { transform: scale(1); }
    }

    .timer-box {
        background: var(--light-gray);
        padding: 15px;
        border-radius: 10px;
        text-align: center;
        margin: 20px 0;
        border: 2px solid var(--border-gray);
    }

    .timer {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary-dark);
    }

    textarea.form-control {
        resize: vertical;
    }

    @media (max-width: 576px) {
        .register-card {
            padding: 30px 20px;
        }
        
        .register-card h3 {
            font-size: 1.5rem;
        }
    }
</style>
</head>
<body>
    <div class="register-card">
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">1</div>
            <div class="step-line <?= $step >= 2 ? 'active' : '' ?>"></div>
            <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">2</div>
            <div class="step-line <?= $step >= 3 ? 'active' : '' ?>"></div>
            <div class="step <?= $step == 3 ? 'active' : '' ?>">3</div>
        </div>

        <?php if ($step == 1): ?>
            <!-- Step 1: Registration Form -->
            <h3><i class="bi bi-person-plus-fill me-2"></i>Create Account</h3>
            <p class="subtitle">Join Ellen's Catering and start booking today</p>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="signupForm">
                <input type="hidden" name="step" value="1">
                
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="name" class="form-control" placeholder="Juan Dela Cruz" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="juan@example.com" required>
                    </div>
                    <small class="text-muted">Verification code will be sent to this email</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Contact Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="tel" name="contact" class="form-control" placeholder="09XX XXX XXXX" pattern="[0-9]{11}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                        <textarea name="address" class="form-control" rows="2" placeholder="Street, Barangay, City, Province" required></textarea>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Min. 8 characters" required minlength="8">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="strengthBar"></div>
                    <small class="text-muted">At least 8 characters with uppercase, lowercase, and numbers</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="Re-enter password" required>
                    </div>
                    <small class="text-danger" id="passwordMismatch" style="display: none;">Passwords do not match</small>
                </div>

                <button type="submit" class="btn btn-primary mt-3">
                    <i class="bi bi-envelope-check me-2"></i>Send Verification Code
                </button>

                <p class="login-link">Already have an account? <a href="login_dashboard.php">Login here</a></p>
            </form>

        <?php elseif ($step == 2): ?>
            <!-- Step 2: Email Verification -->
            <h3><i class="bi bi-shield-check me-2"></i>Verify Your Email</h3>
            <p class="subtitle">Enter the 6-digit code sent to <?= isset($_SESSION['temp_registration']) ? htmlspecialchars($_SESSION['temp_registration']['email']) : '' ?></p>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="timer-box">
                <div class="mb-2"><i class="bi bi-clock me-2"></i>Code expires in:</div>
                <div class="timer" id="timer">15:00</div>
            </div>

            <form method="POST" id="verificationForm">
                <input type="hidden" name="step" value="2">
                
                <div class="mb-4">
                    <label class="form-label text-center d-block">Verification Code</label>
                    <input type="text" name="verification_code" class="form-control verification-input" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required autofocus>
                    <small class="text-muted d-block text-center mt-2">Check your email inbox and spam folder</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Verify & Complete Registration
                </button>
            </form>

            <form method="POST" class="mt-3">
                <input type="hidden" name="resend_code" value="1">
                <input type="hidden" name="step" value="2">
                <button type="submit" class="btn btn-secondary w-100" id="resendBtn">
                    <i class="bi bi-arrow-clockwise me-2"></i>Resend Code
                </button>
            </form>

            <p class="login-link">Want to use a different email? <a href="signup_dashboard.php">Start Over</a></p>

        <?php elseif ($step == 3): ?>
            <!-- Step 3: Success -->
            <div class="success-animation">
                <i class="bi bi-check-circle-fill"></i>
                <h3 class="mt-3 mb-2" style="color: var(--olive);">Registration Successful!</h3>
                <p class="text-muted">Your account has been created successfully.</p>
                <p class="text-muted mb-4">Redirecting to login page...</p>
                <a href="login_dashboard.php" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const passwordMismatch = document.getElementById('passwordMismatch');

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 8) strength++;
                if (password.match(/[a-z]/)) strength++;
                if (password.match(/[A-Z]/)) strength++;
                if (password.match(/[0-9]/)) strength++;
                if (password.match(/[^a-zA-Z0-9]/)) strength++;

                strengthBar.className = 'password-strength';
                if (strength <= 2) {
                    strengthBar.classList.add('strength-weak');
                } else if (strength <= 3) {
                    strengthBar.classList.add('strength-medium');
                } else {
                    strengthBar.classList.add('strength-strong');
                }
            });
        }

        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    passwordMismatch.style.display = 'block';
                } else {
                    passwordMismatch.style.display = 'none';
                }
            });
        }

        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                if (confirmPasswordInput) confirmPasswordInput.type = type;
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
        }

        // Timer for verification code
        const timerElement = document.getElementById('timer');
        if (timerElement) {
            let timeLeft = 15 * 60; // 15 minutes in seconds
            
            const countdown = setInterval(function() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    timerElement.textContent = 'EXPIRED';
                    timerElement.style.color = '#dc3545';
                    document.getElementById('verificationForm').innerHTML = 
                        '<div class="alert alert-danger text-center">Verification code expired. Please request a new code.</div>';
                } else if (timeLeft <= 60) {
                    timerElement.style.color = '#dc3545';
                }
                
                timeLeft--;
            }, 1000);
        }

        // Auto-format verification code input
        const verificationInput = document.querySelector('.verification-input');
        if (verificationInput) {
            verificationInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // Auto-submit when 6 digits entered
            verificationInput.addEventListener('input', function() {
                if (this.value.length === 6) {
                    // Optional: auto-submit
                    // document.getElementById('verificationForm').submit();
                }
            });
        }

        // Prevent resend spam
        const resendBtn = document.getElementById('resendBtn');
        if (resendBtn) {
            let resendCooldown = 60;
            
            resendBtn.addEventListener('click', function(e) {
                if (resendCooldown > 0 && resendCooldown < 60) {
                    e.preventDefault();
                    return;
                }
                
                this.disabled = true;
                resendCooldown = 60;
                
                const originalText = this.innerHTML;
                const cooldownTimer = setInterval(() => {
                    this.innerHTML = `<i class="bi bi-hourglass-split me-2"></i>Wait ${resendCooldown}s`;
                    resendCooldown--;
                    
                    if (resendCooldown < 0) {
                        clearInterval(cooldownTimer);
                        this.disabled = false;
                        this.innerHTML = originalText;
                    }
                }, 1000);
            });
        }

        // Auto-redirect on success
        <?php if ($step == 3): ?>
        setTimeout(function() {
            window.location.href = 'login_dashboard.php';
        }, 3000);
        <?php endif; ?>

        // Form validation
        const signupForm = document.getElementById('signupForm');
        if (signupForm) {
            signupForm.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPass = confirmPasswordInput.value;
                
                if (password !== confirmPass) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                if (password.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long!');
                    return false;
                }
            });
        }
    </script>
</body>
</html>