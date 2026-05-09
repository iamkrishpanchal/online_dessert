<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if columns exist (for backward compatibility)
$check_dismissed = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'is_dismissed'");
$check_auto_dismiss = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'auto_dismiss_at'");

$has_dismissed_col = $check_dismissed && mysqli_num_rows($check_dismissed) > 0;
$has_auto_dismiss_col = $check_auto_dismiss && mysqli_num_rows($check_auto_dismiss) > 0;

// Build WHERE clause based on available columns
$where_dismissed = $has_dismissed_col ? "AND n.is_dismissed = 0" : "";
$where_auto_dismiss = $has_auto_dismiss_col ? "AND (n.auto_dismiss_at IS NULL OR n.auto_dismiss_at > NOW())" : "";

// Fetch active notifications (not dismissed, not auto-expired)
// Show all notifications from the last 24 hours regardless of order status
// Completed/Cancelled orders will auto-dismiss after 5 minutes
$sql = "SELECT n.notification_id, n.order_id, n.title, n.message, n.status, n.created_at,
               " . ($has_dismissed_col ? "n.is_dismissed" : "0 as is_dismissed") . ",
               " . ($has_auto_dismiss_col ? "n.auto_dismiss_at" : "NULL as auto_dismiss_at") . "
        FROM tbl_notifications n
        WHERE n.user_id = ?
          AND n.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
          $where_dismissed
          $where_auto_dismiss
        ORDER BY n.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $user_id);
if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => false, 'message' => 'Query execution failed: ' . mysqli_error($conn)]);
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$notifications = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Calculate time remaining for auto-dismiss
    if ($has_auto_dismiss_col && !empty($row['auto_dismiss_at'])) {
        $dismiss_time = strtotime($row['auto_dismiss_at']);
        $now = time();
        $row['time_remaining'] = max(0, $dismiss_time - $now);
    } else {
        $row['time_remaining'] = null;
    }
    
    $notifications[] = $row;
}
mysqli_stmt_close($stmt);

echo json_encode(['success' => true, 'notifications' => $notifications]);
mysqli_close($conn);
