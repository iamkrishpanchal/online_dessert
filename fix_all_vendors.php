<?php
include 'admin/vendor/connection.php';

// First, check if status column even exists
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'status'");
if (!$check_column || mysqli_num_rows($check_column) == 0) {
    echo "<h2 style='color: red;'>ERROR: Status column doesn't exist!</h2>";
    echo "<p>Adding status column...</p>";
    $add_column = "ALTER TABLE tbl_vendors ADD COLUMN status ENUM('active','inactive','pending') NOT NULL DEFAULT 'active'";
    if (mysqli_query($conn, $add_column)) {
        echo "<p style='color: green;'>✓ Status column added successfully!</p>";
    } else {
        echo "<p style='color: red;'>Failed to add status column: " . mysqli_error($conn) . "</p>";
    }
}

// Now update ALL vendors to active status
echo "<h2>Fixing Vendor Statuses...</h2>";
$update = "UPDATE tbl_vendors SET status = 'active' WHERE status IS NULL OR status = '' OR status = 'inactive'";
$result = mysqli_query($conn, $update);

if ($result) {
    $affected = mysqli_affected_rows($conn);
    echo "<p style='color: green;'><strong>✓ Updated $affected vendors to ACTIVE status!</strong></p>";
} else {
    echo "<p style='color: red;'>Error updating vendors: " . mysqli_error($conn) . "</p>";
}

// Show all vendors now
echo "<h2>All Vendors - Current Status:</h2>";
$vendors = mysqli_query($conn, "SELECT vendor_id, vendor_name, email, status FROM tbl_vendors ORDER BY vendor_id");
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; font-family: Arial;'>";
echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Status</th></tr>";

if ($vendors && mysqli_num_rows($vendors) > 0) {
    while ($vendor = mysqli_fetch_assoc($vendors)) {
        $status = $vendor['status'] ?? 'NO_STATUS';
        $color = ($status === 'active') ? 'green' : 'orange';
        echo "<tr>";
        echo "<td>" . $vendor['vendor_id'] . "</td>";
        echo "<td>" . htmlspecialchars($vendor['vendor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($vendor['email']) . "</td>";
        echo "<td><span style='color: " . $color . "; font-weight: bold;'>" . $status . "</span></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='4'>No vendors found</td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><strong>✓ All vendors should now be able to login!</strong></p>";
echo "<p><a href='admin/vendor/login.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Go to Vendor Login</a></p>";
?>
