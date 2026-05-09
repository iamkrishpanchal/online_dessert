<?php
include 'admin/vendor/connection.php';

// Fix 1: Set all vendors to 'active' status
$sql = "UPDATE tbl_vendors SET status = 'active' WHERE status IS NULL OR status = '' OR status = 'inactive' OR status = 'rejected'";
$result = mysqli_query($conn, $sql);

if ($result) {
    $affected = mysqli_affected_rows($conn);
    echo "<div style='background: lightgreen; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>SUCCESS:</strong> Updated $affected vendor(s) to 'active' status<br>";
    echo "All vendors should now be able to login.";
    echo "</div>";
} else {
    echo "<div style='background: lightcoral; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>ERROR:</strong> " . mysqli_error($conn);
    echo "</div>";
}

// Show updated vendor statuses
echo "<h2>Updated Vendor Status</h2>";
$query = "SELECT vendor_id, vendor_name, email, status FROM tbl_vendors ORDER BY vendor_id";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Vendor ID</th><th>Name</th><th>Email</th><th>Status</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['vendor_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['vendor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td style='color: green; font-weight: bold;'>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

mysqli_close($conn);
?>
