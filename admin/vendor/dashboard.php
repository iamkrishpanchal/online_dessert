<?php
include 'session.php';
include 'connection.php';

$vendor_id = intval($_SESSION['vendor_id']);

// Accept vendor online/offline button action from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vendor_status'])) {
    $desired = $_POST['vendor_status'] === 'active' ? 1 : 0;
    $vid = $vendor_id;
    $updateSql = "UPDATE tbl_vendors SET is_online = {$desired}, last_active = NOW() WHERE vendor_id = {$vid} LIMIT 1";
    mysqli_query($conn, $updateSql);
    header('Location: dashboard.php');
    exit;
}

$vendor_name = 'Vendor';
$vendorInfo = mysqli_query($conn, "SELECT COALESCE(vendor_name, '') AS name, COALESCE(is_online, 0) AS is_online FROM tbl_vendors WHERE vendor_id = {$vendor_id} LIMIT 1");
$vendor_is_online = 0;
if ($vendorInfo && ($row = mysqli_fetch_assoc($vendorInfo))) {
    if (!empty($row['name'])) {
        $vendor_name = $row['name'];
    }
    $vendor_is_online = intval($row['is_online']);
}

function fetchScalar($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if (!$res) {
        return 0;
    }
    $row = mysqli_fetch_row($res);
    return $row ? ($row[0] ?? 0) : 0;
}

$vendorIdEscaped = mysqli_real_escape_string($conn, (string)$vendor_id);

// calculate net earnings for vendor after 15% admin commission on order amount (subtotal + GST - discount)
$totalRevenue = fetchScalar($conn, "SELECT IFNULL(SUM((IFNULL(subtotal,0) + IFNULL(tax,0) - IFNULL(discount,0)) * 0.85),0) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped} AND order_status = 'Completed' AND payment_status = 'Paid'");
$totalOrders = fetchScalar($conn, "SELECT COUNT(*) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped}");
$completedOrders = fetchScalar($conn, "SELECT COUNT(*) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped} AND order_status = 'Completed'");
$pendingOrders = fetchScalar($conn, "SELECT COUNT(*) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped} AND order_status = 'Pending'");

// Today's activity metrics
$todayOrders = fetchScalar($conn, "SELECT COUNT(*) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped} AND DATE(order_date) = CURDATE()");
$todayEarnings = fetchScalar($conn, "SELECT IFNULL(SUM((IFNULL(subtotal,0) + IFNULL(tax,0) - IFNULL(discount,0)) * 0.85),0) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped} AND order_status = 'Completed' AND payment_status = 'Paid' AND DATE(order_date) = CURDATE()");
$todayNewCustomers = fetchScalar($conn, "SELECT COUNT(DISTINCT customer_name) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped} AND DATE(order_date) = CURDATE()");

$productTable = 'tbl_products';
$productsCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_products'");
if (!$productsCheck || mysqli_num_rows($productsCheck) === 0) {
    $productTable = 'tbl_product';
}

$totalCustomers = fetchScalar($conn, "SELECT COUNT(DISTINCT user_id) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped}");
$totalProducts = fetchScalar($conn, "SELECT COUNT(*) FROM {$productTable} WHERE vendor_id = {$vendorIdEscaped}");

// Recent orders list
$recentOrders = [];
$recentOrdersSql = "SELECT o.order_id, COALESCE(u.user_name, o.customer_name, 'Guest') AS customer_name, o.order_status, ((IFNULL(o.subtotal,0) + IFNULL(o.tax,0)) * 0.85) AS amount,
    (SELECT GROUP_CONCAT(oi.product_name SEPARATOR ', ') FROM tbl_order_items oi WHERE oi.order_id = o.order_id LIMIT 1) AS dessert
    FROM tbl_orders o
    LEFT JOIN tbl_users u ON u.user_id = o.user_id
    WHERE o.vendor_id = {$vendorIdEscaped}
    ORDER BY o.order_date DESC
    LIMIT 8";
if ($resRecent = mysqli_query($conn, $recentOrdersSql)) {
    while ($row = mysqli_fetch_assoc($resRecent)) {
        $recentOrders[] = $row;
    }
}

// Top selling products
$topProducts = [];
$topProductsQuery = "SELECT p.product_id,
       COALESCE(p.product_name, 'Unknown') AS product_name,
       COALESCE(p.product_image, '') AS product_image,
       SUM(oi.quantity) AS total_quantity
    FROM tbl_order_items oi
    JOIN tbl_orders o ON oi.order_id = o.order_id
    JOIN {$productTable} p ON oi.product_id = p.product_id
    WHERE o.vendor_id = {$vendorIdEscaped}
    GROUP BY p.product_id, p.product_name, p.product_image
    ORDER BY total_quantity DESC
    LIMIT 5";

if ($res = mysqli_query($conn, $topProductsQuery)) {
    while ($row = mysqli_fetch_assoc($res)) {
        $topProducts[] = $row;
    }
}

// Low stock products
$lowStockProducts = [];
$lowStockQuery = "SELECT product_id, COALESCE(stock, product_stock, 0) AS qty, product_name
    FROM {$productTable}
    WHERE vendor_id = {$vendorIdEscaped} AND COALESCE(stock, product_stock, 0) <= 5
    ORDER BY qty ASC
    LIMIT 5";
if ($stockRes = mysqli_query($conn, $lowStockQuery)) {
    while ($row = mysqli_fetch_assoc($stockRes)) {
        $lowStockProducts[] = $row;
    }
}

// Latest reviews
$latestReviews = [];
$enableReviewFeed = false;
$reviewsTable = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_reviews'");
if ($reviewsTable && mysqli_num_rows($reviewsTable) > 0) {
    $enableReviewFeed = true;

    $productNameCol = 'product_name';
    $productNameCheck = mysqli_query($conn, "SHOW COLUMNS FROM {$productTable} LIKE 'product_name'");
    if (!$productNameCheck || mysqli_num_rows($productNameCheck) === 0) {
        $productNameCol = 'pname';
    }

    $revQuery = "SELECT r.rating, COALESCE(r.review_text, '') AS review_text, 
                        COALESCE(p.{$productNameCol}, 'Product') AS product_name,
                        COALESCE(u.user_name, 'Customer') AS customer_name
        FROM tbl_reviews r
        LEFT JOIN {$productTable} p ON r.product_id = p.product_id
        LEFT JOIN tbl_users u ON r.user_id = u.user_id
        WHERE r.vendor_id = {$vendorIdEscaped}
        ORDER BY r.created_at DESC
        LIMIT 5";
    if ($revRes = mysqli_query($conn, $revQuery)) {
        while ($row = mysqli_fetch_assoc($revRes)) {
            $latestReviews[] = $row;
        }
    }
}

// Fallback when no review entries exist
if (!$enableReviewFeed || empty($latestReviews)) {
    $latestReviews = [
        ['rating' => 4, 'review_text' => 'Very tasty cake!', 'product_name' => 'Chocolate Cake', 'customer_name' => 'John'],
        ['rating' => 5, 'review_text' => 'Fast delivery', 'product_name' => 'Vanilla Pastry', 'customer_name' => 'Sarah'],
        ['rating' => 4, 'review_text' => 'Good taste and packaging', 'product_name' => 'Cheesecake', 'customer_name' => 'Mike'],
    ];
}


?>
<!DOCTYPE html>
<!--
Template Name: Tinker - HTML Admin Dashboard Template
Author: Left4code
Website: http://www.left4code.com/
Contact: muhammadrizki@left4code.com
Purchase: https://themeforest.net/user/left4code/portfolio
Renew Support: https://themeforest.net/user/left4code/portfolio
License: You must have a valid license purchased only from themeforest(the above link) in order to legally use the theme for your project.
-->
<html lang="en" class="light">
    <!-- BEGIN: Head -->
    <head>
        
        <title>Dashboard</title>
        <!-- BEGIN: CSS Assets-->
        <link rel="stylesheet" href="dist/css/app.css" />
        <!-- override green theme with dark blue -->
        <style>
            /* update theme variables so all of the "emerald"/green styles use the dark-blue color */
            :root {
                --color-primary: 10,37,64; /* rgb for #0a2540 */
                --color-success: 10,37,64;
            }
            /* fallback selectors in case some components use hardcoded green */
            .bg-emerald-900, .bg-emerald-600, .bg-theme-1, .text-emerald-900,
            .text-emerald-600, .text-theme-1 {
                background-color: #0a2540 !important;
                color: #0a2540 !important;
            }
            .mobile-menu-bar, .side-menu {
                background-color: #0a2540 !important;
            }
            /* keep dropdown lists dark blue as well */
            .side-menu .menu__sub-open,
            .side-menu .menu__sub-open li,
            .side-menu .menu__sub-open li > a,
            .side-menu .menu__sub-open li > .menu {
                background-color: #0a2540 !important;
                color: #fff !important;
            }
            .side-menu .menu__sub-open li > a:hover,
            .side-menu .menu__sub-open li > .menu:hover {
                background-color: #081f32 !important;
            }

            /* Dashboard Cards & effects */
            .box {
                background: #ffffff;
                border-radius: 14px;
                border: 1px solid rgba(148, 163, 184, 0.3);
                box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
                transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            }
            .box:hover {
                transform: translateY(-4px);
                box-shadow: 0 14px 32px rgba(15, 23, 42, 0.12);
                border-color: rgba(37, 99, 235, 0.35);
            }
            .box .text-slate-500 {
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                font-size: 0.74rem;
                margin-bottom: 0.65rem;
            }
            .box .value, .box .text-2xl {
                font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                color: #0f172a;
            }
            .box.p-5.bg-white.border { background: linear-gradient(145deg, #ffffff, #f8fbff); }
            .top-card-1 { background: linear-gradient(135deg, #fee2e2, #fef3c7); }
            .top-card-2 { background: linear-gradient(135deg, #d1fae5, #dcfce7); }
            .top-card-3 { background: linear-gradient(135deg, #ede9fe, #faf5ff); }
            .top-card-4 { background: linear-gradient(135deg, #fef3c7, #fef9c3); }
            .today-activity { background: linear-gradient(135deg, #ecfdf5, #f0f9ff); }
            .top-selling-item { border-bottom: 1px solid rgba(148, 163, 184, 0.22); padding: 0.65rem 0; border-radius: 0.75rem; background: rgba(255,255,255,0.4); backdrop-filter: blur(1px); transition: transform 0.2s ease, background 0.2s ease; }
            .top-selling-item:hover { transform: translateX(4px); background: rgba(255,255,255,0.75); }
            .top-selling-item:last-child { border-bottom: none; }
            .top-selling-item strong { color: #1d4ed8; }
            .low-stock-item { background: #fff7ed; border-left: 4px solid #f97316; }
            .recent-review { background:#f2f9ff; border: 1px solid rgba(96,165,250,0.33); box-shadow: 0 8px 22px rgba(31, 41, 55, 0.06); }
            .recent-review:hover { background:#e0f2fe; }
            .recent-review .text-amber-500 { color:#f59e0b !important; }
            .table th { background: #f1f5f9; border-color: #e2e8f0; }
            .table tbody tr:hover { background: #f8fafc; }
            .grid-cols-12 > .col-span-12 { transition: background 0.3s ease; }

            /* Accent decorations */
            .dash-badge { display: inline-flex; align-items: center; gap: 0.35rem; background: rgba(56, 189, 248, 0.12); color: #0369a1; border-radius: 888px; padding: 0.2rem 0.6rem; font-size: 0.75rem; font-weight: 600; }
            .dash-glow { position: relative; z-index: 1; }
            .dash-glow::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.25), transparent 51%); z-index: -1; border-radius: 16px; }
            .text-2xl { letter-spacing: 0.01em; }
            .box .text-xs { text-transform: uppercase; letter-spacing: 0.08em; }
            .top-selling-item span { font-weight: 600; }
            .today-activity { border: 1px solid rgba(37, 99, 235, 0.25); }
            .text-slate-500 + .box { color: #334155; }

            /* New vibrant card styles */
            .dashboard-card {
                position: relative;
                overflow: hidden;
                border-radius: 16px;
                color: #0f172a;
                min-height: 126px;
            }
            .dashboard-card::after {
                content: '';
                position: absolute;
                inset: 0;
                pointer-events: none;
                background: linear-gradient(45deg, rgba(255,255,255,0.18), rgba(255,255,255,0.06));
                opacity: 0.88;
                mix-blend-mode: overlay;
                transition: opacity 0.35s ease;
            }
            .dashboard-card:hover::after {
                opacity: 0.5;
            }
            .dashboard-card .metric {font-size: 2.4rem; font-weight: 700; color: #1e293b;}
            .dashboard-card .metric-small {font-size: 1rem; color: #334155;}
            .dashboard-card h4 {text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.5rem; font-size: 0.82rem;}
            .box .card-icon {
                font-size: 1.3rem;
                padding: 0.55rem;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.35);
                color: #fff;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
                margin-left: auto;
                transition: transform 0.3s ease;
            }
            .dashboard-card:hover .card-icon {
                transform: scale(1.08);
            }
            .recent-orders-card th { color: #1d4ed8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; font-size: 0.84rem; }
            .recent-orders-card td { border-top: 1px solid rgba(148, 163, 184, 0.22); font-size: 1rem; }
            .recent-orders-card tbody tr:hover { background: rgba(37, 99, 235, 0.06); }
            .recent-orders-card .text-emerald-700 { color: #059669 !important; }
            .recent-orders-card .text-amber-700 { color: #d97706 !important; }
            .dashboard-card .text-slate-500 { color: #475569; }
            .dashboard-card .text-amber-700 { color: #92400e; }
            .text-xs { font-size: 0.8rem !important; }
            .text-sm { font-size: 1rem !important; }
            .table th, .table td { font-size: 1rem; }
            .box.bg-amber-50.dashboard-card { background: linear-gradient(130deg, #fff7ed, #fffaeb); border-color: rgba(245, 158, 11, 0.3); }
            .box.bg-white.dashboard-card { background: linear-gradient(130deg, #f8fafc, #ffffff); border-color: rgba(148, 163, 184, 0.35); }
            .top-card-1.dashboard-card { background: linear-gradient(120deg, #fde68a, #fef3c7); }
            .top-card-2.dashboard-card { background: linear-gradient(120deg, #86efac, #dcfce7); }
            .top-card-3.dashboard-card { background: linear-gradient(120deg, #c7d2fe, #eef2ff); }
            .top-card-4.dashboard-card { background: linear-gradient(120deg, #fbbf24, #fff7ed); }
            .today-activity.dashboard-card { background: linear-gradient(120deg, #bfdbfe, #dbeafe); border-color: rgba(59, 130, 246, 0.28); }
            .top-selling-item { border-bottom: 1px solid rgba(148, 163, 184, 0.25); padding: 0.7rem 0; }
            .top-selling-item:last-child { border-bottom: none; }
            .low-stock-item { background: rgba(254, 242, 224, 0.54); border-left: 4px solid #f97316; }
            .recent-review { background:#f0f9ff; border-radius: 12px; padding: 0.8rem; }
            .recent-review .text-amber-500 { color:#f59e0b !important; }
        </style>
        <!-- END: CSS Assets-->

    </head>
    <!-- END: Head -->
    <body class="py-5 md:py-0 bg-white">
        <!-- BEGIN: Mobile Menu -->
        <div class="mobile-menu md:hidden">
            <div class="mobile-menu-bar">
                <a href="" class="flex mr-auto">
                    <img alt="Midone - HTML Admin Template" class="w-6" src="dist/images/logo.svg">
                </a>
                <a href="javascript:;" class="mobile-menu-toggler"> <i data-lucide="bar-chart-2" class="w-8 h-8 text-white transform -rotate-90"></i> </a>
            </div>
           
        </div>
        <!-- END: Mobile Menu -->
        <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
            <!-- BEGIN: Side Menu -->
            <?php include 'sideMenu.php' ?>
            <!-- END: Side Menu -->
            <!-- BEGIN: Content -->
            <div class="content" style="max-width: 1800px; width: 95vw;">
                <!-- BEGIN: Top Bar -->
                <?php include 'topbar.php' ?>
                <!-- END: Top Bar -->
                <div class="relative">
                    <div class="grid grid-cols-12 gap-6">
                        <div class="col-span-12 xl:col-span-12 2xl:col-span-12 z-10">
                            <div class="mt-6 -mb-6 intro-y">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- <div class="box p-5 bg-white border dashboard-card">
                                        <div class="flex justify-between items-start">
                                            <h4>Total Customers</h4>
                                            <span class="card-icon">👥</span>
                                        </div>
                                        <div class="metric mt-2"><?php echo number_format($totalCustomers); ?></div>
                                        <div class="metric-small mt-1">Distinct customers served</div>
                                    </div>
                                    <div class="box p-5 bg-white border dashboard-card">
                                        <div class="flex justify-between items-start">
                                            <h4>Total Products</h4>
                                            <span class="card-icon">📦</span>
                                        </div>
                                        <div class="metric mt-2"><?php echo number_format($totalProducts); ?></div>
                                        <div class="metric-small mt-1">Items available in your store</div>
                                    </div> -->
                                </div>
                            </div>
                            <div class="mt-14 mb-3 grid grid-cols-12 sm:gap-10 intro-y">
                                <div class="col-span-12 sm:col-span-6 md:col-span-4 py-0 sm:pl-5 md:pl-0 lg:pl-5 relative text-center sm:text-left">
                                   
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                                        <div class="box p-5 top-card-1 dashboard-card">
                                            <div class="flex justify-between items-start">
                                                <h4>Total Earnings</h4>
                                                <span class="card-icon">💰</span>
                                            </div>
                                            <div class="metric mt-2">₹ <?php echo number_format($totalRevenue, 2); ?></div>
                                            <br>
                                            <div class="metric-small mt-1">(net of delivery &amp; discounts)</div>
                                        </div>
                                        <div class="box p-5 top-card-2 dashboard-card">
                                            <div class="flex justify-between items-start">
                                                <h4>Total Orders</h4>
                                                <span class="card-icon">🛒</span>
                                            </div>
                                            <div class="metric mt-2"><?php echo number_format($totalOrders); ?></div>
                                            <br>
                                            <div class="metric-small mt-1">All time orders</div>
                                        </div>
                                        <div class="box p-5 top-card-3 dashboard-card">
                                            <div class="flex justify-between items-start">
                                                <h4>Completed</h4>
                                                <span class="card-icon">✅</span>
                                            </div>
                                            <div class="metric mt-2"><?php echo number_format($completedOrders); ?></div>
                                            <br>
                                            <div class="metric-small mt-1">Order completion rate</div>
                                        </div>
                                        <div class="box p-5 top-card-4 dashboard-card">
                                            <div class="flex justify-between items-start">
                                                <h4>Pending</h4>
                                                <span class="card-icon">⌛</span>
                                            </div>
                                            <div class="metric mt-2"><?php echo number_format($pendingOrders); ?></div>
                                            <br>
                                            <div class="metric-small mt-1">Waiting for fulfilment</div>
                                        </div>
                                        <div class="box p-5 today-activity dashboard-card">
                                            <div class="text-slate-500 text-xs">Today’s Activity</div>
                                            <div class="mt-4 space-y-3">
                                                <div class="flex justify-between items-center"><span class="text-sm">Orders Today:</span><strong class="text-lg ml-2"><?php echo number_format($todayOrders); ?></strong></div>
                                                <div class="flex justify-between items-center"><span class="text-sm">Earnings Today:</span><strong class="text-lg ml-2">₹ <?php echo number_format($todayEarnings,2); ?></strong></div>
                                                <div class="flex justify-between items-center"><span class="text-sm">New Customers:</span><strong class="text-lg ml-2"><?php echo number_format($todayNewCustomers); ?></strong></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-span-12 sm:col-span-6 md:col-span-8"> 
                                    <div class="grid grid-cols-1 gap-4">

                                        <div class="box p-5 bg-amber-50 border border-amber-200 dashboard-card">
                                            <div class="text-amber-700 text-xs font-medium">Low Stock Alert</div>
                                            <ul class="mt-3 list-disc pl-5 text-sm">
                                                <?php if (count($lowStockProducts) === 0): ?>
                                                    <li class="text-slate-500">No low stock product alerts.</li>
                                                <?php else: ?>
                                                    <?php foreach ($lowStockProducts as $item): ?>
                                                        <li class="text-amber-800"><?php echo htmlspecialchars($item['product_name']); ?> – Only <?php echo intval($item['qty']); ?> left</li>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </ul>
                                        </div>

                                        <div class="box p-5 bg-white border dashboard-card">
                                            <div class="text-slate-500 text-xs font-medium">Recent Reviews</div>
                                            <div class="mt-3 overflow-x-auto">
                                                <table class="table mt-3 w-full text-sm">
                                                    <thead>
                                                        <tr class="bg-slate-100">
                                                            <th class="py-2 px-2 text-left">Rating</th>
                                                            <th class="py-2 px-2 text-left">Product</th>
                                                            <th class="py-2 px-2 text-left">Customer</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (empty($latestReviews)): ?>
                                                            <tr><td class="py-2 px-2" colspan="3">No reviews available.</td></tr>
                                                        <?php else: ?>
                                                            <?php foreach ($latestReviews as $review): ?>
                                                                <tr class="border-t">
                                                                    <td class="py-2 px-2 text-amber-500"><?php echo str_repeat('★', intval($review['rating'])); ?><?php echo str_repeat('☆', 5 - intval($review['rating'])); ?></td>
                                                                    <td class="py-2 px-2"><?php echo htmlspecialchars($review['product_name'] ?? 'Product'); ?></td>
                                                                    <td class="py-2 px-2"><?php echo htmlspecialchars($review['customer_name'] ?? 'Customer'); ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                      <div class="box p-5 bg-white border dashboard-card">
                                        <div class="flex justify-between items-start">
                                            <h4>Total Customers</h4>
                                            <span class="card-icon">👥</span>
                                        </div>
                                        <div class="metric mt-2"><?php echo number_format($totalCustomers); ?></div>
                                        <br>
                                        <div class="metric-small mt-1">Distinct customers served</div>
                                    </div>
                                    <div class="box p-5 bg-white border dashboard-card">
                                        <div class="flex justify-between items-start">
                                            <h4>Total Products</h4>
                                            <span class="card-icon">📦</span>
                                        </div>
                                        <div class="metric mt-2"><?php echo number_format($totalProducts); ?></div>
                                        <br>
                                        <div class="metric-small mt-1">Items available in your store</div>
                                    </div>


                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Top 5 Products Sold -->
                        <div class="col-span-12">
                            <div class="grid grid-cols-12 gap-6 intro-y">
                                <div class="col-span-12 md:col-span-12">
                                    <div class="box p-5 overflow-x-auto bg-white border dashboard-card recent-orders-card">
                                        <div class="text-slate-500 text-xs font-medium">Top Products Sold</div>
                                        <table class="table mt-3 w-full text-sm">
                                            <thead>
                                                <tr class="bg-slate-100">
                                                    <th class="py-2 px-2">Product</th>
                                                    <th class="py-2 px-2">Quantity Sold</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($topProducts)): ?>
                                                    <tr><td class="p-2" colspan="2">No sold product data available.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($topProducts as $product): ?>
                                                    <tr class="border-t">
                                                        <td class="py-2 px-2"><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                        <td class="py-2 px-2 font-semibold"><?php echo intval($product['total_quantity']); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
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