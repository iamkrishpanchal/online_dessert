<?php
include 'connection.php';

if (!isset($_GET['productId'])) {
    header('Location: viewProduct.php');
    exit;
}

$product_id = mysqli_real_escape_string($conn, $_GET['productId']);

// Detect which product table exists
$productTable = null;
$tres = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product'");
if ($tres && mysqli_num_rows($tres) > 0) {
    $productTable = 'tbl_product';
} else {
    $tres2 = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_products'");
    if ($tres2 && mysqli_num_rows($tres2) > 0) {
        $productTable = 'tbl_products';
    }
}

if (!$productTable) {
    header('Location: viewProduct.php');
    exit;
}

// Build query based on table schema
$product = null;

if ($productTable === 'tbl_product') {
    $query = "SELECT * FROM tbl_product WHERE productId = '$product_id'";
    $result = mysqli_query($conn, $query);
    $product = mysqli_fetch_assoc($result);
} else {
    // tbl_products table
    $query = "SELECT * FROM tbl_products WHERE product_id = '$product_id'";
    $result = mysqli_query($conn, $query);
    $product = mysqli_fetch_assoc($result);
    
    // Map column names for compatibility
    if ($product) {
        $product['productId'] = $product['product_id'];
        $product['pname'] = $product['product_name'];
        $product['price'] = $product['product_price'];
        $product['productImg'] = $product['product_image'];
    }
}

if (!$product) {
    header('Location: viewProduct.php');
    exit;
}

// Set default values for optional fields
if (!isset($product['cat_name'])) {
    $product['cat_name'] = 'Uncategorized';
}
if (!isset($product['status'])) {
    $product['status'] = 1;
}

// Determine correct image path based on table
$imagePath = '';
if (!empty($product['productImg'])) {
    if ($productTable === 'tbl_products') {
        // Vendor products are in vendor/uploads/
        $candidates = ['vendor/uploads/' . $product['productImg'], 'uploads/' . $product['productImg'], $product['productImg']];
    } else {
        // Admin products are in uploads/
        $candidates = ['uploads/' . $product['productImg'], 'vendor/uploads/' . $product['productImg'], $product['productImg']];
    }
    foreach ($candidates as $cand) { if (file_exists(__DIR__ . '/' . $cand)) { $imagePath = $cand; break; } }
    if (!$imagePath) $imagePath = $candidates[0];
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <title><?php echo $product['pname']; ?> - Product Details</title>
    <link rel="stylesheet" href="dist/css/app.css" />
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <?php include 'sideMenu.php' ?>
        <div class="content">
            <div class="intro-y flex items-center mt-8">
                <h2 class="text-lg font-medium mr-auto">Product Details</h2>
            </div>
            <div class="grid grid-cols-12 gap-6 mt-5">
                <div class="intro-y col-span-12 lg:col-span-8">
                    <div class="intro-y box p-5">
                        <div class="mb-4">
                            <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($product['pname']); ?></h3>
                            <p class="text-gray-600">Category: <strong><?php echo htmlspecialchars($product['cat_name']); ?></strong></p>
                            <p class="text-gray-600">Price: <strong>Rs. <?php echo number_format($product['price'], 2); ?></strong></p>
                        </div>
                        <div class="mb-4">
                            <?php if (!empty($imagePath)) { ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['pname']); ?>" class="w-full max-w-lg" onerror="this.style.display='none'">
                            <?php } ?>
                            <div style="<?php echo empty($imagePath) ? 'display:flex' : 'display:none'; ?>;width:100%;max-width:500px;height:300px;background:#f0f0f0;border-radius:8px;align-items:center;justify-content:center;color:#999;font-size:14px;">No Image Available</div>
                        </div>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500">Product ID: <?php echo $product['productId']; ?></p>
                            <p class="text-sm text-gray-500">Status: <?php echo ($product['status'] == 1) ? 'Active' : 'Inactive'; ?></p>
                        </div>
                        <div class="mt-6">
                            <a href="editProduct.php?product_id=<?php echo $product['productId']; ?>" class="btn btn-primary mr-2">Edit</a>
                            <a href="viewProduct.php" class="btn btn-secondary">Back to Products</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="dist/js/app.js"></script>
</body>
</html>
