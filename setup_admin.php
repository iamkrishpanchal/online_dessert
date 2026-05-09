<?php
// Setup admin account
$conn = mysqli_connect("localhost", "root", "", "online_dessert");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected to database successfully!<br>";

// Check if tbl_admin table exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_admin'");
if (mysqli_num_rows($check_table) > 0) {
    echo "tbl_admin table exists<br>";
} else {
    echo "tbl_admin table does NOT exist<br>";
    exit;
}

// Check if admin record already exists
$check_admin = mysqli_query($conn, "SELECT * FROM tbl_admin WHERE admin_email = 'admin@dessert.com'");
if (mysqli_num_rows($check_admin) > 0) {
    echo "Admin account already exists<br>";
} else {
    // Insert admin account
    $insert_query = "INSERT INTO tbl_admin (admin_name, admin_email, admin_password, admin_contact) 
                     VALUES ('Admin', 'admin@dessert.com', 'admin123', '1234567890')";
    
    if (mysqli_query($conn, $insert_query)) {
        echo "Admin account created successfully!<br>";
        echo "Email: admin@dessert.com<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Error creating admin account: " . mysqli_error($conn);
    }
}

// Display all admin accounts
echo "<br>Current admin accounts:<br>";
$result = mysqli_query($conn, "SELECT * FROM tbl_admin");
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: " . $row['admin_id'] . " | Email: " . $row['admin_email'] . " | Name: " . $row['admin_name'] . "<br>";
}

mysqli_close($conn);
?>
