<?php
session_start();
include 'connection.php';

$error = '';
$success = '';
$valid_token = false;
$rider_id = 0;

function ensureRiderResetColumns($conn) {
    $cols = [
        'reset_token' => 'VARCHAR(255) DEFAULT NULL',
        'token_expiry' => 'DATETIME DEFAULT NULL'
    ];

    foreach ($cols as $column => $definition) {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM tbl_riders LIKE '$column'");
        if (!$check || mysqli_num_rows($check) === 0) {
            mysqli_query($conn, "ALTER TABLE tbl_riders ADD COLUMN `$column` $definition");
        }
    }
}

$tokenValue = '';
if (!empty($_GET['token'])) {
    $tokenValue = $_GET['token'];
} elseif (!empty($_POST['token'])) {
    $tokenValue = $_POST['token'];
}

if (empty($tokenValue)) {
    $error = 'Invalid reset link. Please request a new password reset.';
} else {
    $token = mysqli_real_escape_string($conn, $tokenValue);
    ensureRiderResetColumns($conn);

    $stmt = mysqli_prepare($conn, 'SELECT rider_id FROM tbl_riders WHERE reset_token = ? AND (token_expiry > NOW() OR token_expiry IS NULL) LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rider = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($rider) {
        $valid_token = true;
        $rider_id = $rider['rider_id'];
    } else {
        $error = 'Reset link is invalid or has expired. Please request a new one.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($new_password === '' || $confirm_password === '') {
        $error = 'Please fill in both password fields.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
        $updateStmt = mysqli_prepare($conn, 'UPDATE tbl_riders SET password = ?, reset_token = NULL, token_expiry = NULL WHERE rider_id = ?');
        mysqli_stmt_bind_param($updateStmt, 'si', $hashedPassword, $rider_id);
        if (mysqli_stmt_execute($updateStmt)) {
            $success = 'Your password has been reset successfully. Redirecting to login page...';
            header('refresh:3;url=login.php');
        } else {
            $error = 'Unable to reset your password. Please try again.';
        }
        mysqli_stmt_close($updateStmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Rider</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #f5f8ff; color: #111827; }
        .container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .card { width: 100%; max-width: 460px; border-radius: 1.5rem; padding: 2rem; border: 1px solid rgba(15, 29, 66, 0.08); background: #ffffff; box-shadow: 0 24px 55px rgba(20, 63, 138, 0.08); }
        .card h2 { margin-bottom: 1rem; color: #0f2c64; font-size: 2rem; font-weight: 800; }
        .form-control { height: 50px; border-radius: 999px; border: 1px solid #e8edff; background: #fefeff; }
        .form-control:focus { border-color: #0c5cf6; box-shadow: 0 0 0 0.2rem rgba(57, 88, 209, 0.14); }
        .btn-primary { width: 100%; border-radius: 999px; background: #00266d; border: #00266d; color: #fff; height: 50px; font-size: 1rem; font-weight: 700; }
        .btn-primary:hover { background: #001f56; }
        .link-row { margin-top: 1rem; text-align: center; }
        .link-row a { color: #0c5cf6; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Reset Your Password</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($valid_token && !$success): ?>
            <form method="post" novalidate>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($tokenValue); ?>">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required placeholder="Enter new password">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm your new password">
                </div>
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </form>
        <?php endif; ?>
        <div class="link-row">
            <a href="login.php">Back to login</a>
        </div>
    </div>
</div>
</body>
</html>
