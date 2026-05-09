<?php
include 'session.php';
include 'connection.php';

$vendor_id = $_SESSION['vendor_id'] ?? null;
if (!$vendor_id) {
    echo "<script>alert('Vendor ID not found. Please login again.'); window.location.href='login.php';</script>";
    exit;
}

$productIdVal = $_GET["product_id"] ?? 0;

// Verify product belongs to this vendor
$verify_sql = "SELECT product_image FROM tbl_products WHERE product_id=? AND vendor_id=?";
$verify_stmt = mysqli_prepare($conn, $verify_sql);

if ($verify_stmt) {
    mysqli_stmt_bind_param($verify_stmt, 'ii', $productIdVal, $vendor_id);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);
    $product = mysqli_fetch_assoc($verify_result);
    mysqli_stmt_close($verify_stmt);
    
    if (!$product) {
        echo "<script>alert('Product not found or unauthorized access.'); window.location.href='viewProduct.php';</script>";
        exit;
    }
    
    $deletedImg = $product["product_image"];
    if (file_exists("./uploads/" . $deletedImg)) {
        unlink("./uploads/" . $deletedImg);
    }

    // Delete using prepared statement
    $delete_sql = "DELETE FROM tbl_products WHERE product_id=? AND vendor_id=?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    
    if ($delete_stmt) {
        mysqli_stmt_bind_param($delete_stmt, 'ii', $productIdVal, $vendor_id);
        if (mysqli_stmt_execute($delete_stmt)) {
            mysqli_stmt_close($delete_stmt);
            echo "<script>alert('Product deleted successfully!'); window.location.href='viewProduct.php';</script>";
        } else {
            echo "<script>alert('Error deleting product.'); window.location.href='viewProduct.php';</script>";
        }
    } else {
        echo "<script>alert('Database error.'); window.location.href='viewProduct.php';</script>";
    }
} else {
    echo "<script>alert('Database error.'); window.location.href='viewProduct.php';</script>";
}
?>