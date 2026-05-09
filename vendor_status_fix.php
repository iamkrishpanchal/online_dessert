<?php
include 'admin/vendor/connection.php';

// Check and activate vendors with inactive status
$check_query = "SELECT vendor_id, vendor_name, email, status FROM tbl_vendors";
$result = mysqli_query($conn, $check_query);

echo "<h1>Vendor Status Check</h1>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Action</th></tr>";

if ($result && mysqli_num_rows($result) > 0) {
    while ($vendor = mysqli_fetch_assoc($result)) {
        $status = strtolower(trim($vendor['status'] ?? 'active'));
        echo "<tr>";
        echo "<td>" . $vendor['vendor_id'] . "</td>";
        echo "<td>" . htmlspecialchars($vendor['vendor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($vendor['email']) . "</td>";
        echo "<td><span style='color: " . ($status === 'active' ? 'green' : 'red') . ";'>" . ucfirst($status) . "</span></td>";
        
        if ($status !== 'active') {
            $vendor_id = $vendor['vendor_id'];
            echo "<td><a href='?activate=$vendor_id' style='background:green;color:white;padding:5px 10px;text-decoration:none;border-radius:3px;'>Activate</a></td>";
        } else {
            echo "<td><span style='color:green;'>✓ Active</span></td>";
        }
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No vendors found</td></tr>";
}
echo "</table>";

// Handle activation
if (isset($_GET['activate'])) {
    $vendor_id = intval($_GET['activate']);
    $update = "UPDATE tbl_vendors SET status = 'active' WHERE vendor_id = $vendor_id";
    if (mysqli_query($conn, $update)) {
        echo "<h2 style='color: green;'>✓ Vendor #$vendor_id activated successfully!</h2>";
        echo "<p><a href='vendor_status_fix.php'>Click here to refresh</a></p>";
    } else {
        echo "<h2 style='color: red;'>Error activating vendor</h2>";
    }
}
?>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { color: #333; }
    table { border-collapse: collapse; width: 100%; max-width: 800px; }
    th { background: #f0f0f0; padding: 12px; text-align: left; }
    td { padding: 10px; }
    tr:hover { background: #f9f9f9; }
    a { cursor: pointer; }
</style>