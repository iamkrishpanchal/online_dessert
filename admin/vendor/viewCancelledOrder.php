<?php
include 'session.php';
include 'connection.php';
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <title>Cancelled Orders</title>
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
                <h2 class="text-lg font-medium mr-auto">Cancelled Orders</h2>
            </div>
            
            <div class="grid grid-cols-12 gap-6 mt-5">
                <div class="intro-y col-span-12 lg:col-span-12">
                    <div class="intro-y box">
                        <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                            <h2 class="font-medium text-base mr-auto">Cancelled Order List</h2>
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
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $vendor_id = $_SESSION['vendor_id'] ?? null;
                                            if (!$vendor_id) {
                                                echo "<tr><td colspan='9'>Vendor ID not found. Please login again.</td></tr>";
                                            } else {
                                                $query = "SELECT o.order_id, o.order_number, o.customer_name, o.customer_phone, 
                                                         p.product_name, o.quantity, o.total_amount, o.payment_method, 
                                                         o.order_date, o.order_status
                                                         FROM tbl_orders o
                                                         LEFT JOIN tbl_products p ON o.product_id = p.product_id AND p.vendor_id = ?
                                                         WHERE o.order_status = 'Cancelled' AND p.vendor_id = ?
                                                         ORDER BY o.order_date DESC";
                                                
                                                $stmt = mysqli_prepare($conn, $query);
                                                if ($stmt) {
                                                    mysqli_stmt_bind_param($stmt, 'ii', $vendor_id, $vendor_id);
                                                    mysqli_stmt_execute($stmt);
                                                    $result = mysqli_stmt_get_result($stmt);
                                                    $count = 1;
                                                    
                                                    if (mysqli_num_rows($result) > 0) {
                                                        while ($row = mysqli_fetch_assoc($result)) {
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $count++; ?></td>
                                                        <td><?php echo htmlspecialchars($row["order_number"]); ?></td>
                                                        <td><?php echo htmlspecialchars($row["customer_name"]); ?></td>
                                                        <td><?php echo htmlspecialchars($row["customer_phone"]); ?></td>
                                                        <td><?php echo htmlspecialchars($row["product_name"] ?? 'N/A'); ?></td>
                                                        <td><?php echo $row["quantity"]; ?></td>
                                                        <td>Rs. <?php echo number_format($row["total_amount"], 2); ?></td>
                                                        <td><?php echo $row["payment_method"]; ?></td>
                                                        <td><?php echo date('d-m-Y H:i', strtotime($row["order_date"])); ?></td>
                                                    </tr>
                                                    <?php
                                                        }
                                                    } else {
                                                        ?>
                                                        <tr>
                                                            <td colspan="9" class="text-center">No cancelled orders found</td>
                                                        </tr>
                                                        <?php
                                                    }
                                                    mysqli_stmt_close($stmt);
                                                } else {
                                                    echo "<tr><td colspan='9'>Database error.</td></tr>";
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
</body>
</html>
