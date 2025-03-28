<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// Include the database connection file
require_once 'db.php';

// Get search query from URL
$search_query = isset($_GET['q']) ? $_GET['q'] : '';

// Initialize filter and sort variables
$colorFilter = isset($_GET['color']) ? $_GET['color'] : 'all';
$sortOption = isset($_GET['sort']) ? $_GET['sort'] : 'price-asc';

// Initialize arrays to store results
$products = [];
$total_results = 0;

if (!empty($search_query)) {
    // Prepare search query
    $search = '%' . $conn->real_escape_string($search_query) . '%';
    
    // Base query for sarees
    $saree_query = "SELECT 
                s.id, 
                s.name, 
                s.price, 
                s.image,
                s.description,
                s.color,
                s.subcategory_id,
                'saree' as type,
                sub.subcategory_name,
                cat.category_name
              FROM sarees s 
              JOIN subcategories sub ON s.subcategory_id = sub.id
              JOIN categories cat ON sub.category_id = cat.id
              WHERE s.name LIKE ? 
              OR s.description LIKE ? 
              OR s.color LIKE ?";
              
    // Apply sorting
    switch ($sortOption) {
        case 'price-desc':
            $saree_query .= " ORDER BY s.price DESC";
            break;
        case 'newest':
            $saree_query .= " ORDER BY s.id DESC";
            break;
        default:
            $saree_query .= " ORDER BY s.price ASC";
    }
    
    $stmt = $conn->prepare($saree_query);
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    // Apply color filter if selected
    if ($colorFilter != 'all') {
        $products = array_filter($products, function($product) use ($colorFilter) {
            return strtolower($product['color']) == strtolower($colorFilter);
        });
    }
    
    // Get total count
    $total_results = count($products);
}

// Include the header
require_once 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="report.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Yards of Grace</title>
    <style>
        :root {
            --primary-color: #4e034f;
            --primary-hover: #6c0c6d;
            --white: #fff;
            --gray-light: #eee;
            --container-width: 1200px;
            --border-radius: 8px;
            --shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            --text-color: #333;
            --text-light: #666;
        }

        .container {
            display: flex;
            padding: 20px;
            gap: 30px;
            margin-top: 20px;
            max-width: var(--container-width);
            margin-left: auto;
            margin-right: auto;
        }

        .search-header {
            max-width: var(--container-width);
            margin: 20px auto;
            padding: 0 20px;
            text-align: center;
        }

        .search-header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 2rem;
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

        .filter-sidebar h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .main-content {
            flex: 1;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease;
            background: white;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .selected-product {
            border: 2px solid #4e034f;
            box-shadow: 0 0 10px rgba(78, 3, 79, 0.3);
        }

        .product-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .selected-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #4e034f;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        .product-info {
            padding: 15px;
        }

        .product-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .product-category {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .product-color {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .product-price {
            font-size: 18px;
            color: #4e034f;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .view-details-btn {
            background: #4e034f;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            text-align: center;
            flex: 1;
            transition: background 0.3s ease;
        }

        .view-details-btn:hover {
            background: #6c0c6d;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
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

        /* No Results */
        .no-results, .no-query {
            text-align: center;
            padding: 60px 20px;
            background-color: #f9f5f9;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .no-results-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.7;
        }

        .no-results h2, .no-query h2 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .no-results p {
            color: var(--text-light);
            margin-bottom: 10px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .continue-shopping-btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            margin-top: 20px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .continue-shopping-btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .search-page-search {
            max-width: 500px;
            margin: 20px auto;
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
            display: inline-block;
            width: 100%;
        }

        .violet-btn:hover {
            background-color: var(--primary-hover);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 40px;
        }

        .pagination-btn {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            padding: 8px 15px;
            cursor: pointer;
            margin: 0 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .pagination-btn:not([disabled]):hover {
            background-color: var(--primary-color);
            color: white;
        }

        .pagination-btn[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-current {
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                padding: 10px;
            }
            
            .filter-sidebar {
                width: 100%;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .product-image {
                height: 200px;
            }
        }

        @media (max-width: 480px) {
            .search-header h1 {
                font-size: 1.5rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            }
        }
    </style>
</head>
<body>

<!-- Search Header Section -->
<div class="search-header">
    <h1>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h1>
    <p><?php echo $total_results; ?> products found</p>
</div>

<!-- Main Container -->
<div class="container">
    <!-- Filter Sidebar -->
    <div class="filter-sidebar">
        <h2>Filters</h2>
        <form id="filter-form" method="GET" action="">
            <!-- Keep the search query -->
            <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
            
            <div class="filter-group">
                <label for="color-select">Color:</label>
                <select id="color-select" name="color" class="violet-btn">
                    <option value="all">All Colors</option>
                    <option value="red" <?php echo ($colorFilter == 'red') ? 'selected' : ''; ?>>Red</option>
                    <option value="blue" <?php echo ($colorFilter == 'blue') ? 'selected' : ''; ?>>Blue</option>
                    <option value="green" <?php echo ($colorFilter == 'green') ? 'selected' : ''; ?>>Green</option>
                    <option value="gold" <?php echo ($colorFilter == 'gold') ? 'selected' : ''; ?>>Gold</option>
                    <option value="purple" <?php echo ($colorFilter == 'purple') ? 'selected' : ''; ?>>Purple</option>
                    <option value="White" <?php echo ($colorFilter == 'White') ? 'selected' : ''; ?>>White</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sort-select">Sort By:</label>
                <select id="sort-select" name="sort" class="violet-btn">
                    <option value="price-asc" <?php echo ($sortOption == 'price-asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price-desc" <?php echo ($sortOption == 'price-desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="newest" <?php echo ($sortOption == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                </select>
            </div>
            
            <div class="filter-group" style="text-align: center; margin-top: 20px;">
                <button type="button" class="violet-btn" onclick="clearFilters()">Clear Filters</button>
            </div>
        </form>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div id="loading-spinner" class="loading-spinner"></div>
        
        <?php if (empty($products) && !empty($search_query)): ?>
            <div class="no-results">
                <img src="no-results.png" alt="No Results" class="no-results-icon">
                <h2>No products found</h2>
                <p>We couldn't find any products matching "<?php echo htmlspecialchars($search_query); ?>".</p>
                <p>Try checking your spelling or using more general terms.</p>
                <a href="home.php" class="continue-shopping-btn">Continue Shopping</a>
            </div>
        <?php elseif (empty($search_query)): ?>
            <div class="no-query">
                <h2>Please enter a search term</h2>
                <div class="global-search search-page-search">
                    <form action="search_results.php" method="GET">
                        <div class="search-input-group">
                            <div class="search-container">
                                <input type="text" 
                                       name="q" 
                                       placeholder="Search products..." 
                                       class="search-input" 
                                       autocomplete="off"
                                       required 
                                       minlength="2">
                                <button type="submit" class="search-button">
                                    <span class="search-icon">🔍</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <a href="home.php" class="continue-shopping-btn">Browse Categories</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card <?php echo ($product['id'] == $selected_item_id) ? 'selected-product' : ''; ?>">
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php if ($product['id'] == $selected_item_id): ?>
                                <div class="selected-badge">Selected</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-category">
                                <?php echo htmlspecialchars($product['category_name']); ?> > 
                                <?php echo htmlspecialchars($product['subcategory_name']); ?>
                            </div>
                            <div class="product-color">
                                Color: <?php echo htmlspecialchars($product['color']); ?>
                            </div>
                            <div class="product-price">
                                ₹<?php echo number_format($product['price'], 2); ?>
                            </div>
                            <div class="product-actions">
                                <a href="Traditional.php?id=<?php echo $product['id']; ?>" 
                                   class="view-details-btn">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <button class="pagination-btn" disabled>Previous</button>
                <span class="pagination-current">Page 1</span>
                <button class="pagination-btn" disabled>Next</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Event listeners for filter and sort
    document.getElementById('color-select').addEventListener('change', function() {
        document.getElementById('filter-form').submit();
    });

    document.getElementById('sort-select').addEventListener('change', function() {
        document.getElementById('filter-form').submit();
    });

    function clearFilters() {
        document.getElementById('color-select').value = 'all';
        document.getElementById('sort-select').value = 'price-asc';
        document.getElementById('filter-form').submit();
    }
</script>

<?php include 'footer.php'; ?>
</body>
</html>