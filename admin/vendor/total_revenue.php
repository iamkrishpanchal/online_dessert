<?php
include 'session.php';
include 'connection.php';

$vendor_id = intval($_SESSION['vendor_id']);

// Get filter parameters
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'today';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

function fetchScalar($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if (!$res) {
        return 0;
    }
    $row = mysqli_fetch_row($res);
    return $row ? ($row[0] ?? 0) : 0;
}

// Commission rates
const ADMIN_COMMISSION = 0.15; // 15%
// NOTE: Rider compensation is now a fixed ₹50 delivery charge per order (not percentage-based)
// const RIDER_COMMISSION = 0.10; // 10% - LEGACY, kept for reference

// compute total gross revenue and net earnings for this vendor
// gross is just sum of full total_amount; net excludes delivery charges and discounts
$grossRevenue = fetchScalar($conn, "SELECT IFNULL(SUM(total_amount),0) FROM tbl_orders WHERE vendor_id = {$vendor_id} AND order_status = 'Completed' AND payment_status = 'Paid'");

// calculate vendor earnings based on 15% admin commission on order amount (subtotal + GST)
$baseEarnings = fetchScalar($conn, "SELECT IFNULL(SUM((IFNULL(subtotal,0) + IFNULL(tax,0)) * 0.85),0) FROM tbl_orders WHERE vendor_id = {$vendor_id} AND order_status = 'Completed' AND payment_status = 'Paid'");

// Total orders count
$totalOrders = fetchScalar($conn, "SELECT COUNT(*) FROM tbl_orders WHERE vendor_id = {$vendor_id}");

// Revenue by status (optional)
$revenueCompleted = fetchScalar($conn, "SELECT IFNULL(SUM(total_amount),0) FROM tbl_orders WHERE vendor_id = {$vendor_id} AND order_status = 'Completed'");
$revenuePending = fetchScalar($conn, "SELECT IFNULL(SUM(total_amount),0) FROM tbl_orders WHERE vendor_id = {$vendor_id} AND order_status = 'Pending'");

// Vendor earnings are based on 85% of total order after fixed delivery fee
$vendorNetRevenue = max(0, $baseEarnings);

// Recent orders with filters
$orders = [];
// Build query with filters
$query = "SELECT order_id, order_number, total_amount, subtotal, tax, IFNULL(discount,0) AS discount, 50 AS delivery_charges, order_status, order_date, 
    (IFNULL(subtotal,0) + IFNULL(tax,0) - IFNULL(discount,0)) AS amount_before_delivery,
    ((IFNULL(subtotal,0) + IFNULL(tax,0)) * 0.85) AS vendor_earning
    FROM tbl_orders
    WHERE vendor_id = {$vendor_id}
      AND order_status = 'Completed'
      AND payment_status = 'Paid'";

// Apply filters
if ($filter_type === 'today') {
    $query .= " AND DATE(order_date) = CURDATE()";
} elseif ($filter_type === 'custom' && $from_date && $to_date) {
    $query .= " AND DATE(order_date) BETWEEN '{$from_date}' AND '{$to_date}'";
}

$query .= " ORDER BY order_date DESC LIMIT 50";

// include computed earning per order using fixed delivery and admin commission
$orderRes = mysqli_query($conn, $query);
if ($orderRes) {
    while ($row = mysqli_fetch_assoc($orderRes)) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vendor Total Revenue - Dessert Magic</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        .admin-page { background: #f3f7fd; color: #1f2d51; }
        .admin-page .content { padding: 1.25rem; }
        .admin-page .wrapper { max-width: 1180px; margin: 0 auto; }
        .admin-page .intro-y.box { border-radius: 16px; border: 1px solid #dbe3f7; box-shadow: 0 11px 26px rgba(12, 30, 72, 0.13); background: #fff; }
        .admin-page .intro-y.box .p-5 { padding: 1.2rem; }
        .admin-page .top-bar { border-bottom: 1px solid #e1e8f3; }
        .admin-page .table { border: 1px solid #e7edf8; border-radius: 10px; }
        .admin-page .table th, .admin-page .table td { padding: 0.75rem 1rem; }
        .admin-page .table thead { background: linear-gradient(90deg, #20508c, #1d375f); color: #fff; }
        .admin-page .no-data { padding: 1.25rem; text-align: center; color: #5a6d91; }
        .admin-page .dashboard-metric { background: #fff; border: 1px solid #e5ecfa; border-radius: 12px; }
    </style>
    <style>
        body { background-color: #f1f5fb; color: #203050; }
        .content { padding: 1.25rem; }
        .wrapper { max-width: 1180px; margin: 0 auto; }
        .intro-y.box { border-radius: 16px; box-shadow: 0 16px 30px rgba(8, 20, 51, 0.12); border: 1px solid #dbe2f2; }
        .intro-y.box .p-5 { padding: 1.25rem; }
        .top-bar { background: transparent; border-bottom: 1px solid #e3e8f4; }
        .top-bar .breadcrumb { color:#6c7a99; }
        .top-bar .dropdown-toggle { background: #ffffff; border: 1px solid #d7e1f0; }
        .top-bar .dropdown-toggle .font-medium { display: none; }
        .box h2 { font-weight: 700; color: #1f2f55; }
        .table { border: 1px solid #e3ebf8; border-radius: 12px; overflow: hidden; }
        .table thead { background: linear-gradient(90deg, #1f3c8e, #1e2f66); color: #ffffff; }
        .table th, .table td { padding: .75rem 1rem; border-bottom: 1px solid #e6ecf8; }
        .table tbody tr:nth-child(odd) { background: #ffffff; }
        .table tbody tr:hover { background: #e7f2ff; }
        .no-data { padding: 1.25rem; text-align: center; color: #5a6f92; }
        .dashboard-metric { border: 1px solid #e3e8f5; border-radius: 12px; background: #fff; }
        .dashboard-metric h3 { margin-bottom: 0.25rem; }
    </style>
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent admin-page">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <?php include 'sideMenu.php'; ?>
        <div class="content">
            <!-- BEGIN: Top Bar -->
            <?php include 'topBar.php' ?>
            <!-- END: Top Bar -->
            <div class="wrapper p-6">
                <div class="intro-y flex items-center mt-8">
                    <h2 class="text-2xl font-medium mr-auto flex items-center gap-2">
                        <i data-lucide="dollar-sign" class="w-6 h-6"></i>
                        Earnings
                    </h2>
                </div>

                <div class="grid grid-cols-12 gap-6 mt-5">
                    <div class="intro-y col-span-12 lg:col-span-12">
                        <div class="intro-y box">
                            <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                                <h2 class="font-medium text-2xl mr-auto flex items-center gap-2">
                                <i data-lucide="trending-up" class="w-6 h-6"></i>
                                Total Vendor Earnings
                            </h2>
                            </div>
                            <div class="p-5">
                                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                                    <div class="p-4 bg-white rounded shadow-sm">
                                        <div class="text-sm text-gray-500">Vendor Net Earnings</div>
                                        <div class="text-3xl font-bold mt-2">₹<?php echo number_format(floatval($vendorNetRevenue), 2); ?></div>
                                        <div class="text-xs text-slate-500 mt-2">(Vendor share after fixed rider fee)</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="intro-y box mt-5">
                            <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                                <h2 class="font-medium text-2xl mr-auto flex items-center gap-2">
                                <i data-lucide="clock" class="w-6 h-6"></i>
                                Recent Orders
                            </h2>
                            </div>
                            
                            <!-- Filter Section -->
                            <div class="p-5 border-b border-slate-200/60 bg-slate-50">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="form-label font-semibold text-sm">Filter By:</label>
                                        <select name="filter_type" id="filter_type" class="form-control" onchange="updateFilter()">
                                            <option value="today" <?php echo $filter_type === 'today' ? 'selected' : ''; ?>>Today's Orders</option>
                                            <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Orders</option>
                                            <option value="custom" <?php echo $filter_type === 'custom' ? 'selected' : ''; ?>>Custom Date Range</option>
                                        </select>
                                    </div>
                                    
                                    <div id="from_date_div" style="display: <?php echo $filter_type === 'custom' ? 'block' : 'none'; ?>">
                                        <label class="form-label font-semibold text-sm">From Date:</label>
                                        <input type="date" name="from_date" id="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date); ?>">
                                    </div>
                                    
                                    <div id="to_date_div" style="display: <?php echo $filter_type === 'custom' ? 'block' : 'none'; ?>">
                                        <label class="form-label font-semibold text-sm">To Date:</label>
                                        <input type="date" name="to_date" id="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date); ?>">
                                    </div>
                                    
                                    <div class="flex items-end gap-2">
                                        <button type="button" class="btn btn-primary w-full" onclick="applyFilter()" id="apply_btn" style="display: <?php echo $filter_type === 'custom' ? 'block' : 'none'; ?>">Apply Filter</button>
                                        <button type="button" class="btn btn-secondary w-full" onclick="resetFilter()">Reset</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-5">
                                <div class="overflow-x-auto">
                                    <?php if (count($orders) === 0): ?>
                                        <div class="no-data">No orders found for the selected filter.</div>
                                    <?php else: ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Vendor Earning</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                                <td>₹<?php echo number_format((float)$order['amount_before_delivery'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                                                <td>₹<?php echo number_format((float)$order['vendor_earning'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($order['order_date']); ?></td>
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
    <script src="dist/js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function updateFilter() {
            const filterType = document.getElementById('filter_type').value;
            const fromDateDiv = document.getElementById('from_date_div');
            const toDateDiv = document.getElementById('to_date_div');
            const applyBtn = document.getElementById('apply_btn');
            
            if (filterType === 'custom') {
                fromDateDiv.style.display = 'block';
                toDateDiv.style.display = 'block';
                applyBtn.style.display = 'block';
            } else {
                fromDateDiv.style.display = 'none';
                toDateDiv.style.display = 'none';
                applyBtn.style.display = 'none';
                
                // Apply filter immediately for non-custom options
                applyFilterImmediately(filterType);
            }
        }
        
        function applyFilterImmediately(filterType) {
            const url = new URL(window.location.href);
            url.searchParams.set('filter_type', filterType);
            url.searchParams.delete('from_date');
            url.searchParams.delete('to_date');
            window.location.href = url.toString();
        }
        
        function applyFilter() {
            const filterType = document.getElementById('filter_type').value;
            const fromDate = document.getElementById('from_date').value;
            const toDate = document.getElementById('to_date').value;
            
            if (filterType === 'custom' && (!fromDate || !toDate)) {
                alert('Please select both From Date and To Date');
                return;
            }
            
            const url = new URL(window.location.href);
            url.searchParams.set('filter_type', filterType);
            if (fromDate) url.searchParams.set('from_date', fromDate);
            if (toDate) url.searchParams.set('to_date', toDate);
            window.location.href = url.toString();
        }
        
        function resetFilter() {
            const url = new URL(window.location.href);
            url.searchParams.delete('filter_type');
            url.searchParams.delete('from_date');
            url.searchParams.delete('to_date');
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
