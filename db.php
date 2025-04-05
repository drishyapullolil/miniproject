<?php
$log_file = '/var/www/html/database_setup.log';

// Check if the log file is writable
if (is_writable($log_file)) {
    // Write to the log file if it's writable
    file_put_contents($log_file, "Log entry here\n", FILE_APPEND);
} else {
    // Log an error if the log file is not writable
    error_log("Cannot write to log file: " . $log_file);
}
// Session Management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Enable Error Reporting for Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// PostgreSQL Database connection details
$host = 'dpg-cvojjammcj7s73867eog-a.oregon-postgres.render.com';  // Render PostgreSQL hostname
$port = '5432';  // Default PostgreSQL port
$dbname = 'yards_of_grace_db';  // Database name
$username = 'yards_of_grace_db_user';  // Username
$password = 'tJtozPTnA3QgFvz9Bor27YGwSJazMvZs';  // Password

// Logging Function
function logDatabaseSetup($message, $type = 'info') {
    $logFile = __DIR__ . '/database_setup.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
    
    // Check if file is writable, if not, change permissions
    if (is_writable($logFile) || is_writable(dirname($logFile))) {
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    } else {
        // If not writable, log to default error log
        error_log($logEntry);
    }
}

// Improved Connection Handling with PostgreSQL and Comprehensive Error Management
try {
    // Create PostgreSQL connection
    $conn = pg_connect("host=$host port=$port dbname=$dbname user=$username password=$password");

    // Enhanced connection error checking
    if (!$conn) {
        // Log connection error
        logDatabaseSetup("Database Connection Failed: " . pg_last_error(), 'error');
        throw new Exception("Database Connection Failed: " . pg_last_error());
    }

    // Log successful connection
    logDatabaseSetup("Database connection established successfully");

    // Table Creation Queries with Improved Error Handling
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL,
                password VARCHAR(255) NOT NULL,
                phoneno VARCHAR(15) NOT NULL,
                address VARCHAR(255) DEFAULT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'user',
                reset_token VARCHAR(255) DEFAULT NULL,
                token_expiry TIMESTAMP DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL DEFAULT NULL,
                profile_pic VARCHAR(255),
                google_uid VARCHAR(128) NULL,
                photo_url VARCHAR(255) NULL,
                display_name VARCHAR(100) NULL
            )",
        'logins' => "CREATE TABLE IF NOT EXISTS logins (
                id SERIAL PRIMARY KEY,
                user_id INT NOT NULL,
                username VARCHAR(50) NOT NULL,
                ip_address VARCHAR(50) NOT NULL,
                login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )"
    ];

    // Execute table creation with detailed error reporting
    foreach ($tables as $tableName => $createTableQuery) {
        $result = pg_query($conn, $createTableQuery);
        if (!$result) {
            // Log table creation error
            logDatabaseSetup("Error creating {$tableName} table: " . pg_last_error($conn), 'error');
            throw new Exception("Error creating {$tableName} table: " . pg_last_error($conn));
        } else {
            // Log successful table creation
            logDatabaseSetup("Table {$tableName} created or already exists");
        }
    }

    // Log successful database setup
    logDatabaseSetup("Database setup completed successfully");

} catch (Exception $e) {
    // Centralized error handling with logging
    logDatabaseSetup("Critical Database Setup Error: " . $e->getMessage(), 'critical');
    
    // In production, you might want to redirect to an error page
    die("A critical database setup error occurred. Please contact support.");
}

// PostgreSQL version for categories table
$categoriesTable = "CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Execute with pg_query instead of $conn->query
if (!pg_query($conn, $categoriesTable)) {
    logDatabaseSetup("Error creating categories table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating categories table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'categories' created or already exists");
}

// Create Subcategories Table - PostgreSQL version
$subcategoriesTable = "CREATE TABLE IF NOT EXISTS subcategories (
    id SERIAL PRIMARY KEY,
    category_id INT NOT NULL,
    subcategory_name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
)";

if (!pg_query($conn, $subcategoriesTable)) {
    logDatabaseSetup("Error creating subcategories table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating subcategories table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'subcategories' created or already exists");
}

// PostgreSQL version for sarees table
$sareesTable = "CREATE TABLE IF NOT EXISTS sarees (
    id SERIAL PRIMARY KEY,
    category_id INT NOT NULL,
    subcategory_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    saree_name VARCHAR(255) NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    color VARCHAR(255) NOT NULL,
    image VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_stock_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL
)";

if (!pg_query($conn, $sareesTable)) {
    logDatabaseSetup("Error creating sarees table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating sarees table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'sarees' created or already exists");
}

// Product Specifications Table - PostgreSQL version
$productSpecificationsTable = "CREATE TABLE IF NOT EXISTS product_specifications (
    id SERIAL PRIMARY KEY,
    saree_id INT NOT NULL,
    material VARCHAR(255) NOT NULL,
    style VARCHAR(255) NOT NULL,
    saree_length DECIMAL(5, 2) NOT NULL,
    blouse_length DECIMAL(5, 2) NOT NULL,
    wash_care VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    FOREIGN KEY (saree_id) REFERENCES sarees(id) ON DELETE CASCADE
)";

if (!pg_query($conn, $productSpecificationsTable)) {
    logDatabaseSetup("Error creating product_specifications table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating product_specifications table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'product_specifications' created or already exists");
}

// Saree Stock History - PostgreSQL version
$sareeStockHistoryTable = "CREATE TABLE IF NOT EXISTS saree_stock_history (
    id SERIAL PRIMARY KEY,
    saree_id INT NOT NULL,
    stock_added INT NOT NULL,
    previous_stock INT NOT NULL,
    new_stock INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INT NOT NULL,
    FOREIGN KEY (saree_id) REFERENCES sarees(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id)
)";

if (!pg_query($conn, $sareeStockHistoryTable)) {
    logDatabaseSetup("Error creating saree_stock_history table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating saree_stock_history table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'saree_stock_history' created or already exists");
}

// Orders Table - PostgreSQL version
$ordersTable = "CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    order_status VARCHAR(20) DEFAULT 'pending',
    address TEXT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!pg_query($conn, $ordersTable)) {
    logDatabaseSetup("Error creating orders table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating orders table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'orders' created or already exists");
}

// Order Details - PostgreSQL version
$orderDetailsTable = "CREATE TABLE IF NOT EXISTS order_details (
    id SERIAL PRIMARY KEY,
    order_id INT NOT NULL,
    saree_id INT DEFAULT NULL,
    product_id INT DEFAULT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (saree_id) REFERENCES sarees(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES wedding_products(id) ON DELETE CASCADE
)";

if (!pg_query($conn, $orderDetailsTable)) {
    logDatabaseSetup("Error creating order_details table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating order_details table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'order_details' created or already exists");
}

// Payments Table - PostgreSQL version - Using appropriate PostgreSQL enum syntax
$paymentsTable = "CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(255),
    amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) CHECK (status IN ('pending', 'completed', 'failed')) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)";

if (!pg_query($conn, $paymentsTable)) {
    logDatabaseSetup("Error creating payments table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating payments table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'payments' created or already exists");
}

// Wishlist Table - PostgreSQL version
$wishlistTable = "CREATE TABLE IF NOT EXISTS wishlist (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    saree_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (saree_id) REFERENCES sarees(id) ON DELETE CASCADE,
    UNIQUE (user_id, saree_id)
)";

if (!pg_query($conn, $wishlistTable)) {
    logDatabaseSetup("Error creating wishlist table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating wishlist table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'wishlist' created or already exists");
}

// Function to add item to wishlist - PostgreSQL version
function addToWishlist($conn, $userId, $sareeId) {
    // First check if the item is already in the wishlist
    $checkSql = "SELECT id FROM wishlist WHERE user_id = $1 AND saree_id = $2";
    $checkResult = pg_query_params($conn, $checkSql, array($userId, $sareeId));
    
    if (pg_num_rows($checkResult) > 0) {
        return ["success" => false, "message" => "Item already in wishlist"];
    }
    
    // Add to wishlist
    $sql = "INSERT INTO wishlist (user_id, saree_id) VALUES ($1, $2)";
    $result = pg_query_params($conn, $sql, array($userId, $sareeId));
    
    if ($result) {
        return ["success" => true, "message" => "Added to wishlist successfully"];
    } else {
        return ["success" => false, "message" => "Error adding to wishlist: " . pg_last_error($conn)];
    }
}

// Function to remove item from wishlist - PostgreSQL version
function removeFromWishlist($conn, $userId, $sareeId) {
    $sql = "DELETE FROM wishlist WHERE user_id = $1 AND saree_id = $2";
    $result = pg_query_params($conn, $sql, array($userId, $sareeId));
    
    if ($result) {
        return ["success" => true, "message" => "Removed from wishlist successfully"];
    } else {
        return ["success" => false, "message" => "Error removing from wishlist: " . pg_last_error($conn)];
    }
}

// Function to get all wishlist items for a user - PostgreSQL version
function getUserWishlist($conn, $userId) {
    $sql = "
        SELECT w.id as wishlist_id, s.*, c.category_name, sc.subcategory_name
        FROM wishlist w
        JOIN sarees s ON w.saree_id = s.id
        JOIN categories c ON s.category_id = c.id
        LEFT JOIN subcategories sc ON s.subcategory_id = sc.id
        WHERE w.user_id = $1
        ORDER BY w.created_at DESC
    ";
    $result = pg_query_params($conn, $sql, array($userId));
    
    $wishlistItems = [];
    while ($row = pg_fetch_assoc($result)) {
        $wishlistItems[] = $row;
    }
    
    return $wishlistItems;
}

function isInWishlist($conn, $userId, $sareeId) {
    $sql = "SELECT id FROM wishlist WHERE user_id = $1 AND saree_id = $2";
    $result = pg_query_params($conn, $sql, array($userId, $sareeId));
    
    return pg_num_rows($result) > 0;
}

function getWishlistCount($conn, $userId) {
    $sql = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = $1";
    $result = pg_query_params($conn, $sql, array($userId));
    $row = pg_fetch_assoc($result);
    
    return $row['count'];
}

// Cart Table - PostgreSQL version
$cartTable = "CREATE TABLE IF NOT EXISTS cart (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    saree_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (saree_id) REFERENCES sarees(id) ON DELETE CASCADE
)";

if (!pg_query($conn, $cartTable)) {
    logDatabaseSetup("Error creating cart table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating cart table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'cart' created or already exists");
}

// Cart functions - PostgreSQL version
function addToCart($conn, $userId, $sareeId, $quantity = 1) {
    try {
        // Check if item already exists in cart
        $checkSql = "SELECT id, quantity FROM cart WHERE user_id = $1 AND saree_id = $2";
        $checkResult = pg_query_params($conn, $checkSql, array($userId, $sareeId));

        if (pg_num_rows($checkResult) > 0) {
            // Update existing cart item
            $row = pg_fetch_assoc($checkResult);
            $newQuantity = $row['quantity'] + $quantity;
            
            $updateSql = "UPDATE cart SET quantity = $1 WHERE id = $2";
            $updateResult = pg_query_params($conn, $updateSql, array($newQuantity, $row['id']));
            
            if ($updateResult) {
                return ["success" => true, "message" => "Cart updated successfully"];
            }
        } else {
            // Add new item to cart
            $insertSql = "INSERT INTO cart (user_id, saree_id, quantity) VALUES ($1, $2, $3)";
            $insertResult = pg_query_params($conn, $insertSql, array($userId, $sareeId, $quantity));
            
            if ($insertResult) {
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
            WHERE c.user_id = $1";
            
    try {
        $result = pg_query_params($conn, $sql, array($userId));
        $cartItems = [];
        
        while ($row = pg_fetch_assoc($result)) {
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
        $sql = "DELETE FROM cart WHERE user_id = $1 AND saree_id = $2";
        $result = pg_query_params($conn, $sql, array($userId, $sareeId));
        
        if ($result) {
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
        $sql = "UPDATE cart SET quantity = $1 WHERE user_id = $2 AND saree_id = $3";
        $result = pg_query_params($conn, $sql, array($quantity, $userId, $sareeId));
        
        if ($result) {
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
        $sql = "SELECT SUM(quantity) as count FROM cart WHERE user_id = $1";
        $result = pg_query_params($conn, $sql, array($userId));
        $row = pg_fetch_assoc($result);
        
        return $row['count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Get cart count error: " . $e->getMessage());
        return 0;
    }
}
// Wedding Categories Table - PostgreSQL version
$weddingCategoriesTable = "CREATE TABLE IF NOT EXISTS wedding_categories (
    id SERIAL PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Check if the wedding_categories table is created successfully
if (!pg_query($conn, $weddingCategoriesTable)) {
    logDatabaseSetup("Error creating wedding_categories table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating wedding_categories table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'wedding_categories' created or already exists");
}

// Wedding Products Table - PostgreSQL version
$weddingProductsTable = "CREATE TABLE IF NOT EXISTS wedding_products (
    id SERIAL PRIMARY KEY,
    wedding_category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    color VARCHAR(255) NOT NULL,
    image VARCHAR(255) NOT NULL,
    material VARCHAR(255) DEFAULT NULL,
    style VARCHAR(255) DEFAULT NULL,
    occasion VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wedding_category_id) REFERENCES wedding_categories(id) ON DELETE CASCADE
)";

// Check if the wedding_products table is created successfully
if (!pg_query($conn, $weddingProductsTable)) {
    logDatabaseSetup("Error creating wedding_products table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating wedding_products table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'wedding_products' created or already exists");
}

// Proceed to create other tables or processes that depend on wedding_products


// Wedding Specifications Table - PostgreSQL version
$weddingSpecificationsTable = "CREATE TABLE IF NOT EXISTS wedding_specifications (
    id SERIAL PRIMARY KEY,
    product_id INT NOT NULL,
    blouse_details TEXT DEFAULT NULL,
    saree_length DECIMAL(5,2) DEFAULT NULL,
    blouse_length DECIMAL(5,2) DEFAULT NULL,
    wash_care TEXT DEFAULT NULL,
    additional_details TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES wedding_products(id) ON DELETE CASCADE
)";

// Check if the wedding_specifications table is created successfully
if (!pg_query($conn, $weddingSpecificationsTable)) {
    logDatabaseSetup("Error creating wedding_specifications table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating wedding_specifications table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'wedding_specifications' created or already exists");
}

// Wedding Details Table - PostgreSQL version
$weddingDetailsTable = "CREATE TABLE IF NOT EXISTS wedding_details (
    id SERIAL PRIMARY KEY,
    wedding_product_id INT NOT NULL,
    fabric VARCHAR(255) NOT NULL,
    design_type VARCHAR(255) NOT NULL,
    length DECIMAL(5, 2) NOT NULL,
    width DECIMAL(5, 2) NOT NULL,
    care_instructions VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    FOREIGN KEY (wedding_product_id) REFERENCES wedding_products(id) ON DELETE CASCADE
)";

// Check if the wedding_details table is created successfully
if (!pg_query($conn, $weddingDetailsTable)) {
    logDatabaseSetup("Error creating wedding_details table: " . pg_last_error($conn), 'error');
    throw new Exception("Error creating wedding_details table: " . pg_last_error($conn));
} else {
    logDatabaseSetup("Table 'wedding_details' created or already exists");
}

// Check if wedding_category_id column exists in sarees table - PostgreSQL version
$checkColumnQuery = "SELECT column_name FROM information_schema.columns 
                    WHERE table_name = 'sarees' AND column_name = 'wedding_category_id'";

// Check if the sarees table exists before modifying it
$checkTableQuery = "SELECT to_regclass('public.sarees')";
$tableResult = pg_query($conn, $checkTableQuery);
$tableExists = pg_fetch_row($tableResult)[0];

if (!$tableExists) {
    die("Sarees table does not exist.");
}

// Proceed with checking for the wedding_category_id column
$columnResult = pg_query($conn, $checkColumnQuery);

if (pg_num_rows($columnResult) == 0) {
    // Column doesn't exist, add it
    $addColumnQuery = "ALTER TABLE sarees 
        ADD COLUMN wedding_category_id INT,
        ADD CONSTRAINT fk_wedding_category 
        FOREIGN KEY (wedding_category_id) 
        REFERENCES wedding_categories(id) ON DELETE SET NULL";
    
    if (!pg_query($conn, $addColumnQuery)) {
        die("Error adding wedding_category_id column: " . pg_last_error($conn));
    }
} else {
    logDatabaseSetup("Column 'wedding_category_id' already exists in sarees table.");
}

// Google User Check Function - PostgreSQL version
function checkGoogleUser($conn, $email, $uid) {
    try {
        // First check if user exists with this email
        $sql = "SELECT id, username, email, google_uid, role FROM users WHERE email = $1";
        $result = pg_query_params($conn, $sql, array($email));

        if (pg_num_rows($result) === 0) {
            // User doesn't exist - they should sign up
            return [
                'exists' => false,
                'message' => 'Please sign up first to continue.',
                'data' => null
            ];
        }

        $user = pg_fetch_assoc($result);

        // If user exists, update their Google UID if not set
        if ($user['google_uid'] === null) {
            // Update the user's Google UID
            $updateSql = "UPDATE users SET google_uid = $1 WHERE id = $2";
            pg_query_params($conn, $updateSql, array($uid, $user['id']));
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
