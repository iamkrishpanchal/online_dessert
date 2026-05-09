<?php
session_start();
include 'connection.php';

if (empty($_SESSION['user_id'])) {
    echo "❌ NOT LOGGED IN! Cannot check orders.";
    exit;
}

$user_id = intval($_SESSION['user_id']);
echo "<h2>🔍 Debug: Orders for User ID: <strong>$user_id</strong></h2>";
echo "<hr>";
echo "<p><strong>Session Info:</strong></p>";
echo "<ul>";
echo "<li>user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</li>";
echo "<li>user_name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "</li>";
echo "<li>email: " . ($_SESSION['email'] ?? 'NOT SET') . "</li>";
echo "</ul>";

// Check if table exists
$check = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_orders'");
if ($check && mysqli_num_rows($check) > 0) {
    echo "<p>✅ tbl_orders table EXISTS</p>";
    
    // Check total orders in table
    $total = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM tbl_orders");
    if ($total) {
        $row = mysqli_fetch_assoc($total);
        echo "<p>Total orders in database: <strong>" . $row['cnt'] . "</strong></p>";
    }
    
    // Check orders for this user
    $user_orders = mysqli_query($conn, "SELECT order_id, order_number, user_id, total_amount, order_status, created_at FROM tbl_orders WHERE user_id = $user_id");
    if ($user_orders) {
        $count = mysqli_num_rows($user_orders);
        echo "<p>Orders for USER ID $user_id: <strong>" . $count . "</strong></p>";
        
        if ($count > 0) {
            echo "<table border='1'>";
            echo "<tr><th>Order ID</th><th>Order #</th><th>User ID</th><th>Amount</th><th>Status</th><th>Created</th></tr>";
            while ($o = mysqli_fetch_assoc($user_orders)) {
                echo "<tr>";
                echo "<td>" . $o['order_id'] . "</td>";
                echo "<td>" . $o['order_number'] . "</td>";
                echo "<td>" . $o['user_id'] . "</td>";
                echo "<td>₹" . $o['total_amount'] . "</td>";
                echo "<td>" . $o['order_status'] . "</td>";
                echo "<td>" . $o['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>⚠️ No orders found for this user!</p>";
            
            // Show all orders to debug
            echo "<h3>All Orders in Database:</h3>";
            $all = mysqli_query($conn, "SELECT order_id, order_number, user_id, total_amount FROM tbl_orders LIMIT 10");
            if ($all && mysqli_num_rows($all) > 0) {
                echo "<table border='1'>";
                echo "<tr><th>Order ID</th><th>Order #</th><th>User ID</th><th>Amount</th></tr>";
                while ($a = mysqli_fetch_assoc($all)) {
                    echo "<tr>";
                    echo "<td>" . $a['order_id'] . "</td>";
                    echo "<td>" . $a['order_number'] . "</td>";
                    echo "<td>" . $a['user_id'] . "</td>";
                    echo "<td>₹" . $a['total_amount'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No orders in database at all</p>";
            }
        }
    } else {
        echo "<p>Error querying orders: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "❌ tbl_orders table does NOT exist!";
}

echo "<hr>";
echo "<p><a href='orders.php'>Back to Orders</a></p>";
?>
