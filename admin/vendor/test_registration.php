<?php
include 'connection.php';

// Simulate POST data
$_POST['vendor_name'] = 'Test Vendor';
$_POST['email'] = 'test@example.com';
$_POST['password'] = 'password123';
$_POST['phone'] = '1234567890';
$_POST['address'] = 'Test Address';

// Include the registration logic from register.php
if (isset($_POST['register']) || true) { // Force execution for testing
    $vendor_name = $_POST['vendor_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Create vendors table if it doesn't exist
    $createTableSql = "CREATE TABLE IF NOT EXISTS tbl_vendors (
        vendor_id INT AUTO_INCREMENT PRIMARY KEY,
        vendor_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (mysqli_query($conn, $createTableSql)) {
        echo "Table creation attempted.\n";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "\n";
    }

    $sql = "INSERT INTO tbl_vendors (vendor_name, email, password, phone, address) VALUES ('$vendor_name', '$email', '$password', '$phone', '$address')";

    if (mysqli_query($conn, $sql)) {
        echo "Registration successful!\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}

mysqli_close($conn);
?>
