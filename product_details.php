<?php
require_once 'db.php';

// Enhanced security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

include 'header.php';

// Fetch wedding product details from database
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 1; // Default to ID 1 if none provided

$product_query = "SELECT 
                    wp.*, 
                    COALESCE(ws.blouse_details, 'Not specified') AS blouse_details, 
                    COALESCE(ws.saree_length, 0) AS saree_length, 
                    COALESCE(ws.blouse_length, 0) AS blouse_length, 
                    COALESCE(ws.wash_care, 'Standard dry clean only') AS wash_care, 
                    COALESCE(ws.additional_details, wp.description) AS full_description,
                    wc.category_name
                  FROM wedding_products wp 
                  LEFT JOIN wedding_specifications ws ON wp.id = ws.product_id 
                  LEFT JOIN wedding_categories wc ON wp.wedding_category_id = wc.id
                  WHERE wp.id = ?";

$stmt = $conn->prepare($product_query);
$stmt->bind_param("i", $product_id);

// Add error checking for query execution
if (!$stmt->execute()) {
    error_log("Query execution failed: " . $stmt->error);
    die("Error loading product details");
}

$result = $stmt->get_result();

// Debug information
if ($result === false) {
    error_log("Result fetch failed: " . $conn->error);
    die("Error fetching product details");
}

$product = $result->fetch_assoc();

// Debug output
if ($product) {
    error_log("Product ID: " . $product['id']);
} else {
    error_log("No product found with ID: " . $product_id);
}

// Check if product exists with more detailed error handling
if (!$product) {
    error_log("Product not found with ID: " . $product_id);
    header("Location: home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($product['description']); ?>">
    <title><?php echo htmlspecialchars($product['name']); ?> - Wedding Collection</title>
    <style>
        /* Add your CSS styles here, similar to Traditional.php */
        :root {
            --primary-color: #4e034f;
            --primary-hover: #6c0c6d;
            --text-color: #333;
            --text-light: #666;
            --background-light: #fff;
            --border-color: #eee;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --border-radius: 10px;
            --container-width: 1200px;
            --shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
        }

        .product-container {
            max-width: var(--container-width);
            margin: var(--spacing-lg) auto;
            padding: var(--spacing-md);
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-lg);
        }

        .product-gallery {
            position: sticky;
            top: var(--spacing-lg);
        }

        .product-image {
            width: 100%;
            border-radius: var(--border-radius);
            aspect-ratio: 3/4;
            object-fit: cover;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .product-title {
            color: var(--primary-color);
            font-size: 2rem;
            line-height: 1.2;
        }

        .product-price {
            font-size: 1.5rem;
            color: var(--primary-color);
            font-weight: bold;
        }

        .button-group {
            display: flex;
            gap: var(--spacing-md);
            margin: var(--spacing-md) 0;
        }

        .button {
            padding: var(--spacing-md) var(--spacing-lg);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .button-primary {
            background: var(--primary-color);
            color: var(--background-light);
        }

        .button-primary:hover {
            background: var(--primary-hover);
        }

        .product-details {
            display: grid;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-lg);
        }

        .detail-item {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: var(--spacing-sm);
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .product-gallery {
                position: static;
            }
        }
    </style>
</head>
<body>
    <main class="product-container">
        <div class="product-grid">
            <section class="product-gallery" aria-label="Product images">
                <img 
                    src="<?php echo htmlspecialchars($product['image']); ?>" 
                    alt="<?php echo htmlspecialchars($product['name']); ?>" 
                    class="product-image"
                    loading="lazy"
                >
            </section>
            <section class="product-info">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="product-price" aria-label="Price">₹<?php echo number_format($product['price'], 2); ?></p>
                <p class="product-description">
                    <?php echo htmlspecialchars($product['full_description'] ?? $product['description']); ?>
                </p>

                <div class="button-group">
                    <form method="POST" action="addtocart.php" style="display: inline;">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <button type="submit" name="add_to_cart" class="button button-primary" aria-label="Add to cart">
                            ADD TO CART
                        </button>
                    </form>
                    <form method="POST" action="buynow.php" style="display: inline;">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <button type="submit" name="buy_now" class="button button-primary" aria-label="Buy now">
                            BUY NOW
                        </button>
                    </form>
                </div>

                <div class="product-details">
                    <div class="detail-item">
                        <strong>Category:</strong>
                        <span><?php echo htmlspecialchars($product['category_name'] ?? 'Not specified'); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Blouse Details:</strong>
                        <span><?php echo htmlspecialchars($product['blouse_details']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Measurements:</strong>
                        <span>
                            Saree: <?php echo number_format($product['saree_length'], 2); ?> Mtrs; 
                            Blouse: <?php echo number_format($product['blouse_length'], 2); ?> Mtrs
                        </span>
                    </div>
                    <div class="detail-item">
                        <strong>Wash Care:</strong>
                        <span><?php echo htmlspecialchars($product['wash_care']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Stock:</strong>
                        <span><?php echo (int)($product['stock'] ?? 0); ?> pieces available</span>
                    </div>
                    <div class="detail-item">
                        <strong>Return Policy:</strong>
                        <span>Please note that once the falls or blouse is stitched, we will be unable to exchange the product.</span>
                    </div>
                    <div class="detail-item">
                        <strong>Note:</strong>
                        <span>Product colour may slightly vary due to photographic lighting sources or your device settings.</span>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>