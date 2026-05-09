<?php
/**
 * UPDATE ORDER STATUS API - Called from Admin Dashboard
 * Handles status transitions and sends notifications to customers
 */
session_start();
include 'connection.php';

// Check if user is admin or vendor
if (empty($_SESSION['admin_id']) && empty($_SESSION['vendor_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$is_admin = !empty($_SESSION['admin_id']);
$vendor_id = $_SESSION['vendor_id'] ?? null;

$order_id = intval($_GET['order_id'] ?? 0);
$new_status = $_GET['status'] ?? null;
$redirect = $_GET['redirect'] ?? 'orders_dashboard.php';

// Validate status
$valid_statuses = ['Pending', 'Confirmed', 'Dispatched', 'Completed', 'Cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    $_SESSION['error'] = 'Invalid status';
    header("Location: $redirect");
    exit;
}

// Get order details with authorization check
if ($is_admin) {
    $sql_get = "SELECT o.order_id, o.order_number, o.user_id, o.order_status, 
                       o.vendor_id, u.user_name, u.email
                FROM tbl_orders o
                JOIN tbl_users u ON o.user_id = u.user_id
                WHERE o.order_id = ?";
    $params = [$order_id];
    $types = 'i';
} else {
    // Vendor can only update their own orders
    $sql_get = "SELECT o.order_id, o.order_number, o.user_id, o.order_status, 
                       o.vendor_id, u.user_name, u.email
                FROM tbl_orders o
                JOIN tbl_users u ON o.user_id = u.user_id
                WHERE o.order_id = ? AND o.vendor_id = ?";
    $params = [$order_id, $vendor_id];
    $types = 'ii';
}

$stmt = mysqli_prepare($conn, $sql_get);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error'] = 'Database query failed';
    header("Location: $redirect");
    exit;
}

if (!$order) {
    $_SESSION['error'] = 'Order not found';
    header("Location: $redirect");
    exit;
}

// Update order status
$sql_update = "UPDATE tbl_orders SET order_status = ? WHERE order_id = ?";
$stmt = mysqli_prepare($conn, $sql_update);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'si', $new_status, $order_id);
    if (mysqli_stmt_execute($stmt)) {
        // Send notification to customer
        $notification_messages = [
            'Confirmed' => "Your order #{$order['order_number']} has been confirmed!",
            'Dispatched' => "Your order #{$order['order_number']} is on the way!",
            'Completed' => "Your order #{$order['order_number']} has been delivered. Thank you!",
            'Cancelled' => "Your order #{$order['order_number']} has been cancelled.",
            'Pending' => "Your order #{$order['order_number']} is pending confirmation."
        ];

        $notification_title = ucfirst($new_status);
        $notification_message = $notification_messages[$new_status] ?? "Order status updated to $new_status";

        // Insert notification (using correct column names)
        $sql_notif = "INSERT INTO tbl_notifications (user_id, order_id, title, message, status, created_at) 
                     VALUES (?, ?, ?, ?, 'unread', NOW())";
        $stmt_notif = mysqli_prepare($conn, $sql_notif);
        if ($stmt_notif) {
            mysqli_stmt_bind_param($stmt_notif, 'iiss', $order['user_id'], $order_id, 
                                   $notification_title, $notification_message);
            mysqli_stmt_execute($stmt_notif);
            mysqli_stmt_close($stmt_notif);
        }

        // Send email notification (optional)
        if (!empty($order['email'])) {
            $subject = "Order Status Update: " . $notification_title;
            $email_body = "Dear {$order['user_name']},\n\n" . 
                         $notification_message . "\n\n" .
                         "Order Number: {$order['order_number']}\n" .
                         "New Status: " . ucfirst($new_status) . "\n\n" .
                         "Thank you for your order!\n";
            
            // Uncomment to enable email notifications
            // mail($order['email'], $subject, $email_body);
        }

        $_SESSION['success'] = 'Order status updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update order status';
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error'] = 'Database query failed';
}

header("Location: $redirect");
exit;
?>
