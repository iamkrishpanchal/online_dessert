<?php
session_start();
include 'connection.php';

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

if (!mysqli_query($conn, $create_table_sql)) {
    die("Error creating table: " . mysqli_error($conn));
}

echo "<h2>Voucher System Verification</h2>";

// Check table structure
echo "<h3>Table Structure:</h3>";
$show_sql = "SHOW COLUMNS FROM tbl_voucher_claims";
$result = mysqli_query($conn, $show_sql);
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . ($row['Key'] ?: 'N/A') . "</td>";
    echo "<td>" . ($row['Default'] ?: 'N/A') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Show all voucher claims
echo "<h3>All Voucher Claims:</h3>";
$claims_sql = "SELECT vc.claim_id, vc.user_id, u.user_name, u.email, vc.voucher_code, vc.claimed_at, vc.status 
               FROM tbl_voucher_claims vc 
               LEFT JOIN tbl_users u ON vc.user_id = u.user_id 
               ORDER BY vc.claimed_at DESC";
$claims_result = mysqli_query($conn, $claims_sql);

if (mysqli_num_rows($claims_result) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>User ID</th><th>User Name</th><th>Email</th><th>Code</th><th>Claimed At</th><th>Status</th></tr>";
    while ($row = mysqli_fetch_assoc($claims_result)) {
        echo "<tr>";
        echo "<td>" . $row['claim_id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . ($row['user_name'] ?: 'N/A') . "</td>";
        echo "<td>" . ($row['email'] ?: 'N/A') . "</td>";
        echo "<td>" . $row['voucher_code'] . "</td>";
        echo "<td>" . $row['claimed_at'] . "</td>";
        echo "<td><strong>" . $row['status'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No voucher claims yet.</p>";
}

echo "<p><br><a href='index.php'>Back to Home</a></p>";
?>
