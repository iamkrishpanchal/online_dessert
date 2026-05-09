<?php
$conn = mysqli_connect('localhost','root','','online_dessert');
if (!$conn) { echo "DB connect failed: " . mysqli_connect_error(); exit; }
$res = mysqli_query($conn, "SELECT vendor_id, vendor_name, shop_name, is_online, status, last_active FROM tbl_vendors ORDER BY vendor_id ASC");
if (!$res) { echo "Query failed: " . mysqli_error($conn); exit; }
while ($r = mysqli_fetch_assoc($res)) {
    echo sprintf("%3s | %-20s | %-25s | is_online=%s | status=%s | last_active=%s\n", $r['vendor_id'], $r['vendor_name'], $r['shop_name'], $r['is_online'], $r['status'], $r['last_active']);
}
mysqli_close($conn);
?>
