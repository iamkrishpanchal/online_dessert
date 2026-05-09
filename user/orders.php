<?php
session_start();
include 'connection.php';

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    $redirectUrl = urlencode($_SERVER['REQUEST_URI']);
    header('Location: login.php?redirect=' . $redirectUrl);
    exit;
}

$user_id = $_SESSION['user_id'];

// Ensure reviews table exists so we can safely check for existing feedback
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

// check if user_id column exists, if not add it
$user_id_check = mysqli_query($conn, "SHOW COLUMNS FROM tbl_orders LIKE 'user_id'");
if ($user_id_check && mysqli_num_rows($user_id_check) === 0) {
    // add user_id column if missing
    mysqli_query($conn, "ALTER TABLE tbl_orders ADD COLUMN user_id INT DEFAULT 0 AFTER order_number");
    // try to add foreign key
    mysqli_query($conn, "ALTER TABLE tbl_orders ADD FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE CASCADE");
}

// ensure FK exists by checking constraints
$fk_check = mysqli_query($conn, "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME='tbl_orders' AND COLUMN_NAME='user_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
if ($fk_check && mysqli_num_rows($fk_check) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_orders ADD FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE CASCADE");
}

// make sure Razorpay columns exist so SELECTs don't break
$cols = ['razorpay_order_id','razorpay_payment_id','razorpay_signature','rider_id','delivery_status'];
foreach ($cols as $c) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM tbl_orders LIKE '$c'");
    if ($res && mysqli_num_rows($res) === 0) {
        // add minimal VARCHAR; existence check already ensures permission
        mysqli_query($conn, "ALTER TABLE tbl_orders ADD COLUMN $c VARCHAR(255) DEFAULT NULL");
    }
}

// if a COD checkout just happened, the invoice data may be stashed in session
$session_invoice = null;
if (!empty($_SESSION['last_invoice']) && is_array($_SESSION['last_invoice'])) {
    $session_invoice = $_SESSION['last_invoice'];
    // clear so it doesn't persist indefinitely
    unset($_SESSION['last_invoice']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - FoodMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
      /* My Orders table enhancements */
      .card-header.bg-primary {
        background: linear-gradient(90deg, #1b3a73, #0f2461);
        border-radius: 8px 8px 0 0;
        box-shadow: 0 4px 10px rgba(0,0,0,0.18);
      }
      .card-header.bg-primary h4 {
        color: #f5f7fb;
      }
      .table thead th {
        background: #fff3cd;
        color: #6a4f00;
        border-top: 2px solid #ffcc33;
        font-weight: 700;
      }
      .table tbody tr {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
      }
      .table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        background-color: #fffefa;
      }
      .badge.bg-info { background: #cfe4ff; color: #0d6efd; font-weight: 600; }
      .badge.bg-warning { background: #ffe8cb; color: #b35f00; font-weight: 600; }
      .btn-outline-secondary, .btn-outline-danger, .btn-outline-primary, .btn-outline-success {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }
      .btn-outline-secondary:hover, .btn-outline-danger:hover, .btn-outline-primary:hover, .btn-outline-success:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.12);
      }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<main class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">My Orders</h4>
                </div>
                <div class="card-body">
                    <?php
                    if (isset($_GET['msg'])) {
                        if ($_GET['msg'] === 'ordered') {
                            // Show payment-specific message for COD
                            if (!empty($_SESSION['order_payment_method']) && $_SESSION['order_payment_method'] === 'COD') {
                                echo '<div class="alert alert-success" role="alert">
                                        <h4 class="alert-heading">✓ Order Confirmed!</h4>
                                        <p>Your order has been placed successfully. Payment will be collected on delivery.</p>
                                        </div>';
                                unset($_SESSION['order_payment_method']);
                            } else {
                                echo '<div class="alert alert-success">Your order has been placed successfully!</div>';
                            }
                        } elseif ($_GET['msg'] === 'paid') {
                            // Show message for online payment
                            echo '<div class="alert alert-success" role="alert">
                                    <h4 class="alert-heading">✓ Payment Received!</h4>
                                    <p><strong>Your payment will be refunded within 5-10 minutes if you cancel the order.</strong></p>
                                    </div>';
                            unset($_SESSION['order_payment_method']);
                        } elseif ($_GET['msg'] === 'feedback_saved') {
                            echo '<div class="alert alert-success">Thank you for your feedback!</div>';
                        } elseif ($_GET['msg'] === 'feedback_not_ready') {
                            echo '<div class="alert alert-warning">Feedback cannot be added for this order at this time.</div>';
                        } elseif ($_GET['msg'] === 'cancelled') {
                            echo '<div class="alert alert-info">Order has been cancelled successfully.</div>';
                        }
                    }
                    
                    // fetch user's orders for TODAY ONLY (include whether a review already exists)
                    $order_sql = "SELECT o.order_id, o.order_number, o.total_amount, o.order_status, o.payment_status, o.delivery_status, o.payment_method, o.rider_id, o.created_at,
                                  EXISTS(SELECT 1 FROM tbl_reviews r WHERE r.order_id = o.order_id AND r.user_id = ?) AS has_review
                                  FROM tbl_orders o WHERE o.user_id = ? AND DATE(o.created_at) = CURDATE() ORDER BY o.created_at DESC";
                    $order_stmt = mysqli_prepare($conn, $order_sql);
                    if ($order_stmt) {
                        mysqli_stmt_bind_param($order_stmt, 'ii', $user_id, $user_id);
                        mysqli_stmt_execute($order_stmt);
                        $order_res = mysqli_stmt_get_result($order_stmt);
                    }
                    
                    if ($order_res && mysqli_num_rows($order_res) > 0) {
                        $order_count = mysqli_num_rows($order_res);
                        echo '<p class="text-muted mb-3">Total Orders: <strong>' . $order_count . '</strong></p>';
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-hover table-bordered">';
                        echo '<thead class="table-light"><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th>Delivery</th><th>Payment</th><th>Feedback</th><th>Action</th></tr></thead><tbody>';
                        
                        // Reset result pointer and fetch all orders
                        while ($o = mysqli_fetch_assoc($order_res)) {
                            $orderStatus = strtolower($o['order_status']);
                            $isCancelled = $orderStatus === 'cancelled';
                            $isCompleted = $orderStatus === 'completed';
                            $hasReview = !empty($o['has_review']);
                            $feedbackBtn = '';
                            if ($isCompleted && !$isCancelled) {
                                if ($hasReview) {
                                    $feedbackBtn = '<a href="feedback.php?order_id=' . intval($o['order_id']) . '" class="btn btn-sm btn-outline-success">Edit Feedback</a>';
                                } else {
                                    $feedbackBtn = '<a href="feedback.php?order_id=' . intval($o['order_id']) . '" class="btn btn-sm btn-outline-primary">Leave Feedback</a>';
                                }
                            } else {
                                $feedbackBtn = '<span class="text-muted small">Available when completed</span>';
                            }

                            echo '<tr>';
                            echo '<td><strong>' . htmlspecialchars($o['order_number']) . '</strong></td>';
                            echo '<td>' . htmlspecialchars($o['created_at']) . '</td>';
                            echo '<td><strong>₹' . number_format(floatval($o['total_amount']),2) . '</strong></td>';
                            echo '<td><span class="badge bg-info">' . htmlspecialchars($o['order_status']) . '</span></td>';
                            echo '<td>' . htmlspecialchars($o['delivery_status'] ?? 'Not Set') . '</td>';
                            echo '<td><span class="badge bg-warning">' . htmlspecialchars($o['payment_status']) . '</span></td>';
                            echo '<td>' . $feedbackBtn . '</td>';
                            echo '<td>';
                            echo '<a href="invoice.php?order_id=' . intval($o['order_id']) . '" class="btn btn-sm btn-outline-secondary">Bill</a>';
                            // Add cancel button only if order is not already cancelled and not completed
                            if (!$isCancelled && strtolower($o['order_status']) !== 'completed') {
                                echo ' <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmCancel(' . intval($o['order_id']) . ', \'' . htmlspecialchars($o['order_number']) . '\')">Cancel</button>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-info">You haven\'t placed any orders yet.</div>';
                        echo '<div class="d-grid gap-2"><a href="index.php" class="btn btn-primary">Start Shopping</a></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmCancel(orderId, orderNumber) {
    if (confirm('Are you sure you want to cancel order ' + orderNumber + '? This action cannot be undone.')) {
        window.location.href = 'cancel_order.php?order_id=' + orderId;
    }
}
</script>
</body>
</html>
