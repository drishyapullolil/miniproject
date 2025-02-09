<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YARDS OF GRACE</title>
    <link rel="stylesheet" href="styles.css">
    <style>
         *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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
            position: sticky;
            top: 0;
            background: var(--background-light);
            z-index: 100;
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

        /* Center Logo */
        .header-center {
            text-align: center;
        }

        /* Right Icons and Buttons */
        .header-right {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .icon-group {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-icon {
            cursor: pointer;
            transition: color 0.3s ease;
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
            }
            
            .header-left, .header-right {
                width: 100%;
                padding: 10px 0;
            }
            
            .search-container {
                max-width: 100%;
            }
            
            .icon-group {
                justify-content: center;
                flex-wrap: wrap;
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
        border-radius: 25px;
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
        color: #8d0f8f;
        transition: all 0.3s ease;
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
    </style>
</head>
<body>

        <div class="announcement-bar">
            <p>Free domestic shipping on orders above INR 10,000. International shipping available.</p>
        </div>
        <!-- Rest of the header content remains the same -->
    
    <div class="header-main">
        <div class="header-left">
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Search products..." class="search-bar">
                <button onclick="performSearch()" class="search-button">
                    <i class="fas fa-search">🔍</i>
                </button>
            </div>
        </div>
        
        <div class="header-center">
            <h1><img src="logo3.png" alt="Yards of Grace Logo" width="50px">YARDS OF GRACE</h1>
        </div>

        <div class="header-right">
            <div class="icon-group">
                <div class="user-dropdown">
                    <span class="header-icon user-icon">👤 
                        <?php                         
                        if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {                             
                            echo htmlspecialchars($_SESSION['username']);                         
                        } else {                             
                            echo "Guest";                         
                        }                         
                        ?> 
                        <span class="dropdown-arrow">▼</span>
                    </span>
                    <div class="user-dropdown-content">
                        <a href="profile.php">Profile</a>
                        <a href="settings.php">Settings</a>
                        <?php if (isset($_SESSION['username'])): ?>
                            <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="header-icon"><a href="wishlist.php">♥ Wishlist</a></span>
                <span class="header-icon">🛍 Shopping Bag</span>
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
                    <li><a href="#kanjivaram">Kanjivaram Sarees</a>
                        <ul class="subdropdown">
                            <li><a href="pattupettu.php">Pattupettu</a></li>
                            <li><a href="#korvai">Korvai</a></li>
                            <li><a href="#without-border">Without Border</a></li>
                            <li><a href="#checks">Checks</a></li>
                            <li><a href="#traditional">Traditional</a></li>
                        </ul>
                    </li>
                    <li><a href="#banarasi">Banarasi Sarees</a>
                        <ul class="subdropdown">
                            <li><a href="#banarasi-tussar">Banarasi Tussar</a></li>
                            <li><a href="#banarasi-georgette">Banarasi Georgette</a></li>
                            <li><a href="#banarasi-organza">Banarasi Organza</a></li>
                            <li><a href="#banarasi-silk">Banarasi Silk</a></li>
                            <li><a href="#banarasi-cotton">Banarasi Cotton</a></li>
                        </ul>
                    </li>
                    <li><a href="#designer">Designer Sarees</a></li>
                    <li><a href="#wedding">Wedding Sarees</a></li>
                </ul>
            </li>
            <li><a href="#new-arrivals">New Arrivals</a></li>
            <li><a href="#trending">Trending</a></li>
            <li><a href="#deals">Deals</a></li>
            <li><a href="#blog">Blog</a></li>
        </ul>
    </nav>
   
    </header>
</body>
</html> 