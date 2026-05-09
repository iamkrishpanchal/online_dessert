<?php
session_start();
include 'connection.php';

// Check if vendor is logged in
if (!isset($_SESSION['vendor_id'])) {
    header('Location: login.php');
    exit;
}

$vendor_id = (int)$_SESSION['vendor_id'];
$success_msg = '';
$error_msg = '';

// Handle password reset
if (isset($_POST['reset_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_msg = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error_msg = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error_msg = 'Password must be at least 6 characters long.';
    } else {
        // Verify current password
        $stmt = mysqli_prepare($conn, "SELECT password FROM tbl_vendors WHERE vendor_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $vendor_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $vendor = mysqli_fetch_assoc($res);
        
        if (!$vendor || !password_verify($current_password, $vendor['password'])) {
            $error_msg = 'Current password is incorrect.';
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE tbl_vendors SET password = ? WHERE vendor_id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $hashed_password, $vendor_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = 'Password reset successfully! Your new password is now active.';
                // Clear form
                $_POST = [];
            } else {
                $error_msg = 'Failed to reset password. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reset Password</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        .reset-password-container { max-width: 500px; margin: 40px auto; padding: 20px; }
        .reset-password-card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 30px; }
        .card-header { text-align: center; margin-bottom: 30px; }
        .card-header h1 { margin: 0; color: #333; font-size: 24px; }
        .card-header p { color: #666; margin-top: 8px; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; font-size: 14px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        .form-group input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; transition: all 0.3s; font-size: 14px; width: 100%; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-secondary { background: #e5e7eb; color: #333; text-decoration: none; display: inline-block; text-align: center; margin-top: 10px; }
        .btn-secondary:hover { background: #d1d5db; }
        .alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid; font-size: 14px; }
        .alert-success { background: #d1fae5; color: #065f46; border-left-color: #6ee7b7; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left-color: #fca5a5; }
        .password-requirements { background: #f3f4f6; padding: 12px; border-radius: 4px; margin-top: 15px; font-size: 13px; color: #666; }
        .requirement { margin: 5px 0; }
        .requirement.met { color: #10b981; }
    </style>
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        
        <div class="content" style="width: 100%;">
            <div class="reset-password-container">
                <div class="reset-password-card">
                    <div class="card-header">
                        <h1>🔒 Reset Password</h1>
                        <p>Change your password to keep your account secure</p>
                    </div>
                    
                    <?php if ($success_msg) { ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    <?php } else { ?>
                        <?php if ($error_msg) { ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
                        <?php } ?>
                        
                        <form method="POST">
                            <div class="form-group">
                                <label for="current_password">Current Password *</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password *</label>
                                <input type="password" id="new_password" name="new_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="password-requirements">
                                <strong>Password Requirements:</strong>
                                <div class="requirement" id="req-length">✓ At least 6 characters</div>
                                <div class="requirement" id="req-match">✓ Passwords match</div>
                            </div>
                            
                            <button type="submit" name="reset_password" class="btn btn-primary" style="margin-top: 25px;">Reset Password</button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="dist/js/app.js"></script>
    <script>
        // Real-time password validation
        document.getElementById('new_password')?.addEventListener('input', function() {
            const req = document.getElementById('req-length');
            if (this.value.length >= 6) {
                req.classList.add('met');
            } else {
                req.classList.remove('met');
            }
        });
        
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const newPass = document.getElementById('new_password').value;
            const req = document.getElementById('req-match');
            if (newPass && this.value === newPass) {
                req.classList.add('met');
            } else {
                req.classList.remove('met');
            }
        });
    </script>
</body>
</html>
