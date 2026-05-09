<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');

// Check tables
echo "=== TABLES ===\n";
$tables = mysqli_query($conn, 'SHOW TABLES');
while($row = mysqli_fetch_row($tables)) {
    echo $row[0] . "\n";
}

echo "\n=== PRODUCTS TABLE ===\n";
// Try tbl_products first
$columns = mysqli_query($conn, 'SHOW COLUMNS FROM tbl_products');
if($columns && mysqli_num_rows($columns) > 0) {
    echo "Found: tbl_products\nColumns: ";
    while($col = mysqli_fetch_assoc($columns)) {
        echo $col['Field'] . ", ";
    }
    $table = 'tbl_products';
} else {
    echo "tbl_products not found\n";
    $columns = mysqli_query($conn, 'SHOW COLUMNS FROM tbl_product');
    if($columns && mysqli_num_rows($columns) > 0) {
        echo "Found: tbl_product\nColumns: ";
        while($col = mysqli_fetch_assoc($columns)) {
            echo $col['Field'] . ", ";
        }
        $table = 'tbl_product';
    }
}

echo "\n\n=== PRODUCT COUNT ===\n";
$result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $table");
$row = mysqli_fetch_assoc($result);
echo "Total products: " . $row['cnt'] . "\n";

echo "\n=== SAMPLE PRODUCTS ===\n";
$result = mysqli_query($conn, "SELECT * FROM $table LIMIT 5");
while($row = mysqli_fetch_assoc($result)) {
    print_r($row);
    echo "---\n";
}

echo "\n=== CATEGORIES ===\n";
$result = mysqli_query($conn, "SELECT * FROM tbl_categories LIMIT 5");
echo "Sample categories:\n";
while($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['categories_id']}, Name: {$row['categories_name']}\n";
}

$cat_result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM tbl_categories");
$cat_row = mysqli_fetch_assoc($cat_result);
echo "Total categories: " . $cat_row['cnt'] . "\n";

mysqli_close($conn);
?>
