<?php
session_start();
include 'connection.php';

$error = '';
$success = '';
$reset_link = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Please enter your registered email address.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT rider_id FROM tbl_riders WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rider = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$rider) {
            $error = 'No rider account was found with that email address.';
        } else {
            $reset_token = bin2hex(random_bytes(32));

            ensureRiderResetColumns($conn);

            $updateStmt = mysqli_prepare($conn, 'UPDATE tbl_riders SET reset_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE rider_id = ?');
            mysqli_stmt_bind_param($updateStmt, 'si', $reset_token, $rider['rider_id']);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);

            $reset_link = sprintf(
                'http://%s%s/reset_password.php?token=%s',
                $_SERVER['HTTP_HOST'],
                dirname($_SERVER['SCRIPT_NAME']),
                urlencode($reset_token)
            );

            $success = 'A password reset link has been generated. Use the link below to reset your password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Rider</title>
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
        .alert a { color: #0c5cf6; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Forgot Password</h2>
        <p class="text-muted">Enter your rider email and we will generate a secure reset link.</p>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <?php if (!empty($reset_link)): ?>
                    <br>
                    <a href="<?php echo htmlspecialchars($reset_link); ?>">Click here to reset your password</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label">Registered Email</label>
                <input type="email" name="email" class="form-control" required placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Send Reset Link</button>
        </form>
        <div class="link-row">
            <a href="login.php">Back to login</a>
        </div>
    </div>
</div>
</body>
</html>
