
<?php
// Enhanced security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db.php';

// Fetch category or subcategory ID from the URL
$categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
$subcategoryId = isset($_GET['subcategory_id']) ? intval($_GET['subcategory_id']) : null;

// Validate input
if (!$categoryId && !$subcategoryId) {
    // Redirect to a default page or display a message
    header("Location: home.php"); // Redirect to the homepage or a categories listing page
    exit();
}

// Initialize filter and sort variables
$colorFilter = isset($_GET['color']) ? $_GET['color'] : 'all';
$sortOption = isset($_GET['sort']) ? $_GET['sort'] : 'price-asc';

// Base query
$query = "SELECT 
            s.id, 
            s.name,
            s.price, 
            s.stock, 
            s.color, 
            s.image, 
            c.category_name,
            IFNULL(sc.subcategory_name, 'N/A') AS subcategory_name,
            COALESCE(ps.material, 'Not specified') AS material,
            COALESCE(ps.style, 'Not specified') AS style
          FROM sarees s 
          LEFT JOIN categories c ON s.category_id = c.id
          LEFT JOIN subcategories sc ON s.subcategory_id = sc.id
          LEFT JOIN product_specifications ps ON s.id = ps.saree_id
          WHERE ";

// Add category or subcategory condition
if ($categoryId) {
    $query .= "s.category_id = ?";
    $param = $categoryId;
} else {
    $query .= "s.subcategory_id = ?";
    $param = $subcategoryId;
}

// Apply color filter if selected
if ($colorFilter != 'all') {
    $query .= " AND s.color = ?";
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

// Prepare and execute the query
$stmt = $conn->prepare($query);

if ($colorFilter != 'all') {
    $stmt->bind_param("is", $param, $colorFilter);
} else {
    $stmt->bind_param("i", $param);
}

$stmt->execute();
$result = $stmt->get_result();
$sarees = $result->fetch_all(MYSQLI_ASSOC);

// Set the page title
if ($categoryId) {
    $pageTitle = !empty($sarees) ? $sarees[0]['category_name'] : "Category Products";
} else {
    $pageTitle = !empty($sarees) ? $sarees[0]['subcategory_name'] : "Subcategory Products";
}

if (isset($_GET['q'])) {
    $searchTerm = '%' . $conn->real_escape_string($_GET['q']) . '%';
    $clickedId = isset($_GET['clicked_id']) ? (int)$_GET['clicked_id'] : 0;
    
    $query = "SELECT s.*, sub.subcategory_name 
              FROM sarees s 
              JOIN subcategories sub ON s.subcategory_id = sub.id 
              WHERE s.subcategory_id = ? 
              ORDER BY 
                CASE 
                    WHEN s.id = ? THEN 1  -- Clicked product first
                    WHEN s.name LIKE ? THEN 2  -- Then exact matches
                    WHEN s.description LIKE ? THEN 3  -- Then description matches
                    WHEN s.color LIKE ? THEN 4  -- Then color matches
                    ELSE 5
                END,
                s.id DESC";
                
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisss", $subcategoryId, $clickedId, $searchTerm, $searchTerm, $searchTerm);
} else {
    $query = "SELECT s.*, sub.subcategory_name 
              FROM sarees s 
              JOIN subcategories sub ON s.subcategory_id = sub.id 
              WHERE s.subcategory_id = ? 
              ORDER BY s.id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subcategoryId);
}

$stmt->execute();
$result = $stmt->get_result();

// Get the subcategory name for the heading
$subcategoryName = '';
if ($result->num_rows > 0) {
    $firstRow = $result->fetch_assoc();
    $subcategoryName = $firstRow['subcategory_name'];
    // Reset the result pointer
    $result->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Exclusive Saree Collection at Yards of Grace">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Yards of Grace</title>
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
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            padding: 15px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .saree-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .saree-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .saree-item h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 10px 0;
            color: var(--primary-color);
        }

        .saree-item p {
            margin: 5px 0;
            color: #555;
        }

        .saree-item .violet-btn {
            margin-top: 10px;
            align-self: center;
            padding: 10px 20px;
            font-size: 14px;
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
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <style>
        .goog-te-gadget-simple {
            background-color: transparent !important;
            border: none !important;
            padding: 0 !important;
            cursor: pointer;
        }

        .goog-te-gadget-simple span {
            color: white !important;
            font-size: 16px;
        }
    </style>
    <div class="container">
    <div class="filter-sidebar">
        <h2>Filters</h2>
        <form id="filter-form" method="GET" action="">
            <!-- Add hidden inputs to retain category or subcategory ID -->
            <?php if ($categoryId): ?>
                <input type="hidden" name="category_id" value="<?php echo $categoryId; ?>">
            <?php elseif ($subcategoryId): ?>
                <input type="hidden" name="subcategory_id" value="<?php echo $subcategoryId; ?>">
            <?php endif; ?>
            <div class="filter-group">
                <label for="color-select">Color:</label>
                <select id="color-select" name="color" class="violet-btn">
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
                <select id="sort-select" name="sort" class="violet-btn">
                    <option value="price-asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price-desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="newest" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                </select>
            </div>
            <div class="filter-group" style="text-align: center; margin-top: 20px;">
                <button type="button" class="violet-btn" onclick="clearFilters()">Clear Filters</button>
            </div>
        </form>
    </div>
    
    <div class="main-content">
        <div id="loading-spinner" class="loading-spinner"></div>
        <div class="saree-list" id="saree-list">
            <?php if (empty($sarees)): ?>
                <div class="no-products">
                    <p>No products found in this category.</p>
                </div>
            <?php else: ?>
                <?php foreach ($sarees as $saree): ?>
                    <?php
                    // Trim the name to remove extra spaces and ensure it's not null
                    $displayName = isset($saree['name']) ? trim($saree['name']) : 'Saree';
                    ?>
                    <div class="saree-item">
                        <img src="<?php echo htmlspecialchars($saree['image'] ?? ''); ?>"
                             alt="<?php echo htmlspecialchars($displayName); ?>"
                             loading="lazy">
                        
                        <h3><?php echo htmlspecialchars($displayName); ?></h3>
                        
                        <p>Color: <?php echo htmlspecialchars($saree['color'] ?? ''); ?></p>
                        <p class="price">₹<?php echo number_format($saree['price'] ?? 0, 2); ?></p>
                        <a href="Traditional.php?id=<?php echo $saree['id'] ?? ''; ?>" class="violet-btn">View Details</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
</body>
</html>