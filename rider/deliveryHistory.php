<?php
session_start();
include 'connection.php';

$rider_id = $_SESSION['rider_id'] ?? 0;
if (!$rider_id) {
    header('Location: login.php');
    exit;
}

// Fetch delivered orders for this rider (using tracking logs for delivered timestamp)
$sql = "SELECT o.order_id, o.order_number, u.user_name AS customer_name, o.delivery_address, o.delivery_city, o.delivery_pincode, o.total_amount, o.order_date, MAX(t.created_at) AS delivered_at
        FROM tbl_orders o
        JOIN tbl_users u ON u.user_id = o.user_id
        JOIN tbl_order_tracking t ON t.order_id = o.order_id AND t.status = 'delivered'
        WHERE o.rider_id = ?
        GROUP BY o.order_id, o.order_number, u.user_name, o.delivery_address, o.delivery_city, o.delivery_pincode, o.total_amount, o.order_date
        ORDER BY delivered_at DESC";

$stmt = mysqli_prepare($conn, $sql);
$orders = [];
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $rider_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $orders[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <title>Delivery History</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        body { background: linear-gradient(180deg, #f3f9fd 0%, #fafcff 100%); }
        .content { background: transparent; }
        .intro-y h2 { font-size: 1.9rem; font-weight: 800; color: #134f7d; }
        .intro-y .box { border:none; border-radius: 1rem; box-shadow: 0 16px 28px rgba(30, 90, 125, .1); }
        .intro-y .box .flex.items-center { background: linear-gradient(90deg, #0d7cac, #1795cc); color: #fff; border-radius: 1rem 1rem 0 0; }
        .intro-y .box .flex.items-center h2 { color: #fff; }
        .no-data { padding: 2rem; background: #fff; color: #3c5c78; border: 1px dashed #87c1e0; border-radius: .8rem; text-align: center; }
        .table thead th { background: rgba(19, 119, 170, 0.14); color: #1b4a6d; border-bottom: 1px solid #b8d8ea; }
        .table tbody tr { background: #fff; transition: background .18s ease; }
        .table tbody tr:hover { background: #e9f5ff; }
        .table { border-radius: 0.75rem; overflow: hidden; }
    </style>
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <?php include 'sideMenu.php'; ?>
        <div class="content">
            <!-- BEGIN: Top Bar -->
            <?php include 'topBar.php'; ?>
            <!-- END: Top Bar -->
            <div class="intro-y flex items-center mt-8">
                <h2 class="text-lg font-medium mr-auto">Delivery History</h2>
            </div>
            <div class="grid grid-cols-12 gap-6 mt-5">
                <div class="intro-y col-span-12 lg:col-span-12">
                    <div class="intro-y box">
                        <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                            <h2 class="font-medium text-base mr-auto">Delivered Orders</h2>
                        </div>
                        <div class="p-5" id="delivery-history">
                            <div class="preview">
                                <div class="overflow-x-auto">
                                    <?php if (count($orders) === 0): ?>
                                        <div class="no-data">No delivered orders yet.</div>
                                    <?php else: ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Address</th>
                                                <th>Amount</th>
                                                <th>Order Date</th>
                                                <th>Delivered At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['order_id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($order['delivery_address']); ?>
                                                    <?php if (!empty($order['delivery_city']) || !empty($order['delivery_pincode'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(trim($order['delivery_city'] . ' ' . $order['delivery_pincode'])); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>₹<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                                                <td><?php echo htmlspecialchars($order['delivered_at']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
