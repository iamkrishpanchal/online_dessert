<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');
if (!$conn) { 
    echo 'Connection failed: ' . mysqli_connect_error(); 
    exit; 
}

echo "=== Products with Images ===\n\n";
$query = "SELECT product_id, product_name, product_image, vendor_id FROM tbl_products LIMIT 20";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Product: {$row['product_name']}\n";
        echo "  ID: {$row['product_id']}\n";
        echo "  Vendor ID: {$row['vendor_id']}\n";
        echo "  Image Path: {$row['product_image']}\n";
        
        // Try to find the image
        $img = $row['product_image'];
        $candidates = [
            $img, 
            'uploads/' . $img, 
            'uploads/vendors/' . $img, 
            'uploads/products/' . $img,
            '../uploads/' . $img,
            '../uploads/vendors/' . $img,
            '../uploads/products/' . $img,
            '../admin/uploads/' . $img,
            '../admin/vendor/uploads/' . $img,
            'uploads/' . basename($img),
            'uploads/vendors/' . basename($img),
            '../admin/vendor/uploads/' . basename($img)
        ];
        
        $found = 'NOT FOUND';
        foreach ($candidates as $cand) {
            $fullPath = 'c:/wamp64/www/Sem-6 Project/user/' . str_replace('/', '\\', $cand);
            if (file_exists($fullPath)) {
                $found = $cand;
                break;
            }
        }
        echo "  Status: $found\n";
        echo "\n";
    }
}

mysqli_close($conn);
?>
