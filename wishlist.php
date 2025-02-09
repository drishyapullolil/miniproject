<?php
// Enhanced security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Your Wishlist - Yards of Grace">
    <title>Your Wishlist - Yards of Grace</title>
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

        /* Wishlist Container */
        .wishlist-container {
            max-width: var(--container-width);
            margin: var(--spacing-lg) auto;
            padding: var(--spacing-md);
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: var(--spacing-lg);
        }

        .wishlist-item {
            background: var(--background-light);
            border-radius: var(--border-radius);
            padding: var(--spacing-md);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .wishlist-item img {
            width: 100%;
            border-radius: var(--border-radius);
            aspect-ratio: 3/4;
            object-fit: cover;
        }

        .wishlist-item h3 {
            color: var(--primary-color);
            font-size: 1.25rem;
            line-height: 1.2;
        }

        .wishlist-item p {
            color: var(--text-light);
        }

        .wishlist-item .button-group {
            display: flex;
            gap: var(--spacing-md);
            margin-top: auto;
        }

        .wishlist-item .button {
            padding: var(--spacing-md) var(--spacing-lg);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .wishlist-item .button-primary {
            background: var(--primary-color);
            color: var(--background-light);
        }

        .wishlist-item .button-primary:hover {
            background: var(--primary-hover);
        }

        .wishlist-item .button-remove {
            background: #ff4d4d;
            color: var(--background-light);
        }

        .wishlist-item .button-remove:hover {
            background: #ff1a1a;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="wishlist-container">
        <h1>Your Wishlist</h1>
        <div class="wishlist-grid">
            <!-- Wishlist Item 1 -->
            <div class="wishlist-item">
                <img src="i1.jpg" alt="Light Green Kora Organza Embroidery Saree" loading="lazy">
                <h3>Light Green Kora Organza Embroidery Saree</h3>
                <p class="product-price">₹13,630</p>
                <div class="button-group">
                    <button class="button button-primary" aria-label="Add to cart">ADD TO CART</button>
                    <button class="button button-remove" aria-label="Remove from wishlist">REMOVE</button>
                </div>
            </div>

            <!-- Wishlist Item 2 -->
            <div class="wishlist-item">
                <img src="i2.jpg" alt="Another Saree" loading="lazy">
                <h3>Another Saree</h3>
                <p class="product-price">₹10,000</p>
                <div class="button-group">
                    <button class="button button-primary" aria-label="Add to cart">ADD TO CART</button>
                    <button class="button button-remove" aria-label="Remove from wishlist">REMOVE</button>
                </div>
            </div>

            <!-- Add more wishlist items as needed -->
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>