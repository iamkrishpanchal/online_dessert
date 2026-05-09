<?php
// run this once from browser or CLI to create rider-related tables and alter orders
include 'connection.php';
if (!$conn) die("No DB connection\n");

$sqls = [
    // riders table
    "CREATE TABLE IF NOT EXISTS tbl_riders (
        rider_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        vehicle_type VARCHAR(50) DEFAULT NULL,
        vehicle_number VARCHAR(50) DEFAULT NULL,
        latitude DECIMAL(10,7) DEFAULT NULL,
        longitude DECIMAL(10,7) DEFAULT NULL,
        is_online TINYINT(1) NOT NULL DEFAULT 0,
        status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    // order tracking
    "CREATE TABLE IF NOT EXISTS tbl_order_tracking (
        tracking_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        rider_id INT DEFAULT NULL,
        status ENUM('assigned','picked_up','out_for_delivery','delivered','cancelled','other') NOT NULL,
        message VARCHAR(255) DEFAULT NULL,
        latitude DECIMAL(10,7) DEFAULT NULL,
        longitude DECIMAL(10,7) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES tbl_orders(order_id) ON DELETE CASCADE,
        FOREIGN KEY (rider_id) REFERENCES tbl_riders(rider_id) ON DELETE SET NULL
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    // alter orders table
    "ALTER TABLE tbl_orders 
        ADD COLUMN IF NOT EXISTS rider_id INT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS delivery_status ENUM('not_assigned','assigned','picked_up','out_for_delivery','delivered') NOT NULL DEFAULT 'not_assigned'",
    // add foreign key later in case order already has data
];

foreach ($sqls as $q) {
    if (!mysqli_query($conn, $q)) {
        echo "Error executing: $q -- " . mysqli_error($conn) . "<br>";
    } else {
        echo "Executed: $q<br>";
    }
}

// add index / foreign key separately to avoid syntax issues on older MySQL
echo "\nAdding index/foreign key...\n";
mysqli_query($conn, "ALTER TABLE tbl_orders ADD INDEX (rider_id)");
mysqli_query($conn, "ALTER TABLE tbl_orders ADD CONSTRAINT fk_rider FOREIGN KEY (rider_id) REFERENCES tbl_riders(rider_id) ON DELETE SET NULL");

echo "Done";
