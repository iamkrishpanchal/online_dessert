<?php
/**
 * Migration: Add password reset columns to tbl_users
 * - reset_token: Unique token for password reset
 * - reset_token_expires: Expiration timestamp for the reset token
 */

include 'connection.php';

$success_messages = [];
$error_messages = [];

// Check if reset_token column exists
$check_token = "SHOW COLUMNS FROM tbl_users LIKE 'reset_token'";
$result = mysqli_query($conn, $check_token);

if (!$result || mysqli_num_rows($result) === 0) {
    $alter_token = "ALTER TABLE tbl_users ADD COLUMN reset_token VARCHAR(100) DEFAULT NULL UNIQUE";
    if (mysqli_query($conn, $alter_token)) {
        $success_messages[] = "✓ Added 'reset_token' column to tbl_users";
    } else {
        $error_messages[] = "✗ Failed to add 'reset_token' column: " . mysqli_error($conn);
    }
} else {
    $success_messages[] = "✓ 'reset_token' column already exists in tbl_users";
}

// Check if reset_token_expires column exists
$check_expires = "SHOW COLUMNS FROM tbl_users LIKE 'reset_token_expires'";
$result = mysqli_query($conn, $check_expires);

if (!$result || mysqli_num_rows($result) === 0) {
    $alter_expires = "ALTER TABLE tbl_users ADD COLUMN reset_token_expires DATETIME DEFAULT NULL";
    if (mysqli_query($conn, $alter_expires)) {
        $success_messages[] = "✓ Added 'reset_token_expires' column to tbl_users";
    } else {
        $error_messages[] = "✗ Failed to add 'reset_token_expires' column: " . mysqli_error($conn);
    }
} else {
    $success_messages[] = "✓ 'reset_token_expires' column already exists in tbl_users";
}

// Add index on reset_token
$check_index = "SHOW INDEX FROM tbl_users WHERE Column_name = 'reset_token'";
$result = mysqli_query($conn, $check_index);

if (!$result || mysqli_num_rows($result) === 0) {
    $alter_index = "ALTER TABLE tbl_users ADD INDEX reset_token_idx (reset_token)";
    if (mysqli_query($conn, $alter_index)) {
        $success_messages[] = "✓ Added index on 'reset_token' column";
    } else {
        $error_messages[] = "✗ Failed to add index: " . mysqli_error($conn);
    }
} else {
    $success_messages[] = "✓ Index on 'reset_token' already exists";
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Migration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .migration-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        .migration-container h1 {
            color: #667eea;
            margin-bottom: 30px;
            font-weight: 700;
        }
        .success-item, .error-item {
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            font-weight: 500;
        }
        .success-item {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .error-item {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .migration-status {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        .back-link {
            margin-top: 20px;
            text-align: center;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="migration-container">
        <h1>🔐 Password Reset Migration</h1>
        
        <div class="results">
            <?php if (!empty($success_messages)): ?>
                <h5 class="mb-3">✅ Successful:</h5>
                <?php foreach ($success_messages as $msg): ?>
                    <div class="success-item"><?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($error_messages)): ?>
                <h5 class="mb-3 mt-4">❌ Errors:</h5>
                <?php foreach ($error_messages as $msg): ?>
                    <div class="error-item"><?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="migration-status">
            <?php if (empty($error_messages)): ?>
                <h5 style="color: #28a745;">✓ Migration Complete!</h5>
                <p class="mb-0" style="color: #666;">Password reset feature is now ready to use.</p>
            <?php else: ?>
                <h5 style="color: #dc3545;">⚠️ Migration Completed with Issues</h5>
                <p class="mb-0" style="color: #666;">Please check the errors above.</p>
            <?php endif; ?>
        </div>
        
        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>
