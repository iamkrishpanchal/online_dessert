<?php
include 'session.php';
include 'connection.php';

// perform schema introspection early so headers render correctly
$vendor_id = intval($_SESSION['vendor_id'] ?? 0);

// Determine product table / column names (handles both old/new schema variations)
$productTable = 'tbl_products';
$hasTblProducts = false;
$hasTblProduct = false;
$res = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_products'");
if ($res && mysqli_num_rows($res) > 0) {
    $hasTblProducts = true;
}
$res = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product'");
if ($res && mysqli_num_rows($res) > 0) {
    $hasTblProduct = true;
}
if (!$hasTblProducts && $hasTblProduct) {
    $productTable = 'tbl_product';
}

// Detect product columns
$productCols = [];
$colRes = @mysqli_query($conn, "SHOW COLUMNS FROM {$productTable}");
$colNames = [];
if ($colRes) {
    while ($crow = mysqli_fetch_assoc($colRes)) {
        $colNames[] = $crow['Field'];
    }
}

// also inspect category table columns to choose a good name for display
$categoryNameCol = null;
$catColNames = [];
$catRes = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_categories");
if ($catRes) {
    while ($crow = mysqli_fetch_assoc($catRes)) {
        $catColNames[] = $crow['Field'];
    }
    if (in_array('categories_name', $catColNames)) {
        $categoryNameCol = 'categories_name';
    } elseif (in_array('category_name', $catColNames)) {
        $categoryNameCol = 'category_name';
    } elseif (in_array('cat_name', $catColNames)) {
        $categoryNameCol = 'cat_name';
    }
}

// Determine what column names to use
$productIdCol = in_array('product_id', $colNames) ? 'product_id' : (in_array('productId', $colNames) ? 'productId' : null);
$productNameCol = in_array('product_name', $colNames) ? 'product_name' : (in_array('pname', $colNames) ? 'pname' : null);
$priceCol = in_array('product_price', $colNames) ? 'product_price' : (in_array('price', $colNames) ? 'price' : null);
$categoryIdCol = in_array('category_id', $colNames) ? 'category_id' : (in_array('catId', $colNames) ? 'catId' : null);
$imageCol = in_array('product_image', $colNames) ? 'product_image' : (in_array('productImg', $colNames) ? 'productImg' : null);

// Detect discount column if present
$discount_col = null;
$discountCols = ['discount_percent','discount_price','discount'];
foreach ($discountCols as $dc) {
    if (in_array($dc, $colNames)) {
        $discount_col = $dc;
        break;
    }
}

// helper to calculate how many columns will be in the table
function computeColspan() {
    global $productNameCol, $categoryIdCol, $discount_col;
    $count = 1; // Sr. No.
    if ($productNameCol) $count++;
    if ($categoryIdCol) $count++;
    $count++; // price
    if ($discount_col) $count++;
    $count++; // image
    $count++; // operations
    return $count;
}
?>

<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <title>View Product</title>
    <!-- BEGIN: CSS Assets-->
        <link rel="stylesheet" href="dist/css/app.css" />
        <style>
            .content .box { border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.09); background: #fff; }
            .table thead tr { background: linear-gradient(90deg, #213a7a, #1c2e67); color: #fff; }
            .table th, .table td { padding: 0.9rem 0.75rem; vertical-align: middle; }
            .table tbody tr:hover { background: #f2f7ff; }
            .table img { border-radius: 10px; object-fit: cover; max-height: 120px; width: auto; }
            .btn-primary, .btn-info, .btn-danger, .btn-dark { border-radius: 8px; font-size: .94rem; padding: .42rem .9rem; color: #fff !important; border: none; }
            .btn-primary { background-color: #27375a !important; }
            .btn-info { background-color: #0f6fd1 !important; }
            .btn-danger { background-color: #d02323 !important; }
            .btn-dark { background-color: #667494 !important; }
            .btn-primary:hover, .btn-info:hover, .btn-danger:hover, .btn-dark:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(33,63,118,.22); }
            #basic-table { border: 1px solid #d8e2f3; border-radius: 12px; }
            .action-btn { min-width: 78px; font-weight: 600; }
        </style>
        <!-- END: CSS Assets-->
    </head>
    <!-- END: Head -->

    <body class="py-5 md:py-0 bg-white">
        <div class="flex mt-0 md:mt-0 overflow-hidden">
            <!-- BEGIN: Side Menu -->
            <?php include 'sideMenu.php' ?>
            <!-- END: Side Menu -->
            <!-- BEGIN: Content -->
            <div class="content">
                <!-- BEGIN: Top Bar -->
                <?php include 'topBar.php' ?>
                <!-- END: Top Bar -->
                <div class="intro-y flex items-center mt-8">
                    <h2 class="text-lg font-medium mr-auto">

                    </h2>
                </div>
                <div class="grid grid-cols-12 gap-6 mt-5">
                    <div class="intro-y col-span-12 lg:col-span-12">
                        <!-- BEGIN: Basic Table -->
                        <div class="intro-y box">
                            <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                                <h2 class="font-medium text-2xl mr-auto flex items-center gap-2">
                                    <i data-lucide="box" class="w-6 h-6"></i>
                                    Product

                                </h2>
                                <div class="sm:ml-auto mt-3 sm:mt-0">
                                    <a href="addProduct.php" class="btn btn-primary mr-2">+ Add Product</a>
                                </div>
<!-- 
                                <div class="form-check form-switch w-full sm:w-auto sm:ml-auto mt-3 sm:mt-0">

                                    <input id="show-example-1" data-target="#basic-table" class="show-code form-check-input mr-0 ml-3" type="checkbox">
                                </div> -->
                            </div>
                            <div class="p-5" id="basic-table">
                                <div class="preview">
                                    <div class="overflow-x-auto">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="whitespace-nowrap">Sr No.</th>
                                                    <?php if ($productNameCol): ?>
                                                        <th class="whitespace-nowrap">Product Name</th>
                                                    <?php endif; ?>
                                                    <?php if ($categoryIdCol): ?>
                                                        <th class="whitespace-nowrap">
                                                            Category<?php echo $categoryNameCol ? ' Name' : ''; ?>
                                                        </th>
                                                    <?php endif; ?>
                                                    <th class="whitespace-nowrap">Price</th>
                                                    <?php if (isset($discount_col) && $discount_col): ?>
                                                        <th class="whitespace-nowrap"><?php echo ucfirst(str_replace('_',' ', $discount_col)); ?></th>
                                                    <?php endif; ?>
                                                    <th class="whitespace-nowrap">Image</th>
                                                    <th class="whitespace-nowrap">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
// $vendor_id and schema variables already prepared at top of file

if (!$vendor_id) {
    $colspan = computeColspan();
    echo "<tr><td colspan='" . $colspan . "'>Vendor ID not found. Please login again.</td></tr>";
} else {
    // Build SELECT list dynamically based on available columns
    $select_list = [];
    if ($productNameCol) $select_list[] = "p.{$productNameCol} AS pname";
    if ($priceCol) $select_list[] = "p.{$priceCol} AS price";
    if ($categoryIdCol) $select_list[] = "p.{$categoryIdCol} AS category_id";
    if ($productIdCol) $select_list[] = "p.{$productIdCol} AS productId";
    if ($imageCol) $select_list[] = "p.{$imageCol} AS productImg";
    if ($discount_col) $select_list[] = "p.{$discount_col} AS discount_val";

    // add category name field if we know what column holds that
    if ($categoryNameCol) {
        $select_list[] = "c.{$categoryNameCol} AS cat_name";
    }

    // Default to category join if possible
    $categoryJoin = '';
    if ($categoryIdCol) {
        $categoryJoin = "INNER JOIN tbl_categories c ON p.{$categoryIdCol} = c.categories_id";
    }

    $sql = "SELECT " . implode(', ', $select_list) . " FROM {$productTable} p " . $categoryJoin . " WHERE p.vendor_id = ?";

    // If category table has vendor_id and we want to restrict categories, add it
    if ($categoryIdCol) {
        $categoryVendorColExists = false;
        $catColRes = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_categories LIKE 'vendor_id'");
        if ($catColRes && mysqli_num_rows($catColRes) > 0) {
            $categoryVendorColExists = true;
        }
        if ($categoryVendorColExists) {
            $sql .= " AND c.vendor_id = ?";
        }
    }

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        if (strpos($sql, 'c.vendor_id') !== false) {
            mysqli_stmt_bind_param($stmt, 'ii', $vendor_id, $vendor_id);
        } else {
            mysqli_stmt_bind_param($stmt, 'i', $vendor_id);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = 1;

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Resolve image path
                $imgPath = '';
                if (!empty($row['productImg'])) {
                    $candidates = ['uploads/' . $row['productImg'], 'vendor/uploads/' . $row['productImg'], $row['productImg']];
                    foreach ($candidates as $cand) { if (file_exists(__DIR__ . '/' . $cand)) { $imgPath = $cand; break; } }
                    if (!$imgPath) $imgPath = $candidates[0];
                }

                echo '<tr>';
                echo '<td>' . $count++ . '</td>';
                echo '<td>' . htmlspecialchars($row['pname'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['cat_name'] ?? '') . '</td>';
                echo '<td>Rs.' . htmlspecialchars($row['price'] ?? '') . '</td>';
                if ($discount_col) {
                    $disc = $row['discount_val'] ?? '';
                    if ($discount_col === 'discount_percent' && $disc !== '') {
                        $disc = $disc . '%';
                    }
                    echo '<td>' . htmlspecialchars($disc) . '</td>';
                }
                echo '<td><img src="' . htmlspecialchars($imgPath) . '" height="100" width="200"></td>';
                echo '<td>';
                echo '<input type="button" class="btn btn-primary w-24 mr-1 mb-2 action-btn" onclick="handleEdit(' . ($row['productId'] ?? 0) . ')" value="Edit">';
                echo '<input type="button" class="btn btn-danger w-24 mr-1 mb-2 action-btn" onclick="handleDelete(' . ($row['productId'] ?? 0) . ')" value="Delete">';
                echo '<a class="btn btn-dark w-24 mr-1 mb-2 action-btn" href="single_product.php?productId=' . ($row['productId'] ?? 0) . '">View</a>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            $colspan = computeColspan();
            echo "<tr><td colspan='" . $colspan . "' style='text-align:center;'>No products found.</td></tr>";
        }

        mysqli_stmt_close($stmt);
    } else {
        $colspan = computeColspan();
        echo "<tr><td colspan='" . $colspan . "'>Database error.</td></tr>";
    }
}
?>
                                            </tbody>

                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- END: Basic Table -->

                    </div>
                    <!-- BEGIN: JS Assets-->
                    <script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
                    <script src="dist/js/app.js"></script>
                    <!-- END: JS Assets-->
    </body>
    <script>
        function handleEdit(productId) {

            alert(productId)
            window.location.href = "editProduct.php?product_id=" + productId;
        }
    </script>

    <script>
        function handleDelete(productId) {

            alert(productId)
            window.location.href = "deleteProduct.php?product_id=" + productId;
        }
    </script>

    </html>