<?php

session_start();
include 'connection.php';

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = intval($_GET['order_id']);

// Fetch the order to verify it belongs to the user
$order_sql = "SELECT o.order_id, o.order_number, o.order_status, o.payment_method, o.total_amount, o.user_id 
              FROM tbl_orders o 
              WHERE o.order_id = ? AND o.user_id = ?";
$order_stmt = mysqli_prepare($conn, $order_sql);
if ($order_stmt) {
    mysqli_stmt_bind_param($order_stmt, 'ii', $order_id, $user_id);
    mysqli_stmt_execute($order_stmt);
    $order_result = mysqli_stmt_get_result($order_stmt);
    $order = mysqli_fetch_assoc($order_result);
    mysqli_stmt_close($order_stmt);
}

// If order doesn't exist or doesn't belong to user
if (empty($order)) {
    header('Location: orders.php');
    exit;
}

// Check if order can be cancelled (not already cancelled or completed)
$current_status = strtolower($order['order_status']);
if ($current_status === 'cancelled' || $current_status === 'completed') {
    // Cannot cancel already cancelled or completed orders
    header('Location: orders.php?msg=cannot_cancel');
    exit;
}

// Update order status to Cancelled
$update_sql = "UPDATE tbl_orders SET order_status = 'Cancelled' WHERE order_id = ?";
$update_stmt = mysqli_prepare($conn, $update_sql);
if ($update_stmt) {
    mysqli_stmt_bind_param($update_stmt, 'i', $order_id);
    if (mysqli_stmt_execute($update_stmt)) {
        mysqli_stmt_close($update_stmt);
        
        // Create notification for order cancellation
        $auto_dismiss_at = date('Y-m-d H:i:s', time() + 300); // 5 minutes from now
        $check_col = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'auto_dismiss_at'");
        $has_auto_dismiss = $check_col && mysqli_num_rows($check_col) > 0;
        
        if ($has_auto_dismiss) {
            $notif_sql = "INSERT INTO tbl_notifications (user_id, order_id, title, message, auto_dismiss_at) VALUES (?, ?, ?, ?, ?)";
        } else {
            $notif_sql = "INSERT INTO tbl_notifications (user_id, order_id, title, message) VALUES (?, ?, ?, ?)";
        }
        $notif_stmt = mysqli_prepare($conn, $notif_sql);
        if ($notif_stmt) {
            $notif_title = '✗ Order Cancelled';
            $notif_message = "Your order #{$order['order_number']} has been cancelled.";
            if ($order['payment_method'] === 'Razorpay' || $order['payment_method'] === 'ONLINE') {
                $notif_message .= " Your refund of ₹" . number_format($order['total_amount'], 2) . " will be processed within 5-10 minutes.";
            }
            $notif_message .= " This notification will disappear in 5 minutes.";
            
            if ($has_auto_dismiss) {
                mysqli_stmt_bind_param($notif_stmt, 'iisss', $user_id, $order_id, $notif_title, $notif_message, $auto_dismiss_at);
            } else {
                mysqli_stmt_bind_param($notif_stmt, 'iiss', $user_id, $order_id, $notif_title, $notif_message);
            }
            mysqli_stmt_execute($notif_stmt);
            mysqli_stmt_close($notif_stmt);
        }
        
        // Notify vendor about order cancellation (if vendor_id column exists)
        $vendor_check = mysqli_query($conn, "SELECT vendor_id FROM tbl_orders WHERE order_id = $order_id LIMIT 1");
        if ($vendor_check) {
            $vendor_data = mysqli_fetch_assoc($vendor_check);
            if (!empty($vendor_data['vendor_id'])) {
                $vendor_id = intval($vendor_data['vendor_id']);
                $col_check = mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'vendor_id'");
                if ($col_check && mysqli_num_rows($col_check) > 0) {
                    $check_col_vendor = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'auto_dismiss_at'");
                    $has_auto_dismiss_vendor = $check_col_vendor && mysqli_num_rows($check_col_vendor) > 0;
                    
                    if ($has_auto_dismiss_vendor) {
                        $vendor_notif_sql = "INSERT INTO tbl_notifications (vendor_id, order_id, title, message, auto_dismiss_at) VALUES (?, ?, ?, ?, ?)";
                    } else {
                        $vendor_notif_sql = "INSERT INTO tbl_notifications (vendor_id, order_id, title, message) VALUES (?, ?, ?, ?)";
                    }
                    $vendor_notif_stmt = mysqli_prepare($conn, $vendor_notif_sql);
                    if ($vendor_notif_stmt) {
                        $vendor_notif_title = 'Order Cancelled';
                        $vendor_notif_message = "Order #{$order['order_number']} has been cancelled by the customer.";
                        if ($has_auto_dismiss_vendor) {
                            mysqli_stmt_bind_param($vendor_notif_stmt, 'iisss', $vendor_id, $order_id, $vendor_notif_title, $vendor_notif_message, $auto_dismiss_at);
                        } else {
                            mysqli_stmt_bind_param($vendor_notif_stmt, 'iiss', $vendor_id, $order_id, $vendor_notif_title, $vendor_notif_message);
                        }
                        mysqli_stmt_execute($vendor_notif_stmt);
                        mysqli_stmt_close($vendor_notif_stmt);
                    }
                }
            }
        }
        
        header('Location: orders.php?msg=cancelled');
        exit;
    } else {
        // Error updating order
        header('Location: orders.php');
        exit;
    }
} else {
    // Error preparing statement
    header('Location: orders.php');
    exit;
}
?>
