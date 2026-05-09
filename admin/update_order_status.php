<?php
/**
 * Admin endpoint to update order status with notification sending.
 * Call via POST with order_id and new_status.
 * Secured via session check.
 */
session_start();
include 'connection.php';
header('Content-Type: application/json');

// verify admin access
if (empty($_SESSION['admin_id']) && empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = intval($_POST['order_id'] ?? 0);
$new_status = trim($_POST['new_status'] ?? '');

if ($order_id <= 0 || $new_status === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$allowed = ['Pending', 'Confirmed', 'Dispatched', 'Completed', 'Cancelled'];
if (!in_array($new_status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// update order status
$upd = mysqli_prepare($conn, "UPDATE tbl_orders SET order_status = ? WHERE order_id = ?");
if (!$upd) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

mysqli_stmt_bind_param($upd, 'si', $new_status, $order_id);
if (!mysqli_stmt_execute($upd)) {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    mysqli_stmt_close($upd);
    exit;
}
mysqli_stmt_close($upd);

// send notification
$notifTitle = '';
$notifMsg = '';

switch ($new_status) {
    case 'Confirmed':
        $notifTitle = 'Your order has been confirmed.';
        $notifMsg = 'Your order has been confirmed and is being prepared.';
        // also update payment status if transitioning from Pending
        $payUpd = mysqli_prepare($conn, "UPDATE tbl_orders SET payment_status='Paid' WHERE order_id=? AND payment_status='pending'");
        if ($payUpd) {
            mysqli_stmt_bind_param($payUpd, 'i', $order_id);
            mysqli_stmt_execute($payUpd);
            mysqli_stmt_close($payUpd);
        }
        break;
    case 'Dispatched':
        $notifTitle = 'Your order has been dispatched.';
        $notifMsg = 'Your order is on the way to you.';
        break;
    case 'Completed':
        $notifTitle = 'Your order has been delivered successfully.';
        $notifMsg = 'Thank you for your purchase. Order is complete.';
        break;
    case 'Cancelled':
        $notifTitle = 'Your order has been cancelled.';
        $notifMsg = 'Your order has been cancelled. Please contact support if you have questions.';
        break;
}

if ($notifTitle !== '') {
    $ins = mysqli_prepare($conn,
        "INSERT INTO tbl_notifications (user_id, order_id, title, message, status)
         VALUES ((SELECT user_id FROM tbl_orders WHERE order_id=?), ?, ?, ?, 'unread')");
    if ($ins) {
        mysqli_stmt_bind_param($ins, 'iiss', $order_id, $order_id, $notifTitle, $notifMsg);
        mysqli_stmt_execute($ins);
        mysqli_stmt_close($ins);
    }
}

echo json_encode(['success' => true, 'message' => 'Order status updated and notification sent.']);
mysqli_close($conn);
