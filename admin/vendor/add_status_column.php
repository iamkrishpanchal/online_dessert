<?php
include 'connection.php';

$sql = "ALTER TABLE tbl_products ADD COLUMN status ENUM('available', 'not available') DEFAULT 'available'";

if (mysqli_query($conn, $sql)) {
    echo "Status column added successfully.";
} else {
    echo "Error adding column: " . mysqli_error($conn);
}
?>
