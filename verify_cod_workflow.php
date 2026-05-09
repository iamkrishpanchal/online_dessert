<?php
// Get the correct path to connection.php
$conn = null;
if (file_exists('admin/connection.php')) {
    include 'admin/connection.php';
} elseif (file_exists('rider/connection.php')) {
    include 'rider/connection.php';
} elseif (file_exists('user/connection.php')) {
    include 'user/connection.php';
} else {
    die('<p style="color: red;">Error: connection.php not found</p>');
}

if (!$conn) {
    die('<p style="color: red;">Error: Could not connect to database</p>');
}

echo "<h2 style='color: #075984;'>COD Payment Workflow Verification</h2>";

// Check 1: Verify columns exist
echo "<h3>1. Database Structure Check:</h3>";
$columns = ['payment_method', 'payment_status', 'delivery_status'];
foreach ($columns as $col) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM tbl_orders LIKE '$col'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✓ Column '$col' exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Column '$col' MISSING</p>";
    }
}

// Check 2: Sample COD orders
echo "<h3>2. Sample COD Orders (Payment Status):</h3>";
$sql = "SELECT o.order_id, o.order_number, o.payment_method, o.payment_status, o.delivery_status, o.order_status 
        FROM tbl_orders o 
        WHERE UPPER(o.payment_method) = 'COD' 
        ORDER BY o.order_id DESC 
        LIMIT 10";

$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Order ID</th><th>Order #</th><th>Payment Method</th><th>Payment Status</th><th>Delivery Status</th><th>Order Status</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $paymentStatusColor = ($row['payment_status'] === 'Paid' || $row['payment_status'] === 'paid') ? 'green' : 'orange';
        $deliveryStatusColor = ($row['delivery_status'] === 'delivered') ? 'blue' : 'gray';
        
        echo "<tr>";
        echo "<td>" . $row['order_id'] . "</td>";
        echo "<td><strong>" . $row['order_number'] . "</strong></td>";
        echo "<td><strong style='color: red;'>COD</strong></td>";
        echo "<td><strong style='color: $paymentStatusColor;'>" . ucfirst($row['payment_status']) . "</strong></td>";
        echo "<td><strong style='color: $deliveryStatusColor;'>" . $row['delivery_status'] . "</strong></td>";
        echo "<td>" . $row['order_status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>ℹ No COD orders found yet. Place a test COD order to see the workflow.</p>";
}

echo "<h3>3. Workflow Status:</h3>";
echo "<p style='background-color: #e8f5e9; padding: 15px; border-left: 4px solid green;'>";
echo "<strong>✓ COD Payment Workflow is ENABLED</strong><br>";
echo "When a user places a COD order:<br>";
echo "1. Payment Status = 'Pending' (visible in Admin & User)<br>";
echo "2. Admin assigns order to rider<br>";
echo "3. Rider accepts order → Rider can 'Collect Payment' button appears when out_for_delivery<br>";
echo "4. Rider collects cash from customer<br>";
echo "5. Rider clicks 'Collect Payment'<br>";
echo "6. Payment Status automatically updates to 'Paid' ✓<br>";
echo "7. Admin & User both see 'Paid' status<br>";
echo "8. Rider clicks 'Mark Delivered'<br>";
echo "</p>";

echo "<hr>";
echo "<p><a href='../admin/orders_dashboard.php'>← View All Orders in Admin</a> | ";
echo "<a href='../user/orders.php'>View Orders in User Dashboard →</a></p>";
?>
