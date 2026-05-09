<!DOCTYPE html>
<html lang="en">
<head>
    <title>Category Products - FoodMart</title>
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
        /* grid card layout reused from vendor page */
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
        .product-category {
            font-size: 12px;
            color: #999;
            margin-bottom: 8px;
        }
        .product-shop {
            font-size: 12px;
            color: #555;
            margin-bottom: 8px;
        }
        .product-shop a {
            color: #555;
            text-decoration: none;
        }
        .product-shop a:hover {
            text-decoration: underline;
        }
        .product-name {
            font-weight: 600;
            font-size: 14px;
            margin: 0 0 8px 0;
            min-height: 40px;
        }
        .product-price {
            font-size: 16px;
            font-weight: 700;
            color: #ff6b6b;
            margin: auto 0 10px 0;
        }
        .product-footer {
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 8px;
            justify-content: flex-start;
        }
        .btn-view, .btn-cart {
            flex: 1 1 0;
            min-width: 0;
            padding: 4px 10px;
            min-height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #111827;
            text-align: center;
            border-radius: 10px;
            text-decoration: none;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            transition: transform 0.2s ease, background 0.25s ease, box-shadow 0.2s ease, color 0.25s ease;
            border: 1px solid transparent;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
            letter-spacing: 0.03em;
        }
        .btn-view {
            background: #ffffff;
            color: #111827;
            border-color: #d1d5db;
        }
        .btn-view:hover {
            background: #f8fafc;
            color: #111827;
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
        }
        .btn-cart {
            background: #111827;
            color: #ffffff;
            border-color: #111827;
        }
        .btn-cart:hover {
            background: #0f172a;
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.12);
        }
        button.btn-cart[disabled] {
            background: #f3f4f6 !important;
            color: #6b7280 !important;
            border-color: #d1d5db !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
        }
        .no-products {
            background: #e3f2fd;
            padding: 40px 20px;
            border-radius: 8px;
            text-align: center;
            color: #1976d2;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
        <defs>
            <symbol xmlns="http://www.w3.org/2000/svg" id="cart" viewBox="0 0 24 24">
                <path fill="currentColor" d="M8.5 19a1.5 1.5 0 1 0 1.5 1.5A1.5 1.5 0 0 0 8.5 19ZM19 16H7a1 1 0 0 1 0-2h8.491a3.013 3.013 0 0 0 2.885-2.176l1.585-5.55A1 1 0 0 0 19 5H6.74a3.007 3.007 0 0 0-2.82-2H3a1 1 0 0 0 0 2h.921a1.005 1.005 0 0 1 .962.725l.155.545v.005l1.641 5.742A3 3 0 0 0 7 18h12a1 1 0 0 0 0-2Zm-1.326-9l-1.22 4.274a1.005 1.005 0 0 1-.963.726H8.754l-.255-.892L7.326 7ZM16.5 19a1.5 1.5 0 1 0 1.5 1.5a1.5 1.5 0 0 0-1.5-1.5Z"/>
            </symbol>
            <symbol xmlns="http://www.w3.org/2000/svg" id="heart" viewBox="0 0 24 24">
                <path fill="currentColor" d="M20.16 4.61A6.27 6.27 0 0 0 12 4a6.27 6.27 0 0 0-8.16 9.48l7.45 7.45a1 1 0 0 0 1.42 0l7.45-7.45a6.27 6.27 0 0 0 0-8.87Zm-1.41 7.46L12 18.81l-6.75-6.74a4.28 4.28 0 0 1 3-7.3a4.25 4.25 0 0 1 3 1.25a1 1 0 0 0 1.42 0a4.27 4.27 0 0 1 6 6.05Z"/>
            </symbol>
            <symbol xmlns="http://www.w3.org/2000/svg" id="user" viewBox="0 0 24 24">
                <path fill="currentColor" d="M15.71 12.71a6 6 0 1 0-7.42 0a10 10 0 0 0-6.22 8.18a1 1 0 0 0 2 .22a8 8 0 0 1 15.9 0a1 1 0 0 0 1 .89h.11a1 1 0 0 0 .88-1.1a10 10 0 0 0-6.25-8.19ZM12 12a4 4 0 1 1 4-4a4 4 0 0 1-4 4Z"/>
            </symbol>
            <symbol xmlns="http://www.w3.org/2000/svg" id="search" viewBox="0 0 24 24">
                <path fill="currentColor" d="M21.71 20.29L18 16.61A9 9 0 1 0 16.61 18l3.68 3.68a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.39ZM11 18a7 7 0 1 1 7-7a7 7 a 0 0 1-7 7Z"/>
            </symbol>
        </defs>
    </svg>

    <header>
        <?php include 'header.php'; ?>
    </header>

    <main>
        <div class="container">
            <div class="py-5">
                <?php
                include 'connection.php';

                // helper copied from index.php to resolve stored filenames into usable paths
                function findImagePath($filename, $default = 'images/default-product.png') {
                    $filename = trim((string)$filename);
                    if ($filename === '') return $default;
                    $cands = [
                        $filename,
                        '../' . $filename,
                        'uploads/' . $filename,
                        '../uploads/' . $filename,
                        'uploads/vendors/' . $filename,
                        '../uploads/vendors/' . $filename,
                        'admin/vendor/uploads/' . $filename,
                        '../admin/vendor/uploads/' . $filename,
                        'user/uploads/products/' . $filename,
                        '../user/uploads/products/' . $filename,
                    ];
                    foreach ($cands as $cand) {
                        if (file_exists(__DIR__ . '/' . $cand)) {
                            return $cand;
                        }
                    }
                    if (preg_match('#^https?://#i', $filename)) {
                        return $filename;
                    }
                    return $default;
                }

                $category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                $vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;  // Get selected vendor if any

                if ($category_id == 0) {
                    echo '<h2>Please select a category</h2>';
                    echo '<p><a href="index.php">Back to Home</a></p>';
                } else {
                    // Fetch category details (including any image set by the vendor for this category row)
                    $cat_query = "SELECT categories_id, categories_name, categories_image FROM tbl_categories WHERE categories_id = ? LIMIT 1";
                    $stmt = mysqli_prepare($conn, $cat_query);
                    mysqli_stmt_bind_param($stmt, "i", $category_id);
                    mysqli_stmt_execute($stmt);
                    $cat_result = mysqli_stmt_get_result($stmt);
                    $category = mysqli_fetch_assoc($cat_result);

                    if (!$category) {
                        echo '<h2>Category not found</h2>';
                        echo '<p><a href="index.php">Back to Home</a></p>';
                    } else {
                        ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div style="background:linear-gradient(135deg, #FFE6D9 0%, #FFD9E6 100%);padding:30px;border-radius:12px;color:#333;">
                                    <h1 class="mb-0" style="font-weight:700;color:#333;"><?php echo htmlspecialchars($category['categories_name']); ?></h1>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Show all products in this category (from all vendors)
                        $products = [];
                        
                        // first attempt - only products linking to this exact category_id
                        $pquery = "SELECT p.*, v.is_online, v.shop_name AS vendor_shop_name, v.vendor_discount_percent
                                   FROM tbl_products p
                                   LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id
                                   WHERE p.category_id = ?
                                   ORDER BY p.product_name";
                        $pstmt = mysqli_prepare($conn, $pquery);
                        if ($pstmt) { 
                            mysqli_stmt_bind_param($pstmt, 'i', $category_id); 
                            mysqli_stmt_execute($pstmt); 
                            $pres = mysqli_stmt_get_result($pstmt); 
                            $products = $pres ? mysqli_fetch_all($pres, MYSQLI_ASSOC) : []; 
                            mysqli_stmt_close($pstmt); 
                        }
                        // if no products are found, try matching by category name (covers duplicate-name rows)
                        if (empty($products) && !empty($category['categories_name'])) {
                            $name = $category['categories_name'];
                            $pquery = "SELECT p.*, v.is_online, v.shop_name AS vendor_shop_name, v.vendor_discount_percent
                                       FROM tbl_products p 
                                       JOIN tbl_categories c ON p.category_id = c.categories_id
                                       LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id
                                       WHERE c.categories_name = ?
                                       ORDER BY p.product_name";
                            $pstmt = mysqli_prepare($conn, $pquery);
                            if ($pstmt) {
                                mysqli_stmt_bind_param($pstmt, 's', $name);
                                mysqli_stmt_execute($pstmt);
                                $pres = mysqli_stmt_get_result($pstmt);
                                $products = $pres ? mysqli_fetch_all($pres, MYSQLI_ASSOC) : [];
                                mysqli_stmt_close($pstmt);
                            }
                        }
                            
                            // Prefetch vendor names and online status to avoid N+1 queries
                            $vendorNames = [];
                            $vendorStatuses = [];
                            if (!empty($products)) {
                                foreach ($products as $row) {
                                    $vid = intval($row['vendor_id'] ?? 0);
                                    if ($vid > 0) {
                                        $vendorStatuses[$vid] = intval($row['is_online'] ?? ($vendorStatuses[$vid] ?? 0));
                                    }
                                }

                                $vendorIds = array_unique(array_filter(array_map(function($x){ return isset($x['vendor_id']) ? intval($x['vendor_id']) : 0; }, $products)));
                                $vendorDiscounts = [];
if (!empty($vendorIds)) {
                                    $in = implode(',', array_map('intval', $vendorIds));
                                    // check for discount column
                                    $colRes = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'vendor_discount_percent'");
                                    $hasDiscCol = $colRes && mysqli_num_rows($colRes) > 0;
                                    $selectCols = 'vendor_id, shop_name, vendor_name';
                                    if ($hasDiscCol) {
                                        $selectCols .= ', vendor_discount_percent';
                                    }
                                    $vres = mysqli_query($conn, "SELECT {$selectCols} FROM tbl_vendors WHERE vendor_id IN (" . $in . ")");
                                    if ($vres) {
                                        while ($vr = mysqli_fetch_assoc($vres)) {
                                            $vid = intval($vr['vendor_id']);
                                            $vendorNames[$vid] = $vr['shop_name'] ?: $vr['vendor_name'];
                                            $vendorDiscounts[$vid] = floatval($vr['vendor_discount_percent'] ?? 0);
                                        }
                                    }
                                }
                            }

                            // Remove products whose vendor record is missing (hide unknown vendors)
                            if (!empty($products)) {
                                $products = array_values(array_filter($products, function($row) use ($vendorNames) {
                                    $vid = intval($row['vendor_id'] ?? $row['vendor'] ?? 0);
                                    if ($vid <= 0) return true; // keep products without vendor_id
                                    return isset($vendorNames[$vid]);
                                }));
                            }

                            // Resolve image path for every product; do NOT exclude products if the image file is missing.
                            if (!empty($products)) {
                                foreach ($products as $k => $row) {
                                    $pimg = $row['product_image'] ?? '';
                                    $products[$k]['_resolved_image'] = findImagePath($pimg);
                                }
                            }

                        if (empty($products)) {
                            echo '<div class="alert alert-info">No products found for this category.</div>';
                        } else {
                            echo '<h3 style="margin-bottom:25px;">Products in ' . htmlspecialchars($category['categories_name']) . ' (' . count($products) . ')</h3>';
                            echo '<div class="products-grid">';
                            foreach ($products as $p) {
                                $pid = $p['product_id'] ?? '';
                                $pname = $p['product_name'] ?? 'Untitled';
                                $categoryName = htmlspecialchars($category['categories_name']);
                                $price = floatval($p['product_price'] ?? 0);
                                $pimg = $p['_resolved_image'] ?? $p['product_image'] ?? '';
                                $discountPct = 0;
                                if (isset($p['discount_percent']) && floatval($p['discount_percent']) > 0) {
                                    $discountPct = floatval($p['discount_percent']);
                                } elseif (isset($p['discount_price']) && floatval($p['discount_price']) > 0 && floatval($p['discount_price']) < $price && $price > 0) {
                                    $discountPct = round((1 - floatval($p['discount_price']) / $price) * 100);
                                } elseif (isset($p['discount']) && floatval($p['discount']) > 0 && $price > 0) {
                                    $discountPct = round(floatval($p['discount']) / $price * 100);
                                }
                                // vendor-level discount may apply if it's larger than product discount
                                $vid = intval($p['vendor_id'] ?? $p['vendor'] ?? 0);
                                if ($vid > 0 && isset($vendorDiscounts[$vid]) && $vendorDiscounts[$vid] > $discountPct) {
                                    $discountPct = $vendorDiscounts[$vid];
                                }
                                $displayPrice = $price;
                                if ($discountPct > 0) {
                                    $displayPrice = round($price * (1 - $discountPct/100),2);
                                }
                                // use helper to resolve path
                                $resolved_img = findImagePath($pimg);
                                ?>
                                <?php $vendor_is_online = intval($vendorStatuses[$vid] ?? 1); ?>
                                <div class="product-item position-relative<?php echo $vendor_is_online ? '' : ' offline'; ?>" data-vendor-online="<?php echo $vendor_is_online; ?>">
                                    <a href="#" class="btn-wishlist position-absolute" style="right:8px;top:8px;" data-product-id="<?php echo htmlspecialchars($pid); ?>" onclick="event.preventDefault(); event.stopPropagation(); if(WishlistManager.isInWishlist(<?php echo intval($pid); ?>)) { WishlistManager.removeFromWishlist(<?php echo intval($pid); ?>); } else { WishlistManager.addToWishlist(<?php echo intval($pid); ?>, '<?php echo addslashes($pname); ?>', '<?php echo addslashes(number_format($displayPrice,2)); ?>', '<?php echo addslashes($resolved_img); ?>'); }"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                                    <img src="<?php echo htmlspecialchars($resolved_img); ?>" alt="<?php echo htmlspecialchars($pname); ?>" class="product-image" onerror="this.src='images/default-product.png'">
                                    <div class="product-details">
                                        <p class="product-category"><?php echo $categoryName; ?></p>
                                        <?php if ($vid > 0 && isset($vendorNames[$vid])): ?>
                                            <p class="product-shop">by <a href="vendor_products.php?vendor_id=<?php echo urlencode($vid); ?>"><?php echo htmlspecialchars($vendorNames[$vid]); ?></a><?php if (!$vendor_is_online): ?> <span class="badge" style="background:#f8f9fa;color:#000;border:1px solid #ced4da;">Offline</span><?php endif; ?></p>
                                        <?php endif; ?>
                                        <h3 class="product-name"><?php echo htmlspecialchars($pname); ?></h3>
                                        <?php if ($discountPct > 0): ?>
                                            <div class="discount-text"><?php echo htmlspecialchars($discountPct); ?>% off</div>
                                            <p class="product-price" style="text-decoration: line-through; color: #999;">Rs. <?php echo htmlspecialchars(number_format($price,2)); ?></p>
                                        <?php endif; ?>
                                        <p class="product-price">Rs. <?php echo htmlspecialchars(number_format($displayPrice,2)); ?></p>
                                        <div class="product-footer">
                                            <?php // offline badge already shown in vendor line so no overlay in card body ?>
                                            <a href="viewProduct.php?id=<?php echo urlencode($pid); ?>" class="btn-view">View</a>
                                            <?php if ($vendor_is_online): ?>
                                                <form method="post" action="add_to_cart.php" style="flex: 1;">
                                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($pid); ?>">
                                                    <input type="hidden" name="vendor_id" value="<?php echo htmlspecialchars($vid); ?>">
                                                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($pname); ?>">
                                                    <input type="hidden" name="price" value="<?php echo htmlspecialchars(($discountPct>0) ? $displayPrice : $price); ?>">
                                                    <input type="hidden" name="image" value="<?php echo htmlspecialchars($resolved_img); ?>">
                                                    <input type="hidden" name="quantity" value="1">
                                                    <button type="submit" class="btn-cart">Add to Cart</button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn-cart" style="background:#6c757d;cursor:not-allowed;" disabled>Offline</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            echo '</div>';
                        }
                    }
                }
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
