<?php
include 'connection.php';

// Get the image from Chocolate Chip Cookies
$chipCookieResult = mysqli_query($conn, "SELECT product_image FROM tbl_products WHERE product_name = 'Chocolate Chip Cookies' LIMIT 1");
$chipCookie = mysqli_fetch_assoc($chipCookieResult);

if ($chipCookie && !empty($chipCookie['product_image'])) {
    $imageFilename = $chipCookie['product_image'];
    
    // Update all Chocolate Cookie products to use this image
    $updateResult = mysqli_query($conn, "UPDATE tbl_products SET product_image = '$imageFilename' WHERE product_name = 'Chocolate Cookie'");
    
    if ($updateResult) {
        echo "✓ Success! Updated all Chocolate Cookie products with the Chocolate Chip Cookies image.<br>";
        echo "Image filename: " . htmlspecialchars($imageFilename) . "<br>";
        echo "<a href='vendor_detail.php'>Back to Vendors</a>";
    } else {
        echo "✗ Error updating products: " . mysqli_error($conn);
    }
} else {
    echo "✗ Chocolate Chip Cookies product not found or has no image.";
}

mysqli_close($conn);
?>
