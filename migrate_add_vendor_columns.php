<?php
// migrate_add_vendor_columns.php
// Run this from the project root in your browser or CLI to add missing vendor columns.

include __DIR__ . '/user/connection.php';

if (!$conn) {
    echo "Database connection failed: " . mysqli_connect_error();
    exit;
}

$needed = [
    'city' => "ALTER TABLE tbl_vendors ADD COLUMN city VARCHAR(100) NULL",
    'image_path' => "ALTER TABLE tbl_vendors ADD COLUMN image_path VARCHAR(255) NULL",
    'status' => "ALTER TABLE tbl_vendors ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'",
    // vendor-level discount percentage (applied to all products)
    'vendor_discount_percent' => "ALTER TABLE tbl_vendors ADD COLUMN vendor_discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00",
];

$existing = [];
$res = mysqli_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_vendors'");
while ($row = mysqli_fetch_assoc($res)) {
    $existing[$row['COLUMN_NAME']] = true;
}

$added = [];
foreach ($needed as $col => $sql) {
    if (!isset($existing[$col])) {
        if (mysqli_query($conn, $sql)) {
            $added[] = $col;
        } else {
            echo "Failed to add column $col: " . mysqli_error($conn) . "<br>";
        }
    }
}

if (!empty($added)) {
    echo "Added columns: " . implode(', ', $added) . "<br>";
} else {
    echo "No columns needed adding. All present.<br>";
}

echo "Migration complete.";

?>
