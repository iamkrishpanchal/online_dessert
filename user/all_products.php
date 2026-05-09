<?php
// Display all products with pagination
include 'connection.php';
session_start();

// helper used by other pages (copied from viewCategory)
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

$perPage = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// detect products table, similar to other pages
$possible = ['tbl_product','tbl_products','product','products'];
$prodTable = null;
$tablesRes = mysqli_query($conn, "SHOW TABLES");
$existing = [];
while ($tr = mysqli_fetch_row($tablesRes)) {
    $existing[] = $tr[0];
}
foreach ($possible as $p) {
    if (in_array($p, $existing)) { $prodTable = $p; break; }
}

if (!$prodTable) {
    echo 'Product table not found.';
    exit;
}

// count total products
$countRes = mysqli_query($conn, "SELECT COUNT(*) FROM {$prodTable}");
$total = 0;
if ($countRes && ($r = mysqli_fetch_row($countRes))) {
    $total = intval($r[0]);
}
$pages = $total ? ceil($total / $perPage) : 1;

// fetch the page of products
$sql = "SELECT p.*, v.is_online, v.shop_name AS vendor_shop_name, v.vendor_discount_percent
        FROM {$prodTable} p
        LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id
        ORDER BY p.product_name
        LIMIT {$offset},{$perPage}";
$res = mysqli_query($conn, $sql);
$products = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];

?><!DOCTYPE html>
<html lang="en">
<head>
    <title>All Products - Dessert Magic</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        /* reuse grid card layout from other listing pages */
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
            /* remove shadow per request */
            /* box-shadow: 0 2px 8px rgba(0,0,0,0.1); */
            transition: transform 0.3s ease;
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
        .pagination { justify-content: center; }
    </style>
</head>
<body>
    <header><?php include 'header.php'; ?></header>
    <main>
        <div class="container">
            <div class="py-5">
                <h1 class="mb-4" style="font-weight:700;">All Products</h1>
                <?php if (empty($products)): ?>
                    <div class="no-products">No products available.</div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $p):
                            $pid = $p['product_id'] ?? $p['id'] ?? '';
                            $pname = $p['product_name'] ?? $p['name'] ?? 'Untitled';
                            $price = floatval($p['product_price'] ?? 0);
                            $pimg = $p['product_image'] ?? '';
                            $vendor = $p['vendor_shop_name'] ?? '';
                            $vendor_is_online = intval($p['is_online'] ?? 1);
                            $discountPct = 0;
                            if (isset($p['discount_percent']) && floatval($p['discount_percent']) > 0) {
                                $discountPct = floatval($p['discount_percent']);
                            }
                            if (isset($p['vendor_discount_percent']) && floatval($p['vendor_discount_percent']) > $discountPct) {
                                $discountPct = floatval($p['vendor_discount_percent']);
                            }
                            $displayPrice = $price;
                            if ($discountPct > 0) {
                                $displayPrice = round($price * (1 - $discountPct/100), 2);
                            }
                            $resolved_img = findImagePath($pimg);
                        ?>
                        <div class="product-item position-relative<?php echo $vendor_is_online ? '' : ' offline'; ?>" data-vendor-online="<?php echo $vendor_is_online; ?>">
                            <a href="#" class="btn-wishlist position-absolute" style="right:8px;top:8px;" data-product-id="<?php echo htmlspecialchars($pid); ?>" onclick="event.preventDefault(); event.stopPropagation(); if(WishlistManager.isInWishlist(<?php echo intval($pid); ?>)) { WishlistManager.removeFromWishlist(<?php echo intval($pid); ?>); } else { WishlistManager.addToWishlist(<?php echo intval($pid); ?>, '<?php echo addslashes($pname); ?>', '<?php echo addslashes(number_format($displayPrice,2)); ?>', '<?php echo addslashes($resolved_img); ?>'); }"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                            <img src="<?php echo htmlspecialchars($resolved_img); ?>" alt="<?php echo htmlspecialchars($pname); ?>" class="product-image" onerror="this.src='images/default-product.png'">
                            <div class="product-details">
                                <?php if ($vendor): ?>
                                    <p class="product-shop">by <?php echo htmlspecialchars($vendor); ?><?php if (!$vendor_is_online): ?> <span class="badge" style="background:#f8f9fa;color:#000;border:1px solid #ced4da;">Offline</span><?php endif; ?></p>
                                <?php endif; ?>
                                <h3 class="product-name"><?php echo htmlspecialchars($pname); ?></h3>
                                <?php if ($discountPct > 0): ?>
                                    <div class="discount-text"><?php echo htmlspecialchars($discountPct); ?>% off</div>
                                    <p class="product-price" style="text-decoration: line-through; color: #999;">Rs. <?php echo htmlspecialchars(number_format($price,2)); ?></p>
                                <?php endif; ?>
                                <p class="product-price">Rs. <?php echo htmlspecialchars(number_format($displayPrice,2)); ?></p>
                                <div class="product-footer">
                                    <?php if (!$vendor_is_online): ?>
                                        <!-- offline user badge shown above, no extra overlay here -->
                                    <?php endif; ?>
                                    <a href="viewProduct.php?id=<?php echo urlencode($pid); ?>" class="btn-view">View</a>
                                    <?php if ($vendor_is_online): ?>
                                        <form method="post" action="add_to_cart.php" style="flex: 1;">
                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($pid); ?>">
                                            <input type="hidden" name="vendor_id" value="<?php echo htmlspecialchars($p['vendor_id'] ?? 0); ?>">
                                            <input type="hidden" name="name" value="<?php echo htmlspecialchars($pname); ?>">
                                            <input type="hidden" name="price" value="<?php echo htmlspecialchars($displayPrice); ?>">
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
                        <?php endforeach; ?>
                    </div>

                    <!-- pagination -->
                    <nav aria-label="Products pagination">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item"><a class="page-link" href="all_products.php?page=<?php echo $page-1; ?>">&laquo; Prev</a></li>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $pages; $i++): ?>
                                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="all_products.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($page < $pages): ?>
                                <li class="page-item"><a class="page-link" href="all_products.php?page=<?php echo $page+1; ?>">Next &raquo;</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                <?php endif; ?>
            </div>
        </div>
    </main>
    <footer><?php include 'footer.php'; ?></footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
