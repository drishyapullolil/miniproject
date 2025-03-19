<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Get product ID and type from URL parameters
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product_type = isset($_GET['type']) ? $_GET['type'] : '';

if ($product_id <= 0 || empty($product_type)) {
    echo '<div class="error">Invalid product information</div>';
    exit;
}

$product = null;

// Fetch product details based on product type
if ($product_type === 'saree') {
    $query = "SELECT 
                s.*, 
                sub.subcategory_name,
                cat.category_name
              FROM sarees s 
              JOIN subcategories sub ON s.subcategory_id = sub.id
              JOIN categories cat ON sub.category_id = cat.id
              WHERE s.id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    }
}

// If no product found
if (!$product) {
    echo '<div class="error">Product not found</div>';
    exit;
}
?>

<div class="quick-view-product">
    <div class="quick-view-grid">
        <div class="quick-view-image">
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        
        <div class="quick-view-details">
            <h2 class="quick-view-title"><?php echo htmlspecialchars($product['name']); ?></h2>
            
            <p class="quick-view-category">
                <?php echo htmlspecialchars($product['category_name'] . ' > ' . $product['subcategory_name']); ?>
            </p>
            
            <div class="quick-view-price">
                <span class="current-price">₹<?php echo number_format($product['price'], 2); ?></span>
                <?php if (isset($product['original_price']) && $product['original_price'] > $product['price']): ?>
                    <span class="original-price">₹<?php echo number_format($product['original_price'], 2); ?></span>
                    <span class="discount-badge">
                        <?php 
                            $discount = round(($product['original_price'] - $product['price']) / $product['original_price'] * 100);
                            echo $discount . '% OFF';
                        ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="product-attributes">
                <?php if (isset($product['color'])): ?>
                    <div class="attribute-group">
                        <span class="attribute-label">Color:</span>
                        <span class="attribute-value"><?php echo htmlspecialchars($product['color']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($product['material'])): ?>
                    <div class="attribute-group">
                        <span class="attribute-label">Material:</span>
                        <span class="attribute-value"><?php echo htmlspecialchars($product['material']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($product['length'])): ?>
                    <div class="attribute-group">
                        <span class="attribute-label">Length:</span>
                        <span class="attribute-value"><?php echo htmlspecialchars($product['length']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="product-description">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>
            
            <div class="product-actions">
                <div class="quantity-selector">
                    <button class="quantity-btn minus">-</button>
                    <input type="number" value="1" min="1" max="10" class="quantity-input">
                    <button class="quantity-btn plus">+</button>
                </div>
                
                <button class="add-to-cart-btn">Add to Cart</button>
                <button class="wishlist-btn">♥ Add to Wishlist</button>
            </div>
            
            <div class="product-meta">
                <p class="stock-status">
                    <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                        <span class="in-stock">✓ In Stock</span>
                    <?php else: ?>
                        <span class="out-of-stock">× Out of Stock</span>
                    <?php endif; ?>
                </p>
                
                <a href="product_details.php?id=<?php echo $product['id']; ?>&type=<?php echo $product_type; ?>" class="view-full-details">
                    View Full Details
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.quick-view-product {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.quick-view-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.quick-view-image img {
    width: 100%;
    height: auto;
    border-radius: 10px;
    object-fit: cover;
}

.quick-view-title {
    font-size: 24px;
    margin-bottom: 10px;
    color: #333;
}

.quick-view-category {
    color: #666;
    font-size: 14px;
    margin-bottom: 15px;
}

.quick-view-price {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.current-price {
    font-size: 24px;
    font-weight: 600;
    color: #8d0f8f;
    margin-right: 10px;
}

.original-price {
    font-size: 18px;
    color: #999;
    text-decoration: line-through;
    margin-right: 10px;
}

.discount-badge {
    background-color: #f84f50;
    color: white;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.product-attributes {
    margin-bottom: 20px;
}

.attribute-group {
    display: flex;
    margin-bottom: 8px;
}

.attribute-label {
    font-weight: 500;
    width: 80px;
    color: #555;
}

.attribute-value {
    color: #333;
}

.product-description {
    margin-bottom: 25px;
}

.product-description h3 {
    font-size: 16px;
    margin-bottom: 10px;
    color: #333;
}

.product-description p {
    color: #666;
    line-height: 1.6;
    font-size: 14px;
}

.product-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}

.quantity-selector {
    display: flex;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 5px;
    overflow: hidden;
}

.quantity-btn {
    background: #f5f5f5;
    border: none;
    width: 30px;
    height: 40px;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
}

.quantity-btn:hover {
    background: #e8e8e8;
}

.quantity-input {
    width: 50px;
    height: 40px;
    text-align: center;
    border: none;
    border-left: 1px solid #ddd;
    border-right: 1px solid #ddd;
}

.quantity-input::-webkit-inner-spin-button,
.quantity-input::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.add-to-cart-btn {
    background-color: #8d0f8f;
    color: white;
    border: none;
    padding: 0 25px;
    height: 40px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.3s;
    flex-grow: 1;
}

.add-to-cart-btn:hover {
    background-color: #4e034f;
}

.wishlist-btn {
    background-color: white;
    color: #8d0f8f;
    border: 1px solid #8d0f8f;
    padding: 0 15px;
    height: 40px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.wishlist-btn:hover {
    background-color: #fdf4fd;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.stock-status {
    font-size: 14px;
}

.in-stock {
    color: #28a745;
}

.out-of-stock {
    color: #dc3545;
}

.view-full-details {
    color: #8d0f8f;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.view-full-details:hover {
    text-decoration: underline;
}

.error {
    padding: 20px;
    text-align: center;
    color: #dc3545;
    background-color: #f8d7da;
    border-radius: 5px;
    margin: 20px 0;
}

@media (max-width: 768px) {
    .quick-view-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-view-image {
        text-align: center;
    }
    
    .quick-view-image img {
        max-width: 300px;
    }
    
    .product-actions {
        flex-direction: column;
    }
    
    .quantity-selector {
        width: 100%;
    }
}
</style>

<script>
// Quantity selector functionality
document.addEventListener('DOMContentLoaded', function() {
    const minusBtn = document.querySelector('.quantity-btn.minus');
    const plusBtn = document.querySelector('.quantity-btn.plus');
    const quantityInput = document.querySelector('.quantity-input');
    
    minusBtn.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        if (value > 1)