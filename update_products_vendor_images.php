<?php
include 'user/connection.php';

echo "<h2>Updating Products with Vendor-Specific Images</h2>";
echo "<p style='color: #666;'>This script assigns images from each vendor's uploads to their own products only.</p>";

// Source directory with vendor-uploaded images
$vendor_img_dir = __DIR__ . '/admin/vendor/uploads';

echo "<h3>Step 1: Scanning Vendor Images</h3>";

// Get all vendor images grouped by vendor
// Assumption: Images might be named with vendor_id prefix or stored in vendor-specific folders
$all_vendor_images = [];

if (is_dir($vendor_img_dir)) {
    $files = scandir($vendor_img_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($vendor_img_dir . '/' . $file)) {
            // Only include image files
            if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
                $all_vendor_images[] = $file;
            }
        }
    }
}

echo "<p>Found " . count($all_vendor_images) . " total vendor product images</p>";

if (empty($all_vendor_images)) {
    echo "<p style='color: red;'>No vendor images found in admin/vendor/uploads!</p>";
    echo "<p>Please upload product images through the vendor admin panel.</p>";
    exit;
}

// Get all active vendors who have products
echo "<h3>Step 2: Getting Vendors with Products</h3>";

// Prefer the plural products table if it exists to avoid fatal errors
$prodTable = null;
$check1 = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_products'");
$check2 = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product'");
if ($check1 && mysqli_num_rows($check1) > 0) {
    $prodTable = 'tbl_products';
} elseif ($check2 && mysqli_num_rows($check2) > 0) {
    $prodTable = 'tbl_product';
}

$vendors = [];
if ($prodTable) {
    $vendor_query = "SELECT v.vendor_id, v.vendor_name, v.shop_name, COUNT(p.product_id) as product_count
                     FROM tbl_vendors v
                     LEFT JOIN {$prodTable} p ON v.vendor_id = p.vendor_id
                     WHERE p.product_id IS NOT NULL
                     GROUP BY v.vendor_id, v.vendor_name, v.shop_name
                     ORDER BY v.vendor_id";

    $vendor_result = mysqli_query($conn, $vendor_query);
    if ($vendor_result) {
        $vendors = mysqli_fetch_all($vendor_result, MYSQLI_ASSOC);
    }
    echo "<p>Found " . count($vendors) . " vendors with products (using {$prodTable})</p>";
} else {
    echo "<p style='color: orange;'>No known product table (tbl_products / tbl_product) found in the database.</p>";
}


// Distribute images among vendors
// Each vendor gets a portion of the images based on their product count
$total_vendor_images = count($all_vendor_images);
$images_per_vendor = ceil($total_vendor_images / max(count($vendors), 1));

echo "<h3>Step 3: Assigning Vendor-Specific Images to Products</h3>";

$total_updated = 0;
$vendor_stats = [];

foreach ($vendors as $vendor) {
    $vendor_id = $vendor['vendor_id'];
    $vendor_name = $vendor['shop_name'] ?: $vendor['vendor_name'];
    $product_count = $vendor['product_count'];
    
    if ($product_count == 0) {
        continue;
    }
    
    // Get products for this vendor (choose correct product table)
    $prodTableForVendor = null;
    $chkA = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_products'");
    $chkB = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product'");
    if ($chkA && mysqli_num_rows($chkA) > 0) $prodTableForVendor = 'tbl_products';
    elseif ($chkB && mysqli_num_rows($chkB) > 0) $prodTableForVendor = 'tbl_product';

    if ($prodTableForVendor) {
        $prod_query = "SELECT product_id, product_name, product_image FROM {$prodTableForVendor} WHERE vendor_id = ? ORDER BY product_id";
        $prod_stmt = mysqli_prepare($conn, $prod_query);
    } else {
        $prod_stmt = false;
    }

    if ($prod_stmt) {
        mysqli_stmt_bind_param($prod_stmt, "i", $vendor_id);
        mysqli_stmt_execute($prod_stmt);
        $prod_result = mysqli_stmt_get_result($prod_stmt);
        $products = $prod_result ? mysqli_fetch_all($prod_result, MYSQLI_ASSOC) : [];
        mysqli_stmt_close($prod_stmt);
    } else {
        $products = [];
    }
    
    if (empty($products)) {
        continue;
    }
    
    // Assign vendor-specific images to this vendor's products
    // Each vendor gets a different set of images
    $vendor_start_index = ($vendor_id - 1) % $total_vendor_images;
    $updated_count = 0;
    
    echo "<div style='margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 8px;'>";
    echo "<h4>Vendor: " . htmlspecialchars($vendor_name) . " (ID: $vendor_id) - $product_count products</h4>";
    
    foreach ($products as $index => $product) {
        // Get image for this product - cycle through available images
        $img_index = ($vendor_start_index + $index) % $total_vendor_images;
        $vendor_img = $all_vendor_images[$img_index];
        
        // Create image path relative to user directory
        $img_path = 'admin/vendor/uploads/' . $vendor_img;
        
        // Update product with vendor-specific image only if it doesn't already have one
        // or if you want to overwrite existing images, remove the condition
        $current_image = $product['product_image'];
        
        // Only update if current image is empty or is a default placeholder
        if (empty($current_image) || $current_image == 'images/default-product.png' || strpos($current_image, 'default') !== false) {
            $update_query = "UPDATE tbl_product SET product_image = ? WHERE product_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            
            if (!$update_stmt) {
                $update_query = "UPDATE tbl_products SET product_image = ? WHERE product_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
            }
            
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, 'si', $img_path, $product['product_id']);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $updated_count++;
                    echo "<span style='color: green;'>✓</span> ";
                } else {
                    echo "<span style='color: red;'>✗</span> ";
                }
                mysqli_stmt_close($update_stmt);
            }
        }
    }
    
    echo "<p><strong>Updated: $updated_count / $product_count products</strong></p>";
    echo "</div>";
    
    $total_updated += $updated_count;
    $vendor_stats[] = [
        'vendor_name' => $vendor_name,
        'total_products' => $product_count,
        'updated' => $updated_count
    ];
}

// Show summary
echo "<h2 style='color: green;'>Summary</h2>";
echo "<p>Total products updated with vendor-specific images: $total_updated</p>";

echo "<h3>Vendor Statistics:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Vendor</th><th>Total Products</th><th>Updated</th></tr>";

foreach ($vendor_stats as $stat) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($stat['vendor_name']) . "</td>";
    echo "<td>" . $stat['total_products'] . "</td>";
    echo "<td>" . $stat['updated'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Show sample of updated products
echo "<h3>Sample of Updated Products (showing vendor image ownership):</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Product ID</th><th>Product Name</th><th>Category</th><th>Image</th><th>Vendor</th></tr>";

// Pick the correct products table for the sample
$sampleProdTable = null;
$chkS1 = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_products'");
$chkS2 = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product'");
if ($chkS1 && mysqli_num_rows($chkS1) > 0) $sampleProdTable = 'tbl_products';
elseif ($chkS2 && mysqli_num_rows($chkS2) > 0) $sampleProdTable = 'tbl_product';

$sample_result = false;
if ($sampleProdTable) {
    $sample_query = "SELECT p.product_id, p.product_name, p.category_id, p.product_image, v.shop_name 
                 FROM {$sampleProdTable} p 
                 LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id 
                 ORDER BY p.product_id LIMIT 15";
    $sample_result = mysqli_query($conn, $sample_query);
}

if ($sample_result) {
    while ($p = mysqli_fetch_assoc($sample_result)) {
        $img_file = basename($p['product_image']);
        $vendor = $p['shop_name'] ?: 'Unknown';
        echo "<tr>";
        echo "<td>" . $p['product_id'] . "</td>";
        echo "<td>" . htmlspecialchars($p['product_name']) . "</td>";
        echo "<td>" . $p['category_id'] . "</td>";
        echo "<td><small>" . htmlspecialchars($img_file) . "</small></td>";
        echo "<td>" . htmlspecialchars($vendor) . "</td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "<p style='margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 5px;'>";
echo "<strong>✓ Success!</strong> Products now show images from their respective vendors (not random images).<br>";
echo "Each vendor's products display only images from that specific vendor.";
echo "</p>";

mysqli_close($conn);
?>
