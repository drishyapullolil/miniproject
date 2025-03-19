<?php
// Create a dedicated test file to isolate PHPMailer issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug function
function debug_to_file($message) {
    file_put_contents('test_email_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

// Log PHP version and loaded extensions
debug_to_file("PHP Version: " . phpversion());
debug_to_file("Loaded Extensions: " . implode(", ", get_loaded_extensions()));

// Check if files exist before requiring them
$phpmailerPath = __DIR__ . '/PHPMailer/src/PHPMailer.php';
$smtpPath = __DIR__ . '/PHPMailer/src/SMTP.php';
$exceptionPath = __DIR__ . '/PHPMailer/src/Exception.php';

debug_to_file("Checking for PHPMailer files:");
debug_to_file("PHPMailer.php exists: " . (file_exists($phpmailerPath) ? "Yes" : "No"));
debug_to_file("SMTP.php exists: " . (file_exists($smtpPath) ? "Yes" : "No"));
debug_to_file("Exception.php exists: " . (file_exists($exceptionPath) ? "Yes" : "No"));

// Try to include the files - first with src directory (GitHub version)
if (file_exists($phpmailerPath) && file_exists($smtpPath) && file_exists($exceptionPath)) {
    debug_to_file("Including PHPMailer files from src directory");
    require_once $phpmailerPath;
    require_once $smtpPath;
    require_once $exceptionPath;
} else {
    // Try without src directory (older or manual installation)
    $altPhpmailerPath = __DIR__ . '/PHPMailer/PHPMailer.php';
    $altSmtpPath = __DIR__ . '/PHPMailer/SMTP.php';
    $altExceptionPath = __DIR__ . '/PHPMailer/Exception.php';
    
    debug_to_file("Checking alternative paths:");
    debug_to_file("Alt PHPMailer.php exists: " . (file_exists($altPhpmailerPath) ? "Yes" : "No"));
    debug_to_file("Alt SMTP.php exists: " . (file_exists($altSmtpPath) ? "Yes" : "No"));
    debug_to_file("Alt Exception.php exists: " . (file_exists($altExceptionPath) ? "Yes" : "No"));
    
    if (file_exists($altPhpmailerPath) && file_exists($altSmtpPath) && file_exists($altExceptionPath)) {
        debug_to_file("Including PHPMailer files from direct PHPMailer directory");
        require_once $altPhpmailerPath;
        require_once $altSmtpPath;
        require_once $altExceptionPath;
    } else {
        debug_to_file("ERROR: PHPMailer files not found!");
        die("PHPMailer files not found. Please check your installation.");
    }
}

// Check if the classes exist
debug_to_file("Checking if PHPMailer classes exist:");
debug_to_file("PHPMailer\PHPMailer\PHPMailer class exists: " . (class_exists('PHPMailer\PHPMailer\PHPMailer') ? "Yes" : "No"));
debug_to_file("PHPMailer\PHPMailer\SMTP class exists: " . (class_exists('PHPMailer\PHPMailer\SMTP') ? "Yes" : "No"));
debug_to_file("PHPMailer\PHPMailer\Exception class exists: " . (class_exists('PHPMailer\PHPMailer\Exception') ? "Yes" : "No"));

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Starting the email test
debug_to_file("Starting email test");

try {
    $mail = new PHPMailer(true);
    
    // Enable verbose debug output
    $mail->SMTPDebug = 3; // Verbose debug output
    $mail->Debugoutput = function($str, $level) {
        debug_to_file("PHPMailer Debug: $str");
    };
    
    // Server settings
    debug_to_file("Configuring SMTP settings");
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'padanamakkalonix@gmail.com';
    $mail->Password   = 'shyl ywsq bwel vnvk';
    
    // Try SMTPS on port 465 first
    debug_to_file("Using ENCRYPTION_SMTPS with port 465");
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    
    // Recipients
    $mail->setFrom('padanamakkalonix@gmail.com', 'Yards Of Grace');
    $mail->addAddress('padanamakkalonix@gmail.com'); // Send to yourself as a test
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test Email';
    $mail->Body    = '<h1>This is a test email</h1><p>This email was sent to verify that PHPMailer is working correctly.</p>';
    $mail->AltBody = 'This is a test email to verify that PHPMailer is working correctly.';
    
    debug_to_file("About to send email");
    $mail->send();
    debug_to_file("Email sent successfully!");
    
    echo '<div style="background-color: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px; border: 1px solid #c3e6cb;">
        <h2>Success!</h2>
        <p>Email sent successfully. Check your inbox for the test email.</p>
        <p>Check the test_email_debug.txt file for detailed debugging information.</p>
    </div>';
    
} catch (Exception $e) {
    debug_to_file("FAILED: Email could not be sent");
    debug_to_file("Mailer Error: " . $mail->ErrorInfo);
    
    // Try alternative settings
    debug_to_file("Trying alternative SMTP settings");
    try {
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 3;
        $mail->Debugoutput = function($str, $level) {
            debug_to_file("ALT PHPMailer Debug: $str");
        };
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'padanamakkalonix@gmail.com';
        $mail->Password   = 'shyl ywsq bwel vnvk';
        
        // Try STARTTLS on port 587 as alternative
        debug_to_file("Using ENCRYPTION_STARTTLS with port 587");
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->setFrom('padanamakkalonix@gmail.com', 'Yards Of Grace');
        $mail->addAddress('padanamakkalonix@gmail.com');
        
        $mail->isHTML(true);
        $mail->Subject = 'PHPMailer Test Email (Alternative Settings)';
        $mail->Body    = '<h1>This is a test email</h1><p>This email was sent with alternative settings to verify that PHPMailer is working correctly.</p>';
        
        debug_to_file("About to send email with alternative settings");
        $mail->send();
        debug_to_file("Email sent successfully with alternative settings!");
        
        echo '<div style="background-color: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px; border: 1px solid #c3e6cb;">
            <h2>Success with Alternative Settings!</h2>
            <p>Email sent successfully using port 587 with STARTTLS. Check your inbox for the test email.</p>
            <p>Check the test_email_debug.txt file for detailed debugging information.</p>
        </div>';
        
    } catch (Exception $e2) {
        debug_to_file("FAILED: Alternative settings also failed");
        debug_to_file("Alternative Mailer Error: " . $mail->ErrorInfo);
        
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 20px; border-radius: 5px; border: 1px solid #f5c6cb;">
            <h2>Email Sending Failed</h2>
            <p>Email could not be sent with either setting.</p>
            <p><strong>Error:</strong> ' . htmlspecialchars($mail->ErrorInfo) . '</p>
            <p>Check the test_email_debug.txt file for detailed debugging information.</p>
            <h3>Troubleshooting Steps:</h3>
            <ol>
                <li>Verify that your Gmail app password is correct and active</li>
                <li>Make sure that your Gmail account doesn\'t have additional security measures preventing the login</li>
                <li>Check if your server can connect to smtp.gmail.com</li>
                <li>Ensure that OpenSSL is enabled in your PHP configuration</li>
                <li>Try creating a new app password in your Google account</li>
            </ol>
        </div>';
    }
}
?>

<div style="padding: 20px; margin: 20px; background-color: #f8f9fa; border-radius: 5px;">
    <h2>PHPMailer Installation Instructions</h2>
    
    <p>If the test fails, you may need to reinstall PHPMailer:</p>
    
    <h3>Option 1: Using Composer (Recommended)</h3>
    <ol>
        <li>Open command prompt</li>
        <li>Navigate to your project directory: <code>cd C:\xamppp\htdocs\YOG</code></li>
        <li>Run: <code>composer require phpmailer/phpmailer</code></li>
        <li>Update your includes to:
<pre>
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
</pre>
        </li>
    </ol>
    
    <h3>Option 2: Manual Installation</h3>
    <ol>
        <li>Download PHPMailer from <a href="https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip" target="_blank">GitHub</a></li>
        <li>Extract the zip file</li>
        <li>Create a folder named "PHPMailer" in your project root</li>
        <li>Copy the contents of the "src" folder into your "PHPMailer" folder</li>
        <li>Update your includes to:
<pre>
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
</pre>
        </li>
    </ol>
</div>
