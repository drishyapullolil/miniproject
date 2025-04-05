<?php
$log_file = '/var/www/html/database_setup.log';

// Check if the log file is writable
if (is_writable($log_file)) {
    file_put_contents($log_file, "Starting database setup\n", FILE_APPEND);
} else {
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
$host = 'dpg-cvojjammcj7s73867eog-a.oregon-postgres.render.com';
$port = '5432';
$dbname = 'yards_of_grace_db';
$username = 'yards_of_grace_db_user';
$password = 'tJtozPTnA3QgFvz9Bor27YGwSJazMvZs';

// Logging Function
function logDatabaseSetup($message, $type = 'info') {
    $logFile = __DIR__ . '/database_setup.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
    
    if (is_writable($logFile) || is_writable(dirname($logFile))) {
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    } else {
        error_log($logEntry);
    }
}

// Improved Connection Handling with PostgreSQL
try {
    // Create PostgreSQL connection
    $conn = pg_connect("host=$host port=$port dbname=$dbname user=$username password=$password");

    if (!$conn) {
        logDatabaseSetup("Database Connection Failed: " . pg_last_error(), 'error');
        throw new Exception("Database Connection Failed: " . pg_last_error());
    }

    logDatabaseSetup("Database connection established successfully");

    // Table Creation Queries with PostgreSQL syntax
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
            google_uid VARCHAR(128),
            photo_url VARCHAR(255),
            display_name VARCHAR(100)
        ",
        
        'logins' => "CREATE TABLE IF NOT EXISTS logins (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            username VARCHAR(50) NOT NULL,
            ip_address VARCHAR(50) NOT NULL,
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'categories' => "CREATE TABLE IF NOT EXISTS categories (
            id SERIAL PRIMARY KEY,
            category_name VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'subcategories' => "CREATE TABLE IF NOT EXISTS subcategories (
            id SERIAL PRIMARY KEY,
            category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
            subcategory_name VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'sarees' => "CREATE TABLE IF NOT EXISTS sarees (
            id SERIAL PRIMARY KEY,
            category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
            subcategory_id INTEGER REFERENCES subcategories(id) ON DELETE SET NULL,
            name VARCHAR(255) NOT NULL,
            saree_name VARCHAR(255),
            price DECIMAL(10,2) NOT NULL,
            stock INTEGER DEFAULT 0,
            color VARCHAR(255) NOT NULL,
            image VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_stock_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'product_specifications' => "CREATE TABLE IF NOT EXISTS product_specifications (
            id SERIAL PRIMARY KEY,
            saree_id INTEGER NOT NULL REFERENCES sarees(id) ON DELETE CASCADE,
            material VARCHAR(255) NOT NULL,
            style VARCHAR(255) NOT NULL,
            saree_length DECIMAL(5, 2) NOT NULL,
            blouse_length DECIMAL(5, 2) NOT NULL,
            wash_care VARCHAR(255) NOT NULL,
            description TEXT NOT NULL
        )",
        
        'saree_stock_history' => "CREATE TABLE IF NOT EXISTS saree_stock_history (
            id SERIAL PRIMARY KEY,
            saree_id INTEGER NOT NULL REFERENCES sarees(id) ON DELETE CASCADE,
            stock_added INTEGER NOT NULL,
            previous_stock INTEGER NOT NULL,
            new_stock INTEGER NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_by INTEGER NOT NULL REFERENCES users(id)
        )",
        
        'orders' => "CREATE TABLE IF NOT EXISTS orders (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id),
            name VARCHAR(255) NOT NULL,
            total_amount DECIMAL(10, 2) NOT NULL,
            order_status VARCHAR(20) DEFAULT 'pending',
            address TEXT NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            payment_status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'order_details' => "CREATE TABLE IF NOT EXISTS order_details (
            id SERIAL PRIMARY KEY,
            order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
            saree_id INTEGER REFERENCES sarees(id) ON DELETE SET NULL,
            product_id INTEGER REFERENCES wedding_products(id) ON DELETE SET NULL,
            quantity INTEGER NOT NULL,
            price DECIMAL(10, 2) NOT NULL
        )",
        
        'payments' => "CREATE TABLE IF NOT EXISTS payments (
            id SERIAL PRIMARY KEY,
            order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
            payment_method VARCHAR(50) NOT NULL,
            transaction_id VARCHAR(255),
            amount DECIMAL(10, 2) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'wishlist' => "CREATE TABLE IF NOT EXISTS wishlist (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            saree_id INTEGER NOT NULL REFERENCES sarees(id) ON DELETE CASCADE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (user_id, saree_id)
        )",
        
        'cart' => "CREATE TABLE IF NOT EXISTS cart (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            saree_id INTEGER NOT NULL REFERENCES sarees(id) ON DELETE CASCADE,
            quantity INTEGER NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'wedding_categories' => "CREATE TABLE IF NOT EXISTS wedding_categories (
            id SERIAL PRIMARY KEY,
            category_name VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'wedding_products' => "CREATE TABLE IF NOT EXISTS wedding_products (
            id SERIAL PRIMARY KEY,
            wedding_category_id INTEGER NOT NULL REFERENCES wedding_categories(id) ON DELETE CASCADE,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            stock INTEGER DEFAULT 0,
            color VARCHAR(255) NOT NULL,
            image VARCHAR(255) NOT NULL,
            material VARCHAR(255),
            style VARCHAR(255),
            occasion VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'wedding_specifications' => "CREATE TABLE IF NOT EXISTS wedding_specifications (
            id SERIAL PRIMARY KEY,
            product_id INTEGER NOT NULL REFERENCES wedding_products(id) ON DELETE CASCADE,
            blouse_details VARCHAR(255),
            saree_length DECIMAL(5, 2),
            blouse_length DECIMAL(5, 2),
            wash_care VARCHAR(255),
            additional_details TEXT
        )",
        
        'wedding_details' => "CREATE TABLE IF NOT EXISTS wedding_details (
            id SERIAL PRIMARY KEY,
            wedding_product_id INTEGER NOT NULL REFERENCES wedding_products(id) ON DELETE CASCADE,
            fabric VARCHAR(255) NOT NULL,
            design_type VARCHAR(255) NOT NULL,
            length DECIMAL(5, 2) NOT NULL,
            width DECIMAL(5, 2) NOT NULL,
            care_instructions VARCHAR(255) NOT NULL,
            description TEXT NOT NULL
        )"
    ];

    // Execute table creation
    foreach ($tables as $tableName => $createTableQuery) {
        $result = pg_query($conn, $createTableQuery);
        if (!$result) {
            logDatabaseSetup("Error creating {$tableName} table: " . pg_last_error($conn), 'error');
            throw new Exception("Error creating {$tableName} table: " . pg_last_error($conn));
        }
        logDatabaseSetup("Table {$tableName} created or already exists");
    }

    // Check and add wedding_category_id column to sarees table if needed
    $checkColumnQuery = "SELECT column_name 
                        FROM information_schema.columns 
                        WHERE table_name='sarees' AND column_name='wedding_category_id'";
    $result = pg_query($conn, $checkColumnQuery);
    
    if (pg_num_rows($result) == 0) {
        $addColumnQuery = "ALTER TABLE sarees 
                          ADD COLUMN wedding_category_id INTEGER REFERENCES wedding_categories(id) ON DELETE SET NULL";
        if (!pg_query($conn, $addColumnQuery)) {
            throw new Exception("Error adding wedding_category_id column: " . pg_last_error($conn));
        }
        logDatabaseSetup("Added wedding_category_id column to sarees table");
    }

    logDatabaseSetup("Database setup completed successfully");

} catch (Exception $e) {
    logDatabaseSetup("Critical Database Setup Error: " . $e->getMessage(), 'critical');
    die("A critical database setup error occurred. Please contact support.");
}

// PostgreSQL versions of the functions
function addToWishlist($conn, $userId, $sareeId) {
    // Check if item already exists in wishlist
    $checkQuery = "SELECT id FROM wishlist WHERE user_id = $1 AND saree_id = $2";
    $checkResult = pg_query_params($conn, $checkQuery, [$userId, $sareeId]);
    
    if (pg_num_rows($checkResult) > 0) {
        return ["success" => false, "message" => "Item already in wishlist"];
    }
    
    // Add to wishlist
    $insertQuery = "INSERT INTO wishlist (user_id, saree_id) VALUES ($1, $2)";
    $insertResult = pg_query_params($conn, $insertQuery, [$userId, $sareeId]);
    
    if ($insertResult) {
        return ["success" => true, "message" => "Added to wishlist successfully"];
    } else {
        return ["success" => false, "message" => "Error adding to wishlist: " . pg_last_error($conn)];
    }
}

function removeFromWishlist($conn, $userId, $sareeId) {
    $query = "DELETE FROM wishlist WHERE user_id = $1 AND saree_id = $2";
    $result = pg_query_params($conn, $query, [$userId, $sareeId]);
    
    if ($result) {
        return ["success" => true, "message" => "Removed from wishlist successfully"];
    } else {
        return ["success" => false, "message" => "Error removing from wishlist: " . pg_last_error($conn)];
    }
}

function getUserWishlist($conn, $userId) {
    $query = "
        SELECT w.id as wishlist_id, s.*, c.category_name, sc.subcategory_name
        FROM wishlist w
        JOIN sarees s ON w.saree_id = s.id
        JOIN categories c ON s.category_id = c.id
        LEFT JOIN subcategories sc ON s.subcategory_id = sc.id
        WHERE w.user_id = $1
        ORDER BY w.created_at DESC
    ";
    
    $result = pg_query_params($conn, $query, [$userId]);
    $wishlistItems = [];
    
    while ($row = pg_fetch_assoc($result)) {
        $wishlistItems[] = $row;
    }
    
    return $wishlistItems;
}

function isInWishlist($conn, $userId, $sareeId) {
    $query = "SELECT id FROM wishlist WHERE user_id = $1 AND saree_id = $2";
    $result = pg_query_params($conn, $query, [$userId, $sareeId]);
    return pg_num_rows($result) > 0;
}

function getWishlistCount($conn, $userId) {
    $query = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = $1";
    $result = pg_query_params($conn, $query, [$userId]);
    $row = pg_fetch_assoc($result);
    return $row['count'] ?? 0;
}

// Cart functions
function addToCart($conn, $userId, $sareeId, $quantity = 1) {
    try {
        // Check if item already exists in cart
        $checkQuery = "SELECT id, quantity FROM cart WHERE user_id = $1 AND saree_id = $2";
        $checkResult = pg_query_params($conn, $checkQuery, [$userId, $sareeId]);

        if (pg_num_rows($checkResult) > 0) {
            // Update existing cart item
            $row = pg_fetch_assoc($checkResult);
            $newQuantity = $row['quantity'] + $quantity;
            
            $updateQuery = "UPDATE cart SET quantity = $1 WHERE id = $2";
            $updateResult = pg_query_params($conn, $updateQuery, [$newQuantity, $row['id']]);
            
            if ($updateResult) {
                return ["success" => true, "message" => "Cart updated successfully"];
            }
        } else {
            // Add new item to cart
            $insertQuery = "INSERT INTO cart (user_id, saree_id, quantity) VALUES ($1, $2, $3)";
            $insertResult = pg_query_params($conn, $insertQuery, [$userId, $sareeId, $quantity]);
            
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
    $query = "SELECT 
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
        $result = pg_query_params($conn, $query, [$userId]);
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
        $query = "DELETE FROM cart WHERE user_id = $1 AND saree_id = $2";
        $result = pg_query_params($conn, $query, [$userId, $sareeId]);
        
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
        $query = "UPDATE cart SET quantity = $1 WHERE user_id = $2 AND saree_id = $3";
        $result = pg_query_params($conn, $query, [$quantity, $userId, $sareeId]);
        
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
        $query = "SELECT SUM(quantity) as count FROM cart WHERE user_id = $1";
        $result = pg_query_params($conn, $query, [$userId]);
        $row = pg_fetch_assoc($result);
        return $row['count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Get cart count error: " . $e->getMessage());
        return 0;
    }
}

function checkGoogleUser($conn, $email, $uid) {
    try {
        // First check if user exists with this email
        $query = "SELECT id, username, email, google_uid, role FROM users WHERE email = $1";
        $result = pg_query_params($conn, $query, [$email]);

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
            $updateQuery = "UPDATE users SET google_uid = $1 WHERE id = $2";
            pg_query_params($conn, $updateQuery, [$uid, $user['id']]);
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
