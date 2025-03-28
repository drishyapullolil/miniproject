<?php
session_start();
require_once 'db.php';

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$orders = [];
$errors = [];
$success = '';

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $orderId = (int)$_POST['order_id'];

    try {
        // Check if the order belongs to the user and is cancellable
        $checkQuery = "SELECT id, order_status FROM orders WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("ii", $orderId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        if ($order && $order['order_status'] === 'pending') {
            // Update order status to 'cancelled'
            $updateQuery = "UPDATE orders SET order_status = 'cancelled' WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();

            // Restore stock for each item in the order
            $restoreStockQuery = "UPDATE sarees s
                                  JOIN order_details od ON s.id = od.saree_id
                                  SET s.stock = s.stock + od.quantity
                                  WHERE od.order_id = ?";
            $stmt = $conn->prepare($restoreStockQuery);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();

            $success = "Order #$orderId has been cancelled successfully.";
            
            // Redirect to avoid form resubmission
            header("Location: orders.php?success=" . urlencode($success));
            exit();
        } else {
            $errors[] = "Order cannot be cancelled. It may have already been processed or does not exist.";
        }
    } catch (Exception $e) {
        $errors[] = "Error cancelling order: " . $e->getMessage();
    }
}

// Retrieve success message from URL if it exists
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Filter orders if requested
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$whereClause = "WHERE o.user_id = ?";
$params = [$userId];

if (!empty($statusFilter) && $statusFilter !== 'all') {
    $whereClause .= " AND o.order_status = ?";
    $params[] = $statusFilter;
}

// Fetch orders with payment information
$orderQuery = "SELECT o.*, p.payment_method, p.transaction_id, p.status as payment_status, p.amount
               FROM orders o
               LEFT JOIN payments p ON o.id = p.order_id
               $whereClause
               ORDER BY o.created_at DESC";

$stmt = $conn->prepare($orderQuery);
if (count($params) === 1) {
    $stmt->bind_param("i", $params[0]);
} else if (count($params) > 1) {
    $types = str_repeat("s", count($params));
    $types[0] = "i"; // First parameter is integer (user_id)
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Fetch order details for each order
    $orderDetailsQuery = "SELECT od.*, s.name as saree_name, s.image
                         FROM order_details od
                         LEFT JOIN sarees s ON od.saree_id = s.id
                         WHERE od.order_id = ?";
    
    $detailStmt = $conn->prepare($orderDetailsQuery);
    $detailStmt->bind_param("i", $row['id']);
    $detailStmt->execute();
    $detailsResult = $detailStmt->get_result();
    
    $orderDetails = [];
    while ($detail = $detailsResult->fetch_assoc()) {
        $orderDetails[] = $detail;
    }
    
    $row['items'] = $orderDetails;
    $orders[] = $row;
}

// Get available order statuses for filter
$statusQuery = "SELECT DISTINCT order_status FROM orders WHERE user_id = ?";
$statusStmt = $conn->prepare($statusQuery);
$statusStmt->bind_param("i", $userId);
$statusStmt->execute();
$statusResult = $statusStmt->get_result();
$availableStatuses = [];
while ($statusRow = $statusResult->fetch_assoc()) {
    $availableStatuses[] = $statusRow['order_status'];
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Orders - Yards of Grace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e034f;
            --primary-hover: #6c0c6d;
            --secondary-color: #f8e9f8;
            --accent-color: #a7489b;
            --text-color: #333;
            --text-light: #666;
            --background-light: #fff;
            --border-color: #eee;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --border-radius: 10px;
            --container-width: 1200px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: #f8f8f8;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: var(--container-width);
            margin: 0 auto;
            padding: var(--spacing-lg);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
        }

        .filter-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
            background-color: var(--background-light);
            padding: var(--spacing-md);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .filter-group {
            display: flex;
            align-items: center;
        }

        .filter-group label {
            margin-right: var(--spacing-sm);
            font-weight: 500;
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background-color: white;
            font-size: 0.9rem;
        }

        .order-card {
            background: var(--background-light);
            border-radius: var(--border-radius);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .order-header h3 {
            margin-top: 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }

        .order-header h3 i {
            margin-right: var(--spacing-sm);
        }

        .order-meta {
            text-align: right;
        }

        .order-meta p {
            margin: 5px 0;
        }

        .order-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .order-status.pending {
            background-color: #FFF3CD;
            color: #856404;
        }

        .order-status.processing {
            background-color: #D1ECF1;
            color: #0C5460;
        }

        .order-status.shipped {
            background-color: #D4EDDA;
            color: #155724;
        }

        .order-status.delivered {
            background-color: #C3E6CB;
            color: #155724;
        }

        .order-status.cancelled {
            background-color: #F8D7DA;
            color: #721C24;
        }

        .payment-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: var(--border-radius);
            font-size: 0.8rem;
            font-weight: 500;
        }

        .payment-status.pending {
            background-color: #FFF3CD;
            color: #856404;
        }

        .payment-status.paid {
            background-color: #D4EDDA;
            color: #155724;
        }

        .payment-status.failed {
            background-color: #F8D7DA;
            color: #721C24;
        }

        .order-details {
            margin: var(--spacing-md) 0;
        }

        .order-sections {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .payment-info, .shipping-info {
            flex: 1;
            background-color: var(--secondary-color);
            padding: var(--spacing-md);
            border-radius: var(--border-radius);
        }

        .payment-info h4, .shipping-info h4 {
            margin-top: 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }

        .payment-info h4 i, .shipping-info h4 i {
            margin-right: var(--spacing-sm);
        }

        .payment-info p, .shipping-info p {
            margin: 5px 0;
            font-size: 0.95rem;
        }

        .order-items {
            margin-top: var(--spacing-md);
            border-top: 1px solid var(--border-color);
            padding-top: var(--spacing-md);
        }

        .order-items h4 {
            color: var(--primary-color);
            margin-bottom: var(--spacing-md);
            display: flex;
            align-items: center;
        }

        .order-items h4 i {
            margin-right: var(--spacing-sm);
        }

        .order-item {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-md);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px dashed var(--border-color);
        }

        .order-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .order-item img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: var(--border-radius);
            margin-right: var(--spacing-md);
            border: 1px solid var(--border-color);
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-details h5 {
            margin: 0 0 5px 0;
            font-size: 1rem;
        }

        .order-item-details p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .item-price {
            font-weight: 600;
            color: var(--primary-color);
        }

        .order-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: var(--spacing-md);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-color);
        }

        .cancel-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 0.9rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
        }

        .cancel-button i {
            margin-right: 5px;
        }

        .cancel-button:hover {
            background-color: #c82333;
        }

        .track-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: var(--spacing-md);
            transition: var(--transition);
            display: flex;
            align-items: center;
        }

        .track-button i {
            margin-right: 5px;
        }

        .track-button:hover {
            background-color: var(--primary-hover);
        }

        .notification {
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            animation: fadeIn 0.5s;
        }

        .notification i {
            margin-right: var(--spacing-md);
            font-size: 1.5rem;
        }

        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .no-orders {
            text-align: center;
            padding: var(--spacing-lg);
            background-color: var(--background-light);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .no-orders i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: var(--spacing-md);
            display: block;
        }

        .shop-link {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            text-decoration: none;
            margin-top: var(--spacing-md);
            transition: var(--transition);
        }

        .shop-link:hover {
            background-color: var(--primary-hover);
        }

        .order-summary {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: var(--spacing-md);
            margin-top: var(--spacing-md);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .summary-row.total {
            font-weight: bold;
            border-top: 1px solid var(--border-color);
            padding-top: 5px;
            margin-top: 5px;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
            }
            
            .order-meta {
                text-align: left;
                margin-top: var(--spacing-md);
            }
            
            .order-sections {
                flex-direction: column;
            }
            
            .payment-info, .shipping-info {
                margin-bottom: var(--spacing-md);
            }
        }

        .collapsible-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .expanded .collapsible-content {
            max-height: 1000px;
        }

        .toggle-details {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            padding: 5px 10px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .toggle-details i {
            margin-right: 5px;
            transition: transform 0.3s ease;
        }

        .expanded .toggle-details i {
            transform: rotate(180deg);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-shopping-bag"></i> Your Orders</h1>
            <div class="orders-count">
                <span><?php echo count($orders); ?> order<?php echo count($orders) !== 1 ? 's' : ''; ?> found</span>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="notification error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="notification success">
                <i class="fas fa-check-circle"></i>
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <div class="filter-controls">
            <div class="filter-group">
                <label for="status-filter"><i class="fas fa-filter"></i> Filter by Status:</label>
                <select id="status-filter" class="filter-select" onchange="window.location.href = 'orders.php?status=' + this.value">
                    <option value="all" <?php echo $statusFilter === 'all' || $statusFilter === '' ? 'selected' : ''; ?>>All Orders</option>
                    <?php foreach ($availableStatuses as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
                            <?php echo ucfirst($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <i class="fas fa-shopping-cart"></i>
                <h3>You haven't placed any orders yet</h3>
                <p>Explore our collection and find your perfect style.</p>
                <a href="shop.php" class="shop-link"><i class="fas fa-store"></i> Shop Now</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <h3><i class="fas fa-receipt"></i> Order #<?php echo $order['id']; ?></h3>
                        <div class="order-meta">
                            <p><i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($order['created_at'])); ?></p>
                            <p><span class="order-status <?php echo strtolower($order['order_status']); ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span></p>
                        </div>
                    </div>

                    <div class="order-sections">
                        <div class="payment-info">
                            <h4><i class="fas fa-credit-card"></i> Payment Details</h4>
                            <p><strong>Method:</strong> <?php echo ucfirst($order['payment_method'] ?? 'Not specified'); ?></p>
                            <p><strong>Status:</strong> <span class="payment-status <?php echo strtolower($order['payment_status'] ?? 'pending'); ?>">
                                <?php echo ucfirst($order['payment_status'] ?? 'Pending'); ?>
                            </span></p>
                            <?php if (isset($order['transaction_id']) && $order['transaction_id']): ?>
                                <p><strong>Transaction ID:</strong> <?php echo $order['transaction_id']; ?></p>
                            <?php endif; ?>
                            <p><strong>Amount:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                        </div>

                        <div class="shipping-info">
                            <h4><i class="fas fa-shipping-fast"></i> Shipping Information</h4>
                            <p>Delivery to your registered address</p>
                            <p>Order will be processed according to the standard delivery timeline</p>
                            <?php if (isset($order['shipping_address']) && !empty($order['shipping_address'])): ?>
                                <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button class="toggle-details" onclick="toggleOrderDetails(this)">
                        <i class="fas fa-chevron-down"></i> View Order Items
                    </button>
                    
                    <div class="collapsible-content">
                        <div class="order-items">
                            <h4><i class="fas fa-box-open"></i> Items in Your Order</h4>
                            <?php if (empty($order['items'])): ?>
                                <p>No items found for this order.</p>
                            <?php else: ?>
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <?php if (isset($item['image']) && !empty($item['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['saree_name'] ?? 'Product'); ?>" 
                                                 class="item-image">
                                        <?php else: ?>
                                            <div style="width:70px;height:70px;background:#eee;border-radius:var(--border-radius);display:flex;align-items:center;justify-content:center;margin-right:var(--spacing-md);">
                                                <i class="fas fa-image" style="color:#aaa;font-size:24px;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="order-item-details">
                                            <h5><?php echo htmlspecialchars($item['saree_name'] ?? 'Product #'.$item['saree_id']); ?></h5>
                                            <p>Quantity: <?php echo $item['quantity']; ?></p>
                                            <p>Unit Price: <span class="item-price">₹<?php echo number_format($item['price'], 2); ?></span></p>
                                            <p>Subtotal: <span class="item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="order-summary">
                                <div class="summary-row">
                                    <span>Subtotal:</span>
                                    <span>₹<?php echo number_format($order['total_amount'] - ($order['shipping_cost'] ?? 0), 2); ?></span>
                                </div>
                                <div class="summary-row">
                                    <span>Shipping:</span>
                                    <span>₹<?php echo number_format($order['shipping_cost'] ?? 0, 2); ?></span>
                                </div>
                                <?php if (isset($order['discount']) && $order['discount'] > 0): ?>
                                <div class="summary-row">
                                    <span>Discount:</span>
                                    <span>-₹<?php echo number_format($order['discount'], 2); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="summary-row total">
                                    <span>Total:</span>
                                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="order-actions">
                        <?php if (isset($order['order_status']) && in_array($order['order_status'], ['shipped', 'processing'])): ?>
                            <a href="track_order.php?id=<?php echo $order['id']; ?>" class="track-button">
                                <i class="fas fa-truck"></i> Track Order
                            </a>
                        <?php endif; ?>
                        
                        <?php if (isset($order['order_status']) && $order['order_status'] === 'delivered'): ?>
                            <a href="review.php?order_id=<?php echo $order['id']; ?>" class="track-button" style="background-color: #4CAF50;">
                                <i class="fas fa-star"></i> Write Review
                            </a>
                        <?php endif; ?>
                        
                        <?php if (isset($order['order_status']) && $order['order_status'] === 'pending'): ?>
                            <form method="POST" class="cancel-form" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="cancel_order" class="cancel-button">
                                    <i class="fas fa-times"></i> Cancel Order
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function toggleOrderDetails(button) {
            const orderCard = button.closest('.order-card');
            if (orderCard.classList.contains('expanded')) {
                orderCard.classList.remove('expanded');
                button.querySelector('i').classList.remove('fa-chevron-up');
                button.querySelector('i').classList.add('fa-chevron-down');
            } else {
                orderCard.classList.add('expanded');
                button.querySelector('i').classList.remove('fa-chevron-down');
                button.querySelector('i').classList.add('fa-chevron-up');
            }
        }
        
        // Auto-hide notifications after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const notifications = document.querySelectorAll('.notification');
                notifications.forEach(function(notification) {
                    notification.style.opacity = '0';
                    setTimeout(function() {
                        notification.style.display = 'none';
                    }, 500);
                });
            }, 5000);
        });
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>