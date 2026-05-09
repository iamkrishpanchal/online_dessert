<?php
/**
 * Migration: Add dismissal and auto-dismiss columns to tbl_notifications
 * Features:
 * - is_dismissed: User manually dismissed the notification (manual close button)
 * - auto_dismiss_at: Timestamp for auto-dismiss (5 mins for completed orders)
 */
session_start();
include 'user/connection.php';

$success_messages = [];
$error_messages = [];

// Check if columns exist and add if missing
$check_sql = "SHOW COLUMNS FROM tbl_notifications LIKE 'is_dismissed'";
$result = mysqli_query($conn, $check_sql);

if ($result && mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    $alter_sql = "ALTER TABLE tbl_notifications ADD COLUMN is_dismissed TINYINT(1) DEFAULT 0 AFTER status";
    if (mysqli_query($conn, $alter_sql)) {
        $success_messages[] = "✓ Added 'is_dismissed' column to tbl_notifications";
    } else {
        $error_messages[] = "✗ Failed to add 'is_dismissed' column: " . mysqli_error($conn);
    }
} else {
    $success_messages[] = "✓ 'is_dismissed' column already exists";
}

// Check for auto_dismiss_at column
$check_auto_dismiss = "SHOW COLUMNS FROM tbl_notifications LIKE 'auto_dismiss_at'";
$result2 = mysqli_query($conn, $check_auto_dismiss);

if ($result2 && mysqli_num_rows($result2) == 0) {
    // Column doesn't exist, add it
    $alter_sql2 = "ALTER TABLE tbl_notifications ADD COLUMN auto_dismiss_at DATETIME NULL AFTER is_dismissed";
    if (mysqli_query($conn, $alter_sql2)) {
        $success_messages[] = "✓ Added 'auto_dismiss_at' column to tbl_notifications";
    } else {
        $error_messages[] = "✗ Failed to add 'auto_dismiss_at' column: " . mysqli_error($conn);
    }
} else {
    $success_messages[] = "✓ 'auto_dismiss_at' column already exists";
}

mysqli_close($conn);

// Display results
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Notification Migration</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #27ae60; margin: 10px 0; padding: 10px; background: #e8f8f5; border-left: 4px solid #27ae60; }
        .error { color: #e74c3c; margin: 10px 0; padding: 10px; background: #fadbd8; border-left: 4px solid #e74c3c; }
        .status { font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Notification System Migration</h1>
        <p class="status">Database migration for notification dismissal and auto-dismiss features</p>
        
        <?php if (!empty($success_messages)): ?>
            <h2>✓ Success</h2>
            <?php foreach ($success_messages as $msg): ?>
                <div class="success"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($error_messages)): ?>
            <h2>✗ Errors</h2>
            <?php foreach ($error_messages as $msg): ?>
                <div class="error"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <p style="margin-top: 20px; color: #666;">
            <strong>Migration Complete!</strong> The database is now ready for notification dismissal features.
        </p>
        <p>
            <a href="index.php" style="color: #3498db; text-decoration: none;">← Back to Home</a>
        </p>
    </div>
</body>
</html>
