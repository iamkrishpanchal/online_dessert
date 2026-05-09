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
                                    Purchase Table

                                </h2>
                                <div class="form-check form-switch w-full sm:w-auto sm:ml-auto mt-3 sm:mt-0">

                                    <input id="show-example-1" data-target="#basic-table" class="show-code form-check-input mr-0 ml-3" type="checkbox">
                                </div>
                            </div>
                            <div class="p-5" id="basic-table">
                                <div class="preview">
                                    <div class="overflow-x-auto">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="whitespace-nowrap">Purchase ID</th>
                                                    <th class="whitespace-nowrap">Product Name</th>
                                                    <th class="whitespace-nowrap">Payment Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $query = "SELECT tbl_purchase.PaymentStatus,tbl_products.product_name FROM tbl_purchase INNER JOIN tbl_products ON tbl_purchase.productId=tbl_products.product_id;";
                                                $result = mysqli_query($conn, $query);
                                                $count = 1;
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                ?>
                                                    <tr>
                                                        <td> <?php echo $count++; ?></td>
                                                        <td> <?php echo $row["product_name"]; ?></td>
                                                        <td> <?php echo $row["PaymentStatus"]; ?></td>
                                                    </tr>
                                                <?php
                                                }
                                                ?>
                                            </tbody>

                                        </table>
                                    </div>
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