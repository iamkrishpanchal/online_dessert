<?php
include 'session.php';
include 'connection.php';

// Delete all vendors
$query = "DELETE FROM tbl_vendors";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "✅ All vendors deleted successfully!<br>";
    echo "<a href='dashboard.php'>Back to Dashboard</a>";
} else {
    echo "❌ Error deleting vendors: " . mysqli_error($conn) . "<br>";
    echo "<a href='dashboard.php'>Back to Dashboard</a>";
}

mysqli_close($conn);
?>
