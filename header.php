<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged out but session still exists
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // If last activity was more than 30 minutes ago
    session_unset();     // unset $_SESSION variable for the run-time 
    session_destroy();   // destroy session data in storage
    // Redirect to the same page to refresh after session destroy
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$_SESSION['last_activity'] = time(); // Update last activity time

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// Include the database connection file
require_once 'db.php';
// Fetch categories and subcategories from the database
$categories = [];
$subcategories = [];

$categoryQuery = "SELECT * FROM categories";
$categoryResult = $conn->query($categoryQuery);

if ($categoryResult->num_rows > 0) {
    while ($categoryRow = $categoryResult->fetch_assoc()) {
        $categories[] = $categoryRow;

        $subcategoryQuery = "SELECT * FROM subcategories WHERE category_id = " . $categoryRow['id'];
        $subcategoryResult = $conn->query($subcategoryQuery);

        if ($subcategoryResult->num_rows > 0) {
            while ($subcategoryRow = $subcategoryResult->fetch_assoc()) {
                $subcategories[$categoryRow['id']][] = $subcategoryRow;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Add this in the <head> section of your header.php file -->
<link rel="stylesheet" href="live_search.css">

<!-- Add this just before the closing </body> tag -->
<script src="live_search.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YARDS OF GRACE</title>
    <link rel="stylesheet" href="styles.css">
    <style>
         *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            text-decoration: none;
        }

        /* Variables */
        :root {
            --primary-color: #4e034f;
            --primary-hover: #6c0c6d;
            --text-color: #333;
            --text-light: #666;
            --background-light: #fff;
            --border-color: #eee;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --border-radius: 10px;
            --container-width: 1200px;
            --shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            
            top: 0;
            background: var(--background-light);
            z-index: 100;
            text-decoration: none;
        }

        .announcement-bar {
            background: var(--primary-color);
            color: var(--background-light);
            text-align: center;
            padding: var(--spacing-sm);
        }

        /* Header Layout */
        .header-main {
            
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: white;
            text-decoration: none;
        }

        /* Search Container */
        .header-left {
            position: sticky;
            top: 0;
            background: var(--background-light);
            z-index: 100;
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
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px 10px;
            max-width: 400px;
            width: 100%;
        }

        .search-input {
            flex: 1;
            border: none;
            padding: 8px;
            font-size: 14px;
            outline: none;
            background: transparent;
        }

        .search-button {
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            font-size: 16px;
        }

        .search-button:hover {
            opacity: 0.8;
        }

        /* Center Logo */
        .header-center {
            text-align: center;
            text-decoration: none;
        }

        /* Right Icons and Buttons */
        .header-right {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            text-decoration: none;
        }

        .icon-group {
            display: flex;
            align-items: center;
            gap: 20px;
            text-decoration: none;
        }

        .header-icon {
            cursor: pointer;
            transition: color 0.3s ease;
            text-decoration: none;
        }

        /* Navigation Menu */
        .nav-menu {
            background-color: white;
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
            padding: 15px 0;
            display: block;
            transition: color 0.3s ease;
        }

        .main-menu a:hover {
            color: #8d0f8f;
        }

        /* Auth Buttons */
        .login-btn, .signup-btn {
            background-color: #8d0f8f;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 8px 24px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
            min-width: 100px;
            box-shadow: 0 2px 4px rgba(141, 15, 143, 0.2);
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-left: 10px;
        }

        .login-btn {
            background-color: transparent;
            border: 2px solid #8d0f8f;
            color: #8d0f8f;
        }

        .login-btn:hover, .signup-btn:hover {
            background-color: #4e034f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(141, 15, 143, 0.3);
            color: white;
        }

        .login-btn:hover {
            background-color: #8d0f8f;
        }

        @media (max-width: 768px) {
            .header-main {
                flex-direction: column;
                text-decoration: none;
            }
            
            .header-left, .header-right {
                width: 100%;
                padding: 10px 0;
                text-decoration: none;
            }
            
            .search-container {
                max-width: 100%;
            }
            
            .icon-group {
                justify-content: center;
                flex-wrap: wrap;
                text-decoration: none;
            }
        }
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
        text-decoration: none;
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
        text-decoration: none;
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
        display: none; /* Remove the line effect for all menu items */
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
        padding-right: 20px; /* Make space for the dropdown arrow */
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

    /* Search Bar Styling */
    .search-container {
        display: flex;
        align-items: center;
        background: #fff;
        border: 2px solid #8d0f8f;
        border-radius: 5px;
        padding: 5px 15px;
        max-width: 400px;
        width: 100%;
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
        display: flex;
        align-items: center;
    }

    .search-button:hover {
        transform: scale(1.1);
    }

    /* Add animation for focus state */
    .search-container:focus-within {
        box-shadow: 0 0 8px rgba(141, 15, 143, 0.3);
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .search-container {
            max-width: 100%;
            margin: 10px 0;
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
    /* Search Container and Results Styling */
.header-left {
    position: relative; /* Change to relative for proper positioning context */
    z-index: 1001; /* Higher than other elements */
}

.search-container {
    display: flex;
    align-items: center;
    background: #fff;
    border: 2px solid #8d0f8f;
    border-radius: 5px;
    padding: 5px 15px;
    max-width: 400px;
    width: 100%;
    position: relative;
}

/* Add this new style for search results container */
.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1002; /* Even higher than the search container */
    margin-top: 5px;
}

/* Ensure the header has a lower z-index */
.header {
    /*position: sticky;*/
    top: 0;
    background: var(--background-light);
    z-index: 100; /* Lower than search container */
}

.nav-menu {
    position: relative;
    z-index: 99; /* Lower than header */
}

/* Add these styles to your existing CSS */
.price-range {
    font-size: 0.8em;
    color: #666;
    margin-left: 8px;
}

.dropdown li a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
}

/* Wedding collection specific styles */
.main-menu > li:nth-child(4) .dropdown { /* Targeting Wedding Collection dropdown */
    min-width: 250px;
}

.main-menu > li:nth-child(4) .dropdown li a:hover {
    background-color: #fff1f9; /* Light pink background on hover */
}

/* Optional: Add a wedding icon */
.main-menu > li:nth-child(4) > a::before {
    
    margin-right: 5px;
    font-size: 1.1em;
}

.global-search {
    position: relative;
    max-width: 450px;
    margin: 0 auto;
    padding: 10px;
}

.search-input-group {
    display: flex;
    gap: 10px;
}

.search-input {
    flex: 1;
    padding: 12px 20px;
    border: 2px solid #8d0f8f;
    border-radius: 25px;
    font-size: 16px;
    outline: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.search-input:focus {
    border-color: #4e034f;
    box-shadow: 0 0 8px rgba(141, 15, 143, 0.3);
}

.search-button {
    background: #8d0f8f;
    color: white;
    border: none;
    border-radius: 25px;
    width: 45px;
    height: 45px;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.search-button:hover {
    background: #4e034f;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(141, 15, 143, 0.3);
}

.live-search-results {
    display: none;
    position: absolute;
    top: calc(100% + 5px);
    left: 0;
    right: 0;
    background: white;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
    border: 1px solid #f0e0f0;
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.search-result-item {
    display: flex;
    padding: 15px;
    text-decoration: none;
    color: inherit;
    border-bottom: 1px solid #f5e0f5;
    transition: all 0.3s ease;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-item:hover {
    background-color: #fdf4fd;
}

.search-result-item img {
    width: 60px;
    height: 75px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.search-result-item:hover img {
    transform: scale(1.05);
}

.search-item-details {
    margin-left: 15px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.search-item-name {
    font-weight: 500;
    margin-bottom: 8px;
    color: #333;
    font-size: 15px;
    transition: color 0.3s ease;
}

.search-result-item:hover .search-item-name {
    color: #8d0f8f;
}

.search-item-price {
    color: #8d0f8f;
    font-weight: 600;
    font-size: 16px;
}

/* Status messages */
.searching, .no-results, .error {
    padding: 20px;
    text-align: center;
    color: #666;
    font-size: 15px;
}

.searching {
    position: relative;
    padding: 25px;
}

.searching::after {
    content: '';
    height: 20px;
    width: 20px;
    border: 3px solid #8d0f8f;
    border-right-color: transparent;
    border-radius: 50%;
    display: inline-block;
    position: absolute;
    margin-left: 10px;
    animation: rotate 0.8s linear infinite;
}

@keyframes rotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.no-results {
    color: #8d0f8f;
    font-style: italic;
}

.error {
    color: #dc3545;
    font-weight: 500;
}

/* Custom scrollbar for results */
.live-search-results::-webkit-scrollbar {
    width: 8px;
}

.live-search-results::-webkit-scrollbar-track {
    background: #f9f0f9;
    border-radius: 10px;
}

.live-search-results::-webkit-scrollbar-thumb {
    background: #d0a0d0;
    border-radius: 10px;
}

.live-search-results::-webkit-scrollbar-thumb:hover {
    background: #8d0f8f;
}

/* Responsive styles */
@media (max-width: 768px) {
    .global-search {
        padding: 10px 15px;
        max-width: 100%;
    }
    
    .search-input {
        font-size: 14px;
        padding: 10px 15px;
    }
    
    .search-button {
        width: 40px;
        height: 40px;
    }
    
    .search-result-item {
        padding: 10px;
    }
    
    .search-item-name {
        font-size: 14px;
    }
    
    .search-item-price {
        font-size: 15px;
    }
}

/* Enhanced Search Bar Styling */
.global-search {
    position: relative;
    max-width: 450px;
    margin: 0 auto;
    padding: 10px;
}

.search-input-group {
    display: flex;
    position: relative;
}

.search-container {
    display: flex;
    align-items: center;
    background: #fff;
    border: 2px solid #8d0f8f;
    border-radius: 50px;
    padding: 5px;
    width: 100%;
    box-shadow: 0 2px 5px rgba(141, 15, 143, 0.1);
}

.search-container:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(141, 15, 143, 0.15);
}

.search-container:focus-within {
    border-color: #4e034f;
    box-shadow: 0 4px 15px rgba(141, 15, 143, 0.2);
}

.search-input {
    flex: 1;
    border: none;
    padding: 12px 20px;
    font-size: 16px;
    outline: none;
    background: transparent;
    color: #333;
}

.search-button {
    background: #8d0f8f;  /* Solid purple background */
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    margin-right: 5px;
    transition: all 0.3s ease;
}

.search-icon {
    color: white;  /* White icon */
    font-size: 16px;
}

.search-button:hover {
    background: #7a0d7b;
    transform: scale(1.05);
}

.search-button:active {
    transform: scale(0.95);
}
    </style>
</head>
<body>
    <div class="announcement-bar">
        <p>Free domestic shipping on orders above INR 10,000. International shipping available.</p>
    </div>

    <div class="header-main">
        <div class="header-left">
            <div class="global-search">
                <form action="search_results.php" method="GET">
                    <div class="search-input-group">
                        <div class="search-container">
                            <input type="text" 
                                   id="live-search"
                                   name="q" 
                                   placeholder="Search products..." 
                                   class="search-input" 
                                   autocomplete="off"
                                   minlength="2">
                            <button type="submit" class="search-button">
                                <span class="search-icon">🔍</span>
                            </button>
                            <div id="search-results" class="search-results-dropdown"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="header-center">
            <h1><img src="logo3.png" alt="Yards of Grace Logo" width="50px">YARDS OF GRACE</h1>
        </div>

        <div class="header-right">
            <div class="icon-group">
                <div class="user-dropdown">
                    <span class="header-icon user-icon">👤 <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?>
                        <span class="dropdown-arrow">▼</span>
                    </span>
                    <div class="user-dropdown-content">
                        <?php if (isset($_SESSION['username'])): ?>
                            <a href="profile.php">Profile</a>
                            <a href="orders.php">My Orders</a>
                            <a href="settings.php">Settings</a>
                            <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
                        <?php else: ?>
                            <a href="login.php">Login</a>
                            <a href="reg.php">Sign Up</a>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="header-icon">
                    <?php if (isset($_SESSION['username'])): ?>
                        <a href="wishlist.php">♥ Wishlist</a>
                    <?php else: ?>
                        <a href="login.php">♥ Wishlist</a>
                    <?php endif; ?>
                </span>
                <span class="header-icon">
                    <?php if (isset($_SESSION['username'])): ?>
                        <a href="addtocart.php">🛍 Shopping Bag</a>
                    <?php else: ?>
                        <a href="login.php">🛍 Shopping Bag</a>
                    <?php endif; ?>
                </span>
                <?php if (!isset($_SESSION['username'])): ?>
                    <a href="login.php" class="login-btn">Login</a>
                    <a href="reg.php" class="signup-btn">Sign up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <header class="header">
    <nav class="nav-menu">
        <ul class="main-menu">
            <li><a href="home.php">Home</a></li>
            <li><a href="#category">Category</a>
                <ul class="dropdown">
                    <?php foreach ($categories as $category): ?>
                        <li>
                            <a href="categories_user.php?category_id=<?= $category['id'] ?>">
                                <?= htmlspecialchars($category['category_name']) ?>
                            </a>
                            <?php if (isset($subcategories[$category['id']])): ?>
                                <ul class="subdropdown">
                                    <?php foreach ($subcategories[$category['id']] as $subcategory): ?>
                                        <li>
                                            <a href="categories_user.php?subcategory_id=<?= $subcategory['id'] ?>">
                                                <?= htmlspecialchars($subcategory['subcategory_name']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <li><a href="#new-arrivals">New Arrivals</a></li>
            <li><a href="#wedding">Wedding Collection</a>
                <ul class="dropdown">
                    <?php
                    // Fetch wedding categories
                    $weddingQuery = "SELECT * FROM wedding_categories ORDER BY category_name";
                    $weddingResult = $conn->query($weddingQuery);
                    
                    while ($weddingCategory = $weddingResult->fetch_assoc()): 
                    ?>
                        <li>
                            <a href="wedding_categories_user.php?category_id=<?= $weddingCategory['id'] ?>">
                                <?= htmlspecialchars($weddingCategory['category_name']) ?>
                                
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </li>
            <li><a href="#deals">Deals</a></li>
            <li><a href="#blog">Blog</a></li>
        </ul>
    </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('live-search');
    const searchResults = document.getElementById('search-results');
    let searchTimeout;

    // Function to handle search submission
    function submitSearch() {
        const query = searchInput.value.trim();
        if (query.length >= 2) {
            window.location.href = `search_results.php?q=${encodeURIComponent(query)}`;
        }
    }

    // Handle Enter key press
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            submitSearch();
        }
    });

    // Live search preview
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        searchResults.innerHTML = '<div class="searching">Searching...</div>';
        searchResults.style.display = 'block';

        searchTimeout = setTimeout(() => {
            fetch(`live_search.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(response => {
                    if (!response.success) {
                        console.error('Search error:', response.debug_message);
                        searchResults.innerHTML = `<div class="error">${response.message}</div>`;
                        return;
                    }
                    
                    const data = response.data;
                    if (data.length > 0) {
                        searchResults.innerHTML = `
                            ${data.map(item => `
                                <a href="search_results.php?q=${encodeURIComponent(query)}" class="search-result-item">
                                    <img src="${item.image}" alt="${item.name}">
                                    <div class="search-item-details">
                                        <div class="search-item-name">${item.name}</div>
                                        <div class="search-item-category">${item.category_name} > ${item.subcategory_name}</div>
                                        <div class="search-item-price">₹${item.price}</div>
                                    </div>
                                </a>
                            `).join('')}
                            <div class="view-all-results">
                                <a href="search_results.php?q=${encodeURIComponent(query)}">View all results</a>
                            </div>
                        `;
                    } else {
                        searchResults.innerHTML = '<div class="no-results">No results found</div>';
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<div class="error">Search error occurred</div>';
                });
        }, 300);
    });

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchResults.contains(e.target) && e.target !== searchInput) {
            searchResults.style.display = 'none';
        }
    });
});
</script>
</body>
</html>