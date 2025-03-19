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
                profile_pic VARCHAR(255),
                google_uid VARCHAR(128) NULL,
                photo_url VARCHAR(255) NULL,
                display_name VARCHAR(100) NULL
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
    order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'cash_received') DEFAULT 'pending',
    address TEXT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(20) DEFAULT 'pending', -- Added payment_status column
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
    saree_id INT(11) DEFAULT NULL,  -- Allow NULL for saree_id
    product_id INT(11) DEFAULT NULL,  -- Allow NULL for product_id
    quantity INT(11) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (saree_id) REFERENCES sarees(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES wedding_products(id) ON DELETE CASCADE
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

function isInWishlist($conn, $userId, $sareeId) {
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND saree_id = ?");
    $stmt->bind_param("ii", $userId, $sareeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}
function getWishlistCount($conn, $userId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

// Cart functions
function addToCart($conn, $userId, $sareeId, $quantity = 1) {
    try {
        // Check if item already exists in cart
        $checkSql = "SELECT id, quantity FROM cart WHERE user_id = ? AND saree_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $userId, $sareeId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing cart item
            $row = $result->fetch_assoc();
            $newQuantity = $row['quantity'] + $quantity;
            
            $updateSql = "UPDATE cart SET quantity = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ii", $newQuantity, $row['id']);
            
            if ($updateStmt->execute()) {
                return ["success" => true, "message" => "Cart updated successfully"];
            }
        } else {
            // Add new item to cart
            $insertSql = "INSERT INTO cart (user_id, saree_id, quantity) VALUES (?, ?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("iii", $userId, $sareeId, $quantity);
            
            if ($insertStmt->execute()) {
                return ["success" => true, "message" => "Item added to cart"];
            }
        }
        
        return ["success" => false, "message" => "Failed to update cart"];
        
    } catch (Exception $e) {
        error_log("Add to cart error: " . $e->getMessage());
        return ["success" => false, "message" => "An error occurred"];
    }
}

function getCartItems($conn, $userId) {
    $sql = "SELECT 
                c.id as cart_id, 
                c.saree_id, 
                c.quantity, 
                s.name, 
                s.price, 
                s.image,
                s.description, 
                cat.category_name, 
                sc.subcategory_name
            FROM cart c 
            JOIN sarees s ON c.saree_id = s.id 
            JOIN categories cat ON s.category_id = cat.id
            LEFT JOIN subcategories sc ON s.subcategory_id = sc.id
            WHERE c.user_id = ?";
            
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $cartItems = [];
        
        while ($row = $result->fetch_assoc()) {
            $cartItems[] = $row;
        }
        
        return $cartItems;
        
    } catch (Exception $e) {
        error_log("Get cart items error: " . $e->getMessage());
        return [];
    }
}

function removeFromCart($conn, $userId, $sareeId) {
    try {
        $sql = "DELETE FROM cart WHERE user_id = ? AND saree_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $sareeId);
        
        if ($stmt->execute()) {
            return ["success" => true, "message" => "Item removed from cart"];
        }
        
        return ["success" => false, "message" => "Failed to remove item"];
        
    } catch (Exception $e) {
        error_log("Remove from cart error: " . $e->getMessage());
        return ["success" => false, "message" => "An error occurred"];
    }
}

function updateCartQuantity($conn, $userId, $sareeId, $quantity) {
    try {
        $sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND saree_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $quantity, $userId, $sareeId);
        
        if ($stmt->execute()) {
            return ["success" => true, "message" => "Quantity updated"];
        }
        
        return ["success" => false, "message" => "Failed to update quantity"];
        
    } catch (Exception $e) {
        error_log("Update cart quantity error: " . $e->getMessage());
        return ["success" => false, "message" => "An error occurred"];
    }
}

function getCartCount($conn, $userId) {
    try {
        $sql = "SELECT SUM(quantity) as count FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Get cart count error: " . $e->getMessage());
        return 0;
    }
}

$cartTable = "CREATE TABLE IF NOT EXISTS cart (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    saree_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (saree_id) REFERENCES sarees(id) ON DELETE CASCADE
)";

// Execute the query for 'cart' table
if ($conn->query($cartTable) !== TRUE) {
    logDatabaseSetup("Error creating cart table: " . $conn->error, 'error');
    throw new Exception("Error creating cart table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'cart' created or already exists");
}

/// Create Wedding Collection Categories Table
$weddingCategoriesTable = "CREATE TABLE IF NOT EXISTS wedding_categories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($weddingCategoriesTable) !== TRUE) {
    logDatabaseSetup("Error creating wedding_categories table: " . $conn->error, 'error');
    throw new Exception("Error creating wedding_categories table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'wedding_categories' created or already exists");
}

// Verify wedding_category_id column exists in sarees table
$checkColumnQuery = "SHOW COLUMNS FROM sarees LIKE 'wedding_category_id'";
$columnResult = $conn->query($checkColumnQuery);

if ($columnResult->num_rows == 0) {
    // Column doesn't exist, add it
    $addColumnQuery = "ALTER TABLE sarees 
        ADD COLUMN wedding_category_id INT(11),
        ADD FOREIGN KEY (wedding_category_id) REFERENCES wedding_categories(id) ON DELETE SET NULL";
    
    if (!$conn->query($addColumnQuery)) {
        die("Error adding wedding_category_id column: " . $conn->error);
    }
}

// Create Wedding Collection Products Table
$weddingProductsTable = "CREATE TABLE IF NOT EXISTS wedding_products (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    wedding_category_id INT(11) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT(11) DEFAULT 0,
    color VARCHAR(255) NOT NULL,
    image VARCHAR(255) NOT NULL,
    material VARCHAR(255) DEFAULT NULL,
    style VARCHAR(255) DEFAULT NULL,
    occasion VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (wedding_category_id) REFERENCES wedding_categories(id) ON DELETE CASCADE
)";

if ($conn->query($weddingProductsTable) !== TRUE) {
    logDatabaseSetup("Error creating wedding_products table: " . $conn->error, 'error');
    throw new Exception("Error creating wedding_products table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'wedding_products' created or already exists");
}

// Create Wedding Collection Specifications Table
$weddingSpecificationsTable = "CREATE TABLE IF NOT EXISTS wedding_specifications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    product_id INT(11) NOT NULL,
    blouse_details TEXT DEFAULT NULL,
    saree_length DECIMAL(5,2) DEFAULT NULL,
    blouse_length DECIMAL(5,2) DEFAULT NULL,
    wash_care TEXT DEFAULT NULL,
    additional_details TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES wedding_products(id) ON DELETE CASCADE
)";

if ($conn->query($weddingSpecificationsTable) !== TRUE) {
    logDatabaseSetup("Error creating wedding_specifications table: " . $conn->error, 'error');
    throw new Exception("Error creating wedding_specifications table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'wedding_specifications' created or already exists");
}

$weddingDetailsTable = "CREATE TABLE IF NOT EXISTS wedding_details (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    wedding_product_id INT(11) NOT NULL,
    fabric VARCHAR(255) NOT NULL,
    design_type VARCHAR(255) NOT NULL,
    length DECIMAL(5, 2) NOT NULL,
    width DECIMAL(5, 2) NOT NULL,
    care_instructions VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    FOREIGN KEY (wedding_product_id) REFERENCES wedding_products(id) ON DELETE CASCADE
)";

if ($conn->query($weddingDetailsTable) !== TRUE) {
    logDatabaseSetup("Error creating wedding_details table: " . $conn->error, 'error');
    throw new Exception("Error creating wedding_details table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'wedding_details' created or already exists");
}
$weddingSpecificationsTable = "CREATE TABLE IF NOT EXISTS wedding_specifications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    product_id INT(11) NOT NULL,
    blouse_details VARCHAR(255) DEFAULT NULL,
    saree_length DECIMAL(5, 2) DEFAULT NULL,
    blouse_length DECIMAL(5, 2) DEFAULT NULL,
    wash_care VARCHAR(255) DEFAULT NULL,
    additional_details TEXT DEFAULT NULL,
    FOREIGN KEY (product_id) REFERENCES wedding_products(id) ON DELETE CASCADE
)";

if ($conn->query($weddingSpecificationsTable) !== TRUE) {
    logDatabaseSetup("Error creating wedding_specifications table: " . $conn->error, 'error');
    throw new Exception("Error creating wedding_specifications table: " . $conn->error);
} else {
    logDatabaseSetup("Table 'wedding_specifications' created or already exists");
}

function checkGoogleUser($conn, $email, $uid) {
    try {
        // First check if user exists with this email
        $stmt = $conn->prepare("SELECT id, username, email, google_uid, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // User doesn't exist - they should sign up
            return [
                'exists' => false,
                'message' => 'Please sign up first to continue.',
                'data' => null
            ];
        }

        $user = $result->fetch_assoc();

        // If user exists, update their Google UID if not set
        if ($user['google_uid'] === null) {
            // Update the user's Google UID
            $updateStmt = $conn->prepare("UPDATE users SET google_uid = ? WHERE id = ?");
            $updateStmt->bind_param("si", $uid, $user['id']);
            $updateStmt->execute();
        }

        // Return user data for login
        return [
            'exists' => true,
            'needs_linking' => false,
            'message' => 'User found',
            'data' => [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];

    } catch (Exception $e) {
        error_log("Google user check error: " . $e->getMessage());
        return [
            'exists' => false,
            'message' => 'An error occurred while checking user status',
            'data' => null
        ];
    }
}
?>