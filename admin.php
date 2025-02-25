<?php
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Yards of Grace</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #eee;
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            color: purple;
        }

        .recent-orders {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #eee;
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .order-table th, .order-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .order-table th {
            background-color: #f8f8f8;
            color: #666;
        }

        .status-pending {
            color: orange;
        }

        .status-delivered {
            color: green;
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
                <a href="admin.php"><li class="active">Dashboard Overview</li></a>
                <a href="manage.php"><li >User Management</li></a>
                <a href="Category.php"><li>Category Management</li></a>
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
            <h2>Welcome, <?php 
    echo isset($_SESSION['username']) 
        ? htmlspecialchars($_SESSION['username']) 
        : 'Admin'; 
?></h2>
                <p>Here's an overview of your account</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <p>5</p>
                </div>
                <div class="stat-card">
                    <h3>Wishlist Items</h3>
                    <p>12</p>
                </div>
                <div class="stat-card">
                    <h3>Cart Items</h3>
                    <p>3</p>
                </div>
                <div class="stat-card">
                    <h3>Total Spent</h3>
                    <p>₹25,000</p>
                </div>
            </div>

            <div class="recent-orders">
                <h3>Recent Orders</h3>
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#ORD001</td>
                            <td>2025-01-20</td>
                            <td>Silk Saree</td>
                            <td>₹12,500</td>
                            <td class="status-delivered">Delivered</td>
                        </tr>
                        <tr>
                            <td>#ORD002</td>
                            <td>2025-01-18</td>
                            <td>Cotton Saree</td>
                            <td>₹8,000</td>
                            <td class="status-pending">Pending</td>
                        </tr>
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