<?php
/**
 * Script to create a notification entry. Intended to be called after order/payment success.
 * Accepts POST parameters: order_id (optional), title, message, user_id (optional).
 * If user_id is not supplied, the logged-in user is used.
 */
session_start();
include 'connection.php';
header('Content-Type: application/json');

// determine target: prefer explicit POST vendor_id/user_id
$user_id = intval($_POST['user_id'] ?? 0);
$vendor_id = intval($_POST['vendor_id'] ?? 0);
if (!$user_id && !isset($_SESSION['user_id'])) {
    // if no user id provided, and session exists use it
    $user_id = $_SESSION['user_id'] ?? 0;
}

$order_id = intval($_POST['order_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');

// Check if this notification should auto-dismiss (completed/cancelled orders)
// Auto-dismiss after 5 minutes for completion and cancellation notifications
$notification_type = strtolower($_POST['notification_type'] ?? '');
$auto_dismiss_at = null;

if ($has_auto_dismiss_col = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'auto_dismiss_at'")) {
    if (mysqli_num_rows($has_auto_dismiss_col) > 0) {
        // If this is a completion or cancellation notification, auto-dismiss after 5 minutes
        if (in_array($notification_type, ['completed', 'cancelled'])) {
            $auto_dismiss_at = date('Y-m-d H:i:s', time() + 300); // 5 minutes from now
        }
    }
}

if (!$user_id && !$vendor_id) {
    echo json_encode(['success' => false, 'message' => 'No recipient specified']);
    exit;
}

if ($title === '' || $message === '') {
    echo json_encode(['success' => false, 'message' => 'Title and message are required']);
    exit;
}

// build query depending on whether it's for user or vendor
if ($vendor_id) {
    $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'vendor_id'");
    if ($colCheck && mysqli_num_rows($colCheck) > 0) {
        if ($auto_dismiss_at) {
            $sql = "INSERT INTO tbl_notifications (vendor_id, order_id, title, message, status, auto_dismiss_at) VALUES (?, ?, ?, ?, 'unread', ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'iisss', $vendor_id, $order_id, $title, $message, $auto_dismiss_at);
        } else {
            $sql = "INSERT INTO tbl_notifications (vendor_id, order_id, title, message, status) VALUES (?, ?, ?, ?, 'unread')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'iiss', $vendor_id, $order_id, $title, $message);
        }
    } else {
        // fall back to user column if vendor_id isn't available
        if ($auto_dismiss_at) {
            $sql = "INSERT INTO tbl_notifications (user_id, order_id, title, message, status, auto_dismiss_at) VALUES (?, ?, ?, ?, 'unread', ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'iisss', $user_id, $order_id, $title, $message, $auto_dismiss_at);
        } else {
            $sql = "INSERT INTO tbl_notifications (user_id, order_id, title, message, status) VALUES (?, ?, ?, ?, 'unread')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'iiss', $user_id, $order_id, $title, $message);
        }
    }
} else {
    if ($auto_dismiss_at) {
        $sql = "INSERT INTO tbl_notifications (user_id, order_id, title, message, status, auto_dismiss_at) VALUES (?, ?, ?, ?, 'unread', ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'iisss', $user_id, $order_id, $title, $message, $auto_dismiss_at);
    } else {
        $sql = "INSERT INTO tbl_notifications (user_id, order_id, title, message, status) VALUES (?, ?, ?, ?, 'unread')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'iiss', $user_id, $order_id, $title, $message);
    }
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'notification_id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
