<?php
$conn = mysqli_connect("localhost", "root", "", "online_dessert");
if (!$conn) die("No DB connection\n");

$result = mysqli_query($conn, "DESCRIBE tbl_orders");
echo "tbl_orders structure:\n";
while($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>