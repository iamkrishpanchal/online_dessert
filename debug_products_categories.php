<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');

echo "<h2>Products and Categories Debug</h2>";

// Find the products table
$prodTable = 'tbl_products';
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_products'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
  $prodTable = 'tbl_product';
}
echo "<p>Products table: <strong>$prodTable</strong></p>";

// Get columns
$colsRes = mysqli_query($conn, "SHOW COLUMNS FROM $prodTable");
$colNames = array();
if ($colsRes) {
    while ($col = mysqli_fetch_assoc($colsRes)) {
      $colNames[] = $col['Field'];
    }
}
echo "<p>Columns: " . implode(", ", $colNames) . "</p>";

// Find category column
$categoryCol = null;
foreach(['category_id', 'catId', 'categories_id', 'cat_id', 'category'] as $c) {
  if (in_array($c, $colNames)) {
    $categoryCol = $c;
    break;
  }
}
echo "<p>Category column: <strong>$categoryCol</strong></p>";

// Count products with NULL categories
if ($categoryCol) {
  $result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $prodTable WHERE $categoryCol IS NULL");
  $row = mysqli_fetch_assoc($result);
  echo "<p>Products with NULL category: " . $row['cnt'] . "</p>";
  
  $result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $prodTable WHERE $categoryCol > 0");
  $row = mysqli_fetch_assoc($result);
  echo "<p>Products with category value > 0: " . $row['cnt'] . "</p>";
}

// List categories
echo "<h3>Categories</h3>";
$catResult = mysqli_query($conn, "SELECT categories_id, categories_name FROM tbl_categories ORDER BY categories_id");
$cats = array();
while ($row = mysqli_fetch_assoc($catResult)) {
  $cats[] = $row;
  echo "ID: {$row['categories_id']}, Name: {$row['categories_name']}<br>";
}
echo "<p>Total categories: " . count($cats) . "</p>";

// Check products for each category
echo "<h3>Products by Category</h3>";
if ($categoryCol) {
  foreach($cats as $cat) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $prodTable WHERE $categoryCol = {$cat['categories_id']}");
    $row = mysqli_fetch_assoc($result);
    echo "{$cat['categories_name']}: {$row['cnt']} products<br>";
  }
}

// Sample products
echo "<h3>Sample Products</h3>";
$nameCol = in_array('product_name', $colNames) ? 'product_name' : 'pname';
$idCol = 'product_id';
if ($categoryCol) {
  $result = mysqli_query($conn, "SELECT $idCol, $nameCol, $categoryCol FROM $prodTable LIMIT 10");
  while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row[$idCol]}, Name: {$row[$nameCol]}, Category Col Value: {$row[$categoryCol]}<br>";
  }
}

mysqli_close($conn);
?>
