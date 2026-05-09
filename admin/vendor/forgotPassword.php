<?php
session_start();
include 'connection.php';

$error = '';
$success = '';
$showResetForm = false;
$vendorEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['find_account'])) {
        // Step 1: Find account by email
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Please enter your email address.';
        } else {
            // Check if email exists
            $stmt = mysqli_prepare($conn, "SELECT vendor_id FROM tbl_vendors WHERE email = ?");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            
            if ($res && mysqli_num_rows($res) > 0) {
                $vendorEmail = $email;
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
        } else {
            // Check if email exists
            $stmt = mysqli_prepare($conn, "SELECT vendor_id FROM tbl_vendors WHERE email = ?");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            
            if ($res && mysqli_num_rows($res) > 0) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE tbl_vendors SET password = ? WHERE email = ?");
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
    <title>Forgot Password</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        body {
            background: linear-gradient(135deg, rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://img.freepik.com/free-photo/top-view-delicious-fruit-cakes-creamy-desserts-with-fruits-light-white-background-cream-tea-dessert-biscuit-cake-cookie_140725-116187.jpg') center/cover no-repeat;
            background-attachment: fixed;
        }

        .forgot-password-container {
            max-width: 450px;
            margin: 0 auto;
        }

        .title-box {
            background: #2563eb;
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .error-message {
            background-color: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 12px;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .success-message {
            background-color: #d1fae5;
            border: 1px solid #6ee7b7;
            color: #065f46;
            padding: 12px;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .form-divider {
            text-align: center;
            margin: 1rem 0;
            color: #6b7280;
        }

        .back-to-login {
            text-align: center;
            margin-top: 1rem;
        }

        .back-to-login a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
        }

        .back-to-login a:hover {
            text-decoration: underline;
        }

        @font-face {
            font-family: 'GRAVIS';
            src: url('dist/fonts/GRAVIS.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        .gravis {
            font-family: 'GRAVIS', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem;">
    <div class="forgot-password-container">
        <div class="intro-y text-center mb-4 title-box">
            <h2 class="text-2xl font-semibold text-white gravis">🔐 Reset Password</h2>
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
            <div class="intro-y box">
                <div class="flex items-center p-5 border-b border-slate-200/60 dark:border-darkmode-400">
                    <h2 class="font-medium text-base mr-auto">Find Your Account</h2>
                </div>
                <form method="POST" class="p-5">
                    <p class="text-sm text-gray-600 mb-4">
                        Enter the email address associated with your vendor account. We'll help you reset your password.
                    </p>
                    <div class="mt-4">
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
                    <div class="mt-5">
                        <button type="submit" name="find_account" class="btn btn-primary w-full">
                            Continue
                        </button>
                    </div>
                </form>
            </div>
        <?php } else { ?>
            <!-- Step 2: Reset password -->
            <div class="intro-y box">
                <div class="flex items-center p-5 border-b border-slate-200/60 dark:border-darkmode-400">
                    <h2 class="font-medium text-base mr-auto">Create New Password</h2>
                </div>
                <form method="POST" class="p-5">
                    <p class="text-sm text-gray-600 mb-4">
                        Email: <strong><?php echo htmlspecialchars($vendorEmail); ?></strong>
                    </p>
                    
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($vendorEmail); ?>">
                    
                    <div class="mt-4">
                        <label for="new_password" class="form-label">New Password</label>
                        <input 
                            id="new_password" 
                            type="password" 
                            class="form-control" 
                            name="new_password" 
                            placeholder="Enter new password"
                            required
                            minlength="6">
                        <small class="text-gray-500">Minimum 6 characters</small>
                    </div>

                    <div class="mt-4">
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

                    <div class="mt-5">
                        <button type="submit" name="reset_password" class="btn btn-primary w-full">
                            Reset Password
                        </button>
                    </div>

                    <div class="form-divider">or</div>

                    <button type="button" class="btn btn-secondary w-full" onclick="location.href='login.php'">
                        Back to Login
                    </button>
                </form>
            </div>
        <?php } ?>

        <?php if (!$success) { ?>
            <div class="back-to-login">
                <a href="login.php">← Back to Login</a>
            </div>
        <?php } ?>
    </div>

    <script src="dist/js/app.js"></script>
</body>
</html>
