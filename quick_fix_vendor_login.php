<?php
// Direct quick fix for vendor login status
$conn = mysqli_connect("localhost", "root", "", "online_dessert");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Ensure status column exists
$statusCheck = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'status'");
if (mysqli_num_rows($statusCheck) == 0) {
    mysqli_query($conn, "ALTER TABLE tbl_vendors ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
}

// Update ALL vendors to active
$sql = "UPDATE tbl_vendors SET status = 'active' WHERE 1=1";
mysqli_query($conn, $sql);

echo "<h2 style='color: green; text-align: center; padding: 20px; background: #f0f0f0; border-radius: 5px;'>";
echo "✓ FIXED! All vendors are now set to 'active' status.";
echo "</h2>";

echo "<p style='text-align: center; margin-top: 20px;'>";
echo "You can now go back and login as any vendor with their email and password.";
echo "</p>";

// Show result
$vendors = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_vendors WHERE status = 'active'");
$row = mysqli_fetch_assoc($vendors);
echo "<p style='text-align: center; color: green;'><strong>" . $row['total'] . " vendor(s)</strong> are now ACTIVE</p>";

mysqli_close($conn);

// Redirect after 3 seconds
echo "<script>
setTimeout(function() {
    window.location.href = 'admin/vendor/login.php';
}, 3000);
</script>";
?>
