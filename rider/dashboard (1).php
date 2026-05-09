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
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rider Dashboard</title>
    <link rel="stylesheet" href="dist/css/app.css" />
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
                            <div class="intro-y box p-10 min-h-[260px] bg-primary/20">
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
                            <div class="intro-y box p-10 min-h-[260px] bg-success/20">
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
                            <div class="intro-y box p-10 min-h-[260px] bg-warning/20">
                                <div class="flex items-center">
                                    <div class="w-16 h-16 flex items-center justify-center rounded-full bg-warning/30 text-warning mr-4">
                                        <i data-lucide="dollar-sign" class="w-8 h-8"></i>
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
                <!-- Assigned orders preview -->
                <div class="col-span-12 xl:col-span-4">
                    <div class="intro-y box p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium">Upcoming Assignments</h3>
                            <a href="assigned_orders.php" class="text-primary ml-4">See all</a>
                        </div>
                        <div class="mt-4 overflow-x-auto">
                            <?php if (count($assignedOrders) === 0): ?>
                                <div class="text-center py-10 text-slate-500">No assigned orders right now.</div>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Status</th>
                                            <th class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignedOrders as $order): ?>
                                            <tr>
                                                <td class="whitespace-nowrap">#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                                <td class="whitespace-nowrap">
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo ($order['delivery_status'] === 'assigned' ? 'bg-primary/10 text-primary' : ($order['delivery_status'] === 'picked_up' ? 'bg-warning/10 text-warning' : 'bg-success/10 text-success')); ?>">
                                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['delivery_status']))); ?>
                                                    </span>
                                                </td>
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
