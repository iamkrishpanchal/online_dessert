<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');

echo "<h2>Database Tables</h2>";
$result = mysqli_query($conn, "SHOW TABLES");
echo "<pre>";
while ($row = mysqli_fetch_row($result)) {
    echo $row[0] . "\n";
}
echo "</pre>";

mysqli_close($conn);
?>
