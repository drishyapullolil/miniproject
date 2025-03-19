<?php
// Buffer all output
ob_start();

// Start session and include db.php instead of config.php
session_start();
require_once 'db.php';  // Changed from config.php to db.php

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'wishlist_error.log');

// Clear any previous output
ob_clean();

// Set headers for JSON response
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("Wishlist API: User not logged in");
    sendJsonResponse(false, 'User not logged in');
}

// Check if required parameters are present
if (!isset($_POST['action']) || !isset($_POST['saree_id'])) {
    error_log("Wishlist API: Missing parameters - Action: " . ($_POST['action'] ?? 'not set') . ", Saree ID: " . ($_POST['saree_id'] ?? 'not set'));
    sendJsonResponse(false, 'Missing required parameters');
}

$action = $_POST['action'];
$saree_id = (int)$_POST['saree_id'];
$user_id = (int)$_SESSION['user_id'];

error_log("Processing wishlist request - Action: $action, Saree ID: $saree_id, User ID: $user_id");

try {
    // Use the functions already defined in db.php
    if ($action === 'add') {
        $result = addToWishlist($conn, $user_id, $saree_id);
        sendJsonResponse($result['success'], $result['message']);
    } 
    elseif ($action === 'remove') {
        $result = removeFromWishlist($conn, $user_id, $saree_id);
        sendJsonResponse($result['success'], $result['message']);
    } 
    else {
        error_log("Invalid action received: $action");
        sendJsonResponse(false, 'Invalid action');
    }

} catch (Exception $e) {
    error_log("Wishlist Error: " . $e->getMessage());
    sendJsonResponse(false, $e->getMessage());
} finally {
    // No need to close connections here as they are managed in db.php
    // Just end output buffering
    ob_end_flush();
} 