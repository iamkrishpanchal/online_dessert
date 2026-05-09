<?php
include 'connection.php';

echo "<h2>Fix Payment Method for Online Orders</h2>";

// Priority 1: Find orders that have razorpay_payment_id
$query1 = "SELECT order_id, order_number, razorpay_payment_id, payment_method FROM tbl_orders 
          WHERE razorpay_payment_id IS NOT NULL AND razorpay_payment_id != ''
          AND (payment_method IS NULL OR payment_method = '' OR payment_method = 'COD')
          ORDER BY order_id DESC";

$result1 = mysqli_query($conn, $query1);
$count1 = mysqli_num_rows($result1);

echo "<h3>Priority 1: Orders with Razorpay Payment ID (Definite Online Payments)</h3>";
echo "<p>Found <strong>$count1</strong> orders that have Razorpay payment ID but incorrect payment method.</p>";

if ($count1 > 0) {
    echo "<table border='1' cellpadding='10' style='margin: 20px 0; border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Order ID</th><th>Order Number</th><th>Current Payment Method</th><th>Razorpay Payment ID</th></tr>";
    
    $order_ids_1 = [];
    $result1_copy = mysqli_query($conn, $query1);
    while ($row = mysqli_fetch_assoc($result1_copy)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['order_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['order_number']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['payment_method'] ?? 'NULL') . "</strong></td>";
        echo "<td>" . substr(htmlspecialchars($row['razorpay_payment_id'] ?? ''), 0, 30) . "...</td>";
        echo "</tr>";
        $order_ids_1[] = $row['order_id'];
    }
    echo "</table>";
    
    if (!empty($order_ids_1)) {
        $ids_str = implode(',', $order_ids_1);
        $update_query = "UPDATE tbl_orders SET payment_method = 'Razorpay' WHERE order_id IN ($ids_str)";
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_razorpay'])) {
            if (mysqli_query($conn, $update_query)) {
                $affected = mysqli_affected_rows($conn);
                echo "<p style='color: green; font-weight: bold; background: #e8f5e9; padding: 15px; border-radius: 4px; margin: 20px 0;'>✓ Successfully updated <strong>$affected</strong> orders to payment_method = 'Razorpay'</p>";
            } else {
                echo "<p style='color: red;'>Error updating orders: " . mysqli_error($conn) . "</p>";
            }
        } elseif (!isset($_POST['fix_razorpay'])) {
            echo "<form method='post' style='margin: 15px 0;'>";
            echo "<button type='submit' name='fix_razorpay' value='1' class='btn btn-warning' style='background: #ff9800; border: none; padding: 10px 20px; color: white; border-radius: 4px; cursor: pointer; font-weight: bold;'>✓ Fix These $count1 Orders</button>";
            echo "</form>";
        }
    }
} else {
    echo "<p style='color: green;'>✓ No orders found with this issue.</p>";
}

// Priority 2: Orders with payment_status='paid' but payment_method is COD/NULL  
echo "<h3 style='margin-top: 40px;'>Priority 2: Orders with 'Paid' Status but marked as COD</h3>";

$query2 = "SELECT count(*) as cnt FROM tbl_orders WHERE payment_status = 'paid' AND (payment_method = 'COD' OR payment_method IS NULL OR payment_method = '')";
$result2 = mysqli_query($conn, $query2);
$row2 = mysqli_fetch_assoc($result2);
$paid_cod_count = $row2['cnt'];

echo "<p>Found <strong>$paid_cod_count</strong> orders with 'paid' status but marked as COD/Unknown.</p>";
echo "<p><em>⚠️ These orders show payment_status='paid' which indicates payment was completed (likely online).</em></p>";

if ($paid_cod_count > 0) {
    $query3 = "SELECT order_id, order_number, payment_status, payment_method, razorpay_payment_id FROM tbl_orders 
              WHERE payment_status = 'paid' AND (payment_method = 'COD' OR payment_method IS NULL OR payment_method = '')
              ORDER BY order_id DESC
              LIMIT 50";
    $result3 = mysqli_query($conn, $query3);
    
    echo "<table border='1' cellpadding='10' style='margin: 20px 0; border-collapse: collapse;'>";
    echo "<tr style='background-color: #fff3e0;'><th>Order ID</th><th>Order Number</th><th>Payment Status</th><th>Payment Method</th><th>Has Razorpay ID</th></tr>";
    
    $order_ids_2 = [];
    while ($row = mysqli_fetch_assoc($result3)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['order_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['order_number']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['payment_status']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['payment_method'] ?? 'NULL') . "</td>";
        echo "<td>" . (!empty($row['razorpay_payment_id']) ? '✓ Yes' : '✗ No') . "</td>";
        echo "</tr>";
        $order_ids_2[] = $row['order_id'];
    }
    echo "</table>";
    
    if (!empty($order_ids_2)) {
        $ids_str_2 = implode(',', $order_ids_2);
        $update_query_2 = "UPDATE tbl_orders SET payment_method = 'Online' WHERE order_id IN ($ids_str_2)";
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_paid'])) {
            if (mysqli_query($conn, $update_query_2)) {
                $affected = mysqli_affected_rows($conn);
                echo "<p style='color: green; font-weight: bold; background: #e8f5e9; padding: 15px; border-radius: 4px; margin: 20px 0;'>✓ Successfully updated <strong>$affected</strong> orders to payment_method = 'Online'</p>";
            } else {
                echo "<p style='color: red;'>Error updating orders: " . mysqli_error($conn) . "</p>";
            }
        } elseif (!isset($_POST['fix_paid'])) {
            echo "<form method='post' style='margin: 15px 0;'>";
            echo "<button type='submit' name='fix_paid' value='1' class='btn btn-success' style='background: #4caf50; border: none; padding: 10px 20px; color: white; border-radius: 4px; cursor: pointer; font-weight: bold;'>✓ Fix These $paid_cod_count Orders</button>";
            echo "</form>";
        }
    }
}

// Summary
echo "<h3 style='margin-top: 40px; padding-top: 20px; border-top: 2px solid #ddd;'>Summary</h3>";
echo "<p>Total issues found: <strong>" . ($count1 + $paid_cod_count) . "</strong></p>";
echo "<ul>";
echo "<li><strong>$count1</strong> orders with Razorpay Payment ID (should be payment_method='Razorpay')</li>";
echo "<li><strong>$paid_cod_count</strong> orders with payment_status='paid' (should be payment_method='Online')</li>";
echo "</ul>";
?>
