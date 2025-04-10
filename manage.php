<?php
session_start();


// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}


// Database connection
$db = new mysqli('localhost', 'root', '','yardsofgrace');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}


// Handle Delete User
if (isset($_POST['delete_user'])) {
    $user_id = $db->real_escape_string($_POST['user_id']);
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user_id);
   
    if ($stmt->execute()) {
        $message = "User successfully deleted";
    } else {
        $error = "Error deleting user";
    }
    $stmt->close();
}


// Handle Role Update
if (isset($_POST['update_role'])) {
    $user_id = $db->real_escape_string($_POST['user_id']);
    $new_role = $db->real_escape_string($_POST['new_role']);
   
    // Prevent changing own role
    if ($user_id != $_SESSION['user_id']) {
        $query = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("si", $new_role, $user_id);
       
        if ($stmt->execute()) {
            $message = "Role successfully updated";
        } else {
            $error = "Error updating role";
        }
        $stmt->close();
    } else {
        $error = "Cannot modify your own role";
    }
}


// Fetch Users
$query = "SELECT u.*,
          (SELECT COUNT(*) FROM logins l WHERE l.user_id = u.id) as login_count,
          (SELECT MAX(login_time) FROM logins l WHERE l.user_id = u.id) as last_login_time
          FROM users u ORDER BY u.created_at DESC";
$result = $db->query($query);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Management - Yards of Grace</title>
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


        .users-table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #eee;
            overflow-x: auto;
        }


        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }


        .users-table th, .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }


        .users-table th {
            background-color: #f8f8f8;
            color: #666;
        }


        .action-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            opacity: 0.7;
            cursor: not-allowed;
        }


        .action-btn:disabled {
            background-color: #dc3545;
            opacity: 0.7;
        }


        .action-btn.update {
            background-color: #28a745;
        }


        .action-btn.update:hover {
            background-color: #218838;
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


        .role-select {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f8f8;
            cursor: not-allowed;
            opacity: 0.7;
        }


        .role-select:disabled {
            background-color: #f8f8f8;
            cursor: not-allowed;
        }


        .actions-cell {
            display: flex;
            gap: 5px;
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


    <div class="dashboard-container">
        <div class="sidebar">
            <ul class="sidebar-menu">
            <a href="admin.php"><li >Dashboard Overview</li></a>
                <a href="manage.php"><li class="active">User Management</li></a>
                <a href="Category.php"><li>Category Management</li></a>
                <a href="subcategory.php"><li>Subcategory Management</li></a>
                <a href="category_details.php"><li>Product Management</li></a>
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
                <h2>User Management</h2>
                <p>Manage all user accounts</p>
            </div>


            <?php if (isset($message)): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
           
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>


            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Login Count</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phoneno']); ?></td>
                            <td>
                                <select class="role-select" disabled>
                                    <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </td>
                            <td><?php echo $user['login_count']; ?></td>
                            <td><?php echo $user['last_login_time']; ?></td>
                            <td class="actions-cell">
                                <button class="action-btn" disabled>Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <footer>
        <p>© 2025 Yards of Grace. All rights reserved.</p>
    </footer>


    <script>
        // Add active class to sidebar menu items
        document.querySelectorAll('.sidebar-menu li').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelector('.sidebar-menu li.active').classList.remove('active');
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
