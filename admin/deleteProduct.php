<?php
include 'session.php';
include 'connection.php';

if (!isset($_GET['product_id'])) {
    header('Location: viewProduct.php');
    exit;
}

$product_id = mysqli_real_escape_string($conn, $_GET['product_id']);

// Get product details first
$query = "SELECT productImg FROM tbl_product WHERE productId = '$product_id'";
$result = mysqli_query($conn, $query);
$product = mysqli_fetch_assoc($result);

if ($product) {
    // Delete image file
    if (file_exists("uploads/" . $product['productImg'])) {
        unlink("uploads/" . $product['productImg']);
    }
    
    // Delete from database
    $delete_query = "DELETE FROM tbl_product WHERE productId = '$product_id'";
    
    if (mysqli_query($conn, $delete_query)) {
        header('Location: viewProduct.php?msg=deleted');
        exit;
    }
}

header('Location: viewProduct.php?msg=error');
exit;
?>
