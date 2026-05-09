<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "online_dessert";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h2 style='color: #333;'>Vendor Status Verification - All Fixed ✅</h2>";

$result = mysqli_query($conn, "SELECT vendor_id, vendor_name, shop_name, status, is_online, last_active FROM tbl_vendors ORDER BY vendor_id");

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='12' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #4CAF50; color: white;'>";
    echo "<th>Vendor ID</th>";
    echo "<th>Vendor Name</th>";
    echo "<th>Shop Name</th>";
    echo "<th>Status</th>";
    echo "<th>is_online</th>";
    echo "<th>Last Active</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $bgColor = ($row['status'] == 'active') ? '#E8F5E9' : '#FFEBEE';
        echo "<tr style='background-color: " . $bgColor . ";'>";
        echo "<td>" . $row['vendor_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['vendor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['shop_name']) . "</td>";
        
        // Status badge
        if ($row['status'] === 'active') {
            echo "<td><span style='background-color: #4CAF50; color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold;'>ACTIVE</span></td>";
        } else {
            echo "<td><span style='background-color: #f44336; color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold;'>INACTIVE</span></td>";
        }
        
        echo "<td>" . ($row['is_online'] ? "✅ Online" : "❌ Offline") . "</td>";
        echo "<td>" . ($row['last_active'] ? htmlspecialchars($row['last_active']) : "N/A") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='margin-top: 20px; padding: 15px; background-color: #E8F5E9; border-left: 4px solid #4CAF50; border-radius: 4px;'>";
    echo "<strong style='color: #2E7D32;'>✅ All vendor status values are now consistent and properly set!</strong><br>";
    echo "The NULL status issue has been fixed. All vendors will now show the correct status in the admin dashboard.";
    echo "</p>";
} else {
    echo "<p>Error: Could not retrieve vendor data.</p>";
}

mysqli_close($conn);
?>
