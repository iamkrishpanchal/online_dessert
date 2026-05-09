<?php
$conn = mysqli_connect('localhost','root','','online_dessert');
$res = mysqli_query($conn, 'SELECT vendor_id, shop_name, logo_path FROM tbl_vendors LIMIT 10');
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['vendor_id'] . ' | ' . $row['shop_name'] . ' | ' . $row['logo_path'] . "\n";
}
