<?php
include 'connection.php';

// Find products table
$possible = ['tbl_product','tbl_products','product','products'];
$prodTable = null;
$tablesRes = mysqli_query($conn, "SHOW TABLES");
$existing = []; 
while($tr = mysqli_fetch_row($tablesRes)) $existing[] = $tr[0];
foreach($possible as $p) if(in_array($p, $existing)) { $prodTable = $p; break; }

if (!$prodTable) {
    echo 'Product table not found.'; exit;
}

echo '<h2>All Products in Database</h2>';
echo '<table border="1" cellpadding="5"><tr>';
echo '<th>ID</th><th>Name</th><th>Image Column</th><th>Image Value</th><th>File Exists?</th>';
echo '</tr>';

$query = "SELECT * FROM {$prodTable} LIMIT 10";
$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $id = $row['product_id'] ?? $row['id'] ?? $row['pid'] ?? '';
    $name = $row['product_name'] ?? $row['name'] ?? $row['title'] ?? 'Unknown';
    
    // Check all possible image columns
    $imgVal = '';
    $imgCol = '';
    foreach(['image', 'image_path', 'img', 'photo', 'product_image'] as $col) {
        if (isset($row[$col]) && !empty($row[$col])) {
            $imgVal = $row[$col];
            $imgCol = $col;
            break;
        }
    }
    
    $exists = '';
    if ($imgVal) {
        $candidates = [$imgVal, 'uploads/' . $imgVal, 'uploads/vendors/' . $imgVal, 'uploads/products/' . $imgVal];
        foreach ($candidates as $cand) {
            if (file_exists(__DIR__ . '/' . $cand)) {
                $exists = '✓ EXISTS: ' . $cand;
                break;
            }
        }
        if (!$exists) $exists = '✗ NOT FOUND';
    } else {
        $exists = 'EMPTY';
    }
    
    echo '<tr>';
    echo '<td>' . htmlspecialchars($id) . '</td>';
    echo '<td>' . htmlspecialchars($name) . '</td>';
    echo '<td>' . htmlspecialchars($imgCol) . '</td>';
    echo '<td><code>' . htmlspecialchars($imgVal) . '</code></td>';
    echo '<td>' . $exists . '</td>';
    echo '</tr>';
}

echo '</table>';

echo '<h2>Server Folder Structure</h2>';
echo '<pre>';
echo 'user/ folder:';
system('dir "' . __DIR__ . '" /B');
echo '\n\nuploads/ folder (if exists):';
if (is_dir(__DIR__ . '/uploads')) {
    system('dir "' . __DIR__ . '/uploads" /B /S');
}
echo '</pre>';
?>
