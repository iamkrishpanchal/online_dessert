<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

$admin_id = $_SESSION['admin_id'] ?? null;
$vendor_id = $_SESSION['vendor_id'] ?? null;
if (empty($admin_id) && empty($vendor_id)) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

// Ensure tbl_order_tracking table exists
$trackingTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_order_tracking'");
if (!$trackingTableCheck || mysqli_num_rows($trackingTableCheck) === 0) {
    $createTracking = "CREATE TABLE IF NOT EXISTS tbl_order_tracking (
        tracking_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        rider_id INT,
        status VARCHAR(50),
        message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX order_idx (order_id),
        FOREIGN KEY (order_id) REFERENCES tbl_orders(order_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $createTracking);
}

$order_id = intval($_POST['order_id'] ?? 0);
$rider_id = intval($_POST['rider_id'] ?? 0);

if ($order_id <= 0 || $rider_id <= 0) {
    echo json_encode(['success'=>false,'message'=>'Invalid parameters']);
    exit;
}

// If a vendor is assigning, ensure the order belongs to that vendor.
// Admin users can assign any order.
if (!empty($vendor_id) && empty($admin_id)) {
    $orderCheck = mysqli_prepare($conn, "SELECT order_id, rider_id, order_status, delivery_status FROM tbl_orders WHERE order_id = ? AND vendor_id = ?");
    mysqli_stmt_bind_param($orderCheck, 'ii', $order_id, $vendor_id);
} else {
    // Admin can assign any order; ensure the order exists.
    $orderCheck = mysqli_prepare($conn, "SELECT order_id, rider_id, order_status, delivery_status FROM tbl_orders WHERE order_id = ?");
    mysqli_stmt_bind_param($orderCheck, 'i', $order_id);
}
mysqli_stmt_execute($orderCheck);
mysqli_stmt_bind_result($orderCheck, $found_order_id, $existing_rider_id, $order_status, $delivery_status);
if (!mysqli_stmt_fetch($orderCheck) || $found_order_id <= 0) {
    echo json_encode(['success'=>false,'message'=>'Order not found or access denied']);
    exit;
}
mysqli_stmt_close($orderCheck);

// Prevent assigning riders to orders that are already completed, delivered, or cancelled.
if (in_array(strtolower($order_status), ['completed', 'cancelled']) || strtolower($delivery_status) === 'delivered') {
    echo json_encode(['success'=>false,'message'=>'Cannot assign a rider to an order that has already been delivered or completed.']);
    exit;
}

// Prevent reassigning an already assigned order.
if (!empty($existing_rider_id)) {
    echo json_encode(['success'=>false,'message'=>'A rider is already assigned to this order and cannot be changed.']);
    exit;
}

// verify rider exists and is active
$chk = mysqli_prepare($conn, "SELECT rider_id FROM tbl_riders WHERE rider_id=? AND status='active'");
mysqli_stmt_bind_param($chk,'i',$rider_id);
mysqli_stmt_execute($chk);
mysqli_stmt_store_result($chk);
if (mysqli_stmt_num_rows($chk) === 0) {
    echo json_encode(['success'=>false,'message'=>'Rider not found or inactive']);
    exit;
}
mysqli_stmt_close($chk);

// prevent assigning a rider who is currently handling another active delivery
$busyCheck = mysqli_prepare($conn, "SELECT 1 FROM tbl_orders WHERE rider_id = ? AND delivery_status IN ('assigned','picked_up','out_for_delivery','payment_collected') LIMIT 1");
mysqli_stmt_bind_param($busyCheck, 'i', $rider_id);
mysqli_stmt_execute($busyCheck);
mysqli_stmt_store_result($busyCheck);
if (mysqli_stmt_num_rows($busyCheck) > 0) {
    echo json_encode(['success'=>false,'message'=>'This rider is currently on another delivery and cannot be assigned until it is completed.']);
    exit;
}
mysqli_stmt_close($busyCheck);

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

// Create a notification for the rider so they can see the assigned order
$orderInfo = mysqli_prepare($conn, "SELECT order_number, customer_name, delivery_address, delivery_city, delivery_pincode FROM tbl_orders WHERE order_id = ?");
if ($orderInfo) {
    mysqli_stmt_bind_param($orderInfo, 'i', $order_id);
    mysqli_stmt_execute($orderInfo);
    $orderRes = mysqli_stmt_get_result($orderInfo);
    $orderRow = mysqli_fetch_assoc($orderRes);
    mysqli_stmt_close($orderInfo);

    if ($orderRow) {
        $title = 'New Delivery Assigned';
        $message = "Order #{$orderRow['order_number']} has been assigned to you. " .
                   "Customer: {$orderRow['customer_name']}. " .
                   "Address: {$orderRow['delivery_address']} {$orderRow['delivery_city']} {$orderRow['delivery_pincode']}";

        $notif = mysqli_prepare($conn, "INSERT INTO tbl_notifications (user_id,order_id,title,message) VALUES (?,?,?,?)");
        if ($notif) {
            mysqli_stmt_bind_param($notif, 'iiss', $rider_id, $order_id, $title, $message);
            mysqli_stmt_execute($notif);
            mysqli_stmt_close($notif);
        }
    }
}

echo json_encode(['success'=>true,'message'=>'Rider assigned']);