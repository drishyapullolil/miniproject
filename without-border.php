<?php
// Enhanced security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Include the database connection file
include 'db.php';

// Initialize filter and sort variables
$colorFilter = isset($_GET['color']) ? $_GET['color'] : 'all';
$sortOption = isset($_GET['sort']) ? $_GET['sort'] : 'price-asc';

// Modified base SQL query to join with subcategories table
$query = "SELECT s.* 
          FROM sarees s
          INNER JOIN subcategories sc ON s.subcategory_id = sc.id
          WHERE sc.subcategory_name = 'without Border'";

// Apply color filter if selected
if ($colorFilter != 'all') {
    $query .= " AND s.color = '$colorFilter'";
}

// Apply sorting
switch ($sortOption) {
    case 'price-desc':
        $query .= " ORDER BY s.price DESC";
        break;
    case 'newest':
        $query .= " ORDER BY s.created_at DESC";
        break;
    default:
        $query .= " ORDER BY s.price ASC";
}

// Execute the query
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$sarees = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sarees[] = $row;
    }
}

include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Exclusive Saree Collection at Yards of Grace">
    <title>YARDS OF GRACE - Saree Collection</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --primary-color: #4e034f;
            --primary-hover: #6c0c6d;
            --white: #fff;
            --gray-light: #eee;
        }

        .container {
            display: flex;
            padding: 20px;
            gap: 30px;
            margin-top: 20px;
        }

        .filter-sidebar {
            width: 280px;
            background-color: var(--primary-color);
            color: var(--white);
            padding: 20px;
            border-radius: 10px;
            position: sticky;
            top: 20px;
            height: fit-content;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .main-content {
            flex: 1;
        }

        .saree-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .saree-item {
            width: 100%;
        }

        .saree-item img {
            width: 100%;
            height: 450px;
            object-fit: cover;
        }

        .violet-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
        }

        .violet-btn:hover {
            background-color: var(--primary-hover);
        }

        .loading-spinner {
            display: none;
            margin: 20px auto;
            width: 40px;
            height: 40px;
            border: 3px solid var(--gray-light);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                padding: 10px;
            }

            .filter-sidebar {
                width: 100%;
            }

            .saree-list {
                grid-template-columns: 1fr;
            }
        }

        /* Navigation Menu Styling */
        .nav-menu {
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 10px 0;
        }

        .main-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .main-menu li {
            position: relative;
            padding: 0 20px;
        }

        .main-menu a {
            text-decoration: none;
            color: #333;
            font-size: 16px;
            font-weight: 500;
            padding: 15px 0;
            display: block;
            transition: color 0.3s ease;
        }

        .main-menu a:hover {
            color: #8d0f8f;
        }

        /* Dropdown Styling */
        .dropdown, .subdropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            z-index: 1000;
            border-radius: 5px;
            padding: 10px 0;
        }

        .main-menu li:hover > .dropdown,
        .dropdown li:hover > .subdropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown li, .subdropdown li {
            padding: 0;
            width: 100%;
        }

        .dropdown a, .subdropdown a {
            padding: 10px 20px;
            font-size: 14px;
            color: #666;
            border-left: 3px solid transparent;
        }

        .dropdown a:hover, .subdropdown a:hover {
            background-color: #f8f8f8;
            border-left: 3px solid #8d0f8f;
            color: #8d0f8f;
        }

        /* Subdropdown positioning */
        .subdropdown {
            left: 100%;
            top: 0;
        }

        /* Dropdown indicators */
        .dropdown li, .main-menu > li {
            position: relative;
        }

        .dropdown li:has(> .subdropdown)::after,
        .main-menu > li:has(> .dropdown)::after {
            content: '›';
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 18px;
        }

        .main-menu > li:has(> .dropdown)::after {
            content: '▾';
            font-size: 12px;
        }

        /* Remove the previous hover line effect and add new styles */
        .main-menu > li::after {
            display: none;
        }

        /* Add new hover effects */
        .main-menu > li > a {
            position: relative;
            transition: color 0.3s ease;
        }

        .main-menu > li > a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #8d0f8f;
            transition: width 0.3s ease;
        }

        /* Show line only for non-dropdown menu items */
        .main-menu > li:not(:has(> .dropdown)):hover > a::after {
            width: 100%;
        }

        /* Special hover effect for dropdown items */
        .main-menu > li:has(> .dropdown) > a {
            padding-right: 20px;
        }

        .main-menu > li:has(> .dropdown):hover > a {
            color: #8d0f8f;
        }

        /* Update dropdown arrow style */
        .main-menu > li:has(> .dropdown)::after {
            content: '▾';
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            color: #666;
            pointer-events: none;
        }

        .main-menu > li:has(> .dropdown):hover::after {
            color: #8d0f8f;
        }

        /* Active state */
        .main-menu a.active {
            color: #8d0f8f;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .main-menu {
                flex-direction: column;
                align-items: flex-start;
            }

            .main-menu li {
                width: 100%;
                padding: 0;
            }

            .dropdown, .subdropdown {
                position: static;
                box-shadow: none;
                opacity: 1;
                visibility: visible;
                transform: none;
                width: 100%;
                display: none;
            }

            .main-menu li:hover > .dropdown,
            .dropdown li:hover > .subdropdown {
                display: block;
            }

            .dropdown a, .subdropdown a {
                padding-left: 40px;
            }

            .subdropdown a {
                padding-left: 60px;
            }
        }

        /* Add these search bar styles */
        .header-left {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0 20px;
        }

        .search-container {
            display: flex;
            align-items: center;
            background: #fff;
            border: 2px solid #8d0f8f;
            border-radius: 25px;
            padding: 5px 15px;
            max-width: 400px;
            width: 100%;
            position: relative;
        }

        .search-bar {
            flex: 1;
            border: none;
            padding: 8px;
            font-size: 14px;
            outline: none;
            background: transparent;
            width: 100%;
        }

        .search-button {
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            color: #8d0f8f;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            transform: scale(1.1);
        }

        .search-container:focus-within {
            box-shadow: 0 0 8px rgba(141, 15, 143, 0.3);
        }

        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .suggestion-item {
            padding: 8px 15px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .suggestion-item:hover {
            background-color: #f5f5f5;
            color: #8d0f8f;
        }

        @media (max-width: 768px) {
            .header-main {
                flex-direction: column;
                padding: 10px;
            }

            .header-left {
                width: 100%;
                padding: 10px 0;
            }

            .search-container {
                max-width: 100%;
            }
        }

        /* Add these styles for the user dropdown */
        .user-dropdown {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .user-icon {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .dropdown-arrow {
            font-size: 10px;
            transition: transform 0.3s ease;
        }

        .user-dropdown:hover .dropdown-arrow {
            transform: rotate(180deg);
        }

        .user-dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 8px 0;
            z-index: 1000;
        }

        .user-dropdown:hover .user-dropdown-content {
            display: block;
        }

        .user-dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s ease, color 0.3s ease;
            font-size: 14px;
        }

        .user-dropdown-content a:hover {
            background-color: #f5f5f5;
            color: #8d0f8f;
        }

        .user-dropdown-content a:not(:last-child) {
            border-bottom: 1px solid #eee;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .user-dropdown-content {
                position: fixed;
                top: auto;
                right: 10px;
                width: calc(100% - 20px);
                max-width: 300px;
            }
        }
        </style>
</head>
<body>
    <div class="container">
    <div class="filter-sidebar">
    <h2>Filters</h2>
    <form method="GET" action="">
        <div class="filter-group">
            <label for="color-select">Color:</label>
            <select id="color-select" name="color" onchange="this.form.submit()" class="violet-btn">
                <option value="all">All Colors</option>
                <option value="red" <?php echo (isset($_GET['color']) && $_GET['color'] == 'red') ? 'selected' : ''; ?>>Red</option>
                <option value="blue" <?php echo (isset($_GET['color']) && $_GET['color'] == 'blue') ? 'selected' : ''; ?>>Blue</option>
                <option value="green" <?php echo (isset($_GET['color']) && $_GET['color'] == 'green') ? 'selected' : ''; ?>>Green</option>
                <option value="gold" <?php echo (isset($_GET['color']) && $_GET['color'] == 'gold') ? 'selected' : ''; ?>>Gold</option>
                <option value="purple" <?php echo (isset($_GET['color']) && $_GET['color'] == 'purple') ? 'selected' : ''; ?>>Purple</option>
                <option value="White" <?php echo (isset($_GET['color']) && $_GET['color'] == 'White') ? 'selected' : ''; ?>>White</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="sort-select">Sort By:</label>
            <select id="sort-select" name="sort" onchange="this.form.submit()" class="violet-btn">
                <option value="price-asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price-desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="newest" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'newest') ? 'selected' : ''; ?>>Newest First</option>
            </select>
        </div>
    </form>
</div>

        <div class="main-content">
            <div id="loading-spinner" class="loading-spinner"></div>
            <div class="saree-list" id="saree-list">
                <?php foreach ($sarees as $saree): ?>
                    <div class="saree-item">
                        <img src="<?php echo htmlspecialchars($saree['image']); ?>" 
                             alt="<?php echo htmlspecialchars($saree['name']); ?>" 
                             loading="lazy">
                        <h3><?php echo htmlspecialchars($saree['name']); ?></h3>
                        <p>Color: <?php echo htmlspecialchars($saree['color']); ?></p>
                        <p class="price">₹<?php echo number_format($saree['price'], 2); ?></p>
                        <a href="Traditional.php?id=<?php echo $saree['id']; ?>" class="violet-btn">View Details</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // JavaScript functions for filtering, sorting, and displaying sarees
        function formatPrice(price) {
            return '₹' + price.toLocaleString('en-IN');
        }

        function filterSarees() {
            const selectedColor = document.getElementById("color-select").value;
            const sortBy = document.getElementById("sort-select").value;
            const spinner = document.getElementById("loading-spinner");
            const sareeList = document.getElementById("saree-list");

            spinner.style.display = "block";
            sareeList.style.opacity = "0.5";

            // Fetch sarees from the server based on filters
            fetch(`filter_sarees.php?color=${selectedColor}&sort=${sortBy}`)
                .then(response => response.json())
                .then(data => {
                    displaySarees(data);
                    spinner.style.display = "none";
                    sareeList.style.opacity = "1";
                })
                .catch(error => {
                    console.error('Error fetching sarees:', error);
                    spinner.style.display = "none";
                    sareeList.style.opacity = "1";
                });
        }

        function displaySarees(sareesToShow) {
            const sareeList = document.getElementById("saree-list");
            sareeList.innerHTML = sareesToShow.map(saree => `
                <div class="saree-item">
                    <img src="${saree.image}" alt="${saree.name}" loading="lazy">
                    <h3>${saree.name}</h3>
                    <p>Color: ${saree.color}</p>
                    <p class="price">${formatPrice(saree.price)}</p>
                    <a href="Traditional.php?id=${saree.id}" class="violet-btn">View Details</a>
                </div>
            `).join('');
        }

        // Initial display
        filterSarees();
    </script>
</body>
</html>