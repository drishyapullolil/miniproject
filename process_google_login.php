<?php
session_start();
require_once 'db.php';

// Get the JSON data sent from the client
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check if we have the required data
if (!isset($data['email']) || !isset($data['uid'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required user data'
    ]);
    exit;
}

// Extract user data
$email = $data['email'];
$name = $data['name'] ?? '';
$uid = $data['uid'];
$photoURL = $data['photoURL'] ?? '';

try {
    // Replace the existing check with this:
    $userCheck = checkGoogleUser($conn, $email, $uid);

    if (!$userCheck['exists']) {
        // User doesn't exist - redirect to signup
        echo json_encode([
            'success' => false,
            'message' => 'Please sign up first to continue.',
            'redirect' => 'reg.php'
        ]);
        exit;
    }

    // User exists - proceed with login
    $user = $userCheck['data'];

    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    // Update last login time
    $update_stmt = $conn->prepare("UPDATE users SET 
        last_login = CURRENT_TIMESTAMP,
        google_uid = ?,
        photo_url = ?,
        display_name = ?
        WHERE id = ?");
    $update_stmt->bind_param("sssi", $uid, $photoURL, $name, $user['user_id']);
    $update_stmt->execute();

    // Record the login
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $login_time = date('Y-m-d H:i:s');
    $login_stmt = $conn->prepare("INSERT INTO logins (user_id, username, login_time, ip_address) VALUES (?, ?, ?, ?)");
    $login_stmt->bind_param("isss", $user['user_id'], $user['username'], $login_time, $ip_address);
    $login_stmt->execute();

    // Return success with appropriate redirect
    $redirect = ($user['role'] === 'admin') ? 'admin.php' : 'home.php';
    echo json_encode([
        'success' => true,
        'redirect' => $redirect
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing login: ' . $e->getMessage()
    ]);
}
?>