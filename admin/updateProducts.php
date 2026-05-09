<?php
include 'session.php';
include 'connection.php';

$message = '';

if ($_POST) {
    // Delete Chocolate Cakes from category 14
    mysqli_query($conn, "DELETE FROM tbl_products WHERE product_name LIKE '%Chocolate Cake%' AND category_id = 14");
    
    // Get first vendor
    $vendorResult = mysqli_query($conn, "SELECT vendor_id FROM tbl_vendors LIMIT 1");
    $vendor = mysqli_fetch_assoc($vendorResult);
    $vendor_id = $vendor['vendor_id'] ?? 1;
    
    // Handle image upload
    $imageFilename = '';
    if (!empty($_FILES['product_image']['name'])) {
        $exe = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $imageFilename = time() . '.' . $exe;
        $dest = './vendor/uploads/' . $imageFilename;
        if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $dest)) {
            $message = "Error uploading image";
        }
    }
    
    if (empty($message)) {
        // Add Blueberry Cake
        $name = 'Blueberry Cake';
        $price = 450;
        $category = 14;
        $stock = 10;
        
        $sql = "INSERT INTO tbl_products (product_name, product_price, category_id, product_image, stock, vendor_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'siisii', $name, $price, $category, $imageFilename, $stock, $vendor_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "✓ Chocolate Cake removed and Blueberry Cake added successfully!";
        } else {
            $message = "✗ Error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Blueberry Cake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container" style="max-width: 500px;">
        <h2>Replace Chocolate Cake with Blueberry Cake</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Product Image</label>
                <input type="file" name="product_image" class="form-control" accept="image/*" required>
                <small class="text-muted">Upload a blueberry cake image</small>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Blueberry Cake</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
