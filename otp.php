<?php
session_start();

// Redirect if no temp registration data exists
if (!isset($_SESSION['temp_registration'])) {
    header("Location: reg.php");
    exit();
}

// Process OTP verification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp'];
    $stored_otp = $_SESSION['temp_registration']['otp'];
    $otp_expiry = $_SESSION['temp_registration']['otp_expiry'];
    
    // Check if OTP is expired
    if (time() > strtotime($otp_expiry)) {
        echo "<script>alert('OTP has expired. Please try registering again.'); window.location.href='reg.php';</script>";
        exit();
    }
    
    // Verify OTP
    if ($entered_otp == $stored_otp) {
        include 'db.php';
        
        // Extract user data from session
        $username = $_SESSION['temp_registration']['username'];
        $email = $_SESSION['temp_registration']['email'];
        $password = $_SESSION['temp_registration']['password'];
        $phone = $_SESSION['temp_registration']['phone'];
        $role = $_SESSION['temp_registration']['role'];
        
        // Insert the verified user
        $sql = "INSERT INTO users (username, email, password, phoneno, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            die("Error in SQL Preparation: " . $conn->error);
        }
        
        $stmt->bind_param("sssss", $username, $email, $password, $phone, $role);
        
        if ($stmt->execute()) {
            // Clean up the temp registration data
            unset($_SESSION['temp_registration']);
            
            // Set user session if you want to auto-login
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            
            echo "<script>alert('Registration successful! Welcome to Yards of Grace.'); window.location.href='login.php';</script>";
        } else {
            echo "<script>alert('Failed to complete registration. Please try again.'); window.location.href='reg.php';</script>";
        }
        
        $stmt->close();
        $conn->close();
    } else {
        echo "<script>alert('Invalid OTP. Please try again.');</script>";
    }
}

// Resend OTP functionality
if (isset($_GET['resend']) && $_GET['resend'] == 'true') {
    include 'db.php';
    
    // Generate new OTP
    function generateOTP($length = 6) {
        $characters = '0123456789';
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $otp;
    }
    
    // Function to send email with OTP
    function sendOTPEmail($email, $otp) {
        $subject = "Your New OTP for Yards of Grace Registration";
        $message = "Your new OTP for registration is: $otp\n\nThis code will expire in 10 minutes.";
        $headers = "From: noreply@yardsofgrace.com";
        
        return mail($email, $subject, $message, $headers);
    }
    
    $email = $_SESSION['temp_registration']['email'];
    $new_otp = generateOTP();
    $new_otp_expiry = date('Y-m-d H:i:s', time() + 600); // 10 minutes
    
    // Update session with new OTP
    $_SESSION['temp_registration']['otp'] = $new_otp;
    $_SESSION['temp_registration']['otp_expiry'] = $new_otp_expiry;
    
    // Send new OTP via email
    if (sendOTPEmail($email, $new_otp)) {
        echo "<script>alert('A new OTP has been sent to your email address.');</script>";
    } else {
        echo "<script>alert('Failed to send new OTP. Please try again.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Yards of Grace</title>
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
            width: 90%;
            max-width: 500px;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h1 {
            color: purple;
            margin-bottom: 20px;
        }

        p {
            color: #7a7a7a;
            margin-bottom: 30px;
        }

        .otp-input-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .otp-input {
            width: 50px;
            height: 50px;
            font-size: 24px;
            margin: 0 5px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background-color: #9f1d92c1;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 15px;
        }

        .btn:hover {
            background-color: #f705ff;
        }

        .resend {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }

        .resend:hover {
            text-decoration: underline;
        }

        .timer {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Verify Your Email</h1>
        <p>We've sent a 6-digit OTP to <?php echo isset($_SESSION['temp_registration']['email']) ? $_SESSION['temp_registration']['email'] : ''; ?>. Enter it below to complete your registration.</p>
        
        <form id="otpForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="otp-input-container">
                <input type="text" maxlength="6" name="otp" id="otp" class="otp-input" style="width: 200px;" placeholder="Enter 6-digit OTP" required>
            </div>
            
            <button type="submit" class="btn">Verify & Complete Registration</button>
        </form>
        
        <div id="timerContainer" class="timer">
            You can request a new OTP in <span id="timer">60</span> seconds
        </div>
        
        <a href="verify_otp.php?resend=true" class="resend" id="resendLink" style="display: none;">Didn't receive the code? Resend OTP</a>
    </div>

    <script>
        // Timer for resend OTP button
        let timeLeft = 60;
        const timerElement = document.getElementById('timer');
        const timerContainer = document.getElementById('timerContainer');
        const resendLink = document.getElementById('resendLink');
        
        function updateTimer() {
            if (timeLeft <= 0) {
                timerContainer.style.display = 'none';
                resendLink.style.display = 'block';
                return;
            }
            
            timeLeft--;
            timerElement.textContent = timeLeft;
            setTimeout(updateTimer, 1000);
        }
        
        // Start the timer
        updateTimer();
    </script>
</body>
</html>