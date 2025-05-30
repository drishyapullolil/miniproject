<?php
session_start();
include_once 'db.php'; // Include database connection

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Fetch orders data from database
function getOrders($conn, $limit = 10) {
    $sql = "SELECT o.id, o.user_id, o.total_amount, o.order_status, o.created_at, 
                  u.username, o.payment_method 
           FROM orders o
           JOIN users u ON o.user_id = u.id
           ORDER BY o.created_at DESC 
           LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    return $orders;
}

// Get order details
function getOrderDetails($conn, $orderId) {
    // First, let's check if order details exist without the join
    $checkSql = "SELECT * FROM order_details WHERE order_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $orderId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows == 0) {
        error_log("No entries in order_details for Order ID: $orderId");
        
        // Try to get just the order
        $orderSql = "SELECT * FROM orders WHERE id = ?";
        $orderStmt = $conn->prepare($orderSql);
        $orderStmt->bind_param("i", $orderId);
        $orderStmt->execute();
        $orderResult = $orderStmt->get_result();
        
        if ($orderResult->num_rows > 0) {
            $order = $orderResult->fetch_assoc();
            // Create a placeholder item if order exists but no details
            return [
                [
                    'saree_name' => 'Order details not available',
                    'price' => $order['total_amount'],
                    'quantity' => 1,
                    'order_id' => $orderId
                ]
            ];
        }
        
        return [];
    }
    
    // If we have order details, try the join
    $sql = "SELECT od.*, COALESCE(s.name, 'Product Not Found') as saree_name 
            FROM order_details od
            LEFT JOIN sarees s ON od.saree_id = s.id
            WHERE od.order_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

// Get order statistics
function getOrderStats($conn) {
    // Total orders
    $totalOrdersQuery = "SELECT COUNT(*) as total FROM orders";
    $totalOrdersResult = $conn->query($totalOrdersQuery);
    $totalOrders = $totalOrdersResult->fetch_assoc()['total'];
    
    // Orders by status
    $statusQuery = "SELECT order_status, COUNT(*) as count 
                   FROM orders 
                   GROUP BY order_status";
    $statusResult = $conn->query($statusQuery);
    
    $ordersByStatus = [];
    while ($row = $statusResult->fetch_assoc()) {
        $ordersByStatus[$row['order_status']] = $row['count'];
    }
    
    // Total revenue
    $revenueQuery = "SELECT SUM(total_amount) as total_revenue FROM orders WHERE order_status != 'cancelled'";
    $revenueResult = $conn->query($revenueQuery);
    $totalRevenue = $revenueResult->fetch_assoc()['total_revenue'] ?: 0;
    
    return [
        'total_orders' => $totalOrders,
        'by_status' => $ordersByStatus,
        'total_revenue' => $totalRevenue
    ];
}

// Update order status
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['new_status'];
    
    // Trim any whitespace
    $newStatus = trim($newStatus);

    // Display the exact value for debugging
    error_log("Attempting to update order ID: $orderId with status: '$newStatus'");
    
    // Check current order status first
    $checkStatusSql = "SELECT order_status, payment_method FROM orders WHERE id = ?";
    $checkStmt = $conn->prepare($checkStatusSql);
    $checkStmt->bind_param("i", $orderId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $orderInfo = $result->fetch_assoc();
    $currentStatus = $orderInfo['order_status'];
    $paymentMethod = $orderInfo['payment_method'];
    
    // Rules for status changes
    if ($currentStatus == 'cash_received') {
        // Can't change status once cash is received
        $_SESSION['error_message'] = "Cannot update status for orders where cash has been received";
    } elseif ($currentStatus == 'delivered' && $newStatus != 'cash_received') {
        // Delivered orders can only change to cash_received (for COD)
        $_SESSION['error_message'] = "Delivered orders can only be marked as cash received";
    } else {
        // Before the update query, validate the status
        $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cash_received', 'cancelled'];
        if (!in_array($newStatus, $allowedStatuses)) {
            // Log the invalid status value
            error_log("Invalid status value attempted: '$newStatus' for Order ID: $orderId");
            $_SESSION['error_message'] = "Invalid status value: " . htmlspecialchars($newStatus);
            header("Location: order_manage.php");
            exit();
        }
        
        // Then proceed with the update if the status is valid
        $updateSql = "UPDATE orders SET order_status = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $newStatus, $orderId);
        
        // Debugging output before executing the update
        error_log("Order ID: $orderId, New Status: '$newStatus'");
        
        if ($updateStmt->execute()) {
            $_SESSION['success_message'] = "Order status updated successfully";
        } else {
            // Log detailed error message
            error_log("Failed to update order status for Order ID: $orderId. Error: " . $conn->error);
            $_SESSION['error_message'] = "Failed to update order status: " . $conn->error;
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: order_manage.php");
    exit();
}

// Handle cash received action
if (isset($_POST['cash_received']) && isset($_POST['order_id'])) {
    $orderId = $_POST['order_id'];
    $updateCashReceivedSql = "UPDATE orders SET order_status = 'cash_received' WHERE id = ? AND payment_method = 'cod'";
    $stmt = $conn->prepare($updateCashReceivedSql);
    $stmt->bind_param("i", $orderId);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Order marked as cash received successfully";
    } else {
        $_SESSION['error_message'] = "Failed to update order status: " . $conn->error;
    }
    // Redirect to prevent form resubmission and preserve view parameter
    if (isset($_GET['view_cod_order'])) {
        header("Location: order_manage.php?view_cod_order=" . $_GET['view_cod_order']);
    } else if (isset($_GET['view_order'])) {
        header("Location: order_manage.php?view_order=" . $_GET['view_order']);
    } else {
        header("Location: order_manage.php");
    }
    exit();
}

// Get order statistics
$orderStats = getOrderStats($conn);

// Get orders for display
$recentOrders = getOrders($conn);

// Get specific order details if requested
$selectedOrderDetails = null;
$orderHeader = null;
if (isset($_GET['view_order']) || isset($_GET['view_cod_order'])) {
    $orderId = isset($_GET['view_order']) ? $_GET['view_order'] : $_GET['view_cod_order'];
    $selectedOrderDetails = getOrderDetails($conn, $orderId);
    
    // Debug
    if (empty($selectedOrderDetails)) {
        echo "<div class='alert alert-danger'>No order details found for Order ID: " . $orderId . "</div>";
    }
    
    // Get the order header info
    $orderHeaderSql = "SELECT o.*, u.username, u.email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = ?";
    $stmt = $conn->prepare($orderHeaderSql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $orderHeader = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Yards of Grace</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        .top-bar {
            background-color: #000;
            color: white;
            text-align: center;
            padding: 8px;
        }

        .header-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            border-bottom: 1px solid #eee;
        }

        .header-center h1 {
            color: purple;
        }

        .dashboard-container {
            display: flex;
            min-height: calc(100vh - 180px);
        }

        .sidebar {
            width: 250px;
            background-color: #f8f8f8;
            padding: 20px;
            border-right: 1px solid #eee;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu a {
            text-decoration: none;
            color: inherit;
        }

        .sidebar-menu li {
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            color: inherit;
        }

        .sidebar-menu li:hover {
            background-color: #f0f0f0;
        }

        .sidebar-menu li.active {
            background-color: purple;
            color: white;
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .dashboard-header h2 {
            color: purple;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #eee;
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            color: purple;
        }

        .orders-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #eee;
            margin-bottom: 30px;
        }

        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-table th, .order-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .order-table th {
            background-color: #f8f8f8;
            color: #666;
        }

        .status-pending {
            background-color: #FFF3CD;
            color: #856404;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        .status-processing {
            background-color: #D1ECF1;
            color: #0C5460;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        .status-shipped {
            background-color: #D4EDDA;
            color: #155724;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        .status-delivered {
            background-color: #C3E6CB;
            color: #155724;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        .status-cash_received {
            background-color: #2E9AFE;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        .status-cancelled {
            background-color: #F8D7DA;
            color: #721C24;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        .payment-cod {
            background-color: #E2E3E5;
            color: #383d41;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 5px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-view {
            background-color: #e9ecef;
            color: #495057;
        }

        .btn-view:hover {
            background-color: #dee2e6;
        }

        .btn-cod {
            background-color: #fd7e14;
            color: white;
        }

        .btn-cod:hover {
            background-color: #e67211;
        }

        .btn-update {
            background-color: #6f42c1;
            color: white;
        }

        .btn-update:hover {
            background-color: #5a32a3;
        }

        .btn-cash {
            background-color: #28a745;
            color: white;
        }

        .btn-cash:hover {
            background-color: #218838;
        }

        .btn-disabled {
            background-color: #adb5bd;
            color: white;
            cursor: not-allowed;
        }

        .order-details-modal {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .order-details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .payment-method-label {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
            background-color: #6c757d;
            color: white;
        }

        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .order-items-table th, .order-items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .order-summary {
            background-color: #f8f8f8;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }

        .order-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .logout-btn {
            background-color: purple;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .logout-btn:hover {
            background-color: #800080;
        }

        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-update-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 20px;
        }

        .status-select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .disabled-note {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f8f8;
            border-radius: 4px;
            border-left: 4px solid #6c757d;
            font-style: italic;
        }

        footer {
            background: #f8f8f8;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #eee;
        }

        .cod-info {
            margin-top: 15px;
            padding: 10px;
            background-color: #fff3cd;
            border-radius: 4px;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <p>Free domestic shipping on orders above INR 10,000. International shipping available.</p>
    </div>

    <div class="header-main">
        <div class="header-center">
            <h1><img src="logo3.png" alt="Logo" width="50px">YARDS OF GRACE</h1>
        </div>
        <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
    </div>

    <div class="dashboard-container">
        <div class="sidebar">
            <ul class="sidebar-menu">
            <a href="admin.php"><li >Dashboard Overview</li></a>
                <a href="manage.php"><li >User Management</li></a>
                <a href="Category.php"><li >Category Management</li></a>
                <a href="subcategory.php"><li >Subcategory Management</li></a>
                <a href="category_details.php"><li >Product Management</li></a>
                <a href="wedding_categories.php"><li>Wedding Categories</li></a>
                <a href="wedding_products.php"><li>Wedding Products</li></a>
                <a href="wedding_images.php"><li>Wedding Specifications</li></a>
                <a href="review_of_user.php"><li>Reviews</li></a>
                <a href="admin_report.php"><li>Reports</li></a>
                <a href="order_manage.php"><li class="active">Orders</li></a>
                <a href="#"><li>Products</li></a>
              
                <a href="#"><li>Settings</li></a>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h2>Orders Management</h2>
                <p>Manage and track all customer orders</p>
            </div>

            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <p><?php echo $orderStats['total_orders']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Orders</h3>
                    <p><?php echo $orderStats['by_status']['pending'] ?? 0; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Delivered Orders</h3>
                    <p><?php echo $orderStats['by_status']['delivered'] ?? 0; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Cash Received (COD)</h3>
                    <p><?php echo $orderStats['by_status']['cash_received'] ?? 0; ?></p>
                </div>
            </div>

            <div class="orders-container">
                <div class="orders-header">
                    <h3>Recent Orders</h3>
                </div>
                
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No orders found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>
                                        #ORD<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?>
                                        <?php if ($order['payment_method'] === 'cod'): ?>
                                            <span class="payment-cod">COD</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-<?php echo $order['order_status']; ?>">
                                            <?php 
                                            if ($order['order_status'] === 'cash_received') {
                                                echo 'Cash Received';
                                            } else {
                                                echo ucfirst($order['order_status']); 
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <?php if ($order['payment_method'] === 'cod'): ?>
                                            <a href="?view_cod_order=<?php echo $order['id']; ?>" class="btn btn-cod">View COD</a>
                                        <?php else: ?>
                                            <a href="?view_order=<?php echo $order['id']; ?>" class="btn btn-view">View</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($selectedOrderDetails && $orderHeader): ?>
                <div class="order-details-modal">
                    <div class="order-details-header">
                        <h3>
                            Order #ORD<?php echo str_pad($orderHeader['id'], 3, '0', STR_PAD_LEFT); ?> Details
                            <?php if ($orderHeader['payment_method'] === 'cod'): ?>
                                <span class="payment-method-label">Cash On Delivery</span>
                            <?php else: ?>
                                <span class="payment-method-label">Online Payment</span>
                            <?php endif; ?>
                        </h3>
                        <span class="status-<?php echo $orderHeader['order_status']; ?>">
                            <?php 
                            if ($orderHeader['order_status'] === 'cash_received') {
                                echo 'Cash Received';
                            } else {
                                echo ucfirst($orderHeader['order_status']); 
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="order-info">
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($orderHeader['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($orderHeader['email']); ?></p>
                        <p><strong>Order Date:</strong> <?php echo date('Y-m-d H:i', strtotime($orderHeader['created_at'])); ?></p>
                        <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($orderHeader['address']); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo $orderHeader['payment_method'] === 'cod' ? 'Cash On Delivery' : 'Online Payment'; ?></p>
                    </div>
                    
                    <?php if ($orderHeader['payment_method'] === 'cod'): ?>
                        <div class="cod-info">
                            <p><strong>COD Order Instructions:</strong> Please confirm delivery and cash collection status using the controls below.</p>
                        </div>
                    <?php endif; ?>
                    
                    <h4 style="margin: 20px 0 10px;">Order Items</h4>
                    <table class="order-items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = 0;
                            foreach ($selectedOrderDetails as $item): 
                                $subtotal = $item['price'] * $item['quantity'];
                                $total += $subtotal;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['saree_name']); ?></td>
                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>₹<?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="order-summary">
                        <div class="order-summary-row">
                            <span>Subtotal:</span>
                            <span>₹<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="order-summary-row">
                            <span>Shipping:</span>
                            <span>₹<?php echo ($orderHeader['total_amount'] > 10000) ? '0.00' : '500.00'; ?></span>
                        </div>
                        <div class="order-summary-row" style="font-weight: bold;">
                            <span>Total:</span>
                            <span>₹<?php echo number_format($orderHeader['total_amount'], 2); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($orderHeader['order_status'] == 'cash_received' && $orderHeader['payment_method'] == 'cod'): ?>
    <!-- Show cash received information for completed COD orders -->
    <div style="margin-top: 20px; padding: 15px; background-color: #d4edda; border-radius: 4px; border-left: 4px solid #28a745;">
    <p><strong>Payment Collected:</strong> Cash amount of ₹<?php echo number_format($total, 2); ?> has been received for this order.</p>        <p><strong>Collection Date:</strong> <?php echo date('Y-m-d H:i'); ?></p>
    </div>
<?php elseif ($orderHeader['order_status'] == 'delivered' && $orderHeader['payment_method'] == 'cod'): ?>
    <!-- More code... -->
                        <!-- Show cash received button only for delivered COD orders -->
                        <div style="margin-top: 20px; padding: 15px; background-color: #fff3cd; border-radius: 4px;">
                            <p><strong>Payment Collection:</strong> Mark this order as Cash Received once payment of ₹<?php echo number_format($orderHeader['total_amount'], 2); ?> has been collected from the customer.</p>
                            <form method="POST" action="order_manage.php?<?php echo isset($_GET['view_cod_order']) ? 'view_cod_order='.$_GET['view_cod_order'] : 'view_order='.$orderHeader['id']; ?>" style="margin-top: 10px;">
                                <input type="hidden" name="order_id" value="<?php echo $orderHeader['id']; ?>">
                                <button type="submit" name="cash_received" class="btn btn-cash">Mark as Cash Received</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php if ($orderHeader['order_status'] == 'delivered' || $orderHeader['order_status'] == 'cash_received'): ?>
                        <!-- For delivered/cash_received orders, show notice and disabled form -->
                        <div class="disabled-note">
                            <p>
                                <?php if ($orderHeader['order_status'] == 'cash_received'): ?>
                                    This order has been marked as Cash Received and its status cannot be changed.
                                <?php else: ?>
                                    This order has been delivered and its status can only be changed to Cash Received (for COD orders).
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="status-update-form">
                            <label for="new_status">Current Status:</label>
                            <select id="new_status" class="status-select" disabled>
                                <option value="<?php echo $orderHeader['order_status']; ?>" selected>
                                    <?php 
                                    if ($orderHeader['order_status'] === 'cash_received') {
                                        echo 'Cash Received';
                                    } else {
                                        echo ucfirst($orderHeader['order_status']); 
                                    }
                                    ?>
                                </option>
                            </select>
                            <button class="btn btn-disabled" disabled>Update Status</button>
                        </div>
                    <?php else: ?>
                        <!-- For non-delivered orders, show normal update form -->
                        <form class="status-update-form" method="POST" action="order_manage.php">
                            <input type="hidden" name="order_id" value="<?php echo $orderHeader['id']; ?>">
                            <label for="new_status">Update Status:</label>
                            <select id="new_status" name="new_status" class="status-select">
                                <option value="pending" <?php echo ($orderHeader['order_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo ($orderHeader['order_status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo ($orderHeader['order_status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo ($orderHeader['order_status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cash_received" <?php echo ($orderHeader['order_status'] == 'cash_received') ? 'selected' : ''; ?>>Cash Received</option>
                                <option value="cancelled" <?php echo ($orderHeader['order_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-update">Update Status</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>© 2025 Yards of Grace. All rights reserved.</p>
    </footer>

    <script>
        // JavaScript for UI interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight success message and fade it out after 3 seconds
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(function() {
                    successAlert.style.opacity = '0';
                    successAlert.style.transition = 'opacity 1s';
                    setTimeout(function() {
                        successAlert.style.display = 'none';
                    }, 1000);
                }, 3000);
            }
            
            // Add active class to sidebar menu items
            document.querySelectorAll('.sidebar-menu li').forEach(item => {
                item.addEventListener('click', function() {
                    const activeItem = document.querySelector('.sidebar-menu li.active');
                    if (activeItem) {
                        activeItem.classList.remove('active');
                    }
                    this.classList.add('active');
                });
            });

            // Add an alert to confirm form submission
            const cashReceivedForm = document.querySelector('form[action="order_manage.php"]');
            if (cashReceivedForm) {
                cashReceivedForm.addEventListener('submit', function(event) {
                    alert('Submitting form to mark as Cash Received');
                });
            }
        });
    </script>
</body>
</html>