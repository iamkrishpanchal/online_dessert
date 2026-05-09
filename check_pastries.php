<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');
if (!$conn) { 
    echo 'Connection failed: ' . mysqli_connect_error(); 
    exit; 
}

echo "=== Products in Pastries Category (categories_id = 18) ===\n\n";

$query = "SELECT product_id, product_name, vendor_id, product_image FROM tbl_products WHERE categories_id = 18 ORDER BY product_id";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID: {$row['product_id']}\n";
        echo "  Name: {$row['product_name']}\n";
        echo "  Vendor ID: {$row['vendor_id']}\n";
        echo "  Image: {$row['product_image']}\n";
        echo "\n";
    }
} else {
    echo "No products found in Pastries category.\n";
}

echo "\n=== All Products in Database by Category ===\n\n";
$catQuery = "SELECT DISTINCT categories_id, categories_name FROM tbl_categories ORDER BY categories_id";
$catResult = mysqli_query($conn, $catQuery);

while ($cat = mysqli_fetch_assoc($catResult)) {
    $catId = $cat['categories_id'];
    $catName = $cat['categories_name'];
    $prodCount = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM tbl_products WHERE categories_id = {$catId}"));
    echo "Category ID {$catId} ({$catName}): {$prodCount} products\n";
}

mysqli_close($conn);
?>
