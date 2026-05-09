<?php
session_start();
include 'connection.php';

echo "<h2>✅ Orders Fix Verification</h2>";

// Check if user_id column exists NOW
$cols_result = mysqli_query($conn, "SHOW COLUMNS FROM tbl_orders");
$columns = [];
$user_id_exists = false;
while ($col = mysqli_fetch_assoc($cols_result)) {
    $columns[] = $col['Field'];
    if ($col['Field'] === 'user_id') {
        $user_id_exists = true;
    }
}

echo "<h3>1. Table Structure Check:</h3>";
if ($user_id_exists) {
    echo "<p>✅ <strong>user_id column EXISTS in tbl_orders</strong></p>";
} else {
    echo "<p>❌ <strong>user_id column MISSING in tbl_orders</strong></p>";
    echo "<p>Attempting to add the column...</p>";
    $add_result = mysqli_query($conn, "ALTER TABLE tbl_orders ADD COLUMN user_id INT DEFAULT 0 AFTER order_number");
    if ($add_result) {
        echo "<p>✅ Column added successfully!</p>";
    } else {
        echo "<p>❌ Failed to add column: " . mysqli_error($conn) . "</p>";
    }
}

// Check all columns
echo "<h3>2. All tbl_orders columns:</h3>";
echo "<ul>";
foreach ($columns as $col) {
    echo "<li>$col</li>";
}
echo "</ul>";

// Test orders query
echo "<h3>3. Testing Orders Query:</h3>";
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "<p>Testing with User ID: <strong>$user_id</strong></p>";
    
    $test_query = "SELECT o.order_id, o.order_number, o.total_amount, o.order_status, o.created_at
                   FROM tbl_orders o WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 5";
    $stmt = mysqli_prepare($conn, $test_query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $order_count = mysqli_num_rows($result);
            echo "<p>✅ Query executed successfully</p>";
            echo "<p>Orders found: <strong>$order_count</strong></p>";
            
            if ($order_count > 0) {
                echo "<table border='1'>";
                echo "<tr><th>Order ID</th><th>Order #</th><th>Amount</th><th>Status</th><th>Date</th></tr>";
                while ($order = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>" . $order['order_id'] . "</td>";
                    echo "<td>" . $order['order_number'] . "</td>";
                    echo "<td>₹" . number_format($order['total_amount'], 2) . "</td>";
                    echo "<td>" . $order['order_status'] . "</td>";
                    echo "<td>" . $order['created_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "<p>❌ Query failed: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>❌ Failed to prepare statement: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>⚠️ Please login first to test the query</p>";
}

echo "<p><br><a href='orders.php'>Back to My Orders</a></p>";
?>
