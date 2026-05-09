
<?php
// Rider assignment is ADMIN-ONLY functionality
// Vendors are no longer allowed to assign riders
session_start();

// Redirect back to vendor dashboard
header('Location: admin/vendor/dashboard.php');
exit;

/*
DISABLED: Vendor rider assignment removed
This functionality is now restricted to Admin only.
Admins can assign riders from the Order Details page.
*/
?>

function formatCurrency($amount) {
    return number_format((float)$amount, 2);
}

$pendingOrdersList = [];
$pendingOrdersQuery = "SELECT order_id, customer_name, total_amount, order_date, rider_id FROM tbl_orders WHERE vendor_id = {$vendor_id} AND order_status = 'Pending'";
if ($res3 = mysqli_query($conn, $pendingOrdersQuery)) {
    while ($row = mysqli_fetch_assoc($res3)) {
        $pendingOrdersList[] = $row;
    }
}

$ridersList = [];
$ridersQuery = "SELECT rider_id, name FROM tbl_riders WHERE status = 'active'";
if ($res4 = mysqli_query($conn, $ridersQuery)) {
    while ($row = mysqli_fetch_assoc($res4)) {
        $ridersList[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <title>Assign Rider</title>
    <link rel="stylesheet" href="admin/dist/css/app.css" />
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <!-- BEGIN: Side Menu -->
        <?php include 'admin/vendor/sideMenu.php'; ?>
        <!-- END: Side Menu -->
        <!-- BEGIN: Content -->
        <div class="content">
            <!-- BEGIN: Top Bar -->
            <?php include 'admin/vendor/topBar.php'; ?>
            <!-- END: Top Bar -->
            <div class="intro-y flex items-center mt-8">
                <h2 class="text-lg font-medium mr-auto">Assign Rider to Pending Orders</h2>
            </div>
            <div class="grid grid-cols-12 gap-6 mt-5">
                <div class="intro-y col-span-12 lg:col-span-12">
                    <div class="intro-y box">
                        <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                            <h2 class="font-medium text-base mr-auto">Pending Orders</h2>
                        </div>
                        <div class="p-5" id="pending-orders">
                            <div class="preview">
                                <div class="overflow-x-auto">
                                    <?php if (count($pendingOrdersList) === 0): ?>
                                        <div class="no-data">No pending orders to assign.</div>
                                    <?php else: ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Order Date</th>
                                                <th>Assign Rider</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pendingOrdersList as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['order_id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td>₹<?php echo formatCurrency($order['total_amount']); ?></td>
                                                <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                                                <td>
                                                    <?php if ($order['rider_id']): ?>
                                                        <span style="color:#2563eb;font-weight:600;">Assigned</span>
                                                    <?php else: ?>
                                                        <form method="post" action="vendor_assign_rider.php" style="display:inline-block;" onsubmit="return assignRider(this,event)">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                            <select name="rider_id" required style="padding:0.4rem 0.7rem;border-radius:0.5rem;border:1px solid #cbd5e1;">
                                                                <option value="">Select Rider</option>
                                                                <?php foreach ($ridersList as $rider): ?>
                                                                    <option value="<?php echo $rider['rider_id']; ?>"><?php echo htmlspecialchars($rider['name']); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <button type="submit" style="margin-left:0.5rem;padding:0.4rem 1rem;border-radius:0.5rem;background:#2563eb;color:#fff;border:none;font-weight:600;">Assign</button>
                                                        </form>
                                                    <?php endif; ?>
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
        <!-- END: Content -->
    </div>
    <script>
    function assignRider(form, event) {
        event.preventDefault();
        var fd = new FormData(form);
        fetch('vendor_assign_rider.php', {
            method: 'POST',
            body: fd
        })
        .then(resp => resp.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload();
            }
        });
        return false;
    }
    </script>
</body>
</html>
