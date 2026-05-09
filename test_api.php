<?php
/**
 * Quick API Test - Test Notification Endpoints
 */
session_start();
include 'user/connection.php';
header('Content-Type: text/html; charset=utf-8');

$user_id = $_SESSION['user_id'] ?? null;

// Get test results
$test_results = [];

// Test 1: User logged in
if ($user_id) {
    $test_results['login'] = ['status' => 'PASS', 'message' => 'User logged in (ID: ' . $user_id . ')'];
} else {
    $test_results['login'] = ['status' => 'FAIL', 'message' => 'User not logged in'];
}

// Test 2: Database connection
if ($conn && !mysqli_connect_errno()) {
    $test_results['db_connection'] = ['status' => 'PASS', 'message' => 'Database connected'];
} else {
    $test_results['db_connection'] = ['status' => 'FAIL', 'message' => 'Database connection failed'];
}

// Test 3: Notifications table exists
if ($conn) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_notifications'");
    if ($check && mysqli_num_rows($check) > 0) {
        $test_results['notifications_table'] = ['status' => 'PASS', 'message' => 'tbl_notifications exists'];
    } else {
        $test_results['notifications_table'] = ['status' => 'FAIL', 'message' => 'tbl_notifications not found'];
    }
}

// Test 4: Check columns
if ($conn) {
    $cols = mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications");
    $col_list = [];
    while ($col = mysqli_fetch_assoc($cols)) {
        $col_list[] = $col['Field'];
    }
    $test_results['columns'] = ['status' => 'PASS', 'message' => 'Columns: ' . implode(', ', $col_list)];
}

// Test 5: Count notifications for user
if ($conn && $user_id) {
    $count = mysqli_query($conn, "SELECT COUNT(*) as c FROM tbl_notifications WHERE user_id = $user_id");
    $row = mysqli_fetch_assoc($count);
    $total = $row['c'] ?? 0;
    
    if ($total > 0) {
        $test_results['notifications_exist'] = ['status' => 'PASS', 'message' => 'Found ' . $total . ' notification(s)'];
    } else {
        $test_results['notifications_exist'] = ['status' => 'WARN', 'message' => 'No notifications found (place an order first)'];
    }
}

// Test 6: Count orders for user
if ($conn && $user_id) {
    $orders = mysqli_query($conn, "SELECT COUNT(*) as c FROM tbl_orders WHERE user_id = $user_id");
    $row = mysqli_fetch_assoc($orders);
    $order_count = $row['c'] ?? 0;
    
    if ($order_count > 0) {
        $test_results['orders_exist'] = ['status' => 'PASS', 'message' => 'Found ' . $order_count . ' order(s)'];
    } else {
        $test_results['orders_exist'] = ['status' => 'WARN', 'message' => 'No orders found (place an order to generate notifications)'];
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Notification API Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #999;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .test-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid #ddd;
        }
        .test-item.pass {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .test-item.fail {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .test-item.warn {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .test-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        .test-content {
            flex: 1;
        }
        .test-title {
            font-weight: bold;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .test-message {
            color: #666;
            font-size: 13px;
            margin-top: 4px;
        }
        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: block;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }
        .info-box {
            background: #e7f3ff;
            border: 2px solid #b3d9ff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 25px;
            font-size: 13px;
            color: #0056b3;
            line-height: 1.6;
        }
        .info-box h3 {
            margin-bottom: 8px;
            color: #004085;
        }
        .info-box ul {
            margin-left: 20px;
        }
        .info-box li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Notification System Test</h1>
        <p class="subtitle">Quick API Endpoint Verification</p>
        
        <?php foreach ($test_results as $name => $result): ?>
            <div class="test-item <?php echo strtolower($result['status']); ?>">
                <div class="test-icon">
                    <?php 
                    if ($result['status'] === 'PASS') echo '✓';
                    elseif ($result['status'] === 'FAIL') echo '✗';
                    else echo '⚠';
                    ?>
                </div>
                <div class="test-content">
                    <div class="test-title"><?php echo htmlspecialchars($name); ?></div>
                    <div class="test-message"><?php echo htmlspecialchars($result['message']); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="info-box">
            <h3>📋 Next Steps</h3>
            <ul>
                <li><strong>Step 1:</strong> Click "Run Migration" to add new database columns</li>
                <li><strong>Step 2:</strong> Place a new order from the shop</li>
                <li><strong>Step 3:</strong> Update the order status to "Completed" or "Cancelled"</li>
                <li><strong>Step 4:</strong> Check the notification bell icon - it should show the notification</li>
                <li><strong>Step 5:</strong> Click the ✕ button to dismiss or wait 5 minutes for auto-dismiss</li>
            </ul>
        </div>
        
        <div class="actions">
            <a href="migrate_notification_dismissal.php" class="btn btn-primary">🔧 Run Migration</a>
            <a href="user/" class="btn btn-secondary">👤 Back to App</a>
        </div>
    </div>
</body>
</html>
