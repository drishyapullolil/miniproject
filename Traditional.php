<?php
require_once 'db.php';

// Enhanced security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

include 'header.php';

// Fetch saree details from database with category and subcategory
$saree_id = isset($_GET['id']) ? (int)$_GET['id'] : 1; // Default to ID 1 if none provided

$saree_query = "SELECT 
                    s.*, 
                    COALESCE(ps.material, 'Not specified') AS material, 
                    COALESCE(ps.style, 'Not specified') AS style, 
                    COALESCE(ps.saree_length, 0) AS saree_length, 
                    COALESCE(ps.blouse_length, 0) AS blouse_length, 
                    COALESCE(ps.wash_care, 'Not specified') AS wash_care, 
                    COALESCE(ps.description, s.description) AS description,
                    c.category_name,
                    COALESCE(sc.subcategory_name, 'N/A') AS subcategory_name,
                    CASE WHEN ps.id IS NULL THEN 0 ELSE 1 END as has_specifications
                FROM sarees s 
                LEFT JOIN product_specifications ps ON s.id = ps.saree_id 
                LEFT JOIN categories c ON s.category_id = c.id
                LEFT JOIN subcategories sc ON s.subcategory_id = sc.id
                WHERE s.id = ?";

$stmt = $conn->prepare($saree_query);
$stmt->bind_param("i", $saree_id);

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

$saree = $result->fetch_assoc();

// Debug output
if ($saree) {
    error_log("Saree ID: " . $saree['id'] . " Has Specs: " . $saree['has_specifications']);
} else {
    error_log("No saree found with ID: " . $saree_id);
}

// Check if saree exists with more detailed error handling
if (!$saree) {
    error_log("Saree not found with ID: " . $saree_id);
    header("Location: home.php");
    exit();
}
// Add this temporarily for debugging
$debug_query = "SELECT * FROM product_specifications WHERE saree_id = ?";
$debug_stmt = $conn->prepare($debug_query);
$debug_stmt->bind_param("i", $saree_id);
$debug_stmt->execute();
$debug_result = $debug_stmt->get_result();
error_log("Number of specifications found for saree $saree_id: " . $debug_result->num_rows);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($saree['description']); ?>">
    <title><?php echo htmlspecialchars($saree['name']); ?> - Yards of Grace</title>
    <?php
// Get the necessary data for breadcrumbs - place this after fetching the saree details
$categoryId = $saree['category_id'] ?? null;
$subcategoryId = $saree['subcategory_id'] ?? null;
$categoryName = $saree['category_name'] ?? '';
$subcategoryName = $saree['subcategory_name'] ?? '';

// Check if subcategory is valid (not empty or N/A)
$hasValidSubcategory = $subcategoryId && $subcategoryName && $subcategoryName != 'N/A';

// Debug information
error_log("Breadcrumb data - Category ID: $categoryId, Name: $categoryName, Subcategory ID: $subcategoryId, Name: $subcategoryName, Has Valid Subcategory: " . ($hasValidSubcategory ? 'Yes' : 'No'));
?>

<!-- Add this to your HTML right after the opening <body> tag and before the main content -->
<div class="breadcrumb-container">
    <div class="breadcrumb">
        <a href="home.php">Home</a>
        <span class="separator">></span>
        
        <?php if ($hasValidSubcategory): ?>
            <!-- If there is a valid subcategory, show only the subcategory -->
            <a href="categories_user.php?subcategory_id=<?php echo $subcategoryId; ?>"><?php echo htmlspecialchars($subcategoryName); ?></a>
        <?php elseif ($categoryId && $categoryName): ?>
            <!-- If no subcategory, show just the category -->
            <a href="categories_user.php?category_id=<?php echo $categoryId; ?>"><?php echo htmlspecialchars($categoryName); ?></a>
        <?php endif; ?>
        
        <span class="separator">></span>
        <span class="current"><?php echo htmlspecialchars($saree['name']); ?></span>
    </div>
</div>

<!-- Add this to your CSS styles section -->
<style>
    .breadcrumb-container {
        max-width: var(--container-width);
        margin: 0 auto;
        padding: var(--spacing-md) var(--spacing-md) 0;
    }
    
    .breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        padding: var(--spacing-sm) 0;
        margin-bottom: var(--spacing-md);
        font-size: 0.9rem;
        color: var(--text-light);
    }
    
    .breadcrumb a {
        color: var(--primary-color);
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .breadcrumb a:hover {
        color: var(--primary-hover);
        text-decoration: underline;
    }
    
    .breadcrumb .separator {
        margin: 0 var(--spacing-sm);
        color: var(--text-light);
    }
    
    .breadcrumb .current {
        color: var(--text-color);
        font-weight: 500;
        max-width: 250px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    @media (max-width: 768px) {
        .breadcrumb {
            font-size: 0.8rem;
        }
        
        .breadcrumb .current {
            max-width: 150px;
        }
    }
</style>
    <style>
        /* CSS Reset */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* Variables */
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

        /* Base Styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
        }

        /* Header Styles */
        

        .main-header {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            padding: var(--spacing-md);
            max-width: var(--container-width);
            margin: 0 auto;
            gap: var(--spacing-lg);
        }

        /* Navigation */
        .nav-menu {
            background: var(--background-light);
            border-bottom: 1px solid var(--border-color);
        }

        /* Product Container */
        .product-container {
            max-width: var(--container-width);
            margin: var(--spacing-lg) auto;
            padding: var(--spacing-md);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-lg);
            background: var(--background-light);
            border-radius: var(--border-radius);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow);
        }

        /* Product Images */
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

        /* Product Info */
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

        .product-code {
            color: var(--text-light);
        }

        /* Buttons */
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

        .button-wishlist {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            background: none;
            color: var(--text-light);
            padding: 0;
        }

        .button-wishlist:hover {
            color: var(--primary-color);
        }

        /* Product Details */
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

        /* Responsive Design */
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

            .main-header {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <main class="product-container">
        <div class="product-grid">
            <section class="product-gallery" aria-label="Product images">
                <img 
                    src="<?php echo htmlspecialchars($saree['image']); ?>" 
                    alt="<?php echo htmlspecialchars($saree['name']); ?>" 
                    class="product-image"
                    loading="lazy"
                >
            </section>

            <section class="product-info">
                <h1 class="product-title"><?php echo htmlspecialchars($saree['name']); ?></h1>
                <p class="product-price" aria-label="Price">₹<?php echo number_format($saree['price'], 2); ?></p>
                <p class="product-code">Product Code: <?php echo htmlspecialchars($saree['id']); ?></p>
                <p class="product-description">
                    <?php echo htmlspecialchars($saree['description']); ?>
                </p>

                <div class="button-group">
                    <form method="POST" action="addtocart.php" style="display: inline;">
                        <input type="hidden" name="saree_id" value="<?php echo $saree['id']; ?>">
                        <button type="submit" name="add_to_cart" class="button button-primary" aria-label="Add to cart">
                            ADD TO CART
                        </button>
                    </form>
                    <form method="POST" action="buynow.php" style="display: inline;">
                        <input type="hidden" name="saree_id" value="<?php echo $saree['id']; ?>">
                        <button type="submit" name="buy_now" class="button button-primary" aria-label="Buy now">
                            BUY NOW
                        </button>
                    </form>
                </div>

                <form method="POST" action="wishlist.php" class="wishlist-form">
                    <input type="hidden" name="saree_id" value="<?php echo $saree['id']; ?>">
                    <button type="submit" class="button button-wishlist" aria-label="Add to wishlist">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                        ADD TO WISHLIST
                    </button>
                </form>

                <div class="product-details">
    <div class="detail-item">
        <strong>Material:</strong>
        <span><?php echo htmlspecialchars($saree['material'] ?? 'Not specified'); ?></span>
    </div>
    <div class="detail-item">
        <strong>Style:</strong>
        <span><?php echo htmlspecialchars($saree['style'] ?? 'Not specified'); ?></span>
    </div>
    <div class="detail-item">
        <strong>Measurements:</strong>
        <span>Saree: <?php echo number_format($saree['saree_length'] ?? 0, 2); ?> Mtrs; 
              Blouse: <?php echo number_format($saree['blouse_length'] ?? 0, 2); ?> Mtrs</span>
    </div>
    <div class="detail-item">
        <strong>Color:</strong>
        <span><?php echo htmlspecialchars($saree['color'] ?? 'Not specified'); ?></span>
    </div>
    <div class="detail-item">
        <strong>Wash Care:</strong>
        <span><?php echo htmlspecialchars($saree['wash_care'] ?? 'Not specified'); ?></span>
    </div>
    <div class="detail-item">
        <strong>Stock:</strong>
        <span><?php echo (int)($saree['stock'] ?? 0); ?> pieces available</span>
    </div>
    <?php if ($saree['has_specifications']): ?>
    <div class="detail-item alert">
        <strong>Product Specifications Available</strong>
    </div>
    <?php endif; ?>
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

    <script>
    // Add to cart notification
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Stock check
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const stock = <?php echo $saree['stock']; ?>;
            if (stock <= 0) {
                e.preventDefault();
                showNotification('Sorry, this item is out of stock', 'error');
            }
        });
    });
    </script>

    <style>
    /* Add this to your existing styles */
    .notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 5px;
        color: white;
        z-index: 1000;
        animation: slideIn 0.5s ease-out;
    }

    .notification.success {
        background-color: #4CAF50;
    }

    .notification.error {
        background-color: #f44336;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    </style>
</body>
</html>