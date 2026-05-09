<?php
session_start();
if (!isset($_SESSION['vendor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}
$vendor_id = intval($_SESSION['vendor_id']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}
$notif_id = intval($_POST['id']);

require_once __DIR__ . '/../user/connection.php';
$conn = get_db_connection();

$sql = "UPDATE tbl_notifications SET status='read', updated_at=CURRENT_TIMESTAMP
        WHERE notification_id = ? AND vendor_id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ii', $notif_id, $vendor_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => mysqli_error($conn)]);
}

mysqli_close($conn);
