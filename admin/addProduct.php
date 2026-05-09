<?php
include 'session.php';
include 'connection.php';
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <title>Add Product</title>
    <link rel="stylesheet" href="dist/css/app.css" />
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <?php include 'sideMenu.php' ?>
        <div class="content">
            <div class="intro-y flex items-center mt-8">
                <h2 class="text-lg font-medium mr-auto">Add New Product</h2>
            </div>
            <div class="grid grid-cols-12 gap-6 mt-5">
                <div class="intro-y col-span-12 lg:col-span-8">
                    <div class="intro-y box p-5">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="pname" class="form-control" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Category</label>
                                <select name="catId" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php
                                    $cat_query = "SELECT id, cat_name FROM tbl_cat";
                                    $cat_result = mysqli_query($conn, $cat_query);
                                    while ($cat_row = mysqli_fetch_assoc($cat_result)) {
                                        echo "<option value='" . $cat_row['id'] . "'>" . $cat_row['cat_name'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Price</label>
                                <input type="number" name="price" class="form-control" step="0.01" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Discount (%)</label>
                                <input type="number" name="discount_percent" class="form-control" step="0.01" value="0">
                                <small class="text-muted">Optional percentage off the base price.</small>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Product Image</label>
                                <input type="file" name="productImg" class="form-control" accept="image/*" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"></textarea>
                            </div>
                            <div class="form-check form-switch mb-4">
                                <input id="status" name="status" class="form-check-input" type="checkbox" checked>
                                <label class="form-check-label" for="status">Active</label>
                            </div>
                            <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                            <a href="viewProduct.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    if (isset($_POST['add_product'])) {
        $pname = mysqli_real_escape_string($conn, $_POST['pname']);
        $catId = mysqli_real_escape_string($conn, $_POST['catId']);
        $price = mysqli_real_escape_string($conn, $_POST['price']);
        // capture whichever discount column we have
        $discount_percent = 0;
        if (isset($discountCol) && $discountCol && isset($_POST[$discountCol])) {
            $discount_percent = mysqli_real_escape_string($conn, $_POST[$discountCol]);
        }
        $status = isset($_POST['status']) ? 1 : 0;
        
        // Get vendor info from session if available
        $vendor_id = isset($_SESSION['vendor_id']) ? mysqli_real_escape_string($conn, $_SESSION['vendor_id']) : null;
        $vendor_name = isset($_SESSION['vendor_name']) ? mysqli_real_escape_string($conn, $_SESSION['vendor_name']) : null;
        
        // Handle file upload
        if ($_FILES['productImg']['name']) {
            $filename = $_FILES['productImg']['name'];
            $tmp_name = $_FILES['productImg']['tmp_name'];
            $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
            $new_filename = time() . '.' . $file_ext;
            
            if (move_uploaded_file($tmp_name, "uploads/" . $new_filename)) {
                // Check if vendor columns exist, if not create them dynamically
                $colRes = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_products LIKE 'vendor_id'");
                $has_vendor_id_col = ($colRes && mysqli_num_rows($colRes) > 0);
                
                $colRes2 = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_products LIKE 'vendor_name'");
                $has_vendor_name_col = ($colRes2 && mysqli_num_rows($colRes2) > 0);
                
                // Build insert query with vendor info if columns exist
                // choose column name for insert
                $discField = $discountCol ?: 'discount_percent';
                $hasExtra = $has_vendor_id_col && $has_vendor_name_col && $vendor_id && $vendor_name;
                if ($hasExtra) {
                    $insert_query = "INSERT INTO tbl_products (pname, catId, price, $discField, productImg, status, vendor_id, vendor_name) 
                                    VALUES ('$pname', '$catId', '$price', '$discount_percent', '$new_filename', '$status', '$vendor_id', '$vendor_name')";
                } else {
                    $insert_query = "INSERT INTO tbl_products (pname, catId, price, $discField, productImg, status) 
                                    VALUES ('$pname', '$catId', '$price', '$discount_percent', '$new_filename', '$status')";
                }
                
                if (mysqli_query($conn, $insert_query)) {
                    echo "<script>alert('Product added successfully!'); window.location.href='viewProduct.php';</script>";
                } else {
                    echo "<script>alert('Error adding product: " . mysqli_error($conn) . "');</script>";
                }
            } else {
                echo "<script>alert('Error uploading file!');</script>";
            }
        }
    }
    ?>

    <script src="dist/js/app.js"></script>
</body>
</html>
