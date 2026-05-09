<?php
// returns JSON array of notifications for logged-in vendor
session_start();
if (!isset($_SESSION['vendor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}
$vendor_id = intval($_SESSION['vendor_id']);

require_once __DIR__ . '/../user/connection.php';
$conn = get_db_connection();

$sql = "SELECT notification_id, order_id, title, message, status, created_at
        FROM tbl_notifications
        WHERE vendor_id = ?
        ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['error' => mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($stmt, 'i', $vendor_id);
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
