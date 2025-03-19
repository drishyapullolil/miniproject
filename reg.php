<?php
session_start();
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';


// Debug connection
if ($conn) {
    echo "<!-- Database connected successfully -->";
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
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
   
    // Generate OTP
    $otp = rand(100000, 999999); // Generate a 6-digit OTP
    $_SESSION['otp'] = $otp; // Store OTP in session for later verification
    $_SESSION['email'] = $email; // Store email for sending OTP
    $_SESSION['username'] = $username; // Store username for registration
    $_SESSION['password'] = $password; // Store password for registration
    $_SESSION['phone'] = $phone; // Store phone for registration


    // Send OTP to user's email
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = 'padanamakkalonix@gmail.com'; // SMTP username
        $mail->Password = 'shyl ywsq bwel vnvk'; // SMTP password (use App Password if 2FA is enabled)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable SSL encryption
        $mail->Port = 465; // TCP port to connect to


        // Recipients
        $mail->setFrom('padanamakkalonix@gmail.com', 'Your Name');
        $mail->addAddress($email); // Add a recipient


        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Registration';
        $mail->Body    = "Your OTP code is: <strong>$otp</strong>";


        $mail->send();
        echo 'OTP has been sent to your email. Please verify to complete registration.';
        // Redirect to OTP verification page
        header("Location: verify_otp.php");
        exit();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
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
            width: 90%;
            max-width: 1200px;
            height: 130vh;
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
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }


        .btn:not(:disabled) {
            opacity: 1;
        }


        .btn:hover:not(:disabled) {
            background-color: #f705ff;
        }


        .requirements-list {
            list-style: none;
            padding: 0;
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }


        .requirements-list li {
            margin-bottom: 3px;
            padding-left: 20px;
            position: relative;
        }


        .requirements-list li:before {
            content: '✕';
            color: #dc3545;
            position: absolute;
            left: 0;
        }


        .requirements-list li.valid:before {
            content: '✓';
            color: #28a745;
        }


        .requirements-list li.valid {
            color: #28a745;
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
                        <span id="phoneError" class="error">Please enter a valid Indian phone number.</span>
                    </div>
                   
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="********" required>
                        <ul class="requirements-list">
                            <li id="length">At least 8 characters long</li>
                            <li id="uppercase">Contains uppercase letter</li>
                            <li id="lowercase">Contains lowercase letter</li>
                            <li id="number">Contains number</li>
                            <li id="special">Contains special character</li>
                        </ul>
                    </div>
                   
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="********" required>
                        <span id="confirmPasswordError" class="error">Passwords do not match.</span>
                    </div>
                   
                    <button type="submit" class="btn" id="submitBtn" disabled>Sign Up</button>
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
                <img src="image3.png" alt="Image 4">
            </div>
        </div>
    </div>


    <script>
        // Password validation requirements
        // Password validation requirements
const passwordRequirements = {
    length: password => password.length >= 8,
    uppercase: password => /[A-Z]/.test(password),
    lowercase: password => /[a-z]/.test(password),
    number: password => /[0-9]/.test(password),
    special: password => /[!@#$%^&*(),.?":{}|<>]/.test(password)
};

const requirements = {
    length: document.getElementById('length'),
    uppercase: document.getElementById('uppercase'),
    lowercase: document.getElementById('lowercase'),
    number: document.getElementById('number'),
    special: document.getElementById('special')
};

// Initialize all error messages to be hidden
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById("usernameError").style.display = "none";
    document.getElementById("emailError").style.display = "none";
    document.getElementById("phoneError").style.display = "none";
    document.getElementById("confirmPasswordError").style.display = "none";
});

function validatePassword() {
    const password = document.getElementById('password').value;
    let isValid = true;

    // Check each requirement
    for (const [requirement, validator] of Object.entries(passwordRequirements)) {
        const element = requirements[requirement];
        const valid = validator(password);
        element.className = valid ? 'valid' : '';
        if (!valid) isValid = false;
    }

    return isValid;
}

function validatePasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const confirmError = document.getElementById('confirmPasswordError');
    
    // Only show error if confirmPassword field has been touched
    if (confirmPassword !== '') {
        const match = password === confirmPassword;
        confirmError.style.display = match ? 'none' : 'block';
        return match;
    }
    
    return false; // If confirm password is empty, consider it invalid for button enabling
}

function validateUsername() {
    const username = document.getElementById("username").value;
    const usernameRegex = /^[a-zA-Z0-9]{3,}$/;
    const usernameValid = usernameRegex.test(username);
    
    // Only show error if username field has been touched
    if (username !== '') {
        document.getElementById("usernameError").style.display = usernameValid ? "none" : "block";
        return usernameValid;
    }
    
    return false;
}

function validateEmail() {
    const email = document.getElementById("email").value;
    const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    const emailValid = emailRegex.test(email);
    
    // Only show error if email field has been touched
    if (email !== '') {
        document.getElementById("emailError").style.display = emailValid ? "none" : "block";
        return emailValid;
    }
    
    return false;
}

function validatePhone() {
    const phone = document.getElementById("phone").value;
    const phoneRegex = /^(?:\+91[-\s]?)?[6-9]\d{9}$/;
    const phoneValid = phoneRegex.test(phone);
    
    // Only show error if phone field has been touched
    if (phone !== '') {
        document.getElementById("phoneError").style.display = phoneValid ? "none" : "block";
        return phoneValid;
    }
    
    return false;
}

function updateSubmitButton() {
    const submitBtn = document.getElementById('submitBtn');
    
    // Check if all fields have values
    const username = document.getElementById("username").value;
    const email = document.getElementById("email").value;
    const phone = document.getElementById("phone").value;
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    
    // Only enable button if all fields are filled AND valid
    const allFieldsFilled = username !== '' && email !== '' && phone !== '' && 
                           password !== '' && confirmPassword !== '';
    
    const usernameValid = validateUsername();
    const emailValid = validateEmail();
    const phoneValid = validatePhone();
    const passwordValid = validatePassword();
    const passwordsMatch = validatePasswordMatch();
    
    // Check if filled fields are valid (for partially completed form)
    const filledFieldsValid = 
        (username === '' || usernameValid) && 
        (email === '' || emailValid) && 
        (phone === '' || phoneValid) && 
        (password === '' || passwordValid) && 
        (confirmPassword === '' || passwordsMatch);
    
    // Only enable button when all fields are filled AND valid
    submitBtn.disabled = !(allFieldsFilled && usernameValid && emailValid && 
                          phoneValid && passwordValid && passwordsMatch);
}

// Event listeners for real-time validation
document.getElementById('username').addEventListener('input', function() {
    validateUsername();
    updateSubmitButton();
});

document.getElementById('email').addEventListener('input', function() {
    validateEmail();
    updateSubmitButton();
});

document.getElementById('phone').addEventListener('input', function() {
    validatePhone();
    updateSubmitButton();
});

document.getElementById('password').addEventListener('input', function() {
    validatePassword();
    if (document.getElementById('confirmPassword').value !== '') {
        validatePasswordMatch();
    }
    updateSubmitButton();
});

document.getElementById('confirmPassword').addEventListener('input', function() {
    validatePasswordMatch();
    updateSubmitButton();
});

// Final validation before form submission
function validateForm() {
    const usernameValid = validateUsername();
    const emailValid = validateEmail();
    const phoneValid = validatePhone();
    const passwordValid = validatePassword();
    const passwordsMatch = validatePasswordMatch();

    return usernameValid && emailValid && phoneValid && passwordValid && passwordsMatch;
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