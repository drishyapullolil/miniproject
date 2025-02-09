
<?php

// Enhanced security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include 'header.php';
?>


    <div class="profile-container">
        <div class="profile-sidebar">
            
            <div class="profile-menu">
                <a href="#personal-info" class="active">Personal Information</a>
                <a href="#orders">My Orders</a>
                <a href="#wishlist">Wishlist</a>
                <a href="#addresses">Addresses</a>
                <a href="#settings">Settings</a>
            </div>
        </div>

        <div class="profile-content">
            <section id="personal-info" class="profile-section">
                <h2>Personal Information</h2>
                <form class="profile-form">
                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="user@example.com">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" pattern="[0-9]{10}">
                    </div>
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob">
                    </div>
                    <button type="submit" class="violet-btn">Save Changes</button>
                </form>
            </section>

            <section id="orders" class="profile-section" style="display: none;">
                <h2>My Orders</h2>
                <div class="orders-list">
                    <!-- Order items will be dynamically loaded -->
                    <p>No orders found.</p>
                </div>
            </section>

            <section id="wishlist" class="profile-section" style="display: none;">
                <h2>My Wishlist</h2>
                <div class="wishlist-grid">
                    <!-- Wishlist items will be dynamically loaded -->
                    <p>Your wishlist is empty.</p>
                </div>
            </section>

            <section id="addresses" class="profile-section" style="display: none;">
                <h2>My Addresses</h2>
                <button class="violet-btn" onclick="showAddAddressForm()">Add New Address</button>
                <div class="addresses-list">
                    <!-- Addresses will be dynamically loaded -->
                    <p>No addresses saved.</p>
                </div>
            </section>
        </div>
    </div>

    <style>
    .profile-container {
        display: flex;
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
        gap: 30px;
    }

    .profile-sidebar {
        width: 280px;
        flex-shrink: 0;
    }

    

    .profile-menu {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .profile-menu a {
        display: block;
        padding: 15px 20px;
        color: #333;
        text-decoration: none;
        border-left: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .profile-menu a:hover,
    .profile-menu a.active {
        background-color: #f8f8f8;
        border-left-color: #8d0f8f;
        color: #8d0f8f;
    }

    .profile-content {
        flex: 1;
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .profile-section {
        margin-bottom: 30px;
    }

    .profile-section h2 {
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }

    .profile-form .form-group {
        margin-bottom: 20px;
    }

    .profile-form label {
        display: block;
        margin-bottom: 8px;
        color: #666;
    }

    .profile-form input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }

    .profile-form input:focus {
        border-color: #8d0f8f;
        outline: none;
        box-shadow: 0 0 5px rgba(141, 15, 143, 0.2);
    }

    .violet-btn {
        background: #8d0f8f;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .violet-btn:hover {
        background: #6d0c6d;
    }

    @media (max-width: 768px) {
        .profile-container {
            flex-direction: column;
        }

        .profile-sidebar {
            width: 100%;
        }

        .profile-content {
            padding: 20px;
        }
    }
    </style>

    <script>
    // Handle profile picture upload
    document.getElementById('profile-upload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile-pic').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    // Handle menu navigation
    document.querySelectorAll('.profile-menu a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active state
            document.querySelectorAll('.profile-menu a').forEach(a => a.classList.remove('active'));
            this.classList.add('active');
            
            // Show corresponding section
            const targetId = this.getAttribute('href').substring(1);
            document.querySelectorAll('.profile-section').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(targetId).style.display = 'block';
        });
    });

    // Form submission
    document.querySelector('.profile-form').addEventListener('submit', function(e) {
        e.preventDefault();
        // Add your form submission logic here
        alert('Profile updated successfully!');
    });
    </script>

<?php include 'footer.php'; ?>