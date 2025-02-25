<?php
session_start();
require_once 'db.php';

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

include 'header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

$errors = [];
$success = '';
$userId = $_SESSION['user_id'];

// Get user details
$userQuery = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

// Handle direct buy now from product page
if (isset($_POST['buy_now']) && isset($_POST['saree_id'])) {
    $sareeId = (int)$_POST['saree_id'];
    
    // Fetch saree details
    $sareeQuery = "SELECT * FROM sarees WHERE id = ?";
    $stmt = $conn->prepare($sareeQuery);
    $stmt->bind_param("i", $sareeId);
    $stmt->execute();
    $sareeResult = $stmt->get_result();
    $saree = $sareeResult->fetch_assoc();
    
    if ($saree) {
        $_SESSION['cart'] = [[
            'saree_id' => $saree['id'],
            'name' => $saree['name'],
            'price' => $saree['price'],
            'quantity' => 1,
            'image' => $saree['image']
        ]];
    }
}

// Get cart items
$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$totalAmount = 0;

foreach ($cartItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

// Process order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Validate required fields
    $required_fields = ['full_name', 'address_line1', 'city', 'state', 'pincode', 'phone', 'payment_method'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    // Validate phone number
    if (!empty($_POST['phone']) && !preg_match('/^[0-9]{10}$/', $_POST['phone'])) {
        $errors[] = 'Please enter a valid 10-digit phone number';
    }

    // Validate pincode
    if (!empty($_POST['pincode']) && !preg_match('/^[0-9]{6}$/', $_POST['pincode'])) {
        $errors[] = 'Please enter a valid 6-digit PIN code';
    }

    // Validate payment method details
    $paymentMethod = $_POST['payment_method'];
    if ($paymentMethod === 'card') {
        if (empty($_POST['card_number']) || !preg_match('/^[0-9]{16}$/', $_POST['card_number'])) {
            $errors[] = 'Please enter a valid 16-digit card number';
        }
        if (empty($_POST['card_expiry']) || !preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2})$/', $_POST['card_expiry'])) {
            $errors[] = 'Please enter a valid expiry date (MM/YY)';
        }
        if (empty($_POST['card_cvv']) || !preg_match('/^[0-9]{3,4}$/', $_POST['card_cvv'])) {
            $errors[] = 'Please enter a valid CVV';
        }
    } elseif ($paymentMethod === 'upi') {
        if (empty($_POST['upi_id']) || !preg_match('/^[\w.-]+@[\w.-]+$/', $_POST['upi_id'])) {
            $errors[] = 'Please enter a valid UPI ID';
        }
    }

    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Format address
            $fullAddress = implode("\n", [
                $_POST['full_name'],
                $_POST['address_line1'],
                !empty($_POST['address_line2']) ? $_POST['address_line2'] : '',
                $_POST['city'] . ", " . $_POST['state'],
                "PIN: " . $_POST['pincode'],
                "Phone: " . $_POST['phone']
            ]);

            // Create order
            $orderQuery = "INSERT INTO orders (user_id, name, total_amount, order_status, address, payment_method) 
                          VALUES (?, ?, ?, 'pending', ?, ?)";
            $stmt = $conn->prepare($orderQuery);
            $stmt->bind_param("isdss", $userId, $_POST['full_name'], $totalAmount, $fullAddress, $paymentMethod);
            $stmt->execute();
            $orderId = $conn->insert_id;

            // Process order details and update stock
            foreach ($cartItems as $item) {
                // Check stock availability
                $stockQuery = "SELECT stock FROM sarees WHERE id = ?";
                $stmt = $conn->prepare($stockQuery);
                $stmt->bind_param("i", $item['saree_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $currentStock = $result->fetch_assoc()['stock'];

                if ($currentStock < $item['quantity']) {
                    throw new Exception("Insufficient stock for " . $item['name']);
                }

                // Add order details
                $detailsQuery = "INSERT INTO order_details (order_id, saree_id, quantity, price) 
                                VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($detailsQuery);
                $stmt->bind_param("iiid", $orderId, $item['saree_id'], $item['quantity'], $item['price']);
                $stmt->execute();

                // Update stock
                $updateStockQuery = "UPDATE sarees SET stock = stock - ? WHERE id = ?";
                $stmt = $conn->prepare($updateStockQuery);
                $stmt->bind_param("ii", $item['quantity'], $item['saree_id']);
                $stmt->execute();

                // Record stock history
                $stockHistoryQuery = "INSERT INTO saree_stock_history (saree_id, stock_added, previous_stock, new_stock, updated_by) 
                                    VALUES (?, ?, ?, ?, ?)";
                $newStock = $currentStock - $item['quantity'];
                $stockChange = -$item['quantity'];
                $stmt = $conn->prepare($stockHistoryQuery);
                $stmt->bind_param("iiiii", $item['saree_id'], $stockChange, $currentStock, $newStock, $userId);
                $stmt->execute();
            }

            // Insert payment details
            $transactionId = null;
            if ($paymentMethod === 'card') {
                $transactionId = $_POST['card_number'];
            } elseif ($paymentMethod === 'upi') {
                $transactionId = $_POST['upi_id'];
            }

            $paymentStatus = 'completed';
            $paymentQuery = "INSERT INTO payments (order_id, payment_method, transaction_id, amount, status) 
                            VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($paymentQuery);
            $stmt->bind_param("issds", $orderId, $paymentMethod, $transactionId, $totalAmount, $paymentStatus);
            $stmt->execute();

            $conn->commit();
            unset($_SESSION['cart']);
            $success = "Order placed successfully! Your order ID is: " . $orderId;

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Purchase - Yards of Grace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e034f;
            --primary-light: #6c0c6d;
            --secondary-color: #f8f4ff;
            --accent-color: #8e44ad;
            --success-color: #2ecc71;
            --error-color: #e74c3c;
            --text-color: #333;
            --border-color: #e1e1e1;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --radius: 12px;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-title {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 2rem;
            text-align: center;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        .form-section {
            background: white;
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .required::after {
            content: "*";
            color: var(--error-color);
            margin-left: 4px;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(142, 68, 173, 0.1);
        }

        .form-control.error {
            border-color: var(--error-color);
        }

        .error-message {
            color: var(--error-color);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .input-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .order-summary {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 2rem;
        }

        .order-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .order-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--radius);
        }

        .item-details h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
        }

        .item-price {
            font-weight: 600;
            color: var(--primary-color);
        }

        .order-total {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 2px solid var(--border-color);
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
        }

        .payment-section {
            display: none;
            margin-top: 1rem;
        }

        .payment-method-select {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .payment-option {
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-option.selected {
            border-color: var(--primary-color);
            background: var(--secondary-color);
        }

        .payment-option i {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .notification {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

       

        .step-number {
            width: 30px;
            height: 30px;
            background: var(--border-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            color: white;
            font-weight: 600;
            position: relative;
            z-index: 2;
        }

        .step-number.active {
            background: var(--primary-color);
        }

        .step-number.completed {
            background: var(--success-color);
        }

        .step-title {
            font-size: 0.875rem;
            color: var(--text-color);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .input-group {
                grid-template-columns: 1fr;
            }

            .payment-method-select {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
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
                <div>
                    <p><?php echo htmlspecialchars($success); ?></p>
                    <a href="orders.php" class="btn btn-primary" style="margin-top: 1rem;">View Your Orders</a>
                </div>
            </div>
        <?php else: ?>
            <h1 class="page-title">Complete Your Purchase</h1>

            <div class="progress-steps">
                <div class="progress-step">
                    <div class="step-number active">1</div>
                    <div class="step-title">Shipping</div>
                </div>
                <div class="progress-step">
                    <div class="step-number">2</div>
                    <div class="step-title">Payment</div>
                </div>
                <div class="progress-step">
                    <div class="step-number">3</div>
                    <div class="step-title">Confirmation</div>
                </div>
            </div>

            <div class="checkout-grid">
                <div class="checkout-form">
                    <form method="POST" id="checkoutForm" novalidate>
                        <div class="form-section">
                            <h2 class="section-title">
                                <i class="fas fa-shipping-fast"></i>
                                Shipping Information
                            </h2>
                            
                            <div class="form-group">
                                <label for="full_name" class="required">Full Name</label>
                                <input type="text" 
                                       id="full_name" 
                                       name="full_name" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="address_line1" class="required">Address Line 1</label>
                                <input type="text" 
                                       id="address_line1" 
                                       name="address_line1" 
                                       class="form-control"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="address_line2">Address Line 2 (Optional)</label>
                                <input type="text" 
                                       id="address_line2" 
                                       name="address_line2" 
                                       class="form-control">
                            </div>

                            <div class="input-group">
                                <div class="form-group">
                                    <label for="city" class="required">City</label>
                                    <input type="text" 
                                           id="city" 
                                           name="city" 
                                           class="form-control"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="state" class="required">State</label>
                                    <input type="text" 
                                           id="state" 
                                           name="state" 
                                           class="form-control"
                                           required>
                                </div>
                            </div>

                            <div class="input-group">
                                <div class="form-group">
                                    <label for="pincode" class="required">PIN Code</label>
                                    <input type="text" 
                                           id="pincode" 
                                           name="pincode" 
                                           class="form-control"
                                           pattern="[0-9]{6}"
                                           maxlength="6"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="phone" class="required">Phone Number</label>
                                    <input type="tel" 
                                           id="phone" 
                                           name="phone" 
                                           class="form-control"
                                           pattern="[0-9]{10}"
                                           maxlength="10"
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h2 class="section-title">
                                <i class="fas fa-credit-card"></i>
                                Payment Method
                            </h2>

                            <div class="payment-method-select">
                                <label class="payment-option" for="cod">
                                    <input type="radio" 
                                           id="cod" 
                                           name="payment_method" 
                                           value="cod" 
                                           required 
                                           hidden>
                                    <i class="fas fa-money-bill-wave"></i>
                                    <div>Cash on Delivery</div>
                                </label>

                                <label class="payment-option" for="upi">
                                    <input type="radio" 
                                           id="upi" 
                                           name="payment_method" 
                                           value="upi" 
                                           required 
                                           hidden>
                                    <i class="fas fa-mobile-alt"></i>
                                    <div>UPI Payment</div>
                                </label>

                                <label class="payment-option" for="card">
                                    <input type="radio" 
                                           id="card" 
                                           name="payment_method" 
                                           value="card" 
                                           required 
                                           hidden>
                                    <i class="fas fa-credit-card"></i>
                                    <div>Card Payment</div>
                                </label>
                            </div>
                            
                            <!-- UPI Payment Section -->
                            <div id="upi-section" class="payment-section">
                                <div class="form-group">
                                    <label for="upi_id" class="required">UPI ID</label>
                                    <input type="text" 
                                           id="upi_id" 
                                           name="upi_id" 
                                           class="form-control"
                                           placeholder="username@bank">
                                </div>
                            </div>

                            <!-- Card Payment Section -->
                            <div id="card-section" class="payment-section">
                                <div class="form-group">
                                    <label for="card_number" class="required">Card Number</label>
                                    <input type="text" 
                                           id="card_number" 
                                           name="card_number" 
                                           class="form-control"
                                           placeholder="1234 5678 9012 3456"
                                           maxlength="16">
                                </div>

                                <div class="input-group">
                                    <div class="form-group">
                                        <label for="card_expiry" class="required">Expiry Date</label>
                                        <input type="text" 
                                               id="card_expiry" 
                                               name="card_expiry" 
                                               class="form-control"
                                               placeholder="MM/YY"
                                               maxlength="5">
                                    </div>

                                    <div class="form-group">
                                        <label for="card_cvv" class="required">CVV</label>
                                        <input type="password" 
                                               id="card_cvv" 
                                               name="card_cvv" 
                                               class="form-control"
                                               placeholder="123"
                                               maxlength="4">
                                    </div>
                                    
                                </div>
                            </div>
                            <button type="submit" name="place_order" form="checkoutForm" class="btn btn-primary">
                            Place Order
                        </button>
                        </div>
                        
                    </form>
                </div>

                <div class="order-summary">
                    <h2 class="section-title">Order Summary</h2>
                    <?php if (empty($cartItems)): ?>
                        <p>Your cart is empty</p>
                    <?php else: ?>
                        <?php foreach ($cartItems as $item): ?>
                            <div class="order-item">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p>Quantity: <?php echo $item['quantity']; ?></p>
                                </div>
                                <div class="item-price">
                                    ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="order-total">
                            <span>Total Amount:</span>
                            <span>₹<?php echo number_format($totalAmount, 2); ?></span>
                        </div>

                        
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Handle payment method selection
        document.querySelectorAll('[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Reset all payment sections
                document.querySelectorAll('.payment-section').forEach(section => {
                    section.style.display = 'none';
                });
                
                // Reset all payment options
                document.querySelectorAll('.payment-option').forEach(option => {
                    option.classList.remove('selected');
                });
                
                // Show selected payment section
                if (this.value !== 'cod') {
                    document.getElementById(this.value + '-section').style.display = 'block';
                }
                
                // Highlight selected option
                this.closest('.payment-option').classList.add('selected');
            });
        });

        // Form validation
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const errors = [];
            
            // Reset previous errors
            document.querySelectorAll('.form-control').forEach(input => {
                input.classList.remove('error');
            });
            
            // Validate required fields
            document.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    errors.push(`${field.previousElementSibling.textContent.replace(' *', '')} is required`);
                }
            });

            // Validate phone number
            const phone = document.getElementById('phone');
            if (phone.value && !phone.value.match(/^[0-9]{10}$/)) {
                phone.classList.add('error');
                errors.push('Please enter a valid 10-digit phone number');
            }

            // Validate pincode
            const pincode = document.getElementById('pincode');
            if (pincode.value && !pincode.value.match(/^[0-9]{6}$/)) {
                pincode.classList.add('error');
                errors.push('Please enter a valid 6-digit PIN code');
            }

            // Payment method specific validation
            const paymentMethod = document.querySelector('[name="payment_method"]:checked')?.value;
            if (paymentMethod === 'card') {
                const cardNumber = document.getElementById('card_number');
                const cardExpiry = document.getElementById('card_expiry');
                const cardCvv = document.getElementById('card_cvv');

                if (!cardNumber.value.match(/^[0-9]{16}$/)) {
                    cardNumber.classList.add('error');
                    errors.push('Please enter a valid 16-digit card number');
                }
                if (!cardExpiry.value.match(/^(0[1-9]|1[0-2])\/?([0-9]{2})$/)) {
                    cardExpiry.classList.add('error');
                    errors.push('Please enter a valid expiry date (MM/YY)');
                }
                if (!cardCvv.value.match(/^[0-9]{3,4}$/)) {
                    cardCvv.classList.add('error');
                    errors.push('Please enter a valid CVV');
                }
            } else if (paymentMethod === 'upi') {
                const upiId = document.getElementById('upi_id');
                if (!upiId.value.match(/^[\w.-]+@[\w.-]+$/)) {
                    upiId.classList.add('error');
                    errors.push('Please enter a valid UPI ID');
                }
            }

            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
                window.scrollTo(0, 0);
            }
        });

        // Format card expiry date
        document.getElementById('card_expiry')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            e.target.value = value.slice(0, 5);
        });

        // Format card number
        document.getElementById('card_number')?.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 16);
        });
        

// Initialize step tracking
let currentStep = 1;
const totalSteps = 3;

// Get all form sections and the progress step indicators
const shippingSection = document.querySelector('.checkout-form .form-section:nth-child(1)');
const paymentSection = document.querySelector('.checkout-form .form-section:nth-child(2)');
const progressSteps = document.querySelectorAll('.progress-step .step-number');
const checkoutForm = document.getElementById('checkoutForm');

// Function to validate shipping information
function validateShippingInfo() {
    const requiredFields = ['full_name', 'address_line1', 'city', 'state', 'pincode', 'phone'];
    const errors = [];
    
    requiredFields.forEach(field => {
        const input = document.getElementById(field);
        if (!input.value.trim()) {
            input.classList.add('error');
            errors.push(`${field.replace('_', ' ')} is required`);
        }
    });
    
    // Validate phone number and pincode
    // Validate phone number for Indian format
if (phone.value && !phone.value.match(/^[6-9][0-9]{9}$/)) {
    phone.classList.add('error');
    errors.push('Please enter a valid 10-digit Indian phone number starting with 6, 7, 8, or 9');
}
    const pincode = document.getElementById('pincode');
    
    if (phone.value && !phone.value.match(/^[0-9]{10}$/)) {
        phone.classList.add('error');
        errors.push('Please enter a valid 10-digit phone number');
    }
    
    if (pincode.value && !pincode.value.match(/^[0-9]{6}$/)) {
        pincode.classList.add('error');
        errors.push('Please enter a valid 6-digit PIN code');
    }
    
    return errors;
}

// Function to validate payment information
function validatePaymentInfo() {
    const errors = [];
    const paymentMethod = document.querySelector('[name="payment_method"]:checked')?.value;
    
    if (!paymentMethod) {
        errors.push('Please select a payment method');
        return errors;
    }
    
    if (paymentMethod === 'card') {
        const cardNumber = document.getElementById('card_number');
        const cardExpiry = document.getElementById('card_expiry');
        const cardCvv = document.getElementById('card_cvv');

        if (!cardNumber.value.match(/^[0-9]{16}$/)) {
            cardNumber.classList.add('error');
            errors.push('Please enter a valid 16-digit card number');
        }
        if (!cardExpiry.value.match(/^(0[1-9]|1[0-2])\/?([0-9]{2})$/)) {
            cardExpiry.classList.add('error');
            errors.push('Please enter a valid expiry date (MM/YY)');
        }
        if (!cardCvv.value.match(/^[0-9]{3,4}$/)) {
            cardCvv.classList.add('error');
            errors.push('Please enter a valid CVV');
        }
    } else if (paymentMethod === 'upi') {
        const upiId = document.getElementById('upi_id');
        if (!upiId.value.match(/^[\w.-]+@[\w.-]+$/)) {
            upiId.classList.add('error');
            errors.push('Please enter a valid UPI ID');
        }
    }
    
    return errors;
}

// Function to update the progress steps
function updateProgressSteps(step) {
    progressSteps.forEach((stepNumber, index) => {
        if (index + 1 < step) {
            stepNumber.classList.remove('active');
            stepNumber.classList.add('completed');
        } else if (index + 1 === step) {
            stepNumber.classList.add('active');
            stepNumber.classList.remove('completed');
        } else {
            stepNumber.classList.remove('active', 'completed');
        }
    });
}

// Add navigation buttons to form sections
const shippingButtons = document.createElement('div');
shippingButtons.className = 'form-navigation';
shippingButtons.innerHTML = `
    <button type="button" class="btn btn-primary next-step">Continue to Payment</button>
`;
shippingSection.appendChild(shippingButtons);

const paymentButtons = document.createElement('div');
paymentButtons.className = 'form-navigation';
paymentButtons.innerHTML = `
    <button type="button" class="btn btn-secondary prev-step">Back to Shopping</button>
    <button type="button" class="btn btn-primary next-step">Review Order</button>
`;
paymentSection.appendChild(paymentButtons);

// Add styles for navigation
const style = document.createElement('style');
style.textContent = `
    .form-navigation {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .form-section {
        display: none;
    }
    .form-section.active {
        display: block;
    }
    .step-number.completed {
        background: #2ecc71;
    }
    .step-number.completed::after {
        content: '✓';
        position: absolute;
        color: white;
    }
`;
document.head.appendChild(style);

// Show initial step
shippingSection.classList.add('active');
updateProgressSteps(currentStep);

// Handle navigation between steps
document.querySelectorAll('.next-step').forEach(button => {
    button.addEventListener('click', () => {
        let errors = [];
        if (currentStep === 1) {
            errors = validateShippingInfo();
        } else if (currentStep === 2) {
            errors = validatePaymentInfo();
        }
        
        if (errors.length > 0) {
            alert(errors.join('\n'));
            return;
        }
        
        if (currentStep < totalSteps) {
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            
            currentStep++;
            
            if (currentStep === 2) {
                paymentSection.classList.add('active');
            } else if (currentStep === 3) {
                checkoutForm.submit();
            }
            
            updateProgressSteps(currentStep);
        }
    });
});

document.querySelectorAll('.prev-step').forEach(button => {
    button.addEventListener('click', () => {
        if (currentStep > 1) {
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            
            currentStep--;
            
            if (currentStep === 1) {
                shippingSection.classList.add('active');
            }
            
            updateProgressSteps(currentStep);
        }
    });
});
    </script>
    <?php if ($success): ?>
    <div class="container">
        <div class="notification success" style="text-align: center; max-width: 600px; margin: 2rem auto;">
            <div style="width: 80px; height: 80px; background-color: #d4edda; border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-check" style="font-size: 40px; color: #28a745;"></i>
            </div>
            
            <h2 style="color: #155724; font-size: 24px; margin-bottom: 1rem;">Thank You for Your Order!</h2>
            <p><?php echo htmlspecialchars($success); ?></p>
            
            <div style="background: white; border-radius: 12px; padding: 1.5rem; margin: 1.5rem 0; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
                <div style="border-bottom: 1px solid #e1e1e1; padding-bottom: 1rem; margin-bottom: 1rem;">
                    <p><strong>Order Date:</strong> <?php echo date('F d, Y'); ?></p>
                    <p><strong>Status:</strong> Processing</p>
                </div>
                <p>We'll send you an email with the order details and tracking information.</p>
            </div>
            
           
                <a href="index.php" class="btn" style="background: white; color: var(--text-color); border: 1px solid var(--border-color); display: inline-flex; align-items: center; gap: 0.5rem;">
                    Continue Shipping
                    <i class="fas fa-arrow-right"></i>
                    
                </a>
            </div>
        </div>
    </div>
    
    <style>
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .btn.btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn.btn-primary:hover {
            background: var(--primary-light);
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .notification.success {
            animation: slideUp 0.5s ease forwards;
        }
    </style>

    <script>
        // Confetti animation for order success
        document.addEventListener('DOMContentLoaded', function() {
            const colors = ['#4e034f', '#6c0c6d', '#8e44ad'];
            const numConfetti = 100;
            
            function createConfetti() {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-10px';
                confetti.style.borderRadius = '50%';
                confetti.style.zIndex = '1000';
                document.body.appendChild(confetti);
                
                const animation = confetti.animate([
                    { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
                    { transform: `translateY(100vh) rotate(${Math.random() * 360}deg)`, opacity: 0 }
                ], {
                    duration: Math.random() * 2000 + 2000,
                    easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
                });
                
                animation.onfinish = () => confetti.remove();
            }
            
            for (let i = 0; i < numConfetti; i++) {
                setTimeout(createConfetti, Math.random() * 2000);
            }
        });
    </script>
<?php endif; ?>

    <?php include 'footer.php'; ?>
</body>
</html>