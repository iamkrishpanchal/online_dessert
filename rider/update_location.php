<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

$rider_id = $_SESSION['rider_id'] ?? 0;
if (!$rider_id) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
if ($lat === null || $lng === null) {
    echo json_encode(['success' => false, 'message' => 'Missing coordinates']);
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE tbl_riders SET latitude = ?, longitude = ? WHERE rider_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

mysqli_stmt_bind_param($stmt, 'ddi', $lat, $lng, $rider_id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Failed to store location']);
    exit;
}

echo json_encode(['success' => true]);
