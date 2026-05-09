<?php
$conn = mysqli_connect('localhost','root','','online_dessert');
if (!$conn) { echo "DB connect failed: " . mysqli_connect_error(); exit; }
// Set is_online = 0 for vendors not active
$u1 = mysqli_query($conn, "UPDATE tbl_vendors SET is_online = 0 WHERE NOT (status = 'active' OR status = '1')");
// Optionally set is_online = 1 for active status
$u2 = mysqli_query($conn, "UPDATE tbl_vendors SET is_online = 1 WHERE (status = 'active' OR status = '1')");
echo "Sync executed. Rows updated: " . (mysqli_affected_rows($conn)) . "\n\n";
$res = mysqli_query($conn, "SELECT vendor_id, vendor_name, shop_name, is_online, status, last_active FROM tbl_vendors ORDER BY vendor_id ASC");
while ($r = mysqli_fetch_assoc($res)) {
    echo sprintf("%3s | %-20s | %-25s | is_online=%s | status=%s | last_active=%s\n", $r['vendor_id'], $r['vendor_name'], $r['shop_name'], $r['is_online'], $r['status'], $r['last_active']);
}
mysqli_close($conn);
?>