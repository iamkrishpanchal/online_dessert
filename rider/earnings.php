<?php
session_start();
include 'connection.php';

$rider_id = $_SESSION['rider_id'] ?? 0;
if (!$rider_id) {
    header('Location: login.php');
    exit;
}

// Get filter parameters
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'today';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Check if earnings table exists, otherwise use dynamic calculation
$tableExists = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_rider_earnings'");
$useEarningsTable = $tableExists && mysqli_num_rows($tableExists) > 0;

if ($useEarningsTable) {
    // Use earnings table
    $summary = ['total_orders' => 0, 'total_amount' => 0.0, 'total_delivery_charges' => 0.0];
    $summaryStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total_orders, IFNULL(SUM(o.total_amount),0) AS total_amount, IFNULL(SUM(re.delivery_charge),0) AS total_delivery_charges FROM tbl_rider_earnings re JOIN tbl_orders o ON re.order_id = o.order_id WHERE re.rider_id = ?");
    if ($summaryStmt) {
        mysqli_stmt_bind_param($summaryStmt, 'i', $rider_id);
        mysqli_stmt_execute($summaryStmt);
        $res = mysqli_stmt_get_result($summaryStmt);
        if ($res) {
            $summary = mysqli_fetch_assoc($res);
        }
        mysqli_stmt_close($summaryStmt);
    }
    
    // Fetch detailed earnings with filters
    $orders = [];
    $query = "SELECT re.earning_id, o.order_id, o.order_number, u.user_name AS customer, o.total_amount, re.delivery_charge, o.order_date FROM tbl_rider_earnings re JOIN tbl_orders o ON re.order_id = o.order_id JOIN tbl_users u ON u.user_id = o.user_id WHERE re.rider_id = ?";
    
    // Apply filters
    if ($filter_type === 'today') {
        $query .= " AND DATE(o.order_date) = CURDATE()";
    } elseif ($filter_type === 'custom' && $from_date && $to_date) {
        $query .= " AND DATE(o.order_date) BETWEEN '{$from_date}' AND '{$to_date}'";
    }
    
    $query .= " ORDER BY o.order_date DESC";
    
    $ordersStmt = mysqli_prepare($conn, $query);
    if ($ordersStmt) {
        mysqli_stmt_bind_param($ordersStmt, 'i', $rider_id);
        mysqli_stmt_execute($ordersStmt);
        $res = mysqli_stmt_get_result($ordersStmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $orders[] = $row;
        }
        mysqli_stmt_close($ordersStmt);
    }
} else {
    // Fallback to dynamic calculation
    $summary = ['total_orders' => 0, 'total_amount' => 0.0, 'total_delivery_charges' => 0.0];
    $summaryStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total_orders, IFNULL(SUM(total_amount),0) AS total_amount, COUNT(*) * 50 AS total_delivery_charges FROM tbl_orders WHERE rider_id = ? AND delivery_status = 'delivered'");
    if ($summaryStmt) {
        mysqli_stmt_bind_param($summaryStmt, 'i', $rider_id);
        mysqli_stmt_execute($summaryStmt);
        $res = mysqli_stmt_get_result($summaryStmt);
        if ($res) {
            $summary = mysqli_fetch_assoc($res);
        }
        mysqli_stmt_close($summaryStmt);
    }

    // Fetch detailed delivered orders with filters
    $orders = [];
    $query = "SELECT o.order_id, o.order_number, u.user_name AS customer, o.total_amount, 50 AS delivery_charge, o.order_date FROM tbl_orders o JOIN tbl_users u ON u.user_id = o.user_id WHERE o.rider_id = ? AND o.delivery_status = 'delivered'";
    
    // Apply filters
    if ($filter_type === 'today') {
        $query .= " AND DATE(o.order_date) = CURDATE()";
    } elseif ($filter_type === 'custom' && $from_date && $to_date) {
        $query .= " AND DATE(o.order_date) BETWEEN '{$from_date}' AND '{$to_date}'";
    }
    
    $query .= " ORDER BY o.order_date DESC";
    
    $ordersStmt = mysqli_prepare($conn, $query);
    if ($ordersStmt) {
        mysqli_stmt_bind_param($ordersStmt, 'i', $rider_id);
        mysqli_stmt_execute($ordersStmt);
        $res = mysqli_stmt_get_result($ordersStmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $orders[] = $row;
        }
        mysqli_stmt_close($ordersStmt);
    }
}

$riderDeliveryCharges = floatval($summary['total_delivery_charges'] ?? 0);

// Calculate today's earnings
$todayEarnings = 0.0;
if ($useEarningsTable) {
    $todayStmt = mysqli_prepare($conn, "SELECT IFNULL(SUM(re.delivery_charge),0) AS today_earnings FROM tbl_rider_earnings re JOIN tbl_orders o ON re.order_id = o.order_id WHERE re.rider_id = ? AND DATE(o.order_date) = CURDATE()");
    if ($todayStmt) {
        mysqli_stmt_bind_param($todayStmt, 'i', $rider_id);
        mysqli_stmt_execute($todayStmt);
        $res = mysqli_stmt_get_result($todayStmt);
        if ($res) {
            $row = mysqli_fetch_assoc($res);
            $todayEarnings = floatval($row['today_earnings'] ?? 0);
        }
        mysqli_stmt_close($todayStmt);
    }
} else {
    $todayStmt = mysqli_prepare($conn, "SELECT COUNT(*) * 50 AS today_earnings FROM tbl_orders WHERE rider_id = ? AND delivery_status = 'delivered' AND DATE(order_date) = CURDATE()");
    if ($todayStmt) {
        mysqli_stmt_bind_param($todayStmt, 'i', $rider_id);
        mysqli_stmt_execute($todayStmt);
        $res = mysqli_stmt_get_result($todayStmt);
        if ($res) {
            $row = mysqli_fetch_assoc($res);
            $todayEarnings = floatval($row['today_earnings'] ?? 0);
        }
        mysqli_stmt_close($todayStmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <title>Earnings</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        body { background: linear-gradient(180deg, #f3fbff 0%, #f7fcff 100%); }
        .content { background: transparent; }
        .intro-y h2 { color: #0a4e83; font-size: 1.95rem; font-weight: 800; }
        .box { border: 1px solid rgba(37, 115, 148, 0.14); border-radius: 1rem; box-shadow: 0 18px 30px rgba(20, 70, 120, 0.08); }
        .box .flex.flex-col.sm\:flex-row.items-center { background: linear-gradient(90deg, #47a8e0 0%, #2b72b4 100%); border-radius: 1rem 1rem 0 0; color: white; }
        .box .flex.flex-col.sm\:flex-row.items-center h2 { color: white; }
        .intro-y .btn-primary { background: #1f8b64; border-color: #1f8b64; }
        .intro-y .btn-primary:hover { background: #176c4f; border-color: #176c4f; }
        .table thead th { background: #e5f4ff; color: #16597c; border-bottom: 2px solid #99d2ff; }
        .table tbody tr { background: #fff; transition: background .2s ease; }
        .table tbody tr:hover { background: #ebf7ff; }
        .table { border-radius: 0.8rem; overflow: hidden; }
        .no-data { padding: 1.6rem; border: 1px dashed #7fc4f4; background: #f1faff; border-radius: 0.8rem; color: #1a547a; text-align: center; }
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
                <h2 class="text-lg font-medium mr-auto">Earnings</h2>
            </div>
            <div class="grid grid-cols-12 gap-6 mt-5">
                <div class="intro-y col-span-12 lg:col-span-12">
                    <div class="intro-y box">
                        <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                            <h2 class="font-medium text-base mr-auto">Summary</h2>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="p-4 bg-white rounded shadow-sm">
                                    <div class="text-sm text-gray-500">Delivered Orders</div>
                                    <div class="text-3xl font-bold mt-2"><?php echo intval($summary['total_orders']); ?></div>
                                </div>
                                <div class="p-4 bg-white rounded shadow-sm">
                                    <div class="text-sm text-gray-500">Total Order Amount</div>
                                    <div class="text-3xl font-bold mt-2">₹<?php echo number_format(floatval($summary['total_amount']), 2); ?></div>
                                </div>
                                <div class="p-4 bg-white rounded shadow-sm">
                                    <div class="text-sm text-gray-500">Fixed Delivery Charges (₹50/order)</div>
                                    <div class="text-3xl font-bold mt-2">₹<?php echo number_format($riderDeliveryCharges, 2); ?></div>
                                </div>
                                <div class="p-4 bg-white rounded shadow-sm border-l-4 border-green-500">
                                    <div class="text-sm text-gray-500">Today's Earning</div>
                                    <div class="text-3xl font-bold mt-2 text-green-600">₹<?php echo number_format($todayEarnings, 2); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="intro-y box mt-5">
                        <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                            <h2 class="font-medium text-base mr-auto">Delivered Orders History</h2>
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
                                    <div class="no-data">No delivered orders found for the selected filter.</div>
                                <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Order Amount</th>
                                            <th>Fixed Delivery Charge (₹50)</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td><?php echo htmlspecialchars($order['customer']); ?></td>
                                            <td>₹<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                            <td>₹<?php echo number_format((float)$order['delivery_charge'], 2); ?></td>
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
</body>
</html>

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
