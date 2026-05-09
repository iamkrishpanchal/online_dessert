<?php
session_start();
include 'connection.php';

$rider_id = $_SESSION['rider_id'] ?? 0;
if (!$rider_id) {
    header('Location: login.php');
    exit;
}

$allowed = ['accept','reject','start_delivery','collect_payment','delivered'];
$response = $_POST['response'] ?? '';
$order_id = intval($_POST['order_id'] ?? 0);

if (!$order_id || !in_array($response, $allowed, true)) {
    header('Location: assigned_orders.php');
    exit;
}

// Ensure the order is assigned to this rider and fetch current status + user/vendor info
$orderRow = null;
$check = mysqli_prepare($conn, "SELECT order_id, delivery_status, order_status, user_id, vendor_id, order_number, payment_method, payment_status FROM tbl_orders WHERE order_id = ? AND rider_id = ?");
if ($check) {
    mysqli_stmt_bind_param($check, 'ii', $order_id, $rider_id);
    mysqli_stmt_execute($check);
    $res = mysqli_stmt_get_result($check);
    $orderRow = mysqli_fetch_assoc($res);
    mysqli_stmt_close($check);
}

if (!$orderRow) {
    header('Location: assigned_orders.php');
    exit;
}

$currentDeliveryStatus = $orderRow['delivery_status'];
$msg = '';
$trackStatus = '';
$trackMsg = '';

function notifyRecipient($conn, $orderId, $title, $message, $userId = 0, $vendorId = 0) {
    // If vendor_id column exists and we have a vendorId, use it.
    $vendorCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'vendor_id'");
    $useVendor = $vendorId && $vendorCol && mysqli_num_rows($vendorCol) > 0;

    if ($useVendor) {
        $sql = "INSERT INTO tbl_notifications (vendor_id, order_id, title, message, status) VALUES (?, ?, ?, ?, 'unread')";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'iiss', $vendorId, $orderId, $title, $message);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        return;
    }

    if (!$userId) return;

    $sql = "INSERT INTO tbl_notifications (user_id, order_id, title, message, status) VALUES (?, ?, ?, ?, 'unread')";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'iiss', $userId, $orderId, $title, $message);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function ensureAdminNotificationSupport($conn) {
    $adminCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'admin_id'");
    if ($adminCol && mysqli_num_rows($adminCol) === 0) {
        mysqli_query($conn, "ALTER TABLE tbl_notifications ADD COLUMN admin_id INT DEFAULT NULL");
        mysqli_query($conn, "ALTER TABLE tbl_notifications ADD INDEX notif_admin_idx (admin_id)");
    }

    $userCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'user_id'");
    if ($userCol && mysqli_num_rows($userCol) > 0) {
        $userRow = mysqli_fetch_assoc($userCol);
        if (isset($userRow['Null']) && strcasecmp($userRow['Null'], 'YES') !== 0) {
            mysqli_query($conn, "ALTER TABLE tbl_notifications MODIFY user_id INT DEFAULT NULL");
        }
    }
}

switch ($response) {
    case 'accept':
        if ($currentDeliveryStatus !== 'assigned') break;
        $upd = mysqli_prepare($conn, "UPDATE tbl_orders SET delivery_status='picked_up', order_status='Dispatched' WHERE order_id = ? AND rider_id = ?");
        if ($upd) {
            mysqli_stmt_bind_param($upd, 'ii', $order_id, $rider_id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        }
        $msg = 'accepted';
        $trackStatus = 'picked_up';
        $trackMsg = 'Rider accepted the order and picked it up.';
        break;

    case 'reject':
        if ($currentDeliveryStatus !== 'assigned') break;
        $upd = mysqli_prepare($conn, "UPDATE tbl_orders SET rider_id = NULL, delivery_status='not_assigned', order_status='Confirmed' WHERE order_id = ? AND rider_id = ?");
        if ($upd) {
            mysqli_stmt_bind_param($upd, 'ii', $order_id, $rider_id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        }
        $msg = 'rejected';
        $trackStatus = 'cancelled';
        $trackMsg = 'Rider rejected the order assignment.';

        // Get rider name and customer name for better admin notifications
        $riderName = 'Rider';
        $riderStmt = mysqli_prepare($conn, "SELECT name FROM tbl_riders WHERE rider_id = ? LIMIT 1");
        if ($riderStmt) {
            mysqli_stmt_bind_param($riderStmt, 'i', $rider_id);
            mysqli_stmt_execute($riderStmt);
            $res = mysqli_stmt_get_result($riderStmt);
            $row = mysqli_fetch_assoc($res);
            if ($row && !empty($row['name'])) {
                $riderName = $row['name'];
            }
            mysqli_stmt_close($riderStmt);
        }

        $customerName = '';
        if (!empty($orderRow['user_id'])) {
            $userStmt = mysqli_prepare($conn, "SELECT COALESCE(user_name, customer_name, '') AS customer_name FROM tbl_orders o LEFT JOIN tbl_users u ON o.user_id = u.user_id WHERE o.order_id = ? LIMIT 1");
            if ($userStmt) {
                mysqli_stmt_bind_param($userStmt, 'i', $order_id);
                mysqli_stmt_execute($userStmt);
                $res = mysqli_stmt_get_result($userStmt);
                $row = mysqli_fetch_assoc($res);
                if ($row && !empty($row['customer_name'])) {
                    $customerName = $row['customer_name'];
                }
                mysqli_stmt_close($userStmt);
            }
        }
        if (empty($customerName) && !empty($orderRow['order_number'])) {
            $customerName = 'Order ' . $orderRow['order_number'];
        }

        ensureAdminNotificationSupport($conn);
        $adminColumn = mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'admin_id'");
        if ($adminColumn && mysqli_num_rows($adminColumn) > 0) {
            $adminQuery = mysqli_query($conn, "SELECT admin_id FROM tbl_admin");
            if ($adminQuery) {
                $rejectTitle = "{$riderName} rejected order #{$orderRow['order_number']}";
                $rejectMessage = "{$riderName} rejected this order";
                if (!empty($customerName)) {
                    $rejectMessage .= " for {$customerName}.";
                } else {
                    $rejectMessage .= ".";
                }
                while ($adminRow = mysqli_fetch_assoc($adminQuery)) {
                    $adminId = intval($adminRow['admin_id']);
                    $adminNotif = mysqli_prepare($conn, "INSERT INTO tbl_notifications (admin_id, order_id, title, message, status) VALUES (?, ?, ?, ?, 'unread')");
                    if ($adminNotif) {
                        mysqli_stmt_bind_param($adminNotif, 'iiss', $adminId, $order_id, $rejectTitle, $rejectMessage);
                        mysqli_stmt_execute($adminNotif);
                        mysqli_stmt_close($adminNotif);
                    }
                }
                mysqli_free_result($adminQuery);
            }
        }
        break;

    case 'start_delivery':
        if ($currentDeliveryStatus !== 'picked_up') break;
        $upd = mysqli_prepare($conn, "UPDATE tbl_orders SET delivery_status='out_for_delivery' WHERE order_id = ? AND rider_id = ?");
        if ($upd) {
            mysqli_stmt_bind_param($upd, 'ii', $order_id, $rider_id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        }
        $msg = 'out_for_delivery';
        $trackStatus = 'out_for_delivery';
        $trackMsg = 'Rider is en route to deliver the order.';
        break;

    case 'collect_payment':
        // For COD orders only - collect payment before marking as delivered
        $isCOD = isset($orderRow['payment_method']) && strtoupper($orderRow['payment_method']) === 'COD';
        if (!$isCOD || $currentDeliveryStatus !== 'out_for_delivery') break;
        
        $upd = mysqli_prepare($conn, "UPDATE tbl_orders SET payment_status='paid', delivery_status='payment_collected' WHERE order_id = ? AND rider_id = ?");
        if ($upd) {
            mysqli_stmt_bind_param($upd, 'ii', $order_id, $rider_id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        }
        $msg = 'payment_collected';
        $trackStatus = 'payment_collected';
        $trackMsg = 'Rider collected payment from customer.';
        break;

    case 'delivered':
        // For COD: only allow delivery if payment was collected. For other methods: allow from out_for_delivery
        $isCOD = isset($orderRow['payment_method']) && strtoupper($orderRow['payment_method']) === 'COD';
        
        if ($isCOD) {
            // For COD, payment must be collected first
            if ($currentDeliveryStatus !== 'payment_collected') break;
        } else {
            // For other payment methods, can deliver from out_for_delivery
            if ($currentDeliveryStatus !== 'out_for_delivery') break;
        }
        
        // Update order status and ensure payment is marked as paid
        $upd = mysqli_prepare($conn, "UPDATE tbl_orders SET delivery_status='delivered', order_status='Completed', payment_status='Paid' WHERE order_id = ? AND rider_id = ?");
        if ($upd) {
            mysqli_stmt_bind_param($upd, 'ii', $order_id, $rider_id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        }
        
        // Calculate and record earnings
        $orderDetails = null;
        $detailsStmt = mysqli_prepare($conn, "SELECT subtotal, tax, vendor_id, delivery_charges FROM tbl_orders WHERE order_id = ?");
        if ($detailsStmt) {
            mysqli_stmt_bind_param($detailsStmt, 'i', $order_id);
            mysqli_stmt_execute($detailsStmt);
            $res = mysqli_stmt_get_result($detailsStmt);
            $orderDetails = mysqli_fetch_assoc($res);
            mysqli_stmt_close($detailsStmt);
        }
        
        if ($orderDetails) {
            $subtotal = floatval($orderDetails['subtotal']);
            $tax = floatval($orderDetails['tax']);
            $orderAmountAfterGST = $subtotal + $tax;
            $vendorId = intval($orderDetails['vendor_id']);
            $deliveryCharge = floatval($orderDetails['delivery_charges'] ?? 50.00);

            // If earnings already exist for this order, skip duplicate inserts.
            $earningsCheckStmt = mysqli_prepare($conn, "SELECT 1 FROM tbl_admin_earnings WHERE order_id = ? LIMIT 1");
            $shouldInsertEarnings = true;
            if ($earningsCheckStmt) {
                mysqli_stmt_bind_param($earningsCheckStmt, 'i', $order_id);
                mysqli_stmt_execute($earningsCheckStmt);
                $res = mysqli_stmt_get_result($earningsCheckStmt);
                if ($res && mysqli_num_rows($res) > 0) {
                    $shouldInsertEarnings = false;
                }
                mysqli_stmt_close($earningsCheckStmt);
            }

            if ($shouldInsertEarnings) {
                // Admin earnings (15% commission on subtotal after GST)
                $adminCommission = $orderAmountAfterGST * 0.15;
                $adminStmt = mysqli_prepare($conn, "INSERT INTO tbl_admin_earnings (order_id, order_amount, commission_amount) VALUES (?, ?, ?)");
                if ($adminStmt) {
                    mysqli_stmt_bind_param($adminStmt, 'idd', $order_id, $orderAmountAfterGST, $adminCommission);
                    mysqli_stmt_execute($adminStmt);
                    mysqli_stmt_close($adminStmt);
                }

                // Vendor earnings (85% of amount after GST, no delivery charge deduction)
                $vendorEarning = $orderAmountAfterGST * 0.85;
                $vendorStmt = mysqli_prepare($conn, "INSERT INTO tbl_vendor_earnings (order_id, vendor_id, order_amount, admin_commission, delivery_charge, net_earning) VALUES (?, ?, ?, ?, ?, ?)");
                if ($vendorStmt) {
                    mysqli_stmt_bind_param($vendorStmt, 'iiddid', $order_id, $vendorId, $orderAmountAfterGST, $adminCommission, $deliveryCharge, $vendorEarning);
                    mysqli_stmt_execute($vendorStmt);
                    mysqli_stmt_close($vendorStmt);
                }

                // Rider earnings (delivery charge)
                $riderStmt = mysqli_prepare($conn, "INSERT INTO tbl_rider_earnings (order_id, rider_id, delivery_charge) VALUES (?, ?, ?)");
                if ($riderStmt) {
                    mysqli_stmt_bind_param($riderStmt, 'iid', $order_id, $rider_id, $deliveryCharge);
                    mysqli_stmt_execute($riderStmt);
                    mysqli_stmt_close($riderStmt);
                }
            }
        }
        
        $msg = 'delivered';
        $trackStatus = 'delivered';
        $trackMsg = 'Order delivered successfully.';
        break;
}

if ($trackStatus) {
    $ins = mysqli_prepare($conn, "INSERT INTO tbl_order_tracking (order_id, rider_id, status, message) VALUES (?,?,?,?)");
    if ($ins) {
        mysqli_stmt_bind_param($ins, 'iiss', $order_id, $rider_id, $trackStatus, $trackMsg);
        mysqli_stmt_execute($ins);
        mysqli_stmt_close($ins);
    }

    // Notify customer and vendor about delivery progress
    $orderNumber = $orderRow['order_number'];
    $userId = $orderRow['user_id'];
    $vendorId = $orderRow['vendor_id'] ?? 0;

    $title = "Order #{$orderNumber} Update";
    $message = "Order #{$orderNumber}: {$trackMsg}";

    // Notify customer
    notifyRecipient($conn, $order_id, $title, $message, $userId, 0);

    // Notify vendor (if available)
    if ($vendorId) {
        notifyRecipient($conn, $order_id, $title, $message, 0, $vendorId);
    }
}


// Mark any rider notifications for this order as read
$updNotif = mysqli_prepare($conn, "UPDATE tbl_notifications SET status='read' WHERE user_id = ? AND order_id = ? AND status = 'unread'");
if ($updNotif) {
    mysqli_stmt_bind_param($updNotif, 'ii', $rider_id, $order_id);
    mysqli_stmt_execute($updNotif);
    mysqli_stmt_close($updNotif);
}

header('Location: assigned_orders.php?status=' . urlencode($msg));
exit;
