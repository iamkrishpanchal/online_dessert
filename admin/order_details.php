<?php
/**
 * ORDER DETAILS PAGE - Show complete order information
 */
session_start();
include 'connection.php';

// Check authorization
if (empty($_SESSION['admin_id']) && empty($_SESSION['vendor_id'])) {
    header('Location: ../login.php');
    exit;
}

// Ensure tbl_order_tracking table exists (create if missing)
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

$is_admin = !empty($_SESSION['admin_id']);
$vendor_id = $_SESSION['vendor_id'] ?? null;
$order_id = intval($_GET['order_id'] ?? 0);

// Get order details
if ($is_admin) {
    $sql = "SELECT o.*, u.user_name, u.email, u.phone as user_phone, u.address
            FROM tbl_orders o
            JOIN tbl_users u ON o.user_id = u.user_id
            WHERE o.order_id = ?";
    $params = [$order_id];
    $types = 'i';
} else {
    $sql = "SELECT o.*, u.user_name, u.email, u.phone as user_phone, u.address
            FROM tbl_orders o
            JOIN tbl_users u ON o.user_id = u.user_id
            WHERE o.order_id = ? AND o.vendor_id = ?";
    $params = [$order_id, $vendor_id];
    $types = 'ii';
}

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$order) {
    die("Order not found or access denied");
}

// Get order items - try multiple image column approaches
$itemsTable = 'tbl_products';
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product'");
if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
    $itemsTable = 'tbl_product';
}

// Check which columns exist
$imageColumn = 'product_image';
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM {$itemsTable} LIKE 'product_image'");
if (!$colCheck || mysqli_num_rows($colCheck) === 0) {
    $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM {$itemsTable} LIKE 'image'");
    if ($colCheck && mysqli_num_rows($colCheck) > 0) {
        $imageColumn = 'image';
    }
}

$sql_items = "SELECT oi.*, p.product_name, p.{$imageColumn} as product_image
              FROM tbl_order_items oi
              LEFT JOIN {$itemsTable} p ON oi.product_id = p.product_id
              WHERE oi.order_id = ?";
$stmt = mysqli_prepare($conn, $sql_items);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $order_id);
    mysqli_stmt_execute($stmt);
    $items_result = mysqli_stmt_get_result($stmt);
    $items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

// fetch tracking logs
$tracking_logs = [];
$tstmt = mysqli_prepare($conn, "SELECT * FROM tbl_order_tracking WHERE order_id=? ORDER BY created_at ASC");
if ($tstmt) {
    mysqli_stmt_bind_param($tstmt,'i',$order_id);
    mysqli_stmt_execute($tstmt);
    $tres = mysqli_stmt_get_result($tstmt);
    if ($tres) {
        while ($rowt = mysqli_fetch_assoc($tres)) {
            $tracking_logs[] = $rowt;
        }
    }
    mysqli_stmt_close($tstmt);
}

// Check if rider has updated the order status (automatic updates)
$rider_updated_status = false;
$rider_action = '';
if (!empty($tracking_logs)) {
    foreach ($tracking_logs as $log) {
        if ($log['status'] === 'picked_up' || $log['status'] === 'out_for_delivery' || $log['status'] === 'payment_collected' || $log['status'] === 'delivered') {
            $rider_updated_status = true;
            $rider_action = $log['status'];
            break;
        }
    }
}

// Status timeline
$status_timeline = [
    'Pending' => ['icon' => 'hourglass-split', 'color' => 'warning', 'label' => 'Pending'],
    'Confirmed' => ['icon' => 'check-circle', 'color' => 'info', 'label' => 'Confirmed'],
    'Dispatched' => ['icon' => 'truck', 'color' => 'warning', 'label' => 'Dispatched'],
    'Completed' => ['icon' => 'check-square', 'color' => 'success', 'label' => 'Completed'],
    'Cancelled' => ['icon' => 'x-circle', 'color' => 'danger', 'label' => 'Cancelled']
];
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - <?php echo htmlspecialchars($order['order_number']); ?></title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .detail-card { border: 1px solid #dee2e6; border-radius: 8px; background: white; }
        .timeline { position: relative; padding: 20px 0; }
        .timeline-item {
            display: flex;
            margin-bottom: 20px;
            align-items: flex-start;
        }
        .timeline-dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 20px;
            flex-shrink: 0;
        }
        .timeline-dot.completed { background-color: #28a745; }
        .timeline-dot.current { background-color: #007bff; }
        .timeline-dot.pending { background-color: #ccc; }
        
        .order-item {
            display: flex;
            gap: 20px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 15px;
            background: white;
            transition: all 0.3s ease;
        }
        .order-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .order-item-image {
            width: 120px;
            height: 120px;
            min-width: 120px;
            object-fit: cover;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        .badge-status {
            font-size: 1.1em;
            padding: 0.75em 1.25em;
        }
        .action-section { margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .content { margin-left: 20px; }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 768px) { 
            .content { margin-left: 0; }
        }
        .page-header { padding: 20px 0; margin-bottom: 20px; }
        .container-fluid { padding-left: 0; padding-right: 20px; }
    </style>
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
<div class="flex mt-[4.7rem] md:mt-0 overflow-visible">
    <!-- Side Menu -->
    <?php include 'sideMenu.php' ?>
    <!-- Content -->
    <div class="content w-full">
        <!-- Main Content -->
        <div class="container-fluid p-4">
            <div class="page-header">
                <div class="row align-items-center mb-4">
                    <div class="col">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-2">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="orders_dashboard.php">Orders</a></li>
                                <li class="breadcrumb-item active">Order Details</li>
                            </ol>
                        </nav>
                        <h2 class="mb-0"><i class="bi bi-box-seam"></i> Order Details</h2>
                    </div>
                    <div class="col-auto">
                        <button onclick="location.reload();" class="btn btn-sm btn-outline-primary me-2" title="Refresh page to see latest updates">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <a href="orders_dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Orders
                        </a>
                    </div>
                </div>
            </div>

    <!-- Order Status Alert -->
    <div class="alert alert-info mb-4">
        <strong>Order #<?php echo htmlspecialchars($order['order_number']); ?></strong>
        <span class="ms-2 badge badge-status bg-<?php 
            $status_colors = ['Pending' => 'warning', 'Confirmed' => 'info', 'Dispatched' => 'warning', 'Completed' => 'success', 'Cancelled' => 'danger'];
            echo $status_colors[$order['order_status']] ?? 'secondary';
        ?>">
            <?php echo ucfirst($order['order_status']); ?>
        </span>
        <span class="ms-2 badge bg-<?php echo $order['payment_status'] === 'Paid' ? 'success' : 'warning'; ?>">
            Payment: <?php echo $order['payment_status']; ?>
        </span>
        <?php if ($rider_updated_status): ?>
            <span class="ms-2 badge bg-success">
                <i class="bi bi-robot"></i> Auto-Updated by Rider
            </span>
        <?php endif; ?>
    </div>

    <?php if ($is_admin || !empty($vendor_id)): ?>
    <?php
        // fetch only active riders who are not currently assigned to an unfinished delivery
        $riders = [];
        $rsql = mysqli_query($conn, "SELECT rider_id, name, status FROM tbl_riders WHERE status = 'active' AND rider_id NOT IN (SELECT DISTINCT rider_id FROM tbl_orders WHERE rider_id IS NOT NULL AND delivery_status IN ('assigned','picked_up','out_for_delivery','payment_collected')) ORDER BY name");
        if ($rsql) {
            while ($rr = mysqli_fetch_assoc($rsql)) {
                $riders[] = $rr;
            }
        }
        
        // Get assigned rider's name if rider is already assigned
        $assigned_rider_name = '';
        if (!empty($order['rider_id'])) {
            foreach ($riders as $r) {
                if ($r['rider_id'] == $order['rider_id']) {
                    $assigned_rider_name = $r['name'];
                    break;
                }
            }

            if ($assigned_rider_name === '') {
                $assignedStmt = mysqli_prepare($conn, 'SELECT name FROM tbl_riders WHERE rider_id = ? LIMIT 1');
                if ($assignedStmt) {
                    mysqli_stmt_bind_param($assignedStmt, 'i', $order['rider_id']);
                    mysqli_stmt_execute($assignedStmt);
                    $assignedRes = mysqli_stmt_get_result($assignedStmt);
                    if ($assignedRow = mysqli_fetch_assoc($assignedRes)) {
                        $assigned_rider_name = $assignedRow['name'];
                    }
                    mysqli_stmt_close($assignedStmt);
                }
            }
        }
    ?>
    <!-- RIDER ASSIGNMENT CARD -->
    <div class="card detail-card mb-4" style="border-left: 4px solid #007bff; background-color: #f0f7ff;">
        <div class="card-header" style="background-color: #e3f2fd; border-bottom: 2px solid #007bff;">
            <h5 class="mb-0"><i class="bi bi-truck"></i> Assign Rider for Delivery</h5>
        </div>
        <div class="card-body">
            <?php if (strcasecmp($order['order_status'], 'Completed') === 0 || strcasecmp($order['delivery_status'], 'delivered') === 0): ?>
                <div class="alert alert-warning mb-3" role="alert">
                    <strong>Order already delivered.</strong><br>
                    Delivered orders cannot be assigned a rider.
                </div>
            <?php elseif (!empty($riders)): ?>
                <?php if (!empty($order['rider_id'])): ?>
                    <!-- Rider already assigned - show locked message -->
                    <div class="alert alert-success mb-3" role="alert">
                        <strong>✓ Rider Already Assigned</strong><br>
                        <strong>Rider Name:</strong> <?php echo htmlspecialchars($assigned_rider_name); ?><br>
                        <strong>Delivery Status:</strong> <?php echo htmlspecialchars($order['delivery_status'] ?? 'assigned'); ?><br>
                        <small class="text-muted mt-2 d-block">Rider assignment cannot be changed once set.</small>
                    </div>
                    <!-- Show the form disabled for reference only -->
                    <form class="row g-3 align-items-end" style="opacity: 0.6;">
                        <div class="col-md-6">
                            <label for="rider_id_disabled" class="form-label">Assigned Rider:</label>
                            <select id="rider_id_disabled" class="form-select" disabled>
                                <option><?php echo htmlspecialchars($assigned_rider_name); ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-primary" type="button" disabled onclick="showAlreadyAssignedAlert()">
                                <i class="bi bi-check-circle"></i> Assign Rider
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- No rider assigned yet - show active form -->
                    <form id="assign-rider-form" method="post" action="assign_rider.php" class="row g-3 align-items-end" onsubmit="return validateRiderSelection()">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        <div class="col-md-6">
                            <label for="rider_id" class="form-label">Select Rider:</label>
                            <select name="rider_id" id="rider_id" class="form-select" required>
                                <option value="">-- Choose a rider --</option>
                                <?php foreach ($riders as $r): ?>
                                    <option value="<?php echo $r['rider_id']; ?>">
                                        <?php echo htmlspecialchars($r['name']); ?> 
                                        <span class="badge bg-<?php echo ($r['status'] === 'active') ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($r['status']); ?>
                                        </span>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-check-circle"></i> Assign Rider
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <strong>No available riders right now</strong><br>
                    Either there are no active riders, or all active riders are currently assigned to another delivery. Please wait until a rider completes their current order, activate a rider, or <a href="rider_form.php" class="alert-link">add a new rider</a>.
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Rider Actions Timeline - Show all rider updates -->
    <?php if (!empty($order['rider_id']) && !empty($tracking_logs)): ?>
    <div class="card detail-card mb-4" style="border-left: 4px solid #17a2b8; background-color: #f0f8ff;">
        <div class="card-header" style="background-color: #e0f7ff; border-bottom: 2px solid #17a2b8; padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center;">
            <h6 class="mb-0" style="font-size: 0.95rem;"><i class="bi bi-clock-history"></i> Rider Actions Timeline</h6>
            <span style="font-size: 0.75rem; color: #17a2b8; font-weight: 600;">
                <i class="bi bi-arrow-repeat" style="animation: spin 2s linear infinite;"></i> Auto-updating
            </span>
        </div>
        <div class="card-body" style="padding: 1rem;">
            <div id="rider-timeline-content" class="timeline" style="padding: 0;">
                <?php 
                $rider_actions = [];
                foreach ($tracking_logs as $log) {
                    if (in_array($log['status'], ['picked_up', 'out_for_delivery', 'delivered'])) {
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
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Loading spinner for timeline refresh -->
    <div id="timeline-loading" style="display: none; text-align: center; padding: 10px;">
        <small><i class="bi bi-hourglass-split"></i> Refreshing...</small>
    </div>

    <div class="row">
        <!-- Left Column: Order Items & Timeline -->
        <div class="col-lg-8 mb-4">
            <!-- Order Items -->
            <div class="card detail-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-basket"></i> Order Items</h5>
                </div>
                <div class="card-body">
                    <?php
                    if (!empty($items)) {
                        $subtotal = 0;
                        foreach ($items as $item) {
                            $unit_price = floatval($item['unit_price'] ?? 0);
                            $quantity = intval($item['quantity'] ?? 1);
                            $item_total = $quantity * $unit_price;
                            $subtotal += $item_total;
                            $productImage = trim((string)($item['product_image'] ?? ''));
                            $image = '';
                            if ($productImage !== '') {
                                $candidates = [
                                    'uploads/' . $productImage,
                                    'vendor/uploads/' . $productImage,
                                    $productImage
                                ];
                                foreach ($candidates as $cand) {
                                    if (file_exists(__DIR__ . '/' . $cand)) {
                                        $image = $cand;
                                        break;
                                    }
                                }
                                if ($image === '') {
                                    $image = $candidates[0];
                                }
                            }
                            ?>
                            <div class="order-item">
                                <div>
                                    <?php if (!empty($image)): ?>
                                        <img src="<?php echo htmlspecialchars($image); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                             class="order-item-image" 
                                             style="box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                             onerror="this.src='https://via.placeholder.com/100?text=No+Image'; this.style.opacity='0.7';">
                                    <?php else: ?>
                                        <div class="order-item-image bg-light d-flex align-items-center justify-content-center" style="box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                            <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-2" style="font-weight: 600;"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                    <p class="mb-1 text-muted">
                                        <small>
                                            <strong>Price:</strong> ₹<?php echo number_format($unit_price, 2); ?> 
                                            <span class="mx-2">×</span> 
                                            <strong>Qty:</strong> <?php echo $quantity; ?>
                                        </small>
                                    </p>
                                    <p class="mb-0">
                                        <small style="background-color: #f0f0f0; padding: 4px 8px; border-radius: 4px;">
                                            <strong>Subtotal:</strong> ₹<?php echo number_format($item_total, 2); ?>
                                        </small>
                                    </p>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="text-muted">No items in this order.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Pricing Summary -->
            <div class="card detail-card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <p class="text-muted mb-1">Subtotal:</p>
                            <p class="mb-0">₹<?php echo number_format($order['subtotal'] ?? 0, 2); ?></p>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1">Discount:</p>
                            <p class="mb-0">-₹<?php echo number_format($order['discount'] ?? 0, 2); ?></p>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1">Delivery Charge:</p>
                            <p class="mb-0">₹<?php echo number_format($order['delivery_charges'] ?? 0, 2); ?></p>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1">Tax (5% GST):</p>
                            <p class="mb-0">₹<?php 
                                $tax = floatval($order['tax'] ?? 0);
                                if ($tax == 0) {
                                    $tax = floatval($order['subtotal'] ?? 0) * 0.05;
                                }
                                echo number_format($tax, 2); 
                            ?></p>
                        </div>
                        <div class="col-12 border-top pt-2">
                            <h6 class="mb-0">
                                Total: <strong class="text-success">₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Customer, Delivery & Payment Info -->
        <div class="col-lg-4">
            <!-- Customer Information -->
            <div class="card detail-card mb-4" style="border-top: 3px solid #28a745;">
                <div class="card-header" style="background-color: #f0f8f4;">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Customer Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        <strong>👤 Name:</strong><br>
                        <span class="text-dark"><?php echo htmlspecialchars($order['user_name']); ?></span>
                    </p>
                    <p class="mb-3">
                        <strong>✉️ Email:</strong><br>
                        <a href="mailto:<?php echo htmlspecialchars($order['email']); ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($order['email']); ?>
                        </a>
                    </p>
                    <p class="mb-0">
                        <strong>📱 Phone:</strong><br>
                        <a href="tel:<?php echo htmlspecialchars($order['user_phone']); ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($order['user_phone']); ?>
                        </a>
                    </p>
                </div>
            </div>

            <!-- Delivery Address -->
            <div class="card detail-card mb-4" style="border-top: 3px solid #0d6efd;">
                <div class="card-header" style="background-color: #f0f4ff;">
                    <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Delivery Address</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>📍 Address:</strong><br>
                        <span class="text-dark"><?php echo htmlspecialchars($order['delivery_address'] ?? $order['address'] ?? 'Not provided'); ?></span>
                    </p>
                    <p class="mb-0">
                        <strong>Location:</strong><br>
                        <small class="text-muted">
                            <?php echo htmlspecialchars($order['delivery_city'] ?? ''); ?> 
                            <?php echo !empty($order['delivery_pincode']) ? '- ' . htmlspecialchars($order['delivery_pincode']) : ''; ?>
                        </small>
                    </p>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="card detail-card mb-4" style="border-top: 3px solid #ffc107;">
                <div class="card-header" style="background-color: #fffaf0;">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> Payment Details</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        <strong>💳 Method:</strong><br>
                        <?php 
                        $paymentMethod = strtoupper($order['payment_method'] ?? 'N/A');
                        
                        if ($paymentMethod === 'COD') {
                            echo '<span class="badge" style="padding: 10px 15px; font-size: 0.95rem; background-color: #dc3545; color: white;">
                                    🚚 Cash on Delivery
                                  </span>';
                        } elseif ($paymentMethod === 'RAZORPAY' || $paymentMethod === 'ONLINE') {
                            echo '<span class="badge" style="padding: 10px 15px; font-size: 0.95rem; background-color: #28a745; color: white;">
                                    💳 Online Payment
                                  </span>';
                        } else {
                            echo '<span class="badge bg-secondary" style="padding: 10px 15px; font-size: 0.95rem;">
                                    💰 ' . ucfirst(htmlspecialchars($order['payment_method'] ?? 'N/A')) . '
                                  </span>';
                        }
                        ?>
                    </p>
                    <p class="mb-0">
                        <strong>Status:</strong><br>
                        <span class="badge bg-<?php echo ($order['payment_status'] === 'Paid' || $order['payment_status'] === 'paid') ? 'success' : 'warning'; ?>">
                            <?php 
                            $status = strtolower($order['payment_status'] ?? 'pending');
                            if ($status === 'paid') {
                                echo '✓ Payment Received';
                            } elseif ($status === 'pending') {
                                echo '⏳ Payment Pending';
                            } elseif ($status === 'failed') {
                                echo '✗ Payment Failed';
                            } else {
                                echo ucfirst($order['payment_status']);
                            }
                            ?>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Order Info -->
            <div class="card detail-card mb-4" style="border-top: 3px solid #6c757d;">
                <div class="card-header" style="background-color: #f8f9fa;">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Order Info</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>📅 Order Date:</strong><br>
                        <small><?php echo date('d M, Y | H:i A', strtotime($order['created_at'])); ?></small>
                    </p>
                    <p class="mb-0">
                        <strong>📝 Special Notes:</strong><br>
                        <small class="text-muted"><?php echo !empty($order['special_instructions']) ? htmlspecialchars($order['special_instructions']) : 'None'; ?></small>
                    </p>
                </div>
            </div>


        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Function to show alert when rider is already assigned
function showAlreadyAssignedAlert() {
    alert('You have already assigned a rider to this order. The rider assignment cannot be changed once set.');
}

// Validation to ensure a rider is selected before submission
function validateRiderSelection() {
    var riderSelect = document.getElementById('rider_id');
    if (!riderSelect.value) {
        alert('Please select a rider before assigning.');
        return false;
    }
    return true;
}

$(function(){
    $('#assign-rider-form').on('submit', function(e){
        e.preventDefault();
        var f=$(this);
        $.post(f.attr('action'), f.serialize(), function(resp){
            if(resp.success){
                location.reload();
            } else {
                alert(resp.message || 'Failed to assign');
            }
        },'json');
    });
});

// Function to refresh the Rider Actions Timeline via AJAX
function refreshRiderTimeline(orderId) {
    var loadingDiv = document.getElementById('timeline-loading');
    var timelineContent = document.getElementById('rider-timeline-content');
    
    // Show loading indicator
    if (loadingDiv) {
        loadingDiv.style.display = 'block';
    }
    
    // Make AJAX request to fetch updated timeline
    $.ajax({
        url: 'get_rider_timeline.php',
        type: 'GET',
        data: { order_id: orderId },
        success: function(response) {
            if (timelineContent) {
                timelineContent.innerHTML = response;
            }
            if (loadingDiv) {
                loadingDiv.style.display = 'none';
            }
        },
        error: function() {
            // Silent error - don't show alert on auto-refresh
            if (loadingDiv) {
                loadingDiv.style.display = 'none';
            }
        }
    });
}

// Auto-refresh Rider Actions Timeline every 8 seconds if order has a rider assigned
<?php if (!empty($order['rider_id'])): ?>
var timelineAutoRefreshInterval = setInterval(function() {
    refreshRiderTimeline(<?php echo $order_id; ?>);
}, 8000); // Refresh every 8 seconds

// Clear interval when user navigates away
$(window).on('beforeunload', function() {
    clearInterval(timelineAutoRefreshInterval);
});
<?php endif; ?>
</script>
        </div><!-- End container-fluid -->
        </div><!-- End content div -->
    </div><!-- End flex div -->
    <!-- JavaScript -->
    <script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
    <script src="dist/js/app.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
