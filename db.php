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
                email VARCHAR(100) NOT NULL,
                password VARCHAR(255) NOT NULL,
                phoneno VARCHAR(15) NOT NULL,
                address VARCHAR(255) DEFAULT NULL,
                profile_picture VARCHAR(255) DEFAULT NULL,
                role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
                reset_token VARCHAR(255) DEFAULT NULL,
                token_expiry DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL DEFAULT NULL,
                profile_pic VARCHAR(255)
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
$categoriesTable = "CREATE TABLE IF NOT EXISTS categories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($categoriesTable) !== TRUE) {
    logDatabaseSetup("Error creating categories table: " . $conn->error, 'error');
    throw new Exception("Error creating categories table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'categories' created or already exists");
}

// Create Subcategories Table
$subcategoriesTable = "CREATE TABLE IF NOT EXISTS subcategories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    category_id INT(11) NOT NULL,
    subcategory_name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
)";

if ($conn->query($subcategoriesTable) !== TRUE) {
    logDatabaseSetup("Error creating subcategories table: " . $conn->error, 'error');
    throw new Exception("Error creating subcategories table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'subcategories' created or already exists");
}
$sareesTable = "CREATE TABLE IF NOT EXISTS sarees (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    category_id INT(11) NOT NULL,
    subcategory_id INT(11) DEFAULT NULL,
    name VARCHAR(255) NOT NULL,  -- ✅ Added 'name' for sorting
    saree_name VARCHAR(255)  NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT(11) DEFAULT 0,  -- ✅ Optional: Add stock tracking
    color VARCHAR(255)NOT NULL,
    image VARCHAR(255)NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_stock_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL
)";//ALTER TABLE sarees ADD COLUMN last_stock_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

if ($conn->query($sareesTable) !== TRUE) {
    logDatabaseSetup("Error creating sarees table: " . $conn->error, 'error');
    throw new Exception("Error creating sarees table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'sarees' created or already exists");
}
// Product Specifications Table
$productSpecificationsTable = "CREATE TABLE IF NOT EXISTS product_specifications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    saree_id INT(11) NOT NULL,
    material VARCHAR(255) NOT NULL,
    style VARCHAR(255) NOT NULL,
    saree_length DECIMAL(5, 2) NOT NULL,
    blouse_length DECIMAL(5, 2) NOT NULL,
    wash_care VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    FOREIGN KEY (saree_id) REFERENCES sarees(id) ON DELETE CASCADE
)";

if ($conn->query($productSpecificationsTable) !== TRUE) {
    logDatabaseSetup("Error creating product_specifications table: " . $conn->error, 'error');
    throw new Exception("Error creating product_specifications table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'product_specifications' created or already exists");
}
//ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL;
$sareeStockHistoryTable = "CREATE TABLE IF NOT EXISTS saree_stock_history (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    saree_id INT(11) NOT NULL,
    stock_added INT(11) NOT NULL,
    previous_stock INT(11) NOT NULL,
    new_stock INT(11) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INT(11) NOT NULL,
    FOREIGN KEY (saree_id) REFERENCES sarees(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id)
)";

if ($conn->query($sareeStockHistoryTable) !== TRUE) {
    logDatabaseSetup("Error creating saree_stock_history table: " . $conn->error, 'error');
    throw new Exception("Error creating saree_stock_history table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'saree_stock_history' created or already exists");
}
// SQL query for creating the 'orders' table
$ordersTable = "CREATE TABLE IF NOT EXISTS orders (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL, -- Assuming you have a users table
    name VARCHAR(255) NOT NULL, -- Add the name field
    total_amount DECIMAL(10, 2) NOT NULL,
    order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    address TEXT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";


// Execute the query for 'orders' table
if ($conn->query($ordersTable) !== TRUE) {
    logDatabaseSetup("Error creating orders table: " . $conn->error, 'error');
    throw new Exception("Error creating orders table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'orders' created or already exists");
}

// SQL query for creating the 'order_details' table
$orderDetailsTable = "CREATE TABLE IF NOT EXISTS order_details (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    saree_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (saree_id) REFERENCES sarees(id) ON DELETE CASCADE
)";

// Execute the query for 'order_details' table
if ($conn->query($orderDetailsTable) !== TRUE) {
    logDatabaseSetup("Error creating order_details table: " . $conn->error, 'error');
    throw new Exception("Error creating order_details table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'order_details' created or already exists");
}
$paymentsTable = "CREATE TABLE IF NOT EXISTS payments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(255), -- For UPI or card transactions
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)";

// Execute the query for 'payments' table
if ($conn->query($paymentsTable) !== TRUE) {
    logDatabaseSetup("Error creating payments table: " . $conn->error, 'error');
    throw new Exception("Error creating payments table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'payments' created or already exists");
}
$wishlistTable = "CREATE TABLE IF NOT EXISTS wishlist (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    saree_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (saree_id) REFERENCES sarees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, saree_id)
)";

if ($conn->query($wishlistTable) !== TRUE) {
    logDatabaseSetup("Error creating wishlist table: " . $conn->error, 'error');
    throw new Exception("Error creating wishlist table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'wishlist' created or already exists");
}

// Function to add item to wishlist
function addToWishlist($conn, $userId, $sareeId) {
    // First check if the item is already in the wishlist
    $checkStmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND saree_id = ?");
    $checkStmt->bind_param("ii", $userId, $sareeId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        return ["success" => false, "message" => "Item already in wishlist"];
    }
    
    // Add to wishlist
    $stmt = $conn->prepare("INSERT INTO wishlist (user_id, saree_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $sareeId);
    
    if ($stmt->execute()) {
        return ["success" => true, "message" => "Added to wishlist successfully"];
    } else {
        return ["success" => false, "message" => "Error adding to wishlist: " . $stmt->error];
    }
}

// Function to remove item from wishlist
function removeFromWishlist($conn, $userId, $sareeId) {
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND saree_id = ?");
    $stmt->bind_param("ii", $userId, $sareeId);
    
    if ($stmt->execute()) {
        return ["success" => true, "message" => "Removed from wishlist successfully"];
    } else {
        return ["success" => false, "message" => "Error removing from wishlist: " . $stmt->error];
    }
}

// Function to get all wishlist items for a user
function getUserWishlist($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT w.id as wishlist_id, s.*, c.category_name, sc.subcategory_name
        FROM wishlist w
        JOIN sarees s ON w.saree_id = s.id
        JOIN categories c ON s.category_id = c.id
        LEFT JOIN subcategories sc ON s.subcategory_id = sc.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $wishlistItems = [];
    while ($row = $result->fetch_assoc()) {
        $wishlistItems[] = $row;
    }
    
    return $wishlistItems;
}

// Function to check if an item is in user's wishlist
function isInWishlist($conn, $userId, $sareeId) {
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND saree_id = ?");
    $stmt->bind_param("ii", $userId, $sareeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

// Function to count items in user's wishlist
function getWishlistCount($conn, $userId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}
?>