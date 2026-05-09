<?php
include 'connection.php';

// Check if column already exists
$checkColumn = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='tbl_categories' AND COLUMN_NAME='categories_image'";
$result = mysqli_query($conn, $checkColumn);

if (mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE tbl_categories ADD COLUMN categories_image VARCHAR(255) NOT NULL DEFAULT ''";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Column categories_image added successfully!');</script>";
    } else {
        echo "<script>alert('Error adding column: " . mysqli_error($conn) . "');</script>";
    }
} else {
    echo "<script>alert('Column categories_image already exists!');</script>";
}

mysqli_close($conn);
?>
