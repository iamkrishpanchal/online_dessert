<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');
if (!$conn) { 
    echo 'Connection failed: ' . mysqli_connect_error(); 
    exit; 
}

echo "=== Columns in tbl_products ===\n\n";
$colsRes = mysqli_query($conn, "SHOW COLUMNS FROM tbl_products");
if ($colsRes) {
    while ($col = mysqli_fetch_assoc($colsRes)) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}

echo "\n=== Sample Products ===\n\n";
$query = "SELECT * FROM tbl_products LIMIT 10";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Product ID: " . ($row['product_id'] ?? 'N/A') . "\n";
        echo "  Name: " . ($row['product_name'] ?? 'N/A') . "\n";
        if (isset($row['categories_name'])) echo "  Category: {$row['categories_name']}\n";
        if (isset($row['category_id'])) echo "  Category ID: {$row['category_id']}\n";
        if (isset($row['vendors'])) echo "  Vendor: {$row['vendors']}\n";
        echo "\n";
    }
}

mysqli_close($conn);
?>
