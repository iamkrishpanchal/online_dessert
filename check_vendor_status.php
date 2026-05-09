<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "online_dessert";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check table structure
echo "<h2>Table Structure</h2>";
$result = mysqli_query($conn, "DESCRIBE tbl_vendors");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    foreach ($row as $val) {
        echo "<td>" . htmlspecialchars($val) . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Check vendor data
echo "<h2>Vendor Status Data</h2>";
$result = mysqli_query($conn, "SELECT vendor_id, vendor_name, status, is_online, last_active FROM tbl_vendors ORDER BY vendor_id");
echo "<table border='1'>";
echo "<tr><th>Vendor ID</th><th>Vendor Name</th><th>Status</th><th>is_online</th><th>last_active</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['vendor_id'] . "</td>";
    echo "<td>" . $row['vendor_name'] . "</td>";
    echo "<td>" . ($row['status'] === NULL ? "<span style='color:red;'>NULL</span>" : htmlspecialchars($row['status'])) . "</td>";
    echo "<td>" . $row['is_online'] . "</td>";
    echo "<td>" . $row['last_active'] . "</td>";
    echo "</tr>";
}
echo "</table>";

mysqli_close($conn);
?>
