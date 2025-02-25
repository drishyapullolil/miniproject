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
include 'header.php';
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
        } else {
            $errors[] = "Order cannot be cancelled. It may have already been processed or does not exist.";
        }
    } catch (Exception $e) {
        $errors[] = "Error cancelling order: " . $e->getMessage();
    }
}

// Fetch orders for the logged-in user
try {
    $orderQuery = "SELECT o.id AS order_id, o.total_amount, o.order_status, o.created_at, o.payment_method, 
                          s.name AS item_name, s.image AS item_image, od.quantity, od.price
                   FROM orders o
                   JOIN order_details od ON o.id = od.order_id
                   JOIN sarees s ON od.saree_id = s.id
                   WHERE o.user_id = ?
                   ORDER BY o.created_at DESC";
    $stmt = $conn->prepare($orderQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $errors[] = "Error fetching orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Orders - Yards of Grace</title>
    <style>
        /* Add your existing CSS here */
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

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: var(--container-width);
            margin: 0 auto;
            padding: var(--spacing-lg);
        }

        .order-card {
            background: var(--background-light);
            border-radius: var(--border-radius);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            box-shadow: var(--shadow);
        }

        .order-card h3 {
            margin-top: 0;
            color: var(--primary-color);
        }

        .order-card p {
            margin: var(--spacing-sm) 0;
        }

        .order-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: var(--border-radius);
            font-weight: bold;
        }
        .order-status.pending {
            background-color: #ffcc00;
            color: #000;
        }

        .order-status.completed {
            background-color: #4CAF50;
            color: #fff;
        }

        .order-status.cancelled {
            background-color: #f44336;
            color: #fff;
        }

        .notification {
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            border-radius: var(--border-radius);
            color: white;
        }

        .notification.error {
            background-color: #f44336;
        }

        .notification.success {
            background-color: #4CAF50;
        }

        .order-item {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-sm);
        }

        .order-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: var(--border-radius);
            margin-right: var(--spacing-sm);
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-details h4 {
            margin: 0;
            font-size: 1rem;
        }

        .order-item-details p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .cancel-button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 0.9rem;
        }

        .cancel-button:hover {
            background-color: #d32f2f;
        }
        .order-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: var(--border-radius);
    font-weight: bold;
}

/* Status-specific styles */
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Your Orders</h1>

        <?php if (!empty($errors)): ?>
            <div class="notification error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="notification success">
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <p>You have not placed any orders yet.</p>
        <?php else: ?>
            <?php
            // Group orders by order ID
            $groupedOrders = [];
            foreach ($orders as $order) {
                $orderId = $order['order_id'];
                if (!isset($groupedOrders[$orderId])) {
                    $groupedOrders[$orderId] = [
                        'total_amount' => $order['total_amount'],
                        'order_status' => $order['order_status'],
                        'created_at' => $order['created_at'],
                        'payment_method' => $order['payment_method'],
                        'items' => []
                    ];
                }
                $groupedOrders[$orderId]['items'][] = [
                    'name' => $order['item_name'],
                    'image' => $order['item_image'],
                    'quantity' => $order['quantity'],
                    'price' => $order['price']
                ];
            }
            ?>

            <?php foreach ($groupedOrders as $orderId => $order): ?>
                <div class="order-card">
                    <h3>Order ID: <?php echo htmlspecialchars($orderId); ?></h3>
                    <p><strong>Total Amount:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                    <p><strong>Order Status:</strong> 
                        <span class="order-status <?php echo htmlspecialchars($order['order_status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($order['order_status'])); ?>
                        </span>
                    </p>
                    <p><strong>Payment Method:</strong> <?php echo ucfirst(htmlspecialchars($order['payment_method'])); ?></p>
                    <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>

                    <h4>Items:</h4>
                    <?php foreach ($order['items'] as $item): ?>
                        <div class="order-item">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div class="order-item-details">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p>Quantity: <?php echo $item['quantity']; ?> | Price: ₹<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($order['order_status'] === 'pending'): ?>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                            <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                            <button type="submit" name="cancel_order" class="cancel-button">Cancel Order</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>