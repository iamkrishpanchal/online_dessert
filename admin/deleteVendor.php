<?php
include 'session.php';
include 'connection.php';

if (!isset($_GET['vendor_id']) || empty($_GET['vendor_id'])) {
    header('Location: vendor_detail.php');
    exit;
}

$vendor_id = (int)$_GET['vendor_id'];

// Get vendor info before deleting (for verification)
$stmt = mysqli_prepare($conn, "SELECT vendor_name, shop_name FROM tbl_vendors WHERE vendor_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $vendor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$vendor = mysqli_fetch_assoc($result);

if (!$vendor) {
    $_SESSION['error'] = 'Vendor not found.';
    header('Location: vendor_detail.php');
    exit;
}

// Cleanup dependent data first, then delete vendor
$hasProductVendorId = false;
$prodColRes = mysqli_query($conn, "SHOW COLUMNS FROM tbl_products LIKE 'vendor_id'");
if ($prodColRes && mysqli_num_rows($prodColRes) > 0) {
    $hasProductVendorId = true;
}
$hasCategoryVendorId = false;
$catColRes = mysqli_query($conn, "SHOW COLUMNS FROM tbl_categories LIKE 'vendor_id'");
if ($catColRes && mysqli_num_rows($catColRes) > 0) {
    $hasCategoryVendorId = true;
}

if ($hasProductVendorId) {
    mysqli_query($conn, "DELETE FROM tbl_products WHERE vendor_id = {$vendor_id}");
}
if ($hasCategoryVendorId) {
    mysqli_query($conn, "DELETE FROM tbl_categories WHERE vendor_id = {$vendor_id}");
}

// Now delete vendor
$stmt = mysqli_prepare($conn, "DELETE FROM tbl_vendors WHERE vendor_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $vendor_id);

if (mysqli_stmt_execute($stmt)) {
    header('Location: vendor_detail.php?success=Vendor ' . urlencode($vendor['shop_name'] ?: $vendor['vendor_name']) . ' deleted successfully');
    exit;
} else {
    $errorMsg = mysqli_error($conn);
    header('Location: vendor_detail.php?error=' . urlencode('Failed to delete vendor: ' . $errorMsg));
    exit;
}

mysqli_close($conn);
?>
