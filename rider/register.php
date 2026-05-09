<?php
session_start();
include 'connection.php';

// Ensure rider table exists
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

// If already logged in, redirect
if (!empty($_SESSION['rider_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($name === '' || $email === '' || $phone === '' || $password === '') {
        $errors[] = 'All fields are required.';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Full name must be at least 2 characters.';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Full name must not exceed 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    } elseif (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one letter and one number.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    $phone_digits = preg_replace('/\D+/', '', $phone);
    if (!empty($phone) && (strlen($phone_digits) < 10 || strlen($phone_digits) > 15)) {
        $errors[] = 'Phone must be 10-15 digits.';
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT rider_id FROM tbl_riders WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && mysqli_num_rows($res) > 0) {
            $errors[] = 'Email already registered. Please login.';
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = mysqli_prepare($conn, "INSERT INTO tbl_riders (name,email,phone,password,status) VALUES (?,?,?,?, 'active')");
        mysqli_stmt_bind_param($ins, 'ssss', $name, $email, $phone, $hash);
        if (mysqli_stmt_execute($ins)) {
            header('Location: login.php?registered=1');
            exit;
        }
        $errors[] = 'Failed to create account. Please try again.';
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Registration - Dessert Magic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7ff; }
        .auth-card { max-width: 500px; margin: 60px auto; padding: 28px; background: #fff; border-radius: 14px; box-shadow: 0 12px 32px rgba(0,0,0,0.12); }
        .auth-card h2 { margin-bottom: 18px; }
        .form-control:focus { border-color: #5c6ac4; box-shadow: 0 0 0 0.15rem rgba(92,106,196,0.25); }
        .btn-primary { background: #5c6ac4; border-color: #5c6ac4; }
        .btn-primary:hover { background: #4551b3; border-color: #4551b3; }
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
            color: #4551b3;
            font-size: 1rem;
            padding: 0;
            line-height: 1;
        }
        .password-wrapper .toggle-password:focus {
            outline: 2px solid rgba(69, 81, 179, 0.25);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
<div class="auth-card">
    <h2 class="text-center">Rider Registration</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul></div>
    <?php endif; ?>

    <form method="post" autocomplete="off" novalidate>
        <div class="mb-3">
            <label class="form-label">Full Name <span style="color:red">*</span></label>
            <input type="text" name="name" class="form-control" required minlength="2" maxlength="100" autocomplete="off" autocapitalize="words" autocorrect="off" spellcheck="false" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            <small class="form-text text-muted">2-100 characters</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Email <span style="color:red">*</span></label>
            <input type="email" name="email" class="form-control" required placeholder="Enter a valid email address" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            <small class="form-text text-muted">We'll never share your email</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone <span style="color:red">*</span></label>
            <input type="tel" name="phone" class="form-control" required inputmode="tel" pattern="[0-9+\-() ]{10,20}" autocomplete="off" autocapitalize="off" autocorrect="off" placeholder="Enter 10-15 digit phone number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            <small class="form-text text-muted">10-15 digits. Accepts digits, +, -, (), and spaces</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Password <span style="color:red">*</span></label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" class="form-control" required minlength="6" autocomplete="new-password" autocapitalize="off" autocorrect="off" spellcheck="false" placeholder="Create a strong password (min 6 chars, 1 letter + 1 number)">
                <button type="button" class="toggle-password" data-target="password" aria-label="Show password">👁</button>
            </div>
            <small class="form-text text-muted">Must contain at least one letter and one number</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm Password <span style="color:red">*</span></label>
            <div class="password-wrapper">
                <input type="password" name="confirm" id="confirm_password" class="form-control" required minlength="6" autocomplete="new-password" autocapitalize="off" autocorrect="off" spellcheck="false" placeholder="Re-enter your password">
                <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show password">👁</button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Create Account</button>
    </form>
    <div class="text-center mt-3">
        <a href="login.php">Already have an account? Login</a>
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
