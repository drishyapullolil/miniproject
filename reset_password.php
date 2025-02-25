<?php
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

function debug_to_file($message) {
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

if (!isset($_GET['token'])) {
    debug_to_file("No token provided in URL");
    die("No token provided");
}

$token = $_GET['token'];
debug_to_file("Checking token: " . $token);

// First, check if token exists at all
$checkStmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ?");
$checkStmt->bind_param("s", $token);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    debug_to_file("Token not found in database");
    die("Invalid reset token - token not found");
}

$userData = $checkResult->fetch_assoc();

// Check if token has expired
if ($userData['token_expiry'] !== null && strtotime($userData['token_expiry']) < time()) {
    debug_to_file("Token expired. Expiry time: " . $userData['token_expiry'] . ", Current time: " . date('Y-m-d H:i:s'));
    
    // Clear expired token
    $clearStmt = $conn->prepare("UPDATE users SET reset_token = NULL, token_expiry = NULL WHERE email = ?");
    $clearStmt->bind_param("s", $userData['email']);
    $clearStmt->execute();
    
    die("This reset token has expired. Please request a new one.");
}

$email = $userData['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['password']) || !isset($_POST['confirm_password'])) {
        $error = "Please fill in all fields";
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $error = "Passwords do not match";
    } else {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Update password and clear reset token
        $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE email = ?");
        $updateStmt->bind_param("ss", $password, $email);
        
        if ($updateStmt->execute()) {
            debug_to_file("Password updated successfully for email: " . $email);
            $success = "Password updated successfully! <a href='login.php'>Click here to login</a>";
        } else {
            debug_to_file("Error updating password: " . $conn->error);
            $error = "Error updating password";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        .reset-password-section {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f9f9f9;
            padding: 20px;
        }

        .reset-password-container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .reset-password-container h2 {
            font-size: 24px;
            color: #8d0f8f;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .violet-btn {
            background-color: #8d0f8f;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        .violet-btn:hover {
            background-color: #7a0d7c;
        }

        .login-link {
            margin-top: 20px;
        }

        .login-link a {
            color: #8d0f8f;
            text-decoration: none;
            font-size: 14px;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* New styles for validation feedback */
        .validation-feedback {
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .validation-feedback.show {
            display: block;
        }

        .requirements-list {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            padding-left: 20px;
        }

        .requirements-list li {
            margin-bottom: 3px;
        }

        .requirements-list li.valid {
            color: #155724;
        }

        .requirements-list li.invalid {
            color: #721c24;
        }
    </style>
</head>
<body>
    <section class="reset-password-section">
        <div class="reset-password-container">
            <h2>Reset Password</h2>
            <?php if (isset($error)): ?>
                <div class="message error">
                    <?php echo $error; ?>
                </div>
            <?php elseif (isset($success)): ?>
                <div class="message success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="resetPasswordForm">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required>
                    <ul class="requirements-list">
                        <li id="length">At least 8 characters long</li>
                        <li id="uppercase">Contains uppercase letter</li>
                        <li id="lowercase">Contains lowercase letter</li>
                        <li id="number">Contains number</li>
                        <li id="special">Contains special character</li>
                    </ul>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <div class="validation-feedback error" id="match-feedback"></div>
                </div>
                <button type="submit" class="violet-btn" id="submitBtn" disabled>Update Password</button>
            </form>
            
            <div class="login-link">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const submitBtn = document.getElementById('submitBtn');
            const matchFeedback = document.getElementById('match-feedback');
            const requirements = {
                length: document.getElementById('length'),
                uppercase: document.getElementById('uppercase'),
                lowercase: document.getElementById('lowercase'),
                number: document.getElementById('number'),
                special: document.getElementById('special')
            };

            // Password requirements
            const passwordRequirements = {
                length: password => password.length >= 8,
                uppercase: password => /[A-Z]/.test(password),
                lowercase: password => /[a-z]/.test(password),
                number: password => /[0-9]/.test(password),
                special: password => /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };

            function validatePassword() {
                const password = passwordInput.value;
                let isValid = true;

                // Check each requirement
                for (const [requirement, validator] of Object.entries(passwordRequirements)) {
                    const element = requirements[requirement];
                    const valid = validator(password);
                    element.className = valid ? 'valid' : 'invalid';
                    if (!valid) isValid = false;
                }

                return isValid;
            }

            function validatePasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const match = password === confirmPassword && confirmPassword !== '';

                matchFeedback.className = 'validation-feedback ' + (match ? 'success' : 'error');
                matchFeedback.style.display = confirmPassword ? 'block' : 'none';
                matchFeedback.textContent = match ? 'Passwords match!' : 'Passwords do not match';

                return match;
            }

            function updateSubmitButton() {
                const passwordValid = validatePassword();
                const passwordsMatch = validatePasswordMatch();
                submitBtn.disabled = !(passwordValid && passwordsMatch);
            }

            // Event listeners
            passwordInput.addEventListener('input', updateSubmitButton);
            confirmPasswordInput.addEventListener('input', updateSubmitButton);

            // Form submission
            document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
                if (!validatePassword() || !validatePasswordMatch()) {
                    e.preventDefault();
                    alert('Please ensure all password requirements are met and passwords match.');
                }
            });
        });
    </script>
</body>
</html>