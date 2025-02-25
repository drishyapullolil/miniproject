<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'yardsofgrace';

try {
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle Add Category and Subcategories
if (isset($_POST['add_category'])) {
    try {
        $category = htmlspecialchars($_POST['category']);
        
        // Insert category
        $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->bind_param("s", $category);
        
        if ($stmt->execute()) {
            $category_id = $conn->insert_id;
            
            // Insert subcategories if any
            if (isset($_POST['subcategory']) && is_array($_POST['subcategory'])) {
                $stmt = $conn->prepare("INSERT INTO subcategories (category_id, subcategory_name) VALUES (?, ?)");
                
                foreach ($_POST['subcategory'] as $subcategory) {
                    if (!empty($subcategory)) {
                        $subcategory = htmlspecialchars($subcategory);
                        $stmt->bind_param("is", $category_id, $subcategory);
                        $stmt->execute();
                    }
                }
            }
            $message = "Category and Subcategories successfully added!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Delete Category
if (isset($_POST['delete_category'])) {
    try {
        $category_id = $_POST['category_id'];
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        
        if ($stmt->execute()) {
            $message = "Category successfully deleted!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Remove Subcategory
if (isset($_POST['remove_subcategory'])) {
    try {
        $subcategory_id = $_POST['subcategory_id'];
        $stmt = $conn->prepare("DELETE FROM subcategories WHERE id = ?");
        $stmt->bind_param("i", $subcategory_id);
        
        if ($stmt->execute()) {
            $message = "Subcategory successfully removed!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Add New Subcategory (Edit functionality)
if (isset($_POST['add_new_subcategory'])) {
    try {
        $category_id = $_POST['category_id'];
        $new_subcategory = htmlspecialchars($_POST['new_subcategory']);
        
        $stmt = $conn->prepare("INSERT INTO subcategories (category_id, subcategory_name) VALUES (?, ?)");
        $stmt->bind_param("is", $category_id, $new_subcategory);
        
        if ($stmt->execute()) {
            $message = "New subcategory successfully added!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all categories and their subcategories
$categories = [];
$query = "SELECT c.id, c.category_name, c.description, c.created_at, 
          s.id as subcategory_id, s.subcategory_name, s.description as subcategory_description 
          FROM categories c 
          LEFT JOIN subcategories s ON c.id = s.category_id 
          ORDER BY c.created_at DESC";

$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!isset($categories[$row['id']])) {
            $categories[$row['id']] = [
                'id' => $row['id'],
                'category' => $row['category_name'],
                'description' => $row['description'],
                'created_at' => $row['created_at'],
                'subcategories' => []
            ];
        }
        if ($row['subcategory_id']) {
            $categories[$row['id']]['subcategories'][] = [
                'id' => $row['subcategory_id'],
                'name' => $row['subcategory_name'],
                'description' => $row['subcategory_description']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Yards of Grace</title>
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

        .category-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }

        .categories-table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #eee;
            overflow-x: auto;
        }

        .categories-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .categories-table th, 
        .categories-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .categories-table th {
            background-color: #f8f8f8;
            color: #666;
        }

        .action-btn, 
        .add-category-btn {
            background-color: purple;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
            text-decoration: none;
        }

        .delete-btn {
            background-color: #dc3545;
        }

        .subcategory-input-group {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .subcategory-input-group input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .remove-subcategory-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
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

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            display: none;
        }

        .modal-content {
            position: relative;
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            width: 50%;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            cursor: pointer;
        }

        .close:hover {
            color: #666;
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

        .subcategory-list {
            list-style: none;
            padding: 0;
        }

        .subcategory-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
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
                <a href="Category.php"><li class="active">Category Management</li></a>
                <a href="subcategory.php"><li>Subcategory Management</li></a>
                <a href="category_details.php"><li>Product Management</li></a>
                <a href="order_manage.php"><li>Orders</li></a>
                <a href="#"><li>Products</li></a>
                <a href="#"><li>Reports</li></a>
                <a href="#"><li>Settings</li></a>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h2>Category Management</h2>
                <p>Manage all categories and subcategories</p>
            </div>

            <?php if (isset($message)): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="category-form">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Category Name:</label>
                        <input type="text" name="category" placeholder="Enter category name" required>
                    </div>
                    
                    <div id="subcategoryContainer">
                        <!-- Subcategory input fields will be added here dynamically -->
                    </div>
                    
                    <button type="button" class="action-btn" onclick="addSubcategoryField()">Add Subcategory</button>
                    <button type="submit" name="add_category" class="action-btn">Add Category</button>
                    <a href="category_details.php" class="action-btn">Add more details</a>
                </form>
            </div>

            <div class="categories-table-container">
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Subcategories</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td><?php echo htmlspecialchars($cat['category']); ?></td>
                                <td>
                                    <?php if (!empty($cat['subcategories'])): ?>
                                        <ul class="subcategory-list">
                                            <?php foreach ($cat['subcategories'] as $subcat): ?>
                                                <li>
                                                    <?php echo htmlspecialchars($subcat['name']); ?>
                                                    <form method="POST" action="" style="display:inline;">
                                                        <input type="hidden" name="subcategory_id" value="<?php echo $subcat['id']; ?>">
                                                        <button type="submit" name="remove_subcategory" class="remove-subcategory-btn">Remove</button>
                                                    </form>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <!-- Add button for more subcategories -->
                                        <button type="button" class="action-btn" style="margin-top: 10px;" onclick="showEditModal(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['category']); ?>')">Add More</button>
                                    <?php else: ?>
                                        No subcategories
                                        <button type="button" class="action-btn" style="margin-top: 10px;" onclick="showEditModal(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['category']); ?>')">Add Subcategory</button>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $cat['created_at']; ?></td>
                                <td>
                    <button type="button" class="action-btn" onclick="showEditModal(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['category']); ?>')">Edit</button>
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                        <button type="submit" name="delete_category" class="action-btn delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Add Subcategory</h3>
        <form method="POST" action="">
            <input type="hidden" id="edit_category_id" name="category_id">
            <div class="form-group">
                <label>Category Name:</label>
                <input type="text" id="edit_category_name" disabled>
            </div>
            <div class="form-group">
                <label>Add New Subcategory:</label>
                <input type="text" name="new_subcategory" required placeholder="Enter new subcategory name">
            </div>
            <button type="submit" name="add_new_subcategory" class="action-btn">Add Subcategory</button>
        </form>
    </div>
</div>

</div>
</div>

<footer>
    <p>© 2025 Yards of Grace. All rights reserved.</p>
</footer>

<script>
    // Function to add new subcategory field
    function addSubcategoryField() {
        const container = document.getElementById('subcategoryContainer');
        const inputGroup = document.createElement('div');
        inputGroup.className = 'subcategory-input-group';
        inputGroup.innerHTML = `
            <input type="text" name="subcategory[]" placeholder="Enter subcategory name">
            <button type="button" class="remove-subcategory-btn" onclick="removeSubcategoryField(this)">Remove</button>
        `;
        container.appendChild(inputGroup);
    }

    // Function to remove subcategory field
    function removeSubcategoryField(button) {
        button.parentElement.remove();
    }

    // Modal functionality
    const modal = document.getElementById('editModal');
    const span = document.getElementsByClassName('close')[0];

    function showEditModal(categoryId, categoryName) {
        document.getElementById('edit_category_id').value = categoryId;
        document.getElementById('edit_category_name').value = categoryName;
        modal.style.display = "block";
    }

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Function to show success/error messages temporarily
    document.addEventListener('DOMContentLoaded', function() {
        const messages = document.querySelectorAll('.message');
        if (messages.length > 0) {
            setTimeout(function() {
                messages.forEach(function(message) {
                    message.style.display = 'none';
                });
            }, 3000);
        }
    });
</script>

</body>
</html>