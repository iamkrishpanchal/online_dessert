<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');
if (!$conn) { 
    echo 'Connection failed: ' . mysqli_connect_error(); 
    exit; 
}

echo "=== All Tables ===\n";
$result = mysqli_query($conn, 'SHOW TABLES');
if ($result) {
    $tables = [];
    while($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    print_r($tables);
}

echo "\n=== Products Count by Vendor ===\n";

// Try different table names
foreach(['tbl_product', 'tbl_products', 'product', 'products'] as $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
    if ($check && mysqli_num_rows($check) > 0) {
        echo "\nUsing table: {$table}\n";
        $result = mysqli_query($conn, "SELECT vendor_id, COUNT(*) as cnt FROM {$table} GROUP BY vendor_id ORDER BY vendor_id");
        while($row = mysqli_fetch_assoc($result)) {
            echo "  Vendor {$row['vendor_id']}: {$row['cnt']} products\n";
        }
        break;
    }
}

mysqli_close($conn);
?>
