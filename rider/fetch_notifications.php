<?php
session_start();
if (!isset($_SESSION['rider_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}
$rider_id = intval($_SESSION['rider_id']);

include 'connection.php';

$sql = "SELECT notification_id, order_id, title, message, status, created_at
        FROM tbl_notifications
        WHERE user_id = ?
        ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['error' => mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($stmt, 'i', $rider_id);
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
