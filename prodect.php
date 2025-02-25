<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Initialize the products array in the session if it doesn't exist
if (!isset($_SESSION['products'])) {
    $_SESSION['products'] = [];
}

// Handle Add Product
if (isset($_POST['add_product'])) {
    $name = htmlspecialchars($_POST['name']);
    $description = htmlspecialchars($_POST['description']);
    $price = floatval($_POST['price']);
    $image_url = htmlspecialchars($_POST['image_url']);

    // Add the new product to the session array
    $_SESSION['products'][] = [
        'id' => uniqid(), // Generate a unique ID for the product
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'image_url' => $image_url
    ];

    $message = "Product successfully added!";
}

// Handle Delete Product
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];

    // Find and remove the product from the session array
    foreach ($_SESSION['products'] as $key => $product) {
        if ($product['id'] === $product_id) {
            unset($_SESSION['products'][$key]);
            $message = "Product successfully deleted!";
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Yards of Grace</title>
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
            text-decoration: none;
            color: inherit;
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

        .products-table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #eee;
            overflow-x: auto;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .products-table th, .products-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .products-table th {
            background-color: #f8f8f8;
            color: #666;
        }

        .action-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }

        .action-btn:hover {
            background-color: #c82333;
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

        .logout-btn:hover {
            background-color: #800080;
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
                <a href="products.php"><li class="active">Product Management</li></a>
                <a href="#"><li>Orders</li></a>
                <a href="#"><li>Reports</li></a>
                <a href="#"><li>Settings</li></a>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h2>Product Management</h2>
                <p>Manage all products</p>
            </div>

            <?php if (isset($message)): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Add Product Form -->
            <form method="POST" action="">
                <input type="text" name="name" placeholder="Product Name" required>
                <input type="text" name="description" placeholder="Description" required>
                <input type="number" name="price" placeholder="Price" step="0.01" required>
                <input type="text" name="image_url" placeholder="Image URL" required>
                <button type="submit" name="add_product">Add Product</button>
            </form>

            <!-- Display Products Table -->
            <div class="products-table-container">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Image URL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['products'] as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['description']; ?></td>
                                <td><?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['image_url']; ?></td>
                                <td>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="delete_product" class="action-btn">Delete</button>
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
</body>
</html>