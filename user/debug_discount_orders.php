<?php
session_start();
include 'connection.php';

if (empty($_SESSION['user_id'])) {
    die('Please login first');
}

$user_id = $_SESSION['user_id'];

?><!DOCTYPE html>
<html>
<head>
    <title>Debug - Discount on Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h2>Debug: Voucher Discount on Multi-Vendor Orders</h2>
    
    <div class="card mb-3">
        <div class="card-header">
            <h5>Your Recent Orders</h5>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Vendor</th>
                        <th>Subtotal</th>
                        <th>Tax</th>
                        <th>Discount</th>
                        <th>Total</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT 
                        o.order_id,
                        o.vendor_id,
                        o.subtotal,
                        o.tax,
                        o.discount,
                        o.total_amount,
                        o.payment_status,
                        v.shop_name
                    FROM tbl_orders o
                    LEFT JOIN tbl_vendors v ON o.vendor_id = v.vendor_id
                    WHERE o.user_id = ?
                    ORDER BY o.order_id DESC
                    LIMIT 10";
                    
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, 'i', $user_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    $total_discount = 0;
                    while ($row = mysqli_fetch_assoc($result)) {
                        $discount = floatval($row['discount'] ?? 0);
                        $total_discount += $discount;
                        ?>
                        <tr>
                            <td><strong><?php echo $row['order_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['shop_name'] ?? 'Unknown'); ?></td>
                            <td>₹<?php echo number_format($row['subtotal'], 2); ?></td>
                            <td>₹<?php echo number_format($row['tax'], 2); ?></td>
                            <td style="<?php echo $discount > 0 ? 'color: green; font-weight: bold;' : ''; ?>">
                                <?php echo $discount > 0 ? '-₹' . number_format($discount, 2) : '₹0.00'; ?>
                            </td>
                            <td><strong>₹<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                            <td><?php echo $row['payment_status']; ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            
            <div class="alert alert-info mt-3">
                <strong>Total Discount Applied Across All Orders:</strong> ₹<?php echo number_format($total_discount, 2); ?>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Analysis</h5>
        </div>
        <div class="card-body">
            <?php
            // Get last combined order
            $sql_last = "SELECT 
                u.order_id,
                group_concat(v.shop_name SEPARATOR ', ') as vendors,
                sum(u.subtotal) as total_subtotal,
                sum(u.tax) as total_tax,
                sum(u.discount) as total_discount_applied,
                sum(u.total_amount) as grand_total
            FROM tbl_orders u
            LEFT JOIN tbl_vendors v ON u.vendor_id = v.vendor_id
            WHERE u.user_id = ?
            GROUP BY DATE(u.created_at), u.user_id
            ORDER BY u.created_at DESC
            LIMIT 1";
            
            $stmt2 = mysqli_prepare($conn, $sql_last);
            mysqli_stmt_bind_param($stmt2, 'i', $user_id);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);
            
            if ($last_order = mysqli_fetch_assoc($result2)) {
                $subtotal = floatval($last_order['total_subtotal']);
                $tax = floatval($last_order['total_tax']);
                $discount = floatval($last_order['total_discount_applied']);
                $total = floatval($last_order['grand_total']);
                $expected_total = $subtotal + $tax - $discount;
                ?>
                <h6>Last Combined Order:</h6>
                <p><strong>Vendors:</strong> <?php echo htmlspecialchars($last_order['vendors'] ?? 'N/A'); ?></p>
                <table class="table">
                    <tr>
                        <td>Subtotal (all vendors):</td>
                        <td><strong>₹<?php echo number_format($subtotal, 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Tax (5%):</td>
                        <td><strong>₹<?php echo number_format($tax, 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Discount Applied:</td>
                        <td><strong style="color: green;">-₹<?php echo number_format($discount, 2); ?></strong></td>
                    </tr>
                    <tr style="background-color: #fff3cd;">
                        <td><strong>Expected Total:</strong></td>
                        <td><strong>₹<?php echo number_format($expected_total, 2); ?></strong></td>
                    </tr>
                    <tr style="background-color: #fff3cd;">
                        <td><strong>Actual Total Charged:</strong></td>
                        <td><strong>₹<?php echo number_format($total, 2); ?></strong></td>
                    </tr>
                    <tr <?php echo ($expected_total == $total) ? 'style="background-color: #d4edda;"' : 'style="background-color: #f8d7da;"'; ?>>
                        <td><strong>Status:</strong></td>
                        <td>
                            <?php 
                            if ($expected_total == $total) {
                                echo '<span class="badge bg-success">✓ Correct</span>';
                            } else {
                                echo '<span class="badge bg-danger">✗ Mismatch! Missing: ₹' . number_format($expected_total - $total, 2) . '</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                <?php
            } else {
                echo '<p class="text-muted">No orders found</p>';
            }
            ?>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="index.php" class="btn btn-primary">Back to Home</a>
        <a href="orders.php" class="btn btn-secondary">View All Orders</a>
    </div>
    
</div>
</body>
</html>
