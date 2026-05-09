<?php
include 'vendor/connection.php';

if ($conn) {
    echo "Connected successfully.<br>";

    $result = mysqli_query($conn, "SHOW TABLES");
    echo "All tables:<br>";
    while($row = mysqli_fetch_array($result)) {
        echo $row[0] . "<br>";
    }
} else {
    echo "Connection failed: " . mysqli_connect_error();
}
?>
