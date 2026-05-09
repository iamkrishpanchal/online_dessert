<?php
// Admin Dashboard - Restored classic experience
session_start();
include 'connection.php';

// Ensure user is logged in (admin or vendor)
if (empty($_SESSION['admin_id']) && empty($_SESSION['vendor_id'])) {
    header('Location: ../login.php');
    exit;
}

function safeCount($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if (!$res) return 0;
    $row = mysqli_fetch_row($res);
    return $row ? intval($row[0]) : 0;
}

$totalCustomers = safeCount($conn, "SELECT COUNT(*) FROM tbl_users");
$totalOrders = safeCount($conn, "SELECT COUNT(*) FROM tbl_orders");

// Support both product table names
$productTable = 'tbl_product';
$check = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product'");
if (!$check || mysqli_num_rows($check) === 0) {
    $productTable = 'tbl_products';
}
$totalProducts = safeCount($conn, "SELECT COUNT(*) FROM {$productTable}");

// Vendors may or may not exist
$totalVendors = 0;
$vendorCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_vendors'");
if ($vendorCheck && mysqli_num_rows($vendorCheck) > 0) {
    $totalVendors = safeCount($conn, "SELECT COUNT(*) FROM tbl_vendors");
}

// Additional user activity summary stats
$activeCustomersToday = 0;
$newRegistrationsToday = 0;
$loggedInVendors = 0;
$today = date('Y-m-d');

if (mysqli_query($conn, "SHOW TABLES LIKE 'tbl_orders'")) {
    $activeCustomersToday = safeCount($conn, "SELECT COUNT(DISTINCT user_id) FROM tbl_orders WHERE DATE(order_date) = '$today'");
}

if (mysqli_query($conn, "SHOW TABLES LIKE 'tbl_users'")) {
    $newRegistrationsToday = safeCount($conn, "SELECT COUNT(*) FROM tbl_users WHERE DATE(created_at) = '$today'");
}

if (mysqli_query($conn, "SHOW TABLES LIKE 'tbl_vendors'")) {
    $loggedInVendors = safeCount($conn, "SELECT COUNT(*) FROM tbl_vendors WHERE is_online = 1");
}

// Calculate total admin revenue (15% commission on all completed and paid orders)
$totalAdminRevenue = 0;
$revenueRes = mysqli_query($conn, "SELECT IFNULL(SUM(IFNULL(subtotal,0) + IFNULL(tax,0)),0) AS total FROM tbl_orders WHERE order_status = 'Completed' AND payment_status = 'Paid'");
if ($revenueRes) {
    $revenueRow = mysqli_fetch_assoc($revenueRes);
    $totalAdminRevenue = floatval($revenueRow['total']) * 0.15; // 15% admin commission
}

$role = 'Admin';
if (!empty($_SESSION['vendor_id'])) {
    $role = 'Vendor';
}

$greeting = 'Welcome back, Admin!';
if ($role === 'Vendor') {
    $greeting = 'Welcome back, Vendor!';
}

?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Dessert Magic</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <!-- match dark‑blue branding used by vendor dashboard -->
    <style>
        :root {
            --color-primary: 10,37,64; /* rgb for #0a2540 */
            --color-success: 10,37,64;
            --primary-blue: #0a2540;
            --accent-purple: #6366f1;
            --accent-green: #10b981;
            --accent-orange: #f97316;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
        }
        
        .bg-emerald-900, .bg-emerald-600, .bg-theme-1, .text-emerald-900,
        .text-emerald-600, .text-theme-1 {
            background-color: #0a2540 !important;
            color: #0a2540 !important;
        }
        .mobile-menu-bar, .side-menu {
            background-color: #0a2540 !important;
        }
        .side-menu .menu__sub-open,
        .side-menu .menu__sub-open li,
        .side-menu .menu__sub-open li > a,
        .side-menu .menu__sub-open li > .menu {
            background-color: #0a2540 !important;
            color: #fff !important;
        }

        /* Enhanced wrapper and page styling */
        .wrapper {
            background: linear-gradient(135deg, #f0f4f8 0%, #e8eef5 100%);
            padding: 2rem;
        }

        h2 {
            color: #0a2540;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        h3 {
            color: #1e293b;
            font-weight: 600;
            margin-top: 2rem !important;
            margin-bottom: 1rem !important;
        }

        p.text-slate-500 {
            color: #64748b !important;
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Stats Grid */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stats-card { 
            padding: 1.75rem; 
            border-radius: 1.25rem; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            color: #fff; 
            border: none;
            min-height: 140px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: -40px;
            width: 100px;
            height: 100px;
            opacity: 0.1;
            border-radius: 50%;
        }

        .stats-card h3 { 
            margin: 0; 
            font-size: 1rem; 
            font-weight: 500; 
            letter-spacing: 0.02em;
            opacity: 0.95;
        }

        .stats-card .value { 
            font-weight: 800; 
            font-size: 2.5rem; 
            margin-top: 0.75rem;
            line-height: 1;
        }

        .stats-card.total { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .stats-card.vendors { 
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #fff;
        }

        .stats-card.products { 
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: #fff;
        }

        .stats-card.orders { 
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: #fff;
        }

        /* Panel styling */
        .bg-white.rounded-xl {
            background: #ffffff;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            border: 1px solid #f0f4f8;
            transition: all 0.3s ease;
        }

        .bg-white.rounded-xl:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        /* Quick links */
        .quick-links { 
            margin-top: 1.5rem; 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 1rem;
        }

        .quick-link { 
            display: block; 
            padding: 1.2rem 1.25rem; 
            border-radius: 1rem; 
            border: 2px solid transparent;
            text-decoration: none; 
            color: #1f2937; 
            background: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .quick-link:nth-child(1){ 
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            border-color: #c4b5fd;
            color: #6d28d9;
        }
        
        .quick-link:nth-child(2){ 
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border-color: #86efac;
            color: #166534;
        }
        
        .quick-link:nth-child(3){ 
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-color: #93c5fd;
            color: #1d4ed8;
        }
        
        .quick-link:nth-child(4){ 
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-color: #fca5a5;
            color: #991b1b;
        }
        
        .quick-link:nth-child(5){ 
            background: linear-gradient(135deg, #fef9c3 0%, #fef3c7 100%);
            border-color: #fde68a;
            color: #92400e;
        }
        
        .quick-link:nth-child(6){ 
            background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
            border-color: #e9d5ff;
            color: #5b21b6;
        }

        .quick-link:hover { 
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            border-color: currentColor;
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead tr {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        table thead th {
            padding: 1rem;
            font-weight: 600;
            color: #475569;
            text-align: left;
            font-size: 0.9rem;
        }

        table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s ease;
        }

        table tbody tr:hover {
            background-color: #f8fafc;
        }

        table td {
            padding: 1rem;
            color: #334155;
            font-size: 0.95rem;
        }

        /* Alert boxes*/
        .rounded-xl.border-l-4 {
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .rounded-xl.border-l-4:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        /* Text styling */
        .text-yellow-900, .text-red-900, .text-orange-900 {
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .font-bold {
            font-weight: 700;
        }

        /* Spacing improvements */
        .mb-6 {
            margin-bottom: 2rem;
        }

        /* Link styling */
        a {
            transition: color 0.2s ease;
        }

        a:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body class="py-0 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-0 overflow-hidden">
        <?php include 'sideMenu.php'; ?>
        
        <div class="content">
            <!-- BEGIN: Top Bar -->
            <?php include 'topBar.php'; ?>
            <!-- END: Top Bar -->
            <div class="p-6">
                <div class="container-fluid p-4">
                <h2 class="text-2xl font-semibold mb-2">Welcome back, Admin</h2>
                <p class="text-slate-500 mb-6">Quick overview of your store activity.</p>
                

                <div class="stats-grid">
                    
                    
                    <div class="stats-card" style="background: linear-gradient(140deg, #fcd34d, #f59e0b); color:#78350f;">
                        <h3>Total Earnings</h3>
                        <div class="value">₹<?php echo number_format($totalAdminRevenue, 2); ?></div>
                    </div>

                    <?php
                    // prepare order counts for current week (Mon-Sun)
                    $orderCounts = array_fill(0,7,0);
                    $monday = date('Y-m-d', strtotime('monday this week'));
                    $sunday = date('Y-m-d', strtotime('sunday this week'));
                    $sql = "SELECT DATE(order_date) AS od, COUNT(*) AS cnt
                            FROM tbl_orders
                            WHERE order_date BETWEEN '$monday' AND '$sunday'
                            GROUP BY od";
                    if ($res = mysqli_query($conn, $sql)) {
                        while ($r = mysqli_fetch_assoc($res)) {
                            $d = strtotime($r['od']);
                            // day index 1 (Mon) through 7 (Sun)
                            $idx = intval(date('N', $d)) - 1;
                            $orderCounts[$idx] = intval($r['cnt']);
                        }
                    }
                    $orderDataJson = json_encode($orderCounts);

                    // Recent activity timeline (vendors, orders, rider assignments, products)
                    $recentActivity = [];

                    $res = mysqli_query($conn, "SELECT vendor_name, created_at FROM tbl_vendors ORDER BY created_at DESC LIMIT 5");
                    if ($res) {
                        while ($r = mysqli_fetch_assoc($res)) {
                            $recentActivity[] = [
                                'type' => 'New vendor registered',
                                'label' => $r['vendor_name'],
                                'message' => "Vendor {$r['vendor_name']} registered",
                                'created_at' => $r['created_at']
                            ];
                        }
                    }

                    $res = mysqli_query($conn, "SELECT order_number, created_at FROM tbl_orders ORDER BY created_at DESC LIMIT 5");
                    if ($res) {
                        while ($r = mysqli_fetch_assoc($res)) {
                            $recentActivity[] = [
                                'type' => 'New order placed',
                                'label' => $r['order_number'],
                                'message' => "Order {$r['order_number']} placed",
                                'created_at' => $r['created_at']
                            ];
                        }
                    }

                    $res = mysqli_query($conn, "SELECT o.order_number, r.name AS rider_name, o.updated_at AS created_at FROM tbl_orders o LEFT JOIN tbl_riders r ON o.rider_id = r.rider_id WHERE o.rider_id IS NOT NULL ORDER BY o.updated_at DESC LIMIT 5");
                    if ($res) {
                        while ($r = mysqli_fetch_assoc($res)) {
                            $recentActivity[] = [
                                'type' => 'Rider assigned',
                                'label' => $r['order_number'],
                                'message' => "Rider {$r['rider_name']} assigned to order {$r['order_number']}",
                                'created_at' => $r['created_at']
                            ];
                        }
                    }

                    $res = mysqli_query($conn, "SELECT product_name, created_at FROM {$productTable} ORDER BY created_at DESC LIMIT 5");
                    if ($res) {
                        while ($r = mysqli_fetch_assoc($res)) {
                            $recentActivity[] = [
                                'type' => 'Product added',
                                'label' => $r['product_name'],
                                'message' => "Product {$r['product_name']} added",
                                'created_at' => $r['created_at']
                            ];
                        }
                    }

                    usort($recentActivity, function ($a, $b) {
                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                    });
                    $recentActivity = array_slice($recentActivity, 0, 8);

                    // Top vendors by net vendor earnings (after admin commission and discounts)
                    $topVendors = [];
                    $vendorSalesQuery = "SELECT v.vendor_name, v.shop_name, COALESCE(SUM((IFNULL(o.subtotal,0) + IFNULL(o.tax,0) - IFNULL(o.discount,0)) * 0.85),0) AS earnings " .
                                        "FROM tbl_orders o " .
                                        "JOIN tbl_vendors v ON o.vendor_id = v.vendor_id " .
                                        "WHERE o.order_status = 'Completed' AND o.payment_status = 'Paid' " .
                                        "GROUP BY v.vendor_id, v.vendor_name, v.shop_name " .
                                        "ORDER BY earnings DESC LIMIT 5";
                    $salesRes = mysqli_query($conn, $vendorSalesQuery);
                    if ($salesRes) {
                        while ($row = mysqli_fetch_assoc($salesRes)) {
                            $topVendors[] = $row;
                        }
                    }

                    // Top 5 products by order count
                    $topProducts = [];
                    $pnameCol = 'product_name';
                    $priceCol = 'price';
                    $productIdCol = 'product_id';
                    $vendorIdCol = 'vendor_id';
                    
                    // Check for alternative column names
                    $pnameCheck = @mysqli_query($conn, "SHOW COLUMNS FROM {$productTable} LIKE 'pname'");
                    if ($pnameCheck && mysqli_num_rows($pnameCheck) > 0) {
                        $pnameCol = 'pname';
                    }
                    
                    $priceCheck = @mysqli_query($conn, "SHOW COLUMNS FROM {$productTable} LIKE 'product_price'");
                    if ($priceCheck && mysqli_num_rows($priceCheck) > 0) {
                        $priceCol = 'product_price';
                    }
                    
                    $vendorIdCheck = @mysqli_query($conn, "SHOW COLUMNS FROM {$productTable} LIKE 'vendor_id'");
                    if (!$vendorIdCheck || mysqli_num_rows($vendorIdCheck) === 0) {
                        $vendorIdCol = null;
                    }
                    
                    $vendorJoin = '';
                    $vendorSelect = "'N/A' AS shop_name";
                    if ($vendorIdCol && $vendorCheck && mysqli_num_rows($vendorCheck) > 0) {
                        $vendorJoin = "LEFT JOIN tbl_vendors v ON p.{$vendorIdCol} = v.vendor_id";
                        $vendorSelect = "COALESCE(v.shop_name, 'Unknown') AS shop_name";
                    }
                    
                    $topProductsQuery = "SELECT p.{$productIdCol} AS product_id, p.{$pnameCol} AS product_name, " .
                                        "p.{$priceCol} AS price, {$vendorSelect}, " .
                                        "COUNT(oi.order_item_id) AS order_count " .
                                        "FROM {$productTable} p " .
                                        "LEFT JOIN tbl_order_items oi ON p.{$productIdCol} = oi.product_id " .
                                        $vendorJoin . " " .
                                        "GROUP BY p.{$productIdCol}, p.{$pnameCol}, p.{$priceCol}" . ($vendorIdCol ? ", v.shop_name" : "") . " " .
                                        "ORDER BY order_count DESC LIMIT 5";
                    
                    $productsRes = @mysqli_query($conn, $topProductsQuery);
                    if ($productsRes) {
                        while ($row = mysqli_fetch_assoc($productsRes)) {
                            $topProducts[] = $row;
                        }
                    }

                    // Pending vendor requests for admin action
                    $pendingVendors = [];
                    $pendingVendorQuery = '';
                    $pendingVendorStatusColumn = 'pending';

                    $statusColumnRes = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'verification_status'");
                    if ($statusColumnRes && mysqli_num_rows($statusColumnRes) > 0) {
                        $pendingVendorQuery = "SELECT vendor_id, vendor_name, shop_name, verification_status AS status FROM tbl_vendors WHERE verification_status = 'pending' ORDER BY created_at DESC LIMIT 10";
                        $pendingVendorStatusColumn = 'verification_status';
                    } else {
                        $statusColumnRes = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'is_active'");
                        if ($statusColumnRes && mysqli_num_rows($statusColumnRes) > 0) {
                            $pendingVendorQuery = "SELECT vendor_id, vendor_name, shop_name, is_active AS status FROM tbl_vendors WHERE is_active = 0 ORDER BY created_at DESC LIMIT 10";
                            $pendingVendorStatusColumn = 'is_active';
                        }
                    }

                    if ($pendingVendorQuery) {
                        $res = mysqli_query($conn, $pendingVendorQuery);
                        if ($res) {
                            while ($r = mysqli_fetch_assoc($res)) {
                                $pendingVendors[] = $r;
                            }
                        }
                    }

                    // Feedback / Complaints latest entries
                    $feedbackItems = [];
                    $reviewsCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_reviews'");
                    if ($reviewsCheck && mysqli_num_rows($reviewsCheck) > 0) {
                        $feedbackQuery = "SELECT r.review_id, r.order_id, r.rating, r.title, r.review_text, " .
                                        "v.shop_name AS vendor_shop, r.created_at " .
                                        "FROM tbl_reviews r " .
                                        "LEFT JOIN tbl_vendors v ON r.vendor_id = v.vendor_id " .
                                        "ORDER BY r.created_at DESC LIMIT 8";
                        $feedbackRes = mysqli_query($conn, $feedbackQuery);
                        if ($feedbackRes) {
                            while ($row = mysqli_fetch_assoc($feedbackRes)) {
                                $feedbackItems[] = $row;
                            }
                        }
                    }

                    // System Alerts / Issues
                    $lowStockCount = 0;
                    $lowStockItems = [];
                    $failedPaymentsCount = 0;
                    $cancelledOrdersCount = 0;

                    // Low stock products (check if quantity_available column exists first)
                    $quantityColCheck = @mysqli_query($conn, "SHOW COLUMNS FROM {$productTable} LIKE 'quantity_available'");
                    if ($quantityColCheck && mysqli_num_rows($quantityColCheck) > 0) {
                        // Detect actual column names for product name and vendor id
                        $pnameCol = 'product_name';
                        $pnameCheck = @mysqli_query($conn, "SHOW COLUMNS FROM {$productTable} LIKE 'pname'");
                        if ($pnameCheck && mysqli_num_rows($pnameCheck) > 0) {
                            $pnameCol = 'pname';
                        }
                        
                        $vendorIdCol = 'vendor_id';
                        $vendorIdCheck = @mysqli_query($conn, "SHOW COLUMNS FROM {$productTable} LIKE 'vendor_id'");
                        if (!$vendorIdCheck || mysqli_num_rows($vendorIdCheck) === 0) {
                            // If no vendor_id column, try to get from tbl_vendors or skip vendor join
                            $vendorIdCol = null;
                        }

                        $vendorJoin = '';
                        if ($vendorIdCol) {
                            $vendorJoin = "LEFT JOIN tbl_vendors v ON p.{$vendorIdCol} = v.vendor_id";
                        }

                        $lowStockRes = mysqli_query($conn, "SELECT p.product_id, p.{$pnameCol} AS product_name, p.quantity_available, " .
                                                           ($vendorIdCol ? "v.shop_name" : "'N/A' AS shop_name") . " " .
                                                           "FROM {$productTable} p " .
                                                           $vendorJoin . " " .
                                                           "WHERE p.quantity_available < 5 " .
                                                           "ORDER BY p.quantity_available ASC LIMIT 10");
                        if ($lowStockRes) {
                            while ($row = mysqli_fetch_assoc($lowStockRes)) {
                                $lowStockItems[] = $row;
                                $lowStockCount++;
                            }
                        }
                    }

                    // Failed payments
                    $failedPaymentsRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM tbl_orders WHERE payment_status = 'failed'");
                    if ($failedPaymentsRes) {
                        $failedRow = mysqli_fetch_assoc($failedPaymentsRes);
                        $failedPaymentsCount = $failedRow['cnt'] ?? 0;
                    }

                    // Cancelled orders
                    $cancelledRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM tbl_orders WHERE order_status = 'cancelled'");
                    if ($cancelledRes) {
                        $cancelRow = mysqli_fetch_assoc($cancelledRes);
                        $cancelledOrdersCount = $cancelRow['cnt'] ?? 0;
                    }

                    ?>
                    <div class="stats-card total">
                        <h3>Total Customers</h3>
                        <div class="value"><?php echo number_format($totalCustomers); ?></div>
                    </div>
                    <div class="stats-card vendors">
                        <h3>Total Vendors</h3>
                        <div class="value"><?php echo number_format($totalVendors); ?></div>
                    </div>
                    <div class="stats-card products">
                        <h3>Total Products</h3>
                        <div class="value"><?php echo number_format($totalProducts); ?></div>
                    </div>
                    <div class="stats-card orders">
                        <h3>Total Orders</h3>
                        <div class="value"><?php echo number_format($totalOrders); ?></div>
                    </div>
                </div>

                <h3 class="mt-10 mb-4 text-lg font-semibold">Top Vendors by Earnings</h3>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
                    <?php if (empty($topVendors)): ?>
                        <div class="text-slate-500">No vendor earnings data available yet.</div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200">
                                        <th class="py-2 px-3">Rank</th>
                                        <th class="py-2 px-3">Vendor</th>
                                        <th class="py-2 px-3">Shop Name</th>
                                        <th class="py-2 px-3">Earnings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank=1; ?>
                                    <?php foreach ($topVendors as $vendor): ?>
                                        <tr class="border-b border-slate-100">
                                            <td class="py-2 px-3 font-semibold"><?php echo $rank++; ?></td>
                                            <td class="py-2 px-3"><?php echo htmlspecialchars($vendor['vendor_name']); ?></td>
                                            <td class="py-2 px-3"><?php echo htmlspecialchars($vendor['shop_name']); ?></td>
                                            <td class="py-2 px-3 font-semibold">₹<?php echo number_format($vendor['earnings'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <h3 class="mt-10 mb-4 text-lg font-semibold">Top 5 Products List</h3>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
                    <?php if (empty($topProducts)): ?>
                        <div class="text-slate-500">No product sales data available yet.</div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200">
                                        <th class="py-2 px-3">Rank</th>
                                        <th class="py-2 px-3">Product Name</th>
                                        <th class="py-2 px-3">Price</th>
                                        <th class="py-2 px-3">Shop Name</th>
                                        <th class="py-2 px-3">Orders</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank=1; ?>
                                    <?php foreach ($topProducts as $product): ?>
                                        <tr class="border-b border-slate-100">
                                            <td class="py-2 px-3 font-semibold"><?php echo $rank++; ?></td>
                                            <td class="py-2 px-3"><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td class="py-2 px-3">₹<?php echo number_format($product['price'], 2); ?></td>
                                            <td class="py-2 px-3"><?php echo htmlspecialchars($product['shop_name']); ?></td>
                                            <td class="py-2 px-3 font-semibold"><?php echo intval($product['order_count']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                    <script src="dist/js/app.js"></script>
</body>
</html>
