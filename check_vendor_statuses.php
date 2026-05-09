<?php
include 'admin/connection.php';
$result = mysqli_query($conn, 'SELECT vendor_id, vendor_name, email, status FROM tbl_vendors LIMIT 10');
echo "Current vendor statuses:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo 'ID: ' . $row['vendor_id'] . ', Name: ' . $row['vendor_name'] . ', Email: ' . $row['email'] . ', Status: ' . ($row['status'] ?? 'NULL') . "\n";
}
?>