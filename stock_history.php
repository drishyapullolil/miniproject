<?php
// stock_history.php
session_start();
require_once 'db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Check if saree_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No saree specified!";
    header('Location: Category.php');
    exit();
}

$saree_id = (int)$_GET['id'];

// First, fetch the saree details
$saree_query = "
    SELECT *
    FROM sarees 
    WHERE id = ?
";

$stmt = $conn->prepare($saree_query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $saree_id);
$stmt->execute();
$result = $stmt->get_result();
$saree = $result->fetch_assoc();

if (!$saree) {
    $_SESSION['error'] = "Saree not found!";
    header('Location: Category.php');
    exit();
}

// Fetch stock history with more detailed information
$history_query = "
    SELECT 
        sh.*,
        u.username as updated_by,
        DATE_FORMAT(sh.updated_at, '%Y-%m-%d') as update_date,
        DATE_FORMAT(sh.updated_at, '%h:%i %p') as update_time
    FROM saree_stock_history sh
    LEFT JOIN users u ON sh.updated_by = u.id
    WHERE sh.saree_id = ?
    ORDER BY sh.updated_at DESC
";

$stmt = $conn->prepare($history_query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $saree_id);
$stmt->execute();
$history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock History - <?php echo htmlspecialchars($saree['name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: purple;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .history-table th,
        .history-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .history-table th {
            background-color: purple;
            color: white;
        }

        .history-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .stock-increase {
            color: #28a745;
            font-weight: bold;
        }

        .stock-decrease {
            color: #dc3545;
            font-weight: bold;
        }

        .saree-details {
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .saree-details h2 {
            color: purple;
            margin-bottom: 10px;
        }

        .saree-details p {
            margin: 5px 0;
            color: #666;
        }

        .no-records {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="category_details.php" class="back-button">← Back to Saree Management</a>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="saree-details">
            <h2><?php echo htmlspecialchars($saree['name']); ?> - Stock History</h2>
            <p><strong>Current Stock:</strong> <?php echo $saree['stock']; ?> units</p>
        </div>

        <?php if ($history->num_rows > 0): ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Stock Change</th>
                        <th>Previous Stock</th>
                        <th>New Stock</th>
                        <th>Updated By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $history->fetch_assoc()): ?>
                        <tr>
                            <td class="datetime-column"><?php echo htmlspecialchars($row['update_date']); ?></td>
                            <td class="datetime-column"><?php echo htmlspecialchars($row['update_time']); ?></td>
                            <td>
                                <span class="stock-change <?php echo $row['stock_added'] >= 0 ? 'stock-increase' : 'stock-decrease'; ?>">
                                    <?php 
                                    echo $row['stock_added'] >= 0 ? '+' . $row['stock_added'] : $row['stock_added']; 
                                    ?> units
                                </span>
                            </td>
                            <td><?php echo $row['previous_stock']; ?></td>
                            <td><?php echo $row['new_stock']; ?></td>
                            <td><?php echo htmlspecialchars($row['updated_by'] ?? 'Unknown'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-records">
                <p>No stock history records found for this saree.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>