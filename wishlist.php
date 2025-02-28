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
    </style>
</head>
<body>
    <main class="wishlist-container">
        <div class="wishlist-header">
            <h1>Your Wishlist (<?php echo count($wishlistItems); ?> items)</h1>
            <?php if (count($wishlistItems) > 0): ?>
            <a href="shop.php" class="button-primary">Continue Shopping</a>
            <?php endif; ?>
        </div>
        
        <?php if (count($wishlistItems) == 0): ?>
        <div class="wishlist-empty">
            <h2>Your wishlist is empty</h2>
            <p>Add items to your wishlist to keep track of products you love!</p>
            <a href="shop.php" class="button-primary">Explore Our Collection</a>
        </div>
        <?php else: ?>
        <div class="wishlist-grid">
            <?php foreach ($wishlistItems as $item): ?>
            <div class="wishlist-item" data-id="<?php echo $item['id']; ?>">
                <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                <p class="product-price">₹<?php echo number_format($item['price'], 2); ?></p>
                <div class="button-group">
                    <button class="button button-primary add-to-cart" data-id="<?php echo $item['id']; ?>" aria-label="Add to cart">ADD TO CART</button>
                    <button class="button button-remove remove-from-wishlist" data-id="<?php echo $item['id']; ?>" aria-label="Remove from wishlist">REMOVE</button>
                </div>
            </div>
            <?php endforeach; ?>
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
            
            // Add to cart functionality
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const sareeId = this.getAttribute('data-id');
                    
                    // AJAX request to add to cart
                    fetch('cart-api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=add&saree_id=${sareeId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Added to cart successfully');
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
            
            // Remove from wishlist functionality
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
<?php
// This code snippet can be included in your product listing pages
// Example usage:
// include 'wishlist-button.php';
// Then in your product loop: <?php echo getWishlistButton($conn, $userId, $product['id']); 

// Function to generate wishlist toggle button HTML
function getWishlistButton($conn, $userId, $sareeId) {
    $isInWishlist = isInWishlist($conn, $userId, $sareeId);
    
    $buttonClass = $isInWishlist ? 'wishlist-button active' : 'wishlist-button';
    $ariaLabel = $isInWishlist ? 'Remove from wishlist' : 'Add to wishlist';
    $icon = $isInWishlist ? '❤️' : '🤍';
    
    return '<button class="' . $buttonClass . '" data-id="' . $sareeId . '" aria-label="' . $ariaLabel . '">' . $icon . '</button>';
}
?>

<style>
/* CSS for the wishlist button to be included in your main stylesheet */
.wishlist-button {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255, 255, 255, 0.8);
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 20px;
    transition: all 0.3s ease;
    z-index: 5;
}

.wishlist-button:hover {
    transform: scale(1.1);
    background: rgba(255, 255, 255, 1);
}

.wishlist-button.active {
    background: rgba(255, 192, 203, 0.8);
}

.wishlist-button.active:hover {
    background: rgba(255, 192, 203, 1);
}

@keyframes heartbeat {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.wishlist-button.active {
    animation: heartbeat 0.5s;
}
</style>

<script>
// JavaScript for wishlist button functionality
document.addEventListener('DOMContentLoaded', function() {
    // Function to show toast notifications (if you have a toast element)
    function showToast(message, duration = 3000) {
        if (document.getElementById('toast')) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, duration);
        }
    }
    
    // Toggle wishlist functionality
    document.querySelectorAll('.wishlist-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const sareeId = this.getAttribute('data-id');
            const isActive = this.classList.contains('active');
            const action = isActive ? 'remove' : 'add';
            
            // AJAX request to add/remove from wishlist
            fetch('wishlist-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&saree_id=${sareeId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (action === 'add') {
                        this.classList.add('active');
                        this.innerHTML = '❤️';
                        this.setAttribute('aria-label', 'Remove from wishlist');
                        showToast('Added to wishlist');
                    } else {
                        this.classList.remove('active');
                        this.innerHTML = '🤍';
                        this.setAttribute('aria-label', 'Add to wishlist');
                        showToast('Removed from wishlist');
                    }
                } else {
                    if (data.message === 'User not logged in') {
                        // Redirect to login page
                        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                    } else {
                        showToast(data.message);
                    }
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
