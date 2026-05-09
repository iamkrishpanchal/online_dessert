<?php
include 'session.php';
include 'connection.php';

// Delete all vendors
$query = "DELETE FROM tbl_vendors";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "✅ All vendors deleted successfully!<br>";
    echo "<a href='vendor_detail.php'>Back to Vendor Details</a>";
} else {
    echo "❌ Error deleting vendors: " . mysqli_error($conn) . "<br>";
    echo "<a href='vendor_detail.php'>Back to Vendor Details</a>";
}

mysqli_close($conn);
?>
