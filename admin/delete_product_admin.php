<?php
// Admin deletion endpoint for tbl_products (safe for admin area)
include 'session.php';
include 'connection.php';

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if ($product_id <= 0) {
    header('Location: vendor_products.php');
    exit;
}

// Get product (if exists) and delete image file(s) then DB row
$stmt = mysqli_prepare($conn, "SELECT product_image, vendor_id FROM tbl_products WHERE product_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $product_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$product) {
    header('Location: vendor_products.php?msg=notfound');
    exit;
}

$img = $product['product_image'] ?? '';
$vendor_id = intval($product['vendor_id'] ?? 0);

// Attempt to remove common image file locations (only if file exists)
if (!empty($img) && !preg_match('#^https?://#i', $img)) {
    $candidates = [
        __DIR__ . '/' . $img,
        __DIR__ . '/vendor/uploads/' . $img,
        __DIR__ . '/uploads/' . $img,
        __DIR__ . '/uploads/products/' . $img,
        __DIR__ . '/user/uploads/products/' . $img,
    ];
    foreach ($candidates as $c) { if (file_exists($c)) { @unlink($c); break; } }
}

$del = mysqli_prepare($conn, "DELETE FROM tbl_products WHERE product_id = ? LIMIT 1");
mysqli_stmt_bind_param($del, 'i', $product_id);
$ok = mysqli_stmt_execute($del);
mysqli_stmt_close($del);

// Redirect back to vendor's products page if vendor_id available
$location = 'vendor_products.php';
if ($vendor_id > 0) $location .= '?vendor_id=' . $vendor_id;
if ($ok) $location .= '&msg=deleted'; else $location .= '&msg=error';

header('Location: ' . $location);
exit;
?>