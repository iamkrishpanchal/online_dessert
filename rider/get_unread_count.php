<?php
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['rider_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

include 'connection.php';

$rider_id = intval($_SESSION['rider_id']);

$sql = "SELECT COUNT(*) AS cnt FROM tbl_notifications WHERE status = 'unread' AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $rider_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $cnt);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['success' => true, 'unread' => intval($cnt)]);
mysqli_close($conn);
