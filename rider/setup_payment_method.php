<?php
// Include connection from the rider directory
include 'connection.php';

echo "<h2>Database Migration - Adding Payment Method</h2>";

// 1. Check if payment_method column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM tbl_orders LIKE 'payment_method'");
if (mysqli_num_rows($result) == 0) {
    echo "<p>Adding payment_method column...</p>";
    $add_col = mysqli_query($conn, "ALTER TABLE tbl_orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'COD'");
    if ($add_col) {
        echo "<p style='color: green;'>✓ Column 'payment_method' added successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ Column 'payment_method' already exists</p>";
}

// 2. Update NULL payment_method values to 'COD' as default
echo "<p>Updating NULL payment_method values to 'COD'...</p>";
$update1 = mysqli_query($conn, "UPDATE tbl_orders SET payment_method = 'COD' WHERE payment_method IS NULL OR payment_method = ''");
if ($update1) {
    $affected = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>✓ Updated $affected rows with default payment method</p>";
} else {
    echo "<p style='color: red;'>✗ Error: " . mysqli_error($conn) . "</p>";
}

// 3. Check if payment_status column exists
$result2 = mysqli_query($conn, "SHOW COLUMNS FROM tbl_orders LIKE 'payment_status'");
if (mysqli_num_rows($result2) == 0) {
    echo "<p>Adding payment_status column...</p>";
    $add_col2 = mysqli_query($conn, "ALTER TABLE tbl_orders ADD COLUMN payment_status VARCHAR(50) DEFAULT 'Pending'");
    if ($add_col2) {
        echo "<p style='color: green;'>✓ Column 'payment_status' added successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ Column 'payment_status' already exists</p>";
}

// 4. Show sample data
echo "<h3>Current Sample Orders:</h3>";
$sample = mysqli_query($conn, "SELECT order_id, order_number, payment_method, payment_status, delivery_status FROM tbl_orders LIMIT 10");
if ($sample && mysqli_num_rows($sample) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Order ID</th><th>Order Number</th><th>Payment Method</th><th>Payment Status</th><th>Delivery Status</th></tr>";
    while ($row = mysqli_fetch_assoc($sample)) {
        echo "<tr>";
        echo "<td>" . $row['order_id'] . "</td>";
        echo "<td>" . $row['order_number'] . "</td>";
        echo "<td><strong>" . $row['payment_method'] . "</strong></td>";
        echo "<td>" . $row['payment_status'] . "</td>";
        echo "<td>" . $row['delivery_status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No orders found.</p>";
}

echo "<hr>";
echo "<p><a href='assigned_orders.php'>← Go to Assigned Orders</a></p>";
?>
