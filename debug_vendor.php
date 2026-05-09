<?php
include 'user/connection.php';

echo "<h2>Debug Vendor Check</h2>";

// Check vendor 5
$vendor_id = 5;
$v_query = "SELECT * FROM tbl_vendors WHERE vendor_id = $vendor_id";
$v_result = mysqli_query($conn, $v_query);

if ($v_result && mysqli_num_rows($v_result) > 0) {
    $vendor = mysqli_fetch_assoc($v_result);
    echo "<h3>Vendor #$vendor_id:</h3>";
    echo "<pre>"; print_r($vendor); echo "</pre>";
} else {
    echo "<h3>Vendor #$vendor_id NOT FOUND!</h3>";
}

// Check table structure
echo "<h3>Vendor Table Columns:</h3>";
$cols = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors");
while ($c = mysqli_fetch_assoc($cols)) {
    echo "- " . $c['Field'] . "<br>";
}

// Check all vendors
echo "<h3>All Vendors:</h3>";
$all_v = mysqli_query($conn, "SELECT vendor_id, vendor_name, shop_name FROM tbl_vendors");
while ($v = mysqli_fetch_assoc($all_v)) {
    echo $v['vendor_id'] . " - " . $v['vendor_name'] . " / " . $v['shop_name'] . "<br>";
}

mysqli_close($conn);
?>
