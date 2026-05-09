<?php
include 'session.php';
include 'connection.php';

$vendor_id = $_SESSION['vendor_id'] ?? null;
if (!$vendor_id) {
    echo "<script>alert('Vendor ID not found. Please login again.'); window.location.href='login.php';</script>";
    exit;
}

$productIdVal = $_GET["product_id"] ?? 0;
$sql = "SELECT * FROM tbl_products WHERE product_id=? AND vendor_id=?";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ii', $productIdVal, $vendor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$row) {
        echo "<script>alert('Product not found or unauthorized access.'); window.location.href='viewProduct.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('Database error.'); window.location.href='viewProduct.php';</script>";
    exit;
}

$name_val = $row['product_name'];
$price_val = $row['product_price'];
$categoryid_val = $row["category_id"];
$productImg_val = $row["product_image"];
$stock_val = $row["stock"];
// determine which discount column is available (percent, price or flat amount)
$discount_col = null;
$discCheck = mysqli_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_products' AND COLUMN_NAME IN ('discount_percent','discount_price','discount')");
if ($discCheck) {
    while ($drow = mysqli_fetch_assoc($discCheck)) {
        // prefer percent if present
        if ($drow['COLUMN_NAME'] === 'discount_percent') {
            $discount_col = 'discount_percent';
            break;
        }
        if ($discount_col === null) {
            $discount_col = $drow['COLUMN_NAME'];
        }
    }
}
$discount_val = 0;
if ($discount_col && isset($row[$discount_col])) {
    $discount_val = $row[$discount_col];
}
// prepare label for form field
$discount_label = 'Discount';
$discount_placeholder = '';
$discount_attrs = '';
if ($discount_col === 'discount_percent') {
    $discount_label = 'Discount (%)';
    $discount_placeholder = 'Enter Discount Percentage';
    $discount_attrs = 'min="5" max="10" step="0.01"';
} elseif ($discount_col === 'discount_price') {
    $discount_label = 'Discounted Price';
    $discount_placeholder = 'Enter Discounted Price';
} elseif ($discount_col === 'discount') {
    $discount_label = 'Discount';
    $discount_placeholder = 'Enter Discount Amount';
}



if (isset($_POST["btnSubmit"])) {

        $name = trim($_POST["name"] ?? '');
        $price = trim($_POST["price"] ?? '');
        $category_id = $_POST["category_id"];
        $stock = $_POST["stock"];
        $discount = isset($_POST["discount"]) ? floatval($_POST["discount"]) : 0;

        if ($price === '' || !is_numeric($price)) {
            echo "<script>alert('Please enter a valid product price.'); window.history.back();</script>";
            exit;
        }

        $price = floatval($price);
        if ($price < 0) {
            echo "<script>alert('Product price cannot be negative. It must be zero or greater.'); window.history.back();</script>";
            exit;
        }
        
        if ($discount_col && isset($_POST["discount"])) {
            if ($discount < 0) {
                echo "<script>alert('Product discount cannot be negative.'); window.history.back();</script>";
                exit;
            }
            if ($discount < 5 || $discount > 10) {
                echo "<script>alert('Product discount must be between 5% and 10%.'); window.history.back();</script>";
                exit;
            }
        }

    if ($_FILES["Product_Image"]["name"] != '') {

        $exe = pathinfo($_FILES["Product_Image"]["name"], PATHINFO_EXTENSION);


        $filename = time() . random_int(1111, 9999) . "." . $exe;



        move_uploaded_file($_FILES["Product_Image"]["tmp_name"], "./uploads/" . $filename);
        unlink("./uploads/" . $productImg_val);
    } else {

        $filename = $productImg_val;
    }
    // build update statement conditionally
    $sql = "update tbl_products set product_name='$name', product_price=$price, category_id=$category_id, product_image='$filename', stock=$stock";
    if ($discount_col) {
        $sql .= ", $discount_col=$discount";
    }
    $sql .= " where product_id=$productIdVal";



    $result = mysqli_query($conn, $sql);

    if ($result) {

        echo "<script> alert('Record Successfully Updated');</script>";
        echo "<script>
            window.location.href='viewProduct.php';
        </script>";
    }
    echo $sql;
    die;
}

?>


<!DOCTYPE html>
<html lang="en" class="light">
<!-- BEGIN: Head -->

<head>
    <!-- BEGIN: CSS Assets-->
    <link rel="stylesheet" href="dist/css/app.css" />
    <!-- END: CSS Assets-->
</head>
<!-- END: Head -->

<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
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
                        <div
                            class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60 dark:border-darkmode-400">
                            <h2 class="font-medium text-base mr-auto">
                                Input
                            </h2>

                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <div id="input" class="p-5">
                                <div class="preview" style="margin-top: 12px;">
                                    <div>
                                        <label for="regular-form-1" class="form-label">Enter Product Name</label>
                                        <input id="regular-form-1" type="text" class="form-control"
                                            placeholder="Enter Name" name="name" value="<?php echo $name_val; ?>">
                                    </div>
                                </div>

                                <div class="preview" style="margin-top: 12px;">
                                    <div>
                                        <label for="regular-form-1" class="form-label">Enter Product Price</label>
                                        <input id="regular-form-1" type="number" class="form-control"
                                            placeholder="Enter Price" name="price" min="0" step="0.01" value="<?php echo $price_val; ?>" required>
                                    </div>
                                </div>

                                <div class="preview" style="margin-top: 12px;">
                                    <div>
                                        <label for="category-select" class="form-label">Select Category</label>
                                        <select id="category-select" class="form-control" name="category_id" value="<?php echo $categoryid_val; ?>" required>
                                            <option value="">-- Select Category --</option>
                                            <?php
                                            $catQuery = "SELECT categories_id, categories_name FROM tbl_categories ORDER BY categories_name ASC";
                                            $catResult = mysqli_query($conn, $catQuery);
                                            if ($catResult && mysqli_num_rows($catResult) > 0) {
                                                while ($catRow = mysqli_fetch_assoc($catResult)) {
                                                    $selected = ($catRow['categories_id'] == $categoryid_val) ? 'selected' : '';
                                                    echo '<option value="' . $catRow['categories_id'] . '" ' . $selected . '>' . htmlspecialchars($catRow['categories_name']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="preview" style="margin-top: 12px;">
                                    <div>
                                        <label for="regular-form-1" class="form-label">Image</label>
                                        <img src="uploads/<?php echo $productImg_val; ?>" height="100px" width="100px" />
                                        <input id="regular-form-1" type="File" class="form-control"
                                            placeholder="Enter Image" name="Product_Image">

                                    </div>
                                </div>

                                <div class="preview" style="margin-top: 12px;">
                                    <div>
                                        <label for="stock" class="form-label">Enter Stock Quantity</label>
                                        <input id="stock" type="number" class="form-control"
                                            placeholder="Enter Stock" name="stock" value="<?php echo $stock_val; ?>" min="0" required>
                                    </div>
                                </div>

                                <?php if ($discount_col): ?>
                                <div class="preview" style="margin-top: 12px;">
                                    <div>
                                        <label for="discount" class="form-label"><?php echo htmlspecialchars($discount_label); ?></label>
                                        <input id="discount" type="number" class="form-control"
                                            placeholder="<?php echo htmlspecialchars($discount_placeholder); ?>" name="discount" <?php echo $discount_attrs; ?> value="<?php echo $discount_val; ?>">
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