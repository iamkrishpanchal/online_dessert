<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$admin_id = intval($_SESSION['admin_id']);
$adminCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'admin_id'");
if (!$adminCol || mysqli_num_rows($adminCol) === 0) {
    mysqli_query($conn, "ALTER TABLE tbl_notifications ADD COLUMN admin_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE tbl_notifications ADD INDEX notif_admin_idx (admin_id)");
}

$sql = "SELECT COUNT(*) AS cnt FROM tbl_notifications WHERE status = 'unread' AND admin_id = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $admin_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $cnt);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['success' => true, 'unread' => intval($cnt)]);
mysqli_close($conn);
