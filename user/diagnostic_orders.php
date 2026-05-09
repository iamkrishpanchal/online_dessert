<?php
session_start();
include 'connection.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

echo "<h2>📋 Order Diagnostic Report</h2>";
echo "<p>Current User ID: <strong>$user_id</strong></p>";

// 1. Check if user_id column exists
echo "\n<h3>1. Checking tbl_orders table structure:</h3>";
$cols_result = mysqli_query($conn, "SHOW COLUMNS FROM tbl_orders");
$columns = [];
while ($col = mysqli_fetch_assoc($cols_result)) {
    $columns[] = $col['Field'];
}
echo "<pre>" . implode("\n", $columns) . "</pre>";

if (in_array('user_id', $columns)) {
    echo "<p>✅ user_id column EXISTS</p>";
} else {
    echo "<p>❌ user_id column MISSING - This is the problem!</p>";
}

// 2. Check total orders in table
echo "\n<h3>2. Total orders in database:</h3>";
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM tbl_orders"));
echo "<p>Total orders: <strong>" . $total['cnt'] . "</strong></p>";

// 3. Show all orders with different user_id values
echo "\n<h3>3. Orders breakdown by user_id:</h3>";
$breakdown = mysqli_query($conn, "SELECT user_id, COUNT(*) as order_count FROM tbl_orders GROUP BY user_id LIMIT 20");
echo "<table border='1'><tr><th>User ID</th><th>Order Count</th></tr>";
while ($row = mysqli_fetch_assoc($breakdown)) {
    echo "<tr><td>" . $row['user_id'] . "</td><td>" . $row['order_count'] . "</td></tr>";
}
echo "</table>";

// 4. Check current user's orders
if ($user_id > 0) {
    echo "\n<h3>4. Current user's orders:</h3>";
    $user_orders = mysqli_query($conn, "SELECT order_id, order_number, total_amount, order_status, created_at FROM tbl_orders WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10");
    $order_count = mysqli_num_rows($user_orders);
    echo "<p>Orders found: <strong>$order_count</strong></p>";
    
    if ($order_count > 0) {
        echo "<table border='1'><tr><th>Order ID</th><th>Order #</th><th>Amount</th><th>Status</th><th>Created</th></tr>";
        while ($order = mysqli_fetch_assoc($user_orders)) {
            echo "<tr>";
            echo "<td>" . $order['order_id'] . "</td>";
            echo "<td>" . $order['order_number'] . "</td>";
            echo "<td>₹" . $order['total_amount'] . "</td>";
            echo "<td>" . $order['order_status'] . "</td>";
            echo "<td>" . $order['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ No orders found for user $user_id</p>";
        
        // Check if there are ANY orders with different user reference
        echo "\n<h3>5. Checking if user is in any orders with different reference:</h3>";
        $raw_orders = mysqli_query($conn, "SELECT order_id, order_number, user_id, vendor_id, total_amount FROM tbl_orders LIMIT 5");
        echo "<table border='1'><tr><th>Order ID</th><th>Order #</th><th>User ID</th><th>Vendor ID</th><th>Amount</th></tr>";
        while ($row = mysqli_fetch_assoc($raw_orders)) {
            echo "<tr>";
            echo "<td>" . $row['order_id'] . "</td>";
            echo "<td>" . $row['order_number'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['vendor_id'] . "</td>";
            echo "<td>₹" . $row['total_amount'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// 6. Check recent orders (last 5)
echo "\n<h3>6. Latest 5 orders in system:</h3>";
$latest = mysqli_query($conn, "SELECT order_id, order_number, user_id, vendor_id, total_amount, created_at FROM tbl_orders ORDER BY created_at DESC LIMIT 5");
echo "<table border='1'><tr><th>Order ID</th><th>Order #</th><th>User ID</th><th>Vendor ID</th><th>Amount</th><th>Created</th></tr>";
while ($row = mysqli_fetch_assoc($latest)) {
    echo "<tr>";
    echo "<td>" . $row['order_id'] . "</td>";
    echo "<td>" . $row['order_number'] . "</td>";
    echo "<td>" . $row['user_id'] . "</td>";
    echo "<td>" . $row['vendor_id'] . "</td>";
    echo "<td>₹" . $row['total_amount'] . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "\n<p><a href='orders.php'>Back to Orders</a></p>";
?>
