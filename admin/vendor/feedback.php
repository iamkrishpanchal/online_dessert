<?php
session_start();
include '../connection.php';

// Only vendors may view this page
if (empty($_SESSION['vendor_id'])) {
    header('Location: ../login.php');
    exit;
}

$vendor_id = intval($_SESSION['vendor_id']);

// Ensure reviews table exists
$reviewsTable = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_reviews'");
$reviewsExist = $reviewsTable && mysqli_num_rows($reviewsTable) > 0;

$stats = ['count' => 0, 'avg_rating' => 0];
$reviews = [];
if ($reviewsExist) {
    $statsRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt, ROUND(AVG(rating),2) AS avg_rating FROM tbl_reviews WHERE vendor_id = {$vendor_id}");
    if ($statsRes) {
        $stats = mysqli_fetch_assoc($statsRes);
    }

    // choose product table if exists (same as admin)
    $productTable = 'tbl_products';
    $ptRes = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_products'");
    if (!($ptRes && mysqli_num_rows($ptRes) > 0)) {
        $productTable = 'tbl_product';
        $ptRes2 = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product'");
        if (!($ptRes2 && mysqli_num_rows($ptRes2) > 0)) {
            $productTable = null;
        }
    }

    $sql = "SELECT r.review_id, r.order_id, r.rating, r.title, r.review_text,
                   u.user_name AS customer";
    if ($productTable) {
        $sql .= ", p.product_name AS product";
    } else {
        $sql .= ", '' AS product";
    }
    $sql .= "\n            FROM tbl_reviews r
            LEFT JOIN tbl_users u ON u.user_id = r.user_id";

    if ($productTable) {
        $sql .= "\n            LEFT JOIN {$productTable} p ON p.product_id = r.product_id";
    }

    $sql .= "\n            WHERE r.vendor_id = {$vendor_id}"
          . "\n            ORDER BY r.created_at DESC\n            LIMIT 200";

    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $reviews[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Feedback - Vendor Panel</title>
    <link rel="stylesheet" href="../dist/css/app.css" />
    <style>
        /* Fix gap color between side menu and content in this panel */
        html, body, .flex { background-color: rgb(var(--color-slate-100)) !important; }
        .dark html, .dark body, .dark .flex { background-color: rgb(var(--color-darkmode-700)) !important; }
        .content { background-color: rgb(var(--color-slate-100)) !important; margin-top: 0 !important; padding-top: 0 !important; }
        .wrapper { margin-top: 0 !important; padding-top: 0 !important; }
        .top-bar { margin-top: 0 !important; }
    </style>
</head>
<body class="py-0 md:py-0 bg-white">
    <div class="flex mt-0 md:mt-0 overflow-hidden">
        <?php include 'sideMenu.php'; ?>
        <div class="content">
            <!-- BEGIN: Top Bar -->
            <?php include 'topBar.php' ?>
            <!-- END: Top Bar -->
            <div class="wrapper p-6">
                <div class="intro-y flex items-center mt-8">
                    <h2 class="text-2xl font-medium mr-auto flex items-center gap-2">
                        <i data-lucide="message-circle" class="w-6 h-6"></i>
                        Feedback
                    </h2>
                </div>

                <div class="grid grid-cols-12 gap-6 mt-5">
                    <div class="intro-y col-span-12 lg:col-span-12">
                        <?php if (!$reviewsExist): ?>
                            <div class="intro-y box">
                                <div class="p-5 text-center">
                                    <p class="text-gray-600">No feedback has been recorded yet.</p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($reviewsExist): ?>
                        <div class="intro-y box mt-5">
                            <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                                <h2 class="font-medium text-2xl mr-auto flex items-center gap-2">
                                <i data-lucide="star" class="w-6 h-6"></i>
                                Recent Feedback
                            </h2>
                            </div>
                            <div class="p-5">
                                <div class="overflow-x-auto">
                                    <?php if (count($reviews) === 0): ?>
                                        <div class="no-data">No feedback yet.</div>
                                    <?php else: ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Customer</th>
                                                <th>Product</th>
                                                <th>Rating</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reviews as $r): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($r['customer'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($r['product'] ?? ''); ?></td>
                                                <td><?php
                                                    $stars = intval($r['rating']);
                                                    echo '<span style="color:#f5c518; font-size:1.5rem;">' .
                                                         str_repeat('&#9733;', $stars) . str_repeat('&#9734;', 5 - $stars) .
                                                         '</span>';
                                                ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../dist/js/app.js"></script>
</body>
</html>