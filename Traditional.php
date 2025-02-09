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
    <meta name="description" content="Light Green Kora Organza Embroidery Saree - Traditional Indian Saree with detailed embroidery work">
    <title>Light Green Kora Organza Embroidery Saree - Yards of Grace</title>
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
                    src="i1.jpg" 
                    alt="Light Green Kora Organza Embroidery Saree - Full View" 
                    class="product-image"
                    loading="lazy"
                >
            </section>

            <section class="product-info">
                <h1 class="product-title">Light Green Kora Organza Embroidery Saree</h1>
                <p class="product-price" aria-label="Price">₹13,630</p>
                <p class="product-code">Product Code: T631216</p>
                <p class="product-description">
                    Feel fresh and fabulous in this light green embroidered organza saree, and the printed blouse adds just the right amount of flair!
                </p>

                <div class="button-group">
                    <button class="button button-primary" aria-label="Add to cart"><a href="addtocart.php">ADD TO CART</a></button>
                    <button class="button button-primary" aria-label="Buy now">BUY NOW</button>
                </div>

                <button class="button button-wishlist" aria-label="Add to wishlist">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    ADD TO WISHLIST
                </button>

                <div class="product-details">
                    <div class="detail-item">
                        <strong>Measurements:</strong>
                        <span>Saree: 5.5 Mtrs; Blouse: 0.80 Mtrs</span>
                    </div>
                    <div class="detail-item">
                        <strong>Wash Care:</strong>
                        <span>Dry Wash</span>
                    </div>
                    <div class="detail-item">
                        <strong>Shipping Time:</strong>
                        <span>5-7 business days</span>
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