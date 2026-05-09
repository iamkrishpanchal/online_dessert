<?php
$conn = mysqli_connect('localhost','root','','online_dessert');
if (!$conn) { echo "DB connect failed: " . mysqli_connect_error(); exit; }
$q = "SELECT * FROM tbl_vendors WHERE vendor_name LIKE '%Krish%' OR shop_name LIKE '%Krish%' LIMIT 5";
$res = mysqli_query($conn, $q);
if (!$res) { echo "Query failed: " . mysqli_error($conn); exit; }
while ($r = mysqli_fetch_assoc($res)) {
    echo "--- Vendor Row ---\n";
    foreach ($r as $k=>$v) {
        echo "$k: $v\n";
    }
    echo "\n";
}
mysqli_close($conn);
?>