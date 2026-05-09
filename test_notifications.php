<?php
/**
 * Notification System Diagnostic Tool
 * Helps identify issues with notifications not showing
 */
session_start();
include 'user/connection.php';
header('Content-Type: text/html; charset=utf-8');

$user_id = $_SESSION['user_id'] ?? null;
$diagnostics = [];
$errors = [];
$warnings = [];

// Check 1: User is logged in
if ($user_id) {
    $diagnostics[] = "✓ User logged in (ID: $user_id)";
} else {
    $errors[] = "✗ No user logged in - notifications require login";
    $user_id = null;
}

// Check 2: Database connection
if ($conn && !mysqli_connect_errno()) {
    $diagnostics[] = "✓ Database connection OK";
} else {
    $errors[] = "✗ Database connection failed: " . mysqli_connect_error();
}

// Check 3: tbl_notifications table exists
if ($conn) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_notifications'");
    if ($check && mysqli_num_rows($check) > 0) {
        $diagnostics[] = "✓ tbl_notifications table exists";
    } else {
        $errors[] = "✗ tbl_notifications table not found";
    }
}

// Check 4: Required columns
if ($conn && $user_id) {
    $columns_check = mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications");
    $columns = [];
    while ($col = mysqli_fetch_assoc($columns_check)) {
        $columns[] = $col['Field'];
    }
    
    $required = ['notification_id', 'user_id', 'order_id', 'title', 'message', 'status'];
    $missing = array_diff($required, $columns);
    
    if (empty($missing)) {
        $diagnostics[] = "✓ All required columns exist";
    } else {
        $errors[] = "✗ Missing columns: " . implode(', ', $missing);
    }
    
    // Check for optional columns
    if (in_array('is_dismissed', $columns)) {
        $diagnostics[] = "✓ is_dismissed column found";
    } else {
        $warnings[] = "⚠ is_dismissed column not found - auto-dismiss may not work";
    }
    
    if (in_array('auto_dismiss_at', $columns)) {
        $diagnostics[] = "✓ auto_dismiss_at column found";
    } else {
        $warnings[] = "⚠ auto_dismiss_at column not found - auto-dismiss may not work";
    }
}

// Check 5: Test notifications query
if ($conn && $user_id) {
    // Count total notifications for user
    $total_query = "SELECT COUNT(*) as total FROM tbl_notifications WHERE user_id = $user_id";
    $total_result = mysqli_query($conn, $total_query);
    $total_row = mysqli_fetch_assoc($total_result);
    $total_notifs = $total_row['total'] ?? 0;
    
    if ($total_notifs > 0) {
        $diagnostics[] = "✓ Found $total_notifs notification(s) for this user";
    } else {
        $warnings[] = "⚠ No notifications found for this user (place an order first)";
    }
    
    // Show active notifications count
    $active_query = "SELECT COUNT(*) as active FROM tbl_notifications n 
                     LEFT JOIN tbl_orders o ON n.order_id = o.order_id
                     WHERE n.user_id = $user_id
                     AND n.order_id IS NOT NULL
                     AND (o.order_status IS NULL OR o.order_status <> 'Completed')
                     AND n.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
    
    $active_result = mysqli_query($conn, $active_query);
    if ($active_result) {
        $active_row = mysqli_fetch_assoc($active_result);
        $active_notifs = $active_row['active'] ?? 0;
        $diagnostics[] = "✓ Found $active_notifs active notification(s)";
    } else {
        $errors[] = "✗ Error querying active notifications: " . mysqli_error($conn);
    }
    
    // Check if dismissed column filter is working
    $check_dismissed = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'is_dismissed'");
    if ($check_dismissed && mysqli_num_rows($check_dismissed) > 0) {
        $dismissed_query = "SELECT COUNT(*) as dismissed FROM tbl_notifications WHERE user_id = $user_id AND is_dismissed = 1";
        $dismissed_result = mysqli_query($conn, $dismissed_query);
        $dismissed_row = mysqli_fetch_assoc($dismissed_result);
        $dismissed_notifs = $dismissed_row['dismissed'] ?? 0;
        if ($dismissed_notifs > 0) {
            $diagnostics[] = "✓ Found $dismissed_notifs dismissed notification(s)";
        }
    }
}

// Check 6: Sample notification query
if ($conn && $user_id) {
    $sample_query = "SELECT n.notification_id, n.order_id, n.title, n.message, n.status, n.created_at
                     FROM tbl_notifications n
                     LEFT JOIN tbl_orders o ON n.order_id = o.order_id
                     WHERE n.user_id = $user_id
                     LIMIT 1";
    $sample_result = mysqli_query($conn, $sample_query);
    if ($sample_result && mysqli_num_rows($sample_result) > 0) {
        $sample = mysqli_fetch_assoc($sample_result);
        $diagnostics[] = "✓ Sample notification: " . htmlspecialchars($sample['title']);
    }
}

// Check 7: Orders for this user
if ($conn && $user_id) {
    $orders_query = "SELECT COUNT(*) as total, 
                     SUM(IF(order_status='Pending', 1, 0)) as pending,
                     SUM(IF(order_status='Confirmed', 1, 0)) as confirmed,
                     SUM(IF(order_status='Dispatched', 1, 0)) as dispatched,
                     SUM(IF(order_status='Completed', 1, 0)) as completed,
                     SUM(IF(order_status='Cancelled', 1, 0)) as cancelled
                     FROM tbl_orders WHERE user_id = $user_id";
    $orders_result = mysqli_query($conn, $orders_query);
    if ($orders_result) {
        $orders = mysqli_fetch_assoc($orders_result);
        if ($orders['total'] > 0) {
            $diagnostics[] = sprintf("✓ User has %d order(s): %d pending, %d confirmed, %d dispatched, %d completed, %d cancelled",
                $orders['total'], $orders['pending']??0, $orders['confirmed']??0, $orders['dispatched']??0, 
                $orders['completed']??0, $orders['cancelled']??0);
        } else {
            $warnings[] = "⚠ User has no orders - place an order first";
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Notification System Diagnostics</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #e63946;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #3498db;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .section h2 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .diagnostic {
            margin: 8px 0;
            padding: 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border-left: 3px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 3px solid #dc3545;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border-left: 3px solid #ffc107;
        }
        .action-box {
            background: #e7f3ff;
            border: 2px solid #b3d9ff;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .action-box h3 {
            margin-top: 0;
            color: #0056b3;
        }
        .action-box li {
            margin: 5px 0;
            color: #0056b3;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-danger {
            background: #e63946;
            color: white;
        }
        .btn-danger:hover {
            background: #d62828;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-warning {
            background: #ffc107;
            color: black;
        }
        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 12px;
        }
        table th, table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table th {
            background: #f0f0f0;
            font-weight: bold;
        }
        .status-ok { color: #27ae60; font-weight: bold; }
        .status-error { color: #e74c3c; font-weight: bold; }
        .status-warn { color: #f39c12; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Notification System Diagnostics</h1>
        
        <!-- Diagnostics -->
        <div class="section">
            <h2>✓ System Status</h2>
            <?php foreach ($diagnostics as $diag): ?>
                <div class="diagnostic success"><?php echo htmlspecialchars($diag); ?></div>
            <?php endforeach; ?>
        </div>
        
        <!-- Errors -->
        <?php if (!empty($errors)): ?>
        <div class="section">
            <h2>✗ Errors Found</h2>
            <?php foreach ($errors as $error): ?>
                <div class="diagnostic error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Warnings -->
        <?php if (!empty($warnings)): ?>
        <div class="section">
            <h2>⚠ Warnings</h2>
            <?php foreach ($warnings as $warning): ?>
                <div class="diagnostic warning"><?php echo htmlspecialchars($warning); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Recommendations -->
        <div class="action-box">
            <h3>📋 Recommended Actions</h3>
            <ol>
                <?php if (!$user_id): ?>
                    <li><strong>Login Required:</strong> Please <a href="user/login.php">login</a> to see notifications</li>
                <?php endif; ?>
                
                <?php if ($user_id && !empty($warnings)): ?>
                    <li><strong>Run Migration:</strong> Visit <a href="migrate_notification_dismissal.php" target="_blank">migrate_notification_dismissal.php</a> to add missing columns</li>
                <?php endif; ?>
                
                <li><strong>Place an Order:</strong> Create a new order to generate notifications</li>
                <li><strong>Update Order Status:</strong> Change order status to Completed/Cancelled to trigger notifications</li>
                <li><strong>Clear Browser Cache:</strong> Press Ctrl+Shift+Delete and clear cache</li>
                <li><strong>Hard Refresh:</strong> Press Ctrl+F5 in browser to reload all resources</li>
            </ol>
        </div>
        
        <!-- Action Buttons -->
        <div class="button-group">
            <a href="migrate_notification_dismissal.php" class="btn btn-primary">🔧 Run Migration</a>
            <a href="user/fetch_notifications.php" class="btn btn-primary">📡 Test API Endpoint</a>
            <a href="javascript:location.reload()" class="btn btn-warning">🔄 Refresh This Page</a>
            <a href="user/" class="btn btn-primary">👤 Back to User Area</a>
        </div>
        
        <!-- Debug Info -->
        <div class="section">
            <h2>🐛 Debug Information</h2>
            <table>
                <tr>
                    <th>Item</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>Current User ID</td>
                    <td><?php echo $user_id ? $user_id : '<span class="status-error">Not Logged In</span>'; ?></td>
                </tr>
                <tr>
                    <td>Session Status</td>
                    <td><span class="status-ok"><?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?></span></td>
                </tr>
                <tr>
                    <td>PHP Version</td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td>Server Time</td>
                    <td><?php echo date('Y-m-d H:i:s'); ?></td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
