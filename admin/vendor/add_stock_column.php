<?php
include 'connection.php';

// Check if column already exists
$checkColumn = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='tbl_products' AND COLUMN_NAME='stock'";
$result = mysqli_query($conn, $checkColumn);

if (mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE tbl_products ADD COLUMN stock INT NOT NULL DEFAULT 0";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Column stock added successfully!');</script>";
    } else {
        echo "<script>alert('Error adding column: " . mysqli_error($conn) . "');</script>";
    }
} else {
    echo "<script>alert('Column stock already exists!');</script>";
}

mysqli_close($conn);
?>
