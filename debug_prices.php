<?php
include 'user/connection.php';

echo "<h2>Product Prices Debug</h2>";

// Check tbl_products table structure
echo "<h3>Table Structure:</h3>";
$cols = mysqli_query($conn, "SHOW COLUMNS FROM tbl_products");
while($col = mysqli_fetch_assoc($cols)) {
    echo $col['Field'] . " (" . $col['Type'] . ")<br>";
}

echo "<h3>Sample Products:</h3>";
$result = mysqli_query($conn, "SELECT product_id, product_name, price FROM tbl_products LIMIT 10");
echo "<pre>";
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
echo "</pre>";

mysqli_close($conn);
?>
