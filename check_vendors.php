<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');
if (!$conn) { 
    echo 'Connection failed: ' . mysqli_connect_error(); 
    exit; 
}
echo "=== All Vendors in Database ===\n\n";
$result = mysqli_query($conn, 'SELECT vendor_id, shop_name, vendor_name FROM tbl_vendors ORDER BY vendor_id');
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        echo "Vendor ID: {$row['vendor_id']}\n";
        echo "  Shop Name: {$row['shop_name']}\n";
        echo "  Vendor Name: {$row['vendor_name']}\n";
        echo "\n";
    }
} else {
    echo 'Query failed: ' . mysqli_error($conn);
}
mysqli_close($conn);
?>
