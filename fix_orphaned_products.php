<?php
include 'user/connection.php';

echo "<h2>Deleting Orphaned Products</h2>";

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

// Find orphaned product IDs
$orphaned_ids = [];
while ($p = mysqli_fetch_assoc($prod_result)) {
    if (!in_array($p['vendor_id'], $vendor_ids)) {
        $orphaned_ids[] = $p['product_id'];
    }
}

// Delete orphaned products
if (!empty($orphaned_ids)) {
    $ids_str = implode(',', $orphaned_ids);
    $delete_query = "DELETE FROM tbl_products WHERE product_id IN ($ids_str)";
    
    if (mysqli_query($conn, $delete_query)) {
        $affected = mysqli_affected_rows($conn);
        echo "<h3 style='color: green;'>SUCCESS - Deleted $affected orphaned products.</h3>";
        echo "<p>Deleted Product IDs: " . implode(', ', $orphaned_ids) . "</p>";
    } else {
        echo "<h3 style='color: red;'>ERROR: " . mysqli_error($conn) . "</h3>";
    }
} else {
    echo "<p>No orphaned products found.</p>";
}

// Verify remaining products
echo "<h3>Remaining Products:</h3>";
$check_query = "SELECT p.product_id, p.product_name, p.category_id, p.vendor_id, v.shop_name FROM tbl_products p LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id ORDER BY p.category_id";
$check_result = mysqli_query($conn, $check_query);

$by_category = [];
while ($p = mysqli_fetch_assoc($check_result)) {
    $cat = $p['category_id'];
    if (!isset($by_category[$cat])) {
        $by_category[$cat] = [];
    }
    $by_category[$cat][] = $p;
}

foreach ($by_category as $cat_id => $products) {
    echo "<h4>Category $cat_id:</h4>";
    foreach ($products as $p) {
        $vendor = $p['shop_name'] ?: "Unknown Vendor (ID: " . $p['vendor_id'] . ")";
        echo "- Product ID: " . $p['product_id'] . " | " . $p['product_name'] . " | Vendor: " . $vendor . "<br>";
    }
}

mysqli_close($conn);
?>
