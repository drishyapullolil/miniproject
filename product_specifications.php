<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug function
function debug_to_console($data) {
    echo "<script>console.log('Debug: " . json_encode($data) . "');</script>";
}

// Add after database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Debug information
if (isset($_GET['edit_id'])) {
    echo "Attempting to edit ID: " . $_GET['edit_id'] . "<br>";
    
    $sql = "SELECT * FROM product_specifications WHERE id = " . (int)$_GET['edit_id'];
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo "Query error: " . mysqli_error($conn) . "<br>";
    } else {
        if (mysqli_num_rows($result) > 0) {
            echo "Record found<br>";
        } else {
            echo "No record found<br>";
        }
    }
}

// Handle Add/Edit/Delete for Product Specifications
if (isset($_POST['submit_product_spec'])) {
    debug_to_console($_POST); // Debug: Check submitted data
    
    // Validate and sanitize inputs
    $saree_id = isset($_POST['saree_id']) ? (int)$_POST['saree_id'] : 0;
    $material = isset($_POST['material']) ? mysqli_real_escape_string($conn, trim($_POST['material'])) : '';
    $style = isset($_POST['style']) ? mysqli_real_escape_string($conn, trim($_POST['style'])) : '';
    $saree_length = isset($_POST['saree_length']) ? (float)$_POST['saree_length'] : 0.0;
    $blouse_length = isset($_POST['blouse_length']) ? (float)$_POST['blouse_length'] : 0.0;
    $wash_care = isset($_POST['wash_care']) ? mysqli_real_escape_string($conn, trim($_POST['wash_care'])) : '';
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, trim($_POST['description'])) : '';

    // Validate required fields
    if (empty($saree_id) || empty($material) || empty($style) || empty($saree_length) || 
        empty($blouse_length) || empty($wash_care) || empty($description)) {
        $error = "All fields are required. Please fill in all the information.";
        debug_to_console("Validation failed: Empty fields detected");
    } else {
        // Check if saree_id exists in the sarees table
        $check_saree = mysqli_query($conn, "SELECT id FROM sarees WHERE id = $saree_id");
        if (!$check_saree) {
            $error = "Database error checking saree: " . mysqli_error($conn);
            debug_to_console("Database error: " . mysqli_error($conn));
        } else if (mysqli_num_rows($check_saree) == 0) {
            $error = "Error: Saree ID $saree_id does not exist in the sarees table.";
            debug_to_console("Invalid saree_id: $saree_id");
        } else {
            if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
                // Update existing record
                $edit_id = (int)$_POST['edit_id'];
                $query = "UPDATE product_specifications SET 
                         saree_id = ?, 
                         material = ?, 
                         style = ?, 
                         saree_length = ?, 
                         blouse_length = ?, 
                         wash_care = ?,
                         description = ? 
                         WHERE id = ?";
                         
                $stmt = mysqli_prepare($conn, $query);
                if (!$stmt) {
                    $error = "Error preparing update statement: " . mysqli_error($conn);
                    debug_to_console("Prepare Error: " . mysqli_error($conn));
                } else {
                    mysqli_stmt_bind_param($stmt, "issddssi", $saree_id, $material, $style, $saree_length, $blouse_length, $wash_care, $description, $edit_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $message = "Product specification updated successfully!";
                        debug_to_console("Update successful");
                    } else {
                        $error = "Error updating product specification: " . mysqli_stmt_error($stmt);
                        debug_to_console("Update Error: " . mysqli_stmt_error($stmt));
                    }
                    mysqli_stmt_close($stmt);
                }
            } else {
                // Insert new record
                $query = "INSERT INTO product_specifications 
                         (saree_id, material, style, saree_length, blouse_length, wash_care, description) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                         
                $stmt = mysqli_prepare($conn, $query);
                if (!$stmt) {
                    $error = "Error preparing insert statement: " . mysqli_error($conn);
                    debug_to_console("Prepare Error: " . mysqli_error($conn));
                } else {
                    mysqli_stmt_bind_param($stmt, "issddss", $saree_id, $material, $style, $saree_length, $blouse_length, $wash_care, $description);
                    if (mysqli_stmt_execute($stmt)) {
                        $message = "Product specification saved successfully!";
                        debug_to_console("Insert successful");
                    } else {
                        $error = "Error saving product specification: " . mysqli_stmt_error($stmt);
                        debug_to_console("Insert Error: " . mysqli_stmt_error($stmt));
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

// Handle Delete for Product Specifications
if (isset($_POST['delete_product_spec'])) {
    $id = (int)$_POST['delete_id'];
    $query = "DELETE FROM product_specifications WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        $error = "Error preparing delete statement: " . mysqli_error($conn);
        debug_to_console("Delete Prepare Error: " . mysqli_error($conn));
    } else {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Product specification deleted successfully!";
            debug_to_console("Delete successful");
        } else {
            $error = "Error deleting product specification: " . mysqli_stmt_error($stmt);
            debug_to_console("Delete Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch all product specifications
$product_specs = mysqli_query($conn, "SELECT * FROM product_specifications ORDER BY id DESC");
if (!$product_specs) {
    $error = "Error fetching product specifications: " . mysqli_error($conn);
    debug_to_console("Fetch Error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Product Specifications - Yards of Grace</title>
    <style>
        /* Add your CSS styles here (same as the reference page) */
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
            cursor: pointer;
            margin-right: 5px;
        }

        .action-btn:hover {
            background-color: #c82333;
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
        .submit-btn
        {
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
            background-color: white;
            cursor: pointer;
        }

        .role-select:hover {
            border-color: purple;
        }

        .role-select:disabled {
            background-color: #f8f8f8;
            cursor: not-allowed;
        }

        .actions-cell {
            display: flex;
            gap: 5px;
        }

        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
        }

        .full-width {
            width: 100%;
        }

        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            border-color: purple;
            outline: none;
            box-shadow: 0 0 0 2px rgba(128, 0, 128, 0.1);
        }

        .submit-btn {
            background: purple;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .submit-btn:hover {
            background: #660066;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        textarea.form-input {
            resize: vertical;
            min-height: 100px;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
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
                <a href="#"><li>Orders</li></a>
                <a href="#"><li>Products</li></a>
                <a href="#"><li>Reports</li></a>
                <a href="#"><li>Settings</li></a>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h2>Product Specifications</h2>
                <p>Manage product specifications for sarees</p>
            </div>

            <?php if (isset($message)): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Updated form with proper styling and validation -->
            <div class="form-container">
                <form method="POST" action="" class="product-spec-form">
                    <input type="hidden" name="edit_id" id="edit_id" value="">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="saree_id">Saree ID:</label>
                            <input type="number" name="saree_id" id="saree_id" class="form-input" required min="1">
                        </div>

                        <div class="form-group">
                            <label for="material">Material:</label>
                            <input type="text" name="material" id="material" class="form-input" required maxlength="255">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="style">Style:</label>
                            <input type="text" name="style" id="style" class="form-input" required maxlength="255">
                        </div>

                        <div class="form-group">
                            <label for="saree_length">Saree Length (meters):</label>
                            <input type="number" step="0.01" name="saree_length" id="saree_length" class="form-input" required min="0">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="blouse_length">Blouse Length (meters):</label>
                            <input type="number" step="0.01" name="blouse_length" id="blouse_length" class="form-input" required min="0">
                        </div>

                        <div class="form-group">
                            <label for="wash_care">Wash Care:</label>
                            <input type="text" name="wash_care" id="wash_care" class="form-input" required maxlength="255">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Description:</label>
                        <textarea name="description" id="description" class="form-input" rows="4" required></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="submit_product_spec" class="submit-btn">
                            <i class="fas fa-save"></i> Save Specification
                        </button>
                    </div>
                </form>
            </div>

            <!-- Display table -->
            <div class="users-table-container">
    <table class="users-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Saree ID</th>
                <th>Material</th>
                <th>Style</th>
                <th>Saree Length</th>
                <th>Blouse Length</th>
                <th>Wash Care</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($product_specs && mysqli_num_rows($product_specs) > 0): ?>
                <?php while ($spec = mysqli_fetch_assoc($product_specs)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($spec['id']); ?></td>
                        <td><?php echo htmlspecialchars($spec['saree_id']); ?></td>
                        <td><?php echo htmlspecialchars($spec['material']); ?></td>
                        <td><?php echo htmlspecialchars($spec['style']); ?></td>
                        <td><?php echo htmlspecialchars($spec['saree_length']); ?></td>
                        <td><?php echo htmlspecialchars($spec['blouse_length']); ?></td>
                        <td><?php echo htmlspecialchars($spec['wash_care']); ?></td>
                        <td><?php echo htmlspecialchars($spec['description']); ?></td>
                        <td class="actions-cell">
                            <button onclick="editProductSpec(<?php echo htmlspecialchars($spec['id']); ?>)" class="action-btn update">Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($spec['id']); ?>">
                                <button type="submit" name="delete_product_spec" class="action-btn" onclick="return confirm('Are you sure you want to delete this specification?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="no-records">No product specifications found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2025 Yards of Grace. All rights reserved.</p>
    </footer>

    <script>
        function editProductSpec(id) {
            // Add loading state
            const btn = event.target;
            btn.textContent = 'Loading...';
            btn.disabled = true;

            // Fetch data for the selected ID and populate the form
            fetch(`get_product_spec.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('saree_id').value = data.saree_id;
                    document.getElementById('material').value = data.material;
                    document.getElementById('style').value = data.style;
                    document.getElementById('saree_length').value = data.saree_length;
                    document.getElementById('blouse_length').value = data.blouse_length;
                    document.getElementById('wash_care').value = data.wash_care;
                    
                    // Scroll to form
                    document.querySelector('.product-spec-form').scrollIntoView({ behavior: 'smooth' });
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching product specification details');
                })
                .finally(() => {
                    // Reset button state
                    btn.textContent = 'Edit';
                    btn.disabled = false;
                });
        }
    </script>
</body>
</html>