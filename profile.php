<?php
require_once 'db.php';


// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user data
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Get user's orders
$orders_query = "SELECT o.*, 
                (SELECT COUNT(*) FROM order_details WHERE order_id = o.id) as item_count 
                FROM orders o 
                WHERE o.user_id = ? 
                ORDER BY o.created_at DESC";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$stmt->close();

// Handle profile update
$update_message = '';
$update_status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    
    // Handle profile picture upload
    $profile_pic = $user['profile_pic']; // Keep existing by default
    
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($_FILES['profile_pic']['type'], $allowed_types) && $_FILES['profile_pic']['size'] <= $max_size) {
            $upload_dir = 'uploads/profiles/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = uniqid('profile_') . '_' . $_FILES['profile_pic']['name'];
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                // Delete old profile pic if exists
                if (!empty($user['profile_pic']) && file_exists($user['profile_pic'])) {
                    unlink($user['profile_pic']);
                }
                
                $profile_pic = $upload_path;
            }
        } else {
            $update_message = 'Invalid file. Please upload an image under 2MB in size.';
            $update_status = 'error';
        }
    }
    
    if (empty($update_status)) {
        // Update user profile
        $update_query = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, profile_pic = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssi", $name, $email, $phone, $address, $profile_pic, $user_id);
        
        if ($stmt->execute()) {
            $update_message = 'Profile updated successfully!';
            $update_status = 'success';
            
            // Update session variables if needed
            $_SESSION['user_name'] = $name;
            
            // Refresh user data
            $user['name'] = $name;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['address'] = $address;
            $user['profile_pic'] = $profile_pic;
        } else {
            $update_message = 'Failed to update profile: ' . $stmt->error;
            $update_status = 'error';
        }
        $stmt->close();
    }
}

// Handle password change
$password_message = '';
$password_status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $password_query = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($password_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $password_result = $stmt->get_result();
    $password_row = $password_result->fetch_assoc();
    $stmt->close();
    
    if (!password_verify($current_password, $password_row['password'])) {
        $password_message = 'Current password is incorrect';
        $password_status = 'error';
    } else if ($new_password !== $confirm_password) {
        $password_message = 'New passwords do not match';
        $password_status = 'error';
    } else if (strlen($new_password) < 8) {
        $password_message = 'Password must be at least 8 characters long';
        $password_status = 'error';
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_password_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($update_password_query);
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $password_message = 'Password changed successfully!';
            $password_status = 'success';
        } else {
            $password_message = 'Failed to update password: ' . $stmt->error;
            $password_status = 'error';
        }
        $stmt->close();
    }
}

// Include header
include 'header.php';

?>

<div class="profile-container">
    <div class="profile-header">
        <h1>My Profile</h1>
        <p>Manage your account information and view your orders</p>
    </div>
    
    <div class="profile-tabs">
        <button class="tab-button active" data-tab="profile">Profile Info</button>
        <button class="tab-button" data-tab="orders">My Orders</button>
        <button class="tab-button" data-tab="password">Change Password</button>
    </div>
    
    <div class="profile-content">
        <!-- Profile Tab -->
        <div id="profile" class="tab-content active">
            <?php if (!empty($update_message)): ?>
                <div class="alert <?php echo $update_status; ?>">
                    <?php echo $update_message; ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="post" enctype="multipart/form-data" class="profile-form">
                <div class="profile-picture-section">
                    <div class="profile-picture">
                        <?php if (!empty($user['profile_pic'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_pic'] ?? ''); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <div class="default-avatar">
                                <?php echo strtoupper(substr($user['name'] ?? '', 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-picture-upload">
                        <label for="profile_pic">Upload New Picture</label>
                        <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                        <small>Max size: 2MB. Formats: JPG, PNG, GIF</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Delivery Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
            </form>
        </div>
        
        <!-- Orders Tab -->
        <div id="orders" class="tab-content">
            <h2>Order History</h2>
            
            <?php if ($orders_result->num_rows > 0): ?>
                <div class="orders-list">
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3>Order #<?php echo $order['id']; ?></h3>
                                    <span class="order-date">
                                        <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="order-status <?php echo $order['order_status']; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </div>
                            </div>
                            
                            <div class="order-details">
                                <div class="order-summary">
                                    <p><strong>Items:</strong> <?php echo $order['item_count']; ?></p>
                                    <p><strong>Total:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? ''); ?></p>
                                </div>
                                <div class="order-actions">
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn-view-order">
                                        View Details
                                    </a>
                                    <?php if ($order['order_status'] === 'delivered'): ?>
                                        <a href="review_order.php?id=<?php echo $order['id']; ?>" class="btn-review">
                                            Write Review
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-bag"></i>
                    <p>You haven't placed any orders yet.</p>
                    <a href="products.php" class="btn-shop">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Password Tab -->
        <div id="password" class="tab-content">
            <h2>Change Password</h2>
            
            <?php if (!empty($password_message)): ?>
                <div class="alert <?php echo $password_status; ?>">
                    <?php echo $password_message; ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="post" class="password-form">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <small>Password must be at least 8 characters long</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" name="change_password" class="btn-save">Update Password</button>
            </form>
        </div>
    </div>
</div>

<style>
.profile-container {
    max-width: 1000px;
    margin: 30px auto;
    padding: 0 20px;
}

.profile-header {
    margin-bottom: 30px;
    text-align: center;
}

.profile-header h1 {
    color: #333;
    margin-bottom: 5px;
}

.profile-header p {
    color: #666;
}

.profile-tabs {
    display: flex;
    border-bottom: 1px solid #ddd;
    margin-bottom: 30px;
}

.tab-button {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    color: #666;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tab-button:hover {
    color: #8d0f8f;
}

.tab-button.active {
    color: #8d0f8f;
    border-bottom-color: #8d0f8f;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Profile Form Styles */
.profile-form, .password-form {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.profile-picture-section {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
}

.profile-picture {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 20px;
}

.profile-picture img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.default-avatar {
    width: 100%;
    height: 100%;
    background: #8d0f8f;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
}

.profile-picture-upload {
    display: flex;
    flex-direction: column;
}

.profile-picture-upload label {
    background: #8d0f8f;
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
    cursor: pointer;
    display: inline-block;
    margin-bottom: 10px;
}

.profile-picture-upload input[type="file"] {
    display: none;
}

.profile-picture-upload small {
    color: #666;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    flex: 1;
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #333;
    font-weight: 500;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.form-group textarea {
    resize: vertical;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #666;
}

.btn-save {
    background: #8d0f8f;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn-save:hover {
    background: #760d78;
}

/* Orders Styles */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.order-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #f9f9f9;
    border-bottom: 1px solid #eee;
}

.order-info h3 {
    margin: 0;
    font-size: 18px;
}

.order-date {
    display: block;
    color: #666;
    font-size: 14px;
    margin-top: 5px;
}

.order-status {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 14px;
    font-weight: 500;
}

.order-status.pending {
    background: #fff3cd;
    color: #856404;
}

.order-status.processing {
    background: #d1ecf1;
    color: #0c5460;
}

.order-status.shipped {
    background: #d6f5fc;
    color: #035d79;
}

.order-status.delivered {
    background: #d4edda;
    color: #155724;
}

.order-status.cancelled {
    background: #f8d7da;
    color: #721c24;
}

.order-details {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-summary p {
    margin: 5px 0;
    color: #333;
}

.order-actions {
    display: flex;
    gap: 10px;
}

.btn-view-order, .btn-review, .btn-shop {
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.btn-view-order {
    background: transparent;
    border: 1px solid #8d0f8f;
    color: #8d0f8f;
}

.btn-review {
    background: #8d0f8f;
    color: white;
    border: none;
}

.btn-shop {
    background: #8d0f8f;
    color: white;
    padding: 12px 25px;
    display: inline-block;
    margin-top: 15px;
}

.no-orders {
    text-align: center;
    padding: 50px 0;
}

.no-orders i {
    font-size: 60px;
    color: #ddd;
    margin-bottom: 20px;
}

.no-orders p {
    color: #666;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .profile-tabs {
        overflow-x: auto;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .order-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .order-actions {
        width: 100%;
    }
    
    .btn-view-order, .btn-review {
        flex: 1;
        text-align: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(this.dataset.tab).classList.add('active');
        });
    });
    
    // Profile picture preview
    const profilePicInput = document.getElementById('profile_pic');
    if (profilePicInput) {
        profilePicInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const profilePicture = document.querySelector('.profile-picture');
                    
                    // Create image if it doesn't exist, otherwise update src
                    if (profilePicture.querySelector('img')) {
                        profilePicture.querySelector('img').src = e.target.result;
                    } else {
                        // Remove default avatar if exists
                        if (profilePicture.querySelector('.default-avatar')) {
                            profilePicture.innerHTML = '';
                        }
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Profile Picture';
                        profilePicture.appendChild(img);
                    }
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Password confirmation validation
    const passwordForm = document.querySelector('.password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match');
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>