<?php
include 'session.php';
include 'connection.php';

$vendor_id = intval($_SESSION['vendor_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en" class="light">
    <!-- BEGIN: Head -->
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>View Category</title>
        <!-- BEGIN: CSS Assets-->
        <!-- BEGIN: CSS Assets-->
        <link rel="stylesheet" href="dist/css/app.css" />
        <style>
            body { background: #f4f7fb; color: #1f2a44; font-family: 'Inter', sans-serif; }
            .content { width: 100%; padding: 1.5rem 1rem; }
            .intro-y.box { border-radius: 14px; box-shadow: 0 12px 28px rgba(34,57,93,0.11); background: #ffffff; border: 1px solid #e8edf7; margin-bottom: 1.5rem; }
            .intro-y .flex { gap: 10px; }
            .page-title { font-size: 1.9rem; font-weight: 700; color: #17203f; margin-bottom: .8rem; }
            .table { border-collapse: collapse; width: 100%; background: #ffffff; }
            .table thead tr { background: linear-gradient(90deg, #374568, #1c2e67); color: #ffffff; border-radius: 10px; }
            .table thead th { font-weight: 700; letter-spacing: .02em; border-bottom: none; padding: .95rem 1rem; text-transform: uppercase; font-size: .9rem; }
            .table tbody tr { border-bottom: 1px solid #eef2f8; transition: background .25s ease; }
            .table tbody tr:hover { background: #f0f6ff; }
            .table td { padding: .85rem 1rem; vertical-align: middle; color: #33415c; }
            .table img { border-radius: 10px; object-fit: cover; max-height: 150px; width: auto; box-shadow: 0 5px 15px rgba(33,63,118,.16); }
            .btn-primary, .btn-info, .btn-danger, .btn-dark { border-radius: 8px; font-size: .92rem; padding: .5rem 1rem; color: #fff !important; border: none; box-shadow: 0 3px 12px rgba(12,35,105,.2); }
            .btn-primary { background-color: #344067 !important; }
            .btn-info { background-color: #87919d !important; }
            .btn-danger { background-color: #dd2f3b !important; }
            .btn-dark { background-color: #33415b !important; }
            .btn-primary:hover, .btn-info:hover, .btn-danger:hover, .btn-dark:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(33,63,118,.22); }
            .action-btn { min-width: 86px; font-weight: 600; }
            #products-section { background: #ffffff; border: 1px solid #dde4f5; border-radius: 12px; padding: 1.1rem; margin-top: 1.1rem; }
            #products-section h3 { border-bottom: 1px solid #e8edf7; padding-bottom: .6rem; margin-bottom: .85rem; color: #1f335b; }
            .status-pill { display: inline-block; border-radius: 999px; padding: .2rem .65rem; font-size: .78rem; font-weight: 600; }
            .status-pill.active { background: #ddf7ea; color: #1f6b3f; }
            .status-pill.inactive { background: #fdebe7; color: #9f2e2f; }
        </style>
        <!-- END: CSS Assets-->
    </head>
    <!-- END: Head -->

    <body class="py-0 md:py-0 bg-black/[0.15] dark:bg-transparent">
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
                                    <i data-lucide="grid" class="w-6 h-6"></i>
                                    Category

                                </h2>
                                <div class="sm:ml-auto mt-3 sm:mt-0">
                                    <a href="addCategory.php" class="btn btn-primary mr-2">+ Add Category</a>
                                </div>
                            </div>
                            <div class="p-5" id="basic-table">
                                <div class="preview">
                                    <div class="overflow-x-auto">
                                        <table class="table">
                                            <thead>
                                                <tr style="background-color: #ffffff;">

                                                    <th class="whitespace-nowrap">Sr No.</th>
                                                    <th class="whitespace-nowrap">Name</th>
                                                    <th class="whitespace-nowrap">description</th>
                                                    <th class="whitespace-nowrap">Image</th>
                                                    <th class="whitespace-nowrap">Products</th>
                                                    <th class="whitespace-nowrap">Operation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $vendor_id = $_SESSION['vendor_id'] ?? null;
                                                if (!$vendor_id) {
                                                    echo "<tr><td colspan='6'>Vendor ID not found. Please login again.</td></tr>";
                                                } else {
                                                    $query = "SELECT categories_id, categories_name, categories_description, categories_image FROM tbl_categories WHERE vendor_id = ?";
                                                    
                                                    $stmt = mysqli_prepare($conn, $query);
                                                    if ($stmt) {
                                                        mysqli_stmt_bind_param($stmt, 'i', $vendor_id);
                                                        mysqli_stmt_execute($stmt);
                                                        $result = mysqli_stmt_get_result($stmt);
                                                        $count = 1;
                                                        
                                                        if (mysqli_num_rows($result) == 0) {
                                                            echo "<tr><td colspan='6' style='text-align:center;'>No categories found.</td></tr>";
                                                        } else {
                                                            while ($row = mysqli_fetch_assoc($result)) {
                                                                // Count products in this category for this vendor
                                                                $productCountQuery = "SELECT COUNT(*) as product_count FROM tbl_products WHERE category_id = ? AND vendor_id = ?";
                                                                $pcStmt = mysqli_prepare($conn, $productCountQuery);
                                                                $productCount = 0;
                                                                if ($pcStmt) {
                                                                    mysqli_stmt_bind_param($pcStmt, 'ii', $row['categories_id'], $vendor_id);
                                                                    mysqli_stmt_execute($pcStmt);
                                                                    $pcResult = mysqli_stmt_get_result($pcStmt);
                                                                    if ($pcRow = mysqli_fetch_assoc($pcResult)) {
                                                                        $productCount = $pcRow['product_count'];
                                                                    }
                                                                    mysqli_stmt_close($pcStmt);
                                                                }
                                                ?>
                                                    <tr>
                                                        <td> <?php echo $count++; ?></td>
                                                        <td> <?php echo $row["categories_name"]; ?></td>
                                                        <td> <?php echo $row["categories_description"]; ?></td>
                                                        <td>
                                                            <img src="uploads/<?php echo $row["categories_image"]; ?>" height="200" width="300">
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-info" onclick="toggleProducts(<?php echo $row['categories_id']; ?>)">View Products (<?php echo $productCount; ?>)</button>
                                                        </td>
                                                        <td>
                                                            <input type="button" class="btn btn-primary w-24 mr-1 mb-2 action-btn" onclick="handleEdit(<?php echo $row['categories_id']; ?>)" value="Edit">
                                                            <input type="button" class="btn btn-danger w-24 mr-1 mb-2 action-btn" onclick="handleDelete(<?php echo $row['categories_id']; ?>)" value="Delete">

                                                        </td>
                                                    </tr>
                                                <?php
                                                            }
                                                        }
                                                        mysqli_stmt_close($stmt);
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Products Section -->
                                <div id="products-section" style="margin-top: 30px; display: none;">
                                    <h3 class="text-lg font-medium mb-4">Products in Category</h3>
                                    <div id="products-table" class="overflow-x-auto">
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
    <script>
        let currentCategoryId = null;
        
        function handleEdit(id) {
            alert(id)
            window.location.href = "editCategory.php?cat_id=" + id;
        }

        function handleDelete(id) {
            alert(id)
            window.location.href = "deleteCategory.php?cat_id=" + id;
        }

        function toggleProducts(categoryId) {
            const productsSection = document.getElementById('products-section');
            const productsTable = document.getElementById('products-table');
            
            if (currentCategoryId === categoryId) {
                // Toggle off if same category clicked
                productsSection.style.display = 'none';
                currentCategoryId = null;
                return;
            }

            // Fetch products for this category
            fetch('fetch_category_products.php?category_id=' + categoryId)
                .then(response => response.text())
                .then(data => {
                    productsTable.innerHTML = data;
                    productsSection.style.display = 'block';
                    currentCategoryId = categoryId;
                })
                .catch(error => {
                    alert('Error loading products: ' + error);
                });
        }
    </script>
</html>