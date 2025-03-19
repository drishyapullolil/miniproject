<?php
// create_order.php - Creates a Razorpay order

// Include configuration files
require_once 'config.php';
require_once 'razorpay-php/Razorpay.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['amount']) || !isset($input['shipping_address'])) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    // Initialize Razorpay API
    $api = new Razorpay\Api\Api($razorpay_key_id, $razorpay_key_secret);
    
    // Create order
    $orderData = [
        'receipt'         => 'order_' . time(),
        'amount'          => $input['amount'], // amount in the smallest currency unit (paise)
        'currency'        => $input['currency'],
        'payment_capture' => 1 // auto capture
    ];
    
    $razorpayOrder = $api->order->create($orderData);
    
    // Store order details in session for verification later
    $_SESSION['razorpay_order_id'] = $razorpayOrder['id'];
    $_SESSION['shipping_address'] = $input['shipping_address'];
    
    // Return order ID to JavaScript
    echo json_encode(['order_id' => $razorpayOrder['id']]);
    
} catch (Exception $e) {
    // Return error
    error_log("Razorpay Order Creation Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>

<?php
// verify_payment.php - Verifies Razorpay payment and creates order in database

// Include configuration files
require_once 'config.php';
require_once 'razorpay-php/Razorpay.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['payment_id']) || !isset($input['order_id']) || !isset($input['signature'])) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    // Initialize Razorpay API
    $api = new Razorpay\Api\Api($razorpay_key_id, $razorpay_key_secret);
    
    // Verify signature
    $attributes = [
        'razorpay_payment_id' => $input['payment_id'],
        'razorpay_order_id'   => $input['order_id'],
        'razorpay_signature'  => $input['signature']
    ];
    
    $api->utility->verifyPaymentSignature($attributes);
    
    // Signature verified, get order details
    $payment = $api->payment->fetch($input['payment_id']);
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Create order in database
        $order_date = date('Y-m-d H:i:s');
        $user_id = $_SESSION['user_id'];
        $shipping_address = json_encode($input['shipping_address']); // Store as JSON
        $payment_method = 'razorpay';
        $payment_id = $input['payment_id'];
        $order_status = 'paid';
        
        // Calculate total amount from cart
        $total_amount = 0;
        $cart_query = "SELECT c.saree_id, c.quantity, s.price FROM cart c 
                       JOIN sarees s ON c.saree_id = s.id 
                       WHERE c.user_id = ?";
        $stmt = $conn->prepare($cart_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();
        
        $cart_items = [];
        while ($row = $cart_result->fetch_assoc()) {
            $cart_items[] = $row;
            $total_amount += ($row['price'] * $row['quantity']);
        }
        
        // Insert order
        $insert_order = "INSERT INTO orders (user_id, order_date, total_amount, shipping_address, 
                         payment_method, payment_id, order_status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_order);
        $stmt->bind_param("isdsss", $user_id, $order_date, $total_amount, $shipping_address, 
                                  $payment_method, $payment_id, $order_status);
        $stmt->execute();
        $order_id = $conn->insert_id;
        
        // Insert order items
        $insert_item = "INSERT INTO order_items (order_id, saree_id, quantity, price) 
                       VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_item);
        
        foreach ($cart_items as $item) {
            $stmt->bind_param("iiid", $order_id, $item['saree_id'], $item['quantity'], $item['price']);
            $stmt->execute();
        }
        
        // Clear cart
        $clear_cart = "DELETE FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($clear_cart);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Store order ID in session
        $_SESSION['last_order_id'] = $order_id;
        
        // Return success
        echo json_encode(['success' => true, 'order_id' => $order_id]);
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        error_log("Order Creation Error: " . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} catch (Exception $e) {
    // Payment verification failed
    error_log("Payment Verification Error: " . $e->getMessage());
    echo json_encode(['error' => 'Payment verification failed. ' . $e->getMessage()]);
}
?>

<?php
// create_cod_order.php - Creates a Cash on Delivery order

// Include configuration files
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['shipping_address'])) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Create order in database
    $order_date = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'];
    $shipping_address = json_encode($input['shipping_address']); // Store as JSON
    $payment_method = 'cod';
    $payment_id = 'COD_' . time(); // Generate a unique reference for COD
    $order_status = 'pending'; // For COD, initial status is pending
    
    // Calculate total amount from cart
    $total_amount = 0;
    $cart_query = "SELECT c.saree_id, c.quantity, s.price FROM cart c 
                   JOIN sarees s ON c.saree_id = s.id 
                   WHERE c.user_id = ?";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();
    
    $cart_items = [];
    while ($row = $cart_result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_amount += ($row['price'] * $row['quantity']);
    }
    
    // Check if cart is empty
    if (empty($cart_items)) {
        $conn->rollback();
        echo json_encode(['error' => 'Your cart is empty']);
        exit;
    }
    
    // Insert order
    $insert_order = "INSERT INTO orders (user_id, order_date, total_amount, shipping_address, 
                     payment_method, payment_id, order_status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_order);
    $stmt->bind_param("isdsss", $user_id, $order_date, $total_amount, $shipping_address, 
                              $payment_method, $payment_id, $order_status);
    $stmt->execute();
    $order_id = $conn->insert_id;
    
    // Insert order items
    $insert_item = "INSERT INTO order_items (order_id, saree_id, quantity, price) 
                   VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_item);
    
    foreach ($cart_items as $item) {
        $stmt->bind_param("iiid", $order_id, $item['saree_id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }
    
    // Clear cart
    $clear_cart = "DELETE FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($clear_cart);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Store order ID in session
    $_SESSION['last_order_id'] = $order_id;
    
    // Return success
    echo json_encode(['success' => true, 'order_id' => $order_id]);
    
} catch (Exception $e) {
    // Roll back transaction on error
    $conn->rollback();
    error_log("Order Creation Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>

<?php
// order_confirmation.php - Order confirmation page

// Include configuration files
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get order ID from URL or session
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 
           (isset($_SESSION['last_order_id']) ? $_SESSION['last_order_id'] : 0);

if (!$order_id) {
    header('Location: account.php');
    exit;
}

// Fetch order details
$order_query = "SELECT o.*, DATE_FORMAT(o.order_date, '%d %b %Y %h:%i %p') AS formatted_date
               FROM orders o 
               WHERE o.id = ? AND o.user_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    header('Location: account.php');
    exit;
}

$order = $order_result->fetch_assoc();
$shipping_address = json_decode($order['shipping_address'], true);

// Fetch order items
$items_query = "SELECT oi.*, s.name, s.image FROM order_items oi 
                JOIN sarees s ON oi.saree_id = s.id 
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$order_items = [];

while ($row = $items_result->fetch_assoc()) {
    $order_items[] = $row;
}

// Include header
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Include your CSS styles here -->
</head>
<body>
    <div class="container">
        <div class="notification notification-success">
            <i class="fas fa-check-circle"></i> Your order has been successfully placed! Order ID: #<?php echo $order_id; ?>
        </div>
        
        <h1 class="page-title">Order Confirmation</h1>
        
        <div class="order-details">
            <div class="section-title">Order Information</div>
            <div class="detail-row">
                <span class="label">Order Date:</span>
                <span class="value"><?php echo $order['formatted_date']; ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Order Status:</span>
                <span class="value status-badge status-<?php echo $order['order_status']; ?>">
                    <?php echo ucfirst($order['order_status']); ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="label">Payment Method:</span>
                <span class="value">
                    <?php 
                    if ($order['payment_method'] === 'razorpay') {
                        echo 'Paid online (Razorpay)';
                    } else {
                        echo 'Cash on Delivery';
                    }
                    ?>
                </span>
            </div>
            
            <div class="section-title">Shipping Address</div>
            <div class="address-box">
                <p><strong><?php echo htmlspecialchars($shipping_address['full_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($shipping_address['address_line1']); ?></p>
                <?php if (!empty($shipping_address['address_line2'])): ?>
                    <p><?php echo htmlspecialchars($shipping_address['address_line2']); ?></p>
                <?php endif; ?>
                <p>
                    <?php echo htmlspecialchars($shipping_address['city']); ?>, 
                    <?php echo htmlspecialchars($shipping_address['state']); ?> - 
                    <?php echo htmlspecialchars($shipping_address['pincode']); ?>
                </p>
                <p>Phone: <?php echo htmlspecialchars($shipping_address['phone']); ?></p>
                <p>Email: <?php echo htmlspecialchars($shipping_address['email']); ?></p>
            </div>
            
            <div class="section-title">Order Items</div>
            <div class="order-items">
                <?php foreach ($order_items as $item): ?>
                <div class="order-item">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <div class="item-details">
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="item-meta">
                            <span class="quantity">Qty: <?php echo $item['quantity']; ?></span>
                            <span class="price">₹<?php echo number_format($item['price'], 2); ?></span>
                        </div>
                        <div class="item-total">
                            ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-totals">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Shipping</span>
                    <span>Free</span>
                </div>
                <div class="total-row grand-total">
                    <span>Total</span>
                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="email-notification">
            <i class="fas fa-envelope"></i> A confirmation email has been sent to <?php echo htmlspecialchars($shipping_address['email']); ?>
        </div>
        
        <div class="action-buttons">
            <a href="shop.php" class="btn">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
            <a href="account.php?section=orders" class="btn btn-outline">
                <i class="fas fa-list"></i> View All Orders
            </a>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>