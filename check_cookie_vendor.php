<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');

$result = mysqli_query($conn, "SELECT p.product_id, p.product_name, p.vendor_id, v.shop_name, v.vendor_name FROM tbl_products p LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id WHERE p.product_name LIKE '%Chocolate Cookie%'");

echo "=== Chocolate Cookies ===\n\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "Product: " . $row['product_name'] . "\n";
    echo "  Product ID: " . $row['product_id'] . "\n";
    echo "  Vendor ID: " . $row['vendor_id'] . "\n";
    echo "  Shop Name: " . ($row['shop_name'] ?: $row['vendor_name'] ?: 'Unknown') . "\n";
    echo "\n";
}

mysqli_close($conn);
?>
