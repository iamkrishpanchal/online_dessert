<?php
include 'session.php';
include 'connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$vendor = null;
$vendor_id = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : null;
$vendor_name_param = isset($_GET['vendor_name']) ? $_GET['vendor_name'] : null;

// Fetch vendor details
if ($vendor_id) {
    $stmt = mysqli_prepare($conn, 'SELECT vendor_id, vendor_name, shop_name, phone, address, image_path, logo_path, created_at FROM tbl_vendors WHERE vendor_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $vendor_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $vendor = $res ? mysqli_fetch_assoc($res) : null;
} elseif ($vendor_name_param) {
    $stmt = mysqli_prepare($conn, 'SELECT vendor_id, vendor_name, shop_name, phone, address, image_path, logo_path, created_at FROM tbl_vendors WHERE shop_name = ? OR vendor_name = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'ss', $vendor_name_param, $vendor_name_param);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $vendor = $res ? mysqli_fetch_assoc($res) : null;
    
    if (!$vendor) {
        // Try to find vendor by encoded name
        $decoded_name = urldecode($vendor_name_param);
        $stmt = mysqli_prepare($conn, 'SELECT vendor_id, vendor_name, shop_name, phone, address, image_path, logo_path, created_at FROM tbl_vendors WHERE shop_name = ? OR vendor_name = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'ss', $decoded_name, $decoded_name);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $vendor = $res ? mysqli_fetch_assoc($res) : null;
    }
}

if (!$vendor) {
    echo "<!DOCTYPE html><html><head><title>Vendor Not Found</title><link rel='stylesheet' href='dist/css/app.css' /></head><body class='py-5 md:py-0'><div class='flex mt-[4.7rem] md:mt-0 overflow-hidden'>";
    include 'sideMenu.php';
    echo "<div class='content'>";
    echo "<div class='p-6'><a href='vendor_detail.php' class='btn btn-secondary mb-4'>← Back to Vendors</a><p style='color:#666;margin-top:20px;'>Vendor not found.</p></div></div></div></body></html>";
    exit;
}

// Fetch products for this vendor
$products = [];
$productTable = null;

// Check which product table exists
$tres = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product'");
if ($tres && mysqli_num_rows($tres) > 0) {
    $productTable = 'tbl_product';
} else {
    $tres2 = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_products'");
    if ($tres2 && mysqli_num_rows($tres2) > 0) {
        $productTable = 'tbl_products';
    }
}

if ($productTable) {
    // Detect vendor column
    $colRes = @mysqli_query($conn, "SHOW COLUMNS FROM " . $productTable);
    $cols = [];
    if ($colRes) {
        while ($crow = mysqli_fetch_assoc($colRes)) $cols[] = $crow['Field'];
    }
    
    // Detect vendor id/name column
    $vendorIdField = null;
    $vendorNameField = null;
    $possibleIdFields = ['vendor_id', 'vendorId', 'vendorid', 'vendor'];
    $possibleNameFields = ['vendor_name', 'shop_name', 'vendorname', 'shopname'];
    
    foreach ($cols as $c) {
        if (in_array($c, $possibleIdFields)) $vendorIdField = $c;
        if (in_array($c, $possibleNameFields)) $vendorNameField = $c;
    }

    // Build SELECT based on table schema
    if ($productTable === 'tbl_product') {
        $select = 'productId, pname, price, productImg, status';
        $idCol = 'productId';
    } else {
        $select = 'product_id AS productId, product_name AS pname, product_price AS price, product_image AS productImg, stock AS status';
        $idCol = 'product_id';
    }

    // Fetch products by vendor ID
    if ($vendorIdField && $vendor['vendor_id']) {
        $sql = "SELECT " . $select . " FROM " . $productTable . " WHERE `$vendorIdField` = ? ORDER BY " . $idCol . " DESC";
        $pstmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($pstmt, 'i', $vendor['vendor_id']);
        mysqli_stmt_execute($pstmt);
        $pres = mysqli_stmt_get_result($pstmt);
        if ($pres) while ($prow = mysqli_fetch_assoc($pres)) $products[] = $prow;
    }
    
    // If no products found and we have vendor name field, try by name
    if (empty($products) && $vendorNameField) {
        $shop = $vendor['shop_name'] ?: $vendor['vendor_name'];
        $sql = "SELECT " . $select . " FROM " . $productTable . " WHERE `$vendorNameField` = ? ORDER BY " . $idCol . " DESC";
        $pstmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($pstmt, 's', $shop);
        mysqli_stmt_execute($pstmt);
        $pres = mysqli_stmt_get_result($pstmt);
        if ($pres) while ($prow = mysqli_fetch_assoc($pres)) $products[] = $prow;
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($vendor['shop_name'] ?: $vendor['vendor_name']); ?> - Products</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        .vendor-header {
            background: linear-gradient(135deg, #2c355a 0%, #433f77 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 8px;
            display: flex;
            gap: 30px;
            align-items: flex-start;
            margin-bottom: 30px;
        }
        .vendor-header-img {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
        }
        .vendor-header-img-placeholder {
            width: 140px;
            height: 140px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.6);
            font-size: 12px;
        }
        .vendor-header-info {
            flex-grow: 1;
        }
        .vendor-header-info h2 {
            margin: 0 0 12px 0;
            font-size: 28px;
            font-weight: 700;
        }
        .vendor-header-info p {
            margin: 6px 0;
            font-size: 14px;
            opacity: 0.95;
        }
        .vendor-header-info .label {
            font-weight: 600;
            opacity: 1;
        }
        .back-button {
            background: rgba(0, 0, 0, 0.2);
            color: white;
            border: 1px solid rgba(29, 26, 26, 0.3);
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-block;
        }
        .back-button:hover {
            background: rgba(42, 37, 37, 0.3);
        }
        .products-container { padding: 20px; }
        .product-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .product-card {
            background: #fff;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,.06);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,.12);
        }
        .product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
            background: #f4f4f4;
            margin-bottom: 12px;
        }
        .product-img-placeholder {
            width: 100%;
            height: 140px;
            background: #e5e7eb;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            margin-bottom: 12px;
        }
        .product-info {
            flex-grow: 1;
        }
        .product-info h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 8px;
        }
        .product-status {
            display: inline-block;
            background: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            border-top: 1px solid #eee;
            padding-top: 12px;
        }
        .product-actions a {
            flex: 1;
            padding: 8px;
            text-align: center;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-view {
            background: #e7f3ff;
            color: #0066cc;
        }
        .btn-view:hover {
            background: #0066cc;
            color: white;
        }
    </style>
</head>
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

            <div class="products-container">
                <a href="viewProduct.php" class="back-button">← Back to All the Shops</a>

                <!-- Vendor Header -->
                <div class="vendor-header" style="margin-top: 20px;">
                    <?php 
                        // prefer shop logo, fallback to no-photo placeholder
                        if (!empty($vendor['logo_path'])) {
                            $vendorImgPath = 'uploads/vendors/' . $vendor['logo_path'];
                        } else {
                            $vendorImgPath = '';
                        }
                    ?>
                    <?php if (!empty($vendorImgPath)) { ?>
                        <img class="vendor-header-img" src="<?php echo htmlspecialchars($vendorImgPath); ?>" alt="<?php echo htmlspecialchars($vendor['shop_name']); ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <?php } ?>
                    <div class="vendor-header-img-placeholder" style="<?php echo empty($vendorImgPath) ? 'display:flex' : 'display:none'; ?>">No Logo</div>
                    
                    <div class="vendor-header-info">
                        <h2><?php echo htmlspecialchars($vendor['shop_name'] ?: $vendor['vendor_name']); ?></h2>
                        <br>
                        <p><span class="label">Phone:</span> <?php echo htmlspecialchars($vendor['phone']); ?></p>
                        <p><span class="label">Address:</span> <?php echo htmlspecialchars($vendor['address']); ?></p>
                    </div>
                </div>

                <!-- Products Section -->
                <div class="intro-y flex items-center mt-8">
                    <h2 class="text-lg font-medium mr-auto">Products (<?php echo count($products); ?>)</h2>
                </div>

                <?php if (empty($products)) { ?>
                    <p class="text-gray-600 mt-6">No products found for this vendor.</p>
                <?php } else { ?>
                    <div class="product-cards-grid">
                        <?php foreach ($products as $p) { 
                            $imgPath = null;
                            if (!empty($p['productImg'])) {
                                if ($productTable === 'tbl_products') {
                                    $candidates = ['vendor/uploads/' . $p['productImg'], 'uploads/' . $p['productImg'], $p['productImg']];
                                } else {
                                    $candidates = ['uploads/' . $p['productImg'], $p['productImg']];
                                }
                                foreach ($candidates as $cand) { if (file_exists(__DIR__ . '/' . $cand)) { $imgPath = $cand; break; } }
                                if (!$imgPath) $imgPath = $candidates[0]; // fallback to first candidate
                            }
                        ?>
                            <div class="product-card">
                                <?php if ($imgPath) { ?>
                                    <img class="product-img" src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($p['pname']); ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <?php } ?>
                                <div class="product-img-placeholder" style="<?php echo empty($imgPath) ? 'display:flex' : 'display:none'; ?>">No Image</div>
                                
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($p['pname']); ?></h3>
                                    <div class="product-price">Rs. <?php echo htmlspecialchars($p['price']); ?></div>
                                    <span class="product-status"><?php echo htmlspecialchars($p['status']); ?></span>
                                </div>

                                <!-- <div class="product-actions">
                                    <a href="single_product.php?productId=<?php echo $p['productId']; ?>" class="btn-view">View</a>
                                    <a href="delete_product_admin.php?product_id=<?php echo intval($p['productId']); ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                </div> -->
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

        </div>
        <!-- END: Content -->
    </div>

    <!-- BEGIN: JS Assets-->
    <script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
    <script src="dist/js/app.js"></script>
    <!-- END: JS Assets-->
</body>
</html>
