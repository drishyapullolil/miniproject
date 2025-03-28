<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "yardsofgrace";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initial date range (last 30 days by default)
$defaultStartDate = date('Y-m-d', strtotime('-30 days'));
$defaultEndDate = date('Y-m-d');

// Get filter values if submitted
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'full';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$orderBy = isset($_GET['order_by']) ? $_GET['order_by'] : 'id';
$orderDir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'ASC';

// Handle CSV download
if (isset($_GET['download_csv'])) {
    $reportType = $_GET['download_csv'];
    
    // Prepare filter conditions
    $dateCondition = "";
    if (!empty($startDate) && !empty($endDate)) {
        $dateCondition = " AND created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
    }
    
    $statusCondition = "";
    if ($status != 'all') {
        $statusCondition = " AND order_status = '$status'";
    }
    
    // Create reports directory if it doesn't exist
    $reportDir = __DIR__ . '/reports/';
    if (!file_exists($reportDir)) {
        mkdir($reportDir, 0777, true);
    }
    
    $filename = "Admin_Report_" . $reportType . "_" . date('Ymd_His') . ".csv";
    $filepath = $reportDir . $filename;
    
    // Open file for writing
    $file = fopen($filepath, 'w');
    
    // --- USERS REPORT ---
    if ($reportType == 'full' || $reportType == 'users') {
        // Validate user table orderBy column
        $validUserColumns = ['id', 'username', 'email', 'role'];
        $userOrderBy = in_array($orderBy, $validUserColumns) ? $orderBy : 'id';
        
        // Write CSV header
        fputcsv($file, ['ID', 'Username', 'Email', 'Role']);
        
        $userQuery = "SELECT id, username, email, role FROM users 
                     WHERE 1=1 $dateCondition
                     ORDER BY $userOrderBy $orderDir";
        $userResult = $conn->query($userQuery);
        
        while ($row = $userResult->fetch_assoc()) {
            fputcsv($file, $row);
        }
        
        if ($reportType != 'full') {
            fclose($file);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            readfile($filepath);
            exit();
        } else {
            fputcsv($file, []); // Empty line between sections
            fputcsv($file, ['ORDERS REPORT']);
            fputcsv($file, []); // Empty line
        }
    }
    
    // --- ORDERS REPORT ---
    if ($reportType == 'full' || $reportType == 'orders') {
        // First, let's define our base query with explicit table references
        $orderQuery = "SELECT 
            orders.id,
            users.username,
            orders.total_amount,
            orders.order_status,
            orders.payment_method
        FROM orders 
        INNER JOIN users ON orders.user_id = users.id 
        WHERE 1=1";

        // Add date conditions with explicit table reference
        if (!empty($startDate) && !empty($endDate)) {
            $orderQuery .= " AND orders.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
        }

        // Add status condition
        if ($status != 'all') {
            $orderQuery .= " AND orders.order_status = '$status'";
        }

        // Add ordering with explicit table references
        if ($orderBy == 'username') {
            $orderQuery .= " ORDER BY users.username $orderDir";
        } else {
            $orderQuery .= " ORDER BY orders.$orderBy $orderDir";
        }

        // Execute query
        $result = $conn->query($orderQuery);

        if (!$result) {
            die("Query failed: " . $conn->error);
        }
        
        // Handle order by clause - determine if the column belongs to orders or users table
        $orderByTable = "orders";
        if ($orderBy == "username") {
            $orderByTable = "users";
        }

        // Write CSV header
        fputcsv($file, ['Order ID', 'Username', 'Total Amount', 'Status', 'Payment Method']);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($file, [
                $row['id'],
                $row['username'],
                $row['total_amount'],
                $row['order_status'],
                $row['payment_method']
            ]);
        }
        
        if ($reportType != 'full') {
            fclose($file);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            readfile($filepath);
            exit();
        } else {
            fputcsv($file, []); // Empty line between sections
            fputcsv($file, ['SAREE INVENTORY REPORT']);
            fputcsv($file, []); // Empty line
        }
    }
    
    // --- SAREE STOCK REPORT ---
    if ($reportType == 'full' || $reportType == 'sarees') {
        // Validate saree table orderBy column
        $validSareeColumns = ['id', 'name', 'price', 'stock', 'color', 'created_at', 'last_stock_update'];
        $sareeOrderBy = in_array($orderBy, $validSareeColumns) ? $orderBy : 'id';
        
        // Write CSV header
        fputcsv($file, ['Saree ID', 'Name', 'Price', 'Stock', 'Color', 'Last Stock Update']);
        
        $sareeQuery = "SELECT id, name, price, stock, color, last_stock_update FROM sarees
                       WHERE 1=1 $dateCondition
                       ORDER BY $sareeOrderBy $orderDir";
        $sareeResult = $conn->query($sareeQuery);
        
        while ($row = $sareeResult->fetch_assoc()) {
            fputcsv($file, [
                $row['id'],
                $row['name'],
                $row['price'],
                $row['stock'],
                $row['color'],
                $row['last_stock_update']
            ]);
        }
    }
    
    fclose($file);
    
    // Force download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($filepath);
    exit();
}

// Handle PDF download
if (isset($_GET['download'])) {
    require_once('tcpdf/tcpdf.php');

    // Create PDF
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetTitle('Admin Report - Yards of Grace');
    $pdf->SetHeaderData('', 0, 'Admin Report - Yards of Grace', 'Generated on: ' . date('Y-m-d H:i:s'));
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', 12));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', 10));
    $pdf->SetMargins(15, 30, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);

    // Cover Page
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, 'Admin Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 14);
    $pdf->Cell(0, 10, 'Yards of Grace', 0, 1, 'C');
    $pdf->Ln(20);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'This report contains detailed information about users, orders, and sarees.', 0, 1, 'C');
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    $pdf->Ln(20);
    $pdf->Cell(0, 10, 'Prepared by: ' . $_SESSION['username'], 0, 1, 'C');
    $pdf->AddPage();

    // Added filter info to PDF report
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Filter Settings', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'Date Range: ' . $startDate . ' to ' . $endDate, 0, 1, 'L');
    $pdf->Cell(0, 10, 'Status Filter: ' . ucfirst($status), 0, 1, 'L');
    $pdf->Cell(0, 10, 'Order By: ' . $orderBy . ' ' . $orderDir, 0, 1, 'L');
    $pdf->AddPage();

    // Prepare filter conditions for queries
    $userDateCondition = "";
    $orderDateCondition = "";
    $sareeDataCondition = "";
    
    if (!empty($startDate) && !empty($endDate)) {
        $userDateCondition = " AND users.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
        $orderDateCondition = " AND orders.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
        $sareeDataCondition = " AND sarees.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
    }
    
    $statusCondition = "";
    if ($status != 'all') {
        $statusCondition = " AND order_status = '$status'";
    }

    // --- USERS REPORT ---
    if ($_GET['download'] == 'full' || $_GET['download'] == 'users') {
        // Validate user table orderBy column
        $validUserColumns = ['id', 'username', 'email', 'role'];
        $userOrderBy = in_array($orderBy, $validUserColumns) ? $orderBy : 'id';
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Users Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(30, 10, 'ID', 1);
        $pdf->Cell(50, 10, 'Username', 1);
        $pdf->Cell(60, 10, 'Email', 1);
        $pdf->Cell(30, 10, 'Role', 1);
        $pdf->Ln();

        $userQuery = "SELECT id, username, email, role FROM users
                      WHERE 1=1" . $userDateCondition . "
                      ORDER BY $userOrderBy $orderDir";
        $userResult = $conn->query($userQuery);
        while ($row = $userResult->fetch_assoc()) {
            $pdf->Cell(30, 10, $row['id'], 1);
            $pdf->Cell(50, 10, $row['username'], 1);
            $pdf->Cell(60, 10, $row['email'], 1);
            $pdf->Cell(30, 10, ucfirst($row['role']), 1);
            $pdf->Ln();
        }
        $pdf->Ln(10);
    }

    // --- ORDERS REPORT ---
    if ($_GET['download'] == 'full' || $_GET['download'] == 'orders') {
        // Validate order table orderBy column
        $validOrderColumns = ['id', 'username', 'total_amount', 'order_status', 'payment_method'];
        $orderOrderBy = in_array($orderBy, $validOrderColumns) ? $orderBy : 'id';
        
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Orders Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(20, 10, 'ID', 1);
        $pdf->Cell(40, 10, 'User', 1);
        $pdf->Cell(30, 10, 'Total Amount', 1);
        $pdf->Cell(30, 10, 'Status', 1);
        $pdf->Cell(60, 10, 'Payment Method', 1);
        $pdf->Ln();

        // Handle order by clause
        $orderByTable = "orders";
        if ($orderOrderBy == "username") {
            $orderByTable = "users";
        }

        $orderQuery = "SELECT orders.id, users.username, orders.total_amount, orders.order_status, orders.payment_method 
                       FROM orders 
                       JOIN users ON orders.user_id = users.id
                       WHERE 1=1" . $orderDateCondition . $statusCondition . "
                       ORDER BY $orderByTable.$orderOrderBy $orderDir";
        
        $orderResult = $conn->query($orderQuery);
        while ($row = $orderResult->fetch_assoc()) {
            $pdf->Cell(20, 10, $row['id'], 1);
            $pdf->Cell(40, 10, $row['username'], 1);
            $pdf->Cell(30, 10, "Rs:" . number_format($row['total_amount'], 2), 1);
            $pdf->Cell(30, 10, ucfirst($row['order_status']), 1);
            $pdf->Cell(60, 10, ucfirst($row['payment_method']), 1);
            $pdf->Ln();
        }
        $pdf->Ln(10);
    }

    // --- SAREE STOCK REPORT ---
    if ($_GET['download'] == 'full' || $_GET['download'] == 'sarees') {
        // Validate saree table orderBy column
        $validSareeColumns = ['id', 'name', 'price', 'stock', 'color', 'created_at', 'last_stock_update'];
        $sareeOrderBy = in_array($orderBy, $validSareeColumns) ? $orderBy : 'id';
        
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Saree Stock Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(20, 10, 'ID', 1);
        $pdf->Cell(60, 10, 'Saree Name', 1);
        $pdf->Cell(30, 10, 'Price', 1);
        $pdf->Cell(30, 10, 'Stock', 1);
        $pdf->Cell(50, 10, 'Last Update', 1);
        $pdf->Ln();

        $sareeQuery = "SELECT id, name, price, stock, last_stock_update FROM sarees
                       WHERE 1=1" . $sareeDataCondition . "
                       ORDER BY $sareeOrderBy $orderDir";
        $sareeResult = $conn->query($sareeQuery);
        while ($row = $sareeResult->fetch_assoc()) {
            $pdf->Cell(20, 10, $row['id'], 1);
            $pdf->Cell(60, 10, $row['name'], 1);
            $pdf->Cell(30, 10, "Rs:" . number_format($row['price'], 2), 1);
            $pdf->Cell(30, 10, $row['stock'], 1);
            $pdf->Cell(50, 10, $row['last_stock_update'], 1);
            $pdf->Ln();
        }
    }

    // Close database connection
    $conn->close();

    // Save PDF file in 'reports' folder
    $reportDir = __DIR__ . '/reports/';
    if (!file_exists($reportDir)) {
        mkdir($reportDir, 0777, true);
    }

    $pdfFilePath = $reportDir . 'Admin_Report_' . $_GET['download'] . '.pdf';
    $pdf->Output($pdfFilePath, 'F');

    // Force download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Admin_Report_' . $_GET['download'] . '.pdf"');
    readfile($pdfFilePath);
    exit();
}

// Get order status options for dropdown
$statusOptions = array('all', 'pending', 'processing', 'shipped', 'delivered', 'cancelled', 'cash_received');

// Get column names for orderBy dropdown
$orderByColumns = array('id', 'username', 'name', 'total_amount', 'price', 'stock', 'created_at');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Reports - Yards of Grace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="report.css">
</head>
<body>
    <div class="top-bar">
        <p>Free domestic shipping on orders above INR 10,000. International shipping available.</p>
    </div>

    <div class="header-main">
        <div class="header-center">
            <h1><img src="logo3.png" alt="Logo" width="50px">YARDS OF GRACE</h1>
        </div>
        <div class="nav-buttons">
            <a href="admin.php" class="dashboard-btn">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <button class="logout-btn" onclick="window.location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <div class="main-container">
        <div class="page-title">
            <h1>Admin Reports</h1>
            <p>Generate and download reports to analyze your business data</p>
        </div>
        
        <!-- Filter section -->
        <div class="filter-section">
            <h3 class="filter-title"><i class="fas fa-filter"></i> Filter Report Data</h3>
            <form action="" method="GET" class="filter-form">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                </div>
                <div class="form-group">
                    <label for="report_type">Report Type</label>
                    <select id="report_type" name="report_type" class="form-control">
                        <option value="full" <?php echo $reportType == 'full' ? 'selected' : ''; ?>>Full Report</option>
                        <option value="users" <?php echo $reportType == 'users' ? 'selected' : ''; ?>>Users Only</option>
                        <option value="orders" <?php echo $reportType == 'orders' ? 'selected' : ''; ?>>Orders Only</option>
                        <option value="sarees" <?php echo $reportType == 'sarees' ? 'selected' : ''; ?>>Sarees Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Order Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $status == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="cash_received" <?php echo $status == 'cash_received' ? 'selected' : ''; ?>>Cash Received</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="order_by">Sort By</label>
                    <select id="order_by" name="order_by" class="form-control">
                        <option value="id" <?php echo $orderBy == 'id' ? 'selected' : ''; ?>>ID</option>
                        <option value="username" <?php echo $orderBy == 'username' ? 'selected' : ''; ?>>Username</option>
                        <option value="name" <?php echo $orderBy == 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="total_amount" <?php echo $orderBy == 'total_amount' ? 'selected' : ''; ?>>Total Amount</option>
                        <option value="price" <?php echo $orderBy == 'price' ? 'selected' : ''; ?>>Price</option>
                        <option value="stock" <?php echo $orderBy == 'stock' ? 'selected' : ''; ?>>Stock</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="order_dir">Order</label>
                    <select id="order_dir" name="order_dir" class="form-control">
                        <option value="ASC" <?php echo $orderDir == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="DESC" <?php echo $orderDir == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
                
                <div class="filter-buttons">
                    <button type="reset" class="filter-btn reset-btn">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button type="submit" class="filter-btn apply-btn">
                        <i class="fas fa-check"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
        
        <div class="reports-container">
            <div class="report-card full-report-card">
                <span class="format-badge pdf-badge">PDF</span>
                <span class="format-badge csv-badge">CSV</span>
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="card-title">
                        <h2>Complete Business Report</h2>
                        <p>All-in-one comprehensive report</p>
                    </div>
                </div>
                <div class="card-content">
                    <div class="content-section">
                        <div class="section-title">
                            <i class="fas fa-info-circle"></i> Description
                        </div>
                        <p>This complete report combines all business data in a single document, providing a comprehensive overview of your entire operation.</p>
                    </div>
                    <div class="content-section">
                        <div class="section-title">
                            <i class="fas fa-list-ul"></i> Includes
                            <span class="help-icon">?
                                <span class="help-text">This report is comprehensive and may take longer to generate</span>
                            </span>
                        </div>
                        <ul class="feature-list">
                            <li>Complete user profile information</li>
                            <li>All orders with detailed status</li>
                            <li>Full inventory and stock levels</li>
                            <li>Organized by sections for easy reference</li>
                        </ul>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?download=full<?php echo (!empty($startDate) ? '&start_date='.$startDate : ''); ?><?php echo (!empty($endDate) ? '&end_date='.$endDate : ''); ?><?php echo ($status != 'all' ? '&status='.$status : ''); ?><?php echo (!empty($orderBy) ? '&order_by='.$orderBy : ''); ?><?php echo (!empty($orderDir) ? '&order_dir='.$orderDir : ''); ?>" class="download-btn" onclick="showNotification('pdf-full')">
                        <i class="fas fa-file-pdf"></i> PDF Format
                    </a>
                    <a href="?download_csv=full<?php echo (!empty($startDate) ? '&start_date='.$startDate : ''); ?><?php echo (!empty($endDate) ? '&end_date='.$endDate : ''); ?><?php echo ($status != 'all' ? '&status='.$status : ''); ?><?php echo (!empty($orderBy) ? '&order_by='.$orderBy : ''); ?><?php echo (!empty($orderDir) ? '&order_dir='.$orderDir : ''); ?>" class="download-btn csv-btn" onclick="showNotification('csv-full')">
                        <i class="fas fa-file-csv"></i> CSV Format
                    </a>
                </div>
            </div>
            
            <div class="report-card">
                <span class="format-badge pdf-badge">PDF</span>
                <span class="format-badge csv-badge">CSV</span>
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-title">
                        <h2>Users Report</h2>
                        <p>Customer & admin accounts data</p>
                    </div>
                </div>
                <div class="card-content">
                    <div class="content-section">
                        <div class="section-title">
                            <i class="fas fa-info-circle"></i> Description
                        </div>
                        <p>Get detailed information about all registered users and admin accounts in your system.</p>
                    </div>
                    <div class="content-section">
                        <div class="section-title">
                            <i class="fas fa-list-ul"></i> Includes
                        </div>
                        <ul class="feature-list">
                            <li>User IDs and usernames</li>
                            <li>Email addresses for communication</li>
                            <li>Account roles and permissions</li>
                            <li>Organized in tabular format</li>
                        </ul>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?download=users<?php echo (!empty($startDate) ? '&start_date='.$startDate : ''); ?><?php echo (!empty($endDate) ? '&end_date='.$endDate : ''); ?><?php echo (!empty($orderBy) ? '&order_by='.$orderBy : ''); ?><?php echo (!empty($orderDir) ? '&order_dir='.$orderDir : ''); ?>" class="download-btn" onclick="showNotification('pdf-users')">
                        <i class="fas fa-file-pdf"></i> PDF Format
                    </a>
                    <a href="?download_csv=users<?php echo (!empty($startDate) ? '&start_date='.$startDate : ''); ?><?php echo (!empty($endDate) ? '&end_date='.$endDate : ''); ?><?php echo (!empty($orderBy) ? '&order_by='.$orderBy : ''); ?><?php echo (!empty($orderDir) ? '&order_dir='.$orderDir : ''); ?>" class="download-btn csv-btn" onclick="showNotification('csv-users')">
                        <i class="fas fa-file-csv"></i> CSV Format
                    </a>
                </div>
            </div>
            
            <div class="report-card">
                <span class="format-badge pdf-badge">PDF</span>
                <span class="format-badge csv-badge">CSV</span>
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="card-title">
                        <h2>Orders Report</h2>
                        <p>Transactions and order details</p>
                    </div>
                </div>
                <div class="card-content">
                    <div class="content-section">
                        <div class="section-title">
                            <i class="fas fa-info-circle"></i> Description
                        </div>
                        <p>Analyze all customer orders and transactions with detailed status information.</p>
                    </div>
                    <div class="content-section">
                        <div class="section-title">
                            <i class="fas fa-list-ul"></i> Includes
                        </div>
                        <ul class="feature-list">
                            <li>Order IDs and customer details</li>
                            <li>Total amount and payment methods</li>
                            <li>Current order status tracking</li>
                            <li>Date and time information</li>
                        </ul>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?download=orders<?php echo (!empty($startDate) ? '&start_date='.$startDate : ''); ?><?php echo (!empty($endDate) ? '&end_date='.$endDate : ''); ?><?php echo ($status != 'all' ? '&status='.$status : ''); ?><?php echo (!empty($orderBy) ? '&order_by='.$orderBy : ''); ?><?php echo (!empty($orderDir) ? '&order_dir='.$orderDir : ''); ?>" class="download-btn" onclick="showNotification('pdf-orders')">
                        <i class="fas fa-file-pdf"></i> PDF Format
                    </a>
                    <a href="?download_csv=orders<?php echo (!empty($startDate) ? '&start_date='.$startDate : ''); ?><?php echo (!empty($endDate) ? '&end_date='.$endDate : ''); ?><?php echo ($status != 'all' ? '&status='.$status : ''); ?><?php echo (!empty($orderBy) ? '&order_by='.$orderBy : ''); ?><?php echo (!empty($orderDir) ? '&order_dir='.$orderDir : ''); ?>" class="download-btn csv-btn" onclick="showNotification('csv-orders')">
                        <i class="fas fa-file-csv"></i> CSV Format
                    </a>
                </div>
            </div>
            
            <div class="report-card">
                <span class="format-badge pdf-badge">PDF</span>
                <span class="format-badge csv-badge">CSV</span>
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div class="card-title">
                        <h2>Saree Inventory Report</h2>
                        <p>Stock levels and product data</p>
                    </div>
                </div>
                <div class="card-content">
                    <div class="content-section">
                        <div class="section-title">
                            <i class="fas fa-info-circle"></i> Description
                        </div>
                        <p>Track your entire inventory with detailed information about each saree product.</p>
                    </div>
                    <div class="content-section">
                        <div class="section-title">
                            <i class="fas fa-list-ul"></i> Includes
                        </div>
                        <ul class="feature-list">
                            <li>Product IDs and detailed names</li>
                            <li>Current stock levels and pricing</li>
                            <li>Last stock update timestamps</li>
                            <li>Helps identify low stock items</li>
                        </ul>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?download=sarees<?php echo (!empty($startDate) ? '&start_date='.$startDate : ''); ?><?php echo (!empty($endDate) ? '&end_date='.$endDate : ''); ?><?php echo (!empty($orderBy) ? '&order_by='.$orderBy : ''); ?><?php echo (!empty($orderDir) ? '&order_dir='.$orderDir : ''); ?>" class="download-btn" onclick="showNotification('pdf-sarees')">
                        <i class="fas fa-file-pdf"></i> PDF Format
                    </a>
                    <a href="?download_csv=sarees<?php echo (!empty($startDate) ? '&start_date='.$startDate : ''); ?><?php echo (!empty($endDate) ? '&end_date='.$endDate : ''); ?><?php echo (!empty($orderBy) ? '&order_by='.$orderBy : ''); ?><?php echo (!empty($orderDir) ? '&order_dir='.$orderDir : ''); ?>" class="download-btn csv-btn" onclick="showNotification('csv-sarees')">
                        <i class="fas fa-file-csv"></i> CSV Format
                    </a>
                </div>
            </div>
        </div>
        
        <div class="feedback-section">
            <h3>Need A Custom Report?</h3>
            <p>If you need a specialized report with specific metrics or format, our development team can help.</p>
            <button class="feedback-btn" onclick="window.location.href='contact_dev.php'">
                <i class="fas fa-comment-alt"></i> Request Custom Report
            </button>
        </div>
    </div>
    
    <div id="toast" class="toast-notification">
        <i class="fas fa-check-circle"></i> <span id="toast-message">Your report is being generated</span>
    </div>
    
    <script>
        function showNotification(type) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            
            let message = 'Your report is being generated';
            let format = 'PDF';
            
            if (type.startsWith('csv')) {
                format = 'CSV';
            }
            
            if (type.includes('full')) {
                message = `Your complete ${format} report is being generated`;
            } else if (type.includes('users')) {
                message = `Your users ${format} report is being generated`;
            } else if (type.includes('orders')) {
                message = `Your orders ${format} report is being generated`;
            } else if (type.includes('sarees')) {
                message = `Your inventory ${format} report is being generated`;
            }
            
            toastMessage.textContent = message;
            toast.style.display = 'block';
            
            setTimeout(function() {
                toast.style.opacity = '0';
                setTimeout(function() {
                    toast.style.display = 'none';
                    toast.style.opacity = '1';
                }, 300);
            }, 3000);
        }
        
        // Date validation
        document.querySelector('.filter-form').addEventListener('submit', function(e) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                e.preventDefault();
                alert('Start date cannot be after end date. Please correct your date selection.');
            }
        });
    </script>
</body>
</html>