<!DOCTYPE html>
<html lang="en">
<head>
    <title>Products by Vendor - FoodMart</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <style>
        .vendor-section {
            background: linear-gradient(135deg, #FFE6D9 0%, #FFD9E6 100%);
            padding: 30px;
            border-radius: 12px;
            margin: 30px 0;
        }
        .vendor-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }
        .vendor-logo {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .vendor-info h2 {
            margin: 0;
            color: #333;
            font-weight: 700;
            font-size: 24px;
        }
        .vendor-info p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        .product-count {
            background: #ff6b6b;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-left: 10px;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .product-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .product-item.offline {
            position: relative;
            filter: grayscale(1) brightness(0.92);
            opacity: 0.95;
        }
        .product-item.offline::before {
            content: "Offline";
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 10;
            pointer-events: none;
            background: rgba(0,0,0,0.72);
            color: #fff;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            box-shadow: 0 6px 16px rgba(0,0,0,0.18);
        }
        .product-item.offline::after {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.08);
            border-radius: inherit;
            pointer-events: none;
        }
        .product-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f5f5f5;
        }
        .product-details {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .product-name {
            font-weight: 600;
            font-size: 14px;
            margin: 0 0 8px 0;
            min-height: 40px;
            display: flex;
            align-items: flex-start;
        }
        .product-category {
            font-size: 12px;
            color: #999;
            margin-bottom: 8px;
        }
        .product-price {
            font-size: 16px;
            font-weight: 700;
            color: #ff6b6b;
            margin: 10px 0;
        }
        .product-footer {
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .btn-view {
            display: block;
            width: 100%;
            padding: 12px 16px;
            min-height: 46px;
            background: #ffffff;
            color: #111827;
            text-align: center;
            border-radius: 14px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            transition: transform 0.2s ease, background 0.3s ease, box-shadow 0.3s ease, color 0.3s ease;
            border: 1px solid #d1d5db;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            letter-spacing: 0.04em;
        }
        .btn-view:hover {
            background: #f8fafc;
            color: #111827;
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
            text-decoration: none;
        }
        .no-products {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            color: #1976d2;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <header>
        <?php include 'header.php'; ?>
    </header>

    <main>
        <div class="container">
            <div class="py-5">
                <h1 style="margin-bottom: 30px; font-weight: 700;">Browse Products by Vendor</h1>

                <?php
                include 'connection.php';

                // Get all vendors with their products
                $vendor_query = "SELECT DISTINCT v.vendor_id, v.vendor_name, v.shop_name, v.logo_path, v.image_path, v.is_online,
                                COUNT(p.product_id) as product_count
                                FROM tbl_vendors v
                                LEFT JOIN tbl_products p ON v.vendor_id = p.vendor_id
                                WHERE v.vendor_id IS NOT NULL
                                GROUP BY v.vendor_id, v.vendor_name, v.shop_name, v.logo_path, v.image_path, v.is_online
                                ORDER BY product_count DESC, v.shop_name ASC";

                $vendor_result = mysqli_query($conn, $vendor_query);
                $vendors = mysqli_fetch_all($vendor_result, MYSQLI_ASSOC);

                if (empty($vendors)) {
                    echo '<div class="no-products">No vendors found.</div>';
                } else {
                    foreach ($vendors as $vendor) {
                        $vendor_id = $vendor['vendor_id'];
                        $vendor_name = $vendor['shop_name'] ?: $vendor['vendor_name'];
                        $product_count = $vendor['product_count'];
                        $logo = $vendor['logo_path'] ?: $vendor['image_path'];
                        $vendor_is_online = intval($vendor['is_online'] ?? 0);

                        // Resolve vendor logo
                        // use a stable placeholder when no logo file is available
                        $vendor_logo = 'https://via.placeholder.com/120?text=Shop';
                        if ($logo) {
                            $logo_candidates = [
                                $logo,
                                '../admin/vendor/uploads/' . basename($logo),
                                '../admin/uploads/vendors/' . basename($logo),
                                '../uploads/vendors/' . basename($logo),
                                '../admin/uploads/vendors/' . basename($logo),
                                '../'.$logo,
                            ];
                        // also try stripping admin/ prefix if present
                        if (strpos($logo, 'admin/') === 0) {
                            $altLogo = substr($logo, strlen('admin/'));
                            $logo_candidates[] = $altLogo;
                            $logo_candidates[] = '../'.$altLogo;
                        }
                            foreach ($logo_candidates as $cand) {
                                if (file_exists(__DIR__ . '/' . $cand)) {
                                    $vendor_logo = $cand;
                                    break;
                                }
                            }
                        }

                        ?>
                        <div class="vendor-section">
                            <div class="vendor-header">
                                <img src="<?php echo htmlspecialchars($vendor_logo); ?>" alt="<?php echo htmlspecialchars($vendor_name); ?>" class="vendor-logo" onerror="this.onerror=null;this.src='https://via.placeholder.com/120?text=Shop'">
                                <div class="vendor-info">
                                    <h2>
                                        <?php echo htmlspecialchars($vendor_name); ?>
                                        <?php if (!$vendor_is_online): ?><span class="badge bg-secondary ms-2">Offline</span><?php endif; ?>
                                        <span class="product-count"><?php echo $product_count; ?> Products</span>
                                    </h2>
                                    <p>
                                        <a href="<?php echo 'all_vendor_products.php?vendor_id=' . $vendor_id; ?>" style="color: #ff6b6b; text-decoration: none; font-weight: 600;">
                                            View All Products →
                                        </a>
                                    </p>
                                </div>
                            </div>

                            <?php
                            if ($product_count == 0) {
                                echo '<div class="no-products">This vendor has not added any products yet.</div>';
                            } else {
                                // Get products for this vendor
                                // include discount columns if present
                                $extraCols = '';
                                $colRes = mysqli_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_products' AND COLUMN_NAME IN ('discount_percent','discount_price','discount')");
                                if ($colRes) {
                                    while ($crow = mysqli_fetch_assoc($colRes)) {
                                        $extraCols .= ', p.' . $crow['COLUMN_NAME'];
                                    }
                                }
                                $prod_query = "SELECT p.product_id, p.product_name, p.category_id, c.categories_name, 
                                             p.product_price, p.product_image, p.product_stock" . $extraCols . "
                                             FROM tbl_products p
                                             LEFT JOIN tbl_categories c ON p.category_id = c.categories_id
                                             WHERE p.vendor_id = ?
                                             ORDER BY c.categories_name, p.product_name
                                             LIMIT 8";

                                $prod_stmt = mysqli_prepare($conn, $prod_query);
                                mysqli_stmt_bind_param($prod_stmt, "i", $vendor_id);
                                mysqli_stmt_execute($prod_stmt);
                                $prod_result = mysqli_stmt_get_result($prod_stmt);
                                $products = mysqli_fetch_all($prod_result, MYSQLI_ASSOC);
                                mysqli_stmt_close($prod_stmt);

                                echo '<div class="products-grid">';

                                foreach ($products as $p) {
                                    $pid = $p['product_id'];
                                    $pname = $p['product_name'];
                                    $category = $p['categories_name'] ?: 'Uncategorized';
                                    $price = $p['product_price'];
                                    $pimg = $p['product_image'];
                                    $discountPct = 0;
                                    // if vendor is offline, mark accordingly (same for all products here)
                                    // $vendor_is_online already computed above
                                    if (isset($p['discount_percent']) && floatval($p['discount_percent']) > 0) {
                                        $discountPct = floatval($p['discount_percent']);
                                    } elseif (isset($p['discount_price']) && floatval($p['discount_price']) > 0 && floatval($p['discount_price']) < floatval($price) && floatval($price) > 0) {
                                        $discountPct = round((1 - floatval($p['discount_price']) / floatval($price)) * 100);
                                    } elseif (isset($p['discount']) && floatval($p['discount']) > 0 && floatval($price) > 0) {
                                        $discountPct = round(floatval($p['discount']) / floatval($price) * 100);
                                    }
                                    $displayPrice = $price;
                                    if ($discountPct > 0) {
                                        $displayPrice = round($price * (1 - $discountPct / 100), 2);
                                    }

                                    // Resolve product image
                                    $resolved_img = 'images/default-product.png';
                                    if ($pimg && !preg_match('#^https?://#', $pimg)) {
                                        $img_candidates = [
                                            $pimg,
                                            'uploads/' . $pimg,
                                            '../admin/vendor/uploads/' . basename($pimg),
                                            '../admin/uploads/vendors/' . basename($pimg),
                                            '../uploads/vendors/' . basename($pimg),
                                        ];
                                        foreach ($img_candidates as $cand) {
                                            if (file_exists(__DIR__ . '/' . $cand)) {
                                                $resolved_img = $cand;
                                                break;
                                            }
                                        }
                                    } else {
                                        $resolved_img = $pimg;
                                    }

                                    // Skip product if only placeholder remains
                                    if ($resolved_img === 'images/default-product.png') {
                                        continue;
                                    }

                                    ?>
                                    <div class="product-item position-relative<?php echo $vendor_is_online ? '' : ' offline'; ?>" data-vendor-online="<?php echo $vendor_is_online; ?>">
                                        <img src="<?php echo htmlspecialchars($resolved_img); ?>" alt="<?php echo htmlspecialchars($pname); ?>" class="product-image" onerror="this.src='images/default-product.png'">
                                        <div class="product-details">
                                            <p class="product-category"><?php echo htmlspecialchars($category); ?></p>
                                            <h3 class="product-name"><?php echo htmlspecialchars($pname); ?></h3>
                                            <?php if ($discountPct > 0): ?>
                                                <div class="discount-text"><?php echo htmlspecialchars($discountPct); ?>% off</div>
                                            <p class="product-price" style="text-decoration: line-through; color:#999;">₹<?php echo htmlspecialchars(number_format($price,2)); ?></p>
                                        <?php endif; ?>
                                        <p class="product-price">₹<?php echo htmlspecialchars(number_format($displayPrice,2)); ?></p>
                                            <div class="product-footer">
                                                <a href="viewProduct.php?id=<?php echo urlencode($pid); ?>" class="btn-view">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }

                                echo '</div>';

                                if ($product_count > 8) {
                                    ?>
                                    <div style="text-align: center; margin-top: 20px;">
                                        <a href="<?php echo 'all_vendor_products.php?vendor_id=' . $vendor_id; ?>" style="color: #ff6b6b; font-weight: 600; text-decoration: none;">
                                            View all <?php echo $product_count; ?> products from <?php echo htmlspecialchars($vendor_name); ?> →
                                        </a>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        <?php
                    }
                }

                mysqli_close($conn);
                ?>
            </div>
        </div>
    </main>

    <footer>
        <?php include 'footer.php'; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
