<?php
session_start();
if (!isset($_SESSION['rider_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['notification_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$rider_id = intval($_SESSION['rider_id']);
$notification_id = intval($_POST['notification_id']);

include 'connection.php';

$sql = "UPDATE tbl_notifications SET status='read', updated_at=CURRENT_TIMESTAMP WHERE notification_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ii', $notification_id, $rider_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => mysqli_error($conn)]);
}

mysqli_close($conn);
