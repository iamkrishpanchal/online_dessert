<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

$rider_id = intval($_SESSION['rider_id'] ?? 0);
if ($rider_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$status = trim($_POST['status'] ?? '');
if (!in_array($status, ['active', 'inactive'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

$stmt = mysqli_prepare($conn, 'UPDATE tbl_riders SET status = ? WHERE rider_id = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Unable to prepare request']);
    exit;
}
mysqli_stmt_bind_param($stmt, 'si', $status, $rider_id);
if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}
mysqli_stmt_close($stmt);

$_SESSION['rider_status'] = $status;

echo json_encode(['success' => true, 'message' => 'Status updated to ' . ucfirst($status)]);
