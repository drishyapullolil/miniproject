<?php
// Enhanced security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db.php';

// Get category ID from URL
$categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;

// First, check if the category exists
$categoryQuery = "SELECT * FROM wedding_categories WHERE id = ?";
$categoryStmt = $conn->prepare($categoryQuery);
$categoryStmt->bind_param("i", $categoryId);
$categoryStmt->execute();
$categoryResult = $categoryStmt->get_result();
$category = $categoryResult->fetch_assoc();

if (!$category) {
    // Category not found, redirect to categories page
    header("Location: wedding_categories_user.php");
    exit();
}

// Initialize filter and sort variables
$sortOption = isset($_GET['sort']) ? $_GET['sort'] : 'price-asc';
$priceFilter = isset($_GET['price']) ? $_GET['price'] : 'all';

if (isset($_GET['q'])) {
    $searchTerm = '%' . $conn->real_escape_string($_GET['q']) . '%';
    $clickedId = isset($_GET['clicked_id']) ? (int)$_GET['clicked_id'] : 0;
    
    $query = "SELECT w.*, wc.category_name 
              FROM wedding_collection w 
              JOIN wedding_categories wc ON w.category_id = wc.id 
              WHERE w.category_id = ? 
              ORDER BY 
                CASE 
                    WHEN w.id = ? THEN 1  -- Clicked product first
                    WHEN w.name LIKE ? THEN 2  -- Then exact matches
                    WHEN w.description LIKE ? THEN 3  -- Then description matches
                    WHEN w.color LIKE ? THEN 4  -- Then color matches
                    ELSE 5
                END,
                w.id DESC";
                
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisss", $categoryId, $clickedId, $searchTerm, $searchTerm, $searchTerm);
} else {
    $query = "SELECT w.*, wc.category_name 
              FROM wedding_collection w 
              JOIN wedding_categories wc ON w.category_id = wc.id 
              WHERE w.category_id = ? 
              ORDER BY w.id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $categoryId);
}

$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['category_name']); ?> - Wedding Collection</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --primary-color: #4e034f;
            --primary-hover: #6c0c6d;
            --white: #fff;
            --gray-light: #eee;
        }

        .container {
            display: flex;
            padding: 20px;
            gap: 30px;
            margin-top: 20px;
        }

        .filter-sidebar {
            width: 280px;
            background-color: var(--primary-color);
            color: var(--white);
            padding: 20px;
            border-radius: 10px;
            position: sticky;
            top: 20px;
            height: fit-content;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .main-content {
            flex: 1;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .product-card {
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            padding: 15px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .product-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .product-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 10px 0;
            color: var(--primary-color);
        }

        .product-card p {
            margin: 5px 0;
            color: #555;
        }

        .product-card .violet-btn {
            margin-top: 10px;
            align-self: center;
            padding: 10px 20px;
            font-size: 14px;
        }

        .violet-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
        }

        .violet-btn:hover {
            background-color: var(--primary-hover);
        }

        .loading-spinner {
            display: none;
            margin: 20px auto;
            width: 40px;
            height: 40px;
            border: 3px solid var(--gray-light);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                padding: 10px;
            }

            .filter-sidebar {
                width: 100%;
            }

            .product-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <div class="filter-sidebar">
            <h2>Filters</h2>
            <form id="filter-form" method="GET" action="">
                <input type="hidden" name="category_id" value="<?php echo $categoryId; ?>">
                <div class="filter-group">
                    <label for="price">Filter by Price:</label>
                    <select id="price" name="price" class="violet-btn" onchange="this.form.submit()">
                        <option value="all" <?php if ($priceFilter == 'all') echo 'selected'; ?>>All</option>
                        <option value="low" <?php if ($priceFilter == 'low') echo 'selected'; ?>>Below ₹1000</option>
                        <option value="medium" <?php if ($priceFilter == 'medium') echo 'selected'; ?>>₹1000 - ₹5000</option>
                        <option value="high" <?php if ($priceFilter == 'high') echo 'selected'; ?>>Above ₹5000</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="sort">Sort By:</label>
                    <select id="sort" name="sort" class="violet-btn" onchange="this.form.submit()">
                        <option value="price-asc" <?php if ($sortOption == 'price-asc') echo 'selected'; ?>>Price: Low to High</option>
                        <option value="price-desc" <?php if ($sortOption == 'price-desc') echo 'selected'; ?>>Price: High to Low</option>
                        <option value="newest" <?php if ($sortOption == 'newest') echo 'selected'; ?>>Newest</option>
                    </select>
                </div>
                <div class="filter-group" style="text-align: center; margin-top: 20px;">
                    <button type="button" class="violet-btn" onclick="clearFilters()">Clear Filters</button>
                </div>
            </form>
        </div>

        <div class="main-content">
            <div id="loading-spinner" class="loading-spinner"></div>
            <div class="product-grid" id="product-grid">
                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <p>No products found in this category.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo htmlspecialchars($product['image'] ?? 'default-product.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 loading="lazy">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="price">₹<?php echo number_format($product['price'], 2); ?></p>
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" 
                               class="violet-btn">View Details</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>

    <script>
        function clearFilters() {
            document.getElementById('price').value = 'all';
            document.getElementById('sort').value = 'price-asc';
            document.getElementById('filter-form').submit();
        }
    </script>
</body>
</html>