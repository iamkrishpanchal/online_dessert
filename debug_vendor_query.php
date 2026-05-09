<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');
if (!$conn) { 
    echo 'Connection failed: ' . mysqli_connect_error(); 
    exit; 
}

echo "=== Checking specific vendors ===\n\n";

for ($id = 12; $id <= 15; $id++) {
    $stmt = mysqli_prepare($conn, "SELECT vendor_id, shop_name, vendor_name FROM tbl_vendors WHERE vendor_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $vendor = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    
    echo "Query for vendor_id={$id}:\n";
    if ($vendor) {
        echo "  Result: vendor_id={$vendor['vendor_id']}, shop_name={$vendor['shop_name']}, vendor_name={$vendor['vendor_name']}\n\n";
    } else {
        echo "  Result: No vendor found\n\n";
    }
}

mysqli_close($conn);
?>
