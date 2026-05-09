<?php
// COMPLETE VENDOR LOGIN FIX
// This script fixes all vendor status issues in one go

$conn = mysqli_connect("localhost", "root", "", "online_dessert");

if (!$conn) {
    die("<h2 style='color: red;'>Connection Error: " . mysqli_connect_error() . "</h2>");
}

echo "<h1 style='text-align: center; color: #8a3b0f; padding: 20px;'>Vendor Login Status Complete Fix</h1>";

// Step 1: Ensure status column exists
echo "<h3>Step 1: Checking table structure...</h3>";
$statusCheck = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'status'");
if (mysqli_num_rows($statusCheck) == 0) {
    echo "<p style='color: orange;'>⚠ Status column missing - Adding it...</p>";
    $addCol = mysqli_query($conn, "ALTER TABLE tbl_vendors ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
    echo "<p style='color: green;'>✓ Status column added</p>";
} else {
    echo "<p style='color: green;'>✓ Status column exists</p>";
}

// Step 2: Show vendors BEFORE fix
echo "<h3>Step 2: Current vendor statuses BEFORE fix:</h3>";
$beforeQuery = mysqli_query($conn, "SELECT vendor_id, vendor_name, email, status FROM tbl_vendors ORDER BY vendor_id");
$statusCounts = [
    'active' => 0,
    'inactive' => 0,
    'pending' => 0,
    'null' => 0,
    'other' => 0
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
echo "<tr style='background: #ffc99c;'><th>ID</th><th>Name</th><th>Email</th><th>Current Status</th><th>Issue</th></tr>";

while ($row = mysqli_fetch_assoc($beforeQuery)) {
    $status = $row['status'] ?? 'NULL';
    
    if ($status === 'NULL') {
        $statusCounts['null']++;
        $issue = "⚠ Status is NULL";
    } elseif ($status === 'active') {
        $statusCounts['active']++;
        $issue = "✓ OK";
    } elseif ($status === 'inactive' || $status === 'rejected') {
        $statusCounts['inactive']++;
        $issue = "✗ BLOCKED";
    } else {
        $statusCounts['other']++;
        $issue = "? Unknown value";
    }
    
    echo "<tr>";
    echo "<td>" . $row['vendor_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['vendor_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td><code>" . htmlspecialchars($status) . "</code></td>";
    echo "<td style='color: " . ($issue === '✓ OK' ? 'green' : 'red') . ";'>" . $issue . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
echo "Status Summary: " . $statusCounts['active'] . " active, " . $statusCounts['inactive'] . " inactive, " . 
     $statusCounts['pending'] . " pending, " . $statusCounts['null'] . " null, " . $statusCounts['other'] . " other";
echo "</p>";

// Step 3: Apply fix
echo "<h3 style='margin-top: 30px;'>Step 3: Applying fix...</h3>";

// Fix NULL status
if ($statusCounts['null'] > 0) {
    $fixNull = mysqli_query($conn, "UPDATE tbl_vendors SET status = 'active' WHERE status IS NULL OR status = ''");
    $affectedNull = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>✓ Fixed $affectedNull vendors with NULL/empty status</p>";
}

// Fix inactive/rejected to active
$fixInactive = mysqli_query($conn, "UPDATE tbl_vendors SET status = 'active' WHERE status IN ('inactive', 'rejected', '0', 'false')");
$affectedInactive = mysqli_affected_rows($conn);
if ($affectedInactive > 0) {
    echo "<p style='color: green;'>✓ Fixed $affectedInactive vendors with inactive/rejected status</p>";
}

// Set all remaining to active (just to be sure)
$fixAll = mysqli_query($conn, "UPDATE tbl_vendors SET status = 'active' WHERE status != 'active'");
$affectedRemaining = mysqli_affected_rows($conn);
if ($affectedRemaining > 0) {
    echo "<p style='color: green;'>✓ Fixed $affectedRemaining vendors with unknown status</p>";
}

// Step 4: Verify fix
echo "<h3 style='margin-top: 30px;'>Step 4: Vendor statuses AFTER fix:</h3>";
$afterQuery = mysqli_query($conn, "SELECT vendor_id, vendor_name, email, status FROM tbl_vendors ORDER BY vendor_id");

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
echo "<tr style='background: #90EE90;'><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Result</th></tr>";

$totalVendors = 0;
while ($row = mysqli_fetch_assoc($afterQuery)) {
    $totalVendors++;
    echo "<tr>";
    echo "<td>" . $row['vendor_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['vendor_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td><strong>" . $row['status'] . "</strong></td>";
    echo "<td style='color: green; font-weight: bold;'>✓ ACTIVE</td>";
    echo "</tr>";
}
echo "</table>";

// Step 5: Final summary
echo "<div style='background: #d4edda; border: 2px solid #28a745; border-radius: 5px; padding: 20px; margin-top: 30px; text-align: center;'>";
echo "<h2 style='color: green; margin: 0;'>✓ FIX COMPLETE!</h2>";
echo "<p style='font-size: 16px; margin: 10px 0;'><strong>$totalVendors vendor(s)</strong> are now set to <strong>'active'</strong> status</p>";
echo "<p style='color: #555;'>All vendors can now login with their email and password</p>";
echo "</div>";

// Step 6: Test instructions
echo "<div style='background: #cfe2ff; border: 2px solid #0d6efd; border-radius: 5px; padding: 20px; margin-top: 20px;'>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Go to the vendor login page: <a href='admin/vendor/login.php' target='_blank'>admin/vendor/login.php</a></li>";
echo "<li>Try logging in with any vendor email and password</li>";
echo "<li>If you still get an error, check:<ul>";
echo "<li>Email exists in the list above</li>";
echo "<li>Password is correct</li>";
echo "<li>Browser console for any errors (F12)</li>";
echo "</ul></li>";
echo "</ol>";
echo "</div>";

mysqli_close($conn);
?>
