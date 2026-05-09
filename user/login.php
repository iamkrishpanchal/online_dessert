<?php
session_start();
include 'connection.php';

$login_error = '';
$register_error = '';
$register_success = '';

// Preserve an optional redirect target so users return to the page they originally opened
$redirectTarget = '';
if (!empty($_GET['redirect'])) {
    $redirectTarget = trim($_GET['redirect']);
    $redirectTarget = filter_var($redirectTarget, FILTER_SANITIZE_URL);
    if (preg_match('#https?://#i', $redirectTarget) || strpos($redirectTarget, '//') === 0 || strpos($redirectTarget, '..') !== false) {
        $redirectTarget = '';
    }
}


// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $login_errors = [];

    if ($email === '') {
        $login_errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $login_errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $login_errors[] = 'Password is required.';
    }

    if (empty($login_errors)) {
        $query = "SELECT * FROM tbl_users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            // Verify password
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['user_name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Redirect to the original page after login when available
                if (!empty($_POST['redirect'])) {
                    $redirectPost = trim($_POST['redirect']);
                    if (!preg_match('#https?://#i', $redirectPost) && strpos($redirectPost, '//') !== 0 && strpos($redirectPost, '..') === false) {
                        header('Location: ' . $redirectPost);
                        exit;
                    }
                }

                header('Location: index.php');
                exit;
            } else {
                $login_errors[] = 'Password is incorrect. Please try again.';
            }
        } else {
            $login_errors[] = 'Email not found. Please register a new account.';
        }
    }

    if (!empty($login_errors)) {
        $login_error = implode(' ', $login_errors);
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['reg_email'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $register_errors = [];

    if ($name === '') {
        $register_errors[] = 'Name is required.';
    } elseif (strlen($name) < 2) {
        $register_errors[] = 'Full name must be at least 2 characters.';
    } elseif (strlen($name) > 100) {
        $register_errors[] = 'Full name must not exceed 100 characters.';
    }

    if ($email === '') {
        $register_errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_errors[] = 'Enter a valid email address.';
    }

    if ($password === '') {
        $register_errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $register_errors[] = 'Password must be at least 6 characters.';
    } elseif (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $register_errors[] = 'Password must contain at least one letter and one number.';
    }

    if ($confirm_password === '') {
        $register_errors[] = 'Confirm password is required.';
    } elseif ($password !== $confirm_password) {
        $register_errors[] = 'Passwords do not match.';
    }

    $phone_digits = preg_replace('/\\D+/', '', $phone);
    if ($phone !== '' && (strlen($phone_digits) < 10 || strlen($phone_digits) > 15)) {
        $register_errors[] = 'Phone must be 10-15 digits.';
    }

    if (empty($register_errors)) {
        // Check if email already exists
        $check_query = "SELECT user_id FROM tbl_users WHERE email = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, 's', $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $register_errors[] = 'Email already registered. Please login instead.';
        }
    }

    if (empty($register_errors)) {
        // Hash password and register
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $register_query = "INSERT INTO tbl_users (user_name, email, password, phone) 
                         VALUES (?, ?, ?, ?)";
        $register_stmt = mysqli_prepare($conn, $register_query);
        mysqli_stmt_bind_param($register_stmt, 'ssss', $name, $email, $hashed_password, $phone);
        
        if (mysqli_stmt_execute($register_stmt)) {
            $register_success = 'Registration successful! Please login with your credentials.';
            // Redirect back to the login page (do not auto-login)
            header('Location: login.php?registered=1');
            exit;
        } else {
            $register_errors[] = 'Registration failed. Please try again.';
        }
    }

    if (!empty($register_errors)) {
        $register_error = implode(' ', $register_errors);
    }
}

$activeTab = 'login';
if (isset($_GET['tab']) && $_GET['tab'] === 'register') {
    $activeTab = 'register';
} elseif (isset($_GET['registered'])) {
    $activeTab = 'login';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        html, body {
            background: url('https://i.pinimg.com/1200x/77/6c/e5/776ce566d0db770b0d03c1281492083f.jpg') no-repeat center center !important;
            background-size: cover !important;
            background-attachment: fixed !important;
            min-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        body {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
        }
        
        .auth-container {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.3);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            max-height: 700px;
            width: 100%;
            height: auto;
            overflow-y: auto;
            margin: auto; /* Center the card */
        }
        
        .auth-header {
            background: transparent;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        
        .auth-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 28px;
        }
        
        .nav-tabs {
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
        }
        
        .nav-tabs .nav-link {
            color: #666;
            border: none;
            font-weight: 600;
            padding: 15px 30px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link:hover {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .nav-tabs .nav-link.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: transparent;
        }
        
        .tab-content {
            padding: 0 30px 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #5c1f37 0%, #662d42 100%);
            border: none;
            padding: 10px 10px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 100%;
            color: #ffffff;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
            border: none;
            padding: 12px 15px;
        }
        
        .alert-danger {
            background-color: #ffe5e5;
            color: #d32f2f;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .form-text {
            font-size: 13px;
            color: #666;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            color: #764ba2;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 1.2rem;
            font-size: 0.95rem;
            color: #333;
        }
        
        .form-check input {
            width: 18px;
            height: 18px;
            margin: 0;
            cursor: pointer;
            accent-color: #5c1f37;
            flex-shrink: 0;
        }
        
        .form-check label {
            margin: 0;
            font-weight: 500;
            cursor: pointer;
            color: #333;
        }
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            color: #667eea;
            font-size: 1.05rem;
            padding: 0;
            line-height: 1;
        }
        .password-wrapper .toggle-password:focus {
            outline: 2px solid rgba(102, 126, 234, 0.35);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h2>Welcome to Dessert Magic</h2>
            <br>
            <h4 class="mb-0" style="font-size: 14px; opacity: 0.9;">Order Your Favorite Desserts<h4>
        </div>
        
        <div class="p-0">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activeTab === 'login') ? 'active' : ''; ?>" id="login-tab" data-bs-toggle="tab" href="#login" role="tab">Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activeTab === 'register') ? 'active' : ''; ?>" id="register-tab" data-bs-toggle="tab" href="#register" role="tab">Register</a>
                </li>
            </ul>
            
            <!-- Tab panes -->
            <div class="tab-content">
                <!-- Login Tab -->
                <div id="login" class="tab-pane fade <?php echo ($activeTab === 'login') ? 'show active' : ''; ?>" role="tabpanel">
                    <?php if ($login_error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($login_error); ?></div>
                    <?php endif; ?>
                    
                            <form method="POST" autocomplete="off">
                        <input type="hidden" name="action" value="login">
                        <?php if (!empty($redirectTarget)): ?>
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget); ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required autocomplete="off">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required autocomplete="off">
                                <button type="button" class="toggle-password" data-target="password" aria-label="Show password">👁</button>
                            </div>
                        </div>
                        
                        <div class="mb-3" style="text-align: right;">
                            <a href="forgot_password.php" style="color: #667eea; text-decoration: none; font-size: 13px; font-weight: 500;">Forgot Password?</a>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="remember_me" name="remember_me">
                            <label for="remember_me">Remember me</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Login</button>
                    </form>
                    
                            <div class="back-link">
                            <p class="mb-0">New here? <a href="login.php?tab=register<?php echo !empty($redirectTarget) ? '&redirect=' . urlencode($redirectTarget) : ''; ?>">Create an account</a></p>
                        </div>
                    </div>
                
                <!-- Register Tab -->
                <div id="register" class="tab-pane fade <?php echo ($activeTab === 'register') ? 'show active' : ''; ?>" role="tabpanel">
                    <?php if ($register_error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($register_error); ?></div>
                    <?php endif; ?>
                    <?php if ($register_success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($register_success); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="action" value="register">
                        
                        <div class="form-group">
                            <label for="name">Full Name <span style="color:red">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required minlength="2" maxlength="100">
                            <small class="form-text">2-100 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="reg_email">Email Address <span style="color:red">*</span></label>
                            <input type="email" class="form-control" id="reg_email" name="reg_email" placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['reg_email'] ?? ''); ?>" required autocomplete="off">
                            <small class="form-text">We'll never share your email</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number <span style="color:red">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter 10-15 digit phone number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" pattern="[0-9+\-() ]{10,20}" inputmode="tel">
                            <small class="form-text">10-15 digits. Accepts digits, +, -, (), and spaces</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="reg_password">Password <span style="color:red">*</span></label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control" id="reg_password" name="reg_password" placeholder="Create a strong password (min 6 chars, 1 letter + 1 number)" required minlength="6" autocomplete="new-password">
                                <button type="button" class="toggle-password" data-target="reg_password" aria-label="Show password">👁</button>
                            </div>
                            <small class="form-text">Must contain at least one letter and one number</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password <span style="color:red">*</span></label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required minlength="6" autocomplete="new-password">
                                <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show password">👁</button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Create Account</button>
                    </form>
                    
                    <div class="back-link">
                        <p class="mb-0">Already have an account? <a href="#login" data-bs-toggle="tab">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent browser autofill
        function clearAutofill() {
            const emailInputs = document.querySelectorAll('input[type="email"]');
            const passwordInputs = document.querySelectorAll('input[type="password"]');
            
            emailInputs.forEach(input => {
                input.value = '';
                input.setAttribute('autocomplete', 'off');
            });
            
            passwordInputs.forEach(input => {
                input.value = '';
                input.setAttribute('autocomplete', 'new-password');
            });
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

        document.addEventListener('DOMContentLoaded', function() {
            clearAutofill();
            initPasswordToggleButtons();
            
            // Clear autofill when page becomes visible
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    clearAutofill();
                }
            });
        });

        // Clear on tab switch
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', clearAutofill);
        });
    </script>
</body>
</html>
