<?php
include 'session.php';
include 'connection.php';

$rider_id = $_SESSION['rider_id'] ?? 0;
$rider_name = $_SESSION['rider_name'] ?? 'Rider';

// Summary stats
$summary = [
    'assigned_count' => 0,
    'delivered_count' => 0,
    'earnings' => 0.0,
];
$summaryStmt = mysqli_prepare($conn, "SELECT 
    SUM(CASE WHEN delivery_status IN ('assigned','picked_up','out_for_delivery') THEN 1 ELSE 0 END) AS assigned_count,
    SUM(CASE WHEN delivery_status = 'delivered' THEN 1 ELSE 0 END) AS delivered_count,
    IFNULL(SUM(CASE WHEN delivery_status = 'delivered' THEN delivery_charges ELSE 0 END), 0) AS earnings
FROM tbl_orders
WHERE rider_id = ?");
if ($summaryStmt) {
    mysqli_stmt_bind_param($summaryStmt, 'i', $rider_id);
    mysqli_stmt_execute($summaryStmt);
    $res = mysqli_stmt_get_result($summaryStmt);
    if ($res) {
        $summary = mysqli_fetch_assoc($res);
    }
    mysqli_stmt_close($summaryStmt);
}

// Get next assigned orders (pending delivery)
$assignedOrders = [];
$ordersStmt = mysqli_prepare($conn, "SELECT order_id, order_number, delivery_address, delivery_city, delivery_pincode, total_amount, order_date, delivery_status FROM tbl_orders WHERE rider_id = ? AND delivery_status IN ('assigned','picked_up','out_for_delivery') ORDER BY order_date ASC LIMIT 5");
if ($ordersStmt) {
    mysqli_stmt_bind_param($ordersStmt, 'i', $rider_id);
    mysqli_stmt_execute($ordersStmt);
    $res = mysqli_stmt_get_result($ordersStmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $assignedOrders[] = $row;
    }
    mysqli_stmt_close($ordersStmt);
}

// Get last delivered orders for the rider
$deliveredOrders = [];
$deliveredStmt = mysqli_prepare($conn, "SELECT o.order_id, o.order_number, COALESCE(NULLIF(u.user_name, ''), NULLIF(o.customer_name, ''), 'Guest') AS customer_name, o.total_amount, o.order_date FROM tbl_orders o LEFT JOIN tbl_users u ON o.user_id = u.user_id WHERE o.rider_id = ? AND o.delivery_status = 'delivered' ORDER BY o.order_date DESC LIMIT 5");
if ($deliveredStmt) {
    mysqli_stmt_bind_param($deliveredStmt, 'i', $rider_id);
    mysqli_stmt_execute($deliveredStmt);
    $res = mysqli_stmt_get_result($deliveredStmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $deliveredOrders[] = $row;
    }
    mysqli_stmt_close($deliveredStmt);
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rider Dashboard</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        /* Rider Dashboard custom theme */
        .content { background: linear-gradient(180deg, #f4fbff 0%, #f6fbf8 25%, #f9fcff 100%); }
        .intro-y h2, .intro-y h3 { color: #0f3f5f; }
        .box { border: 1px solid rgba(7, 72, 67, 0.13); border-radius: 1rem; box-shadow: 0 18px 30px rgba(7, 63, 98, 0.08); transition: transform .25s ease, box-shadow .25s ease; }
        .box[data-card="stats"] { background: linear-gradient(135deg, rgba(31, 185, 243, 0.10), rgba(50, 211, 144, 0.12)); border: 1px solid rgba(34, 179, 207, 0.3); }        .box:hover { transform: translateY(-3px); box-shadow: 0 24px 38px rgba(7, 63, 98, 0.15); }
        .btn-outline-primary { border-color: #1589f2; color: #1589f2; background: rgba(21,137,242,0.08); }
        .btn-outline-success { border-color: #28a745; color: #28a745; background: rgba(40,167,69,0.08); }
        .btn-outline-warning { border-color: #ebc006; color: #ebc006; background: rgba(235,192,6,0.11); }
        .btn-outline-primary:hover, .btn-outline-success:hover, .btn-outline-warning:hover { color: #fff !important; }
        .btn-outline-primary:hover { background: #1589f2; border-color: #1589f2; }
        .btn-outline-success:hover { background: #28a745; border-color: #28a745; }
        .btn-outline-warning:hover { background: #ebc006; border-color: #ebc006; }
        .grid .box { min-height: 260px; }
        .table thead th { border-bottom: 2px solid #a7ebd8; background: #e0f6ff; color: #205b83; }
        .table tbody tr:hover { background: #edf8ff; }
        .table { border-radius: 0.75rem; overflow: hidden; }
        .text-primary { color: #0e78e4 !important; }
        .side-nav { box-shadow: 2px 0 20px rgba(20, 53, 93, 0.22); }
        .side-menu { font-weight: 600; } 
        .side-menu.active { background: linear-gradient(135deg, #025c8f, #086b9f) !important; }
        .top-bar { box-shadow: none; }
    </style>
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <?php include 'sideMenu.php'; ?>
        <div class="content">
            <?php include 'topBar.php'; ?>

            <div class="intro-y flex items-center mt-8">
                <h2 class="text-lg font-medium mr-auto">Dashboard</h2>
            </div>

            <div class="grid grid-cols-12 gap-6 mt-5">
                <!-- Stats cards -->
                <div class="col-span-12 xl:col-span-8">
                    <div class="grid grid-cols-12 gap-6">
                        <div class="col-span-12 md:col-span-4">
                            <div class="intro-y box p-10 min-h-[260px] bg-primary/20" data-card="stats">
                                <div class="flex items-center">
                                    <div class="w-16 h-16 flex items-center justify-center rounded-full bg-primary/30 text-primary mr-4">
                                        <i data-lucide="truck" class="w-8 h-8"></i>
                                    </div>
                                    <div>
                                        <div class="text-slate-500 text-xs">Assigned Orders</div>
                                        <div class="text-2xl font-medium"><?php echo intval($summary['assigned_count']); ?></div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="assigned_orders.php" class="btn btn-outline-primary w-full">Manage orders</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <div class="intro-y box p-10 min-h-[260px] bg-success/20" data-card="stats">
                                <div class="flex items-center">
                                    <div class="w-16 h-16 flex items-center justify-center rounded-full bg-success/30 text-success mr-4">
                                        <i data-lucide="check-circle" class="w-8 h-8"></i>
                                    </div>
                                    <div>
                                        <div class="text-slate-500 text-xs">Delivered Orders</div>
                                        <div class="text-2xl font-medium"><?php echo intval($summary['delivered_count']); ?></div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="deliveryHistory.php" class="btn btn-outline-success w-full">View history</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <div class="intro-y box p-10 min-h-[260px] bg-warning/20" data-card="stats">
                                <div class="flex items-center">
                                    <div class="w-16 h-16 flex items-center justify-center rounded-full bg-warning/30 text-warning mr-4">
                                        <i data-lucide="indian-rupee" class="w-8 h-8"></i>
                                    </div>
                                    <div>
                                        <div class="text-slate-500 text-xs">Total Fixed Delivery Charges (₹50/order)</div>
                                        <div class="text-2xl font-medium">₹<?php echo number_format(floatval($summary['earnings']), 2); ?></div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="earnings.php" class="btn btn-outline-warning w-full">View earnings</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <br>
                <!-- Delivered orders preview -->
                <div class="col-span-12 xl:col-span-12">
                    <div class="intro-y box p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium">Recently Delivered Orders</h3>
                            <a href="deliveryHistory.php" class="text-primary ml-4">See all</a>
                        </div>
                        <div class="mt-4 overflow-x-auto">
                            <?php if (count($deliveredOrders) === 0): ?>
                                <div class="text-center py-10 text-slate-500">No delivered orders yet.</div>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($deliveredOrders as $order): ?>
                                            <tr>
                                                <td class="whitespace-nowrap">#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td class="whitespace-nowrap"><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                                <td class="text-right">₹<?php echo number_format((float)$order['total_amount'], 2); ?></td>
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

    <script src="dist/js/app.js"></script>
</body>
</html>
