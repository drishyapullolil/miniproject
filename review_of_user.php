<?php
session_start();

// Check if user is admin
require_once 'db.php';

// First add status column if it doesn't exist
$alter_table = "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS status ENUM('enabled', 'disabled') DEFAULT 'enabled'";
$conn->query($alter_table);

// Fetch all reviews with user and product details
$review_query = "SELECT r.*, u.username, s.name as product_name 
                FROM reviews r
                LEFT JOIN users u ON r.user_id = u.id 
                LEFT JOIN sarees s ON r.product_id = s.id
                ORDER BY r.created_at DESC";

$result = $conn->query($review_query);

// Get counts for stats
$total_reviews = $result->num_rows;
$avg_rating_query = "SELECT AVG(rating) as avg_rating FROM reviews";
$avg_result = $conn->query($avg_rating_query);
$avg_rating = round($avg_result->fetch_assoc()['avg_rating'], 1);
$five_star_query = "SELECT COUNT(*) as count FROM reviews WHERE rating = 5";
$five_star_result = $conn->query($five_star_query);
$five_star_count = $five_star_result->fetch_assoc()['count'];

// Handle enable/disable
if(isset($_POST['toggle_status'])) {
    $review_id = $_POST['review_id'];
    $new_status = $_POST['new_status'];
    
    $update_query = "UPDATE reviews SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $review_id);
    $stmt->execute();
    
    header("Location: review_of_user.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Management - Yards of Grace</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
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

        .reviews-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .reviews-table th,
        .reviews-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .reviews-table th {
            background-color: purple;
            color: white;
        }
        
        .reviews-table tr:hover {
            background-color: #f8f8f8;
        }
        
        .rating-stars {
            color: purple;
        }

        .logout-btn {
            background-color: purple;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .status-toggle {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .status-enabled {
            background-color: #4CAF50;
            color: white;
        }

        .status-disabled {
            background-color: #f44336;
            color: white;
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
            <a href="admin.php"><li >Dashboard Overview</li></a>
                <a href="manage.php"><li >User Management</li></a>
                <a href="Category.php"><li >Category Management</li></a>
                <a href="subcategory.php"><li >Subcategory Management</li></a>
                <a href="category_details.php"><li >Product Management</li></a>
                <a href="wedding_categories.php"><li>Wedding Categories</li></a>
                <a href="wedding_products.php"><li>Wedding Products</li></a>
                <a href="wedding_images.php"><li>Wedding Specifications</li></a>
                <a href="review_of_user.php"><li class="active">Reviews</li></a>
                <a href="admin_report.php"><li>Reports</li></a>
                <a href="order_manage.php"><li >Orders</li></a>
                <a href="#"><li>Products</li></a>
                <a href="#"><li>Settings</li></a>
            </ul>
        </div>

        <div class="main-content">
            <h2 style="color: purple; margin-bottom: 20px;">Review Management</h2>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Reviews</h3>
                    <p><?php echo $total_reviews; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Average Rating</h3>
                    <p><?php echo $avg_rating; ?> ★</p>
                </div>
                <div class="stat-card">
                    <h3>5-Star Reviews</h3>
                    <p><?php echo $five_star_count; ?></p>
                </div>
            </div>
            
            <table class="reviews-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($review = $result->fetch_assoc()): 
                        $status = isset($review['status']) ? $review['status'] : 'enabled'; // Default to enabled if status not set
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($review['id']); ?></td>
                            <td><?php echo htmlspecialchars($review['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($review['username']); ?></td>
                            <td class="rating-stars">
                                <?php 
                                for($i = 1; $i <= 5; $i++) {
                                    echo ($i <= $review['rating']) ? '★' : '☆';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($review['comment']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <input type="hidden" name="new_status" value="<?php echo $status == 'enabled' ? 'disabled' : 'enabled'; ?>">
                                    <button type="submit" name="toggle_status" 
                                            class="status-toggle <?php echo $status == 'enabled' ? 'status-enabled' : 'status-disabled'; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        <p>© 2025 Yards of Grace. All rights reserved.</p>
    </footer>
</body>
</html>