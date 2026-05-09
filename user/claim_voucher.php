<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to claim voucher']);
    exit;
}

$user_id = $_SESSION['user_id'];
$voucher_code = '25PERCENT';

// Ensure table exists
$create_table_sql = "CREATE TABLE IF NOT EXISTS tbl_voucher_claims (
    claim_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    voucher_code VARCHAR(100) NOT NULL DEFAULT '25PERCENT',
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_in_order_id INT DEFAULT NULL,
    status ENUM('active', 'used') DEFAULT 'active',
    UNIQUE KEY unique_user_voucher (user_id, voucher_code),
    FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE CASCADE
)";
@mysqli_query($conn, $create_table_sql);

// Prevent voucher if the user has already placed an order
$orderCountStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM tbl_orders WHERE user_id = ?");
if ($orderCountStmt) {
    mysqli_stmt_bind_param($orderCountStmt, 'i', $user_id);
    mysqli_stmt_execute($orderCountStmt);
    mysqli_stmt_bind_result($orderCountStmt, $orderCount);
    mysqli_stmt_fetch($orderCountStmt);
    mysqli_stmt_close($orderCountStmt);
    if ($orderCount > 0) {
        echo json_encode(['success' => false, 'message' => 'Voucher is only valid for your first order.']);
        exit;
    }
}

// Check if user has already claimed this voucher
$check_sql = "SELECT claim_id, status FROM tbl_voucher_claims WHERE user_id = ? AND voucher_code = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, 'is', $user_id, $voucher_code);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
    $existing = mysqli_fetch_assoc($check_result);
    echo json_encode(['success' => false, 'message' => 'You already claimed this voucher. You can use it only once!']);
} else {
    // Create new voucher claim
    $insert_sql = "INSERT INTO tbl_voucher_claims (user_id, voucher_code, status) VALUES (?, ?, 'active')";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, 'is', $user_id, $voucher_code);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        // Store voucher in session for checkout
        $_SESSION['voucher_claimed'] = [
            'code' => $voucher_code,
            'discount' => 25,
            'claimed_at' => date('Y-m-d H:i:s')
        ];
        echo json_encode([
            'success' => true,
            'message' => 'Voucher claimed successfully! 25% discount applied.',
            'voucher' => $_SESSION['voucher_claimed']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error claiming voucher: ' . mysqli_error($conn)]);
    }
}
?>

