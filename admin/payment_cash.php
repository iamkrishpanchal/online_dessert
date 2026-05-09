<?php
// Admin Cash Payments Page
session_start();
include 'connection.php';

// Only admin can access payment pages
if (empty($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Fetch cash orders (COD or Cash)
$sql = "SELECT o.order_id, o.order_number, u.user_name, u.phone, o.total_amount, o.order_status, o.payment_status, o.payment_method, o.created_at
        FROM tbl_orders o
        JOIN tbl_users u ON u.user_id = o.user_id
        WHERE LOWER(o.payment_method) IN ('cod','cash')
        ORDER BY o.created_at DESC
        LIMIT 200";
$res = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Payments - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .badge-status { font-size: 0.85rem; }
        .payment-header { margin-bottom: 1.5rem; }
    </style>
</head>
<body>
<div class="container-fluid p-4">
    <div class="d-flex align-items-center justify-content-between payment-header">
        <h1 class="h4 mb-0">Cash Payments</h1>
        <a href="orders_dashboard.php" class="btn btn-outline-secondary">Back to Orders</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res && mysqli_num_rows($res) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($res)): ?>
                                <?php $paymentClass = $row['payment_status'] === 'Paid' ? 'badge bg-success' : 'badge bg-warning'; ?>
                                <tr>
                                    <td><a href="order_details.php?order_id=<?php echo $row['order_id']; ?>"><?php echo htmlspecialchars($row['order_number']); ?></a></td>
                                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone'] ?? '-'); ?></td>
                                    <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td><span class="badge-status <?php echo $paymentClass; ?>"><?php echo ucfirst($row['payment_status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                    <td><?php echo date('d-m-Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td><a href="order_details.php?order_id=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No cash payment orders found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
