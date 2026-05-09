<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');
if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success'=>false]);
    exit;
}

$res = mysqli_query($conn, "SELECT rider_id,name,latitude,longitude,is_online,status FROM tbl_riders WHERE is_online=1 AND latitude IS NOT NULL AND longitude IS NOT NULL");
$data = [];
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $data[] = $r;
    }
}

echo json_encode(['success'=>true,'riders'=>$data]);
