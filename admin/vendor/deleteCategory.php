<?php
include 'session.php';
include 'connection.php';

$vendor_id = $_SESSION['vendor_id'] ?? null;
if (!$vendor_id) {
    echo "<script>alert('Vendor ID not found. Please login again.'); window.location.href='login.php';</script>";
    exit;
}

$idVal = $_GET["cat_id"] ?? 0;

if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    // Verify category belongs to this vendor
    $verify_sql = "SELECT categories_image FROM tbl_categories WHERE categories_id=? AND vendor_id=?";
    $verify_stmt = mysqli_prepare($conn, $verify_sql);
    
    if ($verify_stmt) {
        mysqli_stmt_bind_param($verify_stmt, 'ii', $idVal, $vendor_id);
        mysqli_stmt_execute($verify_stmt);
        $verify_result = mysqli_stmt_get_result($verify_stmt);
        $category = mysqli_fetch_assoc($verify_result);
        mysqli_stmt_close($verify_stmt);
        
        if (!$category) {
            echo "<script>alert('Category not found or unauthorized access.'); window.location.href='viewCategory.php';</script>";
            exit;
        }
        
        // Delete category using prepared statement
        $delete_sql = "DELETE FROM tbl_categories WHERE categories_id=? AND vendor_id=?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        
        if ($delete_stmt) {
            mysqli_stmt_bind_param($delete_stmt, 'ii', $idVal, $vendor_id);
            if (mysqli_stmt_execute($delete_stmt)) {
                mysqli_stmt_close($delete_stmt);
                echo "<script>alert('Category deleted successfully!'); window.location.href='viewCategory.php';</script>";
            } else {
                echo "<script>alert('Error deleting category.'); window.location.href='viewCategory.php';</script>";
            }
        } else {
            echo "<script>alert('Database error.'); window.location.href='viewCategory.php';</script>";
        }
    } else {
        echo "<script>alert('Database error.'); window.location.href='viewCategory.php';</script>";
    }
    exit;
}

echo "<script>
    if (confirm('Are you sure you want to delete this category?')) {
        window.location.href = 'deleteCategory.php?cat_id=$idVal&confirm=yes';
    } else {
        window.location.href = 'viewCategory.php';
    }
</script>";
?>
?>