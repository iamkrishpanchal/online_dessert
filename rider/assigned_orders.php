<?php
session_start();
include 'connection.php';

$rider_id = $_SESSION['rider_id'] ?? 0;
if (!$rider_id) {
    $redirectUrl = urlencode($_SERVER['REQUEST_URI']);
    header('Location: login.php?redirect=' . $redirectUrl);
    exit;
}

// optional status message after an action
$statusMsg = '';
if (!empty($_GET['status'])) {
    switch ($_GET['status']) {
        case 'accepted':
            $statusMsg = 'Order accepted. You can now start delivery.';
            break;
        case 'out_for_delivery':
            $statusMsg = 'Order is now out for delivery.';
            break;
        case 'payment_collected':
            $statusMsg = 'Payment collected from customer. You can now mark the order as delivered.';
            break;
        case 'delivered':
            $statusMsg = 'Order marked as delivered.';
            break;
        case 'rejected':
            $statusMsg = 'Order assignment rejected. The vendor may reassign this order.';
            break;
    }
}

// Fetch orders assigned to this rider (pending delivery flow)
$orders = [];
$sql = "SELECT o.order_id, o.order_number, COALESCE(u.user_name, o.customer_name) AS customer_name, o.delivery_address, o.delivery_city, o.delivery_pincode, o.total_amount, o.order_date, o.order_status, o.delivery_status, o.payment_method, o.payment_status, GROUP_CONCAT(oi.product_name SEPARATOR ', ') AS products FROM tbl_orders o LEFT JOIN tbl_users u ON u.user_id = o.user_id LEFT JOIN tbl_order_items oi ON o.order_id = oi.order_id WHERE o.rider_id = $rider_id AND o.delivery_status IN ('assigned','picked_up','out_for_delivery','payment_collected') GROUP BY o.order_id ORDER BY o.order_date DESC";
$res2 = mysqli_query($conn, $sql);

// If query fails (e.g., column doesn't exist), try without payment_method
if (!$res2) {
    $sql = "SELECT o.order_id, o.order_number, COALESCE(u.user_name, o.customer_name) AS customer_name, o.delivery_address, o.delivery_city, o.delivery_pincode, o.total_amount, o.order_date, o.order_status, o.delivery_status, 'COD' AS payment_method, 'Pending' AS payment_status, GROUP_CONCAT(oi.product_name SEPARATOR ', ') AS products FROM tbl_orders o LEFT JOIN tbl_users u ON u.user_id = o.user_id LEFT JOIN tbl_order_items oi ON o.order_id = oi.order_id WHERE o.rider_id = $rider_id AND o.delivery_status IN ('assigned','picked_up','out_for_delivery','payment_collected') GROUP BY o.order_id ORDER BY o.order_date DESC";
    $res2 = mysqli_query($conn, $sql);
}

while ($row = mysqli_fetch_assoc($res2)) {
    $orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <title>Assigned Orders</title>
    
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        body { background: linear-gradient(180deg, #f3f9fd 0%, #f9fbff 100%); }
        .content { background: transparent; }
        .intro-y h2 { color: #075984; font-size: 1.85rem; font-weight: 800; }
        .intro-y .box { border: none; border-radius: 1rem; box-shadow: 0 16px 32px rgba(7, 40, 75, 0.08); overflow: hidden; }
        .intro-y .box p { color: #334e6f; }
        .intro-y .box .header-row { background: linear-gradient(90deg, #0a7bbf 0%, #1c99dc 100%); color: #fff; }
        .intro-y .box .header-row h2 { margin: 0; font-size: 1.2rem; }
        #update-location { background: #0f6f55; border: 1px solid #0f6f55; box-shadow: 0 6px 16px rgba(14, 67, 53, 0.26); }
        #update-location:hover { background: #0e5e4b; border-color: #0e5e4b; }
        .no-data { padding: 2rem; background: #ffffff; border: 1px dashed #bdd4e7; color: #314f6f; border-radius: .75rem; text-align: center; }
        .table thead th { background: rgba(14, 119, 179, 0.12); color: #16486f; border-bottom: 2px solid #8dc8ef; }
        .table tbody tr { background: #fff; transition: background .2s ease; }
        .table tbody tr:hover { background: #f4faff; }
        .table tbody tr td { vertical-align: middle; }
        .btn-success, .btn-danger, .btn-warning { border-radius: 999px; min-width: 95px; margin: 2px 0; }
        .btn-success { background: #1f9a58; border-color: #1f9a58; }
        .btn-danger { background: #dc3545; border-color: #dc3545; }
        .btn-warning { background: #f0ad4e; border-color: #f0ad4e; }
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
                <h2 class="text-lg font-medium mr-auto">Assigned Orders</h2>
            </div>
            <?php if (!empty($statusMsg)): ?>
                <div class="intro-y box p-4 mb-6">
                    <div class="alert alert-success mb-2">
                        <?php echo htmlspecialchars($statusMsg); ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="grid grid-cols-12 gap-6 mt-5">
                <div class="intro-y col-span-12 lg:col-span-12">
                    <div class="intro-y box">
                        <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                            <h2 class="font-medium text-base mr-auto">Orders Assigned To You</h2>
                            </div>
                        <div class="p-5" id="assigned-orders">
                            <div class="preview">
                                <div class="overflow-x-auto">
                                    <?php if (count($orders) === 0): ?>
                                        <div class="no-data">No assigned orders.</div>
                                    <?php else: ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Products</th>
                                                <th>Address</th>
                                                <th>Amount</th>
                                                <th>Order Date</th>
                                                <th>Order Status</th>
                                                <th>Delivery Status</th>
                                                <th>Payment Method</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['order_id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($order['products'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($order['delivery_address']); ?>
                                                    <?php if (!empty($order['delivery_city']) || !empty($order['delivery_pincode'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(trim($order['delivery_city'] . ' ' . $order['delivery_pincode'])); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>₹<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                                                <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                                                <td><?php echo htmlspecialchars($order['delivery_status']); ?></td>
                                                <td style="text-align: center;">
                                                    <?php 
                                                    $pm = isset($order['payment_method']) ? strtolower(trim($order['payment_method'])) : '';
                                                    $ps = isset($order['payment_status']) ? strtolower(trim($order['payment_status'])) : '';
                                                    
                                                    // Check if it's online payment - match payment_method 'online' or payment_status 'paid'
                                                    $isOnline = ($pm === 'online' || $ps === 'paid');
                                                    
                                                    // Display based on payment method
                                                    if ($isOnline) {
                                                        echo '<span style="background-color: #28a745; color: white; padding: 6px 12px; border-radius: 4px; font-weight: 600; font-size: 0.85rem; display: inline-block; white-space: nowrap;">✓ Online Payment</span>';
                                                    } else {
                                                        // COD or Cash payment
                                                        echo '<span style="background-color: #dc3545; color: white; padding: 6px 12px; border-radius: 4px; font-weight: 600; font-size: 0.85rem; display: inline-block; white-space: nowrap;">💳 COD</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <form method="post" action="order_response.php" style="display:inline-block;">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">

                                                        <?php if ($order['delivery_status'] === 'assigned'): ?>
                                                            <button type="submit" name="response" value="accept" class="btn btn-success">Accept</button>
                                                            <button type="submit" name="response" value="reject" class="btn btn-danger">Reject</button>
                                                        <?php elseif ($order['delivery_status'] === 'picked_up'): ?>
                                                            <button type="submit" name="response" value="start_delivery" class="btn btn-warning">Start Delivery</button>
                                                        <?php elseif ($order['delivery_status'] === 'out_for_delivery'): ?>
                                                            <?php 
                                                            // Get payment method and status
                                                            $paymentMethod = isset($order['payment_method']) ? trim($order['payment_method']) : '';
                                                            $paymentStatus = isset($order['payment_status']) ? strtolower(trim($order['payment_status'])) : '';
                                                            
                                                            // Check if it's a COD order and payment hasn't been collected yet
                                                            $isCOD = (strtoupper($paymentMethod) === 'COD');
                                                            $isPaid = ($paymentStatus === 'paid');
                                                            
                                                            // For COD orders not yet paid, show collect payment button
                                                            if ($isCOD && !$isPaid):
                                                            ?>
                                                                <button type="submit" name="response" value="collect_payment" class="btn btn-warning" style="background-color: #ff9800; border-color: #ff9800;" title="Collect ₹<?php echo number_format($order['total_amount'], 2); ?> from customer">💰 Collect Payment</button>
                                                            <?php else: ?>
                                                                <button type="submit" name="response" value="delivered" class="btn btn-success">Mark Delivered</button>
                                                            <?php endif; ?>
                                                        <?php elseif ($order['delivery_status'] === 'payment_collected'): ?>
                                                            <button type="submit" name="response" value="delivered" class="btn btn-success">Mark Delivered</button>
                                                        <?php else: ?>
                                                            <span class="text-muted">No action</span>
                                                        <?php endif; ?>
                                                    </form>
                                                </td>
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

    <script>
    function postRiderLocation(lat, lng) {
        return fetch('update_location.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`
        }).then(r => r.json());
    }

    function updateLocationButtonState(isUpdating) {
        const btn = document.getElementById('update-location');
        if (!btn) return;
        btn.disabled = isUpdating;
        btn.textContent = isUpdating ? 'Updating...' : 'Update my location';
    }

    function updateLocationOnce(showAlert) {
        if (!navigator.geolocation) {
            if (showAlert) alert('Geolocation is not supported by your browser.');
            return;
        }

        updateLocationButtonState(true);

        navigator.geolocation.getCurrentPosition(function(position) {
            postRiderLocation(position.coords.latitude, position.coords.longitude)
                .then(resp => {
                    if (showAlert) {
                        if (resp.success) {
                            alert('Location updated. Customers will now see your position.');
                        } else {
                            alert('Failed to update location: ' + (resp.message || 'unknown'));
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (showAlert) alert('Failed to update location.');
                })
                .finally(() => {
                    updateLocationButtonState(false);
                });
        }, function(err) {
            if (showAlert) alert('Unable to get location: ' + err.message);
            updateLocationButtonState(false);
        }, { enableHighAccuracy: true, timeout: 10000 });
    }

    document.getElementById('update-location').addEventListener('click', function() {
        updateLocationOnce(true);
    });

    // Auto-update location every 20 seconds while the rider has this page open
    setInterval(function() {
        updateLocationOnce(false);
    }, 20000);
    </script>
</body>
</html>
