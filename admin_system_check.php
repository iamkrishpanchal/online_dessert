<?php
/**
 * ADMIN ORDERS SYSTEM - DATABASE COMPATIBILITY CHECK
 * Verifies all required tables and columns exist for the admin orders system
 */

session_start();
include 'admin/connection.php';

$checks_passed = 0;
$checks_failed = 0;
$checks = [];

// Helper function to check table exists
function check_table_exists($table_name, $conn) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");
    return mysqli_num_rows($result) > 0;
}

// Helper function to check column exists
function check_column_exists($table_name, $column_name, $conn) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $table_name LIKE '$column_name'");
    return mysqli_num_rows($result) > 0;
}

// Helper function to record check
function record_check(&$checks, $name, $status, $details = '') {
    global $checks_passed, $checks_failed;
    $checks[] = [
        'name' => $name,
        'status' => $status,
        'details' => $details
    ];
    if ($status === 'pass') {
        $checks_passed++;
    } else {
        $checks_failed++;
    }
}

// START CHECKS

// 1. Check tbl_orders table
$table_exists = check_table_exists('tbl_orders', $conn);
if ($table_exists) {
    record_check($checks, '✓ tbl_orders exists', 'pass');
    
    // Check required columns
    $required_columns = [
        'order_id' => 'Order ID',
        'order_number' => 'Order Number',
        'user_id' => 'User ID',
        'vendor_id' => 'Vendor ID',
        'order_status' => 'Order Status',
        'payment_status' => 'Payment Status',
        'total_amount' => 'Total Amount',
        'created_at' => 'Created At'
    ];
    
    foreach ($required_columns as $col => $label) {
        if (check_column_exists('tbl_orders', $col, $conn)) {
            record_check($checks, "  ✓ Column: $label ($col)", 'pass');
        } else {
            record_check($checks, "  ✗ Missing Column: $label ($col)", 'fail', "Please add: ALTER TABLE tbl_orders ADD COLUMN $col ...");
        }
    }
} else {
    record_check($checks, '✗ tbl_orders missing', 'fail', 'Please create the tbl_orders table');
}

// 2. Check tbl_order_items table
$table_exists = check_table_exists('tbl_order_items', $conn);
if ($table_exists) {
    record_check($checks, '✓ tbl_order_items exists', 'pass');
    $required_columns = [
        'order_item_id' => 'Order Item ID',
        'order_id' => 'Order ID',
        'product_id' => 'Product ID',
        'quantity' => 'Quantity',
        'price' => 'Price'
    ];
    
    foreach ($required_columns as $col => $label) {
        if (check_column_exists('tbl_order_items', $col, $conn)) {
            record_check($checks, "  ✓ Column: $label ($col)", 'pass');
        } else {
            record_check($checks, "  ✗ Missing Column: $label ($col)", 'fail');
        }
    }
} else {
    record_check($checks, '✗ tbl_order_items missing', 'fail', 'Please create the tbl_order_items table');
}

// 3. Check tbl_notifications table
$table_exists = check_table_exists('tbl_notifications', $conn);
if ($table_exists) {
    record_check($checks, '✓ tbl_notifications exists', 'pass');
    $required_columns = [
        'notification_id' => 'Notification ID',
        'user_id' => 'User ID',
        'title' => 'Title',
        'message' => 'Message',
        'type' => 'Type'
    ];
    
    foreach ($required_columns as $col => $label) {
        if (check_column_exists('tbl_notifications', $col, $conn)) {
            record_check($checks, "  ✓ Column: $label ($col)", 'pass');
        } else {
            record_check($checks, "  ✗ Missing Column: $label ($col)", 'fail');
        }
    }
} else {
    record_check($checks, '✗ tbl_notifications missing', 'fail', 'Please create the tbl_notifications table');
}

// 4. Check tbl_users table
$table_exists = check_table_exists('tbl_users', $conn);
if ($table_exists) {
    record_check($checks, '✓ tbl_users exists', 'pass');
} else {
    record_check($checks, '✗ tbl_users missing', 'fail', 'User table is essential');
}

// 5. Check tbl_vendors table (for vendor_id reference)
$table_exists = check_table_exists('tbl_vendors', $conn);
if ($table_exists) {
    record_check($checks, '✓ tbl_vendors exists', 'pass');
} else {
    record_check($checks, '⚠ tbl_vendors missing', 'warning', 'Vendors feature may not work fully');
}

// 6. Check tbl_products table
$table_exists = check_table_exists('tbl_products', $conn);
if ($table_exists) {
    record_check($checks, '✓ tbl_products exists', 'pass');
} else {
    record_check($checks, '✗ tbl_products missing', 'fail', 'Products table is required for orders');
}

// 7. Check new admin files exist
$admin_files = [
    'orders_dashboard.php' => 'Orders Dashboard',
    'admin_update_order_status.php' => 'Order Status Update API',
    'order_details.php' => 'Order Details Page'
];

foreach ($admin_files as $file => $label) {
    $path = __DIR__ . '/admin/' . $file;
    if (file_exists($path)) {
        record_check($checks, "✓ $label exists", 'pass', "/admin/$file");
    } else {
        record_check($checks, "✗ $label missing", 'fail', "Please ensure $file is in admin folder");
    }
}

// End checks
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders System - Database Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .container-check { max-width: 800px; margin: 0 auto; }
        .check-item { 
            padding: 12px;
            margin-bottom: 8px;
            border-left: 4px solid #ccc;
            background: white;
            border-radius: 4px;
        }
        .check-item.pass {
            border-left-color: #28a745;
            background: #f0f9f6;
        }
        .check-item.fail {
            border-left-color: #dc3545;
            background: #fdf5f6;
        }
        .check-item.warning {
            border-left-color: #ffc107;
            background: #fffbf0;
        }
        .check-item.indent {
            padding-left: 30px;
            font-size: 0.95em;
        }
        .summary-badge {
            font-size: 2em;
            margin-right: 10px;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .status-card {
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .status-card.success {
            background: #d4edda;
            border: 2px solid #28a745;
        }
        .status-card.error {
            background: #f8d7da;
            border: 2px solid #dc3545;
        }
        .status-card.warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
        }
        .big-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
<div class="container-check">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="bi bi-check-circle"></i> Admin Orders System - Health Check</h1>
            <p class="text-muted">Database and file verification for the admin order management system</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="status-grid">
        <div class="status-card <?php echo $checks_failed == 0 ? 'success' : 'error'; ?>">
            <div class="big-number"><?php echo $checks_passed; ?></div>
            <div style="font-weight: 600;">Checks Passed</div>
        </div>
        <div class="status-card <?php echo $checks_failed == 0 ? 'success' : 'error'; ?>">
            <div class="big-number"><?php echo $checks_failed; ?></div>
            <div style="font-weight: 600;">Checks Failed</div>
        </div>
        <div class="status-card <?php echo $checks_failed == 0 ? 'success' : 'error'; ?>">
            <div class="big-number"><?php echo count($checks); ?></div>
            <div style="font-weight: 600;">Total Checks</div>
        </div>
    </div>

    <!-- Status Alert -->
    <div class="alert alert-<?php echo $checks_failed == 0 ? 'success' : 'danger'; ?>" role="alert">
        <strong>System Status:</strong>
        <?php if ($checks_failed == 0): ?>
            ✅ All checks passed! Your admin orders system is ready to use.
        <?php else: ?>
            ❌ <?php echo $checks_failed; ?> issue(s) found. Please resolve them before using the system.
        <?php endif; ?>
    </div>

    <!-- Detailed Results -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Detailed Check Results</h5>
        </div>
        <div class="card-body p-0">
            <?php foreach ($checks as $check): ?>
                <div class="check-item <?php 
                    echo $check['status'] === 'pass' ? 'pass' : ($check['status'] === 'fail' ? 'fail' : 'warning');
                    if (strpos($check['name'], '  ✓') !== false || strpos($check['name'], '  ✗') !== false) {
                        echo ' indent';
                    }
                ?>">
                    <strong><?php echo htmlspecialchars($check['name']); ?></strong>
                    <?php if (!empty($check['details'])): ?>
                        <div class="text-muted small mt-1">
                            <?php echo htmlspecialchars($check['details']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recommendations -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="ci-info-circle"></i> Next Steps</h5>
        </div>
        <div class="card-body">
            <?php if ($checks_failed == 0): ?>
                <div class="alert alert-success mb-3">
                    🎉 <strong>All systems go!</strong> You can now:
                </div>
                <ol>
                    <li>Login to the admin panel at <code>/admin/</code></li>
                    <li>Navigate to <strong>Orders → All Orders</strong> in the sidebar</li>
                    <li>View your dashboard with order statistics</li>
                    <li>Click on any order to manage its status</li>
                    <li>Update order statuses (Pending → Confirmed → Dispatched → Completed)</li>
                    <li>Check notifications in user profiles for status updates</li>
                </ol>
                
                <hr>
                
                <h6>Documentation Files:</h6>
                <ul class="small">
                    <li><strong>ADMIN_ORDERS_SYSTEM.md</strong> - Complete system documentation</li>
                    <li><strong>ADMIN_SETUP_VERIFICATION.md</strong> - Setup guide and verification checklist</li>
                    <li><strong>This page</strong> - Database compatibility check</li>
                </ul>
            <?php else: ?>
                <div class="alert alert-danger mb-3">
                    ⚠️ <strong>Issues found!</strong> Please resolve the failed checks before proceeding.
                </div>
                
                <h6>Priority Fixes:</h6>
                <ol>
                    <li><strong>Missing Tables:</strong> Create required database tables (see failed checks above)</li>
                    <li><strong>Missing Admin Files:</strong> Ensure all .php files are in the /admin/ folder</li>
                    <li><strong>Column Issues:</strong> Add any missing columns to existing tables</li>
                </ol>
                
                <h6 class="mt-3">Need Help?</h6>
                <p class="small">
                    Check the documentation files created:
                    <ul class="small">
                        <li>ADMIN_ORDERS_SYSTEM.md - Database setup section</li>
                        <li>ADMIN_SETUP_VERIFICATION.md - Troubleshooting section</li>
                    </ul>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Test Links -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Quick Access Links</h5>
        </div>
        <div class="card-body">
            <div class="btn-group-vertical w-100" role="group">
                <a href="admin/orders_dashboard.php" class="btn btn-primary" onclick="return confirm('This will redirect to admin panel. Are you logged in?')">
                    → Go to Orders Dashboard
                </a>
                <a href="admin/orders_dashboard.php?filter=Pending" class="btn btn-outline-primary">
                    → View Pending Orders
                </a>
                <a href="admin/orders_dashboard.php?filter=Confirmed" class="btn btn-outline-primary">
                    → View Confirmed Orders
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-4 text-center text-muted">
        <p>
            <small>
                Admin Order Management System<br>
                Database Check: <?php echo date('Y-m-d H:i:s'); ?><br>
                <a href="javascript:location.reload()" class="text-muted">Refresh Check</a>
            </small>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
