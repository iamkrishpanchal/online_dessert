<?php
include 'connection.php';

// Check if status column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM tbl_products LIKE 'status'");
$exists = mysqli_num_rows($result) > 0;

if (!$exists) {
    $sql = "ALTER TABLE tbl_products ADD COLUMN status ENUM('available', 'not available') DEFAULT 'available'";
    if (mysqli_query($conn, $sql)) {
        echo "Status column added successfully.";
    } else {
        echo "Error adding column: " . mysqli_error($conn);
    }
} else {
    echo "Status column already exists.";
}
?>
