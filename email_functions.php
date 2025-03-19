<?php
/**
 * Email functions for the application
 * Contains functions to send various types of emails
 */

// Direct inclusion of PHPMailer files
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Debug function for error tracking
function debug_to_file($message) {
    file_put_contents('email_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

/**
 * Send order confirmation email to customer
 * 
 * @param array $orderDetails Order details including items, total, shipping address
 * @param string $email Customer email address
 * @param string $name Customer name
 * @return bool True if email was sent successfully, false otherwise
 */
function sendOrderConfirmationEmail($orderDetails, $email, $name) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'padanamakkalonix@gmail.com';
        $mail->Password   = 'gyof yzjq nlty qcvg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('padanamakkalonix@gmail.com', 'Yards Of Grace');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Order Confirmation - Order #' . $orderDetails['order_id'];
        
        // Build email body
        $body = '<h2>Thank you for your order!</h2>';
        $body .= '<p>Dear ' . htmlspecialchars($name) . ',</p>';
        $body .= '<p>Your order #' . $orderDetails['order_id'] . ' has been received and confirmed.</p>';
        
        $body .= '<h3>Order Details:</h3>';
        $body .= '<table border="0" cellpadding="5" cellspacing="0" width="100%" style="border-collapse: collapse;">';
        $body .= '<tr><th style="text-align: left; border-bottom: 1px solid #ddd;">Item</th><th style="text-align: right; border-bottom: 1px solid #ddd;">Quantity</th><th style="text-align: right; border-bottom: 1px solid #ddd;">Price</th></tr>';
        
        foreach ($orderDetails['items'] as $item) {
            $body .= '<tr>';
            $body .= '<td style="border-bottom: 1px solid #eee;">' . htmlspecialchars($item['name']) . '</td>';
            $body .= '<td style="text-align: right; border-bottom: 1px solid #eee;">' . htmlspecialchars($item['quantity']) . '</td>';
            $body .= '<td style="text-align: right; border-bottom: 1px solid #eee;">₹' . number_format($item['price'], 2) . '</td>';
            $body .= '</tr>';
        }
        
        $body .= '<tr>';
        $body .= '<td colspan="2" style="text-align: right;"><strong>Total:</strong></td>';
        $body .= '<td style="text-align: right;"><strong>₹' . number_format($orderDetails['total'], 2) . '</strong></td>';
        $body .= '</tr>';
        $body .= '</table>';
        
        $body .= '<h3>Shipping Address:</h3>';
        $body .= '<p>' . nl2br(htmlspecialchars($orderDetails['shipping_address'])) . '</p>';
        
        $body .= '<h3>Payment Method:</h3>';
        $body .= '<p>' . htmlspecialchars($orderDetails['payment_method']) . '</p>';
        
        $body .= '<p>If you have any questions about your order, please contact our customer service at <a href="mailto:support@yardsofgrace.com">support@yardsofgrace.com</a>.</p>';
        
        $body .= '<p>Thank you for shopping with us!</p>';
        $body .= '<p>Regards,<br>Yards Of Grace Team</p>';
        
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        
        debug_to_file("Attempting to send order confirmation email to: " . $email);
        $mail->send();
        debug_to_file("Order confirmation email sent successfully");
        return true;
    } catch (Exception $e) {
        debug_to_file("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send password reset email
 * 
 * @param string $email User email address
 * @param string $name User name
 * @param string $resetLink Password reset link
 * @return bool True if email was sent successfully, false otherwise
 */
function sendPasswordResetEmail($email, $name, $resetLink) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'padanamakkalonix@gmail.com';
        $mail->Password   = 'gyof yzjq nlty qcvg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('padanamakkalonix@gmail.com', 'Yards Of Grace');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        
        $body = '<h2>Password Reset Request</h2>';
        $body .= '<p>Dear ' . htmlspecialchars($name) . ',</p>';
        $body .= '<p>You have requested to reset your password. Click the link below to reset your password:</p>';
        $body .= '<p><a href="' . $resetLink . '">Reset Password</a></p>';
        $body .= '<p>This link will expire in 24 hours.</p>';
        $body .= '<p>If you did not request this password reset, please ignore this email or contact our support team.</p>';
        $body .= '<p>Regards,<br>Yards Of Grace Team</p>';
        
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        
        debug_to_file("Attempting to send password reset email to: " . $email);
        $mail->send();
        debug_to_file("Password reset email sent successfully");
        return true;
    } catch (Exception $e) {
        debug_to_file("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send welcome email to new users
 * 
 * @param string $email User email address
 * @param string $name User name
 * @return bool True if email was sent successfully, false otherwise
 */
function sendWelcomeEmail($email, $name) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'padanamakkalonix@gmail.com';
        $mail->Password   = 'gyof yzjq nlty qcvg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('padanamakkalonix@gmail.com', 'Yards Of Grace');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Yards Of Grace!';
        
        $body = '<h2>Welcome to Yards Of Grace!</h2>';
        $body .= '<p>Dear ' . htmlspecialchars($name) . ',</p>';
        $body .= '<p>Thank you for creating an account with us. We are excited to have you as our customer!</p>';
        $body .= '<p>With your account, you can:</p>';
        $body .= '<ul>';
        $body .= '<li>Track your orders</li>';
        $body .= '<li>View your order history</li>';
        $body .= '<li>Update your profile information</li>';
        $body .= '<li>And much more!</li>';
        $body .= '</ul>';
        $body .= '<p>If you have any questions or need assistance, please feel free to contact our customer service at <a href="mailto:support@yardsofgrace.com">support@yardsofgrace.com</a>.</p>';
        $body .= '<p>Happy shopping!</p>';
        $body .= '<p>Regards,<br>Yards Of Grace Team</p>';
        
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        
        debug_to_file("Attempting to send welcome email to: " . $email);
        $mail->send();
        debug_to_file("Welcome email sent successfully");
        return true;
    } catch (Exception $e) {
        debug_to_file("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>