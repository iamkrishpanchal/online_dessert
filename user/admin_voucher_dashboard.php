<?php
session_start();
include 'connection.php';

// Admin check - you can add your own admin validation here
// For now, we'll just check if user has access (can be restricted later)

// Ensure table exists
$create_table_sql = "CREATE TABLE IF NOT EXISTS tbl_voucher_claims (
    claim_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    voucher_code VARCHAR(100) NOT NULL DEFAULT '25PERCENT',
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_in_order_id INT DEFAULT NULL,
    status ENUM('active', 'used') DEFAULT 'active',
    UNIQUE KEY unique_user_voucher (user_id, voucher_code),
    FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE CASCADE
)";
@mysqli_query($conn, $create_table_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; }
        .container { margin-top: 40px; }
        .card { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .badge-active { background-color: #28a745; }
        .badge-used { background-color: #6c757d; }
    </style>
</head>
<body>
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-2">Voucher Management System</h1>
            <p class="text-muted">Track all 25% discount voucher claims and usage</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <?php
                    $total_sql = "SELECT COUNT(*) as total FROM tbl_voucher_claims";
                    $total = mysqli_fetch_assoc(mysqli_query($conn, $total_sql))['total'];
                    ?>
                    <h5 class="card-title">Total Claims</h5>
                    <h2><?php echo $total; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <?php
                    $active_sql = "SELECT COUNT(*) as active FROM tbl_voucher_claims WHERE status = 'active'";
                    $active = mysqli_fetch_assoc(mysqli_query($conn, $active_sql))['active'];
                    ?>
                    <h5 class="card-title">Active</h5>
                    <h2 class="text-success"><?php echo $active; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <?php
                    $used_sql = "SELECT COUNT(*) as used FROM tbl_voucher_claims WHERE status = 'used'";
                    $used = mysqli_fetch_assoc(mysqli_query($conn, $used_sql))['used'];
                    ?>
                    <h5 class="card-title">Used</h5>
                    <h2 class="text-secondary"><?php echo $used; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <?php
                    $conversion = ($total > 0) ? round(($used / $total) * 100, 1) : 0;
                    ?>
                    <h5 class="card-title">Conversion</h5>
                    <h2><?php echo $conversion; ?>%</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">All Voucher Claims</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Voucher Code</th>
                                    <th>Claimed Date</th>
                                    <th>Used in Order</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $claims_sql = "SELECT vc.claim_id, vc.user_id, u.user_name, u.email, vc.voucher_code, vc.claimed_at, vc.used_in_order_id, vc.status 
                                               FROM tbl_voucher_claims vc 
                                               LEFT JOIN tbl_users u ON vc.user_id = u.user_id 
                                               ORDER BY vc.claimed_at DESC";
                                $claims_result = mysqli_query($conn, $claims_sql);

                                if (mysqli_num_rows($claims_result) > 0) {
                                    while ($row = mysqli_fetch_assoc($claims_result)) {
                                        echo "<tr>";
                                        echo "<td>" . $row['claim_id'] . "</td>";
                                        echo "<td>" . $row['user_id'] . "</td>";
                                        echo "<td><strong>" . htmlspecialchars($row['user_name'] ?: 'N/A') . "</strong></td>";
                                        echo "<td>" . htmlspecialchars($row['email'] ?: 'N/A') . "</td>";
                                        echo "<td><code>" . $row['voucher_code'] . "</code></td>";
                                        echo "<td>" . date('M d, Y H:i', strtotime($row['claimed_at'])) . "</td>";
                                        echo "<td>" . ($row['used_in_order_id'] ? '#' . $row['used_in_order_id'] : '-') . "</td>";
                                        $badge_class = ($row['status'] === 'active') ? 'badge-active' : 'badge-used';
                                        echo "<td><span class='badge " . $badge_class . "'>" . ucfirst($row['status']) . "</span></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='text-center text-muted'>No voucher claims yet</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <p class="text-center text-muted">
                <a href="index.php">Back to Home</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
