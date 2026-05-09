<!DOCTYPE html>
<html lang="en">
<head>
    <title>Vendor Products - FoodMart</title>
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
        .vendor-banner {
            background: linear-gradient(135deg, #FFE6D9 0%, #FFD9E6 100%);
            padding: 40px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        .vendor-banner-logo {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .vendor-banner-info h1 {
            margin: 0;
            color: #333;
            font-weight: 700;
            font-size: 32px;
        }
        .vendor-banner-info p {
            margin: 10px 0 0 0;
            color: #666;
            font-size: 16px;
        }
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
            transition: transform 0.3s ease, box-shadow 0.3s ease, filter 0.3s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .product-item.offline {
            filter: grayscale(1) brightness(0.92);
            opacity: 0.85;
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
        .breadcrumb-custom {
            margin-bottom: 20px;
        }
        .breadcrumb-custom a {
            color: #ff6b6b;
            text-decoration: none;
        }
        .breadcrumb-custom a:hover {
            text-decoration: underline;
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
                <?php
                include 'connection.php';

                $vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
                $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
                $category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;

                if ($vendor_id == 0) {
                    echo '<div class="alert alert-danger">Please select a vendor</div>';
                    echo '<p><a href="vendor_products_list.php">Back to Vendors</a></p>';
                } else {
                    // Get vendor details
                    $vendor_query = "SELECT * FROM tbl_vendors WHERE vendor_id = ? LIMIT 1";
                    $stmt = mysqli_prepare($conn, $vendor_query);
                    mysqli_stmt_bind_param($stmt, "i", $vendor_id);
                    mysqli_stmt_execute($stmt);
                    $vendor_result = mysqli_stmt_get_result($stmt);
                    $vendor = mysqli_fetch_assoc($vendor_result);
                    mysqli_stmt_close($stmt);

                    if (!$vendor) {
                        echo '<div class="alert alert-danger">Vendor not found</div>';
                        echo '<p><a href="vendor_products_list.php">Back to Vendors</a></p>';
                    } else {
                        $vendor_name = $vendor['shop_name'] ?: $vendor['vendor_name'];
                        $vendor_logo = $vendor['logo_path'] ?: $vendor['image_path'];
                        $vendor_is_online = intval($vendor['is_online'] ?? 0);

                        // Resolve vendor logo
                        // placeholder used when no logo exists or lookup fails
                        $resolved_logo = 'https://via.placeholder.com/80?text=Shop';
                        if ($vendor_logo) {
                            $logo_candidates = [
                                $vendor_logo,
                                '../admin/vendor/uploads/' . basename($vendor_logo),
                                '../admin/uploads/vendors/' . basename($vendor_logo),
                                '../uploads/vendors/' . basename($vendor_logo),
                                '../admin/uploads/vendors/' . basename($vendor_logo),
                                '../'.$vendor_logo,
                            ];
                        if (strpos($vendor_logo, 'admin/') === 0) {
                            $altLogo = substr($vendor_logo, strlen('admin/'));
                            $logo_candidates[] = $altLogo;
                            $logo_candidates[] = '../'.$altLogo;
                        }
                            foreach ($logo_candidates as $cand) {
                                if (file_exists(__DIR__ . '/' . $cand)) {
                                    $resolved_logo = $cand;
                                    break;
                                }
                            }
                        }

                        ?>
                        <div class="breadcrumb-custom">
                            <a href="vendor_products_list.php">← Back to All Vendors</a>
                        </div>

                        <div class="vendor-banner">
                            <img src="<?php echo htmlspecialchars($resolved_logo); ?>" alt="<?php echo htmlspecialchars($vendor_name); ?>" class="vendor-banner-logo" onerror="this.onerror=null;this.src='https://via.placeholder.com/80?text=Shop'">
                            <div class="vendor-banner-info">
                                <h1><?php echo htmlspecialchars($vendor_name); ?></h1>
                                <p>
                                    <?php echo htmlspecialchars($vendor['address'] ?? 'Food & Beverage Store'); ?>
                                    <?php if (!$vendor_is_online): ?>
                                        <span style="display:inline-block;background:#6c757d;color:#fff;padding:2px 10px;border-radius:5px;font-size:14px;margin-left:10px;vertical-align:middle;">Offline</span>
                                    <?php endif; ?>
                                </p>
                                <p style="margin-top: 5px; color: #ff6b6b; font-weight: 600;">
                                    <?php
                                    // Count products
                                    $count_query = "SELECT COUNT(*) as cnt FROM tbl_products WHERE vendor_id = ?";
                                    $count_stmt = mysqli_prepare($conn, $count_query);
                                    mysqli_stmt_bind_param($count_stmt, "i", $vendor_id);
                                    mysqli_stmt_execute($count_stmt);
                                    $count_result = mysqli_stmt_get_result($count_stmt);
                                    $count_row = mysqli_fetch_assoc($count_result);
                                    mysqli_stmt_close($count_stmt);
                                    echo $count_row['cnt'] . ' Products';
                                    ?>
                                </p>
                            </div>
                        </div>

                        <?php
                        // Get all categories for filter
                        $cat_query = "SELECT DISTINCT c.categories_id, c.categories_name 
                                     FROM tbl_categories c
                                     INNER JOIN tbl_products p ON c.categories_id = p.category_id
                                     WHERE p.vendor_id = ?
                                     ORDER BY c.categories_name";
                        $cat_stmt = mysqli_prepare($conn, $cat_query);
                        mysqli_stmt_bind_param($cat_stmt, "i", $vendor_id);
                        mysqli_stmt_execute($cat_stmt);
                        $cat_result = mysqli_stmt_get_result($cat_stmt);
                        $categories = mysqli_fetch_all($cat_result, MYSQLI_ASSOC);
                        mysqli_stmt_close($cat_stmt);

                        if (!empty($categories)) {
                            ?>
                            <div class="filter-section">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <label for="categoryFilter" class="form-label">Filter by Category:</label>
                                        <select id="categoryFilter" class="form-select">
                                            <option value="">All Categories</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['categories_id']; ?>" <?php echo $category_filter == $cat['categories_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['categories_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="sortFilter" class="form-label">Sort by:</label>
                                        <select id="sortFilter" class="form-select">
                                            <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Product Name</option>
                                            <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                                            <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                                            <option value="recent" <?php echo $sort == 'recent' ? 'selected' : ''; ?>>Recently Added</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.getElementById('categoryFilter').addEventListener('change', function() {
                                    const cat = this.value;
                                    const url = new URL(window.location);
                                    if (cat) url.searchParams.set('category', cat);
                                    else url.searchParams.delete('category');
                                    window.location = url.toString();
                                });

                                document.getElementById('sortFilter').addEventListener('change', function() {
                                    const sort = this.value;
                                    const url = new URL(window.location);
                                    if (sort) url.searchParams.set('sort', sort);
                                    else url.searchParams.delete('sort');
                                    window.location = url.toString();
                                });
                            </script>
                            <?php
                        }

                        // Get products with filters
                        // detect discount columns
                        $discountCols = [];
                        $colRes = mysqli_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_products' AND COLUMN_NAME IN ('discount_percent','discount_price','discount')");
                        if ($colRes) {
                            while ($crow = mysqli_fetch_assoc($colRes)) {
                                $discountCols[] = $crow['COLUMN_NAME'];
                            }
                        }
                        $prodFields = "p.product_id, p.product_name, p.category_id, c.categories_name, 
                                         p.product_price, p.product_image, p.product_stock, p.created_at";
                        // include `stock` column if present (newer schema uses `stock` for inventory)
                        $stockColRes = mysqli_query($conn, "SHOW COLUMNS FROM tbl_products LIKE 'stock'");
                        if ($stockColRes && mysqli_num_rows($stockColRes) > 0) {
                            $prodFields .= ", p.stock";
                        }
                        foreach ($discountCols as $cd) {
                            $prodFields .= ", p.".$cd;
                        }
                        $products_query = "SELECT $prodFields
                                         FROM tbl_products p
                                         LEFT JOIN tbl_categories c ON p.category_id = c.categories_id
                                         WHERE p.vendor_id = ?";
                        $params = [$vendor_id];
                        $types = "i";

                        if ($category_filter > 0) {
                            $products_query .= " AND p.category_id = ?";
                            $params[] = $category_filter;
                            $types .= "i";
                        }

                        // Add sorting
                        switch ($sort) {
                            case 'price_asc':
                                $products_query .= " ORDER BY p.product_price ASC";
                                break;
                            case 'price_desc':
                                $products_query .= " ORDER BY p.product_price DESC";
                                break;
                            case 'recent':
                                $products_query .= " ORDER BY p.created_at DESC";
                                break;
                            default:
                                $products_query .= " ORDER BY p.product_name ASC";
                        }

                        $prod_stmt = mysqli_prepare($conn, $products_query);
                        mysqli_stmt_bind_param($prod_stmt, $types, ...$params);
                        mysqli_stmt_execute($prod_stmt);
                        $prod_result = mysqli_stmt_get_result($prod_stmt);
                        $products = mysqli_fetch_all($prod_result, MYSQLI_ASSOC);
                        mysqli_stmt_close($prod_stmt);

                        if (empty($products)) {
                            echo '<div class="no-products">No products found for this vendor.</div>';
                        } else {
                            echo '<h3 style="margin-bottom: 25px;">All Products (' . count($products) . ')</h3>';
                            echo '<div class="products-grid">';

                            foreach ($products as $p) {
                                $pid = $p['product_id'];
                                $pname = $p['product_name'];
                                $category = $p['categories_name'] ?: 'Uncategorized';
$price = floatval($p['product_price']);
                                    $pimg = $p['product_image'];
                                    $discountPct = 0;
                                    if (isset($p['discount_percent']) && floatval($p['discount_percent']) > 0) {
                                        $discountPct = floatval($p['discount_percent']);
                                    } elseif (isset($p['discount_price']) && floatval($p['discount_price']) > 0 && floatval($p['discount_price']) < $price && $price > 0) {
                                        $discountPct = round((1 - floatval($p['discount_price']) / $price) * 100);
                                    } elseif (isset($p['discount']) && floatval($p['discount']) > 0 && $price > 0) {
                                        $discountPct = round(floatval($p['discount']) / $price * 100);
                                    }
                                    // apply vendor-level discount if larger
                                    $vendorDisc = floatval($vendor['vendor_discount_percent'] ?? 0);
                                    if ($vendorDisc > $discountPct) {
                                        $discountPct = $vendorDisc;
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

                                // Determine stock from available columns
                                $stock = null;
                                if (isset($p['stock'])) {
                                    $stock = intval($p['stock']);
                                } elseif (isset($p['product_stock'])) {
                                    $stock = intval($p['product_stock']);
                                }
                                $outOfStock = ($stock !== null && $stock <= 0);

                                // Skip products that only have the default placeholder image (missing file)
                                if ($resolved_img === 'images/default-product.png') {
                                    continue;
                                }

                                ?>
                                <div class="product-item position-relative<?php echo !$vendor_is_online ? ' offline' : ''; ?>" data-vendor-online="<?php echo $vendor_is_online; ?>">
                                    <a href="#" class="btn-wishlist position-absolute" style="right:8px;top:8px;z-index:10;" data-product-id="<?php echo htmlspecialchars($pid); ?>" onclick="event.preventDefault(); event.stopPropagation(); if(WishlistManager.isInWishlist(<?php echo intval($pid); ?>)) { WishlistManager.removeFromWishlist(<?php echo intval($pid); ?>); } else { WishlistManager.addToWishlist(<?php echo intval($pid); ?>, '<?php echo addslashes($pname); ?>', '<?php echo addslashes(number_format($displayPrice ?? $price,2)); ?>', '<?php echo addslashes($resolved_img); ?>'); }"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                                    <?php if ($outOfStock): ?>
                                        <div style="position:absolute;top:10px;right:10px;background:#dc3545;color:#fff;padding:4px 8px;border-radius:5px;font-size:12px;z-index:5;">Out of stock</div>
                                    <?php endif; ?>
                                    <?php if (!$vendor_is_online): ?>
                                        <div style="position:absolute;top:10px;left:10px;background:#6c757d;color:#fff;padding:4px 8px;border-radius:5px;font-size:12px;z-index:5;">Offline</div>
                                    <?php endif; ?>
                                    <img src="<?php echo htmlspecialchars($resolved_img); ?>" alt="<?php echo htmlspecialchars($pname); ?>" class="product-image" onerror="this.src='images/default-product.png'">
                                    <div class="product-details">
                                        <p class="product-category"><?php echo htmlspecialchars($category); ?></p>
                                        <h3 class="product-name"><?php echo htmlspecialchars($pname); ?></h3>
                                        <?php if ($discountPct > 0): ?>
                                            <div class="discount-text"><?php echo htmlspecialchars($discountPct); ?>% off</div>
                                            <p class="product-price" style="text-decoration: line-through; color: #999;">Rs. <?php echo htmlspecialchars(number_format($price,2)); ?></p>
                                        <?php endif; ?>
                                    <p class="product-price">Rs. <?php echo htmlspecialchars(number_format($displayPrice ?? $price,2)); ?></p>
                                        <div class="product-footer">
                                            <a href="viewProduct.php?id=<?php echo urlencode($pid); ?>" class="btn-view">View</a>
                                            <?php if ($outOfStock || !$vendor_is_online): ?>
                                                <button class="btn-cart" style="background:#6c757d;cursor:not-allowed;" disabled><?php echo $outOfStock ? 'Out of stock' : 'Offline'; ?></button>
                                            <?php else: ?>
                                                <form method="post" action="add_to_cart.php" style="flex: 1;">
                                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($pid); ?>">
                                                    <input type="hidden" name="vendor_id" value="<?php echo htmlspecialchars($vendor_id); ?>">
                                                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($pname); ?>">
                                                    <input type="hidden" name="price" value="<?php echo htmlspecialchars(($discountPct>0) ? $displayPrice : $price); ?>">
                                                    <input type="hidden" name="image" value="<?php echo htmlspecialchars($resolved_img); ?>">
                                                    <input type="hidden" name="quantity" value="1">
                                                    <button type="submit" class="btn-cart">Add to Cart</button>
                                                </form>
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
