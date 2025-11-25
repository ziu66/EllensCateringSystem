<?php
// email_functions.php
// Email verification and notification functions

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Generate verification code
function generateVerificationCode($length = 6) {
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// Store verification code in database
function storeVerificationCode($email, $code) {
    $conn = getDB();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Delete old codes for this email
    $deleteStmt = $conn->prepare("DELETE FROM email_verification WHERE Email = ?");
    $deleteStmt->bind_param("s", $email);
    $deleteStmt->execute();
    $deleteStmt->close();
    
    // Insert new code
    $stmt = $conn->prepare("INSERT INTO email_verification (Email, Code, ExpiresAt, IsUsed) VALUES (?, ?, ?, 0)");
    $stmt->bind_param("sss", $email, $code, $expiresAt);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Verify code
function verifyCode($email, $code) {
    $conn = getDB();
    $now = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("SELECT VerificationID FROM email_verification WHERE Email = ? AND Code = ? AND ExpiresAt > ? AND IsUsed = 0 LIMIT 1");
    $stmt->bind_param("sss", $email, $code, $now);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $verificationID = $row['VerificationID'];
        
        // Mark as used
        $updateStmt = $conn->prepare("UPDATE email_verification SET IsUsed = 1 WHERE VerificationID = ?");
        $updateStmt->bind_param("i", $verificationID);
        $updateStmt->execute();
        $updateStmt->close();
        
        $stmt->close();
        return true;
    }
    
    $stmt->close();
    return false;
}

// Send verification email using PHPMailer (MAIN FUNCTION - USE THIS)
function sendVerificationEmail($email, $name, $code) {
    $mail = new PHPMailer(true);
    
    try {
        // Enable verbose debug output (comment out in production)
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = 'html';
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dump083004@gmail.com'; // Your Gmail
        $mail->Password   = 'mmai zghe emze hhan'; // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('dump083004@gmail.com', "Ellen's Catering");
        $mail->addAddress($email, $name);
        $mail->addReplyTo('dump083004@gmail.com', "Ellen's Catering");
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification - Ellen\'s Catering';
        $mail->Body    = getVerificationEmailTemplate($name, $code);
        $mail->AltBody = "Hello $name,\n\nYour verification code is: $code\n\nThis code will expire in 15 minutes.\n\nBest regards,\nEllen's Catering Team";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Email template for verification
function getVerificationEmailTemplate($name, $code) {
    return <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Arial', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; margin-top: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .logo { color: #536001; font-size: 28px; font-weight: bold; }
            .code-box { background: linear-gradient(135deg, #ffb337, #ffa500); color: white; padding: 20px; border-radius: 10px; text-align: center; margin: 30px 0; }
            .code { font-size: 36px; font-weight: bold; letter-spacing: 8px; }
            .footer { text-align: center; color: #666; font-size: 14px; margin-top: 30px; }
            .info { background: #fff8e7; padding: 15px; border-radius: 8px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">🍽️ Ellen's Catering</div>
                <p style="color: #666;">Email Verification</p>
            </div>
            
            <p>Hello <strong>$name</strong>,</p>
            
            <p>Thank you for signing up with Ellen's Catering! To complete your registration, please use the verification code below:</p>
            
            <div class="code-box">
                <div style="font-size: 14px; margin-bottom: 10px;">Your Verification Code</div>
                <div class="code">$code</div>
            </div>
            
            <div class="info">
                <p style="margin: 5px 0;"><strong>⏰ Important:</strong> This code will expire in <strong>15 minutes</strong>.</p>
                <p style="margin: 5px 0;">If you didn't request this verification, please ignore this email.</p>
            </div>
            
            <p>Once verified, you'll be able to:</p>
            <ul>
                <li>Book catering services for your events</li>
                <li>Browse our exclusive packages</li>
                <li>Track your bookings and payments</li>
                <li>Customize your menu selections</li>
            </ul>
            
            <div class="footer">
                <p>Best regards,<br><strong>Ellen's Catering Team</strong></p>
                <p style="font-size: 12px; color: #999;">Lian, Batangas, Philippines 4216<br>+63 916 789 8776 | info@ellenscatering.com</p>
            </div>
        </div>
    </body>
    </html>
    HTML;
}

// THIS IS THE SIMPLE VERSION - NOT USED ANYMORE
// Keeping it here just for reference, but we won't call it
function sendVerificationEmailSimple($email, $name, $code) {
    // DON'T USE THIS - Use sendVerificationEmail() instead
    // This function uses mail() which requires local mail server
    return sendVerificationEmail($email, $name, $code);
}

// Clean up expired codes (run this periodically)
function cleanupExpiredCodes() {
    $conn = getDB();
    $now = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("DELETE FROM email_verification WHERE ExpiresAt < ? OR (IsUsed = 1 AND ExpiresAt < DATE_SUB(NOW(), INTERVAL 1 DAY))");
    $stmt->bind_param("s", $now);
    $stmt->execute();
    $stmt->close();
}
?>