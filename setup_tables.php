<?php
/**
 * Database Table Setup Script
 * This script creates all necessary tables for the user-vendor-product system
 */

// Include database connection
if (file_exists(__DIR__ . '/admin/connection.php')) {
    include __DIR__ . '/admin/connection.php';
} else {
    die('Database connection file not found.');
}

if (!isset($conn) || !$conn) {
    die('Database connection failed.');
}

// Read and execute SQL file
$sqlFile = __DIR__ . '/create_user_vendor_tables.sql';

if (!file_exists($sqlFile)) {
    die("SQL file not found: " . $sqlFile);
}

$sqlContent = file_get_contents($sqlFile);

// Split SQL statements by semicolon and execute them
$statements = array_filter(array_map('trim', explode(';', $sqlContent)));
$successCount = 0;
$errorCount = 0;
$errors = [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 800px;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        .status {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .table-list {
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }
        .table-item {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-item:last-child {
            border-bottom: none;
        }
        .table-name {
            font-weight: 600;
            color: #333;
        }
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge.created {
            background: #d4edda;
            color: #155724;
        }
        .badge.exists {
            background: #cce5ff;
            color: #004085;
        }
        .badge.error {
            background: #f8d7da;
            color: #721c24;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .summary-item {
            margin: 10px 0;
            font-size: 16px;
        }
        .summary-item strong {
            color: #333;
        }
        .button-group {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        a, button {
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .error-details {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        .error-details h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .error-item {
            background: white;
            padding: 10px;
            margin: 5px 0;
            border-left: 3px solid #ffc107;
            font-family: monospace;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Tables Setup</h1>
        
        <?php
        // Execute SQL statements
        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;
            
            // Skip comment lines and directives
            if (strpos(trim($statement), '--') === 0 || strpos(trim($statement), '/*!') === 0) {
                continue;
            }
            
            if (mysqli_multi_query($conn, $statement . ';')) {
                do {
                    if ($result = mysqli_store_result($conn)) {
                        mysqli_free_result($result);
                    }
                } while (mysqli_next_result($conn));
                
                // Extract table name from statement
                if (preg_match('/CREATE TABLE[^`]*`([^`]+)`/i', $statement, $matches)) {
                    $successCount++;
                } elseif (preg_match('/CREATE OR REPLACE VIEW\s+`?(\w+)`?/i', $statement, $matches)) {
                    $successCount++;
                }
            } else {
                $errorCount++;
                $error = mysqli_error($conn);
                // Check if it's just a "table already exists" warning
                if (strpos($error, 'already exists') === false && strpos($error, '1050') === false) {
                    if (preg_match('/CREATE TABLE[^`]*`([^`]+)`/i', $statement, $matches)) {
                        $errors[] = "Table `{$matches[1]}`: " . $error;
                    } elseif (preg_match('/CREATE OR REPLACE VIEW\s+`?(\w+)`?/i', $statement, $matches)) {
                        $errors[] = "View `{$matches[1]}`: " . $error;
                    } else {
                        $errors[] = $error;
                    }
                }
            }
        }
        
        // Query to get all tables
        $tablesQuery = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME";
        $tablesResult = mysqli_query($conn, $tablesQuery);
        $tables = [];
        
        if ($tablesResult) {
            while ($row = mysqli_fetch_assoc($tablesResult)) {
                $tables[] = $row['TABLE_NAME'];
            }
        }
        
        // Check for views
        $viewsQuery = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='VIEW' AND TABLE_SCHEMA = DATABASE()";
        $viewsResult = mysqli_query($conn, $viewsQuery);
        $views = [];
        
        if ($viewsResult) {
            while ($row = mysqli_fetch_assoc($viewsResult)) {
                $views[] = $row['TABLE_NAME'];
            }
        }
        
        // Display status
        if (count($tables) > 0) {
            echo '<div class="status success">✓ Tables & Views Created Successfully!</div>';
        } else {
            echo '<div class="status error">✗ No tables were created. Check your connection.</div>';
        }
        
        if (count($errors) > 0) {
            echo '<div class="status warning">⚠ Some warnings occurred during setup:</div>';
            echo '<div class="error-details">';
            echo '<h3>Details:</h3>';
            foreach ($errors as $error) {
                echo '<div class="error-item">' . htmlspecialchars($error) . '</div>';
            }
            echo '</div>';
        }
        ?>
        
        <div class="table-list">
            <h3 style="padding: 15px 15px 10px; color: #333; font-size: 16px;">Tables Created:</h3>
            <?php 
            $expectedTables = [
                'tbl_users',
                'tbl_vendors',
                'tbl_categories',
                'tbl_product',
                'tbl_cart',
                'tbl_orders',
                'tbl_order_items',
                'tbl_reviews',
                'tbl_addresses',
                'tbl_favorites',
                'tbl_coupons',
                'tbl_notifications'
            ];
            
            foreach ($expectedTables as $table) {
                $exists = in_array($table, $tables);
                $status = $exists ? 'created' : 'error';
                $label = $exists ? '✓ Created' : '✗ Not Found';
                echo '<div class="table-item">';
                echo '<span class="table-name">' . $table . '</span>';
                echo '<span class="badge ' . $status . '">' . $label . '</span>';
                echo '</div>';
            }
            ?>
        </div>
        
        <?php if (count($views) > 0): ?>
        <div class="table-list" style="margin-top: 20px;">
            <h3 style="padding: 15px 15px 10px; color: #333; font-size: 16px;">Views Created:</h3>
            <?php 
            foreach ($views as $view) {
                echo '<div class="table-item">';
                echo '<span class="table-name">' . $view . '</span>';
                echo '<span class="badge created">✓ Created</span>';
                echo '</div>';
            }
            ?>
        </div>
        <?php endif; ?>
        
        <div class="summary">
            <div class="summary-item"><strong>Total Tables:</strong> <?php echo count(array_filter($expectedTables, function($t) use ($tables) { return in_array($t, $tables); })); ?> / <?php echo count($expectedTables); ?></div>
            <div class="summary-item"><strong>Views Created:</strong> <?php echo count($views); ?></div>
            <div class="summary-item"><strong>Setup Status:</strong> 
                <span style="color: <?php echo (count($errors) === 0  && count($tables) > 5) ? '#28a745' : '#ffc107'; ?>">
                    <?php echo (count($errors) === 0 && count($tables) > 5) ? '✓ Ready to Use' : '⚠ Review Required'; ?>
                </span>
            </div>
        </div>
        
        <div class="button-group">
            <a href="index.php" class="btn-primary">← Go to Home</a>
            <a href="admin/dashboard.php" class="btn-secondary">→ Admin Dashboard</a>
        </div>
    </div>
</body>
</html>
<?php
mysqli_close($conn);
?>
