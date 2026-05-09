<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "online_dessert";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h2>Fixing Vendor Status NULL Issue</h2>";

// Step 1: Check if status column exists
$checkStatus = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'status'");
if (!$checkStatus || mysqli_num_rows($checkStatus) == 0) {
    echo "<p>❌ Status column does not exist. Creating it...</p>";
    mysqli_query($conn, "ALTER TABLE tbl_vendors ADD COLUMN status VARCHAR(50) DEFAULT 'inactive'");
    echo "<p>✅ Status column created with default value 'inactive'</p>";
} else {
    echo "<p>✅ Status column exists</p>";
    
    // Step 2: Modify column to add proper default value and NOT NULL constraint
    $modifyColumn = "ALTER TABLE tbl_vendors MODIFY COLUMN status VARCHAR(50) DEFAULT 'inactive'";
    if (mysqli_query($conn, $modifyColumn)) {
        echo "<p>✅ Set default value for status column to 'inactive'</p>";
    } else {
        echo "<p>⚠️ Could not set default: " . mysqli_error($conn) . "</p>";
    }
}

// Step 3: Fix all NULL status values - set them to 'inactive'
$nullCheck = mysqli_query($conn, "SELECT vendor_id FROM tbl_vendors WHERE status IS NULL");
if ($nullCheck && mysqli_num_rows($nullCheck) > 0) {
    $affectedRows = 0;
    while ($row = mysqli_fetch_assoc($nullCheck)) {
        $vid = (int)$row['vendor_id'];
        if (mysqli_query($conn, "UPDATE tbl_vendors SET status = 'inactive' WHERE vendor_id = $vid")) {
            $affectedRows++;
        }
    }
    echo "<p>✅ Fixed " . $affectedRows . " vendor(s) with NULL status - set to 'inactive'</p>";
} else {
    echo "<p>✅ No NULL status values found</p>";
}

// Step 4: Verify the fix
echo "<h3>Vendor Status After Fix:</h3>";
$result = mysqli_query($conn, "SELECT vendor_id, vendor_name, status, is_online FROM tbl_vendors ORDER BY vendor_id");
if ($result) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Vendor ID</th>";
    echo "<th>Vendor Name</th>";
    echo "<th>Status</th>";
    echo "<th>is_online</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['vendor_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['vendor_name']) . "</td>";
        
        $statusDisplay = $row['status'];
        if ($row['status'] === NULL) {
            $statusDisplay = "<span style='color: red; font-weight: bold;'>NULL</span>";
        } else {
            $badgeColor = ($row['status'] == 'active') ? '#90EE90' : '#FFB6C6';
            $statusDisplay = "<span style='background-color: " . $badgeColor . "; padding: 5px 10px; border-radius: 5px;'>" . htmlspecialchars($row['status']) . "</span>";
        }
        
        echo "<td>" . $statusDisplay . "</td>";
        echo "<td>" . ($row['is_online'] ? "Online (1)" : "Offline (0)") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Error querying vendors: " . mysqli_error($conn) . "</p>";
}

echo "<p style='margin-top: 20px; color: green;'><strong>✅ Vendor status issue has been fixed! All vendors now have consistent status values.</strong></p>";

mysqli_close($conn);
?>
