<?php
include 'session.php';
include 'connection.php';

if (!isset($_GET['product_id'])) {
    header('Location: viewProduct.php');
}

$product_id = mysqli_real_escape_string($conn, $_GET['product_id']);
$query = "SELECT * FROM tbl_product WHERE productId = '$product_id'";
$result = mysqli_query($conn, $query);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header('Location: viewProduct.php');
}

// detect which discount column is available so we can edit it
$discountCol = null;
$discountLabel = 'Discount';
$discountPlaceholder = '';
$discountAttrs = '';
$discountValue = 0;
$colRes = @mysqli_query($conn,
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() \
     AND TABLE_NAME = 'tbl_product' \
     AND COLUMN_NAME IN ('discount_percent','discount_price','discount')");
if ($colRes) {
    while ($crow = mysqli_fetch_assoc($colRes)) {
        if ($crow['COLUMN_NAME'] === 'discount_percent') {
            $discountCol = 'discount_percent';
            break;
        }
        if ($discountCol === null) {
            $discountCol = $crow['COLUMN_NAME'];
        }
    }
}
if ($discountCol && isset($product[$discountCol])) {
    $discountValue = $product[$discountCol];
}
if ($discountCol === 'discount_percent') {
    $discountLabel = 'Discount (%)';
    $discountPlaceholder = 'Enter Discount Percentage';
    $discountAttrs = 'step="0.01" min="0" max="100"';
} elseif ($discountCol === 'discount_price') {
    $discountLabel = 'Discounted Price';
    $discountPlaceholder = 'Enter Discounted Price';
} elseif ($discountCol === 'discount') {
    $discountLabel = 'Discount';
    $discountPlaceholder = 'Enter Discount Amount';
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <title>Edit Product</title>
    <link rel="stylesheet" href="dist/css/app.css" />
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <?php include 'sideMenu.php' ?>
        <div class="content">
            <div class="intro-y flex items-center mt-8">
                <h2 class="text-lg font-medium mr-auto">Edit Product</h2>
            </div>
            <div class="grid grid-cols-12 gap-6 mt-5">
                <div class="intro-y col-span-12 lg:col-span-8">
                    <div class="intro-y box p-5">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="pname" class="form-control" value="<?php echo $product['pname']; ?>" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Category</label>
                                <select name="catId" class="form-control" required>
                                    <?php
                                    $cat_query = "SELECT id, cat_name FROM tbl_cat";
                                    $cat_result = mysqli_query($conn, $cat_query);
                                    while ($cat_row = mysqli_fetch_assoc($cat_result)) {
                                        $selected = ($cat_row['id'] == $product['catId']) ? 'selected' : '';
                                        echo "<option value='" . $cat_row['id'] . "' $selected>" . $cat_row['cat_name'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Price</label>
                                <input type="number" name="price" class="form-control" step="0.01" value="<?php echo $product['price']; ?>" required>
                            </div>
                            <?php if ($discountCol): ?>
                            <div class="mb-4">
                                <label class="form-label"><?php echo htmlspecialchars($discountLabel); ?></label>
                                <input type="number" name="<?php echo htmlspecialchars($discountCol); ?>" class="form-control" <?php echo $discountAttrs; ?> value="<?php echo htmlspecialchars($discountValue); ?>" placeholder="<?php echo htmlspecialchars($discountPlaceholder); ?>">
                            </div>
                            <?php endif; ?>
                            <div class="mb-4">
                                <label class="form-label">Product Image</label>
                                <div class="mb-2">
                                    <img src="uploads/<?php echo $product['productImg']; ?>" height="100" width="200">
                                </div>
                                <input type="file" name="productImg" class="form-control" accept="image/*">
                                <small>Leave empty to keep current image</small>
                            </div>
                            <div class="form-check form-switch mb-4">
                                <input id="status" name="status" class="form-check-input" type="checkbox" <?php echo ($product['status'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status">Active</label>
                            </div>
                            <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                            <a href="viewProduct.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    if (isset($_POST['update_product'])) {
        $pname = mysqli_real_escape_string($conn, $_POST['pname']);
        $catId = mysqli_real_escape_string($conn, $_POST['catId']);
        $price = mysqli_real_escape_string($conn, $_POST['price']);
        $status = isset($_POST['status']) ? 1 : 0;
        
        // pick up discount if column exists
        $discValue = 0;
        if ($discountCol && isset($_POST[$discountCol])) {
            $discValue = mysqli_real_escape_string($conn, $_POST[$discountCol]);
        }
        
        $image = $product['productImg'];
        
        // Handle file upload
        if ($_FILES['productImg']['name']) {
            $filename = $_FILES['productImg']['name'];
            $tmp_name = $_FILES['productImg']['tmp_name'];
            $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
            $new_filename = time() . '.' . $file_ext;
            
            if (move_uploaded_file($tmp_name, "uploads/" . $new_filename)) {
                // Delete old image
                if (file_exists("uploads/" . $product['productImg'])) {
                    unlink("uploads/" . $product['productImg']);
                }
                $image = $new_filename;
            }
        }
        
        $update_query = "UPDATE tbl_product SET pname='$pname', catId='$catId', price='$price', productImg='$image', status='$status'";
        if ($discountCol) {
            $update_query .= ", $discountCol='$discValue'";
        }
        $update_query .= " WHERE productId='$product_id'";
        
        if (mysqli_query($conn, $update_query)) {
            echo "<script>alert('Product updated successfully!'); window.location.href='viewProduct.php';</script>";
        } else {
            echo "<script>alert('Error updating product!');</script>";
        }
    }
    ?>

    <script src="dist/js/app.js"></script>
</body>
</html>
