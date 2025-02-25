<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch subcategory details
if (isset($_GET['subcategory_id'])) {
    $subcategory_id = $_GET['subcategory_id'];
    $stmt = $conn->prepare("SELECT * FROM subcategories WHERE id = ?");
    $stmt->bind_param("i", $subcategory_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subcategory = $result->fetch_assoc();
}

// Handle Update Subcategory
if (isset($_POST['update_subcategory'])) {
    try {
        $subcategory_id = $_POST['subcategory_id'];
        $subcategory_name = htmlspecialchars($_POST['subcategory_name']);
        $description = htmlspecialchars($_POST['description']);
        $price = $_POST['price'];

        // Handle image upload
        if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_name = basename($_FILES['image']['name']);
            $image_tmp = $_FILES['image']['tmp_name'];
            $image_path = "uploads/" . $image_name;
            move_uploaded_file($image_tmp, $image_path);
        } else {
            $image_path = $subcategory['image']; // Keep the existing image if no new image is uploaded
        }

        // Update subcategory
        $stmt = $conn->prepare("UPDATE subcategories SET subcategory_name = ?, description = ?, price = ?, image = ? WHERE id = ?");
        $stmt->bind_param("ssdsi", $subcategory_name, $description, $price, $image_path, $subcategory_id);
        
        if ($stmt->execute()) {
            $message = "Subcategory successfully updated!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subcategory Details - Yards of Grace</title>
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
            text-decoration:none;
        }

        .delete-btn {
            background-color: #dc3545;
        }

        .add-subcategory-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 10px;
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
        .subcategory-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .subcategory-details img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group input[type="file"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }

        .action-btn {
            background-color: purple;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
            text-decoration: none;
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
                <a href="Categories.php"><li>Category Management</li></a>
                <a href="order_manage.php"><li>Orders</li></a>
                <a href="#"><li>Settings</li></a>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h2>Subcategory Details</h2>
                <p>View and edit subcategory details</p>
            </div>

            <?php if (isset($message)): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="subcategory-details">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="subcategory_id" value="<?php echo $subcategory['id']; ?>">
                    
                    <div class="form-group">
                        <label>Subcategory Name:</label>
                        <input type="text" name="subcategory_name" value="<?php echo htmlspecialchars($subcategory['subcategory_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Description:</label>
                        <textarea name="description" rows="4"><?php echo htmlspecialchars($subcategory['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Price:</label>
                        <input type="number" name="price" step="0.01" value="<?php echo htmlspecialchars($subcategory['price']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Image:</label>
                        <input type="file" name="image">
                        <?php if ($subcategory['image']): ?>
                            <img src="<?php echo $subcategory['image']; ?>" alt="Subcategory Image" style="max-width: 200px; margin-top: 10px;">
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="update_subcategory" class="action-btn">Update Subcategory</button>
                </form>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2025 Yards of Grace. All rights reserved.</p>
    </footer>
</body>
</html>