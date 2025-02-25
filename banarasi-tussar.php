<?php
// Debugging: Print the query parameters
echo "<pre>";
print_r($_GET);
echo "</pre>";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db.php';

// Fetch category or subcategory ID from the URL
$categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
$subcategoryId = isset($_GET['subcategory_id']) ? intval($_GET['subcategory_id']) : null;

// Validate input
if (!$categoryId && !$subcategoryId) {
    // Redirect to a default page or display a message
    header("Location: categories.php"); // Redirect to a categories listing page
    exit();
}

// Fetch products based on category or subcategory
if ($categoryId) {
    $query = "SELECT 
                s.id, 
                s.saree_name, 
                s.price, 
                s.stock, 
                s.color, 
                s.image, 
                c.category_name,
                IFNULL(sc.subcategory_name, 'N/A') AS subcategory_name,
                COALESCE(ps.material, 'Not specified') AS material,
                COALESCE(ps.style, 'Not specified') AS style
              FROM sarees s 
              LEFT JOIN categories c ON s.category_id = c.id
              LEFT JOIN subcategories sc ON s.subcategory_id = sc.id
              LEFT JOIN product_specifications ps ON s.id = ps.saree_id
              WHERE s.category_id = ?
              ORDER BY s.id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $categoryId);
} else {
    $query = "SELECT 
                s.id, 
                s.saree_name, 
                s.price, 
                s.stock, 
                s.color, 
                s.image, 
                c.category_name,
                IFNULL(sc.subcategory_name, 'N/A') AS subcategory_name,
                COALESCE(ps.material, 'Not specified') AS material,
                COALESCE(ps.style, 'Not specified') AS style
              FROM sarees s 
              LEFT JOIN categories c ON s.category_id = c.id
              LEFT JOIN subcategories sc ON s.subcategory_id = sc.id
              LEFT JOIN product_specifications ps ON s.id = ps.saree_id
              WHERE s.subcategory_id = ?
              ORDER BY s.id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subcategoryId);
}

$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);

// Set the page title
if ($categoryId) {
    $pageTitle = $products[0]['category_name'] ?? "Category Products";
} else {
    $pageTitle = $products[0]['subcategory_name'] ?? "Subcategory Products";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Yards of Grace</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
            padding: 2rem;
        }

        .product-card {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .product-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }

        .product-info {
            padding: 1rem;
        }

        .product-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .product-price {
            font-weight: bold;
            color: #8d0f8f;
        }

        .no-products {
            text-align: center;
            padding: 3rem;
            font-size: 1.2rem;
            color: #666;
        }

        .out-of-stock {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main>
        <?php if (empty($products)): ?>
            <div class="no-products">
                <p>No products found in this category.</p>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <a href="product.php?id=<?php echo $product['id']; ?>" class="product-card">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['saree_name']); ?>" 
                             class="product-image"
                             loading="lazy">
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['saree_name']); ?></h3>
                            <p class="product-price">₹<?php echo number_format($product['price'], 2); ?></p>
                            <p><strong>Color:</strong> <?php echo htmlspecialchars($product['color']); ?></p>
                            <p><strong>Material:</strong> <?php echo htmlspecialchars($product['material'] ?? 'N/A'); ?></p>
                            <p><strong>Style:</strong> <?php echo htmlspecialchars($product['style'] ?? 'N/A'); ?></p>
                            <p><strong>Subcategory:</strong> <?php echo htmlspecialchars($product['subcategory_name'] ?? 'N/A'); ?></p>
                            <?php if ($product['stock'] <= 0): ?>
                                <p class="out-of-stock">Out of Stock</p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>