<?php
session_start();
require_once 'db.php';
include_once 'email_functions.php';

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
if (isset($_POST['buy_now'])) {
    $sareeId = (int)$_POST['saree_id'];

    // First, let's check your categories table structure
    $sareeQuery = "SELECT s.*, c.category_name 
                   FROM sarees s 
                   LEFT JOIN categories c ON s.category_id = c.id 
                   WHERE s.id = ?";
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

// Handle Razorpay payment success callback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razorpay_payment_id'])) {
    $paymentId = $_POST['razorpay_payment_id'];
    $orderId = $_POST['razorpay_order_id'];
    $signature = $_POST['razorpay_signature'];

    // Validate payment signature with Razorpay (in production, add verification here)

    // Process order with the submitted shipping details
    $fullName = $_POST['full_name'];
    $addressLine1 = $_POST['address_line1'];
    $addressLine2 = $_POST['address_line2'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    $phone = $_POST['phone'];

    try {
        $conn->begin_transaction();

        // Format address
        $fullAddress = implode("\n", [
            $fullName,
            $addressLine1,
            !empty($addressLine2) ? $addressLine2 : '',
            $city . ", " . $state,
            "PIN: " . $pincode,
            "Phone: " . $phone
        ]);

        // Create order
        $orderQuery = "INSERT INTO orders (user_id, name, total_amount, order_status, address, payment_method) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        $orderStatus = 'confirmed'; // Make sure this matches an allowed ENUM value
        $paymentMethod = 'razorpay';

        $stmt = $conn->prepare($orderQuery);
        $stmt->bind_param("isdsss", $userId, $fullName, $totalAmount, $orderStatus, $fullAddress, $paymentMethod);
        $stmt->execute();

        // Get the newly generated order ID
        $dbOrderId = $conn->insert_id;

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
        $paymentStatus = 'completed';
        $paymentQuery = "INSERT INTO payments (order_id, payment_method, transaction_id, amount, status) 
                         VALUES (?, 'razorpay', ?, ?, ?)";
        $stmt = $conn->prepare($paymentQuery);
        $stmt->bind_param("isds", $dbOrderId, $paymentId, $totalAmount, $paymentStatus);
        $stmt->execute();

        $conn->commit();
        unset($_SESSION['cart']);
        $success = "Order placed successfully! Your order ID is: " . $dbOrderId;

        // Prepare order details for email
        $orderDetails = [
            'order_id' => $dbOrderId,
            'items' => $cartItems,
            'total' => $totalAmount,
            'shipping_address' => $fullAddress,
            'payment_method' => 'Razorpay'
        ];

        // Send confirmation email
        try {
            $emailSent = sendOrderConfirmationEmail($orderDetails, $user['email'], $fullName);
            if ($emailSent) {
                $success .= " A confirmation email has been sent to your email address.";
            }
        } catch (Exception $emailException) {
            error_log("Email exception: " . $emailException->getMessage());
        }
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = $e->getMessage();
    }
}

// Process form validation before sending to Razorpay
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_order'])) {
    // Validate required fields
    $required_fields = ['full_name', 'address_line1', 'city', 'state', 'pincode', 'phone'];
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

    if (empty($errors)) {
        // Store shipping info in session for payment callback
        $_SESSION['shipping_info'] = [
            'full_name' => $_POST['full_name'],
            'address_line1' => $_POST['address_line1'],
            'address_line2' => $_POST['address_line2'] ?? '',
            'city' => $_POST['city'],
            'state' => $_POST['state'],
            'pincode' => $_POST['pincode'],
            'phone' => $_POST['phone']
        ];
    }
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Complete Your Purchase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="bstyles.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        .validation-message {
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .form-control.error {
            border-color: #dc3545;
            background-color: #fff8f8;
        }

        .form-control.valid {
            border-color: #198754;
            background-color: #f8fff8;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
    </style>
</head>
<body>
<script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <style>
        .goog-te-gadget-simple {
            background-color: transparent !important;
            border: none !important;
            padding: 0 !important;
            cursor: pointer;
        }

        .goog-te-gadget-simple span {
            color: white !important;
            font-size: 16px;
        }
    </style>
    <div class="container">
        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
            <a href="index.php">Home</a> <span>></span>
            <?php if (isset($saree) && !empty($saree)): ?>
                <a href="categories_user.php?category_id=<?php echo $saree['category_id']; ?>"><?php echo htmlspecialchars($saree['category_name']); ?></a> 
                <span>></span>
                <?php if (isset($hasValidSubcategory) && $hasValidSubcategory): ?>
                    <a href="categories_user.php?subcategory_id=<?php echo $saree['subcategory_id']; ?>"><?php echo htmlspecialchars($saree['subcategory_name']); ?></a>
                    <span>></span>
                <?php endif; ?>
                <a href="Traditional.php?id=<?php echo $saree['id']; ?>"><?php echo htmlspecialchars($saree['name']); ?></a>
                <span>></span>
            <?php endif; ?>
            <span>Checkout</span>
        </div>

        <h1 class="page-title">Complete Your Purchase</h1>

        <!-- Back to shopping button -->
        <div style="margin-bottom: 20px; text-align: center;">
            <?php if (isset($hasValidSubcategory) && $hasValidSubcategory): ?>
                <a href="categories_user.php?subcategory_id=<?php echo $saree['subcategory_id']; ?>" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            <?php else: ?>
                <a href="categories_user.php?category_id=<?php echo $saree['category_id']; ?>" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            <?php endif; ?>
        </div>

        <!-- Success and Error Messages -->
        <?php if (!empty($success)): ?>
            <div class="notification notification-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="notification notification-error">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <?php if (empty($success)): ?>
                <div class="notification notification-info">
                    <i class="fas fa-info-circle"></i> Your cart is empty. Please add items to your cart before checkout.
                </div>
                <div style="text-align: center; margin-top: 30px;">
                    <a href="shop.php" class="btn">Browse Products</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Checkout Form -->
            <div class="checkout-container">
                <form class="checkout-form" method="POST" action="" id="shipping-form">
                    <div class="section-title">Shipping Information</div>

                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo isset($user['name']) ? htmlspecialchars($user['name']) : ''; ?>" 
                               onkeyup="validateName(this)" required>
                        <div class="validation-message" id="name-validation"></div>
                    </div>

                    <div class="form-group">
                        <label for="address_line1">Address Line 1 *</label>
                        <input type="text" class="form-control" id="address_line1" name="address_line1" 
                               onkeyup="validateAddress(this)" required>
                        <div class="validation-message" id="address-validation"></div>
                    </div>

                    <div class="form-group">
                        <label for="address_line2">Address Line 2</label>
                        <input type="text" class="form-control" id="address_line2" name="address_line2">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City *</label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   onkeyup="validateCity(this)" required>
                            <div class="validation-message" id="city-validation"></div>
                        </div>

                        <div class="form-group">
                            <label for="state">State *</label>
                            <input type="text" class="form-control" id="state" name="state" 
                                   onkeyup="validateState(this)" required>
                            <div class="validation-message" id="state-validation"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="pincode">PIN Code *</label>
                            <input type="text" class="form-control" id="pincode" name="pincode" 
                                   onkeyup="validatePincode(this)" required>
                            <div class="validation-message" id="pincode-validation"></div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : ''; ?>" 
                                   onkeyup="validatePhone(this)" required>
                            <div class="validation-message" id="phone-validation"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" readonly>
                    </div>

                    <div class="email-notification">
                        <i class="fas fa-envelope"></i> Order confirmation will be sent to your email address. Please check your inbox after placing the order.
                    </div>

                    <div class="payment-info-container">
                        <div class="section-title">Payment Information</div>
                        <div class="payment-method-info">
                            <div class="razorpay-info">
                                <div class="payment-logo">
                                    <img src="images/razorpay-logo.png" alt="Razorpay" style="max-height: 40px;">
                                </div>
                                <p>You will be redirected to Razorpay's secure payment gateway to complete your payment.</p>
                                <div class="secure-payment-notice">
                                    <i class="fas fa-lock"></i> Payments are secure and encrypted
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="validate_order" value="1">
                    <button type="submit" id="proceed-to-payment" class="btn btn-block" style="margin-top: 30px;">
                        Proceed to Payment <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="order-summary">
                    <div class="section-title">Order Summary</div>

                    <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-price">₹<?php echo number_format($item['price'], 2); ?></div>

                            <div class="quantity-control">
                                <button type="button" class="quantity-btn" onclick="updateQuantity(<?php echo $item['saree_id']; ?>, -1)">-</button>
                                <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" id="qty-<?php echo $item['saree_id']; ?>" min="1" max="10" readonly>
                                <button type="button" class="quantity-btn" onclick="updateQuantity(<?php echo $item['saree_id']; ?>, 1)">+</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="order-totals">
                        <div class="total-row">
                            <span>Subtotal</span>
                            <span>₹<?php echo number_format($totalAmount, 2); ?></span>
                        </div>

                        <div class="total-row">
                            <span>Shipping</span>
                            <span>Free</span>
                        </div>

                        <div class="total-row grand-total">
                            <span>Total</span>
                            <span>₹<?php echo number_format($totalAmount, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden form for Razorpay payment submission -->
            <form name="razorpay-form" id="razorpay-form" action="" method="POST">
                <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" />
                <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" />
                <input type="hidden" name="razorpay_signature" id="razorpay_signature" />

                <!-- Include all shipping fields as hidden inputs -->
                <input type="hidden" name="full_name" id="hidden_full_name" />
                <input type="hidden" name="address_line1" id="hidden_address_line1" />
                <input type="hidden" name="address_line2" id="hidden_address_line2" />
                <input type="hidden" name="city" id="hidden_city" />
                <input type="hidden" name="state" id="hidden_state" />
                <input type="hidden" name="pincode" id="hidden_pincode" />
                <input type="hidden" name="phone" id="hidden_phone" />
            </form>
        <?php endif; ?>

        <!-- Reminder notification at the bottom -->
        <div class="notification notification-info" style="margin-top: 30px;">
            <i class="fas fa-exclamation-circle"></i> If you don't complete your purchase now, we'll send a reminder email to help you complete your order.
        </div>
    </div>

    <?php
    // Initialize Razorpay if form is validated
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_order']) && empty($errors)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Razorpay payment initialization
            var options = {
                "key": "rzp_test_qz3vZymFK7JynA", // Replace with your API key
                "amount": <?php echo ($totalAmount * 100); ?>, // Amount in smallest currency unit (paise)
                "currency": "INR",
                "name": "Yards of Grace",
                "description": "Purchase from Yards of Grace",
                "image": "path/to/your/logo.png", // Replace with your logo URL
                "handler": function (response) {
                    document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                    document.getElementById('razorpay_order_id').value = response.razorpay_order_id || "<?php echo time(); ?>";
                    document.getElementById('razorpay_signature').value = response.razorpay_signature || "";

                    // Copy form values to hidden form
                    document.getElementById('hidden_full_name').value = document.getElementById('full_name').value;
                    document.getElementById('hidden_address_line1').value = document.getElementById('address_line1').value;
                    document.getElementById('hidden_address_line2').value = document.getElementById('address_line2').value;
                    document.getElementById('hidden_city').value = document.getElementById('city').value;
                    document.getElementById('hidden_state').value = document.getElementById('state').value;
                    document.getElementById('hidden_pincode').value = document.getElementById('pincode').value;
                    document.getElementById('hidden_phone').value = document.getElementById('phone').value;

                    // Submit the form
                    document.getElementById('razorpay-form').submit();
                },
                "prefill": {
                    "name": "<?php echo isset($user['name']) ? htmlspecialchars($user['name']) : ''; ?>",
                    "email": "<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>",
                    "contact": "<?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : ''; ?>"
                },
                "notes": {
                    "address": "<?php echo isset($_POST['address_line1']) ? htmlspecialchars($_POST['address_line1']) : ''; ?>"
                },
                "theme": {
                    "color": "#800080" // Match your site's theme color
                }
            };
            var rzp = new Razorpay(options);
            rzp.open();
        });
    </script>
    <?php endif; ?>

    <script>
        function updateQuantity(sareeId, change) {
            const inputElement = document.getElementById(`qty-${sareeId}`);
            let newQuantity = parseInt(inputElement.value) + change;

            // Ensure quantity is between 1 and 10
            if (newQuantity >= 1 && newQuantity <= 10) {
                inputElement.value = newQuantity;

                // Update the subtotal calculation
                updateOrderTotal();

                // AJAX call to update cart quantity
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'update_cart.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function () {
                    if (this.readyState === 4 && this.status === 200) {
                        console.log('Cart updated successfully:', this.responseText);
                    }
                };
                xhr.send(`saree_id=${sareeId}&quantity=${newQuantity}&update_cart=1`);
            }
        }

        function updateOrderTotal() {
            let subtotal = 0;

            // Loop through all cart items and calculate the new subtotal
            const cartItems = document.querySelectorAll('.cart-item');
            cartItems.forEach(item => {
                const priceText = item.querySelector('.item-price').textContent;
                const unitPrice = parseFloat(priceText.replace('₹', '').replace(',', ''));
                const quantity = parseInt(item.querySelector('.quantity-input').value);
                subtotal += unitPrice * quantity;
            });

            // Update the subtotal and total displays
            const subtotalElement = document.querySelector('.order-totals .total-row:first-child span:last-child');
            const totalElement = document.querySelector('.grand-total span:last-child');

            if (subtotalElement && totalElement) {
                const formattedSubtotal = new Intl.NumberFormat('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(subtotal);

                subtotalElement.textContent = `₹${formattedSubtotal}`;
                totalElement.textContent = `₹${formattedSubtotal}`;
            }
        }

        // Add these validation functions
        function validateName(input) {
            const name = input.value.trim();
            const errorDiv = document.getElementById('name-validation');
            
            if (name.length < 2) {
                showError(input, errorDiv, 'Name must be at least 2 characters');
                return false;
            }
            if (!/^[a-zA-Z\s]+$/.test(name)) {
                showError(input, errorDiv, 'Name can only contain letters');
                return false;
            }
            showSuccess(input, errorDiv);
            return true;
        }

        function validatePhone(input) {
            const phone = input.value.trim();
            const errorDiv = document.getElementById('phone-validation');
            
            if (!/^[6-9]\d{9}$/.test(phone)) {
                showError(input, errorDiv, 'Enter valid Indian mobile number');
                return false;
            }
            showSuccess(input, errorDiv);
            return true;
        }

        function validatePincode(input) {
            const pincode = input.value.trim();
            const errorDiv = document.getElementById('pincode-validation');
            
            if (!/^[1-9][0-9]{5}$/.test(pincode)) {
                showError(input, errorDiv, 'Enter valid 6-digit PIN code');
                return false;
            }
            showSuccess(input, errorDiv);
            return true;
        }

        function validateAddress(input) {
            const address = input.value.trim();
            const errorDiv = document.getElementById('address-validation');
            
            if (address.length < 5) {
                showError(input, errorDiv, 'Address must be at least 5 characters');
                return false;
            }
            showSuccess(input, errorDiv);
            return true;
        }

        function validateCity(input) {
            const city = input.value.trim();
            const errorDiv = document.getElementById('city-validation');
            
            if (!/^[a-zA-Z\s]+$/.test(city)) {
                showError(input, errorDiv, 'City can only contain letters');
                return false;
            }
            showSuccess(input, errorDiv);
            return true;
        }

        function validateState(input) {
            const state = input.value.trim();
            const errorDiv = document.getElementById('state-validation');
            
            if (!/^[a-zA-Z\s]+$/.test(state)) {
                showError(input, errorDiv, 'State can only contain letters');
                return false;
            }
            showSuccess(input, errorDiv);
            return true;
        }

        function showError(input, errorDiv, message) {
            if (input && errorDiv) {
                input.classList.add('error');
                input.classList.remove('valid');
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
                errorDiv.style.color = 'red';
            }
        }

        function showSuccess(input, errorDiv) {
            if (input && errorDiv) {
                input.classList.remove('error');
                input.classList.add('valid');
                errorDiv.textContent = '✓';
                errorDiv.style.display = 'block';
                errorDiv.style.color = 'green';
            }
        }
    </script>
</body>
</html>