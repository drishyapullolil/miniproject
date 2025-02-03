<?php
// session_start();
include 'db.php';

// Debug connection
if ($conn) {
    echo "<!-- Database connected successfully -->";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $phone = $_POST['phone'];
    
    // Set default role as 'user'
    $role = 'user';
    
    // Check if email already exists
    $check_email_sql = "SELECT email FROM users WHERE email = ?";
    $check_email_stmt = $conn->prepare($check_email_sql);
    
    if (!$check_email_stmt) {
        die("Error in SQL Preparation (Check Email): " . $conn->error);
    }
    
    $check_email_stmt->bind_param("s", $email);
    $check_email_stmt->execute();
    $check_email_stmt->store_result();
    
    if ($check_email_stmt->num_rows > 0) {
        echo "<script>alert('Email already exists. Please use a different email address.'); window.location.href='reg.php';</script>";
        $check_email_stmt->close();
        $conn->close();
        exit();
    }
    
    $check_email_stmt->close();

    // Check if phone number already exists
    $check_phone_sql = "SELECT phoneno FROM users WHERE phoneno = ?";
    $check_phone_stmt = $conn->prepare($check_phone_sql);
    
    if (!$check_phone_stmt) {
        die("Error in SQL Preparation (Check Phone): " . $conn->error);
    }
    
    $check_phone_stmt->bind_param("s", $phone);
    $check_phone_stmt->execute();
    $check_phone_stmt->store_result();
    
    if ($check_phone_stmt->num_rows > 0) {
        echo "<script>alert('Phone number already exists. Please use a different phone number.'); window.location.href='reg.php';</script>";
        $check_phone_stmt->close();
        $conn->close();
        exit();
    }
    
    $check_phone_stmt->close();
    
    // Check if username already exists
    $check_sql = "SELECT username FROM users WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        die("Error in SQL Preparation (Check Username): " . $conn->error);
    }
    
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        echo "<script>alert('Username already taken. Please choose another one.'); window.location.href='reg.php';</script>";
        $check_stmt->close();
        $conn->close();
        exit();
    }
    
    $check_stmt->close();
    
    // Insert the new user if all checks pass
    $sql = "INSERT INTO users (username, email, password, phoneno, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Error in SQL Preparation: " . $conn->error);
    }
    
    $stmt->bind_param("sssss", $username, $email, $password, $phone, $role);
    
    if ($stmt->execute()) {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role; // Store role in session
        
        // Redirect to login page after successful signup
        echo "<script>alert('Signup successful! Please login to continue.'); window.location.href='login.php';</script>";
    }
    
    $stmt->close();
    $conn->close();
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
            height: 100vh;
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
            .password-match {
            color: green;
            font-size: 12px;
            margin-top: 5px;
            display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="form-container">
                <h1>Sign Up to Yards of Grace</h1>
                <p>Start your journey with us today.</p>
                <form id="signupForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                        <span id="usernameError" class="error">Username must be at least 3 characters long and contain only letters and numbers.</span>
                    </div>
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" placeholder="example@email.com" required>
                        <span id="emailError" class="error">Please enter a valid email address.</span>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" placeholder="Enter phone number" required>
                        <span id="phoneError" class="error">Phone number must be 10 digits.</span>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="********" required>
                        <span id="passwordError" class="error">Password must be at least 6 characters long.</span>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="********" required>
                        <span id="confirmPasswordError" class="error">Passwords do not match.</span>
                        
                    </div>
                    <button type="submit" class="btn">Sign Up</button>
                </form>
                <div class="sign-in">
                    Already have an account? <a href="login.php">Login</a>
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="image-container">
                <img src="my.png" alt="Image 1" class="active">
                <img src="image2.png" alt="Image 2">
                <img src="image1.png" alt="Image 3">
                <img src="image3.png" alt="Image 3">
            </div>
        </div>
    </div>

    <script>
        // Form validation function
        function validateForm() {
            const username = document.getElementById("username").value;
            const email = document.getElementById("email").value;
            const phone = document.getElementById("phone").value;
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirmPassword").value;

            const usernameRegex = /^[a-zA-Z0-9]{3,}$/;
            const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            const phoneRegex = /^(?:\+91[-\s]?)?[6-9]\d{9}$/;

            let isValid = true;

            if (!usernameRegex.test(username)) {
                document.getElementById("usernameError").style.display = "block";
                isValid = false;
            }

            if (!emailRegex.test(email)) {
                document.getElementById("emailError").style.display = "block";
                isValid = false;
            }

            if (!phoneRegex.test(phone)) {
                document.getElementById("phoneError").style.display = "block";
                isValid = false;
            }

            if (password.length < 6) {
                document.getElementById("passwordError").style.display = "block";
                isValid = false;
            }

            if (password !== confirmPassword) {
                document.getElementById("confirmPasswordError").style.display = "block";
                document.getElementById("passwordMatch").style.display = "none";
                isValid = false;
            }
            if (password.length < 6) {
                document.getElementById("passwordError").style.display = "block";
                isValid = false;
            }
            if (confirmPassword !== password) {
        confirmPasswordError.textContent = "Passwords do not match.";
        confirmPasswordError.style.display = "block";
    isValid = false;
    }
            return isValid;
        }
     
        // Real-time validation
        document.getElementById("username").addEventListener("input", function() {
            const usernameRegex = /^[a-zA-Z0-9]{3,}$/;
            document.getElementById("usernameError").style.display = 
                usernameRegex.test(this.value) ? "none" : "block";
        });

        document.getElementById("email").addEventListener("input", function() {
            const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            document.getElementById("emailError").style.display = 
                emailRegex.test(this.value) ? "none" : "block";
        });

        document.getElementById("phone").addEventListener("input", function() {
            const phoneRegex = /^(?:\+91[-\s]?)?[6-9]\d{9}$/;
            document.getElementById("phoneError").style.display = 
                phoneRegex.test(this.value) ? "none" : "block";
        });
        document.getElementById("password").addEventListener("input", function() {
            document.getElementById("passwordError").style.display = 
                this.value.length >= 6 ? "none" : "block";
        });
        document.getElementById("confirmPassword").addEventListener("input", function () {
    validateConfirmPassword();
});

        document.getElementById("password").addEventListener("input", checkPasswordMatch);
        document.getElementById("confirmPassword").addEventListener("input", checkPasswordMatch);

        function checkPasswordMatch() {
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirmPassword").value;
            const confirmError = document.getElementById("confirmPasswordError");
            const matchMessage = document.getElementById("passwordMatch");

            if (password.length >= 6 && confirmPassword.length >= 6) {
                if (password === confirmPassword) {
                    confirmError.style.display = "none";
                    matchMessage.style.display = "block";
                } else {
                    confirmError.style.display = "block";
                    matchMessage.style.display = "none";
                }
            } else {
                confirmError.style.display = "none";
                matchMessage.style.display = "none";
            }
        }

        // Image carousel (remains the same)
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