<?php
session_start();
include 'connection.php';

// Ensure rider table exists (same schema as admin uses)
$tblRes = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_riders'");
if (!$tblRes || mysqli_num_rows($tblRes) === 0) {
    $create = "CREATE TABLE IF NOT EXISTS tbl_riders (
        rider_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        vehicle_type VARCHAR(50) DEFAULT NULL,
        vehicle_number VARCHAR(50) DEFAULT NULL,
        latitude DECIMAL(10,7) DEFAULT NULL,
        longitude DECIMAL(10,7) DEFAULT NULL,
        is_online TINYINT(1) NOT NULL DEFAULT 0,
        status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create);
}

// if already logged in, redirect to dashboard
if (!empty($_SESSION['rider_id'])) {
    header('Location: dashboard.php');
    exit;
}

$login_error = '';
$login_success = '';
$redirectTarget = '';
if (!empty($_GET['redirect'])) {
    $redirectTarget = trim($_GET['redirect']);
    $redirectTarget = filter_var($redirectTarget, FILTER_SANITIZE_URL);
    if (!preg_match('#^/#', $redirectTarget) || preg_match('#https?://#i', $redirectTarget) || strpos($redirectTarget, '..') !== false) {
        $redirectTarget = '';
    }
}

if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $login_success = 'Registration successful! Please login using your new account.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $login_error = 'Email and password are required.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT rider_id, name, email, password, status FROM tbl_riders WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rider = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$rider) {
            // Email not found in database
            $login_error = 'Email not found. Please check your email or register a new account.';
        } elseif ($rider['status'] !== 'active') {
            // Account is not active
            $login_error = 'Your account is ' . htmlspecialchars($rider['status']) . '. Please contact support.';
        } elseif (!password_verify($password, $rider['password'])) {
            // Password is incorrect
            $login_error = 'Password is incorrect. Please try again.';
        } else {
            // Success
            $_SESSION['rider_id'] = $rider['rider_id'];
            $_SESSION['rider_name'] = $rider['name'];
            $_SESSION['rider_email'] = $rider['email'];
            if (!empty($_POST['redirect'])) {
                $redirectPost = trim($_POST['redirect']);
                if (preg_match('#^/#', $redirectPost) && !preg_match('#https?://#i', $redirectPost) && strpos($redirectPost, '..') === false) {
                    header('Location: ' . $redirectPost);
                    exit;
                }
            }
            header('Location: dashboard.php');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Login - Dessert Magic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #fff; color: #111827; }
        .login-layout { display: grid; grid-template-columns: 1fr 1fr; min-height: 100vh; }
        .hero-pane { position: relative; overflow: hidden; background: linear-gradient(135deg, #d9ebff 0%, #f4f9ff 55%, #f3faff 100%); }
        .hero-pane::after { content: ''; position: absolute; inset: 0; background-image: url('../user/images/riderlogin.jpg'); background-size: cover; background-position: center center; opacity: 0.98; }
        .hero-pane .hero-mask { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(246,248,255,0.96), rgba(255,255,255,0.95)); }
        .form-pane { display: flex; align-items: center; justify-content: center; padding: 3rem 2rem; background: #f5f8ff; }
        .form-card { width: min(100%, 460px); background: #fff; border-radius: 1.5rem; box-shadow: 0 24px 55px rgba(20, 63, 138, 0.12); border: 1px solid rgba(85, 133, 222, 0.22); padding: 2.2rem 2.1rem; }
        .form-card h2 { font-size: 2rem; font-weight: 800; color: #0f2c64; margin-bottom: 1.3rem; text-align: center; }
        .form-card .form-control { height: 50px; border-radius: 999px; border: 1px solid #e8edff; background: #fefeff; font-size: 1rem; }
        .form-card .form-control:focus { border-color: #0c5cf6; box-shadow: 0 0 0 0.2rem rgba(57, 88, 209, 0.14); }
        .form-card .form-label { font-weight: 600; color: #1f3568; }
        .form-check-label { color: #1f3568; font-weight: 500; }
        .btn-primary { display: block; width: 100%; background: #00266d; border-color: #00266d; color: #fff; border-radius: 999px; height: 50px; font-size: 1.05rem; font-weight: 700; }
        .btn-primary:hover { background: #001f56; border-color: #001f56; }
        .form-footer { display: flex; justify-content: space-between; align-items: center; margin-top: .85rem; font-size: .95rem; }
        .form-footer a { color: #0d3bb1; text-decoration: none; font-weight: 600; }
        .social-actions { margin-top: 1.25rem; text-align: center; color: #1f3568; }
        .social-icons { margin-top: .45rem; display: flex; justify-content: center; gap: 0.7rem; }
        .social-btn { width: 44px; height: 44px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid rgba(15,29,66,0.18); background: #fff; box-shadow: 0 6px 14px rgba(3,27,86,0.12); }
        .social-btn svg { width: 20px; height: 20px; }
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
            color: #00266d;
            font-size: 1rem;
            padding: 0;
            line-height: 1;
        }
        .password-wrapper .toggle-password:focus {
            outline: 2px solid rgba(0, 38, 109, 0.25);
            outline-offset: 2px;
        }
        .auth-link { margin-top: 1rem; text-align: center; font-size: 0.95rem; }
        .auth-link a { color: #0c5cf6; text-decoration: none; font-weight: 600; }
        @media (max-width: 991px) { .login-layout { grid-template-columns: 1fr; } .hero-pane { min-height: 260px; } }
    </style>
</head>
<body>
<div class="login-layout">
    <div class="hero-pane"><div class="hero-mask"></div></div>
    <div class="form-pane">
        <div class="form-card">
            <h2>Login to continue</h2>
    <?php if (!empty($login_success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($login_success); ?></div>
    <?php endif; ?>
    <?php if ($login_error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($login_error); ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off" novalidate>
        <?php if (!empty($redirectTarget)): ?>
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget); ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label">Username/Email</label>
            <input type="email" name="email" class="form-control" required autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="Enter your email" />
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password" autocapitalize="off" autocorrect="off" spellcheck="false" placeholder="Enter password" />
                <button type="button" class="toggle-password" data-target="password" aria-label="Show password">👁</button>
            </div>
        </div>
        <div class="form-footer">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" />
                <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            <a href="forgot_password.php" class="text-decoration-none" style="color: #0d3bb1; font-weight: 600;">Forgot Password?</a>
        </div>
        <button type="submit" class="btn btn-primary mt-4">Login</button>
    </form>
</div>
</div>
</div>
<script>
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
</body>
</html>
