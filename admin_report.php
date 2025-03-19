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

// Report generation code
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

    // --- USERS REPORT ---
    if ($_GET['download'] == 'full' || $_GET['download'] == 'users') {
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Users Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(30, 10, 'ID', 1);
        $pdf->Cell(50, 10, 'Username', 1);
        $pdf->Cell(60, 10, 'Email', 1);
        $pdf->Cell(30, 10, 'Role', 1);
        $pdf->Ln();

        $userQuery = "SELECT id, username, email, role FROM users";
        $userResult = $conn->query($userQuery);
        while ($row = $userResult->fetch_assoc()) {
            $pdf->Cell(30, 10, $row['id'], 1);
            $pdf->Cell(50, 10, $row['username'], 1);
            $pdf->Cell(60, 10, $row['email'], 1);
            $pdf->Cell(30, 10, ucfirst($row['role']), 1);
            $pdf->Ln();
        }
        $pdf->Ln(10); // Add space before the next section
    }

    // --- ORDERS REPORT ---
    if ($_GET['download'] == 'full' || $_GET['download'] == 'orders') {
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

        $orderQuery = "SELECT orders.id, users.username, orders.total_amount, orders.order_status, orders.payment_method 
                       FROM orders 
                       JOIN users ON orders.user_id = users.id";
        $orderResult = $conn->query($orderQuery);
        while ($row = $orderResult->fetch_assoc()) {
            $pdf->Cell(20, 10, $row['id'], 1);
            $pdf->Cell(40, 10, $row['username'], 1);
            $pdf->Cell(30, 10, "Rs:" . number_format($row['total_amount'], 2), 1);
            $pdf->Cell(30, 10, ucfirst($row['order_status']), 1);
            $pdf->Cell(60, 10, ucfirst($row['payment_method']), 1);
            $pdf->Ln();
        }
        $pdf->Ln(10); // Add space before the next section
    }

    // --- SAREE STOCK REPORT ---
    if ($_GET['download'] == 'full' || $_GET['download'] == 'sarees') {
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

        $sareeQuery = "SELECT id, name, price, stock, last_stock_update FROM sarees";
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Reports - Yards of Grace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #800080;
            --primary-light: #a000a0;
            --primary-dark: #600060;
            --primary-ultra-light: #f9e6f9;
            --white: #ffffff;
            --text-dark: #333333;
            --text-medium: #666666;
            --text-light: #888888;
            --border-light: #e6e0ed;
            --danger: #d81b60;
            --success: #4a9141;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background-color: #fdf6fd;
            padding: 0;
            color: var(--text-dark);
            min-height: 100vh;
        }

        .top-bar {
            background-color: var(--primary-dark);
            color: var(--white);
            text-align: center;
            padding: 10px;
            font-size: 14px;
        }

        .header-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            border-bottom: 1px solid var(--border-light);
            background-color: var(--white);
            box-shadow: 0 2px 15px rgba(128, 0, 128, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-center h1 {
            color: var(--primary);
            display: flex;
            align-items: center;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .header-center h1 img {
            margin-right: 15px;
            transition: transform 0.3s;
        }

        .header-center h1:hover img {
            transform: scale(1.05);
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .dashboard-btn {
            background-color: var(--primary-ultra-light);
            color: var(--primary);
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-weight: 500;
        }

        .dashboard-btn i {
            margin-right: 8px;
            font-size: 16px;
        }

        .dashboard-btn:hover {
            background-color: var(--primary);
            color: var(--white);
        }

        .logout-btn {
            background-color: var(--white);
            color: var(--danger);
            border: 1px solid var(--danger);
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .logout-btn i {
            margin-right: 8px;
            font-size: 16px;
        }

        .logout-btn:hover {
            background-color: var(--danger);
            color: var(--white);
        }

        .main-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-title {
            margin-bottom: 40px;
            text-align: center;
            position: relative;
        }

        .page-title h1 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 32px;
            position: relative;
            display: inline-block;
        }

        .page-title h1::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary);
            border-radius: 3px;
        }

        .page-title p {
            color: var(--text-medium);
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .reports-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .report-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(128, 0, 128, 0.1);
            padding: 30px;
            text-align: left;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .report-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background-color: var(--primary);
            opacity: 0.7;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(128, 0, 128, 0.2);
            border-color: var(--primary-light);
        }

        .card-header {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .card-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-ultra-light);
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            transition: all 0.3s;
        }

        .report-card:hover .card-icon {
            background: var(--primary);
        }

        .card-icon i {
            font-size: 24px;
            color: var(--primary);
            transition: all 0.3s;
        }

        .report-card:hover .card-icon i {
            color: var(--white);
        }

        .card-title h2 {
            font-size: 20px;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .card-title p {
            color: var(--text-light);
            font-size: 14px;
        }

        .card-content {
            flex-grow: 1;
            margin-bottom: 25px;
        }

        .content-section {
            margin-bottom: 15px;
        }

        .section-title {
            display: flex;
            align-items: center;
            font-size: 16px;
            color: var(--primary-dark);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .section-title i {
            margin-right: 8px;
            font-size: 14px;
        }

        .content-section p {
            color: var(--text-medium);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .feature-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .feature-list li {
            font-size: 14px;
            color: var(--text-medium);
            margin-bottom: 8px;
            padding-left: 25px;
            position: relative;
            line-height: 1.4;
        }

        .feature-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: bold;
        }

        .card-footer {
            margin-top: auto;
        }

        .download-btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 14px 0;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.3s;
            font-weight: 500;
            width: 100%;
            box-shadow: 0 4px 15px rgba(128, 0, 128, 0.2);
        }

        .download-btn i {
            margin-right: 10px;
        }

        .download-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(128, 0, 128, 0.3);
        }

        .full-report-card {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #800080, #a000a0);
        }

        .full-report-card::before {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .full-report-card .card-icon {
            background: rgba(255, 255, 255, 0.2);
        }

        .full-report-card:hover .card-icon {
            background: rgba(255, 255, 255, 0.3);
        }

        .full-report-card .card-icon i {
            color: var(--white);
        }

        .full-report-card .card-title h2,
        .full-report-card .section-title,
        .full-report-card .feature-list li:before {
            color: var(--white);
        }

        .full-report-card .card-title p,
        .full-report-card .content-section p,
        .full-report-card .feature-list li {
            color: rgba(255, 255, 255, 0.9);
        }

        .full-report-card .download-btn {
            background-color: var(--white);
            color: var(--primary);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .full-report-card .download-btn:hover {
            background-color: var(--primary-ultra-light);
        }

        .help-icon {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 18px;
            height: 18px;
            background-color: var(--primary-ultra-light);
            color: var(--primary);
            border-radius: 50%;
            font-size: 12px;
            margin-left: 8px;
            cursor: help;
            position: relative;
        }

        .help-text {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--text-dark);
            color: var(--white);
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            width: 180px;
            visibility: hidden;
            opacity: 0;
            transition: all 0.3s;
            pointer-events: none;
            z-index: 10;
            text-align: center;
            margin-bottom: 5px;
            font-weight: normal;
        }

        .help-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 5px;
            border-style: solid;
            border-color: var(--text-dark) transparent transparent transparent;
        }

        .help-icon:hover .help-text {
            visibility: visible;
            opacity: 1;
        }

        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #800080;
            color: var(--white);
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: none;
            z-index: 1000;
            animation: slideIn 0.3s forwards;
            font-weight: 500;
        }

        .toast-notification i {
            margin-right: 10px;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .feedback-section {
            text-align: center;
            margin-top: 50px;
            padding: 20px;
            background-color: var(--primary-ultra-light);
            border-radius: 10px;
        }

        .feedback-section h3 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        .feedback-section p {
            color: var(--text-medium);
            margin-bottom: 15px;
            font-size: 14px;
        }

        .feedback-btn {
            background-color: var(--white);
            color: #800080;
            border: 1px solid var(--primary);
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }

        .feedback-btn i {
            margin-right: 8px;
        }

        .feedback-btn:hover {
            background-color: #800080;
            color: var(--white);
        }

        @media (max-width: 768px) {
            .header-main {
                padding: 15px 20px;
                flex-direction: column;
            }
            
            .header-center {
                margin-bottom: 15px;
            }
            
            .reports-container {
                grid-template-columns: 1fr;
            }
            
            .card-header {
                flex-direction: column;
                text-align: center;
            }
            
            .card-icon {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .card-title {
                text-align: center;
            }
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
        
        <div class="reports-container">
            <div class="report-card full-report-card">
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
                        <p>This complete report combines all business data in a single PDF document, providing a comprehensive overview of your entire operation.</p>
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
                    <a href="?download=full" class="download-btn" onclick="showNotification('full')">
                        <i class="fas fa-download"></i> Download Complete Report
                    </a>
                </div>
            </div>
            
            <div class="report-card">
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
                    <a href="?download=users" class="download-btn" onclick="showNotification('users')">
                        <i class="fas fa-download"></i> Download Users Report
                    </a>
                </div>
            </div>
            
            <div class="report-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="card-title">
                        <h2>Orders Report</h2>
                        <p>Transaction and purchase data</p>
                    </div>
                </div>
                <div class="card-content">
                    <div class="content-section">
                        <div class="section-title">
                            <i class="fas fa-info-circle"></i> Description
                        </div>
                        <p>Access all order information and payment details for tracking sales performance.</p>
                    </div>
                    <div class="content-section">
                        <div class="section-title">
                            <i class="fas fa-list-ul"></i> Includes
                        </div>
                        <ul class="feature-list">
                            <li>Order IDs and customer information</li>
                            <li>Total amounts and payment methods</li>
                            <li>Current order processing status</li>
                            <li>Presented in clear tabular format</li>
                        </ul>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?download=orders" class="download-btn" onclick="showNotification('orders')">
                        <i class="fas fa-download"></i> Download Orders Report
                    </a>
                </div>
            </div>
            
            <div class="report-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div class="card-title">
                        <h2>Saree Stock Report</h2>
                        <p>Inventory and product details</p>
                    </div>
                </div>
                <div class="card-content">
                    <div class="content-section">
                        <div class="section-title">
                            <i class="fas fa-info-circle"></i> Description
                        </div>
                        <p>Review current inventory levels and product information to manage your stock effectively.</p>
                    </div>
                    <div class="content-section">
                        <div class="section-title">
                            <i class="fas fa-list-ul"></i> Includes
                        </div>
                        <ul class="feature-list">
                            <li>Product IDs and saree names</li>
                            <li>Current prices and available stock</li>
                            <li>Last inventory update timestamps</li>
                            <li>Clear tabular presentation format</li>
                        </ul>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?download=sarees" class="download-btn" onclick="showNotification('sarees')">
                        <i class="fas fa-download"></i> Download Saree Stock Report
                    </a>
                </div>
            </div>
        </div>
        
        <div class="feedback-section">
            <h3>Need a customized report?</h3>
            <p>We can create custom reports tailored to your specific business needs.</p>
            <button class="feedback-btn" onclick="alert('Feature coming soon! Please contact the development team for custom reports.')">
                <i class="fas fa-comment-alt"></i> Request Custom Report
            </button>
        </div>
    </div>
    
    <div class="toast-notification" id="notification">
        <i class="fas fa-spinner fa-spin"></i> Generating report... Please wait.
    </div>

    <script>
        function showNotification(reportType) {
            const notification = document.getElementById('notification');
            let message = '';
            
            switch(reportType) {
                case 'full':
                    message = '<i class="fas fa-spinner fa-spin"></i> Generating full business report... Please wait.';
                    break;
                case 'users':
                    message = '<i class="fas fa-spinner fa-spin"></i> Generating users report... Please wait.';
                    break;
                case 'orders':
                    message = '<i class="fas fa-spinner fa-spin"></i> Generating orders report... Please wait.';
                    break;
                case 'sarees':
                    message = '<i class="fas fa-spinner fa-spin"></i> Generating saree stock report... Please wait.';
                    break;
                default:
                    message = '<i class="fas fa-spinner fa-spin"></i> Generating report... Please wait.';
            }
            
            notification.innerHTML = message;
            notification.style.display = 'block';
            
            setTimeout(function() {
                notification.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>