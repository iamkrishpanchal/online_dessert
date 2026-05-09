<?php
include 'connection.php';

// First, try to get image from another waffle product that has an image
$waffleResult = mysqli_query($conn, "SELECT product_image FROM tbl_products WHERE category_id = 13 AND product_image IS NOT NULL AND product_image != '' LIMIT 1");
$waffle = mysqli_fetch_assoc($waffleResult);

if ($waffle && !empty($waffle['product_image'])) {
    $imageFilename = $waffle['product_image'];
    
    // Update Nutella Waffle to use this image
    $updateResult = mysqli_query($conn, "UPDATE tbl_products SET product_image = '$imageFilename' WHERE product_name = 'Nutella Waffle'");
    
    if ($updateResult) {
        echo "✓ Success! Updated Nutella Waffle with waffle image.<br>";
        echo "Image filename: " . htmlspecialchars($imageFilename) . "<br>";
        echo "<a href='dashboard.php'>Back to Dashboard</a>";
    } else {
        echo "✗ Error updating product: " . mysqli_error($conn);
    }
} else {
    // If no waffle products have images, use the default waffle icon
    // The waffle.jpeg from the category is the fallback
    echo "No waffle product with image found. Waffles category uses: waffle.jpeg<br>";
    echo "Please upload a waffle image and try again, or manually assign: waffle.jpeg<br>";
    echo "<a href='dashboard.php'>Back to Dashboard</a>";
}

mysqli_close($conn);
?>
