<?php
include_once __DIR__ . '/../../session.php';
include 'connection.php';

// If vendor already has active session, send to dashboard
if (!empty($_SESSION['vendor_id'])) {
    header('Location: dashboard.php');
    exit;
}

function normalizeVendorStatus($rawStatus) {
    // Default to 'active' if status is NULL, empty, or not provided
    if ($rawStatus === null || $rawStatus === '') {
        return 'active';
    }
    
    $status = strtolower(trim($rawStatus));
    
    if ($status === '') {
        return 'active';
    }

    // Legacy/alternate truthy values - treat as active
    if (in_array($status, ['1', 'true', 'yes', 'active', 'approved'], true)) {
        return 'active';
    }

    // Only reject specific inactive statuses
    if (in_array($status, ['0', 'false', 'no', 'inactive', 'rejected'], true)) {
        return 'inactive';
    }

    // For other statuses like 'pending', 'suspended', return as-is
    return $status;
}

function getEffectiveVendorStatus($vendorRow) {
    $rawStatus = $vendorRow['status'] ?? null;
    
    // If no status or empty, treat as active
    if ($rawStatus === null || $rawStatus === '') {
        return 'active';
    }
    
    $status = normalizeVendorStatus($rawStatus);

    // Reject only explicitly inactive vendors
    if ($status === 'inactive') {
        return 'inactive';
    }

    // Pending approval
    if ($status === 'pending') {
        return 'pending';
    }

    // Suspended
    if ($status === 'suspended') {
        return 'suspended';
    }

    // Everything else is active
    return 'active';
}

$error = '';
if (isset($_POST['login'])) {
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
        // Create vendors table if it doesn't exist (in case)
        $createTableSql = "CREATE TABLE IF NOT EXISTS tbl_vendors (
            vendor_id INT AUTO_INCREMENT PRIMARY KEY,
            vendor_name VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        mysqli_query($conn, $createTableSql);

        // Prepared statement to avoid SQL injection
        $stmt = mysqli_prepare($conn, "SELECT vendor_id, vendor_name, password, status FROM tbl_vendors WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            $statusVal = getEffectiveVendorStatus($row);
            if ($statusVal !== 'active') {
                $error = 'Your account is ' . htmlspecialchars($statusVal) . '. Please contact admin for approval.';
            } else {
                // Use password_verify() to check hashed password
                if (password_verify($password, $row['password'])) {
                    // avoid session fixation
                    session_regenerate_id(true);
                    $_SESSION['vendor_id'] = $row['vendor_id'];
                    $_SESSION['vendor_name'] = $row['vendor_name'];
                    $_SESSION['islogin'] = true;

                    // Ensure online status columns exist and mark vendor online
                    $vid = (int)$row['vendor_id'];
                    $colCheck = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'is_online'");
                    if (!$colCheck || mysqli_num_rows($colCheck) == 0) {
                        @mysqli_query($conn, "ALTER TABLE tbl_vendors ADD COLUMN is_online TINYINT(1) DEFAULT 0");
                    }
                    $colCheck2 = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'last_active'");
                    if (!$colCheck2 || mysqli_num_rows($colCheck2) == 0) {
                        @mysqli_query($conn, "ALTER TABLE tbl_vendors ADD COLUMN last_active DATETIME DEFAULT NULL");
                    }
                    @mysqli_query($conn, "UPDATE tbl_vendors SET is_online = 1, last_active = NOW() WHERE vendor_id = $vid");
                    // Ensure audit table exists and insert login record
                    $createAudit = "CREATE TABLE IF NOT EXISTS vendor_audit (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    vendor_id INT NOT NULL,
                    action VARCHAR(32) NOT NULL,
                    ip VARCHAR(45) DEFAULT NULL,
                    user_agent TEXT DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    @mysqli_query($conn, $createAudit);
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $stmtLog = mysqli_prepare($conn, "INSERT INTO vendor_audit (vendor_id, action, ip, user_agent) VALUES (?, 'login', ?, ?)");
                    if ($stmtLog) { mysqli_stmt_bind_param($stmtLog, 'iss', $vid, $ip, $ua); @mysqli_stmt_execute($stmtLog); @mysqli_stmt_close($stmtLog); }
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Password is incorrect. Please try again.';
                }
            }
        } else {
            $error = 'Email not found. Please check your email or register a new account.';
        }
    } else {
        $error = implode(' ', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Login</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
    /* Canyon bakery-inspired login style */
    :root {
        --card-width: min(420px, 95vw);
        --card-radius: 2rem;
        --primary: #8a3b0f;
        --primary-soft: #ffc99c;
        --surface: #ffffffcc;
        --text: #4a3525;
        --shadow: 0 25px 45px rgba(20, 10, 8, 0.26);
    }

    @font-face {
        font-family: 'GRAVIS';
        src: url('dist/fonts/GRAVIS.ttf') format('truetype');
        font-weight: normal;
        font-style: normal;
        font-display: swap;
    }
    .gravis { font-family: 'GRAVIS', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

    body {
        margin: 0;
        min-height: 100vh;
        background: linear-gradient(180deg, rgba(255,255,255,0.45), rgba(255,255,255,0.45)), url('../../uploads/vendorbg.jpg') center/cover fixed no-repeat;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    }

    .login-card {
        width: 520px;
        max-width: 90vw;
        background: var(--surface);
        border-radius: var(--card-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        border: 1px solid rgba(255, 174, 110, 0.32);
        margin-left: 30px;
    }

    .password-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .password-wrapper .toggle-password {
        position: absolute;
        right: 0.8rem;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        cursor: pointer;
        color: #5d422e;
        font-size: 1.1rem;
        padding: 0;
        line-height: 1;
    }
    .password-wrapper .toggle-password:focus {
        outline: 2px solid rgba(138, 59, 15, 0.35);
        outline-offset: 2px;
    }

    .login-hero {
        background: url('https://images.unsplash.com/photo-1515444744559-ff64f27f6f6d?auto=format&fit=crop&w=800&q=80') center/cover no-repeat;
        min-height: 80px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.5);
        position: relative;
    }

    .login-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0, 0, 0, 0.26), rgba(255, 255, 255, 0.02));
    }

    .login-header {
        padding: 1.3rem 1rem;
        text-align: center;
        background-color: #ffffff;
    }
    .login-header h2 {
        margin: 0;
        font-size: 1.9rem;
        color: var(--primary);
        font-family: 'GRAVIS', sans-serif;
        letter-spacing: 0.02em;
    }

    .login-subtitle { color: #8f6230; font-weight: 500; margin-top: 0.35rem; }

    .login-body {
        padding: 1.1rem 1.1rem 1.5rem;
    }

    .login-card .form-group {
        margin-bottom: 0.9rem;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .login-card label {
        color: #76543a;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .login-card input.form-control {
        width: 100%;
        height: 44px;
        max-height:100%;
        border-radius: 0.85rem;
        border: 1px solid #e3bfa6;
        background: #fff;
        padding: 0.55rem 0.8rem;
        box-shadow: inset 0 1px 8px rgba(0, 0, 0, 0.04);
        color: #5d422e;
        font-size: 1rem;
    }
    .login-card input.form-control:focus {
        outline: 2px solid rgba(138, 59, 15, 0.28);
        border-color: #bd6f44;
    }

    .login-card .btn-primary,
    .login-card .btn-outline-primary {
        width: 100%;
        border-radius: 1rem;
        padding: 0.78rem 0.95rem;
        border: none;
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: 0.01em;
        cursor: pointer;
    }

    .btn-primary {
        background: linear-gradient(130deg, #d07234 0%, #a8471b 100%);
        color: #fff;
        box-shadow: 0 12px 20px rgba(146, 66, 22, 0.35);
    }

    .btn-outline-primary {
        background: transparent;
        border: 1px solid #d07336;
        color: #9c4f26;
    }

    .form-check {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        margin-bottom: 1rem;
        font-size: 0.95rem;
        color: #555;
    }

    .form-check input {
        width: 18px;
        height: 18px;
        margin: 0;
        cursor: pointer;
        accent-color: #8a3b0f;
        flex-shrink: 0;
    }

    .form-check label {
        margin: 0;
        font-weight: 500;
        cursor: pointer;
        color: #76543a;
    }

    .login-footer {
        text-align: center;
        margin-top: 0.4rem;
        font-size: 0.9rem;
        color: #75523a;
    }
    .login-footer a {
        color: #a8501e;
        text-decoration: none;
        font-weight: 600;
    }
    .login-footer a:hover { text-decoration: underline; }

    .error-box {
        background: #ffebe9;
        border: 1px solid #f6b3aa;
        color: #8f2815;
        border-radius: 0.8rem;
        padding: 0.8rem;
        font-size: 0.91rem;
        margin-bottom: 0.9rem;
    }

    @media (max-width: 450px) {
        :root { --card-width: min(100vw, 360px); }
        .login-card { border-radius: 1.4rem; }
    }
    </style>
</head>
<body>
    <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1rem;">
        <div class="login-card">
            <div class="login-hero"></div>
            <div class="login-header">
                <h2 class="gravis">👤 Vendor Login</h2>
                <br>
                <p class="login-subtitle">Sign in to manage your bakery menu and orders</p>
            </div>
            <div class="login-body">
                <form method="POST" autocomplete="off">
                    <?php if ($error) { ?>
                        <div class="error-box">
                            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php } ?>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" type="email" class="form-control" name="email" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input id="password" type="password" class="form-control" name="password" autocomplete="new-password" autocapitalize="off" autocorrect="off" spellcheck="false" required>
                            <button type="button" class="toggle-password" data-target="password" aria-label="Show password">👁</button>
                        </div>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" id="remember_me" name="remember_me">
                        <label for="remember_me">Remember me</label>
                    </div>

                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" name="login" value="Sign In">
                    </div>

                    <div class="form-group" style="margin-top: 0.15rem;">
                       
                        <a href="register.php" class="btn btn-outline-primary">Register here</a>
                    </div>

                    <div class="login-footer">
                        <a href="forgotPassword.php">Forgot Password?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toggle-password').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.dataset.target);
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
    });
    </script>
    <script src="dist/js/app.js"></script>
</body>
</html>
