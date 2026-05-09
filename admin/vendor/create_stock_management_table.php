<?php
include 'session.php';
include 'connection.php';

// Check if table already exists
$checkTable = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME='tbl_stock_management'";
$result = mysqli_query($conn, $checkTable);

if (mysqli_num_rows($result) == 0) {
    // Table doesn't exist, create it
    $sql = "CREATE TABLE tbl_stock_management (
        stock_id INT PRIMARY KEY AUTO_INCREMENT,
        product_id INT NOT NULL,
        quantity_added INT NOT NULL DEFAULT 0,
        previous_quantity INT NOT NULL DEFAULT 0,
        new_quantity INT NOT NULL DEFAULT 0,
        stock_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        created_by INT,
        FOREIGN KEY (product_id) REFERENCES tbl_products(product_id) ON DELETE CASCADE
    )";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Stock management table created successfully!');</script>";
        echo "Stock management table created successfully!<br>";
    } else {
        echo "<script>alert('Error creating table: " . mysqli_error($conn) . "');</script>";
        echo "Error: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "<script>alert('Stock management table already exists!');</script>";
    echo "Table already exists!<br>";
}

mysqli_close($conn);
?>
