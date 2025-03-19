<?php 
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp'];
    
    // Check if the entered OTP matches the one stored in the session
    if ($entered_otp == $_SESSION['otp']) {
        // OTP is correct, proceed with registration
        include 'db.php';
        
        // Hash the password
        $hashed_password = password_hash($_SESSION['password'], PASSWORD_DEFAULT);
        
        // Insert user into the database
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, phoneno) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $_SESSION['username'], $_SESSION['email'], $hashed_password, $_SESSION['phone']);
        
        if ($stmt->execute()) {
            // Clear session data
            unset($_SESSION['otp']);
            unset($_SESSION['email']);
            unset($_SESSION['username']);
            unset($_SESSION['password']);
            unset($_SESSION['phone']);
            header("Location: login.php"); // Redirect to login page
            exit();
        } else {
            echo "<script>alert('Error: " . $stmt->error . "');</script>";
        }
    } else {
        echo "<script>alert('Invalid OTP. Please try again.');</script>";
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h1 {
            color: purple;
            font-size: 24px;
            margin-bottom: 10px;
        }

        p {
            color: #7a7a7a;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: center;
        }

        .form-group label {
            font-size: 16px;
            color: #555;
            margin-bottom: 10px;
            display: block;
            text-align: center;
        }

        .otp-input {
            width: 100%;
            max-width: 250px;
            padding: 15px;
            border: 2px solid #f0f0f0;
            border-radius: 4px;
            font-size: 20px;
            letter-spacing: 2px;
            text-align: center;
            margin: 0 auto;
            transition: border-color 0.3s;
        }

        .otp-input:focus {
            border-color: #9f1d92;
            outline: none;
            box-shadow: 0 0 0 3px rgba(159, 29, 146, 0.2);
        }

        .btn {
            width: 100%;
            max-width: 250px;
            padding: 12px;
            background-color: #9f1d92c1;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s;
        }

        .btn:hover {
            background-color: #f705ff;
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .resend {
            margin-top: 20px;
            font-size: 14px;
        }

        .resend a {
            color: #9f1d92;
            text-decoration: none;
            font-weight: bold;
        }

        .resend a:hover {
            text-decoration: underline;
        }

        .timer {
            margin-top: 15px;
            font-size: 14px;
            color: #666;
        }

        .back-to-signup {
            margin-top: 30px;
            font-size: 14px;
        }

        .back-to-signup a {
            color: #9f1d92;
            text-decoration: none;
        }

        .back-to-signup a:hover {
            text-decoration: underline;
        }

        .logo {
            margin-bottom: 20px;
            max-width: 150px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- You can add your logo here -->
         <img src="logo3.png" alt="Yards of Grace Logo" class="logo">
        
        <h1>Verify Your Email</h1>
        <p>We've sent a 6-digit verification code to <strong><?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?></strong>. Please enter the code below to complete your registration.</p>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="otp">Enter Verification Code</label>
                <input type="text" id="otp" name="otp" class="otp-input" required maxlength="6" autocomplete="off" 
                    pattern="[0-9]{6}" title="Please enter a 6-digit code">
            </div>
            
            <button type="submit" class="btn">Verify Account</button>
        </form>
        
        <div class="timer" id="timer">
            Resend code in <span id="countdown">00:30</span>
        </div>
        
        <div class="resend" id="resendOption" style="display: none;">
            Didn't receive the code? <a href="javascript:void(0);" onclick="resendOTP()">Resend Code</a>
        </div>
        
        <div class="back-to-signup">
            <a href="reg.php">← Back to Sign Up</a>
        </div>
    </div>

    <script>
        // Countdown timer for resend option
        let seconds = 30;
        const countdownElement = document.getElementById('countdown');
        const timerElement = document.getElementById('timer');
        const resendElement = document.getElementById('resendOption');
        
        function updateCountdown() {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            
            countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
            
            if (seconds <= 0) {
                clearInterval(countdownInterval);
                timerElement.style.display = 'none';
                resendElement.style.display = 'block';
            } else {
                seconds--;
            }
        }
        
        // Initial call and then every second
        updateCountdown();
        const countdownInterval = setInterval(updateCountdown, 1000);
        
        // Function to resend OTP (would need backend implementation)
        function resendOTP() {
            // Here you would typically make an AJAX call to your backend to resend the OTP
            alert("A new verification code has been sent to your email.");
            
            // Reset timer
            seconds = 30;
            timerElement.style.display = 'block';
            resendElement.style.display = 'none';
            updateCountdown();
            countdownInterval = setInterval(updateCountdown, 1000);
        }
        
        // Improve UX for OTP input
        const otpInput = document.getElementById('otp');
        otpInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 6);
        });
    </script>
</body>
</html>