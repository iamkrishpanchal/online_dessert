<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = mysqli_connect("localhost", "root", "", "online_dessert");

if (!$conn) {
    die("Connection Error: " . mysqli_connect_error());
}

echo "<h2>Vendor Login Debug</h2>";

// Check if table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_vendors'");
if (mysqli_num_rows($tableCheck) == 0) {
    die("Table tbl_vendors does not exist!");
}

// Get vendor info
echo "<h3>All Vendors in Database:</h3>";
$result = mysqli_query($conn, "SELECT vendor_id, vendor_name, email, status, password FROM tbl_vendors ORDER BY vendor_id");

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #ffc99c;'>";
echo "<th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Password Type</th><th>Password Length</th>";
echo "</tr>";

$count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $count++;
    $pass = $row['password'];
    $passType = (strlen($pass) > 30 && strpos($pass, '$2') === 0) ? "HASHED" : "PLAIN TEXT";
    
    echo "<tr>";
    echo "<td>" . $row['vendor_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['vendor_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . htmlspecialchars($row['status'] ?? 'NULL') . "</td>";
    echo "<td><strong>" . $passType . "</strong></td>";
    echo "<td>" . strlen($pass) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>Total Vendors:</strong> $count</p>";

// Check table structure
echo "<h3>Table Structure:</h3>";
$structResult = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors");
echo "<table border='1' cellpadding='10'>";
echo "<tr style='background: #ffc99c;'><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($structResult)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . ($row['Default'] ?? '') . "</td>";
    echo "</tr>";
}
echo "</table>";

mysqli_close($conn);
?>
