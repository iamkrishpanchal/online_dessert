<?php
include 'session.php';
include 'connection.php';
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <title>Pending Orders</title>
    <link rel="stylesheet" href="dist/css/app.css" />
</head>

<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <!-- BEGIN: Side Menu -->
        <?php include 'sideMenu.php' ?>
        <!-- END: Side Menu -->
        
        <!-- BEGIN: Content -->
        <div class="content">
            <!-- BEGIN: Top Bar -->
            <?php include 'topBar.php' ?>
            <!-- END: Top Bar -->
            
            <div class="intro-y flex items-center mt-8">
                <h2 class="text-lg font-medium mr-auto">Pending Orders</h2>
            </div>
            
            <div class="grid grid-cols-12 gap-6 mt-5">
                <div class="intro-y col-span-12 lg:col-span-12">
                    <div class="intro-y box">
                        <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                            <h2 class="font-medium text-base mr-auto">Pending Order List</h2>
                        </div>
                        
                        <div class="p-5" id="basic-table">
                            <div class="preview">
                                <div class="overflow-x-auto">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th class="whitespace-nowrap">Sr No.</th>
                                                <th class="whitespace-nowrap">Order Number</th>
                                                <th class="whitespace-nowrap">Customer Name</th>
                                                <th class="whitespace-nowrap">Customer Phone</th>
                                                <th class="whitespace-nowrap">Product</th>
                                                <th class="whitespace-nowrap">Quantity</th>
                                                <th class="whitespace-nowrap">Total Amount</th>
                                                <th class="whitespace-nowrap">Payment Method</th>
                                                <th class="whitespace-nowrap">Order Date</th>
                                                <th class="whitespace-nowrap">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $vendor_id = $_SESSION['vendor_id'] ?? null;
                                            if (!$vendor_id) {
                                                echo "<tr><td colspan='10'>Vendor ID not found. Please login again.</td></tr>";
                                            } else {
                                                $query = "SELECT o.order_id, o.order_number, o.customer_name, o.customer_phone, 
                                                         GROUP_CONCAT(oi.product_name SEPARATOR ', ') AS products, 
                                                         SUM(oi.quantity) AS total_qty, 
                                                         o.total_amount, o.payment_method, 
                                                         o.order_date, o.order_status
                                                         FROM tbl_orders o
                                                         LEFT JOIN tbl_order_items oi ON oi.order_id = o.order_id
                                                         WHERE o.order_status = 'Pending' AND o.vendor_id = ?
                                                         GROUP BY o.order_id
                                                         ORDER BY o.order_date DESC";
                                                
                                                $stmt = mysqli_prepare($conn, $query);
                                                if ($stmt) {
                                                    mysqli_stmt_bind_param($stmt, 'i', $vendor_id);
                                                    mysqli_stmt_execute($stmt);
                                                    $result = mysqli_stmt_get_result($stmt);
                                                    $count = 1;
                                                    
                                                    if (mysqli_num_rows($result) > 0) {
                                                        while ($row = mysqli_fetch_assoc($result)) {
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $count++; ?></td>
                                                        <td><?php echo htmlspecialchars($row["order_number"] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($row["customer_name"] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($row["customer_phone"] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($row["products"] ?? 'N/A'); ?></td>
                                                        <td><?php echo $row["total_qty"] ?: 0; ?></td>
                                                        <td>Rs. <?php echo number_format($row["total_amount"], 2); ?></td>
                                                        <td><?php echo $row["payment_method"]; ?></td>
                                                        <td><?php echo date('d-m-Y H:i', strtotime($row["order_date"])); ?></td>
                                                        <td>
                                                            <button class="btn btn-primary btn-sm" onclick="confirmOrder(<?php echo $row['order_id']; ?>)">Confirm</button>
                                                            <button class="btn btn-danger btn-sm" onclick="cancelOrder(<?php echo $row['order_id']; ?>)">Cancel</button>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                        }
                                                    } else {
                                                        ?>
                                                        <tr>
                                                            <td colspan="10" class="text-center">No pending orders found</td>
                                                        </tr>
                                                        <?php
                                                    }
                                                    mysqli_stmt_close($stmt);
                                                } else {
                                                    echo "<tr><td colspan='10'>Database error.</td></tr>";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- END: Content -->
    </div>

    <!-- BEGIN: JS Assets-->
    <script src="dist/js/app.js"></script>
    <!-- END: JS Assets-->
    <script>
        function confirmOrder(orderId) {
            if (confirm('Are you sure you want to confirm this order?')) {
                window.location.href = 'updateOrderStatus.php?order_id=' + orderId + '&status=Confirmed';
            }
        }
        
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                window.location.href = 'updateOrderStatus.php?order_id=' + orderId + '&status=Cancelled';
            }
        }
    </script>
</body>
</html>
