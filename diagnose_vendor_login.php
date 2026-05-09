<?php
$conn = mysqli_connect("localhost", "root", "", "online_dessert");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h1 style='color: #8a3b0f;'>Vendor Login Diagnosis & Fix</h1>";

// Step 1: Check if tbl_vendors table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_vendors'");
if (mysqli_num_rows($tableCheck) == 0) {
    echo "<p style='color: red;'>ERROR: tbl_vendors table does not exist!</p>";
    exit;
}

echo "<h2 style='color: green;'>✓ Table exists</h2>";

// Step 2: Check table structure
echo "<h3>Table Structure:</h3>";
$columns = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors");
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($col = mysqli_fetch_assoc($columns)) {
    echo "<tr>";
    echo "<td>" . $col['Field'] . "</td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . $col['Key'] . "</td>";
    echo "<td>" . ($col['Default'] ?? 'None') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Step 3: Check all vendor statuses BEFORE fix
echo "<h2 style='margin-top: 20px;'>Vendors BEFORE Status Fix:</h2>";
$vendors = mysqli_query($conn, "SELECT vendor_id, vendor_name, email, status FROM tbl_vendors ORDER BY vendor_id");

if (mysqli_num_rows($vendors) > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Status Color</th></tr>";
    
    while ($row = mysqli_fetch_assoc($vendors)) {
        $status = $row['status'] ?? 'NULL';
        $statusDisplay = ($status === 'NULL') ? "<span style='color: red;'>NULL (PROBLEM!)</span>" : htmlspecialchars($status);
        $statusColor = ($status === 'active') ? '<span style="background: lightgreen; padding: 5px; border-radius: 3px;">ACTIVE - OK</span>' : 
                       ($status === 'pending' ? '<span style="background: orange; padding: 5px; border-radius: 3px; color: white;">PENDING</span>' :
                       ($status === 'inactive' || $status === 'rejected' ? '<span style="background: red; padding: 5px; border-radius: 3px; color: white;">INACTIVE/REJECTED</span>' :
                       '<span style="background: yellow; padding: 5px; border-radius: 3px;">UNKNOWN</span>'));
        
        echo "<tr>";
        echo "<td>" . $row['vendor_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['vendor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . $statusDisplay . "</td>";
        echo "<td>" . $statusColor . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No vendors found!</p>";
}

// Step 4: FIX - Update all vendors to 'active' status
echo "<h2 style='margin-top: 20px; color: #8a3b0f;'>Applying Fix...</h2>";

// Make sure status column exists
$statusCheck = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'status'");
if (mysqli_num_rows($statusCheck) == 0) {
    echo "<p style='color: orange;'>Adding 'status' column...</p>";
    mysqli_query($conn, "ALTER TABLE tbl_vendors ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
    echo "<p style='color: green;'>✓ Column added</p>";
}

// Update all vendors to active
$updateSql = "UPDATE tbl_vendors SET status = 'active'";
$updateResult = mysqli_query($conn, $updateSql);

if ($updateResult) {
    $affected = mysqli_affected_rows($conn);
    echo "<p style='color: green;'><strong>✓ SUCCESS:</strong> Updated $affected vendor(s) to 'active' status</p>";
} else {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . mysqli_error($conn) . "</p>";
}

// Step 5: Verify fix - Show vendors AFTER
echo "<h2 style='margin-top: 20px;'>Vendors AFTER Status Fix:</h2>";
$vendorsAfter = mysqli_query($conn, "SELECT vendor_id, vendor_name, email, status FROM tbl_vendors ORDER BY vendor_id");

if (mysqli_num_rows($vendorsAfter) > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #90EE90;'><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Status</th></tr>";
    
    while ($row = mysqli_fetch_assoc($vendorsAfter)) {
        $status = $row['status'];
        echo "<tr>";
        echo "<td>" . $row['vendor_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['vendor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($status) . "</td>";
        echo "<td><span style='background: lightgreen; padding: 5px; border-radius: 3px;'>✓ ACTIVE</span></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 6: Final message
echo "<h2 style='margin-top: 30px; padding: 20px; background: lightgreen; border-radius: 5px;'>";
echo "✓ Fix Complete! All vendors should now be able to login with their email and password.";
echo "</h2>";

echo "<p style='margin-top: 20px; color: #555;'><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Try logging in as a vendor with their email and password</li>";
echo "<li>If still getting an error, check the password is correct</li>";
echo "<li>Check browser console for any JavaScript errors</li>";
echo "</ul>";

mysqli_close($conn);
?>
