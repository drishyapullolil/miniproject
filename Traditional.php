<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Set all headers at the top
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Fetch saree details from database with category and subcategory
$saree_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Check if saree exists before including header
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

if (!$stmt->execute()) {
    header("Location: home.php");
    exit();
}

$result = $stmt->get_result();
$saree = $result->fetch_assoc();

if (!$saree) {
    header("Location: home.php");
    exit();
}

// Only include header and output HTML after all possible redirects
include 'header.php';

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
        background-color: #f9f9f9;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }
    
    .breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        padding: var(--spacing-sm) var(--spacing-md);
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
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-lg);
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
            gap: 8px;
            background: none;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 10px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .button-wishlist:hover {
            background: var(--primary-color);
            color: white;
        }

        .button-wishlist.active {
            background: var(--primary-color);
            color: white;
        }

        .button-wishlist svg {
            transition: all 0.3s ease;
        }

        .button-wishlist:hover svg,
        .button-wishlist.active svg {
            transform: scale(1.1);
        }

        @keyframes heartbeat {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .button-wishlist.active svg {
            animation: heartbeat 0.5s;
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
<script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <style>
        .goog-te-gadget-simple {
            background-color: transparent !important;
            border: none !important;
            padding: 0 !important;
            cursor: pointer;
        }

        .goog-te-gadget-simple span {
            color: white !important;
            font-size: 16px;
        }
    </style>
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
                <form method="POST" style="display: inline;">
        <input type="hidden" name="saree_id" value="<?php echo $saree['id']; ?>">
        <?php
        if(isset($_POST['add_to_cart'])) {
            if(isset($_SESSION['user_id'])) {
                $result = addToCart($conn, $_SESSION['user_id'], $_POST['saree_id']);
                if($result['success']) {
                    echo "<div class='success-message'><i class='fa fa-check-circle'></i> " . $result['message'] . " <a href='addtocart.php'>View Cart</a></div>";
                } else {
                    echo "<div class='error-message'><i class='fa fa-exclamation-circle'></i> " . $result['message'] . "</div>";
                }
            } else {
                echo "<div class='error-message'><i class='fa fa-exclamation-circle'></i> Please <a href='login.php'>log in</a> to add items to your cart.</div>";
            }
        }
        ?>
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

                <?php
                // Check if user is logged in and if item is in wishlist
                $isInWishlist = false;
                if (isset($_SESSION['user_id'])) {
                    $checkWishlist = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND saree_id = ?");
                    $checkWishlist->bind_param("ii", $_SESSION['user_id'], $saree_id);
                    $checkWishlist->execute();
                    $isInWishlist = $checkWishlist->get_result()->num_rows > 0;
                }
                ?>
                
                <button 
                    class="button button-wishlist <?php echo $isInWishlist ? 'active' : ''; ?>" 
                    data-id="<?php echo $saree['id']; ?>" 
                    aria-label="<?php echo $isInWishlist ? 'Remove from wishlist' : 'Add to wishlist'; ?>"
                >
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="<?php echo $isInWishlist ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <span class="wishlist-text">
                        <?php echo $isInWishlist ? 'REMOVE FROM WISHLIST' : 'ADD TO WISHLIST'; ?>
                    </span>
                </button>

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
    <?php
    // Create reviews table if it doesn't exist
    $reviewsTable = "CREATE TABLE IF NOT EXISTS reviews (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        product_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        rating INT(1) NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES sarees(id) ON DELETE CASCADE
    )";

    if ($conn->query($reviewsTable) !== TRUE) {
        error_log("Error creating reviews table: " . $conn->error);
    }

    // Handle review submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
        if (isset($_SESSION['user_id'])) {
            $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
            // Using htmlspecialchars instead of deprecated FILTER_SANITIZE_STRING
            $comment = htmlspecialchars($_POST['comment'], ENT_QUOTES, 'UTF-8');
            $user_id = $_SESSION['user_id'];
            
            if ($rating && $comment) {
                $insert_review = "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_review);
                $stmt->bind_param("iiis", $saree_id, $user_id, $rating, $comment);
                
                if ($stmt->execute()) {
                    echo "<script>showNotification('Review submitted successfully!', 'success');</script>";
                } else {
                    echo "<script>showNotification('Error submitting review.', 'error');</script>";
                }
            }
        }
    }
    ?>

    <section class="reviews-section">
        <h2>Share Your Experience</h2>
        <?php
        // Fetch reviews for this product
        $review_query = "SELECT r.*, u.username 
                        FROM reviews r
                        LEFT JOIN users u ON r.user_id = u.id
                        WHERE r.product_id = ?
                        ORDER BY r.created_at DESC";
        
        $stmt = $conn->prepare($review_query);
        $stmt->bind_param("i", $saree_id);
        $stmt->execute();
        $reviews = $stmt->get_result();
        
        if ($reviews->num_rows > 0): ?>
            <div class="reviews-container">
                <?php while($review = $reviews->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <span class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></span>
                            <div class="rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">
                                        <svg width="24" height="24" viewBox="0 0 24 24">
                                            <path d="M12 2l2.4 7.4h7.6l-6 4.6 2.3 7.2-6.3-4.8-6.3 4.8 2.3-7.2-6-4.6h7.6z" fill="currentColor"/>
                                        </svg>
                                    </span>
                                <?php endfor; ?>
                            </div>
                            <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                        </div>
                        <p class="review-content"><?php echo htmlspecialchars($review['comment']); ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="no-reviews">Be the first to review this product!</p>
        <?php endif; ?>

        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="add-review-section">
                <h3>Write Your Review</h3>
                <form method="POST" class="review-form">
                    <div class="rating-input">
                        <label>Select Your Rating</label>
                        <div class="star-rating">
                            <?php for($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>">
                                    <svg width="32" height="32" viewBox="0 0 24 24">
                                        <path d="M12 2l2.4 7.4h7.6l-6 4.6 2.3 7.2-6.3-4.8-6.3 4.8 2.3-7.2-6-4.6h7.6z" fill="currentColor"/>
                                    </svg>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-help">Click a star to rate</span>
                    </div>
                    <div class="comment-input">
                        <label for="comment">Your Review:</label>
                        <textarea id="comment" name="comment" 
                            placeholder="Tell us what you think about this product" 
                            required></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="submit-review">Submit Review</button>
                </form>
            </div>
        <?php else: ?>
            <p class="login-prompt">Please <a href="login.php">sign in</a> to write a review</p>
        <?php endif; ?>
    </section>

    <style>
    .reviews-section {
        padding: 2rem;
        max-width: 800px;
        margin: 0 auto;
        background: #fff;
        border-radius: 10px;
    }

    .reviews-section h2 {
        color: #a000a0;
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .review-card {
        background: #f8f8f8;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .review-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .reviewer-name {
        font-weight: bold;
        color: #333;
    }

    .rating .star {
        color: #ddd;
    }

    .rating .star.filled {
        color: #a000a0;
    }

    .rating .star svg {
        width: 20px;
        height: 20px;
    }

    .review-date {
        color: #666;
        font-size: 0.9rem;
    }

    .review-content {
        color: #444;
        line-height: 1.5;
    }

    .add-review-section {
        margin-top: 2rem;
        padding: 1.5rem;
        background: #f8f8f8;
        border-radius: 8px;
    }

    .review-form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        gap: 0.5rem;
        margin: 1rem 0;
    }

    .star-rating input {
        display: none;
    }

    .star-rating label {
        color: #ddd;
        cursor: pointer;
    }

    .star-rating label:hover,
    .star-rating label:hover ~ label,
    .star-rating input:checked ~ label {
        color: #a000a0;
    }

    .rating-help {
        display: block;
        color: #666;
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }

    .comment-input textarea {
        width: 100%;
        min-height: 100px;
        padding: 0.8rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        resize: vertical;
    }

    .submit-review {
        background: #a000a0;
        color: white;
        padding: 0.8rem 1.5rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
        transition: background-color 0.2s;
    }

    .submit-review:hover {
        background: #800080;
    }

    .login-prompt {
        text-align: center;
        margin: 2rem 0;
        color: #666;
    }

    .login-prompt a {
        color: #a000a0;
        text-decoration: none;
    }

    .no-reviews {
        text-align: center;
        color: #666;
        padding: 1rem;
    }
    </style>






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

    // Stock check and Wishlist functionality
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const stock = <?php echo $saree['stock']; ?>;
                if (stock <= 0) {
                    e.preventDefault();
                    showNotification('Sorry, this item is out of stock', 'error');
                }
            });
        });

        // Wishlist functionality
        const wishlistBtn = document.querySelector('.button-wishlist');
        if (wishlistBtn) {
            wishlistBtn.addEventListener('click', async function() {
                try {
                    const sareeId = this.getAttribute('data-id');
                    const isActive = this.classList.contains('active');
                    const action = isActive ? 'remove' : 'add';

                    // Check if user is logged in
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                        return;
                    <?php endif; ?>

                    // Create form data
                    const formData = new URLSearchParams();
                    formData.append('action', action);
                    formData.append('saree_id', sareeId);

                    // AJAX request to wishlist-api.php
                    const response = await fetch('wishlist-api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData.toString()
                    });

                    let data;
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        data = await response.json();
                    } else {
                        // If response is not JSON, get the text and log it
                        const text = await response.text();
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid server response');
                    }

                    if (data.success) {
                        if (action === 'add') {
                            this.classList.add('active');
                            this.querySelector('svg').setAttribute('fill', 'currentColor');
                            this.querySelector('.wishlist-text').textContent = 'REMOVE FROM WISHLIST';
                            showNotification('Added to wishlist');
                        } else {
                            this.classList.remove('active');
                            this.querySelector('svg').setAttribute('fill', 'none');
                            this.querySelector('.wishlist-text').textContent = 'ADD TO WISHLIST';
                            showNotification('Removed from wishlist');
                        }
                    } else {
                        throw new Error(data.message || 'Operation failed');
                    }
                } catch (error) {
                    console.error('Wishlist Error:', error);
                    showNotification(error.message || 'An error occurred. Please try again.', 'error');
                }
            });
        }
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