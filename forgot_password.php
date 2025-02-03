<?php
session_start();
require_once 'db.php';

// Function to generate a secure reset token
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: forgot_password.php");
        exit();
    }

    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $reset_token = generateResetToken();
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store reset token and expiry in database
        $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
        $update_stmt->bind_param("sss", $reset_token, $expiry, $email);
        $update_stmt->execute();

        // Send reset email (you'll need to implement email sending)
        $reset_link = "http://yourwebsite.com/reset_password.php?token=" . $reset_token;
        
        // Placeholder for email sending function
        sendResetEmail($email, $reset_link);

        $_SESSION['success'] = "Password reset link sent to your email";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error'] = "Email not found";
        header("Location: forgot_password.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
</head>
<body>
    <h2>Forgot Password</h2>
    <?php
    if (isset($_SESSION['error'])) {
        echo "<p style='color:red;'>" . $_SESSION['error'] . "</p>";
        unset($_SESSION['error']);
    }
    ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Reset Password</button>
    </form>
</body>
</html>