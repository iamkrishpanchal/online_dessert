<?php
// Migration to add earnings tables for admin, vendor, and rider earnings tracking
$conn = mysqli_connect("localhost", "root", "", "online_dessert");
if (!$conn) die("No DB connection\n");

$sqls = [
    // Admin earnings table
    "CREATE TABLE IF NOT EXISTS tbl_admin_earnings (
        earning_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        admin_id INT NOT NULL DEFAULT 1,
        order_amount DECIMAL(10,2) NOT NULL,
        commission_rate DECIMAL(5,2) NOT NULL DEFAULT 15.00,
        commission_amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_order (order_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Vendor earnings table
    "CREATE TABLE IF NOT EXISTS tbl_vendor_earnings (
        earning_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        vendor_id INT NOT NULL,
        order_amount DECIMAL(10,2) NOT NULL,
        admin_commission DECIMAL(10,2) NOT NULL,
        delivery_charge DECIMAL(10,2) NOT NULL DEFAULT 50.00,
        net_earning DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_order (order_id),
        INDEX idx_vendor (vendor_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Rider earnings table
    "CREATE TABLE IF NOT EXISTS tbl_rider_earnings (
        earning_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        rider_id INT NOT NULL,
        delivery_charge DECIMAL(10,2) NOT NULL DEFAULT 50.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_order (order_id),
        INDEX idx_rider (rider_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($sqls as $q) {
    if (!mysqli_query($conn, $q)) {
        echo "Error executing: $q -- " . mysqli_error($conn) . "<br>";
    } else {
        echo "Executed: $q<br>";
    }
}

echo "Earnings tables created successfully!";
?>