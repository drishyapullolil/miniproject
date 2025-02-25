<?php
session_start();
require_once 'db.php';

function loginUser($conn, $username, $password) {
    // Input validation
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Please enter username and password";
        return false;
    }

    // Prepare statement to check user
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows === 0) {
        $_SESSION['login_error'] = "User not found";
        return false;
    }

    $user = $result->fetch_assoc();

    // Verify password
    if (!password_verify($password, $user['password'])) {
        $_SESSION['login_error'] = "Incorrect password";
        return false;
    }

    // Capture login details
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $login_time = date('Y-m-d H:i:s');

    // Insert login details into 'logins' table, including username
    $login_stmt = $conn->prepare("INSERT INTO logins (user_id, username, login_time, ip_address) VALUES (?, ?, ?, ?)");
    if (!$login_stmt) {
        die("SQL Error: " . $conn->error);
    }

    try {
        // Bind parameters correctly
        $login_stmt->bind_param("isss", $user['id'], $user['username'], $login_time, $ip_address);

        // Execute the statement
        if ($login_stmt->execute()) {
            // Update last login time
            $update_stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();

            // Create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: home.php");
            }
            exit();
        } else {
            $_SESSION['login_error'] = "Login failed. Please try again.";
            return false;
        }
    } catch (Exception $e) {
        $_SESSION['login_error'] = "An error occurred: " . $e->getMessage();
        return false;
    }
}

// Login processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!loginUser($conn, $username, $password)) {
        header("Location: login.php");
        exit();
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Yards of Grace</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .container {
            display: flex;
            width: 80%;
            max-width: 1200px;
            height: 80vh;
            background-color: rgb(240, 18, 18);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        h1 {
  color: purple;
}

        .left-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background-color: #ffffff;
        }

        .right-section {
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        .image-container {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .image-container img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }

        .image-container img.active {
            opacity: 1;
        }

        .form-container {
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .left-section h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .left-section p {
            color: #7a7a7a;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #fc0404;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus {
            border-color: #0051ff;
            outline: none;
        }

        .error {
            color: red;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .btn {
            width: 100%;
            padding: 10px;
            background-color: #9f1d92c1;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #f705ff;
        }

        .forgot-password {
            display: block;
            margin-top: 10px;
            font-size: 14px;
            color: #007bff;
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .sign-in {
            margin-top: 20px;
            font-size: 14px;
        }

        .sign-in a {
            color: #007bff;
            text-decoration: none;
        }

        .sign-in a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="form-container">
                <h1>Login to Yards of Grace</h1>
                <p>Start your journey with us today.</p>
                
                <?php
                // Display login errors
                if (isset($_SESSION['login_error'])) {
                    echo "<div style='color: red; margin-bottom: 15px;'>" . 
                         htmlspecialchars($_SESSION['login_error']) . "</div>";
                    unset($_SESSION['login_error']);
                }
                ?>
                
                <form id="loginForm" method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="********" required>
                    </div>
                    <button type="submit" class="btn">Login</button>
                    <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                </form>
                <div class="sign-in">
                    Don't have an account? <a href="reg.php">Sign Up</a>
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="image-container">
                <img src="my.png" alt="Image 1" class="active">
                <img src="image2.png" alt="Image 2">
                <img src="image3.png" alt="Image 3">
                <img src="image1.png" alt="Image 4">
            </div>
        </div>
    </div>

   <script>
    // Real-time validation for the username field
    const usernameInput = document.getElementById("username");
    const usernameError = document.getElementById("usernameError");

    usernameInput.addEventListener("input", function () {
        const usernameRegex = /^[a-zA-Z0-9]{3,}$/;
        if (!usernameRegex.test(usernameInput.value)) {
            usernameError.style.display = "block";
        } else {
            usernameError.style.display = "none";
        }
    });

    // Real-time validation for the password field
    const passwordInput = document.getElementById("password");
    const passwordError = document.getElementById("passwordError");

    passwordInput.addEventListener("input", function () {
        if (passwordInput.value.length < 6) {
            passwordError.style.display = "block";
        } else {
            passwordError.style.display = "none";
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

    // Form submission
    const signupForm = document.getElementById("signupForm");
    signupForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const username = usernameInput.value;
        const password = passwordInput.value;

        if (/^[a-zA-Z0-9]{3,}$/.test(username) && password.length >= 6) {
            alert("Sign-up successful for " + username);

            // Redirect to home.php after successful submission
            window.location.href = "home.php";
        }
    });
</script>
</body>
</html>


