<?php
// Enable Error Reporting for Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "yardsofgrace";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        die("Passwords do not match.");
    }

    // Check if the token is valid and not expired
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($email);
        $stmt->fetch();

        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the user's password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        $stmt->execute();

        // Delete the token
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();

        echo "Password updated successfully. You can now <a href='login.php'>login</a> with your new password.";
    } else {
        echo "Invalid or expired token.";
    }

    $stmt->close();
}

$conn->close();
?>
