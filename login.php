<?php
// Use absolute path to admin vendor connection to avoid include errors
include __DIR__ . '/admin/vendor/connection.php';
session_start();

$error = '';
$success = '';
$email = '';
$password = '';

// Handle forgot password submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forgot_password'])) {
    $reset_email = trim($_POST['reset_email'] ?? '');

    if ($reset_email === '') {
        $error = 'Email is required for password reset.';
    } elseif (!filter_var($reset_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address for password reset.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM tbl_admin WHERE admin_email = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $reset_email);
            mysqli_stmt_execute($stmt);
            $email_result = mysqli_stmt_get_result($stmt);

            if ($email_result && mysqli_num_rows($email_result) > 0) {
                $admin = mysqli_fetch_assoc($email_result);

                // Generate unique reset token
                $reset_token = bin2hex(random_bytes(32));

                // Ensure reset columns exist
                $resetTokenColumn = mysqli_query($conn, "SHOW COLUMNS FROM tbl_admin LIKE 'reset_token'");
                if (!$resetTokenColumn || mysqli_num_rows($resetTokenColumn) === 0) {
                    mysqli_query($conn, "ALTER TABLE tbl_admin ADD COLUMN reset_token VARCHAR(255)");
                }

                $tokenExpiryColumn = mysqli_query($conn, "SHOW COLUMNS FROM tbl_admin LIKE 'token_expiry'");
                if (!$tokenExpiryColumn || mysqli_num_rows($tokenExpiryColumn) === 0) {
                    mysqli_query($conn, "ALTER TABLE tbl_admin ADD COLUMN token_expiry DATETIME");
                }
                
                // Update admin with reset token and database-based expiry
                $update_query = "UPDATE tbl_admin SET reset_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE admin_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, 'si', $reset_token, $admin['admin_id']);
                    mysqli_stmt_execute($update_stmt);
                    if (mysqli_stmt_affected_rows($update_stmt) > 0) {
                        // For now, display reset link (in production, send this via email)
                        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/Sem-6%20Project/reset_password.php?token=" . urlencode($reset_token);
                        $success = "Reset link: <a href='$reset_link' style='color: #c84b8a;'>Click here to reset password</a>";
                    } else {
                        $error = "Unable to generate reset link at this time. Please try again later.";
                    }
                    mysqli_stmt_close($update_stmt);
                } else {
                    $error = 'Unable to generate reset link at this time. Please try again later.';
                }
            } else {
                $error = 'No account found with this email address.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Unable to process password reset. Please try again later.';
        }
    }
}

// Handle regular login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && !isset($_POST['forgot_password'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $errors = [];

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM tbl_admin WHERE admin_email = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $admin = mysqli_fetch_assoc($result);
                $storedPassword = $admin['admin_password'];

                if (password_verify($password, $storedPassword) || $password === $storedPassword) {
                    $_SESSION["islogin"] = true;
                    $_SESSION["admin_id"] = $admin['admin_id'];
                    $_SESSION["admin_email"] = $admin['admin_email'];
                    $_SESSION["admin_name"] = $admin['admin_name'];
                    header("Location: admin/dashboard.php");
                    exit;
                } else {
                    $errors[] = 'Password is incorrect. Please try again.';
                }
            } else {
                $errors[] = 'Email not found. Please check your email or contact support.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = 'Database error. Please try again later.';
        }
    }

    if (!empty($errors)) {
        $error = implode(' ', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - Online Dessert</title>
    <link rel="stylesheet" href="vendor/dist/css/app.css" />
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
            overflow: hidden;
        }
        
        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 900px;
            height: 90vh;
            max-height: 650px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #c84b8a 0%, #8b3a99 50%, #6d28a6 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            color: white;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -100px;
            left: -100px;
        }
        
        .login-left::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            bottom: -80px;
            right: -80px;
        }
        
        .login-left-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        
        .login-left-content h2 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .login-left-content p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .login-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 50px 40px;
            background: white;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .login-header .user-icon {
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
        
        .login-header h1 {
            font-size: 26px;
            color: #333;
            font-weight: 700;
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

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 20px;
            font-size: 0.95rem;
            color: #555;
        }

        .form-check input {
            width: 18px;
            height: 18px;
            margin: 0;
            cursor: pointer;
            accent-color: #c84b8a;
            flex-shrink: 0;
        }

        .form-check label {
            margin: 0;
            font-weight: 500;
            cursor: pointer;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 25px;
        }
        
        .forgot-password a {
            color: #c84b8a;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .forgot-password a:hover {
            color: #8b3a99;
        }
        
        .btn-login {
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
            margin-bottom: 20px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(200, 75, 138, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
            color: #999;
            font-size: 13px;
        }
        
        .divider .line {
            flex: 1;
            height: 1px;
            background: #ddd;
        }
        
        .divider span {
            margin: 0 10px;
        }
        
        .social-login {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        
        .social-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 1.5px solid #ddd;
            background: white;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #666;
            font-size: 20px;
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            border-color: #c84b8a;
            color: #c84b8a;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(200, 75, 138, 0.2);
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
        
        .success a {
            color: #c84b8a;
            font-weight: 600;
        }
        
        .form-container {
            display: none;
        }
        
        .form-container.active {
            display: block;
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 15px;
        }
        
        .back-to-login button {
            background: none;
            border: none;
            color: #c84b8a;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            text-decoration: underline;
            transition: color 0.3s ease;
        }
        
        .back-to-login button:hover {
            color: #8b3a99;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
                width: 95%;
                height: auto;
                max-height: none;
            }
            
            .login-left {
                display: none;
            }
            
            .login-right {
                padding: 40px 30px;
            }
            
            .login-header h1 {
                font-size: 22px;
            }
            
            .login-left-content h2 {
                font-size: 24px;
            }
        }
        
        @media (max-width: 480px) {
            .login-right {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 20px;
            }
            
            .form-group input {
                padding: 10px 12px;
                font-size: 13px;
            }
            
            .btn-login {
                padding: 10px;
                font-size: 14px;
            }
            
            .social-btn {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Side - Decorative -->
        <div class="login-left">
            <div class="login-left-content">
                <h2>Welcome Back</h2>
                <p>Admin Control Panel</p>
            </div>
        </div>
        
        <!-- Right Side - Form -->
        <div class="login-right">
            <div class="login-header">
                <div class="user-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h1 id="form-title">LOGIN</h1>
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
            
            <!-- Login Form -->
            <div id="login-form" class="form-container active">
                <form method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope" style="margin-right: 8px; color: #c84b8a;"></i>Email</label>
                        <input type="email" id="email" name="email" required placeholder="Enter your email" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock" style="margin-right: 8px; color: #c84b8a;"></i>Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required placeholder="Enter your password" autocomplete="new-password" autocapitalize="off" autocorrect="off" spellcheck="false">
                            <button type="button" class="toggle-password" data-target="password" aria-label="Show password">👁</button>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="remember_me" name="remember_me">
                        <label for="remember_me">Remember me</label>
                    </div>
                    
                    <div class="forgot-password">
                        <button type="button" onclick="toggleForm()" style="background: none; border: none; padding: 0; cursor: pointer;">Forgot Password?</button>
                    </div>
                    
                    <button type="submit" class="btn-login">LOGIN</button>
                </form>
            </div>
            
            <!-- Forgot Password Form -->
            <div id="forgot-form" class="form-container">
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="forgot_password" value="1">
                    <div class="form-group">
                        <label for="reset_email"><i class="fas fa-envelope" style="margin-right: 8px; color: #c84b8a;"></i>Email Address</label>
                        <input type="email" id="reset_email" name="reset_email" required placeholder="Enter your registered email" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
                    </div>
                    
                    <p style="font-size: 13px; color: #666; margin-bottom: 20px;">
                        <i class="fas fa-info-circle" style="color: #c84b8a;"></i> We'll send you a link to reset your password.
                    </p>
                    
                    <button type="submit" class="btn-login">SEND RESET LINK</button>
                    
                    <div class="back-to-login">
                        <button type="button" onclick="toggleForm()">Back to Login</button>
                    </div>
                </form>
            </div>
            
            <!-- <div class="divider">
                <div class="line"></div>
                <span>Or Login With</span>
                <div class="line"></div>
            </div>
            
            <div class="social-login">
                <button type="button" class="social-btn" title="Google" onclick="alert('Google login not configured yet')">
                    <i class="fab fa-google"></i>
                </button>
                <button type="button" class="social-btn" title="Facebook" onclick="alert('Facebook login not configured yet')">
                    <i class="fab fa-facebook-f"></i>
                </button>
            </div> -->
        </div>
    </div>
    
    <script>
        function toggleForm() {
            const loginForm = document.getElementById('login-form');
            const forgotForm = document.getElementById('forgot-form');
            const formTitle = document.getElementById('form-title');
            
            loginForm.classList.toggle('active');
            forgotForm.classList.toggle('active');
            
            if (loginForm.classList.contains('active')) {
                formTitle.textContent = 'LOGIN';
            } else {
                formTitle.textContent = 'RESET PASSWORD';
            }
        }

        function initPasswordToggleButtons() {
            document.querySelectorAll('.toggle-password').forEach(function (button) {
                button.addEventListener('click', function () {
                    const input = document.getElementById(button.dataset.target);
                    if (!input) return;
                    if (input.type === 'password') {
                        input.type = 'text';
                        button.textContent = '🙈';
                        button.setAttribute('aria-label', 'Hide password');
                    } else {
                        input.type = 'password';
                        button.textContent = '👁';
                        button.setAttribute('aria-label', 'Show password');
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', initPasswordToggleButtons);
    </script>
