<?php
session_start();
require_once 'db.php';

// Enhanced security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new specification
    if (isset($_POST['add_specification'])) {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $blouse_details = isset($_POST['blouse_details']) ? trim($_POST['blouse_details']) : null;
        $saree_length = isset($_POST['saree_length']) ? (float)$_POST['saree_length'] : null;
        $blouse_length = isset($_POST['blouse_length']) ? (float)$_POST['blouse_length'] : null;
        $wash_care = isset($_POST['wash_care']) ? trim($_POST['wash_care']) : null;
        $additional_details = isset($_POST['additional_details']) ? trim($_POST['additional_details']) : null;

        // Validate product_id
        if ($product_id <= 0) {
            $error = "Invalid product ID";
        } else {
            // Check if specification already exists for this product
            $check_query = "SELECT id FROM wedding_specifications WHERE product_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $product_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                // Update existing specification
                $update_query = "UPDATE wedding_specifications 
                                SET blouse_details = ?, 
                                    saree_length = ?, 
                                    blouse_length = ?, 
                                    wash_care = ?, 
                                    additional_details = ? 
                                WHERE product_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("sddssr", $blouse_details, $saree_length, $blouse_length, $wash_care, $additional_details, $product_id);
                
                if ($update_stmt->execute()) {
                    $success = "Specification updated successfully";
                } else {
                    $error = "Error updating specification: " . $conn->error;
                }
                $update_stmt->close();
            } else {
                // Insert new specification
                $insert_query = "INSERT INTO wedding_specifications 
                                (product_id, blouse_details, saree_length, blouse_length, wash_care, additional_details) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("isddss", $product_id, $blouse_details, $saree_length, $blouse_length, $wash_care, $additional_details);
                
                if ($insert_stmt->execute()) {
                    $success = "Specification added successfully";
                } else {
                    $error = "Error adding specification: " . $conn->error;
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
    }

    // Delete specification
    if (isset($_POST['delete_specification'])) {
        $spec_id = isset($_POST['spec_id']) ? (int)$_POST['spec_id'] : 0;

        if ($spec_id > 0) {
            $delete_query = "DELETE FROM wedding_specifications WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $spec_id);
            
            if ($delete_stmt->execute()) {
                $success = "Specification deleted successfully";
            } else {
                $error = "Error deleting specification: " . $conn->error;
            }
            $delete_stmt->close();
        } else {
            $error = "Invalid specification ID";
        }
    }
}

// Fetch all specifications with product names
$specs_query = "SELECT ws.*, wp.name as product_name 
                FROM wedding_specifications ws 
                JOIN wedding_products wp ON ws.product_id = wp.id 
                ORDER BY ws.id DESC";
$specs_result = $conn->query($specs_query);

// Fetch all products for dropdown
$products_query = "SELECT id, name FROM wedding_products ORDER BY name";
$products_result = $conn->query($products_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Specifications - Yards of Grace</title>
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

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
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

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #eee;
            margin-bottom: 30px;
        }

        .card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .card-header h3 {
            color: purple;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #666;
        }

        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        textarea {
            min-height: 100px;
        }

        .btn {
            background-color: purple;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            background-color: #800080;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f8f8;
            color: #666;
        }

        tr:hover {
            background-color: #f8f8f8;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .text-truncate {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .logout-btn {
            background-color: purple;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        footer {
            background: #f8f8f8;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #eee;
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
                <a href="admin.php"><li>Dashboard Overview</li></a>
                <a href="manage.php"><li>User Management</li></a>
                <a href="Category.php"><li>Category Management</li></a>
                <a href="subcategory.php"><li>Subcategory Management</li></a>
                <a href="category_details.php"><li>Product Management</li></a>
                <a href="wedding_categories.php"><li>Wedding Categories</li></a>
                <a href="wedding_specifications.php"><li class="active">Wedding Specifications</li></a>
                <a href="order_manage.php"><li>Orders</li></a>
                <a href="#"><li>Products</li></a>
                <a href="#"><li>Reports</li></a>
                <a href="#"><li>Settings</li></a>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h2>Wedding Specifications Management</h2>
                <p>Manage product specifications for wedding sarees and blouses</p>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Add/Edit Specification</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="product_id">Product</label>
                            <select name="product_id" id="product_id" required>
                                <option value="">Select a product</option>
                                <?php while($product = $products_result->fetch_assoc()): ?>
                                    <option value="<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="blouse_details">Blouse Details</label>
                            <input type="text" name="blouse_details" id="blouse_details" placeholder="Enter blouse details">
                        </div>
                        
                        <div class="form-group">
                            <label for="saree_length">Saree Length (meters)</label>
                            <input type="number" name="saree_length" id="saree_length" step="0.01" min="0" placeholder="Enter saree length">
                        </div>
                        
                        <div class="form-group">
                            <label for="blouse_length">Blouse Length (meters)</label>
                            <input type="number" name="blouse_length" id="blouse_length" step="0.01" min="0" placeholder="Enter blouse length">
                        </div>
                        
                        <div class="form-group">
                            <label for="wash_care">Wash Care</label>
                            <input type="text" name="wash_care" id="wash_care" placeholder="Enter wash care instructions">
                        </div>
                        
                        <div class="form-group">
                            <label for="additional_details">Additional Details</label>
                            <textarea name="additional_details" id="additional_details" placeholder="Enter additional details"></textarea>
                        </div>
                        
                        <button type="submit" name="add_specification" class="btn">Save Specification</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Existing Specifications</h3>
                </div>
                <div class="card-body">
                    <?php if($specs_result->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product</th>
                                    <th>Blouse Details</th>
                                    <th>Saree Length</th>
                                    <th>Blouse Length</th>
                                    <th>Wash Care</th>
                                    <th>Additional Details</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($spec = $specs_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $spec['id']; ?></td>
                                        <td><?php echo htmlspecialchars($spec['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($spec['blouse_details'] ?: 'Not specified'); ?></td>
                                        <td><?php echo $spec['saree_length'] ? number_format($spec['saree_length'], 2) : 'Not specified'; ?></td>
                                        <td><?php echo $spec['blouse_length'] ? number_format($spec['blouse_length'], 2) : 'Not specified'; ?></td>
                                        <td><?php echo htmlspecialchars($spec['wash_care'] ?: 'Not specified'); ?></td>
                                        <td class="text-truncate"><?php echo htmlspecialchars($spec['additional_details'] ?: 'Not specified'); ?></td>
                                        <td class="action-buttons">
                                            <button 
                                                class="btn"
                                                onclick="editSpecification(
                                                    <?php echo $spec['product_id']; ?>,
                                                    '<?php echo addslashes($spec['blouse_details'] ?: ''); ?>',
                                                    <?php echo $spec['saree_length'] ?: 0; ?>,
                                                    <?php echo $spec['blouse_length'] ?: 0; ?>,
                                                    '<?php echo addslashes($spec['wash_care'] ?: ''); ?>',
                                                    '<?php echo addslashes($spec['additional_details'] ?: ''); ?>'
                                                )"
                                            >
                                                Edit
                                            </button>
                                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this specification?');">
                                                <input type="hidden" name="spec_id" value="<?php echo $spec['id']; ?>">
                                                <button type="submit" name="delete_specification" class="btn btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No specifications found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2025 Yards of Grace. All rights reserved.</p>
    </footer>

    <script>
        // Function to populate form fields for editing
        function editSpecification(productId, blouseDetails, sareeLength, blouseLength, washCare, additionalDetails) {
            document.getElementById('product_id').value = productId;
            document.getElementById('blouse_details').value = blouseDetails;
            document.getElementById('saree_length').value = sareeLength;
            document.getElementById('blouse_length').value = blouseLength;
            document.getElementById('wash_care').value = washCare;
            document.getElementById('additional_details').value = additionalDetails;
            
            // Scroll to the form
            document.querySelector('.card').scrollIntoView({ behavior: 'smooth' });
        }

        // Add active class to sidebar menu items
        document.querySelectorAll('.sidebar-menu li').forEach(item => {
            item.addEventListener('click', function(e) {
                if (!this.classList.contains('active')) {
                    if (this.parentElement.href === '#') {
                        e.preventDefault();
                    }
                    const activeItem = document.querySelector('.sidebar-menu li.active');
                    if (activeItem) {
                        activeItem.classList.remove('active');
                    }
                    this.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>