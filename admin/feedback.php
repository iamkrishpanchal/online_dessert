<?php
session_start();
include 'connection.php';

// Only admins can view feedback
if (empty($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Ensure reviews table exists
$reviewsTable = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_reviews'");
$reviewsExist = $reviewsTable && mysqli_num_rows($reviewsTable) > 0;

$stats = ['count' => 0, 'avg_rating' => 0];
$reviews = [];
if ($reviewsExist) {
    $statsRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt, ROUND(AVG(rating),2) AS avg_rating FROM tbl_reviews");
    if ($statsRes) {
        $stats = mysqli_fetch_assoc($statsRes);
    }

    // select shop_name as shop and drop created_at since we no longer display it
    $sql = "SELECT r.review_id, r.order_id, r.rating, r.title, r.review_text,
                   u.user_name AS customer, v.shop_name AS shop_name";
    $sql .= "\n            FROM tbl_reviews r
            LEFT JOIN tbl_users u ON u.user_id = r.user_id
            LEFT JOIN tbl_vendors v ON v.vendor_id = r.vendor_id";

    $sql .= "\n            ORDER BY r.created_at DESC\n            LIMIT 200";

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
    <title>Feedback - Dessert Magic</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        /* Ensure the gap between side menu and content matches the page background, not green html base color */
        html, body, .flex {
            background-color: rgb(var(--color-slate-100)) !important;
        }
        .content {
            margin-left: 0 !important;
        }
    </style>
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <?php include 'sideMenu.php'; ?>
        <div class="content">
            <div class="wrapper p-6">
                <div class="intro-y flex items-center mt-8">
                    <h2 class="text-lg font-medium mr-auto">Feedback</h2>
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
                                <h2 class="font-medium text-base mr-auto">Recent Feedback</h2>
                            </div>
                            <div class="p-5">
                                <div class="overflow-x-auto">
                                    <?php if (count($reviews) === 0): ?>
                                        <div class="no-data">No feedback yet.</div>
                                    <?php else: ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Order</th>
                                                <th>Customer</th>
                                                <th>Shop Name</th>
                                                <th>Rating</th>
                                                <th>Title</th>
                                                <th>Feedback</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reviews as $r): ?>
                                            <tr>
                                                <td><?php echo intval($r['review_id']); ?></td>
                                                <td><a href="order_details.php?order_id=<?php echo intval($r['order_id']); ?>">#<?php echo intval($r['order_id']); ?></a></td>
                                                <td><?php echo htmlspecialchars($r['customer'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($r['shop_name'] ?? ''); ?></td>
                                                <td><?php
                                                    $stars = intval($r['rating']);
                                                    echo '<span style="color:#f5c518; font-size:1.5rem;">' .
                                                         str_repeat('&#9733;', $stars) . str_repeat('&#9734;', 5 - $stars) .
                                                         '</span>';
                                                ?></td>
                                                <td><?php echo htmlspecialchars($r['title'] ?? ''); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($r['review_text'] ?? '')); ?></td>
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
</body>
</html>
