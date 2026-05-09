<?php
include 'session.php';
include 'connection.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
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
                                <h2 class="font-medium text-base mr-auto">
                                    View Product

                                </h2>
                                <div class="form-check form-switch w-full sm:w-auto sm:ml-auto mt-3 sm:mt-0">

                                    <input id="show-example-1" data-target="#basic-table" class="show-code form-check-input mr-0 ml-3" type="checkbox">
                                </div>
                            </div>
                            <div class="p-5" id="basic-table">
                                <div class="preview">
                                    <div class="overflow-x-auto">
                                        <?php
                                        $productId = isset($_GET["productId"]) ? intval($_GET["productId"]) : 0;
                                        if ($productId <= 0) {
                                            echo "Invalid product ID.";
                                            exit;
                                        }

                                        // restrict query to the requested product (and current vendor, if session exists)
                                        $vendor_id = $_SESSION['vendor_id'] ?? null;
                                        $where = "WHERE product.product_id = {$productId}";
                                        if ($vendor_id !== null) {
                                            $where .= " AND product.vendor_id = " . intval($vendor_id);
                                        }

                                        $sql = "SELECT product.product_name AS pname,
                                                       product.product_price AS price,
                                                       cat.categories_name AS cat_name,
                                                       product.product_id AS productId,
                                                       product.product_image AS productImg
                                                FROM tbl_products AS product
                                                INNER JOIN tbl_categories AS cat
                                                    ON product.category_id = cat.categories_id
                                                {$where} LIMIT 1";
                                        $result = mysqli_query($conn, $sql) or die(mysqli_error($conn));

                                        $row = mysqli_fetch_array($result);
                                        if (!$row) {
                                            echo "Product not found.";
                                            exit;
                                        }
                                        
                                        // Resolve image path
                                        $imgPath = '';
                                        if (!empty($row['productImg'])) {
                                            $candidates = ['uploads/' . $row['productImg'], 'vendor/uploads/' . $row['productImg'], $row['productImg']];
                                            foreach ($candidates as $cand) { if (file_exists(__DIR__ . '/' . $cand)) { $imgPath = $cand; break; } }
                                            if (!$imgPath) $imgPath = $candidates[0];
                                        }
                                        ?>

                                        <table class="table table-striped">

                                            <tr>
                                                <th>Name</th>
                                                <td><?php echo $row['pname'];?></td>
                                            </tr>

                                            <tr>
                                                <th>Price</th>
                                                <td>Rs.<?php echo $row['price'];?></td>
                                            </tr>

                                            <tr>
                                                <th>Category Name</th>
                                                <td><?php echo $row['cat_name'];?></td>
                                            </tr>

                                            <tr>
                                                <th>Image</th>
                                                <td><img src="<?php echo htmlspecialchars($imgPath); ?>" height="100" width="200"></td>
                                            </tr>
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
</html>