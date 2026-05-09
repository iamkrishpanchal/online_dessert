<?php
include 'vendor/connection.php';

if ($conn) {
    echo "Connected successfully to the database.";
} else {
    echo "Connection failed: " . mysqli_connect_error();
}
?>
