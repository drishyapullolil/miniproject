<?php
session_start();

// Security headers
header("X-Content-Type-Options: nosniff"); 
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Yards of Grace</title>
    <!-- Google Translate Script -->
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
</head>
<body>


<?php include 'header.php'; ?>

    <section class="hero">
        <div class="hero-text">
            <h1>The Perfect Sarees<br>For Every Occasion</h1>
            <p>Explore our exclusive collection of handpicked sarees, crafted with love and tradition.</p>
            <button class="violet-btn" onclick="window.location.href='#featured'">Shop Now</button>
        </div>
    </section>

    <section class="text-section">
        <div class="centered-text">
            <h1>Categories</h1><br>
            Welcome to the world of elegance and craftsmanship<br>
            <div class="center-line"></div>
        </div>
    </section>
    
    <section class="image-section">
        <div class="product-grid">
            <div class="product-card">
                <img src="ca.webp" alt="Category Image 1">
                <p>₹ 12,000</p>
                <h3>Silk Sarees</h3>
                <button class="violet-btn">Buy Now</button>
            </div>
            <div class="product-card">
                <img src="ca3.webp" alt="Category Image 2">
                <p>₹ 12,000</p>
                <h3>Cotton Sarees</h3>
                <button class="violet-btn">Buy Now</button>
            </div>
            <div class="product-card">
                <img src="ca2.webp" alt="Category Image 3">
                <p>₹ 12,000</p>
                <h3>Designer Sarees</h3>
                <button class="violet-btn">Buy Now</button>
            </div>
            <div class="product-card">
                <img src="ca3.webp" alt="Category Image 4">
                <p>₹ 12,000</p>
                <h3>Wedding Sarees</h3>
                <button class="violet-btn">Buy Now</button>
            </div>
        </div>
    </section>

    <section class="featured-products">
        <h2>Featured Products</h2>
        <div class="product-grid">
            <div class="product-card">
                <img src="i1.jpg" alt="Product 1">
                <h3>Banarasi Silk Saree</h3>
                <p>₹ 12,000</p>
                <button class="violet-btn">Buy Now</button>
            </div>
            <div class="product-card">
                <img src="i2.jpg" alt="Product 2">
                <h3>Kanjivaram Silk Saree</h3>
                <p>₹ 15,000</p>
                <button class="violet-btn">Buy Now</button>
            </div>
            <div class="product-card">
                <img src="i3.webp" alt="Product 3">
                <h3>Chanderi Cotton Saree</h3>
                <p>₹ 8,000</p>
                <button class="violet-btn">Buy Now</button>
            </div>
            <div class="product-card">
                <img src="i4.webp" alt="Product 4">
                <h3>Designer Georgette Saree</h3>
                <p>₹ 10,000</p>
                <button class="violet-btn">Buy Now</button>
            </div>
        </div>
    </section>

    <section class="testimonials">
        <h2>What Our Customers Say</h2>
        <div class="testimonial-grid">
            <div class="testimonial-card">
                <p>"The sarees are absolutely stunning! The quality and craftsmanship are unmatched."</p>
                <p>- Priya S.</p>
            </div>
            <div class="testimonial-card">
                <p>"I received so many compliments at my wedding. Thank you, Yards of Grace!"</p>
                <p>- Ananya R.</p>
            </div>
            <div class="testimonial-card">
                <p>"Great customer service and fast delivery. Highly recommend!"</p>
                <p>- Riya M.</p>
            </div>
        </div>
    </section>

    <section class="newsletter">
        <h2>Subscribe to Our Newsletter</h2>
        <p>Get the latest updates on new collections, exclusive offers, and more.</p>
        <form action="subscribe.php" method="POST">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit" class="violet-btn">Subscribe</button>
        </form>
    </section>

    <div class="search-container">
        <input type="text" id="searchInput" class="search-bar" placeholder="Search for sarees...">
        <button onclick="performSearch()" class="search-button">
            <i class="fas fa-search"></i>
        </button>
    </div>

<?php include 'footer.php'; ?>

    <script>
    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
        return false;
    }
    </script>

    <style>
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

    <script>
    function performSearch() {
        const searchQuery = document.getElementById('searchInput').value.trim();
        
        if (searchQuery === '') {
            alert('Please enter a search term');
            return;
        }

        // Log the search query (for debugging)
        console.log('Searching for:', searchQuery);

        // You can customize this to search specific categories
        const searchCategories = [
            'Kanjivaram Sarees',
            'Banarasi Sarees',
            'Designer Sarees',
            'Wedding Sarees'
        ];

        // Example: Search in product titles and descriptions
        const products = document.querySelectorAll('.product-card');
        let found = false;

        products.forEach(product => {
            const title = product.querySelector('h3').textContent.toLowerCase();
            const searchTerm = searchQuery.toLowerCase();

            if (title.includes(searchTerm)) {
                product.style.display = 'block';
                found = true;
            } else {
                product.style.display = 'none';
            }
        });

        // Show message if no results found
        const resultsMessage = document.getElementById('searchResults');
        if (!resultsMessage) {
            const message = document.createElement('div');
            message.id = 'searchResults';
            message.style.textAlign = 'center';
            message.style.margin = '20px';
            document.querySelector('.featured-products').prepend(message);
        }

        if (!found) {
            document.getElementById('searchResults').innerHTML = 
                `<p style="color: #666;">No results found for "${searchQuery}"</p>`;
        } else {
            document.getElementById('searchResults').innerHTML = 
                `<p style="color: #8d0f8f;">Showing results for "${searchQuery}"</p>`;
        }
    }

    // Add event listener for Enter key
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });

    // Add auto-complete suggestions
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const suggestions = [
            'Kanjivaram Sarees',
            'Banarasi Sarees',
            'Designer Sarees',
            'Wedding Sarees',
            'Pattupettu',
            'Korvai',
            'Without Border',
            'Checks',
            'Traditional',
            'Banarasi Tussar',
            'Banarasi Georgette',
            'Banarasi Organza',
            'Banarasi Silk',
            'Banarasi Cotton'
        ];

        const matchingSuggestions = suggestions.filter(item => 
            item.toLowerCase().includes(searchTerm)
        );

        // Show/hide suggestions
        let suggestionBox = document.getElementById('searchSuggestions');
        if (!suggestionBox) {
            suggestionBox = document.createElement('div');
            suggestionBox.id = 'searchSuggestions';
            suggestionBox.className = 'search-suggestions';
            e.target.parentNode.appendChild(suggestionBox);
        }

        if (searchTerm && matchingSuggestions.length > 0) {
            suggestionBox.innerHTML = matchingSuggestions
                .map(item => `<div class="suggestion-item">${item}</div>`)
                .join('');
            suggestionBox.style.display = 'block';
        } else {
            suggestionBox.style.display = 'none';
        }
    });

    // Add styles for suggestions
    const style = document.createElement('style');
    style.textContent = `
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
    `;
    document.head.appendChild(style);

    // Handle suggestion clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('suggestion-item')) {
            document.getElementById('searchInput').value = e.target.textContent;
            document.getElementById('searchSuggestions').style.display = 'none';
            performSearch();
        }
    });
    </script>
</body>
</html>