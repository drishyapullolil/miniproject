<?php
// Session Management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Enable Error Reporting for Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "yardsofgrace";

// Logging Function
function logDatabaseSetup($message, $type = 'info') {
    $logFile = __DIR__ . '/database_setup.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Improved Connection Handling with Comprehensive Error Management
try {
    // Create connection using exception handling
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Enhanced connection error checking
    if ($conn->connect_errno) {
        // Log connection error
        logDatabaseSetup("Database Connection Failed: " . $conn->connect_error, 'error');
        throw new Exception("Database Connection Failed: " . $conn->connect_error);
    }

    // Log successful connection
    logDatabaseSetup("Database connection established successfully");

    // Table Creation Queries with Improved Error Handling
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL ,
            password VARCHAR(255) NOT NULL,
            phoneno VARCHAR(15) NOT NULL,
            role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL DEFAULT NULL
        )",
        'logins' => "CREATE TABLE IF NOT EXISTS logins (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(50) NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)"
        
    ];
    
    
    // Execute table creation with detailed error reporting
    foreach ($tables as $tableName => $createTableQuery) {
        if ($conn->query($createTableQuery) !== TRUE) {
            // Log table creation error
            logDatabaseSetup("Error creating {$tableName} table: " . $conn->error, 'error');
            throw new Exception("Error creating {$tableName} table: " . $conn->error);
        } else {
            // Log successful table creation
            logDatabaseSetup("Table {$tableName} created or already exists");
        }
    }

    // Additional Database Configuration
    // Set SQL Mode to Strict (recommended for data integrity)
    $conn->query("SET sql_mode = 'STRICT_ALL_TABLES'");

    // Log successful database setup
    logDatabaseSetup("Database setup completed successfully");

} catch (Exception $e) {
    // Centralized error handling with logging
    logDatabaseSetup("Critical Database Setup Error: " . $e->getMessage(), 'critical');
    
    // In production, you might want to redirect to an error page
    die("A critical database setup error occurred. Please contact support.");

} 

?>