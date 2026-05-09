<?php
include 'connection.php';

// Create vendors table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS tbl_vendors (
    vendor_id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_name VARCHAR(255) NOT NULL,
    email VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Vendors table created successfully.";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>
