<?php
session_start();
include 'connection.php';

// Ensure the reviews table exists (some installs may not have it yet)
$tblRes = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_reviews'");
if ($tblRes && mysqli_num_rows($tblRes) === 0) {
    mysqli_query($conn,
        "CREATE TABLE IF NOT EXISTS tbl_reviews (
            review_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            vendor_id INT,
            product_id INT,
            order_id INT,
            rating INT NOT NULL,
            title VARCHAR(255),
            review_text TEXT,
            helpful_count INT DEFAULT 0,
            is_verified_purchase INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );
}

// Only logged-in users can leave feedback
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
if ($order_id <= 0) {
    header('Location: orders.php');
    exit;
}

// Fetch the order to ensure it belongs to the user and is eligible for feedback
$orderStmt = mysqli_prepare($conn, "SELECT order_id, vendor_id, payment_status, order_status FROM tbl_orders WHERE order_id = ? AND user_id = ? LIMIT 1");
$order = null;
if ($orderStmt) {
    mysqli_stmt_bind_param($orderStmt, 'ii', $order_id, $user_id);
    mysqli_stmt_execute($orderStmt);
    $res = mysqli_stmt_get_result($orderStmt);
    $order = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($orderStmt);
}

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Feedback should only be allowed for completed orders
$orderStatus = strtolower(trim($order['order_status'] ?? ''));
if ($orderStatus !== 'completed') {
    header('Location: orders.php?msg=feedback_not_ready');
    exit;
}

// Fetch all products in the order
$orderProducts = [];
$productStmt = mysqli_prepare($conn,
    "SELECT oi.product_id, p.product_name, p.vendor_id, v.shop_name
     FROM tbl_order_items oi
     JOIN tbl_products p ON oi.product_id = p.product_id
     LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id
     WHERE oi.order_id = ?");
if ($productStmt) {
    mysqli_stmt_bind_param($productStmt, 'i', $order_id);
    mysqli_stmt_execute($productStmt);
    $res = mysqli_stmt_get_result($productStmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $orderProducts[] = $row;
    }
    mysqli_stmt_close($productStmt);
}

if (empty($orderProducts)) {
    header('Location: orders.php?msg=no_products');
    exit;
}



// load existing reviews for this order/user per product
$existingReviews = [];
$sql = "SELECT review_id, product_id, rating FROM tbl_reviews WHERE order_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ii', $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $existingReviews[$r['product_id']] = $r;
    }
    mysqli_stmt_close($stmt);
}

$errors = [];
$successMessage = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // process rating for each product
    foreach ($orderProducts as $prod) {
        $pid = intval($prod['product_id']);
        $pname = $prod['product_name'] ?: 'Product';
        $vid = intval($prod['vendor_id']);
        $rVal = isset($_POST['rating'][$pid]) ? intval($_POST['rating'][$pid]) : 0;
        if ($rVal < 1 || $rVal > 5) {
            $errors[] = "Please provide a rating between 1 and 5 for {$pname}.";
        } else {
            // insert or update
            if (isset($existingReviews[$pid])) {
                $updateStmt = mysqli_prepare($conn, "UPDATE tbl_reviews SET rating = ?, updated_at = NOW() WHERE review_id = ? AND user_id = ?");
                if ($updateStmt) {
                    mysqli_stmt_bind_param($updateStmt, 'iii', $rVal, $existingReviews[$pid]['review_id'], $user_id);
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
                }
            } else {
                $insertStmt = mysqli_prepare($conn, "INSERT INTO tbl_reviews (user_id, vendor_id, product_id, order_id, rating, is_verified_purchase) VALUES (?, ?, ?, ?, ?, 1)");
                if ($insertStmt) {
                    mysqli_stmt_bind_param($insertStmt, 'iiiii', $user_id, $vid, $pid, $order_id, $rVal);
                    mysqli_stmt_execute($insertStmt);
                    mysqli_stmt_close($insertStmt);
                }
            }
        }
    }

    if (empty($errors)) {
        header('Location: orders.php?msg=feedback_saved');
        exit;
    }
}


// Pre-fill ratings for form per product
$rating = [];
foreach ($orderProducts as $prod) {
    $pid = intval($prod['product_id']);
    $rating[$pid] = $existingReviews[$pid]['rating'] ?? 5;
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Feedback - FoodMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>

<main class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Leave Feedback</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?php echo htmlspecialchars($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="feedback.php?order_id=<?php echo intval($order_id); ?>">
                        <?php foreach ($orderProducts as $prod): ?>
                            <div class="mb-3">
                                <label class="form-label">Rating for <?php echo htmlspecialchars($prod['product_name']); ?><?php if (!empty($prod['shop_name'])): ?> <span class="text-muted">(<?php echo htmlspecialchars($prod['shop_name']); ?>)</span><?php endif; ?></label>
                                <div class="star-rating">
                                    <?php $pid = intval($prod['product_id']); ?>
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star_<?php echo $pid; ?>_<?php echo $i; ?>" name="rating[<?php echo $pid; ?>]" value="<?php echo $i; ?>" <?php echo ($rating[$pid] == $i ? 'checked' : ''); ?> required>
                                        <label for="star_<?php echo $pid; ?>_<?php echo $i; ?>">&#9733;</label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <style>
                            .card-header {
                                background: linear-gradient(135deg, #99c0d6 0%, #a5c5d0 100%) !important;
                                color: #fff !important;
                            }
                            .star-rating { direction: rtl; display: inline-block; }
                            .star-rating input { display: none; }
                            .star-rating label { font-size: 1.5rem; color: #ccc; cursor: pointer; }
                            .star-rating input:checked ~ label,
                            .star-rating label:hover,
                            .star-rating label:hover ~ label { color: #ff6b81; }
                        </style>
                        <div class="d-flex justify-content-between">
                            <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
                            <button type="submit" class="btn btn-primary" style="background-color: #2b2a49;;">Submit Feedback</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
