<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$rider_id = intval($_POST['rider_id'] ?? 0);
$new_status = trim($_POST['new_status'] ?? '');

if ($rider_id <= 0 || !in_array($new_status, ['active', 'inactive'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid rider or status']);
    exit;
}

$check = mysqli_prepare($conn, 'SELECT rider_id FROM tbl_riders WHERE rider_id = ?');
if (!$check) {
    echo json_encode(['success' => false, 'message' => 'Unable to validate rider']);
    exit;
}
mysqli_stmt_bind_param($check, 'i', $rider_id);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
if (mysqli_stmt_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Rider not found']);
    exit;
}
mysqli_stmt_close($check);

$upd = mysqli_prepare($conn, 'UPDATE tbl_riders SET status = ? WHERE rider_id = ?');
if (!$upd) {
    echo json_encode(['success' => false, 'message' => 'Unable to update rider status']);
    exit;
}
mysqli_stmt_bind_param($upd, 'si', $new_status, $rider_id);
if (!mysqli_stmt_execute($upd)) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}
mysqli_stmt_close($upd);

echo json_encode(['success' => true, 'message' => 'Rider status changed to ' . ucfirst($new_status) . '.']);
