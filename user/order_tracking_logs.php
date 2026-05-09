<?php
include 'connection.php';
header('Content-Type: application/json');
$order_id = intval($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    echo json_encode(['success'=>false]);
    exit;
}
$res = mysqli_prepare($conn, "SELECT * FROM tbl_order_tracking WHERE order_id=? ORDER BY created_at ASC");
mysqli_stmt_bind_param($res,'i',$order_id);
mysqli_stmt_execute($res);
$rows = mysqli_stmt_get_result($res);
$data = mysqli_fetch_all($rows, MYSQLI_ASSOC);

echo json_encode(['success'=>true,'logs'=>$data]);
