<?php
include 'user/connection.php';

echo "<h2>Check for Orphaned Products</h2>";

// Get all products
$prod_query = "SELECT p.product_id, p.product_name, p.vendor_id FROM tbl_products p";
$prod_result = mysqli_query($conn, $prod_query);

// Get existing vendor IDs
$vendor_query = "SELECT vendor_id FROM tbl_vendors";
$vendor_result = mysqli_query($conn, $vendor_query);
$vendor_ids = [];
while ($v = mysqli_fetch_assoc($vendor_result)) {
    $vendor_ids[] = $v['vendor_id'];
}

echo "<h3>Existing Vendor IDs: " . implode(', ', $vendor_ids) . "</h3>";

// Find orphaned products
echo "<h3>Orphaned Products (vendors don't exist):</h3>";
$orphaned = [];
while ($p = mysqli_fetch_assoc($prod_result)) {
    if (!in_array($p['vendor_id'], $vendor_ids)) {
        $orphaned[] = $p;
        echo "- Product ID: " . $p['product_id'] . " | Name: " . $p['product_name'] . " | Vendor ID: " . $p['vendor_id'] . "<br>";
    }
}

if (empty($orphaned)) {
    echo "No orphaned products found.";
}

mysqli_close($conn);
?>
