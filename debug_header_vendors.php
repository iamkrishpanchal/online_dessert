<?php
include 'user/connection.php';

// Same logic as header.php
$vendors = array();
if($conn) {
  $vendor_where = "";
  $colRes = @mysqli_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_vendors' AND COLUMN_NAME = 'is_active'");
  if ($colRes && mysqli_num_rows($colRes) > 0) {
    $vendor_where = " WHERE is_active = 1";
  } else {
    $colRes2 = @mysqli_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_vendors' AND COLUMN_NAME = 'status'");
    if ($colRes2 && mysqli_num_rows($colRes2) > 0) {
      $vendor_where = " WHERE status = 'active' OR status = '1'";
    } else {
      $vendor_where = "";
    }
  }
  $vendor_query = "SELECT vendor_id, shop_name, vendor_name FROM tbl_vendors" . $vendor_where . " ORDER BY shop_name ASC";
  echo "Query: {$vendor_query}\n\n";
  
  $result = mysqli_query($conn, $vendor_query);
  if($result) {
    echo "=== Vendors returned by header query ===\n";
    while($row = mysqli_fetch_assoc($result)) {
      echo "ID: {$row['vendor_id']} | Shop: {$row['shop_name']} | Name: {$row['vendor_name']}\n";
      $vendors[] = $row;
    }
    echo "\nTotal vendors returned: " . count($vendors) . "\n";
  } else {
    echo "Query failed: " . mysqli_error($conn);
  }
}
mysqli_close($conn);
?>
