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

// Handle form submissions for adding or updating categories
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check which form was submitted
    if (isset($_POST['add_category'])) {
        // Add new category
        $category_name = $_POST['category_name'];
        $description = $_POST['description'];
        
        // Insert category into database
        $sql = "INSERT INTO wedding_categories (category_name, description) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $category_name, $description);
        
        if ($stmt->execute()) {
            $message = "Category added successfully!";
            $messageType = "success";
        } else {
            $message = "Error: " . $stmt->error;
            $messageType = "error";
        }
        
        $stmt->close();
    } elseif (isset($_POST['update_category'])) {
        // Update existing category
        $id = $_POST['category_id'];
        $category_name = $_POST['category_name'];
        $description = $_POST['description'];
        
        // Update category in database
        $sql = "UPDATE wedding_categories SET category_name = ?, description = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $category_name, $description, $id);
        
        if ($stmt->execute()) {
            $message = "Category updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error: " . $stmt->error;
            $messageType = "error";
        }
        
        $stmt->close();
    } elseif (isset($_POST['delete_category'])) {
        // Delete category
        $id = $_POST['category_id'];
        
        $sql = "DELETE FROM wedding_categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Category deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error: " . $stmt->error;
            $messageType = "error";
        }
        
        $stmt->close();
    }
}

// Fetch all categories
$categories = [];
$sql = "SELECT * FROM wedding_categories ORDER BY category_name";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Categories Management - Yards of Grace</title>
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

        .categories-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .categories-table th, .categories-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .categories-table th {
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
                <a href="wedding_categories.php"><li class="active">Wedding Categories</li></a>
                <a href="category_details.php"><li>Product Management</li></a>
                <a href="order_manage.php"><li>Orders</li></a>
                <a href="#"><li>Products</li></a>
                <a href="#"><li>Reports</li></a>
                <a href="#"><li>Settings</li></a>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h2>Wedding Categories Management</h2>
                <p>Manage wedding categories for your store</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="action-buttons">
                <button class="add-btn" onclick="showAddForm()">Add New Category</button>
            </div>

            <!-- Add Category Form -->
            <div id="add-form" class="form-container">
                <h3>Add New Wedding Category</h3>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="category_name">Category Name</label>
                        <input type="text" id="category_name" name="category_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                    </div>
                    <button type="submit" name="add_category" class="submit-btn">Add Category</button>
                    <button type="button" class="cancel-btn" onclick="hideAddForm()">Cancel</button>
                </form>
            </div>

            <!-- Edit Category Form -->
            <div id="edit-form" class="form-container">
                <h3>Edit Wedding Category</h3>
                <form action="" method="post">
                    <input type="hidden" id="edit_category_id" name="category_id">
                    <div class="form-group">
                        <label for="edit_category_name">Category Name</label>
                        <input type="text" id="edit_category_name" name="category_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" class="form-control" rows="4"></textarea>
                    </div>
                    <button type="submit" name="update_category" class="submit-btn">Update Category</button>
                    <button type="button" class="cancel-btn" onclick="hideEditForm()">Cancel</button>
                </form>
            </div>

            <!-- Categories Table -->
            <table class="categories-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No categories found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo $category['category_name']; ?></td>
                                <td><?php echo substr($category['description'], 0, 100) . (strlen($category['description']) > 100 ? '...' : ''); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <button class="action-btn edit-btn" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category), ENT_QUOTES, 'UTF-8'); ?>)">Edit</button>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" name="delete_category" class="action-btn delete-btn">Delete</button>
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
        
        // Populate edit form with category data
        function editCategory(category) {
            document.getElementById('edit_category_id').value = category.id;
            document.getElementById('edit_category_name').value = category.category_name;
            document.getElementById('edit_description').value = category.description;
            
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