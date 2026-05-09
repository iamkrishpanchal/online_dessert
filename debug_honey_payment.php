<?php
include 'connection.php';

// Find all orders from user "Honey"
$query = "SELECT o.order_id, o.user_id, o.order_number, o.payment_method, o.payment_status, u.user_name 
          FROM tbl_orders o
          LEFT JOIN tbl_users u ON o.user_id = u.user_id
          WHERE u.user_name LIKE '%Honey%' OR o.order_number LIKE '%137%' OR o.order_number LIKE '%136%'
          ORDER BY o.order_id DESC
          LIMIT 20";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo "Query error: " . mysqli_error($conn);
    exit;
}

echo "<h2>Honey's Orders - Payment Method Debug</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Order ID</th><th>Order Number</th><th>User</th><th>Payment Method</th><th>Payment Status</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['order_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['order_number']) . "</td>";
    echo "<td>" . htmlspecialchars($row['user_name']) . "</td>";
    echo "<td><strong>" . htmlspecialchars($row['payment_method']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($row['payment_status']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Also check all Razorpay related columns
$query2 = "SELECT o.order_id, o.payment_method, o.razorpay_payment_id, o.razorpay_order_id 
          FROM tbl_orders o
          WHERE o.razorpay_payment_id IS NOT NULL OR o.payment_method = 'Razorpay'
          ORDER BY o.order_id DESC
          LIMIT 10";

$result2 = mysqli_query($conn, $query2);

echo "<h2>All Razorpay Orders</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Order ID</th><th>Payment Method</th><th>Razorpay Payment ID</th><th>Razorpay Order ID</th></tr>";

while ($row = mysqli_fetch_assoc($result2)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['order_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['payment_method'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($row['razorpay_payment_id'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($row['razorpay_order_id'] ?? 'NULL') . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
