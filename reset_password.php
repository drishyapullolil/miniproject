<?php
session_start();
require_once 'db.php';

if (!isset($_GET['token'])) {
    $_SESSION['error'] = "Invalid reset token";
    header("Location: login.php");
    exit();
}

$token = $_GET['token'];

// Validate token
$stmt = $conn->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Invalid or expired reset token";
    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: reset_password.php?token=" . $token);
        exit();
    }

    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password and clear reset token
    $update_stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
    $update_stmt->bind_param("si", $hashed_password, $user['id']);
    $update_stmt->execute();

    $_SESSION['success'] = "Password reset successfully";
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h2>Reset Password</h2>
    <?php
    if (isset($_SESSION['error'])) {
        echo "<p style='color:red;'>" . $_SESSION['error'] . "</p>";
        unset($_SESSION['error']);
    }
    ?>
    <form method="POST">
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
        <button type="submit">Reset Password</button>
    </form>
</body>
</html>