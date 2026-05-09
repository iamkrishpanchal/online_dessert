<?php
session_start();
include __DIR__ . '/user/connection.php';
header('Content-Type: application/json');

if (empty($_SESSION['vendor_id'])) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

$order_id = intval($_POST['order_id'] ?? 0);
$rider_id = intval($_POST['rider_id'] ?? 0);
$vendor_id = intval($_SESSION['vendor_id']);

if ($order_id <= 0 || $rider_id <= 0) {
    echo json_encode(['success'=>false,'message'=>'Invalid parameters']);
    exit;
}

// Check that the order belongs to this vendor and is not already assigned
$chk = mysqli_prepare($conn, "SELECT order_id FROM tbl_orders WHERE order_id=? AND vendor_id=? AND (rider_id IS NULL OR rider_id=0)");
mysqli_stmt_bind_param($chk,'ii',$order_id,$vendor_id);
mysqli_stmt_execute($chk);
mysqli_stmt_store_result($chk);
if (mysqli_stmt_num_rows($chk) === 0) {
    echo json_encode(['success'=>false,'message'=>'Order not found or already assigned']);
    exit;
}
mysqli_stmt_close($chk);

// verify rider exists and is active
$chk2 = mysqli_prepare($conn, "SELECT rider_id FROM tbl_riders WHERE rider_id=? AND status='active'");
mysqli_stmt_bind_param($chk2,'i',$rider_id);
mysqli_stmt_execute($chk2);
mysqli_stmt_store_result($chk2);
if (mysqli_stmt_num_rows($chk2) === 0) {
    echo json_encode(['success'=>false,'message'=>'Rider not found or inactive']);
    exit;
}
mysqli_stmt_close($chk2);

$upd = mysqli_prepare($conn, "UPDATE tbl_orders SET rider_id=?, delivery_status='assigned' WHERE order_id=?");
mysqli_stmt_bind_param($upd,'ii',$rider_id,$order_id);
if (!mysqli_stmt_execute($upd)) {
    echo json_encode(['success'=>false,'message'=>mysqli_error($conn)]);
    exit;
}
mysqli_stmt_close($upd);

// log tracking entry
$ins = mysqli_prepare($conn, "INSERT INTO tbl_order_tracking (order_id,rider_id,status,message) VALUES (?,?, 'assigned','Rider has been assigned to the order')");
mysqli_stmt_bind_param($ins,'ii',$order_id,$rider_id);
mysqli_stmt_execute($ins);
mysqli_stmt_close($ins);

echo json_encode(['success'=>true,'message'=>'Rider assigned']);
