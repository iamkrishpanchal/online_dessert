<?php
include 'session.php';
include 'connection.php';

// detect which discount column exists (used both in form and processing)
$discountCol = null;
$colQuery = mysqli_query($conn,
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_products' AND COLUMN_NAME IN ('discount_percent','discount_price','discount')");
if ($colQuery) {
    while ($dr = mysqli_fetch_assoc($colQuery)) {
        if ($dr['COLUMN_NAME'] === 'discount_percent') {
            $discountCol = 'discount_percent';
            break;
        }
        if ($discountCol === null) {
            $discountCol = $dr['COLUMN_NAME'];
        }
    }
}

if (isset($_POST["btnSubmit"])) {
    $vendor_id = $_SESSION['vendor_id'] ?? null;
    if (!$vendor_id) {
        echo "<script>alert('Vendor ID not found. Please login again.'); window.location.href='login.php';</script>";
        exit;
    }

    $name = trim($_POST["name"] ?? '');
    $price = trim($_POST["price"] ?? '');
    $category_id = $_POST["category_id"];
    $stock = intval($_POST["stock"] ?? 0);
    
    if ($price === '' || !is_numeric($price)) {
        echo "<script>alert('Please enter a valid product price.'); window.history.back();</script>";
        exit;
    }

    $price = floatval($price);
    if ($price < 0) {
        echo "<script>alert('Product price cannot be negative. It must be zero or greater.'); window.history.back();</script>";
        exit;
    }

    if ($stock < 1) {
        echo "<script>alert('Stock quantity must be 1 or more. It cannot be zero or negative.'); window.history.back();</script>";
        exit;
    }

    $discount = (isset($_POST["discount"]) && $discountCol) ? floatval($_POST["discount"]) : 0;
    
    if ($discountCol && isset($_POST["discount"]) && $_POST["discount"] !== '') {
        if ($discount <= 0) {
            echo "<script>alert('Product discount must be greater than 0. Leave it empty if you do not want to apply a discount.'); window.history.back();</script>";
            exit;
        }
    }

    $exe = pathinfo($_FILES["Product_Image"]["name"], PATHINFO_EXTENSION);
    $filename = time() . random_int(1111, 9999) . "." . $exe;
    $uploadDir = __DIR__ . '/uploads';
    
    // Create uploads directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $dest = $uploadDir . '/' . $filename;
    if (move_uploaded_file($_FILES["Product_Image"]["tmp_name"], $dest)) {
        // Store just the filename - path handling in display
        $imagePath = $filename;
    } else {
        echo "<script>alert('Failed to upload image');</script>";
        exit;
    }

    // build INSERT conditionally based on discount column
    $stmt = null;
    if ($discountCol) {
        $sql = "INSERT INTO tbl_products (product_name, product_price, category_id, product_image, stock, vendor_id, $discountCol)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            // product discount percent may be decimal, treat as double
            mysqli_stmt_bind_param($stmt, 'sdisidi', $name, $price, $category_id, $imagePath, $stock, $vendor_id, $discount);
        }
    } else {
        $sql = "INSERT INTO tbl_products (product_name, product_price, category_id, product_image, stock, vendor_id)
        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            // types: string, double, int, string, int, int
            mysqli_stmt_bind_param($stmt, 'sdisii', $name, $price, $category_id, $imagePath, $stock, $vendor_id);
        }
    }
    
    if ($stmt) {
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            echo "<script>alert('Record Successfully Inserted');</script>";
            echo "<script>window.location.href='viewProduct.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('Database error: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <title>Add Product</title>
    <!-- BEGIN: CSS Assets-->
    <link rel="stylesheet" href="dist/css/app.css" />
    <!-- END: CSS Assets-->
</head>

<body class="py-5 md:py-0 bg-white">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <!-- BEGIN: Side Menu -->
        <?php include 'SideMenu.php' ?>
        <!-- END: Side Menu -->
        <!-- BEGIN: Content -->
        <div class="content">
            <!-- BEGIN: Top Bar -->
            <?php include 'topBar.php' ?>
            <!-- END: Top Bar -->
            <div class="intro-y flex items-center mt-8">
                <h2 class="text-lg font-medium mr-auto">
                    Add Product
                </h2>
            </div>
            <div class="grid grid-cols-12 gap-6 mt-5">
                <div class="intro-y col-span-12 lg:col-span-12">
                    <!-- BEGIN: Input -->
                    <div class="intro-y box">

                        <form method="POST" enctype="multipart/form-data">
                            <div id="input" class="p-5">
                                <div class="preview" style="margin-top: 12px;">
                                    <div>
                                        <label for="regular-form-1" class="form-label">Enter Product Name</label>
                                        <input id="regular-form-1" type="text" class="form-control"
                                            placeholder="Enter Name" name="name">
                                    </div>
                                </div>

                                <div class="preview" style="margin-top: 12px;">
                                    <div>
                                        <label for="regular-form-1" class="form-label">Enter Product Price</label>
                                        <input id="regular-form-1" type="number" class="form-control"
                                            placeholder="Enter Price" name="price" min="0" step="0.01" required>
                                    </div>
                                </div>

                                <div class="preview" style="margin-top: 12px;">
                                    <div>
                                        <label for="category-select" class="form-label">Select Category</label>
                                        <select id="category-select" class="form-control" name="category_id" required>
                                            <option value="">-- Select Category --</option>
                                            <?php
                                            $vendor_id = $_SESSION['vendor_id'] ?? null;
                                            if ($vendor_id) {
                                                $catQuery = "SELECT categories_id, categories_name FROM tbl_categories WHERE vendor_id = ? ORDER BY categories_name ASC";
                                                $catStmt = mysqli_prepare($conn, $catQuery);
                                                if ($catStmt) {
                                                    mysqli_stmt_bind_param($catStmt, 'i', $vendor_id);
                                                    mysqli_stmt_execute($catStmt);
                                                    $catResult = mysqli_stmt_get_result($catStmt);
                                                    
                                                    if (mysqli_num_rows($catResult) > 0) {
                                                        while ($catRow = mysqli_fetch_assoc($catResult)) {
                                                            echo '<option value="' . $catRow['categories_id'] . '">' . htmlspecialchars($catRow['categories_name']) . '</option>';
                                                        }
                                                    } else {
                                                        echo '<option value="" disabled>No categories found. Please add a category first.</option>';
                                                    }
                                                    mysqli_stmt_close($catStmt);
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="preview" style="margin-top: 12px;">
                                    <div>
                                        <label for="regular-form-1" class="form-label">Image</label>
                                        <input id="regular-form-1" type="File" class="form-control"
                                            placeholder="Enter Image" name="Product_Image">
                                    </div>
                                </div>

                                <div class="preview" style="margin-top: 12px;">
                                    <div>
                                        <label for="stock" class="form-label">Enter Stock Quantity</label>
                                        <input id="stock" type="number" class="form-control"
                                            placeholder="Enter Stock" name="stock" min="1" required>
                                    </div>
                                </div>

                                <?php
                                // determine discount column and label
                                $discCol = null;
                                $discLabel = 'Discount';
                                $discPlaceholder = '';
                                $discAttrs = '';
                                $chk = mysqli_query($conn,
                                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_products' AND COLUMN_NAME IN ('discount_percent','discount_price','discount')");
                                if ($chk) {
                                    while ($r = mysqli_fetch_assoc($chk)) {
                                        if ($r['COLUMN_NAME'] === 'discount_percent') {
                                            $discCol = 'discount_percent';
                                            break;
                                        }
                                        if ($discCol === null) {
                                            $discCol = $r['COLUMN_NAME'];
                                        }
                                    }
                                }
                                if ($discCol === 'discount_percent') {
                                    $discLabel = 'Discount (%)';
                                    $discAttrs = 'step="0.01"';
                                } elseif ($discCol === 'discount_price') {
                                    $discLabel = 'Discounted Price';
                                } elseif ($discCol === 'discount') {
                                    $discLabel = 'Discount';
                                }
                                ?>
                                <?php if ($discCol): ?>
                                <div class="preview" style="margin-top: 12px;">
                                    <div>
                                        <label for="discount" class="form-label"><?php echo htmlspecialchars($discLabel); ?></label>
                                        <input id="discount" type="number" class="form-control"
                                            placeholder="<?php echo htmlspecialchars($discPlaceholder); ?>" name="discount" <?php echo $discAttrs; ?>>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <input type="Submit" class="btn btn-primary w-24 mr-1 mb-2" name="btnSubmit"
                                    value="Submit" style="margin-top: 12px;">
                            </div>
                    </div>
                    </form>
                </div>
                <!-- END: Input -->

                <!-- END: Content -->
            </div>
            <!-- BEGIN: JS Assets-->
            <script
                src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
            <script src="dist/js/app.js"></script>
            <!-- END: JS Assets-->
</body>

</html>