<?php
header('Content-Type: application/json; charset=utf-8');
include 'connection.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if (!$conn || $q === '') {
    echo json_encode([]);
    exit;
}

$like = '%' . $q . '%';

// Determine which description column exists (some DBs use `description`, others `product_description`)
$desc_col = null;
$colCheck = mysqli_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_products' AND COLUMN_NAME IN ('description','product_description')");
if ($colCheck && mysqli_num_rows($colCheck) > 0) {
    $row = mysqli_fetch_assoc($colCheck);
    $desc_col = $row['COLUMN_NAME'];
}

// Build SQL dynamically depending on whether a description column exists
$fields = "p.product_id, p.product_name, COALESCE(p.product_image, '') AS product_image, COALESCE(c.categories_name, '') AS category";
$sql = "SELECT $fields FROM tbl_products p LEFT JOIN tbl_categories c ON p.category_id = c.categories_id WHERE p.product_name LIKE ?";
if ($desc_col) {
    $sql .= " OR p." . $desc_col . " LIKE ?";
}
$sql .= " LIMIT 20";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode([]);
    exit;
}

// Bind parameters
if ($desc_col) {
    mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
} else {
    mysqli_stmt_bind_param($stmt, 's', $like);
}

mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Resolve image paths to usable URLs similar to other pages
foreach ($rows as &$r) {
    $pimg = $r['product_image'] ?? '';
    $resolved = '';
    if ($pimg && !preg_match('#^https?://#', $pimg)) {
        $candidates = [
            $pimg,
            'uploads/' . $pimg,
            '../admin/vendor/uploads/' . basename($pimg),
            '../admin/uploads/vendors/' . basename($pimg),
            '../uploads/vendors/' . basename($pimg),
        ];
        foreach ($candidates as $cand) {
            if (file_exists(__DIR__ . '/' . $cand)) {
                $resolved = $cand;
                break;
            }
        }
    } else {
        $resolved = $pimg;
    }
    if (!$resolved) $resolved = 'images/default-product.png';
    $r['product_image'] = $resolved;
}

echo json_encode($rows);

?>
