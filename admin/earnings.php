<?php
session_start();
include 'connection.php';

// Ensure user is admin
if (empty($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get filter parameters
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'today';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Check if earnings table exists, otherwise use dynamic calculation
$tableExists = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_admin_earnings'");
$useEarningsTable = $tableExists && mysqli_num_rows($tableExists) > 0;

if ($useEarningsTable) {
    // Use earnings table
    $summary = [
        'total_orders' => 0,
        'commission' => 0.0
    ];
    $summaryStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total_orders, IFNULL(SUM((o.subtotal + o.tax) * 0.15),0) AS commission FROM tbl_admin_earnings ae JOIN tbl_orders o ON ae.order_id = o.order_id");
    if ($summaryStmt) {
        mysqli_stmt_execute($summaryStmt);
        $res = mysqli_stmt_get_result($summaryStmt);
        if ($res) {
            $summary = mysqli_fetch_assoc($res);
        }
        mysqli_stmt_close($summaryStmt);
    }

    // Build query with filters
    $query = "SELECT ae.earning_id, o.order_id, o.order_number, u.user_name AS customer, (o.subtotal + o.tax) AS total_amount, 50 AS delivery_charge, ((o.subtotal + o.tax) * 0.15) AS commission, o.order_date FROM tbl_admin_earnings ae JOIN tbl_orders o ON ae.order_id = o.order_id JOIN tbl_users u ON u.user_id = o.user_id WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Apply filters
    if ($filter_type === 'today') {
        $query .= " AND DATE(o.order_date) = CURDATE()";
    } elseif ($filter_type === 'custom' && $from_date && $to_date) {
        $query .= " AND DATE(o.order_date) BETWEEN ? AND ?";
        $params = [$from_date, $to_date];
        $types = 'ss';
    }
    
    $query .= " ORDER BY o.order_date DESC LIMIT 100";
    
    // Fetch filtered orders
    $orders = [];
    if ($params) {
        $ordersStmt = mysqli_prepare($conn, $query);
        if ($ordersStmt) {
            mysqli_stmt_bind_param($ordersStmt, $types, ...$params);
            mysqli_stmt_execute($ordersStmt);
            $res = mysqli_stmt_get_result($ordersStmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $orders[] = $row;
            }
            mysqli_stmt_close($ordersStmt);
        }
    } else {
        $ordersStmt = mysqli_prepare($conn, $query);
        if ($ordersStmt) {
            mysqli_stmt_execute($ordersStmt);
            $res = mysqli_stmt_get_result($ordersStmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $orders[] = $row;
            }
            mysqli_stmt_close($ordersStmt);
        }
    }
} else {
    // Fallback to dynamic calculation
    $summary = [
        'total_orders' => 0,
        'commission' => 0.0
    ];
    // Admin earns 15% of the order amount (subtotal + GST) for each completed order
    $summaryStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total_orders, IFNULL(SUM(subtotal + tax),0) AS total_sales FROM tbl_orders WHERE order_status = 'Completed' AND payment_status = 'Paid'");
    if ($summaryStmt) {
        mysqli_stmt_execute($summaryStmt);
        $res = mysqli_stmt_get_result($summaryStmt);
        if ($res) {
            $summary = mysqli_fetch_assoc($res);
            $summary['commission'] = floatval($summary['total_sales']) * 0.15;
        }
        mysqli_stmt_close($summaryStmt);
    }

    // Build query with filters
    $query = "SELECT o.order_id, o.order_number, u.user_name AS customer, (o.subtotal + o.tax) AS total_amount, 50 AS delivery_charge, ((o.subtotal + o.tax) * 0.15) AS commission, o.order_date FROM tbl_orders o JOIN tbl_users u ON u.user_id = o.user_id WHERE o.order_status = 'Completed' AND o.payment_status = 'Paid'";
    
    $params = [];
    $types = '';
    
    // Apply filters
    if ($filter_type === 'today') {
        $query .= " AND DATE(o.order_date) = CURDATE()";
    } elseif ($filter_type === 'custom' && $from_date && $to_date) {
        $query .= " AND DATE(o.order_date) BETWEEN ? AND ?";
        $params = [$from_date, $to_date];
        $types = 'ss';
    }
    
    $query .= " ORDER BY o.order_date DESC LIMIT 100";
    
    // Fetch filtered orders
    $orders = [];
    if ($params) {
        $ordersStmt = mysqli_prepare($conn, $query);
        if ($ordersStmt) {
            mysqli_stmt_bind_param($ordersStmt, $types, ...$params);
            mysqli_stmt_execute($ordersStmt);
            $res = mysqli_stmt_get_result($ordersStmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $orders[] = $row;
            }
            mysqli_stmt_close($ordersStmt);
        }
    } else {
        $ordersStmt = mysqli_prepare($conn, $query);
        if ($ordersStmt) {
            mysqli_stmt_execute($ordersStmt);
            $res = mysqli_stmt_get_result($ordersStmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $orders[] = $row;
            }
            mysqli_stmt_close($ordersStmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Earnings - Dessert Magic</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        /* color improvement for earnings page */
        .summary-card {
            border: 2px solid transparent;
            background: linear-gradient(135deg, #eef2ff, #eff6ff);
        }
        .summary-card:nth-child(2) {
            background: linear-gradient(135deg, #ecfdf5, #dcfce7);
        }
        .summary-label {
            color: #2563eb;
            font-weight: 700;
        }
        .summary-value {
            color: #1e3a8a;
        }
        .earnings-table th {
            background-color: #dbeafe;
            color: #1e3a8a;
            border-bottom: 2px solid #93c5fd;
        }
        .earnings-table tbody tr:nth-child(odd) {
            background-color: #f8fafc;
        }
        .earnings-table tbody tr:hover {
            background-color: #e0f2fe;
        }
        .no-data {
            background: #fff7ed;
            color: #92400e;
            border-color: #fbbf24;
        }
    </style>
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <?php include 'sideMenu.php'; ?>
        <div class="content">
            
            <div class="wrapper p-6">
                <div class="intro-y flex items-center mt-8">
                    <h1 class="text-3xl font-extrabold text-blue-800 mr-auto">Earnings💸</h1>
                </div>

                <div class="grid grid-cols-12 gap-6 mt-5">
                    <div class="intro-y col-span-12 lg:col-span-12">
                        <div class="intro-y box">
                            <!-- <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                                <h2 class="font-medium text-base mr-auto">Summary</h2>
                            </div> -->
                            <div class="p-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="p-4 summary-card rounded shadow-sm">
                                        <div class="summary-label">Completed Orders</div>
                                        <div class="summary-value text-3xl font-bold mt-2"><?php echo intval($summary['total_orders']); ?></div>
                                    </div>
                                    <div class="p-4 summary-card rounded shadow-sm">
                                        <div class="summary-label">Admin Earnings (15%)</div>
                                        <div class="summary-value text-3xl font-bold mt-2">₹<?php echo number_format(floatval($summary['commission']), 2); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="intro-y box mt-5">
                            <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                                <h2 class="font-medium text-base mr-auto">Completed Orders History</h2>
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
                                        <div class="no-data">No completed orders found for the selected filter.</div>
                                    <?php else: ?>
                                    <table class="table earnings-table">
                                        <thead>
                                            <tr>
                                                <th>Order</th>
                                                <th>Customer</th>
                                                <th>Order Amount</th>
                                                <th>Admin Share (15%)</th>
                                                <th>Vendor Gets</th>
                                                <th>Delivery Fee</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): 
                                                $vendor_net = (float)$order['total_amount'] - (float)$order['commission'];
                                            ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                                <td><?php echo htmlspecialchars($order['customer']); ?></td>
                                                <td>₹<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                                <td>₹<?php echo number_format((float)$order['commission'], 2); ?></td>
                                                <td class="font-weight-bold" style="color: #28a745;">₹<?php echo number_format($vendor_net, 2); ?></td>
                                                <td>₹<?php echo number_format((float)$order['delivery_charge'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($order['order_date'] ?? ''); ?></td>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    
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
