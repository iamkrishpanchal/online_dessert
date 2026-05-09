<?php
session_start();
include 'connection.php';

$user_id = $_SESSION['user_id'] ?? 0;

?><!DOCTYPE html>
<html>
<head>
    <title>Voucher Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h2>Voucher System Diagnostic</h2>
    
    <?php if ($user_id <= 0): ?>
        <div class="alert alert-warning">Please login first</div>
    <?php else: ?>
        <div class="card mb-3">
            <div class="card-header">
                <h5>User ID: <?php echo $user_id; ?></h5>
            </div>
            <div class="card-body">
                
                <h6>Session Check:</h6>
                <p><strong>$_SESSION['voucher_claimed']:</strong></p>
                <pre><?php var_dump($_SESSION['voucher_claimed'] ?? 'NOT SET'); ?></pre>
                
                <hr>
                
                <h6>Database Check:</h6>
                <?php
                // Create table if not exists
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
                
                // Check all claims for this user
                $check_sql = "SELECT * FROM tbl_voucher_claims WHERE user_id = ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, 'i', $user_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if (mysqli_num_rows($check_result) > 0) {
                    echo "<table class='table'>";
                    echo "<thead><tr><th>Claim ID</th><th>Code</th><th>Status</th><th>Claimed At</th><th>Used In Order</th></tr></thead>";
                    echo "<tbody>";
                    while ($row = mysqli_fetch_assoc($check_result)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['claim_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['voucher_code']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['claimed_at']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['used_in_order_id'] ?? 'NULL') . "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                    echo "</table>";
                } else {
                    echo "<div class='alert alert-warning'>No voucher claims found in database</div>";
                }
                
                // Check with active status specifically
                $active_sql = "SELECT * FROM tbl_voucher_claims WHERE user_id = ? AND status = 'active' AND voucher_code = '25PERCENT'";
                $active_stmt = mysqli_prepare($conn, $active_sql);
                mysqli_stmt_bind_param($active_stmt, 'i', $user_id);
                mysqli_stmt_execute($active_stmt);
                $active_result = mysqli_stmt_get_result($active_stmt);
                
                echo "<hr>";
                echo "<p><strong>Active '25PERCENT' vouchers:</strong> " . mysqli_num_rows($active_result) . "</p>";
                
                if (mysqli_num_rows($active_result) > 0) {
                    echo "<div class='alert alert-success'>✓ User has active voucher</div>";
                } else {
                    echo "<div class='alert alert-danger'>✗ User does not have active voucher</div>";
                }
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Test Calculation</h5>
            </div>
            <div class="card-body">
                <?php
                // Simulate cart calculation
                $subtotal = 2500;
                $gst = $subtotal * 0.05;
                $total = $subtotal + $gst;
                $discount = $total * 0.25;
                $final = $total - $discount;
                
                // Check if voucher applies
                $voucher_applies = false;
                if (!empty($_SESSION['voucher_claimed'])) {
                    $voucher_applies = true;
                } else {
                    $stmt_check = mysqli_prepare($conn, "SELECT claim_id FROM tbl_voucher_claims WHERE user_id = ? AND status = 'active' AND voucher_code = '25PERCENT'");
                    mysqli_stmt_bind_param($stmt_check, 'i', $user_id);
                    mysqli_stmt_execute($stmt_check);
                    if (mysqli_stmt_get_result($stmt_check) && mysqli_num_rows(mysqli_stmt_get_result($stmt_check)) > 0) {
                        $voucher_applies = true;
                    }
                }
                ?>
                <table class='table'>
                    <tr><td>Subtotal:</td><td>₹<?php echo number_format($subtotal, 2); ?></td></tr>
                    <tr><td>GST (5%):</td><td>₹<?php echo number_format($gst, 2); ?></td></tr>
                    <tr><td>Total (before discount):</td><td>₹<?php echo number_format($total, 2); ?></td></tr>
                    <tr><td>Voucher Applied:</td><td><?php echo $voucher_applies ? '<span class="badge bg-success">YES</span>' : '<span class="badge bg-danger">NO</span>'; ?></td></tr>
                    <?php if ($voucher_applies): ?>
                    <tr><td>Discount (25%):</td><td class="text-success">-₹<?php echo number_format($discount, 2); ?></td></tr>
                    <tr><td><strong>Final Total:</strong></td><td><strong>₹<?php echo number_format($final, 2); ?></strong></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
    <?php endif; ?>
    
    <div class="mt-4">
        <a href="index.php" class="btn btn-primary">Back to Home</a>
        <a href="cart.php" class="btn btn-secondary">View Cart</a>
    </div>
    
</div>
</body>
</html>
