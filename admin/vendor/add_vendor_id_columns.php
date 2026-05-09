<?php
include 'connection.php';

// Array of ALTER TABLE statements
$alter_statements = [
    // Add vendor_id to tbl_products
    "ALTER TABLE tbl_products ADD COLUMN vendor_id INT DEFAULT 1 AFTER category_id",
    
    // Add vendor_id to tbl_categories
    "ALTER TABLE tbl_categories ADD COLUMN vendor_id INT DEFAULT 1 AFTER categories_status",
    
    // Add vendor_id to tbl_orders (if orders need filtering too)
    "ALTER TABLE tbl_orders ADD COLUMN vendor_id INT DEFAULT 1 AFTER customer_email",
];

$errors = [];
$success = [];

foreach ($alter_statements as $sql) {
    if (mysqli_query($conn, $sql)) {
        $success[] = "✓ " . substr($sql, 0, 50) . "...";
    } else {
        // Check if column already exists
        if (strpos(mysqli_error($conn), "Duplicate column name") !== false) {
            $success[] = "⚠ Column already exists: " . substr($sql, 0, 50) . "...";
        } else {
            $errors[] = "✗ " . substr($sql, 0, 50) . "... | Error: " . mysqli_error($conn);
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Migration - Add vendor_id Columns</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
        .warning { color: orange; margin: 10px 0; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Database Migration Results</h1>
    
    <?php if (!empty($success)): ?>
        <h2>Success:</h2>
        <?php foreach ($success as $msg): ?>
            <div class="success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <h2>Errors:</h2>
        <?php foreach ($errors as $msg): ?>
            <div class="error"><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <hr>
    <p><a href="javascript:history.back()">Go Back</a></p>
</body>
</html>
