<?php
session_start();
include 'connection.php';

// Check authorization
if (empty($_SESSION['admin_id']) && empty($_SESSION['vendor_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$order_id = intval($_GET['order_id'] ?? 0);

if (!$order_id) {
    http_response_code(400);
    exit('Invalid order ID');
}

// Fetch current order to verify access
$is_admin = !empty($_SESSION['admin_id']);
$vendor_id = $_SESSION['vendor_id'] ?? null;

if ($is_admin) {
    $sql = "SELECT order_id, rider_id FROM tbl_orders WHERE order_id = ?";
    $params = [$order_id];
    $types = 'i';
} else {
    $sql = "SELECT order_id, rider_id FROM tbl_orders WHERE order_id = ? AND vendor_id = ?";
    $params = [$order_id, $vendor_id];
    $types = 'ii';
}

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    exit('Database error');
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$order || empty($order['rider_id'])) {
    echo '<p class="text-muted" style="font-size: 0.85rem; margin: 0;">No rider assigned yet.</p>';
    exit;
}

// Fetch tracking logs
$tracking_logs = [];
$tstmt = mysqli_prepare($conn, "SELECT * FROM tbl_order_tracking WHERE order_id=? ORDER BY created_at ASC");
if ($tstmt) {
    mysqli_stmt_bind_param($tstmt, 'i', $order_id);
    mysqli_stmt_execute($tstmt);
    $tres = mysqli_stmt_get_result($tstmt);
    if ($tres) {
        while ($rowt = mysqli_fetch_assoc($tres)) {
            $tracking_logs[] = $rowt;
        }
    }
    mysqli_stmt_close($tstmt);
}

// Filter and display rider actions
$rider_actions = [];
foreach ($tracking_logs as $log) {
    if (in_array($log['status'], ['picked_up', 'out_for_delivery', 'payment_collected', 'delivered'])) {
        $rider_actions[] = $log;
    }
}

if (!empty($rider_actions)):
    $action_labels = [
        'picked_up' => ['label' => 'Accepted & Picked Up', 'icon' => 'check-circle', 'color' => 'success'],
        'out_for_delivery' => ['label' => 'Out for Delivery', 'icon' => 'truck', 'color' => 'info'],
        'payment_collected' => ['label' => 'Payment Collected (COD)', 'icon' => 'cash-coin', 'color' => 'success'],
        'delivered' => ['label' => 'Delivered', 'icon' => 'check-square', 'color' => 'success']
    ];
    
    foreach ($rider_actions as $action):
        $action_info = $action_labels[$action['status']] ?? ['label' => ucfirst(str_replace('_', ' ', $action['status'])), 'icon' => 'info-circle', 'color' => 'secondary'];
        ?>
        <div style="display: flex; margin-bottom: 10px; align-items: flex-start;">
            <div style="width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; margin-right: 12px; flex-shrink: 0; font-size: 0.9rem; background-color: #<?php 
                $colors = ['success' => '28a745', 'info' => '17a2b8', 'secondary' => '6c757d'];
                echo $colors[$action_info['color']] ?? '6c757d';
            ?>;">
                <i class="bi bi-<?php echo $action_info['icon']; ?>"></i>
            </div>
            <div>
                <p class="mb-0" style="font-size: 0.85rem; font-weight: 600;"><?php echo $action_info['label']; ?></p>
                <small class="text-muted" style="font-size: 0.75rem;">
                    <?php echo date('d M, H:i A', strtotime($action['created_at'])); ?>
                </small>
                <?php if (!empty($action['message'])): ?>
                    <p class="mb-0 mt-1"><small style="font-size: 0.75rem;"><?php echo htmlspecialchars($action['message']); ?></small></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    endforeach;
else:
    ?>
    <p class="text-muted" style="font-size: 0.85rem; margin: 0;">No rider actions yet.</p>
    <?php
endif;
?>
