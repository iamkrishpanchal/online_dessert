<?php
session_start();
include 'connection.php';

if (empty($_SESSION['user_id'])) {
    echo "You must be logged in";
    exit;
}

$user_id = $_SESSION['user_id'];

// Check how many orders this user has
$count_sql = "SELECT COUNT(*) as total_orders FROM tbl_orders WHERE user_id = ?";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, 'i', $user_id);
mysqli_stmt_execute($count_stmt);
$count_res = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_res);

echo "<h3>Order Check for User ID: $user_id</h3>";
echo "<p><strong>Total Orders in Database:</strong> " . $count_row['total_orders'] . "</p>";

// Fetch all orders
$order_sql = "SELECT order_id, order_number, total_amount, order_status, payment_status, delivery_status, created_at FROM tbl_orders WHERE user_id = ? ORDER BY created_at DESC";
$order_stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($order_stmt, 'i', $user_id);
mysqli_stmt_execute($order_stmt);
$order_res = mysqli_stmt_get_result($order_stmt);

echo "<h4>All Orders:</h4>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Order ID</th><th>Order Number</th><th>Amount</th><th>Status</th><th>Payment</th><th>Delivery</th><th>Date</th></tr>";

while ($o = mysqli_fetch_assoc($order_res)) {
    echo "<tr>";
    echo "<td>" . $o['order_id'] . "</td>";
    echo "<td>" . $o['order_number'] . "</td>";
    echo "<td>₹" . number_format($o['total_amount'], 2) . "</td>";
    echo "<td>" . $o['order_status'] . "</td>";
    echo "<td>" . $o['payment_status'] . "</td>";
    echo "<td>" . ($o['delivery_status'] ?? 'Not Set') . "</td>";
    echo "<td>" . $o['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
