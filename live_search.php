<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    // Include the database connection file
    require_once 'db.php';
    
    // Get search query from request
    $search_query = isset($_GET['q']) ? $_GET['q'] : '';
    
    // Validate search query
    if (empty($search_query) || strlen($search_query) < 2) {
        echo json_encode([
            'success' => false,
            'message' => 'Search query too short'
        ]);
        exit;
    }
    
    // Prepare search query with wildcard
    $search = '%' . $conn->real_escape_string($search_query) . '%';
    
    // Query for products (sarees for now)
    $query = "SELECT 
                s.id, 
                s.name, 
                s.price, 
                s.image,
                s.subcategory_id,
                sub.subcategory_name,
                cat.category_name
              FROM sarees s 
              JOIN subcategories sub ON s.subcategory_id = sub.id
              JOIN categories cat ON sub.category_id = cat.id
              WHERE s.name LIKE ? 
              OR s.description LIKE ? 
              OR s.color LIKE ?
              LIMIT 8";
    
    // Prepare and execute the statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("sss", $search, $search, $search);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // Check if result is valid
    if ($result === false) {
        throw new Exception("Result failed: " . $conn->error);
    }
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => $row['id'],
            'name' => htmlspecialchars($row['name']),
            'price' => number_format($row['price'], 2),
            'image' => htmlspecialchars($row['image']),
            'subcategory_id' => $row['subcategory_id'],
            'subcategory_name' => htmlspecialchars($row['subcategory_name']),
            'category_name' => htmlspecialchars($row['category_name'])
        ];
    }
    
    // Return the results
    echo json_encode([
        'success' => true,
        'data' => $items
    ]);
    
} catch (Exception $e) {
    // Log the error with full details
    error_log("Search error in live_search.php: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => 'Search error occurred. Please try again later.',
        'debug_message' => $e->getMessage() // Remove this in production
    ]);
}
