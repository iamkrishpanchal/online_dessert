<?php
include 'connection.php';

// Check what vendors are in the database
$query = "SELECT vendor_id, vendor_name, shop_name, image_path FROM tbl_vendors LIMIT 5";
$result = mysqli_query($conn, $query);

echo "<h2>Vendors in Database:</h2>";
echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>Vendor ID</th><th>Name</th><th>Shop</th><th>Image Path</th><th>File Exists?</th></tr>";

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $img_path = trim($row['image_path']);
        $file_exists = '';
        
        if (!empty($img_path)) {
            $full_path = __DIR__ . '/../uploads/vendors/' . $img_path;
            $file_exists = file_exists($full_path) ? '✓ YES' : '✗ NO';
        } else {
            $file_exists = 'Empty';
        }
        
        echo "<tr>";
        echo "<td>" . $row['vendor_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['vendor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['shop_name']) . "</td>";
        echo "<td>" . htmlspecialchars($img_path) . "</td>";
        echo "<td>" . $file_exists . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No vendors found</td></tr>";
}

echo "</table>";

// List files in vendors directory
echo "<h2>Files in admin/uploads/vendors/:</h2>";
$vendorsDir = __DIR__ . '/../uploads/vendors';
if (is_dir($vendorsDir)) {
    $files = scandir($vendorsDir);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<li>" . htmlspecialchars($file) . "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "Directory does not exist";
}
?>
