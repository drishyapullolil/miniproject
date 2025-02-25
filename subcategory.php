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

// Handle Add Subcategory
if (isset($_POST['add_subcategory'])) {
    try {
        $category_id = $_POST['category_id'];
        $subcategory_name = htmlspecialchars($_POST['subcategory_name']);
        $description = htmlspecialchars($_POST['description']);
        
        $stmt = $conn->prepare("INSERT INTO subcategories (category_id, subcategory_name, description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $category_id, $subcategory_name, $description);
        
        if ($stmt->execute()) {
            $message = "Subcategory successfully added!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all categories for dropdown
$categories = [];
$query = "SELECT id, category_name FROM categories ORDER BY category_name";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch existing subcategories
$subcategories = [];
$query = "SELECT s.*, c.category_name 
          FROM subcategories s 
          JOIN categories c ON s.category_id = c.id 
          ORDER BY c.category_name, s.subcategory_name";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subcategories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subcategory Management - Yards of Grace</title>
    <!-- Copy the same CSS from Category.php and add this additional style -->
    <style>
        .subcategory-form {
            max-width: 600px;
            margin: 0 auto;
        }
        
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }
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
            align-items: left;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .inline-form {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
        }

        .form-row {
            display: flex;
            gap: 15px;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin-left: 0;
        }

        .form-group {
            margin-bottom: 0;
            flex: 1;
            text-align: left;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            height: 38px;
        }

        .subcategory-form {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            margin-left: 0;
            width: 100%;
        }

        .action-btn {
            height: 38px;
            white-space: nowrap;
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
                <a href="admin.php"><li>Dashboard Overview</li></a>
                <a href="manage.php"><li>User Management</li></a>
                <a href="Category.php"><li>Category Management</li></a>
                <a href="subcategory.php"><li class="active">Subcategory Management</li></a>
                <a href="category_details.php"><li>Product Management</li></a>
                <a href="order_manage.php"><li>Orders</li></a>
                <a href="#"><li>Products</li></a>
                <a href="#"><li>Settings</li></a>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h2>Subcategory Management</h2>
                <p>Add and manage subcategories</p>
            </div>

            <?php if (isset($message)): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="subcategory-form">
                <form method="POST" action="" class="inline-form">
                    <div class="form-row">
                        <div class="form-group" style="max-width: 250px;">
                            <select name="category_id" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="max-width: 250px;">
                            <input type="text" name="subcategory_name" placeholder="Enter subcategory name" required>
                        </div>

                        <div class="form-group" style="max-width: 250px;">
                            <input type="text" name="description" placeholder="Enter description">
                        </div>

                        <button type="submit" name="add_subcategory" class="action-btn">Add Subcategory</button>
                    </div>
                </form>
            </div>

            <div class="categories-table-container">
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subcategories as $subcat): ?>
                            <tr>
                                <td><?php echo $subcat['id']; ?></td>
                                <td><?php echo htmlspecialchars($subcat['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($subcat['subcategory_name']); ?></td>
                                <td><?php echo htmlspecialchars($subcat['description'] ?? ''); ?></td>
                                <td>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="subcategory_id" value="<?php echo $subcat['id']; ?>">
                                        <button type="submit" name="remove_subcategory" class="action-btn delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2025 Yards of Grace. All rights reserved.</p>
    </footer>

    <script>
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