<?php
include 'session.php';
include 'connection.php';

$vendor_id = $_SESSION['vendor_id'] ?? null;
if (!$vendor_id) {
    echo "<script>alert('Vendor ID not found. Please login again.'); window.location.href='login.php';</script>";
    exit;
}

if (isset($_GET['order_id']) && isset($_GET['status'])) {
    $order_id = (int)$_GET['order_id'];
    $status = $_GET['status'];
    
    // Validate status
    $allowed_statuses = array('Pending', 'Confirmed', 'Dispatched', 'Completed', 'Cancelled', 'Rejected');
    
    if (in_array($status, $allowed_statuses)) {
        // Verify vendor owns this order directly
        $verify_sql = "SELECT order_id FROM tbl_orders WHERE order_id = ? AND vendor_id = ?";
        $verify_stmt = mysqli_prepare($conn, $verify_sql);
        if ($verify_stmt) {
            mysqli_stmt_bind_param($verify_stmt, 'ii', $order_id, $vendor_id);
            mysqli_stmt_execute($verify_stmt);
            $verify_result = mysqli_stmt_get_result($verify_stmt);
            mysqli_stmt_close($verify_stmt);
            if (mysqli_num_rows($verify_result) > 0) {
                // Update order status with prepared statement
                $update_sql = "UPDATE tbl_orders SET order_status = ? WHERE order_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                
                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, 'si', $status, $order_id);
                    if (mysqli_stmt_execute($update_stmt)) {
                        mysqli_stmt_close($update_stmt);

                        // send notification depending on status
                        $notifTitle = '';
                        $notifMsg = '';
                        $notifType = strtolower($status);
                        $auto_dismiss_at = null;
                        
                        // Set auto_dismiss_at for completion and cancellation (5 minutes from now)
                        if (in_array($status, ['Completed', 'Cancelled'])) {
                            $auto_dismiss_at = date('Y-m-d H:i:s', time() + 300);
                        }
                        
                        switch ($status) {
                            case 'Confirmed':
                                $notifTitle = 'Your order has been confirmed.';
                                $notifMsg   = 'Dear customer, your order has moved to confirmed status.';
                                // also mark payment paid if not already
                                $payUpd = mysqli_prepare($conn, "UPDATE tbl_orders SET payment_status='Paid' WHERE order_id=? AND payment_status<>'Paid'");
                                if ($payUpd) {
                                    mysqli_stmt_bind_param($payUpd, 'i', $order_id);
                                    mysqli_stmt_execute($payUpd);
                                    mysqli_stmt_close($payUpd);
                                }
                                break;
                            case 'Dispatched':
                                $notifTitle = 'Your order has been dispatched.';
                                $notifMsg   = 'Good news! Your order is on the way.';
                                break;
                            case 'Completed':
                                $notifTitle = '✓ Your order has been delivered successfully!';
                                $notifMsg   = 'Thank you for ordering. The order is completed. This notification will disappear in 5 minutes.';
                                break;
                            case 'Cancelled':
                                $notifTitle = '✗ Your order has been cancelled.';
                                $notifMsg   = 'We regret to inform you that your order has been cancelled. This notification will disappear in 5 minutes.';
                                break;
                        }
                        if ($notifTitle !== '') {
                            // Check if auto_dismiss_at column exists
                            $check_col = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'auto_dismiss_at'");
                            $has_auto_dismiss = $check_col && mysqli_num_rows($check_col) > 0;
                            
                            // Fetch user_id for the order
                            $user_id = null;
                            $user_stmt = mysqli_prepare($conn, "SELECT user_id FROM tbl_orders WHERE order_id=?");
                            if ($user_stmt) {
                                mysqli_stmt_bind_param($user_stmt, 'i', $order_id);
                                mysqli_stmt_execute($user_stmt);
                                mysqli_stmt_bind_result($user_stmt, $user_id);
                                mysqli_stmt_fetch($user_stmt);
                                mysqli_stmt_close($user_stmt);
                            }
                            
                            if ($user_id !== null) {
                                if ($has_auto_dismiss && $auto_dismiss_at) {
                                    $ins = mysqli_prepare($conn, "INSERT INTO tbl_notifications (user_id, order_id, title, message, status, auto_dismiss_at) VALUES (?, ?, ?, ?, 'unread', ?)");
                                    if ($ins) {
                                        mysqli_stmt_bind_param($ins, 'iisss', $user_id, $order_id, $notifTitle, $notifMsg, $auto_dismiss_at);
                                        mysqli_stmt_execute($ins);
                                        mysqli_stmt_close($ins);
                                    }
                                } else {
                                    $ins = mysqli_prepare($conn, "INSERT INTO tbl_notifications (user_id, order_id, title, message, status) VALUES (?, ?, ?, ?, 'unread')");
                                    if ($ins) {
                                        mysqli_stmt_bind_param($ins, 'iiss', $user_id, $order_id, $notifTitle, $notifMsg);
                                        mysqli_stmt_execute($ins);
                                        mysqli_stmt_close($ins);
                                    }
                                }
                            }
                        }

                        echo "<script>alert('Order status updated successfully!');</script>";
                        echo "<script>window.history.back();</script>";
                    } else {
                        echo "<script>alert('Error updating order.');</script>";
                        echo "<script>window.history.back();</script>";
                    }
                } else {
                    echo "<script>alert('Database error.');</script>";
                    echo "<script>window.history.back();</script>";
                }
            } else {
                echo "<script>alert('Order not found or unauthorized access.');</script>";
                echo "<script>window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Database error.');</script>";
            echo "<script>window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Invalid status!');</script>";
        echo "<script>window.history.back();</script>";
    }
} else {
    echo "<script>alert('Invalid request!');</script>";
    echo "<script>window.history.back();</script>";
}

mysqli_close($conn);
?>
