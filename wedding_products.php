<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Database connection
require_once('db.php'); // Assuming you have this file for database connection

// Initialize variables
$message = '';
$messageType = '';

// Handle form submissions for adding, updating, or deleting products
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check which form was submitted
    if (isset($_POST['add_product'])) {
        // Add new product
        $wedding_category_id = $_POST['wedding_category_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $color = $_POST['color'];
        $material = $_POST['material'];
        $style = $_POST['style'];
        $occasion = $_POST['occasion'];
        
        // Handle image upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/wedding_products/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $image_name = time() . '_' . basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $image_name;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image = $target_file;
            } else {
                $message = "Error uploading image.";
                $messageType = "error";
            }
        } else {
            $message = "Please upload a product image.";
            $messageType = "error";
        }
        
        if (!empty($image)) {
            // Insert product into database
            $sql = "INSERT INTO wedding_products (wedding_category_id, name, description, price, stock, color, image, material, style, occasion) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdisssss", $wedding_category_id, $name, $description, $price, $stock, $color, $image, $material, $style, $occasion);
            
            if ($stmt->execute()) {
                $message = "Product added successfully!";
                $messageType = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $messageType = "error";
            }
            
            $stmt->close();
        }
    } elseif (isset($_POST['update_product'])) {
        // Update existing product
        $id = $_POST['product_id'];
        $wedding_category_id = $_POST['wedding_category_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $color = $_POST['color'];
        $material = $_POST['material'];
        $style = $_POST['style'];
        $occasion = $_POST['occasion'];
        
        // Check if new image was uploaded
        $image_sql = "";
        $image_value = "";
        $params = "";
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/wedding_products/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $image_name = time() . '_' . basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $image_name;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_sql = ", image = ?";
                $image_value = $target_file;
            }
        }
        
        // Update product in database
        if (!empty($image_sql)) {
            $sql = "UPDATE wedding_products SET 
                    wedding_category_id = ?, 
                    name = ?, 
                    description = ?, 
                    price = ?, 
                    stock = ?, 
                    color = ?, 
                    material = ?, 
                    style = ?, 
                    occasion = ?" . $image_sql . " 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdisssssi", $wedding_category_id, $name, $description, $price, $stock, $color, $material, $style, $occasion, $image_value, $id);
        } else {
            $sql = "UPDATE wedding_products SET 
                    wedding_category_id = ?, 
                    name = ?, 
                    description = ?, 
                    price = ?, 
                    stock = ?, 
                    color = ?, 
                    material = ?, 
                    style = ?, 
                    occasion = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdissssi", $wedding_category_id, $name, $description, $price, $stock, $color, $material, $style, $occasion, $id);
        }
        
        if ($stmt->execute()) {
            $message = "Product updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error: " . $stmt->error;
            $messageType = "error";
        }
        
        $stmt->close();
    } elseif (isset($_POST['delete_product'])) {
        // Delete product
        $id = $_POST['product_id'];
        
        $sql = "DELETE FROM wedding_products WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Product deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error: " . $stmt->error;
            $messageType = "error";
        }
        
        $stmt->close();
    }
}

// Fetch all wedding categories for dropdown
$categories = [];
$sql = "SELECT id, category_name FROM wedding_categories ORDER BY category_name";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch all products
$products = [];
$sql = "SELECT wp.*, wc.category_name 
        FROM wedding_products wp 
        JOIN wedding_categories wc ON wp.wedding_category_id = wc.id 
        ORDER BY wp.created_at DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Products Management - Yards of Grace</title>
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
            overflow-x: auto;
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .dashboard-header h2 {
            color: purple;
            margin-bottom: 10px;
        }

        .action-buttons {
            margin-bottom: 20px;
        }

        .add-btn {
            background-color: purple;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }

        .add-btn:hover {
            background-color: #800080;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .products-table th, .products-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .products-table th {
            background-color: #f8f8f8;
            color: #666;
        }

        .action-btn {
            background-color: #f0f0f0;
            border: none;
            padding: 6px 12px;
            margin: 0 5px;
            border-radius: 4px;
            cursor: pointer;
        }

        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
        }

        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: none;
        }

        .form-container.show {
            display: block;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #666;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .submit-btn {
            background-color: purple;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .cancel-btn {
            background-color: #ccc;
            color: #333;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .alert-error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        .thumbnail {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
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

        footer {
            background: #f8f8f8;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #eee;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 0;
        }

        .form-row .form-group {
            flex: 1;
        }
        
        .price-input {
            position: relative;
        }
        
        .price-input::before {
            content: "₹";
            position: absolute;
            left: 10px;
            top: 10px;
        }
        
        .price-input input {
            padding-left: 25px;
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
                <a href="wedding_categories.php"><li>Wedding Categories</li></a>
                <a href="wedding_products.php"><li class="active">Wedding Products</li></a>
                <a href="category_details.php"><li>Product Management</li></a>
                <a href="order_manage.php"><li>Orders</li></a>
                <a href="#"><li>Reports</li></a>
                <a href="#"><li>Settings</li></a>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h2>Wedding Products Management</h2>
                <p>Manage wedding products for your store</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="action-buttons">
                <button class="add-btn" onclick="showAddForm()">Add New Product</button>
            </div>

            <!-- Add Product Form -->
            <div id="add-form" class="form-container">
                <h3>Add New Wedding Product</h3>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="wedding_category_id">Wedding Category</label>
                        <select id="wedding_category_id" name="wedding_category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo $category['category_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price (₹)</label>
                            <div class="price-input">
                                <input type="number" id="price" name="price" step="0.01" min="0" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock">Stock Quantity</label>
                            <input type="number" id="stock" name="stock" min="0" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="color">Color</label>
                            <input type="text" id="color" name="color" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="material">Material</label>
                            <input type="text" id="material" name="material" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="style">Style</label>
                            <input type="text" id="style" name="style" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="occasion">Occasion</label>
                            <input type="text" id="occasion" name="occasion" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <input type="file" id="image" name="image" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="add_product" class="submit-btn">Add Product</button>
                    <button type="button" class="cancel-btn" onclick="hideAddForm()">Cancel</button>
                </form>
            </div>

            <!-- Edit Product Form -->
            <div id="edit-form" class="form-container">
                <h3>Edit Wedding Product</h3>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" id="edit_product_id" name="product_id">
                    
                    <div class="form-group">
                        <label for="edit_wedding_category_id">Wedding Category</label>
                        <select id="edit_wedding_category_id" name="wedding_category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo $category['category_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_name">Product Name</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_price">Price (₹)</label>
                            <div class="price-input">
                                <input type="number" id="edit_price" name="price" step="0.01" min="0" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_stock">Stock Quantity</label>
                            <input type="number" id="edit_stock" name="stock" min="0" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_color">Color</label>
                            <input type="text" id="edit_color" name="color" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_material">Material</label>
                            <input type="text" id="edit_material" name="material" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_style">Style</label>
                            <input type="text" id="edit_style" name="style" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_occasion">Occasion</label>
                            <input type="text" id="edit_occasion" name="occasion" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_image">Product Image</label>
                        <input type="file" id="edit_image" name="image" class="form-control">
                        <p><small>Leave empty to keep the current image</small></p>
                        <div id="current_image_preview"></div>
                    </div>
                    
                    <button type="submit" name="update_product" class="submit-btn">Update Product</button>
                    <button type="button" class="cancel-btn" onclick="hideEditForm()">Cancel</button>
                </form>
            </div>

            <!-- Products Table -->
            <table class="products-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Color</th>
                        <th>Material</th>
                        <th>Style</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="11" style="text-align: center;">No products found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="thumbnail">
                                    <?php else: ?>
                                        <span>No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><?php echo $product['color']; ?></td>
                                <td><?php echo $product['material']; ?></td>
                                <td><?php echo $product['style']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                                <td>
                                    <button class="action-btn edit-btn" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8'); ?>)">Edit</button>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="delete_product" class="action-btn delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        <p>© 2025 Yards of Grace. All rights reserved.</p>
    </footer>

    <script>
        // Show/hide add form
        function showAddForm() {
            document.getElementById('add-form').classList.add('show');
            document.getElementById('edit-form').classList.remove('show');
        }
        
        function hideAddForm() {
            document.getElementById('add-form').classList.remove('show');
        }
        
        // Show/hide edit form
        function hideEditForm() {
            document.getElementById('edit-form').classList.remove('show');
        }
        
        // Populate edit form with product data
        function editProduct(product) {
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_wedding_category_id').value = product.wedding_category_id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_stock').value = product.stock;
            document.getElementById('edit_color').value = product.color;
            document.getElementById('edit_material').value = product.material;
            document.getElementById('edit_style').value = product.style;
            document.getElementById('edit_occasion').value = product.occasion;
            
            // Show current image if exists
            const imagePreview = document.getElementById('current_image_preview');
            if (product.image) {
                imagePreview.innerHTML = `<img src="${product.image}" alt="${product.name}" style="max-width: 200px; margin-top: 10px;">`;
            } else {
                imagePreview.innerHTML = '';
            }
            
            document.getElementById('edit-form').classList.add('show');
            document.getElementById('add-form').classList.remove('show');
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