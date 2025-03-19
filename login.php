<?php
session_start();
require_once 'db.php';

// Handle AJAX username check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_username') {
    $username = $_POST['username'] ?? '';
    $response = ['exists' => false];
    
    if (!empty($username)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $response['exists'] = true;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle AJAX password check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_password') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $response = ['correct' => false];
    
    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $response['correct'] = true;
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!loginUser($conn, $username, $password)) {
        header("Location: login.php");
        exit();
    }
}
?>

<!-- Add this at the top of your login form -->
<?php if(isset($_SESSION['logout_message'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['logout_message'];
        unset($_SESSION['logout_message']); // Clear the message after showing
        ?>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Yards of Grace</title>
    <!-- Load Firebase modules with the correct version 9.22.0 -->
    <script type="module" src="main.js"></script>
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

        .validation-message {
            font-size: 12px;
            margin-top: 5px;
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
        
        .google-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            background-color: white;
            color: #757575;
            border: 1px solid #dadce0;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Roboto', Arial, sans-serif;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .google-button:hover {
            background-color: #f7f7f7;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }

        .separator {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
        }

        .separator::before,
        .separator::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dadce0;
        }

        .separator span {
            padding: 0 10px;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            text-align: center;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
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
                        <div id="username-validation" class="validation-message"></div>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="********" required>
                        <div id="password-validation" class="validation-message"></div>
                    </div>
                    <button type="submit" class="btn">Login</button>
                    <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                </form>
                <div class="sign-in">
                    Don't have an account? <a href="reg.php">Sign Up</a>
                </div>

                <!-- Add separator here -->
                <div class="separator">
                    <span>or</span>
                </div>

                <div class="input"> 
                    <button id="google-login-btn" class="google-button">
                        <div style="display: flex; align-items: center; justify-content: center;">
                            <span style="margin-right: 10px;">
                                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                                    <path fill="none" d="M0 0h48v48H0z"/>
                                </svg>
                            </span>
                            <span>Log in with Google</span>
                        </div>
                    </button>
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
        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM loaded");
            
            // Image carousel
            const images = document.querySelectorAll('.image-container img');
            if (images.length > 0) {
                let currentIndex = 0;
                setInterval(() => {
                    images[currentIndex].classList.remove('active');
                    currentIndex = (currentIndex + 1) % images.length;
                    images[currentIndex].classList.add('active');
                }, 3000);
            }
            
            // Real-time validation
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const usernameValidation = document.getElementById('username-validation');
            const passwordValidation = document.getElementById('password-validation');
            const loginForm = document.getElementById('loginForm');
            
            // Function to check username
            function validateUsername() {
                if (usernameInput.value.trim() === '') {
                    usernameValidation.textContent = '';
                    return;
                }
                
                // Show checking message
                usernameValidation.textContent = 'Checking...';
                usernameValidation.style.color = 'gray';
                
                // Send AJAX request to check username
                const formData = new FormData();
                formData.append('action', 'check_username');
                formData.append('username', usernameInput.value);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        usernameValidation.textContent = '✓ Username found';
                        usernameValidation.style.color = 'green';
                        
                        // If password has been entered, validate it
                        if (passwordInput.value.trim() !== '') {
                            validatePassword();
                        }
                    } else {
                        usernameValidation.textContent = '✗ Username not found';
                        usernameValidation.style.color = 'red';
                        
                        // Clear password validation if username doesn't exist
                        passwordValidation.textContent = '';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    usernameValidation.textContent = 'Error checking username';
                    usernameValidation.style.color = 'red';
                });
            }
            
            // Function to check password (only if username exists)
            function validatePassword() {
                if (passwordInput.value.trim() === '' || usernameInput.value.trim() === '') {
                    passwordValidation.textContent = '';
                    return;
                }
                
                // Only check password if username is valid
                if (usernameValidation.textContent !== '✓ Username found') {
                    passwordValidation.textContent = 'Please enter a valid username first';
                    passwordValidation.style.color = 'red';
                    return;
                }
                
                // Show checking message
                passwordValidation.textContent = 'Checking...';
                passwordValidation.style.color = 'gray';
                
                // Send AJAX request to check password
                const formData = new FormData();
                formData.append('action', 'check_password');
                formData.append('username', usernameInput.value);
                formData.append('password', passwordInput.value);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.correct) {
                        passwordValidation.textContent = '✓ Password correct';
                        passwordValidation.style.color = 'green';
                    } else {
                        passwordValidation.textContent = '✗ Incorrect password';
                        passwordValidation.style.color = 'red';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    passwordValidation.textContent = 'Error checking password';
                    passwordValidation.style.color = 'red';
                });
            }
            
            // Add event listeners for real-time validation
            let usernameTimer;
            usernameInput.addEventListener('input', function() {
                clearTimeout(usernameTimer);
                // Clear validation messages
                if (usernameInput.value.trim() === '') {
                    usernameValidation.textContent = '';
                    passwordValidation.textContent = '';
                    return;
                }
                
                usernameValidation.textContent = 'Checking...';
                usernameValidation.style.color = 'gray';
                
                // Delay the check slightly to avoid too many requests while typing
                usernameTimer = setTimeout(validateUsername, 500);
            });
            
            let passwordTimer;
            passwordInput.addEventListener('input', function() {
                clearTimeout(passwordTimer);
                
                if (passwordInput.value.trim() === '') {
                    passwordValidation.textContent = '';
                    return;
                }
                
                if (usernameValidation.textContent === '✓ Username found') {
                    passwordValidation.textContent = 'Checking...';
                    passwordValidation.style.color = 'gray';
                    
                    // Delay the check slightly to avoid too many requests while typing
                    passwordTimer = setTimeout(validatePassword, 500);
                } else if (usernameInput.value.trim() !== '') {
                    // If username has value but not validated yet
                    passwordValidation.textContent = 'Validating username first...';
                    passwordValidation.style.color = 'gray';
                    
                    // Check username first, which will trigger password check if successful
                    clearTimeout(usernameTimer);
                    validateUsername();
                }
            });
            
            // Prevent form submission if validation fails
            loginForm.addEventListener('submit', function(event) {
                // If both fields have values but validations haven't been performed
                if (usernameInput.value.trim() !== '' && passwordInput.value.trim() !== '') {
                    if (usernameValidation.textContent === '' || passwordValidation.textContent === '') {
                        event.preventDefault();
                        
                        // Trigger validations
                        validateUsername();
                        return;
                    }
                    
                    // If validation has been performed but failed
                    if (usernameValidation.textContent !== '✓ Username found' || 
                        passwordValidation.textContent !== '✓ Password correct') {
                        event.preventDefault();
                        
                        // Show alert
                        alert('Please fix the errors before submitting.');
                    }
                }
            });
        });
    </script>
</body>
</html>