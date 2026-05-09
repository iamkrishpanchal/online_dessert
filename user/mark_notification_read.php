<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notif_id = intval($_POST['notification_id'] ?? 0);

if ($notif_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

$sql = "UPDATE tbl_notifications SET status = 'read' WHERE notification_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $notif_id, $user_id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

mysqli_close($conn);
