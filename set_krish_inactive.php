<?php
$conn = mysqli_connect('localhost','root','','online_dessert');
if (!$conn) { echo "DB connect failed: " . mysqli_connect_error(); exit; }
$vid = 14;
$updates = mysqli_query($conn, "UPDATE tbl_vendors SET is_online = 0, last_active = NOW(), status = 'inactive' WHERE vendor_id = $vid");
if ($updates) echo "Updated vendor $vid to inactive\n";
else echo "Update failed: " . mysqli_error($conn) . "\n";
// Confirm
$res = mysqli_query($conn, "SELECT vendor_id, vendor_name, is_online, last_active, status FROM tbl_vendors WHERE vendor_id = $vid");
$r = mysqli_fetch_assoc($res);
print_r($r);
mysqli_close($conn);
?>