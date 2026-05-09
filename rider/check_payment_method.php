<?php
// Include connection from the rider directory
include 'connection.php';

// Check if payment_method column exists
echo "<h3>Checking payment_method column...</h3>";
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM tbl_orders LIKE 'payment_method'");
if ($colCheck && mysqli_num_rows($colCheck) > 0) {
    echo "<p style='color: green;'>✓ Column 'payment_method' EXISTS</p>";
} else {
    echo "<p style='color: red;'>✗ Column 'payment_method' DOES NOT EXIST - needs to be added</p>";
}

// Check if payment_status column exists
$colCheck2 = mysqli_query($conn, "SHOW COLUMNS FROM tbl_orders LIKE 'payment_status'");
if ($colCheck2 && mysqli_num_rows($colCheck2) > 0) {
    echo "<p style='color: green;'>✓ Column 'payment_status' EXISTS</p>";
} else {
    echo "<p style='color: red;'>✗ Column 'payment_status' DOES NOT EXIST - needs to be added</p>";
}

// Show sample orders with payment details
echo "<h3>Sample Orders with Payment Details:</h3>";
$sampleSql = "SELECT order_id, order_number, payment_method, payment_status, delivery_status FROM tbl_orders LIMIT 5";
$sampleRes = mysqli_query($conn, $sampleSql);

if ($sampleRes) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Order ID</th><th>Order Number</th><th>Payment Method</th><th>Payment Status</th><th>Delivery Status</th></tr>";
    while ($row = mysqli_fetch_assoc($sampleRes)) {
        echo "<tr>";
        echo "<td>" . $row['order_id'] . "</td>";
        echo "<td>" . $row['order_number'] . "</td>";
        echo "<td>" . ($row['payment_method'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['payment_status'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['delivery_status'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error fetching orders: " . mysqli_error($conn) . "</p>";
}

echo "<hr>";
echo "<p><a href='assigned_orders.php'>← Back to Assigned Orders</a></p>";
?>
