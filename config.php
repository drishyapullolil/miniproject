<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Site configuration
define('SITE_NAME', 'YARDS OF GRACE');
define('SITE_URL', 'https://your-site.com');
define('UPLOAD_PATH', __DIR__ . '/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Allowed image types
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/webp'
]);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_start();

// Helper functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}