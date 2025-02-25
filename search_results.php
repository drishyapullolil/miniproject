<?php
require_once 'db.php';
include 'header.php';

// Get the search query
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!empty($searchQuery)) {
    // Prepare the search query with wildcards for partial matches
    $searchTerm = "%" . $conn->real_escape_string($searchQuery) . "%";
    
    // Search in sarees table
    $sql = "SELECT s.*, c.category_name, sc.subcategory_name 
            FROM sarees s
            LEFT JOIN categories c ON s.category_id = c.id
            LEFT JOIN subcategories sc ON s.subcategory_id = sc.id
            WHERE s.name LIKE ? 
            OR s.saree_name LIKE ? 
            OR s.description LIKE ?
            OR c.category_name LIKE ?
            OR sc.subcategory_name LIKE ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
?>

<div class="results-grid">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="saree-item">
                <img src="<?php echo htmlspecialchars($row['image']); ?>" 
                     alt="<?php echo htmlspecialchars($row['name']); ?>" 
                     loading="lazy">
                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                <p>Color: <?php echo htmlspecialchars($row['color']); ?></p>
                <p class="price">₹<?php echo number_format($row['price'], 2); ?></p>
                <a href="Traditional.php?id=<?php echo $row['id']; ?>" class="violet-btn">View Details</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="no-results">
            <h3>No results found</h3>
            <p>Try different keywords or check your spelling</p>
        </div>
    <?php endif; ?>
</div>

<?php
} else {
    echo '<div class="no-results">Please enter a search term</div>';
}
?>

<style>
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
        display: inline-block;
        margin-top: 10px;
    }

    .violet-btn:hover {
        background-color: var(--primary-hover);
    }

    :root {
        --primary-color: #4e034f;
        --primary-hover: #6c0c6d;
    }

    .results-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
        padding: 20px;
    }

    .price {
        font-weight: bold;
        color: #4e034f;
        margin: 10px 0;
    }

    .no-results {
        text-align: center;
        padding: 40px;
        color: #666;
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .results-grid {
            grid-template-columns: 1fr;
        }
    }
</style> 