<?php
session_start();
include 'connection.php';

// Create voucher claims table
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

if (mysqli_query($conn, $create_table_sql)) {
    echo "Voucher tracking table created successfully!";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>
