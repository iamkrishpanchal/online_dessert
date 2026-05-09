<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$admin_id = intval($_SESSION['admin_id']);
$adminCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'admin_id'");
if (!$adminCol || mysqli_num_rows($adminCol) === 0) {
    mysqli_query($conn, "ALTER TABLE tbl_notifications ADD COLUMN admin_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE tbl_notifications ADD INDEX notif_admin_idx (admin_id)");
}

$sql = "SELECT notification_id, order_id, title, message, status, created_at
        FROM tbl_notifications
        WHERE admin_id = ?
        ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['error' => mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}

header('Content-Type: application/json');
echo json_encode($notifications);
mysqli_stmt_close($stmt);
mysqli_close($conn);
