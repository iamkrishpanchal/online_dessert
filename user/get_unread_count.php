<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) && empty($_SESSION['vendor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Check if columns exist for backward compatibility
$check_dismissed = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'is_dismissed'");
$has_dismissed_col = $check_dismissed && mysqli_num_rows($check_dismissed) > 0;

$check_auto_dismiss = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'auto_dismiss_at'");
$has_auto_dismiss_col = $check_auto_dismiss && mysqli_num_rows($check_auto_dismiss) > 0;

// Build WHERE clause
$where_dismissed = $has_dismissed_col ? "AND n.is_dismissed = 0" : "";
$where_auto_dismiss = $has_auto_dismiss_col ? "AND (n.auto_dismiss_at IS NULL OR n.auto_dismiss_at > NOW())" : "";

// determine which id to use
if (!empty($_SESSION['vendor_id'])) {
    $vendor_id = $_SESSION['vendor_id'];
    $sql = "SELECT COUNT(*) AS cnt
            FROM tbl_notifications n
            WHERE n.vendor_id = ?
              AND n.status = 'unread'
              AND n.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
              $where_dismissed
              $where_auto_dismiss";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $vendor_id);
} else {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT COUNT(*) AS cnt
            FROM tbl_notifications n
            WHERE n.user_id = ?
              AND n.status = 'unread'
              AND n.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
              $where_dismissed
              $where_auto_dismiss";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
} 

mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $cnt);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['success' => true, 'unread' => intval($cnt ?? 0)]);
mysqli_close($conn);
