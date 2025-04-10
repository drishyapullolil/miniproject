<?php
session_start();
// Enhanced security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include 'header.php';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "yardsofgrace";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user's cart items
// Assuming you have a user session/cookie to identify the current user

$user_id = $_SESSION['user_id'] ?? 0; // Default to 0 if not logged in

// Include database functions
include_once 'db.php'; // This file should contain the wishlist functions

// Process cart actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $item_id = $conn->real_escape_string($_POST['item_id']);
        
        if ($_POST['action'] == 'update' && isset($_POST['quantity'])) {
            $quantity = (int)$_POST['quantity'];
            if ($quantity > 0) {
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND saree_id = ?");
                $stmt->bind_param("iii", $quantity, $user_id, $item_id);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($_POST['action'] == 'remove') {
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND saree_id = ?");
            $stmt->bind_param("ii", $user_id, $item_id);
            $stmt->execute();
            $stmt->close();
        } elseif ($_POST['action'] == 'move_to_wishlist') {
            // First check if it's already in wishlist
            if (!isInWishlist($conn, $user_id, $item_id)) {
                // Add to wishlist
                $stmt = $conn->prepare("INSERT INTO wishlist (user_id, saree_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $user_id, $item_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Remove from cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND saree_id = ?");
            $stmt->bind_param("ii", $user_id, $item_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Redirect to avoid form resubmission
        header("Location: cart.php");
        exit();
    }
}

// Fetch cart items with product details using JOIN
// Fetch cart items with product details using JOIN
$sql = "SELECT c.id as cart_id, c.saree_id, c.quantity, s.name, s.price, 
         s.image as image_url, s.description, cat.category_name, 
         sc.subcategory_name, 0 as discount_percentage
        FROM cart c 
        JOIN sarees s ON c.saree_id = s.id 
        JOIN categories cat ON s.category_id = cat.id
        LEFT JOIN subcategories sc ON s.subcategory_id = sc.id
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

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
            flex-direction: column;
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

        .cart-item .button-wishlist {
            background: #4a90e2;
            color: var(--background-light);
        }

        .cart-item .button-wishlist:hover {
            background: #357abD;
        }

        /* Category and Subcategory tags */
        .product-category {
            display: inline-block;
            background: #f0f0f0;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        /* Price display with discount */
        .price-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
        }

        .original-price {
            text-decoration: line-through;
            color: #999;
        }

        .discount-badge {
            background: #e53935;
            color: white;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-size: 0.8rem;
        }

        /* Empty Cart Message */
        .empty-cart {
            text-align: center;
            padding: var(--spacing-lg);
            background: var(--background-light);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        /* Cart Summary */
        .cart-summary {
            margin-top: var(--spacing-lg);
            padding: var(--spacing-md);
            background: var(--background-light);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .cart-totals {
            margin-bottom: var(--spacing-md);
        }

        .cart-totals table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-totals td {
            padding: 0.5rem 0;
        }

        .cart-totals .total-row {
            font-weight: bold;
            border-top: 1px solid var(--border-color);
            font-size: 1.2rem;
        }

        .cart-totals td:last-child {
            text-align: right;
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
            width: 100%;
            text-align: center;
            display: block;
        }

        .cart-summary .button-checkout:hover {
            background: var(--primary-hover);
        }

        .cart-actions {
            display: flex;
            justify-content: space-between;
            margin-top: var(--spacing-md);
        }

        .continue-shopping {
            text-decoration: none;
            color: var(--primary-color);
            display: inline-block;
            margin-top: var(--spacing-md);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .cart-item .button-group {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }

            .cart-summary {
                text-align: center;
            }

            .cart-totals td:last-child {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <main class="cart-container">
        <h1>Your Cart</h1>
        
        <div class="cart-grid">
            <?php
            if ($result->num_rows > 0) {
                // Display cart items from database
                while($row = $result->fetch_assoc()) {
                    // Calculate discounted price if applicable
                    $original_price = $row['price'];
                    $discount_percentage = $row['discount_percentage'];
                    $final_price = $original_price;
                    
                    if ($discount_percentage > 0) {
                        $final_price = $original_price - ($original_price * $discount_percentage / 100);
                    }
                    
                    // Format prices with thousand separator
                    $formatted_original_price = '₹' . number_format($original_price, 0, '.', ',');
                    $formatted_final_price = '₹' . number_format($final_price, 0, '.', ',');
                    
                    echo '<div class="cart-item" data-item-id="' . $row['saree_id'] . '">';
                    echo '<img src="' . htmlspecialchars($row['image_url'] ?? '') . '" alt="' . htmlspecialchars($row['name']) . '" loading="lazy">';
                    echo '<div>';
                    echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
                    
                    // Display category and subcategory
                    echo '<div>';
                    echo '<span class="product-category">' . htmlspecialchars($row['category_name']) . '</span>';
                    if (!empty($row['subcategory_name'])) {
                        echo '<span class="product-category">' . htmlspecialchars($row['subcategory_name']) . '</span>';
                    }
                    echo '</div>';
                    
                    // Display price with discount if applicable
                    echo '<div class="price-container">';
                    if ($discount_percentage > 0) {
                        echo '<span class="original-price">' . $formatted_original_price . '</span>';
                        echo '<span class="product-price" data-price="' . $final_price . '">' . $formatted_final_price . '</span>';
                        echo '<span class="discount-badge">' . $discount_percentage . '% OFF</span>';
                    } else {
                        echo '<span class="product-price" data-price="' . $final_price . '">' . $formatted_final_price . '</span>';
                    }
                    echo '</div>';
                    
                    // Brief description
                    if (!empty($row['description'])) {
                        $short_desc = strlen($row['description']) > 100 ? substr($row['description'], 0, 97) . '...' : $row['description'];
                        echo '<p class="product-description">' . htmlspecialchars($short_desc) . '</p>';
                    }
                    
                    echo '<div class="quantity">';
                    echo '<form method="post" action="cart.php">';
                    echo '<input type="hidden" name="action" value="update">';
                    echo '<input type="hidden" name="item_id" value="' . $row['saree_id'] . '">';
                    echo '<label for="quantity' . $row['saree_id'] . '">Quantity:</label>';
                    echo '<input type="number" id="quantity' . $row['saree_id'] . '" name="quantity" value="' . $row['quantity'] . '" min="1">';
                    echo '</div>';
                    echo '</div>';
                    echo '<div class="button-group">';
                    echo '<button type="submit" class="button button-primary" aria-label="Update quantity">UPDATE</button>';
                    echo '</form>';
                    
                    echo '<form method="post" action="cart.php" style="display: inline;">';
                    echo '<input type="hidden" name="action" value="remove">';
                    echo '<input type="hidden" name="item_id" value="' . $row['saree_id'] . '">';
                    echo '<button type="submit" class="button button-remove" aria-label="Remove from cart">REMOVE</button>';
                    echo '</form>';
                    
                    echo '<form method="post" action="cart.php" style="display: inline;">';
                    echo '<input type="hidden" name="action" value="move_to_wishlist">';
                    echo '<input type="hidden" name="item_id" value="' . $row['saree_id'] . '">';
                    echo '<button type="submit" class="button button-wishlist" aria-label="Move to wishlist">MOVE TO WISHLIST</button>';
                    echo '</form>';
                    
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                // Display empty cart message
                echo '<div class="empty-cart">';
                echo '<h2>Your cart is empty</h2>';
                echo '<p>Add some beautiful sarees to your cart and they will appear here.</p>';
                
                // Show wishlist items count if any
                $wishlist_count = getWishlistCount($conn, $user_id);
                if ($wishlist_count > 0) {
                    echo '<p>You have ' . $wishlist_count . ' item' . ($wishlist_count > 1 ? 's' : '') . ' in your wishlist.</p>';
                    echo '<a href="wishlist.php" class="button button-primary" style="display: inline-block; margin-top: var(--spacing-md); margin-right: var(--spacing-md);">View Wishlist</a>';
                }
                
                echo '<a href="products.php" class="button button-primary" style="display: inline-block; margin-top: var(--spacing-md);">Continue Shopping</a>';
                echo '</div>';
            }
            ?>
        </div>

        <?php 
        if ($result->num_rows > 0) { 
            // Calculate totals from database records
            $subtotal = 0;
            $total_discount = 0;
            
            // Reset the result pointer to the beginning
            $result->data_seek(0);
            
            while($row = $result->fetch_assoc()) {
                $original_price = $row['price'];
                $discount_percentage = $row['discount_percentage'];
                $quantity = $row['quantity'];
                
                $item_subtotal = $original_price * $quantity;
                $subtotal += $item_subtotal;
                
                if ($discount_percentage > 0) {
                    $discount_amount = $item_subtotal * $discount_percentage / 100;
                    $total_discount += $discount_amount;
                }
            }
            
            $shipping = 0; // Free shipping or calculate based on your logic
            $grand_total = $subtotal - $total_discount + $shipping;
            
            // Format amounts for display
            $formatted_subtotal = '₹' . number_format($subtotal, 0, '.', ',');
            $formatted_discount = '₹' . number_format($total_discount, 0, '.', ',');
            $formatted_shipping = '₹' . number_format($shipping, 0, '.', ',');
            $formatted_grand_total = '₹' . number_format($grand_total, 0, '.', ',');
        ?>
        <!-- Cart Summary -->
        <div class="cart-summary">
            <div class="cart-totals">
                <h2>Order Summary</h2>
                <table>
                    <tr>
                        <td>Subtotal</td>
                        <td id="subtotal"><?php echo $formatted_subtotal; ?></td>
                    </tr>
                    <?php if ($total_discount > 0) { ?>
                    <tr>
                        <td>Discount</td>
                        <td id="discount">-<?php echo $formatted_discount; ?></td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <td>Shipping</td>
                        <td id="shipping"><?php echo $formatted_shipping; ?></td>
                    </tr>
                    <tr class="total-row">
                        <td>Total</td>
                        <td id="total-price"><?php echo $formatted_grand_total; ?></td>
                    </tr>
                </table>
            </div>
            <a href="checkout.php" class="button-checkout" aria-label="Proceed to checkout">PROCEED TO CHECKOUT</a>
            <div class="cart-actions">
                <a href="products.php" class="continue-shopping">Continue Shopping</a>
                <a href="wishlist.php" class="continue-shopping">View Wishlist (<?php echo getWishlistCount($conn, $user_id); ?>)</a>
            </div>
        </div>
        <?php } ?>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        // Function to calculate the total price (client-side updates before form submission)
        function calculateTotal() {
            let subtotal = 0;
            let discount = 0;
            let shipping = 0; // Adjust this logic as needed

            // Loop through all cart items
            document.querySelectorAll('.cart-item').forEach(item => {
                const price = parseFloat(item.querySelector('.product-price').getAttribute('data-price'));
                const quantity = parseFloat(item.querySelector('input[type="number"]').value);
                subtotal += price * quantity;
            });

            // Calculate grand total
            const grandTotal = subtotal - discount + shipping;

            // Update the totals in the cart summary
            const subtotalElement = document.getElementById('subtotal');
            const discountElement = document.getElementById('discount');
            const totalElement = document.getElementById('total-price');
            
            if (subtotalElement) {
                subtotalElement.textContent = `₹${subtotal.toLocaleString()}`;
            }
            
            if (discountElement) {
                discountElement.textContent = `-₹${discount.toLocaleString()}`;
            }
            
            if (totalElement) {
                totalElement.textContent = `₹${grandTotal.toLocaleString()}`;
            }
        }

        // Add event listeners to quantity inputs for real-time total updates
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('change', calculateTotal);
        });
    </script>
</body>
</html>
<?php
// Close database connection
$stmt->close();
$conn->close();
?>