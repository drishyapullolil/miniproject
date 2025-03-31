<?php
// Remove vendor/autoload.php and use direct requires
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';
require_once 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Debug function
function debug_to_file($message) {
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    debug_to_file("Received reset request for email: " . $email);
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        // Change expiry to 24 hours
        $expires = date("Y-m-d H:i:s", time() + (24 * 3600));

        debug_to_file("Generated token: " . $token);
        debug_to_file("Expires at: " . $expires);

        // Clear any existing tokens first
        $clearStmt = $conn->prepare("UPDATE users SET reset_token = NULL, token_expiry = NULL WHERE email = ?");
        $clearStmt->bind_param("s", $email);
        $clearStmt->execute();

        // Update user with new reset token
        $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE email = ?");
        $updateStmt->bind_param("sss", $token, $expires, $email);
        
        if ($updateStmt->execute()) {
            debug_to_file("Token stored in database successfully");
            $reset_link = "http://localhost/YOG/reset_password.php?token=" . $token;
            
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'padanamakkalonix@gmail.com';
                $mail->Password = 'gyof yzjq nlty qcvg';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                $mail->setFrom('padanamakkalonix@gmail.com', 'Yards Of Grace');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>You have requested to reset your password. Click the link below to proceed:</p>
                    <p><a href='$reset_link'>Reset Password</a></p>
                    <p>If you didn't request this, please ignore this email.</p>
                    <p>This link will expire in 24 hours.</p>";

                $mail->send();
                debug_to_file("Reset email sent successfully");
                $message = "Password reset link sent to your email.";
                $messageClass = "success";
            } catch (Exception $e) {
                debug_to_file("Failed to send reset email: " . $mail->ErrorInfo);
                $message = "Email sending failed.";
                $messageClass = "error";
            }
        }
    } else {
        $message = "No account found with that email.";
        $messageClass = "error";
        debug_to_file("No account found for email: " . $email);
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        .forgot-password-section {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f9f9f9;
            padding: 20px;
        }

        .forgot-password-container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .forgot-password-container h2 {
            font-size: 24px;
            color: #8d0f8f;
            margin-bottom: 10px;
        }

        .forgot-password-container p {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .violet-btn {
            background-color: #8d0f8f;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        .violet-btn:hover {
            background-color: #7a0d7c;
        }

        .back-to-login {
            margin-top: 20px;
        }

        .back-to-login a {
            color: #8d0f8f;
            text-decoration: none;
            font-size: 14px;
        }

        .back-to-login a:hover {
            text-decoration: underline;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <section class="forgot-password-section">
        <div class="forgot-password-container">
            <h2>Forgot Password</h2>
            <?php if (isset($message)): ?>
                <div class="message <?php echo $messageClass; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <p>Please enter your email address to reset your password.</p>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>
                <button type="submit" class="violet-btn">Reset Password</button>
            </form>
            <div class="back-to-login">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </section>
</body>
</html>