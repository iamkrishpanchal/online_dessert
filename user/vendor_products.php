<?php
// Vendor products page showing all products from a vendor, optionally filtered by category
include 'connection.php';

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// prefer the richer all_vendor_products page; redirect if we're being called directly
if ($vendor_id > 0) {
    $url = 'all_vendor_products.php?vendor_id=' . $vendor_id;
    if ($category_id > 0) {
        $url .= '&category_id=' . $category_id;
    }
    header('Location: ' . $url);
    exit;
}

if ($vendor_id <= 0) {
    echo 'Invalid vendor.'; exit;
}

// fetch vendor basic info including logo
// check for discount column
$hasDisc = false;
$cd = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'vendor_discount_percent'");
if ($cd && mysqli_num_rows($cd) > 0) { $hasDisc = true; }
$selectCols = 'vendor_id, shop_name, vendor_name, image_path, logo_path';
if ($hasDisc) { $selectCols .= ', vendor_discount_percent'; }
$vstmt = mysqli_prepare($conn, "SELECT {$selectCols} FROM tbl_vendors WHERE vendor_id = ? LIMIT 1");
mysqli_stmt_bind_param($vstmt, 'i', $vendor_id);
mysqli_stmt_execute($vstmt);
$vres = mysqli_stmt_get_result($vstmt);
$vendor = mysqli_fetch_assoc($vres);
mysqli_stmt_close($vstmt);
// DEBUG: Show what the database returned
if (!$vendor) { 
    echo "<!-- DEBUG: Vendor not found for vendor_id={$vendor_id} -->"; 
} else {
    echo "<!-- DEBUG: Query returned vendor_id={$vendor['vendor_id']}, shop_name={$vendor['shop_name']}, vendor_name={$vendor['vendor_name']} -->";
}
if (!$vendor) { echo 'Vendor not found.'; exit; }

// If category_id provided, fetch category info for display
$category = null;
$categoryImage = null;
if ($category_id > 0) {
    $cstmt = mysqli_prepare($conn, "SELECT categories_id, categories_name, categories_image FROM tbl_categories WHERE categories_id = ? AND vendor_id = ? LIMIT 1");
    mysqli_stmt_bind_param($cstmt, 'ii', $category_id, $vendor_id);
    mysqli_stmt_execute($cstmt);
    $cres = mysqli_stmt_get_result($cstmt);
    $category = mysqli_fetch_assoc($cres);
    mysqli_stmt_close($cstmt);
    
    if ($category && !empty($category['categories_image'])) {
        $raw = $category['categories_image'];
        $candidates = [$raw, 'uploads/' . $raw, 'uploads/vendors/' . $raw];
        foreach ($candidates as $cand) { 
            if (file_exists(__DIR__ . '/' . $cand)) { 
                $categoryImage = $cand; 
                break; 
            } 
        }
    }
}

// determine products table like other pages do
$possible = ['tbl_product','tbl_products','product','products'];
$prodTable = null;
$tablesRes = mysqli_query($conn, "SHOW TABLES");
$existing = [];
while($tr = mysqli_fetch_row($tablesRes)) { $existing[] = $tr[0]; }
foreach($possible as $p) { if(in_array($p, $existing)) { $prodTable = $p; break; } }

?>
<!DOCTYPE html>
<html><head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($vendor['shop_name'] ?: $vendor['vendor_name']); ?> - Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head><body>
<?php include 'header.php'; ?>
<main class="container py-4">
    <!-- DEBUG: vendor_id=<?php echo $vendor_id; ?>, shop_name=<?php echo htmlspecialchars($vendor['shop_name']); ?> -->
    <div class="row mb-4">
        <div class="col-12">
            <div style="background:linear-gradient(135deg, #FFE6D9 0%, #FFD9E6 100%);padding:30px;border-radius:12px;color:#333;">
                <h1 class="mb-0" style="font-weight:700;color:#333;"><?php echo htmlspecialchars($vendor['shop_name'] ?: $vendor['vendor_name']); ?></h1>
                <!-- HEADING DEBUG: vendor_array=<?php echo json_encode($vendor); ?> -->
            </div>
        </div>
    </div>
    
    <?php if (!$prodTable): ?>
        <div class="alert alert-danger">Product table not found.</div>
    <?php else:
        $products = [];
        
        // Inspect product table columns to find category column
        $colsRes = mysqli_query($conn, "SHOW COLUMNS FROM {$prodTable}");
        $prodCols = [];
        if ($colsRes) { while($c = mysqli_fetch_assoc($colsRes)) $prodCols[] = $c['Field']; }

        $categoryCol = null;
        foreach(['category_id','categories_id','cat_id','category','categories_name'] as $cc) {
            if (in_array($cc, $prodCols)) { $categoryCol = $cc; break; }
        }
        
        // Build query: filter by vendor_id and optionally by category
        if ($category_id > 0 && $categoryCol) {
            if ($categoryCol === 'categories_name' && $category) {
                // Query by category name
                $pstmt = mysqli_prepare($conn, "SELECT * FROM {$prodTable} WHERE vendor_id = ? AND categories_name = ? LIMIT 200");
                if ($pstmt) {
                    $cname = $category['categories_name'];
                    mysqli_stmt_bind_param($pstmt, 'is', $vendor_id, $cname);
                    mysqli_stmt_execute($pstmt);
                    $pres = mysqli_stmt_get_result($pstmt);
                    $products = $pres ? mysqli_fetch_all($pres, MYSQLI_ASSOC) : [];
                    mysqli_stmt_close($pstmt);
                }
            } else {
                // Query by category id
                $pstmt = mysqli_prepare($conn, "SELECT * FROM {$prodTable} WHERE vendor_id = ? AND {$categoryCol} = ? LIMIT 200");
                if ($pstmt) {
                    mysqli_stmt_bind_param($pstmt, 'ii', $vendor_id, $category_id);
                    mysqli_stmt_execute($pstmt);
                    $pres = mysqli_stmt_get_result($pstmt);
                    $products = $pres ? mysqli_fetch_all($pres, MYSQLI_ASSOC) : [];
                    mysqli_stmt_close($pstmt);
                }
            }
        } else {
            // Just show all products from this vendor
            $pstmt = mysqli_prepare($conn, "SELECT * FROM {$prodTable} WHERE vendor_id = ? LIMIT 200");
            if ($pstmt) {
                mysqli_stmt_bind_param($pstmt, 'i', $vendor_id);
                mysqli_stmt_execute($pstmt);
                $pres = mysqli_stmt_get_result($pstmt);
                $products = $pres ? mysqli_fetch_all($pres, MYSQLI_ASSOC) : [];
                mysqli_stmt_close($pstmt);
            }
        }
        
        if (empty($products)): ?>
            <div class="alert alert-info">No products found.</div>
        <?php else: ?>
            <div class="row">
            <?php foreach($products as $p):
                // detect name, price, image fields
                $pid = $p['product_id'] ?? $p['id'] ?? '';
                $name = $p['product_name'] ?? $p['name'] ?? $p['title'] ?? ($p['pname'] ?? 'Untitled');
                $price = $p['price'] ?? $p['sale_price'] ?? $p['mrp'] ?? null;
                $img = $p['product_image'] ?? $p['image'] ?? $p['image_path'] ?? $p['img'] ?? ($p['photo'] ?? 'images/default-product.png');
                // apply vendor discount if present
                $vendorDisc = floatval($vendor['vendor_discount_percent'] ?? 0);
                $discountPct = 0;
                if (isset($p['discount_percent']) && floatval($p['discount_percent']) > 0) {
                    $discountPct = floatval($p['discount_percent']);
                }
                if ($vendorDisc > $discountPct) {
                    $discountPct = $vendorDisc;
                }
                if ($price !== null && $discountPct > 0) {
                    $displayPrice = round($price * (1 - $discountPct/100),2);
                } else {
                    $displayPrice = $price;
                }
                if ($img && !preg_match('#^https?://#', $img)) {
                    $imgCandidates = [$img, 'uploads/' . $img, 'uploads/vendors/' . $img, 'uploads/products/' . $img, '../uploads/' . $img, '../uploads/vendors/' . $img, '../uploads/products/' . $img, '../admin/uploads/' . $img, '../admin/vendor/uploads/' . $img, 'uploads/' . basename($img), 'uploads/vendors/' . basename($img), 'uploads/products/' . basename($img), '../admin/vendor/uploads/' . basename($img)];
                    $resolvedImg = '';
                    foreach ($imgCandidates as $cand) { if (file_exists(__DIR__ . '/' . $cand)) { $resolvedImg = $cand; break; } }
                    // If not found and no extension, try adding .jpg in vendor uploads
                    if (!$resolvedImg && !preg_match('/\.(jpg|jpeg|png|gif)$/i', $img)) {
                        $jpg_path = '../admin/vendor/uploads/' . $img . '.jpg';
                        if (file_exists(__DIR__ . '/' . $jpg_path)) {
                            $resolvedImg = $jpg_path;
                        }
                    }
                    $img = $resolvedImg ?: $img; // Keep original path if not found
                } else if (!$img) {
                    // Only use a generic default if no image path is provided
                    $img = 'images/chocolat/b3ac7733c4c0f81d13b5024bebae3408.jpg';
                }
                ?>
                <div class="col-md-3 mb-4">
                    <div class="product-item position-relative p-3 h-100">
                        <a href="#" class="btn-wishlist position-absolute" style="right:8px;top:8px;"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                        <figure class="text-center">
                            <a href="viewProduct.php?id=<?php echo urlencode($pid); ?>" title="<?php echo htmlspecialchars($name); ?>">
                                <img src="<?php echo htmlspecialchars($img); ?>" class="tab-image img-fluid" style="max-height:160px; object-fit:contain;">
                            </a>
                        </figure>
                        <h3 class="h6 mt-2"><?php echo htmlspecialchars($name); ?></h3>
                        <?php if ($discountPct>0): ?>
                            <div class="discount-text"><?php echo htmlspecialchars($discountPct); ?>% off</div>
                        <?php endif; ?>
                        <span class="price d-block fw-bold mb-2">₹<?php echo ($discountPct>0) ? htmlspecialchars($displayPrice) : htmlspecialchars($price); ?></span>
                        <form method="post" action="add_to_cart.php" class="d-flex align-items-center justify-content-between mt-auto m-0">
                            <div class="input-group product-qty" style="width:120px;">
                                <button type="button" class="quantity-left-minus btn btn-outline-secondary btn-number" data-type="minus">-</button>
                                <input type="text" id="quantity" name="quantity" value="1" class="form-control input-number text-center cart-qty-input" style="max-width:48px;" />
                                <button type="button" class="quantity-right-plus btn btn-outline-secondary btn-number" data-type="plus">+</button>
                            </div>
                            <div>
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($pid); ?>">
                                <input type="hidden" name="vendor_id" value="<?php echo htmlspecialchars($vendor_id); ?>">
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($name); ?>">
                                <input type="hidden" name="price" value="<?php echo htmlspecialchars(($discountPct>0) ? $displayPrice : $price); ?>">
                                <input type="hidden" name="image" value="<?php echo htmlspecialchars($img); ?>">
                                <button class="nav-link btn btn-sm btn-primary" style="padding:6px 10px;">Add to Cart</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif;
    endif; ?>
</main>
<?php include 'footer.php'; ?>
</body></html>
