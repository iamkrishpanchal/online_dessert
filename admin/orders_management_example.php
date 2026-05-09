<?php
/**
 * Example: Admin Dashboard Order Management with Status Updates
 * This shows how to integrate order status updates with notification sending.
 * 
 * File: admin/orders_management.php (or similar)
 */
session_start();
include 'connection.php';

// Verify admin access
if (empty($_SESSION['admin_id']) && empty($_SESSION['vendor_id'])) {
    header('Location: ../login.php');
    exit;
}

$vendor_id = $_SESSION['vendor_id'] ?? null;
$is_admin = !empty($_SESSION['admin_id']);

// Handle AJAX status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    
    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');
    
    if (!$order_id || !$new_status) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    $allowed_statuses = ['Pending', 'Confirmed', 'Dispatched', 'Completed', 'Cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    // Verify admin/vendor permission
    if (!$is_admin && $vendor_id) {
        // Vendor only: verify they own this order
        $verify = mysqli_prepare($conn, 
            "SELECT o.order_id FROM tbl_orders o 
             WHERE o.order_id = ? AND o.vendor_id = ?");
        mysqli_stmt_bind_param($verify, 'ii', $order_id, $vendor_id);
        mysqli_stmt_execute($verify);
        $res = mysqli_stmt_get_result($verify);
        if (mysqli_num_rows($res) === 0) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            mysqli_stmt_close($verify);
            exit;
        }
        mysqli_stmt_close($verify);
    }
    
    // Update status
    $upd = mysqli_prepare($conn, "UPDATE tbl_orders SET order_status = ? WHERE order_id = ?");
    mysqli_stmt_bind_param($upd, 'si', $new_status, $order_id);
    if (!mysqli_stmt_execute($upd)) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        mysqli_stmt_close($upd);
        exit;
    }
    mysqli_stmt_close($upd);
    
    // Send notification to customer
    $notif_map = [
        'Confirmed' => [
            'title' => 'Your order has been confirmed.',
            'message' => 'Your order is confirmed and is being prepared for dispatch.'
        ],
        'Dispatched' => [
            'title' => 'Your order has been dispatched.',
            'message' => 'Your order is on its way. You can track it anytime.'
        ],
        'Completed' => [
            'title' => 'Your order has been delivered successfully.',
            'message' => 'Thank you for your order. We hope you enjoy your purchase!'
        ],
        'Cancelled' => [
            'title' => 'Your order has been cancelled.',
            'message' => 'Your order has been cancelled. Please contact support for more info.'
        ]
    ];
    
    if (isset($notif_map[$new_status])) {
        $title = $notif_map[$new_status]['title'];
        $message = $notif_map[$new_status]['message'];
        
        $ins = mysqli_prepare($conn,
            "INSERT INTO tbl_notifications (user_id, order_id, title, message, status)
             VALUES ((SELECT user_id FROM tbl_orders WHERE order_id = ?), ?, ?, ?, 'unread')");
        if ($ins) {
            mysqli_stmt_bind_param($ins, 'iiss', $order_id, $order_id, $title, $message);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
        }
    }
    
    // If changing to Confirmed, also set payment_status to Paid
    if ($new_status === 'Confirmed') {
        $payupd = mysqli_prepare($conn,
            "UPDATE tbl_orders SET payment_status = 'Paid' WHERE order_id = ? AND payment_status = 'pending'");
        if ($payupd) {
            mysqli_stmt_bind_param($payupd, 'i', $order_id);
            mysqli_stmt_execute($payupd);
            mysqli_stmt_close($payupd);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Order status updated and notification sent.']);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .order-card {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .order-card.pending { border-left-color: #ffc107; }
        .order-card.confirmed { border-left-color: #17a2b8; }
        .order-card.dispatched { border-left-color: #fd7e14; }
        .order-card.completed { border-left-color: #28a745; }
        .order-card.cancelled { border-left-color: #dc3545; }
        
        .status-badge {
            padding: 0.5em 0.75em;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.confirmed { background: #cfe2ff; color: #084298; }
        .status-badge.dispatched { background: #fff5e1; color: #664d03; }
        .status-badge.completed { background: #d1e7dd; color: #0f5132; }
        .status-badge.cancelled { background: #f8d7da; color: #842029; }
        
        .order-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .order-actions button {
            font-size: 0.85em;
            padding: 0.4em 0.8em;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <h2>Order Management</h2>
    <hr>
    
    <div id="orders-list">
        <!-- Orders will be loaded here -->
    </div>
</div>

<script>
    // Fetch and display all pending/active orders
    function loadOrders() {
        const filterStatus = document.getElementById('filter-status')?.value || 'all';
        
        fetch('get_orders_api.php?filter=' + encodeURIComponent(filterStatus))
            .then(r => r.json())
            .then(data => {
                let html = '';
                if (data.success && data.orders && data.orders.length) {
                    data.orders.forEach(order => {
                        const statusClass = order.order_status.toLowerCase();
                        html += `<div class="card order-card ${statusClass}">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Order #${order.order_number}</h5>
                                        <p>
                                            <small>
                                                <strong>User:</strong> ${order.user_name || 'Unknown'}<br>
                                                <strong>Amount:</strong> ₹${parseFloat(order.total_amount).toFixed(2)}<br>
                                                <strong>Payment:</strong> <span class="badge badge-info">${order.payment_status}</span><br>
                                                <strong>Date:</strong> ${new Date(order.created_at).toLocaleDateString()}
                                            </small>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-end">
                                            <span class="status-badge ${statusClass}">${order.order_status}</span>
                                            <div class="order-actions mt-3">
                                                ${order.order_status !== 'Confirmed' 
                                                    ? `<button class="btn btn-sm btn-primary" onclick="updateStatus(${order.order_id}, 'Confirmed')">✓ Confirm</button>` 
                                                    : ''}
                                                ${order.order_status !== 'Dispatched' && order.order_status !== 'Cancelled' && order.order_status !== 'Completed'
                                                    ? `<button class="btn btn-sm btn-warning" onclick="updateStatus(${order.order_id}, 'Dispatched')">📦 Dispatch</button>`
                                                    : ''}
                                                ${order.order_status !== 'Completed' && order.order_status !== 'Cancelled'
                                                    ? `<button class="btn btn-sm btn-success" onclick="updateStatus(${order.order_id}, 'Completed')">✓ Deliver</button>`
                                                    : ''}
                                                ${order.order_status !== 'Cancelled' && order.order_status !== 'Completed'
                                                    ? `<button class="btn btn-sm btn-danger" onclick="updateStatus(${order.order_id}, 'Cancelled')">✗ Cancel</button>`
                                                    : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    });
                } else {
                    html = '<div class="alert alert-info">No orders found.</div>';
                }
                document.getElementById('orders-list').innerHTML = html;
            })
            .catch(err => {
                console.error('Error loading orders:', err);
                document.getElementById('orders-list').innerHTML = 
                    '<div class="alert alert-danger">Error loading orders.</div>';
            });
    }
    
    // Update order status and notify customer
    function updateStatus(orderId, newStatus) {
        if (!confirm(`Update order to "${newStatus}"? Customer will be notified.`)) {
            return;
        }
        
        const btn = event.target;
        btn.classList.add('loading');
        btn.disabled = true;
        
        fetch('orders_management.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=update_status&order_id=${orderId}&new_status=${encodeURIComponent(newStatus)}`
        })
        .then(r => r.json())
        .then(data => {
            btn.classList.remove('loading');
            btn.disabled = false;
            
            if (data.success) {
                alert('✓ Order updated! Customer notified.');
                loadOrders(); // Reload list
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            btn.classList.remove('loading');
            btn.disabled = false;
            alert('Error: ' + err.message);
        });
    }
    
    // Load orders on page load
    window.addEventListener('DOMContentLoaded', loadOrders);
    
    // Auto-reload every 30 seconds
    setInterval(loadOrders, 30000);
</script>
</body>
</html>
