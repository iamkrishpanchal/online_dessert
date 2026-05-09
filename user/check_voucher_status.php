<?php
session_start();
include 'connection.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'not_logged_in']);
    exit;
}

$user_id = $_SESSION['user_id'];

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

// Check if user has claimed the voucher
$check_sql = "SELECT claim_id, status FROM tbl_voucher_claims WHERE user_id = ? AND voucher_code = '25PERCENT'";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, 'i', $user_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

header('Content-Type: application/json');

if (mysqli_num_rows($check_result) > 0) {
    $claim = mysqli_fetch_assoc($check_result);
    echo json_encode([
        'status' => 'claimed',
        'claim_id' => $claim['claim_id'],
        'voucher_status' => $claim['status']
    ]);
} else {
    echo json_encode(['status' => 'not_claimed']);
}
?>
