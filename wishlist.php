<?php
// Enhanced security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

session_start();
include 'config.php'; // Database connection
include 'functions.php'; // Include wishlist functions
include 'header.php';

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=wishlist.php");
    exit;
}

// Get user's wishlist items
$userId = $_SESSION['user_id'];
$wishlistItems = getUserWishlist($conn, $userId);

// Define discount rules
$discountPercent = 10; // 10% discount
$discountThreshold = 10000; // Apply discount if total is over ₹10,000
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
            --discount-color: #e63946;
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

        .wishlist-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
        }

        .wishlist-empty {
            text-align: center;
            padding: var(--spacing-lg);
            background: var(--background-light);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .wishlist-empty p {
            margin-bottom: var(--spacing-md);
        }

        .wishlist-empty .button-primary {
            display: inline-block;
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--primary-color);
            color: var(--background-light);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .wishlist-empty .button-primary:hover {
            background: var(--primary-hover);
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
            position: relative;
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
            width: 100%;
            text-align: center;
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

        /* Discount badge */
        .discount-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--discount-color);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            z-index: 10;
        }

        /* Price styles */
        .product-price {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .original-price {
            text-decoration: line-through;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .discounted-price {
            color: var(--discount-color);
            font-weight: bold;
        }

        /* Quantity selector styles */
        .quantity-selector {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            max-width: 120px;
        }

        .quantity-btn {
            background-color: #f5f5f5;
            border: none;
            color: var(--text-color);
            width: 36px;
            height: 36px;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .quantity-btn:hover {
            background-color: #e0e0e0;
        }

        .quantity-input {
            width: 48px;
            height: 36px;
            text-align: center;
            border: none;
            border-left: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
            font-size: 14px;
        }

        .quantity-input::-webkit-inner-spin-button, 
        .quantity-input::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }

        .quantity-input {
            -moz-appearance: textfield;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }

        .toast.show {
            opacity: 1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .wishlist-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .wishlist-item .button-group {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
            }
        }

        .wishlist-checkout {
            margin-top: 30px;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
        }

        .wishlist-total {
            margin-bottom: 20px;
        }

        .total-amount {
            font-size: 24px;
            color: var(--primary-color);
            font-weight: bold;
        }

        .checkout-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 30px;
            font-size: 18px;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            border: none;
            border-radius: var(--border-radius);
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .checkout-button:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .checkout-button i {
            font-size: 20px;
        }

        /* New discount summary styles */
        .discount-summary {
            margin: 15px 0;
            padding: 12px;
            background-color: #f8f8f8;
            border-radius: var(--border-radius);
            text-align: left;
        }

        .discount-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }

        .discount-value {
            color: var(--discount-color);
            font-weight: bold;
        }

        .final-total {
            border-top: 1px solid #ddd;
            margin-top: 10px;
            padding-top: 10px;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .discount-notice {
            margin-top: 15px;
            font-size: 0.9rem;
            color: var(--discount-color);
        }

        @media (max-width: 768px) {
            .wishlist-checkout {
                margin-top: 20px;
                padding: 15px;
            }

            .checkout-button {
                padding: 12px 24px;
                font-size: 16px;
            }
        }

        .continue-shopping,
        .explore-collection {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--primary-color);
            color: white;
        }

        .continue-shopping:hover,
        .explore-collection:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .continue-shopping i,
        .explore-collection i {
            font-size: 16px;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .continue-shopping,
            .explore-collection {
                padding: 10px 20px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <main class="wishlist-container">
        <div class="wishlist-header">
            <h1>Your Wishlist (<?php echo count($wishlistItems); ?> items)</h1>
            <?php if (count($wishlistItems) > 0): ?>
            <button onclick="window.location.href='home.php'" class="button-primary continue-shopping">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </button>
            <?php endif; ?>
        </div>
        
        <?php if (count($wishlistItems) == 0): ?>
        <div class="wishlist-empty">
            <h2>Your wishlist is empty</h2>
            <p>Add items to your wishlist to keep track of products you love!</p>
            <button onclick="window.location.href='home.php'" class="button-primary explore-collection">
                <i class="fas fa-shopping-bag"></i> Explore Our Collection
            </button>
        </div>
        <?php else: ?>
        <div class="wishlist-grid">
            <?php foreach ($wishlistItems as $item): 
                // Calculate discount
                $discountAmount = 0;
                $finalPrice = $item['price'];
                
                // Apply item-specific discounts if needed (e.g., certain products have special discounts)
                if (isset($item['discount']) && $item['discount'] > 0) {
                    $discountAmount = ($item['price'] * $item['discount']) / 100;
                    $finalPrice = $item['price'] - $discountAmount;
                }
                
                // Default quantity (can be retrieved from database if stored)
                $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
            ?>
            <div class="wishlist-item" data-id="<?php echo $item['id']; ?>">
                <?php if (isset($item['discount']) && $item['discount'] > 0): ?>
                <div class="discount-badge"><?php echo $item['discount']; ?>% OFF</div>
                <?php endif; ?>
                
                <img 
                    src="<?php echo htmlspecialchars($item['image']); ?>" 
                    alt="<?php echo htmlspecialchars($item['name']); ?>" 
                    class="product-image"
                    loading="lazy"
                >
                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                
                <div class="product-price">
                    <?php if (isset($item['discount']) && $item['discount'] > 0): ?>
                        <span class="original-price">₹<?php echo number_format($item['price'], 2); ?></span>
                        <span class="discounted-price">₹<?php echo number_format($finalPrice, 2); ?></span>
                    <?php else: ?>
                        <span>₹<?php echo number_format($item['price'], 2); ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Quantity selector -->
                <div class="quantity-wrapper">
                    <label for="quantity-<?php echo $item['id']; ?>">Quantity:</label>
                    <div class="quantity-selector">
                        <button type="button" class="quantity-btn decrease-quantity" aria-label="Decrease quantity">-</button>
                        <input 
                            type="number" 
                            id="quantity-<?php echo $item['id']; ?>" 
                            class="quantity-input" 
                            min="1" 
                            max="10" 
                            value="<?php echo $quantity; ?>" 
                            data-id="<?php echo $item['id']; ?>" 
                            data-price="<?php echo $finalPrice; ?>"
                        >
                        <button type="button" class="quantity-btn increase-quantity" aria-label="Increase quantity">+</button>
                    </div>
                </div>
                
                <div class="button-group">
                    <button class="button button-primary add-to-cart" data-id="<?php echo $item['id']; ?>" data-quantity="1" aria-label="Add to cart">ADD TO CART</button>
                    <button class="button button-remove remove-from-wishlist" data-id="<?php echo $item['id']; ?>" aria-label="Remove from wishlist">REMOVE</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Add Proceed to Checkout section with discounts -->
        <div class="wishlist-checkout">
            <div class="wishlist-total">
                <h3>Wishlist Summary</h3>
                
                <?php
                $subtotal = 0;
                $totalDiscount = 0;
                $finalTotal = 0;
                
                foreach ($wishlistItems as $item) {
                    $itemPrice = $item['price'];
                    $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
                    $subtotal += $itemPrice * $quantity;
                    
                    // Apply item-specific discounts
                    if (isset($item['discount']) && $item['discount'] > 0) {
                        $itemDiscount = ($itemPrice * $item['discount'] / 100) * $quantity;
                        $totalDiscount += $itemDiscount;
                    }
                }
                
                // Apply additional bulk discount if eligible
                $bulkDiscount = 0;
                if ($subtotal >= $discountThreshold) {
                    $bulkDiscount = ($subtotal * $discountPercent) / 100;
                    $totalDiscount += $bulkDiscount;
                }
                
                $finalTotal = $subtotal - $totalDiscount;
                ?>
                
                <div class="discount-summary">
                    <div class="discount-row">
                        <span>Subtotal:</span>
                        <span class="subtotal-value">₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <?php if ($totalDiscount > 0): ?>
                        <?php if (isset($bulkDiscount) && $bulkDiscount > 0): ?>
                        <div class="discount-row">
                            <span>Bulk Discount (<?php echo $discountPercent; ?>%):</span>
                            <span class="bulk-discount-value discount-value">-₹<?php echo number_format($bulkDiscount, 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="discount-row">
                            <span>Item Discounts:</span>
                            <span class="item-discount-value discount-value">-₹<?php echo number_format($totalDiscount - $bulkDiscount, 2); ?></span>
                        </div>
                        
                        <div class="discount-row final-total">
                            <span>Final Total:</span>
                            <span class="final-total-value">₹<?php echo number_format($finalTotal, 2); ?></span>
                        </div>
                        
                        <div class="discount-notice">
                            You saved <span class="total-saved">₹<?php echo number_format($totalDiscount, 2); ?></span> 
                            (<span class="saved-percentage"><?php echo round(($totalDiscount/$subtotal)*100); ?></span>%)
                        </div>
                    <?php else: ?>
                        <div class="discount-row final-total">
                            <span>Total:</span>
                            <span class="final-total-value">₹<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        
                        <?php if ($subtotal > 0 && $subtotal < $discountThreshold): ?>
                        <div class="discount-notice">
                            Add ₹<?php echo number_format($discountThreshold - $subtotal, 2); ?> more to get <?php echo $discountPercent; ?>% off!
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <form action="buynow.php" method="POST" class="checkout-form">
                <?php foreach ($wishlistItems as $item): ?>
                    <input type="hidden" name="saree_ids[]" value="<?php echo $item['id']; ?>">
                    <input type="hidden" name="quantities[]" id="checkout-quantity-<?php echo $item['id']; ?>" value="1" class="checkout-quantity">
                <?php endforeach; ?>
                <button type="submit" name="checkout_wishlist" class="button-primary checkout-button">
                    <i class="fas fa-shopping-cart"></i> Proceed to Checkout
                </button>
            </form>
        </div>
        <?php endif; ?>
    </main>
    
    <div id="toast" class="toast"></div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to show toast notifications
            function showToast(message, duration = 3000) {
                const toast = document.getElementById('toast');
                toast.textContent = message;
                toast.classList.add('show');
                
                setTimeout(() => {
                    toast.classList.remove('show');
                }, duration);
            }
            
            // Function to update quantity (both UI and backend)
            function updateQuantity(inputElement, newQuantity) {
                const sareeId = inputElement.getAttribute('data-id');
                const maxQuantity = parseInt(inputElement.getAttribute('max'));
                
                // Ensure quantity is within valid range
                newQuantity = Math.max(1, Math.min(newQuantity, maxQuantity));
                
                // Update input value
                inputElement.value = newQuantity;
                
                // Update hidden checkout input for this item
                const checkoutQuantityInput = document.getElementById(`checkout-quantity-${sareeId}`);
                if (checkoutQuantityInput) {
                    checkoutQuantityInput.value = newQuantity;
                }
                
                // Update ADD TO CART button data-quantity attribute
                const addToCartButton = inputElement.closest('.wishlist-item').querySelector('.add-to-cart');
                if (addToCartButton) {
                    addToCartButton.setAttribute('data-quantity', newQuantity);
                }
                
                // Update price totals
                updateTotals();
                
                // Save quantity to database (optional - can be implemented as needed)
                saveQuantityToDatabase(sareeId, newQuantity);
            }
            
            // Function to save quantity to database via AJAX
            function saveQuantityToDatabase(sareeId, quantity) {
                // Optional: AJAX request to save quantity
                fetch('wishlist-api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_quantity&saree_id=${sareeId}&quantity=${quantity}`
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Error updating quantity:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
            
            // Function to calculate and update all price totals
            function updateTotals() {
                let subtotal = 0;
                let itemDiscountTotal = 0;
                let discountThreshold = 10000; // Same as PHP value
                let discountPercent = 10; // Same as PHP value
                
                // Calculate totals based on current quantities
                document.querySelectorAll('.quantity-input').forEach(input => {
                    const sareeId = input.getAttribute('data-id');
                    const quantity = parseInt(input.value);
                    const itemElement = input.closest('.wishlist-item');
                    const price = parseFloat(input.getAttribute('data-price'));
                    
                    // Get item discount if available
                    const discountBadge = itemElement.querySelector('.discount-badge');
                    let itemDiscountPercent = 0;
                    if (discountBadge) {
                        itemDiscountPercent = parseFloat(discountBadge.textContent);
                    }
                    
                    // Calculate item subtotal and discount
                    const originalPrice = price / (1 - (itemDiscountPercent / 100));
                    const itemSubtotal = originalPrice * quantity;
                    const itemDiscount = itemDiscountPercent > 0 ? (originalPrice * itemDiscountPercent / 100) * quantity : 0;
                    
                    subtotal += itemSubtotal;
                    itemDiscountTotal += itemDiscount;
                });
                
                // Calculate bulk discount if applicable
                let bulkDiscount = 0;
                if (subtotal >= discountThreshold) {
                    bulkDiscount = (subtotal * discountPercent) / 100;
                }
                
                const totalDiscount = itemDiscountTotal + bulkDiscount;
                const finalTotal = subtotal - totalDiscount;
                
                // Update summary display
                const subtotalElement = document.querySelector('.subtotal-value');
                if (subtotalElement) {
                    subtotalElement.textContent = `₹${subtotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`;
                }
                
                const bulkDiscountElement = document.querySelector('.bulk-discount-value');
                if (bulkDiscountElement) {
                    bulkDiscountElement.textContent = `-₹${bulkDiscount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`;
                }
                
                const itemDiscountElement = document.querySelector('.item-discount-value');
                if (itemDiscountElement) {
                    itemDiscountElement.textContent = `-₹${(totalDiscount - bulkDiscount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`;
                }
                
                const finalTotalElement = document.querySelector('.final-total-value');
                if (finalTotalElement) {
                    finalTotalElement.textContent = `₹${finalTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`;
                }
                
                const totalSavedElement = document.querySelector('.total-saved');
                if (totalSavedElement) {
                    totalSavedElement.textContent = `₹${totalDiscount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`;
                }
                
                const savedPercentageElement = document.querySelector('.saved-percentage');
                if (savedPercentageElement) {
                    savedPercentageElement.textContent = `${Math.round((totalDiscount/subtotal)*100)}`;
                }
                
                // Update discount notice if needed
                const discountNotice = document.querySelector('.discount-notice');
                if (discountNotice && subtotal < discountThreshold) {
                    discountNotice.textContent = `Add ₹${(discountThreshold - subtotal).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")} more to get ${discountPercent}% off!`;
                }
            }
            
            // Event listeners for quantity buttons
            document.querySelectorAll('.decrease-quantity').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.parentElement.querySelector('.quantity-input');
                    const currentQuantity = parseInt(input.value);
                    updateQuantity(input, currentQuantity - 1);
                });
            });
            
            document.querySelectorAll('.increase-quantity').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.parentElement.querySelector('.quantity-input');
                    const currentQuantity = parseInt(input.value);
                    updateQuantity(input, currentQuantity + 1);
                });
            });
            
            // Event listener for quantity input change
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', function() {
                    let quantity = parseInt(this.value);
                    if (isNaN(quantity) || quantity < 1) {
                        quantity = 1;
                    }
                    updateQuantity(this, quantity);
                });
            });
            
            // Initialize totals on page load
            updateTotals();
            
            // Add to cart functionality (updated to include quantity)
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const sareeId = this.getAttribute('data-id');
                    const quantityInput = document.getElementById(`quantity-${sareeId}`);
                    const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
                    
                    // AJAX request to add to cart with quantity
                    fetch('cart-api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=add&saree_id=${sareeId}&quantity=${quantity}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(`Added ${quantity} item(s) to cart`);
                            // Update cart count in header if you have one
                        } else {
                            showToast(data.message);
                        }
                    })
                    .catch(error => {
                        showToast('An error occurred. Please try again.');
                        console.error('Error:', error);
                    });
                });
            });
            
            // Remove from wishlist functionality (unchanged)
            document.querySelectorAll('.remove-from-wishlist').forEach(button => {
                button.addEventListener('click', function() {
                    const sareeId = this.getAttribute('data-id');
                    const itemElement = this.closest('.wishlist-item');
                    
                    // AJAX request to remove from wishlist
                    fetch('wishlist-api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=remove&saree_id=${sareeId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Removed from wishlist');
                            
                            // Remove the item from the UI
                            itemElement.style.opacity = '0';
                            setTimeout(() => {
                                itemElement.remove();
                                
                                // Update count in header
                                const wishlistCount = document.querySelectorAll('.wishlist-item').length;
                                document.querySelector('.wishlist-header h1').textContent = `Your Wishlist (${wishlistCount} items)`;
                                
                                // Show empty state if no items left
                                if (wishlistCount === 0) {
                                    location.reload(); // Reload to show empty state
                                } else {
                                    // Update totals after removing item
                                    updateTotals();
                                }
                            }, 300);
                        } else {
                            showToast(data.message);
                        }
                    })
                    .catch(error => {
                        showToast('An error occurred. Please try again.');
                        console.error('Error:', error);
                    });
                });
            });
        });
    </script>
    
    <?php include 'footer.php'; ?>
</body>
</html>