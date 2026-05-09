<?php
include 'connection.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// detect products table
$possible = ['tbl_product','tbl_products','product','products'];
$prodTable = null;
$tablesRes = mysqli_query($conn, "SHOW TABLES");
$existing = []; while($tr = mysqli_fetch_row($tablesRes)) $existing[] = $tr[0];
foreach($possible as $p) if(in_array($p, $existing)) { $prodTable = $p; break; }

if (!$prodTable) {
    echo 'Product table not found.'; exit;
}

// Detect which column is the primary key
$colsRes = mysqli_query($conn, "SHOW COLUMNS FROM {$prodTable}");
$prodCols = [];
$idCol = null;
if ($colsRes) { 
    while($c = mysqli_fetch_assoc($colsRes)) { 
        $prodCols[] = $c['Field'];
        if (in_array($c['Field'], ['product_id', 'id', 'pid'])) {
            $idCol = $c['Field'];
        }
    }
}

if (!$idCol) { echo 'Product ID column not found.'; exit; }

$stmt = mysqli_prepare($conn, "SELECT * FROM {$prodTable} WHERE {$idCol} = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$product) { echo 'Product not found.'; exit; }

echo '<h2>Product Debug Info</h2>';
echo '<pre>';
echo "Product Table: " . $prodTable . "\n";
echo "ID Column: " . $idCol . "\n\n";

echo "Product ID: " . $product[$idCol] . "\n";
echo "Product Name: " . ($product['product_name'] ?? $product['name'] ?? 'N/A') . "\n\n";

// Check all image-related columns
echo "=== Image Fields ===\n";
foreach(['image', 'image_path', 'img', 'photo', 'product_image', 'product_image_path'] as $col) {
    if (isset($product[$col])) {
        echo $col . " = '" . $product[$col] . "'\n";
    }
}

$image = $product['product_image'] ?? $product['image'] ?? $product['image_path'] ?? $product['img'] ?? ($product['photo'] ?? '');
echo "\n=== Selected Image Value ===\n";
echo "Raw: '" . $image . "'\n\n";

if ($image && !preg_match('#^https?://#', $image)) {
    echo "=== Checking Image Paths ===\n";
    $imgCandidates = [$image, 'uploads/' . $image, 'uploads/vendors/' . $image, 'uploads/products/' . $image, '../uploads/' . $image, '../uploads/vendors/' . $image, '../uploads/products/' . $image, '../admin/uploads/' . $image, '../admin/vendor/uploads/' . $image];
    foreach ($imgCandidates as $cand) {
        $fullPath = __DIR__ . '/' . $cand;
        $exists = file_exists($fullPath) ? 'EXISTS' : 'NOT FOUND';
        echo $cand . " => " . $exists . "\n";
    }
} else if (preg_match('#^https?://#', $image)) {
    echo "Image is a URL: " . $image . "\n";
} else {
    echo "No image path found\n";
}

echo '</pre>';
?>
