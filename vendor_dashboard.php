<?php
session_start();

// Make sure vendor is logged in
if (empty($_SESSION['vendor_id'])) {
    header('Location: login.php');
    exit;
}

// Database connection
include __DIR__ . '/user/connection.php';
$vendor_id = intval($_SESSION['vendor_id']);

// Helpers
function fetchScalar($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if (!$res) {
        return 0;
    }
    $row = mysqli_fetch_row($res);
    return $row ? ($row[0] ?? 0) : 0;
}

// Dashboard cards
$pendingOrdersList = [];
// ensure we escape the vendor id before using in all queries
$vendorIdEscaped = mysqli_real_escape_string($conn, (string)$vendor_id);

$pendingOrdersQuery = "SELECT order_id, customer_name, total_amount, order_date, rider_id FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped} AND order_status = 'Pending'";
if ($res3 = mysqli_query($conn, $pendingOrdersQuery)) {
    while ($row = mysqli_fetch_assoc($res3)) {
        $pendingOrdersList[] = $row;
    }
}

// Fetch available riders
$ridersList = [];
$ridersQuery = "SELECT rider_id, rider_name FROM tbl_riders WHERE status = 'active'";
if ($res4 = mysqli_query($conn, $ridersQuery)) {
    while ($row = mysqli_fetch_assoc($res4)) {
        $ridersList[] = $row;
    }
}
$vendorIdEscaped = mysqli_real_escape_string($conn, (string)$vendor_id);

// compute total orders count, but revenue uses vendor's actual earnings after 15% admin commission (no delivery fee deduction)
$totalRevenue = fetchScalar($conn, "SELECT IFNULL(SUM((IFNULL(subtotal,0) + IFNULL(tax,0)) * 0.85),0) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped} AND order_status = 'Completed' AND payment_status = 'Paid'");
$totalOrders = fetchScalar($conn, "SELECT COUNT(*) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped}");
$completedOrders = fetchScalar($conn, "SELECT COUNT(*) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped} AND order_status = 'Completed'");
$pendingOrders = fetchScalar($conn, "SELECT COUNT(*) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped} AND order_status = 'Pending'");

// Sales analytics (last 30 days)
$salesData = [];
$labels = [];
$now = new DateTime('today');
for ($i = 29; $i >= 0; $i--) {
    $day = (clone $now)->modify("-{$i} days");
    $key = $day->format('Y-m-d');
    $labels[] = $day->format('M j');
    $salesData[$key] = 0.0;
}

$query = "SELECT DATE(order_date) AS order_day, IFNULL(SUM((IFNULL(subtotal,0) + IFNULL(tax,0)) * 0.85),0) AS revenue
          FROM tbl_orders
          WHERE vendor_id = {$vendorIdEscaped}
            AND order_status = 'Completed'
            AND payment_status = 'Paid'
            AND order_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
          GROUP BY order_day
          ORDER BY order_day ASC";

if ($res = mysqli_query($conn, $query)) {
    while ($row = mysqli_fetch_assoc($res)) {
        $day = $row['order_day'];
        $salesData[$day] = (float)$row['revenue'];
    }
}

$salesValues = array_values($salesData);

// Top Selling Products
$topProducts = [];
$topProductsQuery = "
    SELECT p.product_id,
           COALESCE(p.product_name, 'Unknown Product') AS product_name,
           COALESCE(p.product_image, '') AS product_image,
           SUM(oi.quantity) AS total_quantity
    FROM tbl_order_items oi
    JOIN tbl_orders o ON oi.order_id = o.order_id
    JOIN tbl_products p ON oi.product_id = p.product_id
    WHERE o.vendor_id = {$vendorIdEscaped}
      AND o.order_status = 'Completed'
      AND o.payment_status = 'Paid'
    GROUP BY p.product_id, p.product_name, p.product_image
    ORDER BY total_quantity DESC
    LIMIT 5
";
if ($res2 = mysqli_query($conn, $topProductsQuery)) {
    while ($row = mysqli_fetch_assoc($res2)) {
        $topProducts[] = $row;
    }
}

// Helper for currency formatting
function formatCurrency($amount) {
    return number_format((float)$amount, 2);
}

// Today's metrics (live)
$todayOrdersCount = fetchScalar($conn, "SELECT COUNT(*) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped} AND DATE(order_date) = CURDATE()");
$todayEarnings = fetchScalar($conn, "SELECT IFNULL(SUM((IFNULL(subtotal,0) + IFNULL(tax,0)) * 0.85),0) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped} AND order_status = 'Completed' AND payment_status = 'Paid' AND DATE(order_date) = CURDATE()");
$todayNewCustomers = fetchScalar($conn, "SELECT COUNT(DISTINCT customer_name) FROM tbl_orders WHERE vendor_id = {$vendorIdEscaped} AND DATE(order_date) = CURDATE()");

// Recent orders for table (show latest 8)
$recentOrders = [];
$recentOrdersSql = "SELECT o.order_id, o.customer_name, (IFNULL(o.subtotal,0) + IFNULL(o.tax,0)) * 0.85 AS amount, o.order_status,
    (SELECT oi.product_name FROM tbl_order_items oi WHERE oi.order_id = o.order_id LIMIT 1) AS dessert
    FROM tbl_orders o
    WHERE o.vendor_id = {$vendorIdEscaped}
    ORDER BY o.order_date DESC
    LIMIT 8";
if ($resRecent = mysqli_query($conn, $recentOrdersSql)) {
    while ($order = mysqli_fetch_assoc($resRecent)) {
        $recentOrders[] = $order;
    }
}

?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vendor Dashboard</title>
    <link rel="stylesheet" href="admin/dist/css/app.css" />
    <style>
        body { background: #f3f4f6; }
        .dashboard-wrapper { max-width: 1140px; margin: 0 auto; padding: 2rem 1rem; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        .card { background: #ffffff; border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 8px 18px rgba(0,0,0,0.08); }
        .card h3 { margin: 0; font-size: 1rem; color: #374151; }
        .card .value { margin-top: 0.75rem; font-size: 2rem; font-weight: 700; color: #0f172a; }
        .card.small { padding: 0.85rem; }

        .section { margin-top: 2rem; }
        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .section-header h2 { font-size: 1.25rem; font-weight: 700; margin: 0; }

        .chart-wrapper { background: #ffffff; border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 8px 18px rgba(0,0,0,0.08); }
        .chart-legend { display: flex; gap: 1rem; margin-top: 0.75rem; }
        .chart-legend span { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: #475569; }
        .chart-legend span::before { content: ''; width: 12px; height: 12px; display: inline-block; border-radius: 999px; }

        .top-products { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
        .product-card { padding: 1rem; background: #ffffff; border-radius: 0.75rem; box-shadow: 0 8px 18px rgba(0,0,0,0.08); display: flex; gap: 0.75rem; align-items: center; }
        .product-image { width: 58px; height: 58px; flex-shrink: 0; background: #e2e8f0; border-radius: 0.75rem; overflow: hidden; display: grid; place-items: center; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        .product-meta { flex-grow: 1; }
        .product-title { font-weight: 700; margin: 0; color: #0f172a; font-size: 1rem; }
        .product-qty { margin: 0.25rem 0 0; color: #64748b; font-size: 0.85rem; }

        .no-data { color: #64748b; font-size: 0.95rem; padding: 1.25rem; text-align: center; }

        @media (max-width: 640px) {
            .dashboard-wrapper { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <header class="section-header">
            <div>
                <h1 class="text-2xl font-semibold">Vendor Dashboard</h1>
                <p class="text-slate-500 mt-1">Overview of sales, orders and top products.</p>
            </div>
            <div>
                <a href="logout.php" class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">Logout</a>
            </div>
        </header>

        <div class="cards">
            <div class="card">
                <h3>Total Earnings</h3>
                <div class="value">₹ <?php echo formatCurrency($totalRevenue); ?></div>
            </div>
            <div class="card">
                <h3>Total Orders</h3>
                <div class="value"><?php echo number_format($totalOrders); ?></div>
            </div>
            <div class="card">
                <h3>Completed Orders</h3>
                <div class="value"><?php echo number_format($completedOrders); ?></div>
            </div>
            <div class="card">
                <h3>Pending Orders</h3>
                <div class="value"><?php echo number_format($pendingOrders); ?></div>
            </div>
        </div>

        <section class="section" style="margin-top:1.5rem;">
            <div class="cards" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem;">
                <div class="card" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <h3>Today's Activity</h3>
                    <div style="font-size: 0.98rem; color: #475569;">Orders Today:</div>
                    <div class="value"><?php echo number_format($todayOrdersCount); ?></div>
                    <div style="font-size: 0.98rem; color: #475569;">Earnings Today:</div>
                    <div class="value">₹ <?php echo formatCurrency($todayEarnings); ?></div>
                    <div style="font-size: 0.98rem; color: #475569;">New Customers:</div>
                    <div class="value"><?php echo number_format($todayNewCustomers); ?></div>
                </div>
                <div class="card" style="overflow-x:auto;">
                    <h3>Recent Orders</h3>
                    <?php if (empty($recentOrders)): ?>
                        <div class="no-data">No recent orders yet.</div>
                    <?php else: ?>
                        <table style="width:100%; border-collapse: collapse; margin-top: 0.75rem;">
                            <thead>
                                <tr style="background:#f8fafc; color:#334155; font-size:0.85rem;">
                                    <th style="text-align:left; padding:0.55rem;">Order ID</th>
                                    <th style="text-align:left; padding:0.55rem;">Customer</th>
                                    <th style="text-align:left; padding:0.55rem;">Dessert</th>
                                    <th style="text-align:right; padding:0.55rem;">Amount</th>
                                    <th style="text-align:left; padding:0.55rem;">Status</th>
                                    <th style="text-align:left; padding:0.55rem;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $o): ?>
                                    <tr style="border-top:1px solid #e2e8f0;">
                                        <td style="padding:0.55rem;">#<?php echo intval($o['order_id']); ?></td>
                                        <td style="padding:0.55rem;"><?php echo htmlspecialchars($o['customer_name']); ?></td>
                                        <td style="padding:0.55rem;"><?php echo htmlspecialchars($o['dessert'] ?: '--'); ?></td>
                                        <td style="padding:0.55rem; text-align:right;">₹ <?php echo formatCurrency($o['amount']); ?></td>
                                        <td style="padding:0.55rem; color:<?php echo ($o['order_status'] === 'Pending' ? '#dc2626' : '#16a34a'); ?>;"><?php echo htmlspecialchars($o['order_status']); ?></td>
                                        <td style="padding:0.55rem;">
                                            <a href="order_view.php?order_id=<?php echo intval($o['order_id']); ?>" style="font-size:0.78rem;color:#2563eb;">View</a>
                                            |
                                            <a href="order_update.php?order_id=<?php echo intval($o['order_id']); ?>" style="font-size:0.78rem;color:#7c3aed;">Update</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-header">
                <h2>Sales Analytics (Last 30 days)</h2>
            </div>
            <div class="chart-wrapper">
                <canvas id="salesChart" height="120"></canvas>
                <div class="chart-legend">
                    <span><span style="background:#2563eb"></span> Daily revenue</span>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-header">
                <h2>Top Selling Products</h2>
            </div>
            <?php if (count($topProducts) === 0): ?>
                <div class="no-data">No product sales found for your store yet.</div>
            <?php else: ?>
                <div class="top-products">
                    <?php foreach ($topProducts as $p):
                        $img = trim($p['product_image']);
                        $imgUrl = $img !== '' ? htmlspecialchars($img, ENT_QUOTES) : '';
                        $label = htmlspecialchars($p['product_name']);
                        $qty = number_format($p['total_quantity']);
                    ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if ($imgUrl): ?>
                                    <img src="<?php echo $imgUrl; ?>" alt="<?php echo $label; ?>">
                                <?php else: ?>
                                    <span style="color:#64748b;font-size:0.75rem;">No image</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-meta">
                                <p class="product-title"><?php echo $label; ?></p>
                                <p class="product-qty"><?php echo $qty; ?> sold</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <footer style="margin-top: 3rem; text-align: center; color: #64748b; font-size: 0.85rem;">
                    <section class="section">
                        <div class="section-header">
                            <h2>Pending Orders - Assign Rider</h2>
                        </div>
                        <?php if (count($pendingOrdersList) === 0): ?>
                            <div class="no-data">No pending orders to assign.</div>
                        <?php else: ?>
                            <table style="width:100%;background:#fff;border-radius:0.75rem;box-shadow:0 8px 18px rgba(0,0,0,0.08);margin-bottom:2rem;">
                                <thead>
                                    <tr style="background:#f3f4f6;">
                                        <th style="padding:0.75rem;text-align:left;">Order ID</th>
                                        <th style="padding:0.75rem;text-align:left;">Customer</th>
                                        <th style="padding:0.75rem;text-align:left;">Amount</th>
                                        <th style="padding:0.75rem;text-align:left;">Order Date</th>
                                        <th style="padding:0.75rem;text-align:left;">Assign Rider</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingOrdersList as $order): ?>
                                    <tr>
                                        <td style="padding:0.75rem;">#<?php echo $order['order_id']; ?></td>
                                        <td style="padding:0.75rem;"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td style="padding:0.75rem;">₹<?php echo formatCurrency($order['total_amount']); ?></td>
                                        <td style="padding:0.75rem;"><?php echo htmlspecialchars($order['order_date']); ?></td>
                                        <td style="padding:0.75rem;">
                                            <?php if ($order['rider_id']): ?>
                                                <span style="color:#2563eb;font-weight:600;">Assigned</span>
                                            <?php else: ?>
                                                <form method="post" action="vendor_assign_rider.php" style="display:inline-block;" onsubmit="return assignRider(this,event)">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <select name="rider_id" required style="padding:0.4rem 0.7rem;border-radius:0.5rem;border:1px solid #cbd5e1;">
                                                        <option value="">Select Rider</option>
                                                        <?php foreach ($ridersList as $rider): ?>
                                                            <option value="<?php echo $rider['rider_id']; ?>"><?php echo htmlspecialchars($rider['rider_name']); ?></option>
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
                    </section>
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
            <p>Powered by your multi-vendor platform.</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('salesChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($labels, JSON_UNESCAPED_SLASHES); ?>,
                    datasets: [{
                        label: 'Revenue',
                        data: <?php echo json_encode($salesValues, JSON_UNESCAPED_SLASHES); ?>,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.15)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2,
                        pointHoverRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#475569' }
                        },
                        y: {
                            grid: { color: 'rgba(148,163,184,0.35)' },
                            ticks: {
                                color: '#475569',
                                callback: function(value) { return '₹' + value; }
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed.y;
                                    return 'Revenue: ₹' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
