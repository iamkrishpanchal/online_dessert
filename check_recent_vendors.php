<?php
include 'admin/connection.php';
$result = mysqli_query($conn, 'SELECT vendor_id, vendor_name, email, status, created_at FROM tbl_vendors ORDER BY vendor_id DESC LIMIT 5');
echo "Recent vendor registrations:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo 'ID: ' . $row['vendor_id'] . ', Name: ' . $row['vendor_name'] . ', Email: ' . $row['email'] . ', Status: ' . $row['status'] . ', Created: ' . $row['created_at'] . "\n";
}
?>