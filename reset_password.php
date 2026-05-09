<?php
include __DIR__ . '/admin/vendor/connection.php';
session_start();

$error = '';
$success = '';
$valid_token = false;
$admin_id = '';

// Check if token is provided via GET or POST
$tokenValue = '';
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $tokenValue = $_GET['token'];
} elseif (isset($_POST['token']) && !empty($_POST['token'])) {
    $tokenValue = $_POST['token'];
}

if (empty($tokenValue)) {
    $error = "Invalid reset link. Please request a new password reset.";
} else {
    $token = mysqli_real_escape_string($conn, $tokenValue);

    $tokenExpiryExists = false;
    $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM tbl_admin LIKE 'token_expiry'");
    if ($colCheck && mysqli_num_rows($colCheck) > 0) {
        $tokenExpiryExists = true;
    }

    if ($tokenExpiryExists) {
        $token_query = "SELECT admin_id, admin_email FROM tbl_admin WHERE reset_token = '$token' AND (token_expiry > NOW() OR token_expiry IS NULL)";
    } else {
        $token_query = "SELECT admin_id, admin_email FROM tbl_admin WHERE reset_token = '$token'";
    }

    $token_result = mysqli_query($conn, $token_query);
    if ($token_result && mysqli_num_rows($token_result) > 0) {
        $admin = mysqli_fetch_assoc($token_result);
        $admin_id = $admin['admin_id'];
        $valid_token = true;
    } else {
        $error = "Reset link has expired or is invalid. Please request a new one.";
    }
}

// Handle password reset submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Update password and clear reset token
        $update_query = "UPDATE tbl_admin SET admin_password = '$new_password', reset_token = NULL, token_expiry = NULL WHERE admin_id = $admin_id";
        
        if (mysqli_query($conn, $update_query)) {
            $success = "Password reset successfully! Redirecting to login...";
            header("refresh:3;url=login.php");
        } else {
            $error = "Error resetting password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - Online Dessert Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #6d28a6 0%, #8b3a99 50%, #c84b8a 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .reset-container {
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .reset-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #c84b8a 0%, #8b3a99 100%);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 15px;
            font-size: 32px;
            color: white;
        }
        
        .reset-header h1 {
            font-size: 26px;
            color: #333;
            font-weight: 700;
        }
        
        .reset-header p {
            color: #666;
            font-size: 14px;
            margin-top: 8px;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c84b8a;
            font-size: 14px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #c84b8a;
            box-shadow: 0 0 0 3px rgba(200, 75, 138, 0.1);
            background: white;
        }
        
        .form-group input::placeholder {
            color: #999;
        }
        
        .password-requirements {
            background: #f0f0f0;
            padding: 12px;
            border-radius: 8px;
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .password-requirements li {
            margin-left: 20px;
        }
        
        .btn-reset {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #c84b8a 0%, #8b3a99 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(200, 75, 138, 0.3);
        }
        
        .btn-reset:active {
            transform: translateY(0);
        }
        
        .btn-reset:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-login a {
            color: #c84b8a;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .back-to-login a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .reset-container {
                padding: 30px 20px;
            }
            
            .reset-header h1 {
                font-size: 22px;
            }
            
            .form-group input {
                padding: 10px 12px;
                font-size: 13px;
            }
            
            .btn-reset {
                padding: 10px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <div class="reset-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h1>Reset Password</h1>
            <p>Create a new password for your account</p>
        </div>
        
        <?php if(isset($error) && !empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($success) && !empty($success)): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if($valid_token): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="new_password"><i class="fas fa-lock" style="margin-right: 8px; color: #c84b8a;"></i>New Password</label>
                    <input type="password" id="new_password" name="new_password" required placeholder="Enter new password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock" style="margin-right: 8px; color: #c84b8a;"></i>Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm new password">
                </div>
                
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($tokenValue); ?>">
                <div class="password-requirements">
                    <strong>Password Requirements:</strong>
                    <ul style="margin-top: 6px;">
                        <li>Minimum 6 characters</li>
                        <li>Must match confirmation password</li>
                    </ul>
                </div>
                
                <button type="submit" class="btn-reset">RESET PASSWORD</button>
            </form>
        <?php endif; ?>
        
        <div class="back-to-login">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>
