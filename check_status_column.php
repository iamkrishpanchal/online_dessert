<?php
include 'admin/connection.php';
$result = mysqli_query($conn, 'SHOW COLUMNS FROM tbl_vendors LIKE "status"');
if (mysqli_num_rows($result) > 0) {
    echo 'Status column exists\n';
    $row = mysqli_fetch_assoc($result);
    print_r($row);
} else {
    echo 'Status column does not exist\n';
}
?>