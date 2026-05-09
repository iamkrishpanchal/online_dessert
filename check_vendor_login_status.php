<?php
include 'admin/vendor/connection.php';

echo "<h2>Vendor Status Check</h2>";

$query = "SELECT vendor_id, vendor_name, email, status, password FROM tbl_vendors ORDER BY vendor_id";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Vendor ID</th><th>Name</th><th>Email</th><th>Status</th><th>Password Hash</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $status = $row['status'] ?? 'N/A';
        $statusColor = ($status === 'active') ? 'green' : (($status === 'pending') ? 'orange' : 'red');
        
        echo "<tr>";
        echo "<td>" . $row['vendor_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['vendor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td style='color: " . $statusColor . "; font-weight: bold;'>" . $status . "</td>";
        echo "<td>" . substr($row['password'], 0, 20) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No vendors found in database.";
}

mysqli_close($conn);
?>
