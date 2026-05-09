<?php
/**
 * ADMIN DASHBOARD - View All Orders by Status
 * Main admin panel showing Pending, Confirmed, Dispatched, Completed, and Cancelled orders
 */
session_start();
include 'connection.php';

// Check if user is admin or vendor
if (empty($_SESSION['admin_id']) && empty($_SESSION['vendor_id'])) {
    header('Location: login.php');
    exit;
}

$vendor_id = $_SESSION['vendor_id'] ?? null;
$is_vendor = !empty($vendor_id);
$is_admin = !empty($_SESSION['admin_id']);

// If vendor page is used, enforce vendor-only view even when admin session exists
if ($is_vendor) {
    $is_admin = false;
}

// Get statistics for each order status
$stats = [
    'Pending' => 0,
    'Confirmed' => 0,
    'Dispatched' => 0,
    'Completed' => 0,
    'Cancelled' => 0
];

if ($is_vendor) {
    // Vendor sees only their orders from today
    $sql_stats = "SELECT order_status, COUNT(*) as count FROM tbl_orders WHERE vendor_id=? AND DATE(created_at) = CURDATE() GROUP BY order_status";
} elseif ($is_admin) {
    // Main admin sees all orders
    $sql_stats = "SELECT order_status, COUNT(*) as count FROM tbl_orders GROUP BY order_status";
} else {
    // no one valid
    $sql_stats = "SELECT order_status, COUNT(*) as count FROM tbl_orders WHERE 0";
}

$stmt = mysqli_prepare($conn, $sql_stats);
if ($stmt && !$is_admin) {
    mysqli_stmt_bind_param($stmt, 'i', $vendor_id);
}
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        if (isset($stats[$row['order_status']])) {
            $stats[$row['order_status']] = $row['count'];
        }
    }
    mysqli_stmt_close($stmt);
}

$current_filter = $_GET['filter'] ?? 'all';
$payment_filter = $_GET['payment'] ?? '';
$category_id = intval($_GET['category'] ?? 0);
$product_id = intval($_GET['product'] ?? 0);

// load category/product lists for filter dropdowns (admin only)
$categories = [];
$prod_list = [];
if ($is_admin) {
    $cres = mysqli_query($conn, "SELECT categories_id,categories_name FROM tbl_categories ORDER BY categories_name");
    while ($cr = mysqli_fetch_assoc($cres)) {
        $categories[$cr['categories_id']] = $cr['categories_name'];
    }
    // Some installations use tbl_products instead of tbl_product
    $productTable = 'tbl_product';
    $tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product'");
    if (!$tblCheck || mysqli_num_rows($tblCheck) === 0) {
        $productTable = 'tbl_products';
    }
    $pres = mysqli_query($conn, "SELECT product_id, product_name FROM {$productTable} ORDER BY product_name");
    if ($pres) {
        while ($pr = mysqli_fetch_assoc($pres)) {
            $prod_list[$pr['product_id']] = $pr['product_name'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        body { background-color: #f8f9fa; margin-top: 0; }
        .flex { margin-top: 0 !important; }
        .content { padding-top: 0 !important; margin-top: 0 !important; }
        .p-6 { padding-top: 0 !important; margin-top: 0 !important; }
        .wrapper { margin-top: 0 !important; padding-top: 0 !important; }
        .top-bar { margin-top: 0 !important; }
        .stat-card {
            border-left: 4px solid #007bff;
            border-radius: 4px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .stat-card.pending { border-left-color: #ffc107; }
        .stat-card.confirmed { border-left-color: #17a2b8; }
        .stat-card.dispatched { border-left-color: #fd7e14; }
        .stat-card.completed { border-left-color: #28a745; }
        .stat-card.cancelled { border-left-color: #dc3545; }
        
        .status-badge {
            display: inline-block;
            padding: 0.5em 1em;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
        }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-confirmed { background: #cfe2ff; color: #084298; }
        .badge-dispatched { background: #fff5e1; color: #664d03; }
        .badge-completed { background: #d1e7dd; color: #0f5132; }
        .badge-cancelled { background: #f8d7da; color: #842029; }
        
        .count-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #007bff;
        }
        .stat-card.pending .count-number { color: #ffc107; }
        .stat-card.confirmed .count-number { color: #17a2b8; }
        .stat-card.dispatched .count-number { color: #fd7e14; }
        .stat-card.completed .count-number { color: #28a745; }
        .stat-card.cancelled .count-number { color: #dc3545; }
        
        .order-table { margin-top: 30px; }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .action-buttons .btn { font-size: 0.85em; padding: 0.25rem 0.75rem; }
        
        .filter-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-nav .btn { font-weight: 500; }
        .filter-nav .btn.active { box-shadow: 0 0 10px rgba(0,0,0,0.2); }
    </style>
</head>
<body class="py-0 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-0 md:mt-0 overflow-hidden">
        <!-- BEGIN: Side Menu -->
        <?php include 'sideMenu.php'; ?>
        <!-- END: Side Menu -->

        <!-- BEGIN: Content -->
        <div class="content">
            <!-- BEGIN: Top Bar -->
            <?php include 'topBar.php' ?>
            <!-- END: Top Bar -->

            <!-- BEGIN: Main Content -->
            <div class="p-6">
                <div class="container-fluid p-4">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h1 class="mb-4">
                                <i class="bi bi-box"></i> Order Dashboard
                                <?php if (!$is_admin) echo " - Vendor Panel"; ?>
                            </h1>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                      
                        <div class="col-md-4 col-lg-2 mb-3">
                            <div class="card stat-card confirmed text-center p-3">
                                <div class="count-number"><?php echo $stats['Confirmed']; ?></div>
                                <div class="small">Confirmed</div>
                                <a href="?filter=Confirmed" class="btn btn-sm btn-outline-info mt-2">View</a>
                            </div>
                        </div>
                      
                        <div class="col-md-4 col-lg-2 mb-3">
                            <div class="card stat-card completed text-center p-3">
                                <div class="count-number"><?php echo $stats['Completed']; ?></div>
                                <div class="small">Completed</div>
                                <a href="?filter=Completed" class="btn btn-sm btn-outline-success mt-2">View</a>
                            </div>
                        </div>
                        <div class="col-md-4 col-lg-2 mb-3">
                            <div class="card stat-card cancelled text-center p-3">
                                <div class="count-number"><?php echo $stats['Cancelled']; ?></div>
                                <div class="small">Cancelled</div>
                                <a href="?filter=Cancelled" class="btn btn-sm btn-outline-danger mt-2">View</a>
                            </div>
                        </div>
                        <div class="col-md-4 col-lg-2 mb-3">
                            <div class="card stat-card text-center p-3" style="border-left-color: #007bff;">
                                <div class="count-number"><?php echo array_sum($stats); ?></div>
                                <div class="small">Total</div>
                                <a href="?" class="btn btn-sm btn-outline-primary mt-2">View All</a>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Buttons -->
                    <div class="filter-nav">
                        <a href="?" class="btn <?php echo $current_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All Orders</a>
                    
                        <a href="?filter=Confirmed" class="btn <?php echo $current_filter === 'Confirmed' ? 'btn-info' : 'btn-outline-info'; ?>">Confirmed</a>
                        <a href="?filter=Completed" class="btn <?php echo $current_filter === 'Completed' ? 'btn-success' : 'btn-outline-success'; ?>">Completed</a>
                        <a href="?filter=Cancelled" class="btn <?php echo $current_filter === 'Cancelled' ? 'btn-danger' : 'btn-outline-danger'; ?>">Cancelled</a>
                    </div>
                    <?php if ($is_admin): ?>
                    <form method="get" class="mb-3">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($current_filter); ?>">
                        <input type="hidden" name="payment" value="<?php echo htmlspecialchars($payment_filter); ?>">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <select name="category" class="form-select" onchange="this.form.submit()">
                                    <option value="">All categories</option>
                                    <?php foreach ($categories as $cid => $cname): ?>
                                        <option value="<?php echo $cid; ?>" <?php if ($category_id === $cid) echo 'selected'; ?>><?php echo htmlspecialchars($cname); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name="product" class="form-select" onchange="this.form.submit()">
                                    <option value="">All products</option>
                                    <?php foreach ($prod_list as $pid => $pname): ?>
                                        <option value="<?php echo $pid; ?>" <?php if ($product_id === $pid) echo 'selected'; ?>><?php echo htmlspecialchars($pname); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>

                    <!-- Orders Table -->
                    <div class="order-table">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <?php
                                    if ($current_filter === 'all') {
                                        echo "All Orders";
                                    } else {
                                        echo ucfirst($current_filter) . " Orders";
                                    }
                                    ?>
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Order #</th>
                                                <th>Customer</th>
                                                <th>Phone</th>
                                                <th>Items</th>
                                                <th>Total Amount</th>
                                                <th>Status</th>
                                                <th>Delivery</th>
                                                <th>Rider</th>
                                                <th>Payment</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
if ($is_vendor) {
    $sql = "SELECT o.order_id, o.order_number, u.user_name, u.phone,
                   o.total_amount, o.order_status, o.payment_status, o.delivery_status, o.rider_id, r.name AS rider_name, o.created_at
            FROM tbl_orders o
            JOIN tbl_users u ON o.user_id = u.user_id
            LEFT JOIN tbl_riders r ON o.rider_id = r.rider_id
            WHERE o.vendor_id = ? AND DATE(o.created_at) = CURDATE()";
    $params = [$vendor_id];
    $types = 'i';
} elseif ($is_admin) {
    $sql = "SELECT o.order_id, o.order_number, u.user_name, u.phone,
                   o.total_amount, o.order_status, o.payment_status, o.delivery_status, o.rider_id, r.name AS rider_name, o.created_at
            FROM tbl_orders o
            JOIN tbl_users u ON o.user_id = u.user_id
            LEFT JOIN tbl_riders r ON o.rider_id = r.rider_id";
    $params = [];
    $types = '';
    // apply category/product filters via order_items join if needed
    if ($category_id || $product_id) {
        $sql .= " JOIN tbl_order_items oi ON oi.order_id=o.order_id ";
        $sql .= " JOIN tbl_product p ON p.product_id=oi.product_id ";
    }
} else {
    $sql = "SELECT o.order_id, o.order_number, u.user_name, u.phone,
                   o.total_amount, o.order_status, o.payment_status, o.delivery_status, o.rider_id, r.name AS rider_name, o.created_at
            FROM tbl_orders o
            JOIN tbl_users u ON o.user_id = u.user_id
            LEFT JOIN tbl_riders r ON o.rider_id = r.rider_id
            WHERE 0";
    $params = [];
    $types = '';
                                            }

                                            if ($current_filter !== 'all') {
                                                $sql .= " AND o.order_status = ?";
                                                $params[] = $current_filter;
                                                $types .= 's';
                                            }
                                            if ($category_id) {
                                                $sql .= " AND p.category_id = ?";
                                                $params[] = $category_id;
                                                $types .= 'i';
                                            }
                                            if ($product_id) {
                                                $sql .= " AND p.product_id = ?";
                                                $params[] = $product_id;
                                                $types .= 'i';
                                            }

                                            $sql .= " ORDER BY o.created_at DESC LIMIT 100";

                                            $stmt = mysqli_prepare($conn, $sql);
                                            if ($stmt && !empty($params)) {
                                                mysqli_stmt_bind_param($stmt, $types, ...$params);
                                            }
                                            if ($stmt) {
                                                mysqli_stmt_execute($stmt);
                                                $result = mysqli_stmt_get_result($stmt);
                                                
                                                if (mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $status_class = 'badge-' . strtolower($row['order_status']);
                                                        $payment_class = $row['payment_status'] === 'Paid' ? 'bg-success' : 'bg-warning';
                                                        ?>
                                                        <tr>
                                                            <td><a href="order_details.php?order_id=<?php echo $row['order_id']; ?>"><strong><?php echo htmlspecialchars($row['order_number']); ?></strong></a></td>
                                                            <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($row['phone'] ?? '-'); ?></td>
                                                            <?php
                                                            // fetch item names for display
                                                            $items_list = [];
                                                            $items_res2 = mysqli_query($conn, "SELECT product_name, quantity FROM tbl_order_items WHERE order_id=" . intval($row['order_id']));
                                                            while($ir = mysqli_fetch_assoc($items_res2)) {
                                                                $items_list[] = htmlspecialchars($ir['product_name']) . ' (' . intval($ir['quantity']) . ')';
                                                            }
                                                            ?>
                                                            <td><?php echo implode(', ', $items_list); ?></td>
                                                            <td><strong>₹<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                                                            <td>
                                                                <span class="status-badge <?php echo $status_class; ?>">
                                                                    <?php echo ucfirst($row['order_status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($row['delivery_status'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($row['rider_name'] ?? ''); ?></td>
                                                            <td>
                                                                <span class="badge <?php echo $payment_class; ?>">
                                                                    <?php echo ucfirst($row['payment_status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('d-m-Y H:i', strtotime($row['created_at'])); ?></td>
                                                        </tr>
                                                        <?php
                                                    }
                                                } else {
                                                    ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center py-4 text-muted">
                                                            No orders found for this filter.
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                mysqli_stmt_close($stmt);
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
            <!-- END: Main Content -->
        </div>
        <!-- END: Content -->
    </div>

    <script src="dist/js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
