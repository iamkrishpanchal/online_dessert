<?php
include 'user/connection.php';

// Test category ID 15 (Pancake from the screenshot)
$catId = 15;

echo "<h2>Testing Category: Pancake (ID: $catId)</h2>";

// 1. Check if table is tbl_product or tbl_products
$result1 = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product'");
$result2 = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_products'");

$table = null;
if (mysqli_num_rows($result1) > 0) {
    $table = 'tbl_product';
} elseif (mysqli_num_rows($result2) > 0) {
    $table = 'tbl_products';
}

echo "<p>Using table: <strong>$table</strong></p>";

if (!$table) {
    echo "ERROR: No product table found!";
    exit;
}

// 2. Get all columns
$cols = mysqli_query($conn, "SHOW COLUMNS FROM $table");
$columns = [];
echo "<p>Columns: ";
while ($col = mysqli_fetch_assoc($cols)) {
    $columns[] = $col['Field'];
    echo $col['Field'] . ", ";
}
echo "</p>";

// 3. Simple query: Get ALL products first
echo "<h3>ALL Products in database:</h3>";
$result = mysqli_query($conn, "SELECT * FROM $table");
$total = mysqli_num_rows($result);
echo "<p>Total products: <strong>$total</strong></p>";

if ($total == 0) {
    echo "<p style='color:red;'>ERROR: Table is empty!</p>";
    exit;
}

// Show first 10 products as-is
echo "<p>First 10 products (raw data):</p>";
$result = mysqli_query($conn, "SELECT * FROM $table LIMIT 10");
echo "<pre>";
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
    echo "---\n";
}
echo "</pre>";

// 4. Find which column links to categories
echo "<h3>Looking for category column...</h3>";
$categoryColumns = ['category_id', 'catId', 'categories_id', 'cat_id', 'category'];
$foundCol = null;

foreach ($categoryColumns as $col) {
    if (in_array($col, $columns)) {
        $foundCol = $col;
        echo "<p>Found category column: <strong>$col</strong></p>";
        
        // Show sample values
        $result = mysqli_query($conn, "SELECT DISTINCT $col FROM $table LIMIT 10");
        echo "<p>Sample values in $col: ";
        while ($row = mysqli_fetch_assoc($result)) {
            echo $row[$col] . ", ";
        }
        echo "</p>";
        break;
    }
}

if (!$foundCol) {
    echo "<p style='color:red;'>ERROR: No category column found!</p>";
    echo "<p>Available columns: " . implode(", ", $columns) . "</p>";
    exit;
}

// 5. Now try the actual query for category 15
echo "<h3>Query for category ID $catId:</h3>";
$query = "SELECT * FROM $table WHERE $foundCol = $catId";
echo "<p style='background:#eee; padding:10px;'><code>$query</code></p>";

$result = mysqli_query($conn, $query);
if (!$result) {
    echo "<p style='color:red;'>Query error: " . mysqli_error($conn) . "</p>";
} else {
    $count = mysqli_num_rows($result);
    echo "<p>Products found: <strong>$count</strong></p>";
    
    if ($count > 0) {
        echo "<pre>";
        while ($row = mysqli_fetch_assoc($result)) {
            print_r($row);
        }
        echo "</pre>";
    }
}

mysqli_close($conn);
?>
