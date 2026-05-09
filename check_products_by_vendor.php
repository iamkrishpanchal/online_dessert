<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');
if (!$conn) { 
    echo 'Connection failed: ' . mysqli_connect_error(); 
    exit; 
}
echo "=== Products by Vendor ===\n\n";
$result = mysqli_query($conn, 'SELECT vendor_id, COUNT(*) as count FROM tbl_products GROUP BY vendor_id ORDER BY vendor_id');
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        echo "Vendor ID {$row['vendor_id']}: {$row['count']} products\n";
    }
} else {
    echo 'Query failed: ' . mysqli_error($conn);
}
mysqli_close($conn);
?>
