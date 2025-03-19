<?php
// pending_orders_check.php
// Script to check for orders that have been in pending status for more than 24 hours
// and send email notifications to the admin using PHPMailer with Gmail SMTP

// Include database connection
require_once 'db.php';

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once 'PHPMailer.php';
require_once 'Exception.php';
require_once 'SMTP.php';

// Admin email address - Update this to your admin email
$adminEmail = "admin@yardsofgrace.com";

// Get pending orders older than 24 hours
function getPendingOrders($conn) {
    $sql = "SELECT o.id, o.user_id, o.total_amount, o.created_at, 
                 u.username, u.email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.order_status = 'pending' 
            AND o.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY o.created_at ASC";
    
    $result = $conn->query($sql);
    
    $pendingOrders = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pendingOrders[] = $row;
        }
    }
    
    return $pendingOrders;
}

// Send email notification using PHPMailer with Gmail
function sendPendingOrdersEmail($adminEmail, $pendingOrders) {
    if (empty($pendingOrders)) {
        return false; // No pending orders to notify about
    }
    
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'padanamakkalonix@gmail.com';
        $mail->Password = 'shyl ywsq bwel vnvk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        
        // Sender and recipient
        $mail->setFrom('padanamakkalonix@gmail.com', 'Yards of Grace Notification');
        $mail->addAddress($adminEmail);
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = "ALERT: Pending Orders Requiring Attention";
        
        // Build email body
        $message = "<html><body>";
        $message .= "<h2>Pending Orders Alert</h2>";
        $message .= "<p>The following orders have been in <strong>pending status</strong> for more than 24 hours and require your attention:</p>";
        $message .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        $message .= "<tr style='background-color: #f2f2f2;'><th>Order ID</th><th>Customer</th><th>Amount</th><th>Created Date</th><th>Action</th></tr>";
        
        foreach ($pendingOrders as $order) {
            $orderId = str_pad($order['id'], 3, '0', STR_PAD_LEFT);
            $orderDate = date('Y-m-d H:i', strtotime($order['created_at']));
            $orderAmount = number_format($order['total_amount'], 2);
            $orderLink = "https://yardsofgrace.com/order_manage.php?view_order=" . $order['id'];
            
            $message .= "<tr>";
            $message .= "<td>#ORD{$orderId}</td>";
            $message .= "<td>{$order['username']}</td>";
            $message .= "<td>₹{$orderAmount}</td>";
            $message .= "<td>{$orderDate}</td>";
            $message .= "<td><a href='{$orderLink}' style='color: #6f42c1;'>View Order</a></td>";
            $message .= "</tr>";
        }
        
        $message .= "</table>";
        $message .= "<p>Please log in to the admin dashboard to process these orders.</p>";
        $message .= "<p><a href='https://yardsofgrace.com/order_manage.php' style='display: inline-block; padding: 10px 15px; background-color: #6f42c1; color: white; text-decoration: none; border-radius: 4px;'>Go to Order Management</a></p>";
        $message .= "<p>Thank you,<br>Yards of Grace Notification System</p>";
        $message .= "</body></html>";
        
        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(["<br>", "</p>"], ["\n", "\n\n"], $message));
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        logActivity("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Log function to keep track of script execution
function logActivity($message) {
    $logFile = __DIR__ . '/logs/pending_orders_check.log';
    $directory = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Main execution
try {
    logActivity("Script started");
    
    // Get pending orders
    $pendingOrders = getPendingOrders($conn);
    $orderCount = count($pendingOrders);
    
    logActivity("Found {$orderCount} pending orders older than 24 hours");
    
    if ($orderCount > 0) {
        // Send email notification
        $emailSent = sendPendingOrdersEmail($adminEmail, $pendingOrders);
        
        if ($emailSent) {
            logActivity("Email notification sent successfully to {$adminEmail}");
        } else {
            logActivity("Failed to send email notification");
        }
    } else {
        logActivity("No pending orders found that need attention");
    }
    
    logActivity("Script completed successfully");
} catch (Exception $e) {
    logActivity("Error: " . $e->getMessage());
}

// Close database connection
$conn->close();
?>