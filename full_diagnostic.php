<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');

echo "<h2>Database Diagnostic Report</h2>";

// 1. List all tables
echo "<h3>1. All Tables in Database:</h3>";
$result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
    echo $row[0] . "<br>";
}

// 2. Check which product table exists
echo "<h3>2. Products Table Check:</h3>";
$prodTable = null;
if (in_array('tbl_products', $tables)) {
    $prodTable = 'tbl_products';
    echo "Found: <strong>tbl_products</strong><br>";
} elseif (in_array('tbl_product', $tables)) {
    $prodTable = 'tbl_product';
    echo "Found: <strong>tbl_product</strong><br>";
}

if (!$prodTable) {
    echo "ERROR: No product table found!<br>";
    exit;
}

// 3. Show all columns in product table
echo "<h3>3. Columns in $prodTable:</h3>";
$cols = mysqli_query($conn, "SHOW COLUMNS FROM $prodTable");
$colNames = [];
while ($col = mysqli_fetch_assoc($cols)) {
    $colNames[] = $col['Field'];
    echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
}

// 4. Count total products
echo "<h3>4. Total Products:</h3>";
$result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $prodTable");
$row = mysqli_fetch_assoc($result);
echo "Total: " . $row['cnt'] . "<br>";

// 5. Sample products with all data
echo "<h3>5. Sample Products (First 5):</h3>";
$result = mysqli_query($conn, "SELECT * FROM $prodTable LIMIT 5");
if (mysqli_num_rows($result) > 0) {
    echo "<pre>";
    while ($row = mysqli_fetch_assoc($result)) {
        print_r($row);
        echo "---<br>";
    }
    echo "</pre>";
} else {
    echo "No products in table!<br>";
}

// 6. Check categories
echo "<h3>6. Categories:</h3>";
$result = mysqli_query($conn, "SELECT * FROM tbl_categories");
$cats = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cats[] = $row;
    echo "ID: {$row['categories_id']}, Name: {$row['categories_name']}<br>";
}
echo "Total: " . count($cats) . "<br>";

// 7. Find category column
echo "<h3>7. Category Column Detection:</h3>";
$categoryCol = null;
foreach(['category_id', 'catId', 'categories_id', 'cat_id', 'category'] as $c) {
    if (in_array($c, $colNames)) {
        $categoryCol = $c;
        echo "Found: <strong>$categoryCol</strong><br>";
        break;
    }
}

if (!$categoryCol) {
    echo "ERROR: No category column found!<br>";
    echo "Available columns: " . implode(", ", $colNames)<br>";
    exit;
}

// 8. Show products by category
echo "<h3>8. Products per Category:</h3>";
foreach ($cats as $cat) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $prodTable WHERE $categoryCol = {$cat['categories_id']}");
    $row = mysqli_fetch_assoc($result);
    echo "{$cat['categories_name']} (ID: {$cat['categories_id']}): {$row['cnt']} products<br>";
}

// 9. Show specific category (ID 15 - Pancake)
echo "<h3>9. Specific Test - Category ID 15 (Pancake):</h3>";
$result = mysqli_query($conn, "SELECT * FROM $prodTable WHERE $categoryCol = 15");
$count = mysqli_num_rows($result);
echo "Products found: <strong>$count</strong><br>";
if ($count > 0) {
    echo "<pre>";
    while ($row = mysqli_fetch_assoc($result)) {
        print_r($row);
        echo "---<br>";
    }
    echo "</pre>";
}

// 10. Show ALL products with their category values
echo "<h3>10. All Products and Their Category Values:</h3>";
$nameCol = in_array('product_name', $colNames) ? 'product_name' : (in_array('pname', $colNames) ? 'pname' : 'name');
$result = mysqli_query($conn, "SELECT product_id, $nameCol as name, $categoryCol as category_val FROM $prodTable");
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['product_id']}, Name: {$row['name']}, Category Value: {$row['category_val']}<br>";
}

mysqli_close($conn);
?>
