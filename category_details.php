<?php
session_start();
require_once 'db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle Saree Operations
if (isset($_POST['submit_saree'])) {
    try {
        $saree_id = isset($_POST['saree_id']) ? $_POST['saree_id'] : null;
        
        // Validate name contains only letters and spaces
        if (!preg_match("/^[a-zA-Z\s]+$/", $_POST['name'])) {
            throw new Exception("Name can only contain letters and spaces");
        }
        $name = htmlspecialchars($_POST['name']);
        
        // Validate color contains only letters and spaces  
        if (!preg_match("/^[a-zA-Z\s]+$/", $_POST['color'])) {
            throw new Exception("Color can only contain letters and spaces");
        }
        $color = htmlspecialchars($_POST['color']);
        
        // Validate price is numeric
        if (!is_numeric($_POST['price'])) {
            throw new Exception("Price must be a number");
        }
        $price = $_POST['price'];
        
        // Validate stock is numeric
        if (!is_numeric($_POST['stock'])) {
            throw new Exception("Quantity must be a number"); 
        }
        $stock = $_POST['stock'];
        
        $category_id = $_POST['category_id'];
        $subcategory_id = $_POST['subcategory_id'];
        $description = htmlspecialchars($_POST['description']);
        
        // Handle image upload
        $image_path = '';
        $targetDir = "sarees/";
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            $image_tmp = $_FILES['image']['tmp_name'];
            $image_path = $targetDir . $image_name;
            
            if (move_uploaded_file($image_tmp, $image_path)) {
                // File uploaded successfully
            } else {
                throw new Exception("Failed to move uploaded file!");
            }
        }

        if ($saree_id) {
            // Update existing saree
            $sql = "UPDATE sarees SET 
                    name = ?, color = ?, price = ?, category_id = ?, 
                    subcategory_id = ?, description = ?, stock = ?";
            $params = "ssdiisi";
            $values = [$name, $color, $price, $category_id, $subcategory_id, $description, $stock];
            
            if ($image_path) {
                $sql .= ", image = ?";
                $params .= "s";
                $values[] = $image_path;
            }
            
            $sql .= " WHERE id = ?";
            $params .= "i";
            $values[] = $saree_id;
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($params, ...$values);
        } else {
            // Insert new saree
            $stmt = $conn->prepare("INSERT INTO sarees (name, color, price, category_id, subcategory_id, description, image, stock) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdiiiss", $name, $color, $price, $category_id, $subcategory_id, $description, $image_path, $stock);
        }
        
        if ($stmt->execute()) {
            $_SESSION['message'] = $saree_id ? "Saree updated successfully!" : "Saree added successfully!";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Delete Saree
if (isset($_POST['delete_saree'])) {
    try {
        $saree_id = $_POST['saree_id'];
        $stmt = $conn->prepare("DELETE FROM sarees WHERE id = ?");
        $stmt->bind_param("i", $saree_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Saree deleted successfully!";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Update Saree Price and Stock
if (isset($_POST['update_saree'])) {
    try {
        $saree_id = $_POST['saree_id'];
        
        // Validate new price is numeric
        if (!is_numeric($_POST['new_price'])) {
            throw new Exception("Price must be a number");
        }
        $new_price = $_POST['new_price'];
        
        // Validate stock is numeric
        if (isset($_POST['stock_to_add']) && !is_numeric($_POST['stock_to_add'])) {
            throw new Exception("Stock quantity must be a number");
        }
        $stock_to_add = isset($_POST['stock_to_add']) ? (int)$_POST['stock_to_add'] : 0;
        
        // Start transaction
        $conn->begin_transaction();
        
        // First, get the current stock
        $stmt = $conn->prepare("SELECT stock FROM sarees WHERE id = ?");
        $stmt->bind_param("i", $saree_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_saree = $result->fetch_assoc();
        
        if (!$current_saree) {
            throw new Exception("Saree not found");
        }
        
        $previous_stock = $current_saree['stock'];
        $new_stock = $previous_stock + $stock_to_add; // Calculate new total stock
        
        // Update saree details
        $stmt = $conn->prepare("UPDATE sarees SET price = ?, stock = ? WHERE id = ?");
        $stmt->bind_param("dii", $new_price, $new_stock, $saree_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update saree details");
        }
        
        // Only record in stock history if stock was added
        if ($stock_to_add > 0) {
            $stmt = $conn->prepare("INSERT INTO saree_stock_history (saree_id, previous_stock, new_stock, stock_added, updated_by) 
                                  VALUES (?, ?, ?, ?, ?)");
            if (!$stmt->bind_param("iiiii", $saree_id, $previous_stock, $new_stock, $stock_to_add, $_SESSION['user_id'])) {
                throw new Exception("Failed to bind parameters for stock history");
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to record stock history");
            }
        }
        
      
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['message'] = "Saree details updated successfully!";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get messages from session
$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;

// Clear session messages
unset($_SESSION['message']);
unset($_SESSION['error']);

// Fetch categories and subcategories for dropdowns
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");
$subcategories = $conn->query("SELECT * FROM subcategories ORDER BY subcategory_name");

// Fetch existing sarees
$sarees = $conn->query("SELECT s.*, c.category_name, sc.subcategory_name 
                       FROM sarees s 
                       LEFT JOIN categories c ON s.category_id = c.id 
                       LEFT JOIN subcategories sc ON s.subcategory_id = sc.id 
                       ORDER BY s.name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Saree Management</title>
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

        .sidebar-menu li {
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .sidebar-menu a {
            text-decoration: none;
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

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .action-btn {
            background-color: purple;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }

        .delete-btn {
            background-color: #dc3545;
        }

        .saree-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .saree-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: white;
        }

        .saree-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .tab-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        footer {
            background: #f8f8f8;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #eee;
        }

        .logout-btn {
            background-color: purple;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .edit-form {
            margin: 15px 0;
            padding: 10px;
            background-color: #f8f8f8;
            border-radius: 4px;
        }

        .edit-form .form-group {
            margin-bottom: 10px;
        }

        .edit-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .edit-form input[type="number"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .saree-card {
            position: relative;
            padding: 20px;
        }

        .action-btn {
            margin-top: 10px;
        }

        .delete-btn {
            background-color: #dc3545;
            margin-top: 10px;
        }

        .saree-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <header>
        <div class="top-bar">
            <p>Free domestic shipping on orders above INR 10,000. International shipping available.</p>
        </div>

        <div class="header-main">
            <div class="header-center">
                <h1><img src="logo3.png" alt="Logo" width="50px">YARDS OF GRACE</h1>
            </div>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="sidebar">
            <ul class="sidebar-menu">
            <a href="admin.php"><li >Dashboard Overview</li></a>
                <a href="manage.php"><li >User Management</li></a>
                <a href="Category.php"><li >Category Management</li></a>
                <a href="subcategory.php"><li >Subcategory Management</li></a>
                <a href="category_details.php"><li class="active">Product Management</li></a>
                <a href="wedding_categories.php"><li>Wedding Categories</li></a>
                <a href="wedding_products.php"><li>Wedding Products</li></a>
                <a href="wedding_images.php"><li>Wedding Specifications</li></a>
                <a href="review_of_user.php"><li>Reviews</li></a>
                <a href="admin_report.php"><li>Reports</li></a>
                <a href="order_manage.php"><li>Orders</li></a>
                <a href="#"><li>Products</li></a>
              
                <a href="#"><li>Settings</li></a>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h2>Saree Collection Management</h2>
                <p>Manage your saree collection</p>
            </div>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="tabs">
                <button class="action-btn" onclick="showTab('add')">Add New Saree</button>
                <button class="action-btn" onclick="showTab('manage')">Manage Collection</button>
                
            </div>

            <!-- Add New Saree -->
            <div id="add-tab" class="tab-content">
                <h3>Add New Saree</h3>
                <form method="POST" action="" enctype="multipart/form-data" id="sareeForm">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed" required>
                    </div>
                    <div class="form-group">
                        <label>Color:</label>
                        <input type="text" name="color" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed" required>
                    </div>
                    <div class="form-group">
                        <label>Price:</label>
                        <input type="number" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity:</label>
                        <input type="number" name="stock" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Category:</label>
                        <select name="category_id" required onchange="loadSubcategories(this.value)">
                            <option value="">Select Category</option>
                            <?php 
                            $categories->data_seek(0);
                            while ($category = $categories->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subcategory:</label>
                        <select name="subcategory_id" required id="subcategory-select">
                            <option value="">Select Subcategory</option>
                            <?php 
                            $subcategories->data_seek(0);
                            while ($subcategory = $subcategories->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $subcategory['id']; ?>" data-category="<?php echo $subcategory['category_id']; ?>">
                                    <?php echo htmlspecialchars($subcategory['subcategory_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <textarea name="description" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Image:</label>
                        <input type="file" name="image" accept="image/*" required>
                    </div>
                    <button type="submit" name="submit_saree" class="action-btn">Add Saree</button>
                </form>
            </div>

            <!-- Manage Collection -->
            <div id="manage-tab" class="tab-content" style="display: none;">
                <h3>Current Collection</h3>
                <div class="saree-grid">
                    <?php while ($saree = $sarees->fetch_assoc()): ?>
                        <div class="saree-card">
                            <img src="<?php echo htmlspecialchars($saree['image']); ?>" alt="<?php echo htmlspecialchars($saree['name']); ?>" class="saree-image">
                            <h4><?php echo htmlspecialchars($saree['name']); ?></h4>
                            <p>Color: <?php echo htmlspecialchars($saree['color']); ?></p>
                            <p>Saree id: <?php echo htmlspecialchars($saree['id']); ?></p>
                            <!-- Edit form for price and stock -->
                            <form method="POST" action="" class="edit-form">
                                <input type="hidden" name="saree_id" value="<?php echo $saree['id']; ?>">
                                
                                <div class="form-group">
    <label>Price (₹):</label>
    <input type="number" name="new_price" value="<?php echo $saree['price']; ?>" step="0.01" min="0" required>
</div>

<div class="form-group">
    <label>Current Stock:</label>
    <span class="current-stock"><?php echo $saree['stock']; ?></span>
</div>

<div class="form-group">
    <label>Add Stock:</label>
    <input type="number" name="stock_to_add" value="0" min="0" required>
</div>
                                
                                <button type="submit" name="update_saree" class="action-btn">Update</button>
                                <a href="product_specifications.php" class="action-btn">Product Specifications</a>
                            </form>
                            
                            <p>Category: <?php echo htmlspecialchars($saree['category_name']); ?></p>
                            <p>Subcategory: <?php echo htmlspecialchars($saree['subcategory_name']); ?></p>
                            
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="saree_id" value="<?php echo $saree['id']; ?>">
                                <button type="submit" name="delete_saree" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this saree?')">Delete</button>
                            </form>
                            <a href="stock_history.php?id=<?php echo $saree['id']; ?>" class="action-btn">View Stock History</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2025 Yards of Grace. All rights reserved.</p>
    </footer>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            
            document.getElementById(tabName + '-tab').style.display = 'block';
            document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.add('active');
        }

        function loadSubcategories(categoryId) {
            const subcategorySelect = document.getElementById('subcategory-select');
            const options = subcategorySelect.getElementsByTagName('option');
            
            for (let option of options) {
                if (option.value === "") {
                    option.style.display = 'block';
                    continue;
                }
                
                const categoryMatch = option.getAttribute('data-category') === categoryId;
                option.style.display = categoryMatch ? 'block' : 'none';
                
                if (!categoryMatch) {
                    option.selected = false;
                }
            }
        }
    </script>
</body>
</html>