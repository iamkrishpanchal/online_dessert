<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = mysqli_connect("localhost", "root", "", "online_dessert");

if (!$conn) {
    die("Connection Error: " . mysqli_connect_error());
}

echo "<h1 style='color: #8a3b0f;'>Vendor Login Status Fix</h1>";

// Show vendors BEFORE fix
echo "<h3>BEFORE FIX - Vendor Statuses:</h3>";
$beforeResult = mysqli_query($conn, "SELECT vendor_id, vendor_name, email, status FROM tbl_vendors ORDER BY vendor_id");
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr style='background: #ffccb3;'><th>ID</th><th>Name</th><th>Email</th><th>Status</th></tr>";

$inactiveCount = 0;
while ($row = mysqli_fetch_assoc($beforeResult)) {
    if ($row['status'] === 'inactive') {
        $inactiveCount++;
    }
    echo "<tr>";
    echo "<td>" . $row['vendor_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['vendor_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td style='background: #ffcccc;'>" . htmlspecialchars($row['status']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p style='color: red; font-weight: bold;'>⚠ Found $inactiveCount vendors with INACTIVE status - This blocks login!</p>";

// Apply fix - Set all vendors to active
echo "<h3>Applying Fix...</h3>";
$updateResult = mysqli_query($conn, "UPDATE tbl_vendors SET status = 'active' WHERE status = 'inactive'");

if ($updateResult) {
    $affectedRows = mysqli_affected_rows($conn);
    echo "<p style='color: green; font-weight: bold;'>✓ Successfully updated $affectedRows vendors to 'active' status!</p>";
} else {
    echo "<p style='color: red;'>✗ Error updating vendors: " . mysqli_error($conn) . "</p>";
}

// Show vendors AFTER fix
echo "<h3>AFTER FIX - Vendor Statuses:</h3>";
$afterResult = mysqli_query($conn, "SELECT vendor_id, vendor_name, email, status FROM tbl_vendors ORDER BY vendor_id");
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr style='background: #ccffcc;'><th>ID</th><th>Name</th><th>Email</th><th>Status</th></tr>";

$activeCount = 0;
while ($row = mysqli_fetch_assoc($afterResult)) {
    if ($row['status'] === 'active') {
        $activeCount++;
    }
    echo "<tr>";
    echo "<td>" . $row['vendor_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['vendor_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td style='background: #ccffcc;'>" . htmlspecialchars($row['status']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p style='color: green; font-weight: bold;'>✓ All $activeCount vendors are now ACTIVE and can log in!</p>";

echo "<h3 style='margin-top: 30px; color: #8a3b0f;'>Summary:</h3>";
echo "<ul style='font-size: 18px;'>";
echo "<li>✓ All vendors' status changed from 'inactive' to 'active'</li>";
echo "<li>✓ Vendors can now log in with their email and password</li>";
echo "<li>✓ Login page is at: <code>admin/vendor/login.php</code></li>";
echo "</ul>";

mysqli_close($conn);
?>
