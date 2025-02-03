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
</head>
<body>
    <header>
        <div class="top-bar">
            <p>Free domestic shipping on orders above INR 10,000. International shipping available.</p>
        </div>
        <div class="header-main">
            <div class="header-left">
                <input type="text" placeholder="Search" class="search-bar" />
            </div>
            
            <div class="header-center">
                <h1><img src="logo3.png" alt="Yards of Grace Logo" width="50px">YARDS OF GRACE</h1>
            </div>
            <div class="header-right">
                <div class="icon-group">
                    <span class="header-icon">👤 
                        <?php                         
                        if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {                             
                            echo htmlspecialchars($_SESSION['username']);                         
                        } else {                             
                            echo "Guest";                         
                        }                         
                        ?> 
                    </span>
                    <span class="header-icon">♥ Wishlist</span>
                    <span class="header-icon">🛍 Shopping Bag</span>
                </div>
                <div class="auth-container">
                    <?php
                    if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
                        echo '<button class="violet-btn" onclick="logout()">Logout</button>';
                    } else {
                        echo '<button class="violet-btn" onclick="window.location.href=\'login.php\'">Login</button>';
                        echo '<button class="violet-btn" onclick="window.location.href=\'reg.php\'">Signup</button>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <nav class="nav-menu">
    <ul>
        <li><a href="#sarees">Category</a>
            <ul class="sub-category">
                <li><a href="#silk">Silk Sarees</a></li>
                <li><a href="#cotton">Cotton Sarees</a></li>
                <li><a href="#designer">Designer Sarees</a></li>
                <li><a href="#wedding">Wedding Sarees</a></li>
            </ul>
        </li>
        <li><a href="#featured">Featured</a></li>
        <li><a href="#accessories">Accessories</a></li>
        <li><a href="#giftcards">Gift Cards</a></li>
        <li><a href="#blogs">Blogs</a></li>
        <li><a href="#webstories">Web Stories</a></li>
    </ul>
</nav>
    </header>

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

    <footer>
        <div class="footer-top">
            <div class="footer-section">
                <h4>YARDS OF GRACE</h4>
                <p>© 2025 Yards of Grace. All rights reserved.</p>
            </div>
            <div class="footer-section">
                <h4>ADDRESS</h4>
                <p>123 Saree Street,<br>Chennai, Tamil Nadu 600001</p>
            </div>
            <div class="footer-section">
                <h4>CONTACT</h4>
                <p>Phone: +91 1234567890<br>Email: info@yardsofgrace.com</p>
            </div>
            <div class="footer-section">
                <h4>SOCIAL MEDIA</h4>
                <div class="social-icons">
                    <a href="#">&#xf09a;</a>
                    <a href="#">&#xf16d;</a>
                    <a href="#">&#xf0d2;</a>
                    <a href="#">&#xf099;</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>Shipping Policy | Terms and Conditions | Privacy Policy | Contact Us</p>
        </div>
    </footer>

    <script>
    function logout() {
        // Prevent caching of the page after logout
        window.location.replace('logout.php');
        return false;
    }
    </script>
</body>
</html>