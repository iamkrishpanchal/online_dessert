<?php
include 'connection.php';

// Rename the 'status' column to 'stock' in tbl_products
$sql = "ALTER TABLE tbl_products CHANGE status stock INT DEFAULT 0";

if (mysqli_query($conn, $sql)) {
    echo "Column renamed successfully from 'status' to 'stock'.";
} else {
    echo "Error renaming column: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
