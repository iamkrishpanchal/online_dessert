<?php
include 'connection.php';

// Check if table already exists
$checkTable = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME='tbl_orders'";
$result = mysqli_query($conn, $checkTable);

if (mysqli_num_rows($result) == 0) {
    // Table doesn't exist, create it
    $sql = "CREATE TABLE tbl_orders (
        order_id INT PRIMARY KEY AUTO_INCREMENT,
        order_number VARCHAR(50) UNIQUE NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        customer_email VARCHAR(100),
        customer_phone VARCHAR(20),
        product_id INT,
        quantity INT NOT NULL DEFAULT 1,
        order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        order_status ENUM('Pending', 'Confirmed', 'Dispatched', 'Completed', 'Cancelled', 'Rejected') DEFAULT 'Pending',
        total_amount DECIMAL(10, 2),
        payment_method ENUM('Cash', 'Online') DEFAULT 'Cash',
        notes TEXT,
        FOREIGN KEY (product_id) REFERENCES tbl_products(product_id) ON DELETE SET NULL
    )";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Orders table created successfully!');</script>";
        echo "Orders table created successfully!<br>";
    } else {
        echo "<script>alert('Error creating table: " . mysqli_error($conn) . "');</script>";
        echo "Error: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "<script>alert('Orders table already exists!');</script>";
    echo "Table already exists!<br>";
}

mysqli_close($conn);
?>
