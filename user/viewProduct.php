<?php
include 'connection.php';
session_start();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { echo 'Invalid product id'; exit; }

// detect products table
$possible = ['tbl_product','tbl_products','product','products'];
$prodTable = null;
$tablesRes = mysqli_query($conn, "SHOW TABLES");
$existing = []; while($tr = mysqli_fetch_row($tablesRes)) $existing[] = $tr[0];
foreach($possible as $p) if(in_array($p, $existing)) { $prodTable = $p; break; }
if (!$prodTable) { echo 'Products table not found.'; exit; }

// Detect which column is the primary key
$colsRes = mysqli_query($conn, "SHOW COLUMNS FROM {$prodTable}");
$prodCols = [];
$idCol = null;
if ($colsRes) { 
    while($c = mysqli_fetch_assoc($colsRes)) { 
        $prodCols[] = $c['Field'];
        if (in_array($c['Field'], ['product_id', 'id', 'pid'])) {
            $idCol = $c['Field'];
        }
    }
}
if (!$idCol) { echo 'Product ID column not found.'; exit; }

$stmt = mysqli_prepare($conn, "SELECT * FROM {$prodTable} WHERE {$idCol} = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);
if (!$product) { echo 'Product not found.'; exit; }

$name = $product['product_name'] ?? $product['name'] ?? $product['title'] ?? 'Product';
$price = $product['product_price'] ?? $product['price'] ?? $product['sale_price'] ?? $product['mrp'] ?? '';
$discount_percent = $product['discount_percent'] ?? 0;
$vendor_discount = 0;

// Compute final price after discount (vendor_discount might be applied later)
$discounted_price = '';
$original_discount = max(0, floatval($discount_percent));
$applied_discount = $original_discount;

// If there is no discount, make sure discounted price is empty so view uses normal price (effect applied after vendor fetch)
if ($price !== '' && $applied_discount > 0) {
    $discounted_price = round(floatval($price) * (1 - $applied_discount / 100), 2);
}
if ($discounted_price !== '' && $discounted_price >= floatval($price)) {
    $discounted_price = '';
    $applied_discount = 0;
}
$image = $product['product_image'] ?? $product['image'] ?? $product['image_path'] ?? $product['img'] ?? 'images/default-product.png';
$vendor_id = $product['vendor_id'] ?? 0;
$vendor_is_online = 1; // default assume online
$description = $product['description'] ?? $product['product_description'] ?? '';

// Resolve product image path
if ($image && !preg_match('#^https?://#', $image)) {
    $imgCandidates = [
        $image,
        'images/' . $image,
        'uploads/' . $image, 
        'uploads/vendors/' . $image, 
        'uploads/products/' . $image,
        '../uploads/' . $image,
        '../uploads/vendors/' . $image,
        '../uploads/products/' . $image,
        '../admin/uploads/' . $image,
        '../admin/vendor/uploads/' . $image
    ];
    $resolvedImg = '';
    foreach ($imgCandidates as $cand) { 
        if (file_exists(__DIR__ . '/' . $cand)) { 
            $resolvedImg = $cand; 
            break; 
        } 
    }
    $image = $resolvedImg ?: 'images/default-product.png';
}

// If the product has no resolvable image, treat it as unavailable (removed)
if ($image === 'images/default-product.png') {
    echo 'Product not available.';
    exit;
}

// Fetch vendor details
$vendor_name = '';
if ($vendor_id > 0) {
    // detect discount column
    $hasDisc = false;
    $cd = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'vendor_discount_percent'");
    if ($cd && mysqli_num_rows($cd) > 0) { $hasDisc = true; }
    $selectCols = 'shop_name, vendor_name, is_online';
    if ($hasDisc) { $selectCols .= ', vendor_discount_percent'; }
    $vstmt = mysqli_prepare($conn, "SELECT {$selectCols} FROM tbl_vendors WHERE vendor_id = ? LIMIT 1");
    mysqli_stmt_bind_param($vstmt, 'i', $vendor_id);
    mysqli_stmt_execute($vstmt);
    $vres = mysqli_stmt_get_result($vstmt);
    if ($vendor = mysqli_fetch_assoc($vres)) {
        $vendor_name = $vendor['shop_name'] ?: $vendor['vendor_name'];
        $vendor_discount = floatval($vendor['vendor_discount_percent'] ?? 0);
        $vendor_is_online = intval($vendor['is_online'] ?? 0);

        // Recalculate discount after vendor info loaded
        $applied_discount = max($original_discount, $vendor_discount);
        if ($price !== '' && $applied_discount > 0) {
            $discounted_price = round(floatval($price) * (1 - $applied_discount / 100), 2);
            if ($discounted_price >= floatval($price)) {
                $discounted_price = '';
                $applied_discount = 0;
            }
        }
    }
    mysqli_stmt_close($vstmt);
}

// Determine stock availability for this product
$stock = null;
if (isset($product['stock'])) {
    $stock = intval($product['stock']);
} elseif (isset($product['product_stock'])) {
    $stock = intval($product['product_stock']);
} else {
    // if the stock column isn’t in the fetched row, attempt to detect it
    $colRes = mysqli_query($conn, "SHOW COLUMNS FROM {$prodTable} LIKE 'stock'");
    if ($colRes && mysqli_num_rows($colRes) > 0) {
        $stock = intval($product['stock'] ?? 0);
    } else {
        $colRes = mysqli_query($conn, "SHOW COLUMNS FROM {$prodTable} LIKE 'product_stock'");
        if ($colRes && mysqli_num_rows($colRes) > 0) {
            $stock = intval($product['product_stock'] ?? 0);
        }
    }
}

$in_stock = ($stock === null) ? true : ($stock > 0);

?><!doctype html>
<html><head>
  <meta charset="utf-8"><title><?php echo htmlspecialchars($name); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head><body>
<?php include 'header.php'; ?>
<main class="container py-5">
  <?php if (!empty($_SESSION['cart_error'])): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['cart_error']); ?></div>
    <?php unset($_SESSION['cart_error']); ?>
  <?php endif; ?>
  <div class="row">
    <!-- Product Image - Left Side -->
    <div class="col-md-6 mb-4">
      <div style="background:#f9f9f9;padding:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;min-height:400px;">
        <img src="<?php echo htmlspecialchars($image); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($name); ?>" style="max-width:100%;max-height:400px;object-fit:contain;">
      </div>
    </div>
    
    <!-- Product Details - Right Side -->
    <div class="col-md-6">
      <?php if ($vendor_name): ?>
      <p style="color:#999;font-size:14px;margin-bottom:8px;"><?php echo htmlspecialchars($vendor_name); ?><?php if (!$vendor_is_online): ?> <span class="badge" style="background:#f8f9fa;color:#000;border:1px solid #ced4da;">(Offline)</span><?php endif; ?></p>
      <?php endif; ?>
      
      <div class="d-flex justify-content-between align-items-start gap-3" style="margin-bottom:16px;">
        <h1 style="font-size:32px;font-weight:700;margin:0;flex:1;"><?php echo htmlspecialchars($name); ?></h1>
        <a href="#" class="btn-wishlist" style="font-size:28px;" data-product-id="<?php echo htmlspecialchars($product[$idCol] ?? ''); ?>" onclick="event.preventDefault(); event.stopPropagation(); if(WishlistManager.isInWishlist(<?php echo intval($product[$idCol] ?? 0); ?>)) { WishlistManager.removeFromWishlist(<?php echo intval($product[$idCol] ?? 0); ?>); } else { WishlistManager.addToWishlist(<?php echo intval($product[$idCol] ?? 0); ?>, '<?php echo addslashes($name); ?>', '<?php echo addslashes(($discounted_price !== '' && $discounted_price < $price) ? $discounted_price : $price); ?>', '<?php echo addslashes($image); ?>'); }"><svg width="32" height="32"><use xlink:href="#heart"></use></svg></a>
      </div>
      
      <!-- Rating -->
      <div style="margin-bottom:16px;">
        <span style="color:#ffc107;font-size:16px;">★ 4.6</span>
        <span style="color:#999;font-size:14px;margin-left:8px;">(235 Reviews)</span>
      </div>
      
      <!-- Price -->
      <?php if ($price !== ''): ?>
        <?php if ($discounted_price !== '' && $discounted_price < $price): ?>
          <p style="font-size:32px;font-weight:700;color:#e74c3c;margin-bottom:6px;">₹<?php echo htmlspecialchars($discounted_price); ?></p>
          <p style="font-size:16px;color:#999;text-decoration:line-through;margin-bottom:16px;">Original: ₹<?php echo htmlspecialchars($price); ?></p>
          <p style="font-size:14px;color:#27ae60;margin-bottom:24px;">You save <?php echo htmlspecialchars(number_format($price - $discounted_price, 2)); ?> (<?php echo htmlspecialchars($applied_discount); ?>% off)</p>
        <?php else: ?>
          <p style="font-size:32px;font-weight:700;color:#e74c3c;margin-bottom:24px;">₹<?php echo htmlspecialchars($price); ?></p>
        <?php endif; ?>
      <?php endif; ?>
      
      <!-- Add to Cart Form -->
      <?php if (!$vendor_is_online): ?>
          <div class="alert alert-warning">This shop is currently offline; you cannot place an order from it.</div>
      <?php endif; ?>
      <form method="post" action="add_to_cart.php" <?php if (!$vendor_is_online) echo 'onsubmit="return false;"'; ?>>
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product[$idCol] ?? ''); ?>">
        <input type="hidden" name="vendor_id" value="<?php echo htmlspecialchars($vendor_id); ?>">
        <input type="hidden" name="name" value="<?php echo htmlspecialchars($name); ?>">
        <input type="hidden" name="price" value="<?php echo htmlspecialchars(($discounted_price !== '' && $discounted_price < $price) ? $discounted_price : $price); ?>">
        <input type="hidden" name="image" value="<?php echo htmlspecialchars($image); ?>">
        
        <div class="d-flex align-items-center gap-3">
          <div style="display:flex;border:1px solid #ddd;border-radius:4px;">
            <button type="button" class="btn btn-light" style="width:44px;height:44px;border:none;" onclick="document.getElementById('qty').value = Math.max(1, parseInt(document.getElementById('qty').value) - 1);">−</button>
            <input type="number" id="qty" name="quantity" value="1" min="1" style="width:60px;border:none;text-align:center;font-size:16px;" />
            <button type="button" class="btn btn-light" style="width:44px;height:44px;border:none;" onclick="document.getElementById('qty').value = parseInt(document.getElementById('qty').value) + 1;">+</button>
          </div>
          <button type="submit" class="btn btn-primary" style="padding:12px 40px;font-size:16px;font-weight:600;">Add to Cart</button>
        </div>
      </form>
    </div>
  </div>
</main>
<?php include 'footer.php'; ?>
</body></html>
