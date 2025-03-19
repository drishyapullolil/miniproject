<?php
// Include database connection
require_once 'config/db.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Include TCPDF library
require_once('tcpdf/tcpdf.php');

// Function to generate reports
function generateReport($conn, $reportType, $startDate = null, $endDate = null, $additionalParams = []) {
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Yards of Grace');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Admin Report');
    $pdf->SetSubject('Yards of Grace Report');
    
    // Set default header data
    $pdf->SetHeaderData('logo.png', 30, 'Yards of Grace', 'Admin Report: ' . ucfirst($reportType));
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array('helvetica', '', 10));
    $pdf->setFooterFont(Array('helvetica', '', 8));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont('courier');
    
    // Set margins
    $pdf->SetMargins(15, 27, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Date range info
    $dateInfo = '';
    if ($startDate && $endDate) {
        $dateInfo = "Date Range: " . date('Y-m-d', strtotime($startDate)) . " to " . date('Y-m-d', strtotime($endDate));
    } else if ($startDate) {
        $dateInfo = "From: " . date('Y-m-d', strtotime($startDate));
    } else if ($endDate) {
        $dateInfo = "Until: " . date('Y-m-d', strtotime($endDate));
    } else {
        $dateInfo = "All Time";
    }
    
    $pdf->Cell(0, 10, $dateInfo, 0, 1, 'C');
    $pdf->Ln(5);
    
    // Generate different reports based on type
    switch ($reportType) {
        case 'sales':
            generateSalesReport($pdf, $conn, $startDate, $endDate);
            break;
        case 'inventory':
            generateInventoryReport($pdf, $conn, $additionalParams);
            break;
        case 'orders':
            generateOrdersReport($pdf, $conn, $startDate, $endDate, $additionalParams);
            break;
        case 'customers':
            generateCustomersReport($pdf, $conn, $startDate, $endDate);
            break;
        case 'products':
            generateProductsReport($pdf, $conn, $additionalParams);
            break;
        default:
            $pdf->Cell(0, 10, 'Invalid report type', 0, 1, 'C');
            break;
    }
    
    // Close and output PDF document
    return $pdf->Output('admin_report_' . $reportType . '.pdf', 'I');
}

// Generate Sales Report
function generateSalesReport($pdf, $conn, $startDate, $endDate) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Sales Report', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Date condition for queries
    $dateCondition = "";
    if ($startDate && $endDate) {
        $dateCondition = " WHERE o.created_at BETWEEN '$startDate' AND '$endDate'";
    } else if ($startDate) {
        $dateCondition = " WHERE o.created_at >= '$startDate'";
    } else if ($endDate) {
        $dateCondition = " WHERE o.created_at <= '$endDate'";
    }
    
    // Total Sales Revenue
    $query = "SELECT SUM(total_amount) as total_revenue, 
                     COUNT(id) as total_orders,
                     AVG(total_amount) as average_order
              FROM orders o" . $dateCondition;
    $result = $conn->query($query);
    $summary = $result->fetch_assoc();
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Sales Summary', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 8, 'Total Revenue:', 0, 0);
    $pdf->Cell(0, 8, '₹' . number_format($summary['total_revenue'], 2), 0, 1);
    
    $pdf->Cell(60, 8, 'Total Orders:', 0, 0);
    $pdf->Cell(0, 8, $summary['total_orders'], 0, 1);
    
    $pdf->Cell(60, 8, 'Average Order Value:', 0, 0);
    $pdf->Cell(0, 8, '₹' . number_format($summary['average_order'], 2), 0, 1);
    
    $pdf->Ln(5);
    
    // Sales by Category
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Sales by Category', 0, 1, 'L');
    
    $query = "SELECT c.category_name, 
                     SUM(od.price * od.quantity) as category_sales,
                     COUNT(DISTINCT o.id) as order_count
              FROM order_details od
              JOIN orders o ON od.order_id = o.id
              JOIN sarees s ON od.saree_id = s.id
              JOIN categories c ON s.category_id = c.id
              $dateCondition
              GROUP BY c.id
              ORDER BY category_sales DESC";
    
    $result = $conn->query($query);
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(60, 8, 'Category', 1, 0, 'L');
    $pdf->Cell(40, 8, 'Total Sales', 1, 0, 'R');
    $pdf->Cell(40, 8, 'Orders', 1, 0, 'R');
    $pdf->Cell(40, 8, 'Percentage', 1, 1, 'R');
    
    $pdf->SetFont('helvetica', '', 10);
    $totalCategorySales = 0;
    $categoryData = [];
    
    while ($row = $result->fetch_assoc()) {
        $categoryData[] = $row;
        $totalCategorySales += $row['category_sales'];
    }
    
    foreach ($categoryData as $row) {
        $percentage = ($row['category_sales'] / $totalCategorySales) * 100;
        
        $pdf->Cell(60, 8, $row['category_name'], 1, 0, 'L');
        $pdf->Cell(40, 8, '₹' . number_format($row['category_sales'], 2), 1, 0, 'R');
        $pdf->Cell(40, 8, $row['order_count'], 1, 0, 'R');
        $pdf->Cell(40, 8, number_format($percentage, 2) . '%', 1, 1, 'R');
    }
    
    $pdf->Ln(5);
    
    // Monthly Sales Trend
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Monthly Sales Trend', 0, 1, 'L');
    
    $query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                DATE_FORMAT(created_at, '%b %Y') as month_name,
                SUM(total_amount) as monthly_sales,
                COUNT(id) as monthly_orders
             FROM orders
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month DESC
             LIMIT 12";
    
    $result = $conn->query($query);
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(40, 8, 'Month', 1, 0, 'L');
    $pdf->Cell(50, 8, 'Revenue', 1, 0, 'R');
    $pdf->Cell(50, 8, 'Orders', 1, 0, 'R');
    $pdf->Cell(50, 8, 'Avg Order Value', 1, 1, 'R');
    
    $pdf->SetFont('helvetica', '', 10);
    
    while ($row = $result->fetch_assoc()) {
        $avgOrderValue = $row['monthly_sales'] / $row['monthly_orders'];
        
        $pdf->Cell(40, 8, $row['month_name'], 1, 0, 'L');
        $pdf->Cell(50, 8, '₹' . number_format($row['monthly_sales'], 2), 1, 0, 'R');
        $pdf->Cell(50, 8, $row['monthly_orders'], 1, 0, 'R');
        $pdf->Cell(50, 8, '₹' . number_format($avgOrderValue, 2), 1, 1, 'R');
    }
}

// Generate Inventory Report
function generateInventoryReport($pdf, $conn, $params) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Inventory Report', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Inventory Summary
    $query = "SELECT 
                COUNT(*) as total_products, 
                SUM(stock) as total_stock,
                SUM(stock * price) as inventory_value,
                AVG(stock) as avg_stock_per_product
             FROM sarees";
    
    $result = $conn->query($query);
    $summary = $result->fetch_assoc();
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Inventory Summary', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(80, 8, 'Total Products:', 0, 0);
    $pdf->Cell(0, 8, $summary['total_products'], 0, 1);
    
    $pdf->Cell(80, 8, 'Total Items in Stock:', 0, 0);
    $pdf->Cell(0, 8, $summary['total_stock'], 0, 1);
    
    $pdf->Cell(80, 8, 'Average Stock per Product:', 0, 0);
    $pdf->Cell(0, 8, number_format($summary['avg_stock_per_product'], 2), 0, 1);
    
    $pdf->Cell(80, 8, 'Total Inventory Value:', 0, 0);
    $pdf->Cell(0, 8, '₹' . number_format($summary['inventory_value'], 2), 0, 1);
    
    $pdf->Ln(5);
    
    // Low Stock Items
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Low Stock Items (Stock ≤ 5)', 0, 1, 'L');
    
    $query = "SELECT 
                s.id, 
                s.name, 
                c.category_name,
                sc.subcategory_name,
                s.stock,
                s.price,
                s.stock * s.price as value,
                s.last_stock_update
             FROM sarees s
             JOIN categories c ON s.category_id = c.id
             LEFT JOIN subcategories sc ON s.subcategory_id = sc.id
             WHERE s.stock <= 5
             ORDER BY s.stock ASC";
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(10, 8, 'ID', 1, 0, 'C');
        $pdf->Cell(50, 8, 'Product Name', 1, 0, 'L');
        $pdf->Cell(35, 8, 'Category', 1, 0, 'L');
        $pdf->Cell(20, 8, 'Stock', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Price', 1, 0, 'R');
        $pdf->Cell(35, 8, 'Last Updated', 1, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 10);
        
        while ($row = $result->fetch_assoc()) {
            $category = $row['category_name'];
            if ($row['subcategory_name']) {
                $category .= ' > ' . $row['subcategory_name'];
            }
            
            $pdf->Cell(10, 8, $row['id'], 1, 0, 'C');
            $pdf->Cell(50, 8, $row['name'], 1, 0, 'L');
            $pdf->Cell(35, 8, $category, 1, 0, 'L');
            $pdf->Cell(20, 8, $row['stock'], 1, 0, 'C');
            $pdf->Cell(30, 8, '₹' . number_format($row['price'], 2), 1, 0, 'R');
            $pdf->Cell(35, 8, date('Y-m-d', strtotime($row['last_stock_update'])), 1, 1, 'C');
        }
    } else {
        $pdf->Cell(0, 10, 'No low stock items found.', 0, 1, 'L');
    }
    
    $pdf->Ln(5);
    
    // Stock by Category
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Inventory by Category', 0, 1, 'L');
    
    $query = "SELECT 
                c.category_name,
                COUNT(s.id) as product_count,
                SUM(s.stock) as total_stock,
                SUM(s.stock * s.price) as category_value
             FROM categories c
             JOIN sarees s ON c.id = s.category_id
             GROUP BY c.id
             ORDER BY total_stock DESC";
    
    $result = $conn->query($query);
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(60, 8, 'Category', 1, 0, 'L');
    $pdf->Cell(30, 8, 'Products', 1, 0, 'C');
    $pdf->Cell(30, 8, 'Stock', 1, 0, 'C');
    $pdf->Cell(60, 8, 'Inventory Value', 1, 1, 'R');
    
    $pdf->SetFont('helvetica', '', 10);
    
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(60, 8, $row['category_name'], 1, 0, 'L');
        $pdf->Cell(30, 8, $row['product_count'], 1, 0, 'C');
        $pdf->Cell(30, 8, $row['total_stock'], 1, 0, 'C');
        $pdf->Cell(60, 8, '₹' . number_format($row['category_value'], 2), 1, 1, 'R');
    }
    
    // Recent Stock Updates
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Recent Stock Updates', 0, 1, 'L');
    
    $query = "SELECT 
                ssh.id,
                s.name as product_name,
                ssh.stock_added,
                ssh.previous_stock,
                ssh.new_stock,
                ssh.updated_at,
                u.username as updated_by
             FROM saree_stock_history ssh
             JOIN sarees s ON ssh.saree_id = s.id
             JOIN users u ON ssh.updated_by = u.id
             ORDER BY ssh.updated_at DESC
             LIMIT 20";
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 8, 'Product', 1, 0, 'L');
        $pdf->Cell(25, 8, 'Added', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Previous', 1, 0, 'C');
        $pdf->Cell(25, 8, 'New Stock', 1, 0, 'C');
        $pdf->Cell(35, 8, 'Date', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Updated By', 1, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 10);
        
        while ($row = $result->fetch_assoc()) {
            $pdf->Cell(60, 8, $row['product_name'], 1, 0, 'L');
            $pdf->Cell(25, 8, $row['stock_added'], 1, 0, 'C');
            $pdf->Cell(25, 8, $row['previous_stock'], 1, 0, 'C');
            $pdf->Cell(25, 8, $row['new_stock'], 1, 0, 'C');
            $pdf->Cell(35, 8, date('Y-m-d H:i', strtotime($row['updated_at'])), 1, 0, 'C');
            $pdf->Cell(30, 8, $row['updated_by'], 1, 1, 'C');
        }
    } else {
        $pdf->Cell(0, 10, 'No stock updates found.', 0, 1, 'L');
    }
}

// Generate Orders Report
function generateOrdersReport($pdf, $conn, $startDate, $endDate, $params) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Orders Report', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Date condition for queries
    $dateCondition = "";
    if ($startDate && $endDate) {
        $dateCondition = " WHERE o.created_at BETWEEN '$startDate' AND '$endDate'";
    } else if ($startDate) {
        $dateCondition = " WHERE o.created_at >= '$startDate'";
    } else if ($endDate) {
        $dateCondition = " WHERE o.created_at <= '$endDate'";
    }
    
    // Orders Status Summary
    $query = "SELECT 
                order_status,
                COUNT(*) as count,
                SUM(total_amount) as total
             FROM orders o
             $dateCondition
             GROUP BY order_status";
    
    $result = $conn->query($query);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Orders Status Summary', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(40, 8, 'Status', 1, 0, 'L');
    $pdf->Cell(30, 8, 'Count', 1, 0, 'C');
    $pdf->Cell(60, 8, 'Total Value', 1, 0, 'R');
    $pdf->Cell(40, 8, 'Percentage', 1, 1, 'R');
    
    $pdf->SetFont('helvetica', '', 10);
    
    $totalOrders = 0;
    $statusData = [];
    
    while ($row = $result->fetch_assoc()) {
        $statusData[] = $row;
        $totalOrders += $row['count'];
    }
    
    foreach ($statusData as $row) {
        $percentage = ($row['count'] / $totalOrders) * 100;
        
        $pdf->Cell(40, 8, ucfirst($row['order_status']), 1, 0, 'L');
        $pdf->Cell(30, 8, $row['count'], 1, 0, 'C');
        $pdf->Cell(60, 8, '₹' . number_format($row['total'], 2), 1, 0, 'R');
        $pdf->Cell(40, 8, number_format($percentage, 2) . '%', 1, 1, 'R');
    }
    
    $pdf->Ln(5);
    
    // Payment Method Summary
    $query = "SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(total_amount) as total
             FROM orders o
             $dateCondition
             GROUP BY payment_method";
    
    $result = $conn->query($query);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Payment Method Summary', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(60, 8, 'Payment Method', 1, 0, 'L');
    $pdf->Cell(30, 8, 'Count', 1, 0, 'C');
    $pdf->Cell(60, 8, 'Total Value', 1, 0, 'R');
    $pdf->Cell(40, 8, 'Percentage', 1, 1, 'R');
    
    $pdf->SetFont('helvetica', '', 10);
    
    $totalPayments = 0;
    $paymentData = [];
    
    while ($row = $result->fetch_assoc()) {
        $paymentData[] = $row;
        $totalPayments += $row['count'];
    }
    
    foreach ($paymentData as $row) {
        $percentage = ($row['count'] / $totalPayments) * 100;
        
        $pdf->Cell(60, 8, $row['payment_method'], 1, 0, 'L');
        $pdf->Cell(30, 8, $row['count'], 1, 0, 'C');
        $pdf->Cell(60, 8, '₹' . number_format($row['total'], 2), 1, 0, 'R');
        $pdf->Cell(40, 8, number_format($percentage, 2) . '%', 1, 1, 'R');
    }
    
    $pdf->Ln(5);
    
    // Recent Orders
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Recent Orders', 0, 1, 'L');
    
    $query = "SELECT 
                o.id,
                o.user_id,
                o.name,
                u.username,
                o.total_amount,
                o.order_status,
                o.payment_method,
                o.payment_status,
                o.created_at
             FROM orders o
             JOIN users u ON o.user_id = u.id
             $dateCondition
             ORDER BY o.created_at DESC
             LIMIT 15";
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(15, 8, 'ID', 1, 0, 'C');
        $pdf->Cell(35, 8, 'Customer', 1, 0, 'L');
        $pdf->Cell(25, 8, 'Amount', 1, 0, 'R');
        $pdf->Cell(25, 8, 'Status', 1, 0, 'C');
        $pdf->Cell(35, 8, 'Payment Method', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Pay Status', 1, 0, 'C');
        $pdf->Cell(35, 8, 'Date', 1, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 9);
        
        while ($row = $result->fetch_assoc()) {
            $pdf->Cell(15, 8, $row['id'], 1, 0, 'C');
            $pdf->Cell(35, 8, $row['name'], 1, 0, 'L');
            $pdf->Cell(25, 8, '₹' . number_format($row['total_amount'], 2), 1, 0, 'R');
            $pdf->Cell(25, 8, ucfirst($row['order_status']), 1, 0, 'C');
            $pdf->Cell(35, 8, $row['payment_method'], 1, 0, 'C');
            $pdf->Cell(25, 8, ucfirst($row['payment_status']), 1, 0, 'C');
            $pdf->Cell(35, 8, date('Y-m-d H:i', strtotime($row['created_at'])), 1, 1, 'C');
        }
    } else {
        $pdf->Cell(0, 10, 'No orders found.', 0, 1, 'L');
    }
}

// Generate Customers Report
function generateCustomersReport($pdf, $conn, $startDate, $endDate) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Customers Report', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Date condition for queries
    $dateCondition = "";
    if ($startDate && $endDate) {
        $dateCondition = " WHERE u.created_at BETWEEN '$startDate' AND '$endDate'";
    } else if ($startDate) {
        $dateCondition = " WHERE u.created_at >= '$startDate'";
    } else if ($endDate) {
        $dateCondition = " WHERE u.created_at <= '$endDate'";
    }
    
    // Customer Summary
    $query = "SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_count,
                COUNT(CASE WHEN role = 'user' THEN 1 END) as user_count,
                AVG(DATEDIFF(NOW(), created_at)) as avg_account_age
             FROM users u" . $dateCondition;
    
    $result = $conn->query($query);
    $summary = $result->fetch_assoc();
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Customer Summary', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 8, 'Total Customers:', 0, 0);
    $pdf->Cell(0, 8, $summary['total_customers'], 0, 1);
    
    $pdf->Cell(60, 8, 'Regular Users:', 0, 0);
    $pdf->Cell(0, 8, $summary['user_count'], 0, 1);
    
    $pdf->Cell(60, 8, 'Admin Users:', 0, 0);
    $pdf->Cell(0, 8, $summary['admin_count'], 0, 1);
    
    $pdf->Cell(60, 8, 'Avg. Account Age:', 0, 0);
    $pdf->Cell(0, 8, round($summary['avg_account_age']) . ' days', 0, 1);
    
    $pdf->Ln(5);
    
    // Top Customers by Orders
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Top Customers by Order Volume', 0, 1, 'L');
    
    $query = "SELECT 
                u.id,
                u.username,
                u.email,
                COUNT(o.id) as order_count,
                SUM(o.total_amount) as total_spent,
                MAX(o.created_at) as last_order_date
             FROM users u
             LEFT JOIN orders o ON u.id = o.user_id
             WHERE u.role = 'user'
             GROUP BY u.id
             HAVING order_count > 0
             ORDER BY order_count DESC, total_spent DESC
             LIMIT 10";
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 8, 'Customer', 1, 0, 'L');
        $pdf->Cell(60, 8, 'Email', 1, 0, 'L');
        $pdf->Cell(25, 8, 'Orders', 1, 0, 'C');
        $pdf->Cell(40, 8, 'Total Spent', 1, 0, 'R');
        $pdf->Cell(35, 8, 'Last Order', 1, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 10);
        
        while ($row = $result->fetch_assoc()) {
            $pdf->Cell(40, 8, $row['username'], 1, 0, 'L');
            $pdf->Cell(60, 8, $row['email'], 1, 0, 'L');
            $pdf->Cell(25, 8, $row['order_count'], 1, 0, 'C');
            $pdf->Cell(40, 8, '₹' . number_format($row['total_spent'], 2), 1, 0, 'R');
            $pdf->Cell(35, 8, date('Y-m-d', strtotime($row['last_order_date'])), 1, 1, 'C');
        }
    } else {
        $pdf->Cell(0, 10, 'No customer order data found.', 0, 1, 'L');
    }
    
    $pdf->Ln(5);
    
    // New Customers Over Time
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'New Customer Registrations