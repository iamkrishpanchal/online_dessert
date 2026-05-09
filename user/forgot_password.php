<?php
session_start();
include 'connection.php';

// Ensure password reset columns exist
$checkColumns = mysqli_query($conn, "SHOW COLUMNS FROM tbl_users LIKE 'reset_token'");
if (!$checkColumns || mysqli_num_rows($checkColumns) === 0) {
    mysqli_query($conn, "ALTER TABLE tbl_users ADD COLUMN reset_token VARCHAR(100) DEFAULT NULL UNIQUE");
}

$error = '';
$success = '';
$showResetForm = false;
$userEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['find_account'])) {
        // Step 1: Find account by email
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Please enter your email address.';
        } else {
            // Check if email exists
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM tbl_users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            
            if ($res && mysqli_num_rows($res) > 0) {
                $userEmail = $email;
                $showResetForm = true;
            } else {
                $error = 'Email not found. Please enter the email associated with your account.';
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        // Step 2: Reset password
        $email = trim($_POST['email'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        if (empty($email) || empty($new_password) || empty($confirm_password)) {
            $error = 'All fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif (!preg_match('/[a-zA-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            $error = 'Password must contain at least one letter and one number.';
        } else {
            // Check if email exists
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM tbl_users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            
            if ($res && mysqli_num_rows($res) > 0) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE tbl_users SET password = ? WHERE email = ?");
                mysqli_stmt_bind_param($stmt, 'ss', $hashed_password, $email);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Password reset successfully! You can now login with your new password.';
                    $showResetForm = false;
                } else {
                    $error = 'Failed to reset password. Please try again.';
                }
            } else {
                $error = 'Email not found.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Dessert Magic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://i.pinimg.com/1200x/77/6c/e5/776ce566d0db770b0d03c1281492083f.jpg') center/cover no-repeat;
            background-attachment: fixed;
        }

        .forgot-password-container {
            max-width: 450px;
            margin: 0 auto;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .title-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .title-box h2 {
            color: white;
            font-weight: 700;
            font-size: 24px;
            margin: 0;
        }

        .box {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .box-header {
            border-bottom: 1px solid #e5e7eb;
            padding: 1.25rem;
            font-weight: 600;
            color: #333;
        }

        .box-content {
            padding: 1.5rem;
        }

        .error-message {
            background-color: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .success-message {
            background-color: #d1fae5;
            border: 1px solid #6ee7b7;
            color: #065f46;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .form-divider {
            text-align: center;
            margin: 1rem 0;
            color: #6b7280;
            font-size: 14px;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 14px;
            color: #333;
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 0.375rem;
            padding: 0.625rem 0.875rem;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-text {
            display: block;
            margin-top: 0.25rem;
            font-size: 12px;
            color: #6b7280;
        }

        .btn {
            padding: 0.625rem 1rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            width: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #333;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-top: 0.75rem;
        }

        .btn-secondary:hover {
            background: #d1d5db;
            color: #333;
        }

        .back-to-login {
            text-align: center;
            margin-top: 1rem;
        }

        .back-to-login a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .back-to-login a:hover {
            text-decoration: underline;
        }

        .form-group {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <div style="width: 100%;">
            <div class="title-box">
                <h2>🔐 Reset Password</h2>
            </div>

            <?php if ($error) { ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>

            <?php if ($success) { ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <div class="back-to-login">
                    <a href="login.php">← Back to Login</a>
                </div>
            <?php } elseif (!$showResetForm) { ?>
                <!-- Step 1: Email verification -->
                <div class="box">
                    <div class="box-header">
                        Find Your Account
                    </div>
                    <div class="box-content">
                        <p style="font-size: 14px; color: #666; margin-bottom: 1rem;">
                            Enter the email address associated with your account. We'll help you reset your password.
                        </p>
                        <form method="POST">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input 
                                    id="email" 
                                    type="email" 
                                    class="form-control" 
                                    name="email" 
                                    placeholder="Enter your email"
                                    required
                                    autocomplete="off">
                            </div>
                            <button type="submit" name="find_account" class="btn btn-primary">
                                Continue
                            </button>
                        </form>
                    </div>
                </div>
            <?php } else { ?>
                <!-- Step 2: Reset password -->
                <div class="box">
                    <div class="box-header">
                        Create New Password
                    </div>
                    <div class="box-content">
                        <p style="font-size: 14px; color: #666; margin-bottom: 1rem;">
                            Email: <strong><?php echo htmlspecialchars($userEmail); ?></strong>
                        </p>
                        
                        <form method="POST">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($userEmail); ?>">
                            
                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password</label>
                                <input 
                                    id="new_password" 
                                    type="password" 
                                    class="form-control" 
                                    name="new_password" 
                                    placeholder="Enter new password"
                                    required
                                    minlength="6">
                                <span class="form-text">Minimum 6 characters, must include letter & number</span>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input 
                                    id="confirm_password" 
                                    type="password" 
                                    class="form-control" 
                                    name="confirm_password" 
                                    placeholder="Confirm password"
                                    required
                                    minlength="6">
                            </div>

                            <button type="submit" name="reset_password" class="btn btn-primary">
                                Reset Password
                            </button>

                            <div class="form-divider">or</div>

                            <button type="button" class="btn btn-secondary" onclick="location.href='login.php'">
                                Back to Login
                            </button>
                        </form>
                    </div>
                </div>
            <?php } ?>

            <?php if (!$success && $showResetForm) { ?>
                <div class="back-to-login">
                    <a href="forgot_password.php">← Change Email</a>
                </div>
            <?php } elseif (!$success && !$showResetForm) { ?>
                <div class="back-to-login">
                    <a href="login.php">← Back to Login</a>
                </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>
