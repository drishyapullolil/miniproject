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
    <meta name="description" content="Your Cart - Yards of Grace">
    <title>Your Cart - Yards of Grace</title>
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

        /* Cart Container */
        .cart-container {
            max-width: var(--container-width);
            margin: var(--spacing-lg) auto;
            padding: var(--spacing-md);
        }

        .cart-grid {
            display: grid;
            gap: var(--spacing-lg);
        }

        .cart-item {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            align-items: center;
            gap: var(--spacing-md);
            background: var(--background-light);
            border-radius: var(--border-radius);
            padding: var(--spacing-md);
            box-shadow: var(--shadow);
        }

        .cart-item img {
            width: 100%;
            border-radius: var(--border-radius);
            aspect-ratio: 3/4;
            object-fit: cover;
        }

        .cart-item h3 {
            color: var(--primary-color);
            font-size: 1.25rem;
            line-height: 1.2;
        }

        .cart-item p {
            color: var(--text-light);
        }

        .cart-item .quantity {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .cart-item .quantity input {
            width: 50px;
            padding: var(--spacing-sm);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            text-align: center;
        }

        .cart-item .button-group {
            display: flex;
            gap: var(--spacing-md);
        }

        .cart-item .button {
            padding: var(--spacing-md) var(--spacing-lg);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .cart-item .button-primary {
            background: var(--primary-color);
            color: var(--background-light);
        }

        .cart-item .button-primary:hover {
            background: var(--primary-hover);
        }

        .cart-item .button-remove {
            background: #ff4d4d;
            color: var(--background-light);
        }

        .cart-item .button-remove:hover {
            background: #ff1a1a;
        }

        /* Cart Summary */
        .cart-summary {
            margin-top: var(--spacing-lg);
            padding: var(--spacing-md);
            background: var(--background-light);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: right;
        }

        .cart-summary h2 {
            color: var(--primary-color);
            margin-bottom: var(--spacing-md);
        }

        .cart-summary .button-checkout {
            background: var(--primary-color);
            color: var(--background-light);
            padding: var(--spacing-md) var(--spacing-lg);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .cart-summary .button-checkout:hover {
            background: var(--primary-hover);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .cart-item .button-group {
                justify-content: center;
            }
        }
        </style>
</head>
<body>
    <main class="cart-container">
        <h1>Your Cart</h1>
        <div class="cart-grid">
            <!-- Cart Item 1 -->
            <div class="cart-item">
                <img src="i1.jpg" alt="Light Green Kora Organza Embroidery Saree" loading="lazy">
                <div>
                    <h3>Light Green Kora Organza Embroidery Saree</h3>
                    <p class="product-price" data-price="13630">₹13,630</p>
                    <div class="quantity">
                        <label for="quantity1">Quantity:</label>
                        <input type="number" id="quantity1" name="quantity1" value="1" min="1">
                    </div>
                </div>
                <div class="button-group">
                    <button class="button button-primary" aria-label="Update quantity">UPDATE</button>
                    <button class="button button-remove" aria-label="Remove from cart">REMOVE</button>
                </div>
            </div>

            <!-- Cart Item 2 -->
            <div class="cart-item">
                <img src="i2.jpg" alt="Another Saree" loading="lazy">
                <div>
                    <h3>Another Saree</h3>
                    <p class="product-price" data-price="10000">₹10,000</p>
                    <div class="quantity">
                        <label for="quantity2">Quantity:</label>
                        <input type="number" id="quantity2" name="quantity2" value="1" min="1">
                    </div>
                </div>
                <div class="button-group">
                    <button class="button button-primary" aria-label="Update quantity">UPDATE</button>
                    <button class="button button-remove" aria-label="Remove from cart">REMOVE</button>
                </div>
            </div>

            <!-- Add more cart items as needed -->
        </div>

        <!-- Cart Summary -->
        <div class="cart-summary">
            <h2>Total: <span id="total-price">₹23,630</span></h2>
            <button class="button button-checkout" aria-label="Proceed to checkout">PROCEED TO CHECKOUT</button>
        </div>
    </main>
    <?php include 'footer.php'; ?>

    <script>
        // Function to calculate the total price
        function calculateTotal() {
            let total = 0;

            // Loop through all cart items
            document.querySelectorAll('.cart-item').forEach(item => {
                const price = parseFloat(item.querySelector('.product-price').getAttribute('data-price'));
                const quantity = parseFloat(item.querySelector('input[type="number"]').value);
                total += price * quantity;
            });

            // Update the total price in the cart summary
            document.getElementById('total-price').textContent = `₹${total.toLocaleString()}`;
        }

        // Add event listeners to quantity inputs
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('change', calculateTotal);
        });

        // Calculate the total price on page load
        calculateTotal();
    </script>
</body>
</html>