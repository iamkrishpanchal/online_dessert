<?php
include 'user/connection.php';

echo "<h2>Test: Category View Simulation</h2>";

// Get first category
$cat_query = "SELECT categories_id, categories_name FROM tbl_categories WHERE categories_status = 1 LIMIT 1";
$result = mysqli_query($conn, $cat_query);
$category = mysqli_fetch_assoc($result);

if (!$category) {
    echo "No categories found!";
    exit;
}

echo "<p>Testing category: <strong>{$category['categories_name']}</strong> (ID: {$category['categories_id']})</p>";

// Find product table
$prodTable = null;
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_products'");
if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
    $prodTable = 'tbl_products';
} else {
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product'");
    if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
        $prodTable = 'tbl_product';
    }
}

echo "<p>Products table: <strong>$prodTable</strong></p>";

// Get columns
$colsRes = mysqli_query($conn, "SHOW COLUMNS FROM $prodTable");
$colNames = array();
while ($col = mysqli_fetch_assoc($colsRes)) {
    $colNames[] = $col['Field'];
}

// Find category column
$categoryCol = null;
foreach(['category_id', 'catId', 'categories_id', 'cat_id', 'category'] as $c) {
    if (in_array($c, $colNames)) {
        $categoryCol = $c;
        break;
    }
}

echo "<p>Category column: <strong>$categoryCol</strong></p>";

if (!$categoryCol) {
    echo "<p style='color:red;'>ERROR: No category column found!</p>";
    exit;
}

// Query products
$pquery = "SELECT * FROM $prodTable WHERE $categoryCol = {$category['categories_id']} LIMIT 10";
echo "<p style='background:#eee; padding:10px;'>Query: <code>$pquery</code></p>";

$pres = mysqli_query($conn, $pquery);
$products = mysqli_fetch_all($pres, MYSQLI_ASSOC);

echo "<p>Products found: <strong>" . count($products) . "</strong></p>";

if (count($products) > 0) {
    echo "<h3>Sample Products:</h3>";
    foreach($products as $p) {
        echo "<pre>";
        print_r($p);
        echo "</pre>";
    }
} else {
    echo "<p style='color:red;'>No products found for this category!</p>";
    
    echo "<h3>Checking all products with their categories:</h3>";
    $allProds = mysqli_query($conn, "SELECT product_id, " . (in_array('product_name', $colNames) ? 'product_name' : 'pname') . " as name, $categoryCol as cat_id FROM $prodTable LIMIT 20");
    while ($p = mysqli_fetch_assoc($allProds)) {
        echo "Product {$p['product_id']}: {$p['name']} - Category ID: {$p['cat_id']}<br>";
    }
}

mysqli_close($conn);
?>
