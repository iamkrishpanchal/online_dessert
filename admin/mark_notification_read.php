<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$notification_id = intval($_POST['id']);
$admin_id = intval($_SESSION['admin_id']);

$adminCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'admin_id'");
if (!$adminCol || mysqli_num_rows($adminCol) === 0) {
    mysqli_query($conn, "ALTER TABLE tbl_notifications ADD COLUMN admin_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE tbl_notifications ADD INDEX notif_admin_idx (admin_id)");
}

$sql = "UPDATE tbl_notifications SET status='read', updated_at=CURRENT_TIMESTAMP WHERE notification_id = ? AND admin_id = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['error' => mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'ii', $notification_id, $admin_id);
if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['error' => mysqli_error($conn)]);
} else {
    echo json_encode(['success' => true]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
