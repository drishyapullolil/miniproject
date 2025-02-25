<?php
session_start();

// Database configuration
$db_host = 'localhost';
$db_name = 'yards_of_grace';
$db_user = 'root';
$db_pass = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // Prepare SQL statement
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            
            // Prevent session fixation
            session_regenerate_id(true);
            
            // Redirect to home page
            header("Location: home.php");
            exit();
        } else {
            $error_message = "Invalid username or password";
        }
        
    } catch(PDOException $e) {
        $error_message = "An error occurred. Please try again later.";
        error_log("Login error: " . $e->getMessage());
    }
}

// Check if user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Yards of Grace</title>
    <?php include 'styles.php'; ?>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="form-container">
                <h1>Login to Yards of Grace</h1>
                <p>Start your journey with us today.</p>
                <?php if(isset($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" 
                               placeholder="Enter your username" required
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        <span id="usernameError" class="error"></span>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" 
                               placeholder="********" required>
                        <span id="passwordError" class="error"></span>
                    </div>
                    <button type="submit" class="btn">Login</button>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </form>
                <div class="sign-in">
                    Don't have an account? <a href="register.php">Sign Up</a>
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="image-container">
                <img src="image1.png" alt="Image 1" class="active">
                <img src="image2.png" alt="Image 2">
                <img src="image3.png" alt="Image 3">
            </div>
        </div>
    </div>

    <script>
    // Client-side validation
    document.getElementById("loginForm").addEventListener("submit", function(e) {
        let isValid = true;
        const username = document.getElementById("username").value;
        const password = document.getElementById("password").value;
        
        // Username validation
        if (!/^[a-zA-Z0-9]{3,}$/.test(username)) {
            document.getElementById("usernameError").textContent = 
                "Username must be at least 3 characters long and contain only letters and numbers.";
            document.getElementById("usernameError").style.display = "block";
            isValid = false;
        } else {
            document.getElementById("usernameError").style.display = "none";
        }
        
        // Password validation
        if (password.length < 6) {
            document.getElementById("passwordError").textContent = 
                "Password must be at least 6 characters long.";
            document.getElementById("passwordError").style.display = "block";
            isValid = false;
        } else {
            document.getElementById("passwordError").style.display = "none";
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });

    // Image carousel
    const images = document.querySelectorAll(".image-container img");
    let currentIndex = 0;

    setInterval(() => {
        images[currentIndex].classList.remove("active");
        currentIndex = (currentIndex + 1) % images.length;
        images[currentIndex].classList.add("active");
    }, 3000);
    </script>
</body>
</html>