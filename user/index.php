<?php
session_start();
include 'connection.php';
// Temporary debug helpers: show runtime errors in the browser
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check voucher status for the user
$voucher_status = 'not_logged_in'; // not_logged_in, not_claimed, already_claimed
$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id > 0) {
    // Create voucher claims table if not exists
    $create_table_sql = "CREATE TABLE IF NOT EXISTS tbl_voucher_claims (
        claim_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        voucher_code VARCHAR(100) NOT NULL DEFAULT '25PERCENT',
        claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        used_in_order_id INT DEFAULT NULL,
        status ENUM('active', 'used') DEFAULT 'active',
        UNIQUE KEY unique_user_voucher (user_id, voucher_code),
        FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE CASCADE
    )";
    @mysqli_query($conn, $create_table_sql);

    // If the user has already placed an order, they should not be shown the voucher option
    $orderCountStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM tbl_orders WHERE user_id = ?");
    if ($orderCountStmt) {
        mysqli_stmt_bind_param($orderCountStmt, 'i', $user_id);
        mysqli_stmt_execute($orderCountStmt);
        mysqli_stmt_bind_result($orderCountStmt, $orderCount);
        mysqli_stmt_fetch($orderCountStmt);
        mysqli_stmt_close($orderCountStmt);
        if ($orderCount > 0) {
            $voucher_status = 'already_claimed';
        }
    }

    // Check if user has claimed the voucher
    if ($voucher_status !== 'already_claimed') {
        $check_sql = "SELECT claim_id FROM tbl_voucher_claims WHERE user_id = ? AND voucher_code = '25PERCENT'";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 'i', $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        $voucher_status = (mysqli_num_rows($check_result) > 0) ? 'already_claimed' : 'not_claimed';
    }
}

// Fetch all categories from database
$categories = [];
if ($conn) {
    $result = mysqli_query($conn, "SELECT categories_id, categories_name, categories_image FROM tbl_categories WHERE categories_status = 1 ORDER BY categories_name ASC");
    if ($result) {
        $categories = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

// helper: take a filename or path stored in DB and convert it into a usable URL
function findImagePath($filename, $default = 'images/default-product.png') {
    $filename = trim((string)$filename);
    if ($filename === '') return $default;
    // try a list of plausible relative filesystem paths (starting from current script dir)
    $cands = [
        $filename,
        '../' . $filename,
        'uploads/' . $filename,
        '../uploads/' . $filename,
        'uploads/vendors/' . $filename,
        '../uploads/vendors/' . $filename,
        '../admin/vendor/uploads/' . $filename,
        'admin/vendor/uploads/' . $filename,
        'user/uploads/products/' . $filename,
        '../user/uploads/products/' . $filename,
    ];
    foreach ($cands as $cand) {
        if (file_exists(__DIR__ . '/' . $cand)) {
            return $cand;
        }
    }
    // if it already looks like a URL use it
    if (preg_match('#^https?://#i', $filename)) {
        return $filename;
    }
    return $default;
}

// helper: return a list of image paths from a local directory so that the
// homepage carousel / promo banner can be managed simply by dropping files
// into a folder.  Caller should pass a path relative to the current script.
// If the directory does not exist or is empty a minimal fallback image will
// be returned so the carousel still renders.
function getCarouselImages($dir = 'images/chocolat') {
    $result = [];
    $absolute = __DIR__ . '/' . $dir;
    if (is_dir($absolute)) {
        // collect common image extensions
        $patterns = [
            $absolute . '/*.jpg',
            $absolute . '/*.jpeg',
            $absolute . '/*.png',
            $absolute . '/*.gif',
        ];
        foreach ($patterns as $pattern) {
            foreach (glob($pattern) as $path) {
                // convert absolute path back to relative URL
                $result[] = str_replace('\\', '/', substr($path, strlen(__DIR__) + 1));
            }
        }
    }
    if (empty($result)) {
        // fallback single placeholder image
        $result[] = 'images/default-promo.jpg';
    }
    return $result;
}

// fetch products (optionally limited to vendor or if user asked to view all)
$products = [];
$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$show_all = isset($_GET['show_all']) && intval($_GET['show_all']) === 1;
$total_products = 0; // only used for home page
$best_products = []; // will hold products meeting best-selling criteria
if ($conn) {
    // build field list and optionally include rating if the column exists
    $prod_fields = "p.product_id, p.product_name, p.product_price, p.product_image, p.category_id, p.vendor_id, COALESCE(c.categories_name,'') as categories_name";
    $colRes = @mysqli_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_products' AND COLUMN_NAME = 'rating'");
    if ($colRes && mysqli_num_rows($colRes) > 0) {
        $prod_fields .= ", p.rating";
    }
    // also include whatever discount columns we have
    $discountCols = [];
    $colRes2 = @mysqli_query($conn,
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_products' AND COLUMN_NAME IN ('discount_percent','discount_price','discount')");
    if ($colRes2) {
        while ($crow = mysqli_fetch_assoc($colRes2)) {
            $discountCols[] = $crow['COLUMN_NAME'];
        }
    }
    foreach ($discountCols as $col) {
        $prod_fields .= ", p." . $col;
    }
    // make sure the discount columns exist so queries later won't break
    $needed = [
        'discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0',
        'discount_price DECIMAL(10,2) NOT NULL DEFAULT 0',
        'discount DECIMAL(10,2) NOT NULL DEFAULT 0'
    ];
    foreach ($needed as $def) {
        list($cname) = explode(' ', $def, 2);
        $cname = trim($cname);
        $resCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_products LIKE '$cname'");
        if ($resCol && mysqli_num_rows($resCol) === 0) {
            mysqli_query($conn, "ALTER TABLE tbl_products ADD COLUMN $def");
            // also add to local array so prod_fields and other logic see it immediately
            $prod_fields .= ", p." . $cname;
            $discountCols[] = $cname;
        }
    }
    $prod_sql = "SELECT $prod_fields, v.is_online FROM tbl_products p
                                           LEFT JOIN tbl_categories c ON p.category_id = c.categories_id
                                           LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id";

    if ($vendor_id > 0) {
        // vendor-specific listing
        $prod_sql .= " WHERE p.vendor_id = " . $vendor_id;
    }

    // home page without show_all should only fetch a small sample
    if (!$show_all && $vendor_id == 0) {
        // count total products for button logic
        $count_res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM tbl_products");
        if ($count_res) {
            $total_products = intval(mysqli_fetch_assoc($count_res)['cnt']);
        }
        $prod_sql .= " ORDER BY p.product_name ASC LIMIT 13";
    } else {
        $prod_sql .= " ORDER BY p.product_name ASC";
    }

    $res = mysqli_query($conn, $prod_sql);
    if ($res) {
        $products = mysqli_fetch_all($res, MYSQLI_ASSOC);
    }

    // prepare best selling products if showing home page only
    if (!$show_all && $vendor_id == 0) {
        // ensure order items table exists before querying
        $tbl_check = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_order_items'");
        if ($tbl_check && mysqli_num_rows($tbl_check) > 0) {
            $best_sql = "SELECT p.product_id, p.product_name, p.product_price, p.product_image,
                           p.discount_percent, p.discount_price, p.discount,
                           v.vendor_discount_percent,
                           COUNT(DISTINCT oi.order_id) AS buyer_count
                         FROM tbl_order_items oi
                         JOIN tbl_products p ON p.product_id = oi.product_id
                         LEFT JOIN tbl_vendors v ON v.vendor_id = p.vendor_id
                         GROUP BY oi.product_id
                         HAVING buyer_count >= 5
                         ORDER BY buyer_count DESC
                         LIMIT 10";
            $best_res = mysqli_query($conn, $best_sql);
            if ($best_res) {
                $best_products = mysqli_fetch_all($best_res, MYSQLI_ASSOC);
            }
        }
    }
}

// load top shops (vendors) ranked by total products sold
$top_vendors = [];
if ($conn) {
    // Calculate total products sold per vendor from order data
    // Join vendors -> products -> order_items -> orders to get sales count
    $sql = "SELECT 
                v.vendor_id, 
                v.shop_name, 
                v.logo_path,
                COALESCE(SUM(oi.quantity), 0) as total_products_sold
            FROM tbl_vendors v
            LEFT JOIN tbl_products p ON v.vendor_id = p.vendor_id
            LEFT JOIN tbl_order_items oi ON p.product_id = oi.product_id
            LEFT JOIN tbl_orders o ON oi.order_id = o.order_id
            GROUP BY v.vendor_id, v.shop_name, v.logo_path
            ORDER BY total_products_sold DESC, v.shop_name ASC
            LIMIT 3";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        $top_vendors = mysqli_fetch_all($res, MYSQLI_ASSOC);
    }
}
?>
<!-- Temporary: hide preloader while debugging JS load issues -->
<style>
  .preloader-wrapper, .preloader { display: none !important; }
  .btn-wishlist.active svg { fill: #ff6b6b; color: #ff6b6b; }
  .btn-wishlist { transition: all 0.2s ease; cursor: pointer; }
  .btn-wishlist:hover { transform: scale(1.15); }

  .product-item form {
      gap: 0.75rem;
      align-items: center;
  }
  .product-item .product-qty {
      width: 110px;
  }
  .product-item .product-qty .btn {
      min-width: 32px;
      padding: 0.35rem 0.5rem;
      font-size: 0.85rem;
  }
  .product-item .cart-qty-input {
      min-width: 42px;
      font-size: 0.88rem;
      padding: 0.35rem 0.45rem;
  }
  .product-item form .btn-primary {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 0.65rem;
      font-size: 0.82rem;
      padding: 0.4rem 0.85rem;
      min-width: 88px;
      letter-spacing: 0.02em;
      text-transform: capitalize;
      transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
  }
  .product-item form .btn-primary:hover,
  .product-item form .btn-primary:focus {
      transform: translateY(-1px);
      box-shadow: 0 6px 14px rgba(13,110,253,0.16);
  }
</style>
<style>
  /* logo used in top shops carousel */
  .top-shop-logo {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #fff;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
  }
  .top-shop-logo:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 16px rgba(0,0,0,0.15);
  }
  /* layout for top shops section */
  .shops-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 30px;
  }
  .vendor-item-wrapper {
    transition: all 0.3s ease;
    display: inline-block !important;
  }
  .vendor-item {
    display: inline-block;
    position: relative;
  }
  .section-title {
      font-size: 2.5rem;
      font-weight: 700;
      background: linear-gradient(135deg, #374e80 0%, #1d3a4b 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      letter-spacing: 0.5px;
      margin-bottom: 1.5rem;
      position: relative;
      display: inline-block;
  }
  .section-title::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 0;
      width: 60px;
      height: 4px;
      background: linear-gradient(135deg, #374e80 0%, #1d3a4b 100%);
      border-radius: 2px;
      transition: width 0.3s ease;
  }
  .section-header:hover .section-title::after {
      width: 120px;
  }
  .section-subtitle {
      max-width: 800px;
      margin: 0 auto 30px;
      color: #666;
      text-align: center;
      font-size: 1rem;
  }
  /* Promo carousel styles */
  #promoCarousel {
      height: 650px;
      display: flex;
      flex-direction: column;
      border-radius: 25px;
      overflow: hidden;
      box-shadow: 0 8px 32px rgba(0,0,0,0.15);
  }
  #promoCarousel .carousel-inner {
      height: 100%;
      flex: 1;
  }
  #promoCarousel .carousel-item {
      height: 100%;
  }
  #promoCarousel .carousel-item img {
      height: 100%;
      width: 100%;
      object-fit: contain;
  }
  #promoCarousel .carousel-control-prev,
  #promoCarousel .carousel-control-next {
      width: 60px;
      height: 60px;
      background-color: rgba(255,255,255,0.8);
      border-radius: 50%;
      top: 50%;
      transform: translateY(-50%);
  }
</style>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- <title>FoodMart - Free eCommerce Grocery Store HTML Website Template</title> -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <meta name="description" content="">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
    <link rel="stylesheet" type="text/css" href="style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

  </head>
  <body>

    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
      <defs>
        <symbol xmlns="http://www.w3.org/2000/svg" id="link" viewBox="0 0 24 24">
          <path fill="currentColor" d="M12 19a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm5 0a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm0-4a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm-5 0a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm7-12h-1V2a1 1 0 0 0-2 0v1H8V2a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3Zm1 17a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-9h16Zm0-11H4V6a1 1 0 0 1 1-1h1v1a1 1 0 0 0 2 0V5h8v1a1 1 0 0 0 2 0V5h1a1 1 0 0 1 1 1ZM7 15a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm0 4a1 1 0 1 0-1-1a1 1 0 0 0 1 1Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="arrow-right" viewBox="0 0 24 24">
          <path fill="currentColor" d="M17.92 11.62a1 1 0 0 0-.21-.33l-5-5a1 1 0 0 0-1.42 1.42l3.3 3.29H7a1 1 0 0 0 0 2h7.59l-3.3 3.29a1 1 0 0 0 0 1.42a1 1 0 0 0 1.42 0l5-5a1 1 0 0 0 .21-.33a1 1 0 0 0 0-.76Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="categories" viewBox="0 0 24 24">
          <path fill="currentColor" d="M19 5.5h-6.28l-.32-1a3 3 0 0 0-2.84-2H5a3 3 0 0 0-3 3v13a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-10a3 3 0 0 0-3-3Zm1 13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-13a1 1 0 0 1 1-1h4.56a1 1 0 0 1 .95.68l.54 1.64a1 1 0 0 0 .95.68h7a1 1 0 0 1 1 1Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="calendar" viewBox="0 0 24 24">
          <path fill="currentColor" d="M19 4h-2V3a1 1 0 0 0-2 0v1H9V3a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3Zm1 15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-7h16Zm0-9H4V7a1 1 0 0 1 1-1h2v1a1 1 0 0 0 2 0V6h6v1a1 1 0 0 0 2 0V6h2a1 1 0 0 1 1 1Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="heart" viewBox="0 0 24 24">
          <path fill="currentColor" d="M20.16 4.61A6.27 6.27 0 0 0 12 4a6.27 6.27 0 0 0-8.16 9.48l7.45 7.45a1 1 0 0 0 1.42 0l7.45-7.45a6.27 6.27 0 0 0 0-8.87Zm-1.41 7.46L12 18.81l-6.75-6.74a4.28 4.28 0 0 1 3-7.3a4.25 4.25 0 0 1 3 1.25a1 1 0 0 0 1.42 0a4.27 4.27 0 0 1 6 6.05Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="heart-solid" viewBox="0 0 24 24">
          <path fill="currentColor" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </symbol>

        <symbol xmlns="http://www.w3.org/2000/svg" id="plus" viewBox="0 0 24 24">
          <path fill="currentColor" d="M19 11h-6V5a1 1 0 0 0-2 0v6H5a1 1 0 0 0 0 2h6v6a1 1 0 0 0 2 0v-6h6a1 1 0 0 0 0-2Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="minus" viewBox="0 0 24 24">
          <path fill="currentColor" d="M19 11H5a1 1 0 0 0 0 2h14a1 1 0 0 0 0-2Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="cart" viewBox="0 0 24 24">
          <path fill="currentColor" d="M8.5 19a1.5 1.5 0 1 0 1.5 1.5A1.5 1.5 0 0 0 8.5 19ZM19 16H7a1 1 0 0 1 0-2h8.491a3.013 3.013 0 0 0 2.885-2.176l1.585-5.55A1 1 0 0 0 19 5H6.74a3.007 3.007 0 0 0-2.82-2H3a1 1 0 0 0 0 2h.921a1.005 1.005 0 0 1 .962.725l.155.545v.005l1.641 5.742A3 3 0 0 0 7 18h12a1 1 0 0 0 0-2Zm-1.326-9l-1.22 4.274a1.005 1.005 0 0 1-.963.726H8.754l-.255-.892L7.326 7ZM16.5 19a1.5 1.5 0 1 0 1.5 1.5a1.5 1.5 0 0 0-1.5-1.5Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="check" viewBox="0 0 24 24">
          <path fill="currentColor" d="M18.71 7.21a1 1 0 0 0-1.42 0l-7.45 7.46l-3.13-3.14A1 1 0 1 0 5.29 13l3.84 3.84a1 1 0 0 0 1.42 0l8.16-8.16a1 1 0 0 0 0-1.47Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="trash" viewBox="0 0 24 24">
          <path fill="currentColor" d="M10 18a1 1 0 0 0 1-1v-6a1 1 0 0 0-2 0v6a1 1 0 0 0 1 1ZM20 6h-4V5a3 3 0 0 0-3-3h-2a3 3 0 0 0-3 3v1H4a1 1 0 0 0 0 2h1v11a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3V8h1a1 1 0 0 0 0-2ZM10 5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v1h-4Zm7 14a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V8h10Zm-3-1a1 1 0 0 0 1-1v-6a1 1 0 0 0-2 0v6a1 1 0 0 0 1 1Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="star-outline" viewBox="0 0 15 15">
          <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M7.5 9.804L5.337 11l.413-2.533L4 6.674l2.418-.37L7.5 4l1.082 2.304l2.418.37l-1.75 1.793L9.663 11L7.5 9.804Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="star-solid" viewBox="0 0 15 15">
          <path fill="currentColor" d="M7.953 3.788a.5.5 0 0 0-.906 0L6.08 5.85l-2.154.33a.5.5 0 0 0-.283.843l1.574 1.613l-.373 2.284a.5.5 0 0 0 .736.518l1.92-1.063l1.921 1.063a.5.5 0 0 0 .736-.519l-.373-2.283l1.574-1.613a.5.5 0 0 0-.283-.844L8.921 5.85l-.968-2.062Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="search" viewBox="0 0 24 24">
          <path fill="currentColor" d="M21.71 20.29L18 16.61A9 9 0 1 0 16.61 18l3.68 3.68a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.39ZM11 18a7 7 0 1 1 7-7a7 7 0 0 1-7 7Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="user" viewBox="0 0 24 24">
          <path fill="currentColor" d="M15.71 12.71a6 6 0 1 0-7.42 0a10 10 0 0 0-6.22 8.18a1 1 0 0 0 2 .22a8 8 0 0 1 15.9 0a1 1 0 0 0 1 .89h.11a1 1 0 0 0 .88-1.1a10 10 0 0 0-6.25-8.19ZM12 12a4 4 0 1 1 4-4a4 4 0 0 1-4 4Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="close" viewBox="0 0 15 15">
          <path fill="currentColor" d="M7.953 3.788a.5.5 0 0 0-.906 0L6.08 5.85l-2.154.33a.5.5 0 0 0-.283.843l1.574 1.613l-.373 2.284a.5.5 0 0 0 .736.518l1.92-1.063l1.921 1.063a.5.5 0 0 0 .736-.519l-.373-2.283l1.574-1.613a.5.5 0 0 0-.283-.844L8.921 5.85l-.968-2.062Z"/>
        </symbol>
      </defs>
    </svg>

    <div class="preloader-wrapper">
      <div class="preloader">
      </div>
    </div>

    <div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="offcanvasCart" aria-labelledby="My Cart">
      <div class="offcanvas-header justify-content-center">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <div class="order-md-last">
          <?php
                $GST_RATE = 0.05;
                $cart = $_SESSION['cart'] ?? array();
                $itemCount = 0;
                $subtotal = 0.0;
                foreach($cart as $ci) { $itemCount += intval($ci['quantity'] ?? 1); $price = isset($ci['price']) ? floatval(preg_replace('/[^0-9\.\-]/','', (string)$ci['price'])) : 0.0; $subtotal += $price * intval($ci['quantity'] ?? 1); }
                $total_gst = $subtotal * $GST_RATE;
                $total = $subtotal + $total_gst;
                
                // Check if user has voucher
                $voucher_applied = false;
                $voucher_discount_amount = 0;
                $user_id = $_SESSION['user_id'] ?? 0;
                if ($user_id > 0) {
                    // First check session for voucher
                    if (!empty($_SESSION['voucher_claimed'])) {
                        $voucher_applied = true;
                    } else {
                        // Then check database
                        $check_sql = "SELECT claim_id FROM tbl_voucher_claims WHERE user_id = ? AND voucher_code = '25PERCENT' AND status = 'active'";
                        $check_stmt = mysqli_prepare($conn, $check_sql);
                        if ($check_stmt) {
                            mysqli_stmt_bind_param($check_stmt, 'i', $user_id);
                            mysqli_stmt_execute($check_stmt);
                            $check_result = mysqli_stmt_get_result($check_stmt);
                            $voucher_applied = (mysqli_num_rows($check_result) > 0);
                        }
                    }
                }
                
                // Calculate discount if voucher applied
                if ($voucher_applied) {
                    $voucher_discount_amount = round($total * 0.15, 2);
                }
          ?>
          <h4 class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-primary">Your cart</span>
            <span class="badge bg-primary rounded-pill"><?php echo $itemCount; ?></span>
          </h4>
          <?php if (empty($cart)): ?>
            <div class="alert alert-info">Your cart is empty.</div>
          <?php else: ?>
          <ul class="list-group mb-3">
            <?php foreach($cart as $ci):
                $pname = htmlspecialchars($ci['name'] ?? 'Product');
                $pdesc = htmlspecialchars($ci['description'] ?? '');
                $pqty = intval($ci['quantity'] ?? 1);
                $pprice = isset($ci['price']) ? floatval(preg_replace('/[^0-9\.\-]/','', (string)$ci['price'])) : 0.0;
                $psubtotal = $pprice * $pqty;
            ?>
            <li class="list-group-item d-flex justify-content-between lh-sm">
              <div>
                <h6 class="my-0"><?php echo $pname; ?> × <?php echo $pqty; ?></h6>
                <?php if ($pdesc): ?><small class="text-body-secondary"><?php echo $pdesc; ?></small><?php endif; ?>
              </div>
              <span class="text-body-secondary">₹<?php echo number_format($psubtotal,2); ?></span>
            </li>
            <?php endforeach; ?>
            <li class="list-group-item d-flex justify-content-between">
              <span>Subtotal</span>
              <strong>₹<?php echo number_format($subtotal,2); ?></strong>
            </li>
            <li class="list-group-item d-flex justify-content-between">
              <span>GST (5%)</span>
              <strong>₹<?php echo number_format($total_gst,2); ?></strong>
            </li>
            <?php if ($voucher_applied): ?>
            <li class="list-group-item d-flex justify-content-between" style="background-color:#d4edda;">
              <span><strong>Voucher Discount (15%)</strong></span>
              <strong class="text-success">-₹<?php echo number_format($voucher_discount_amount,2); ?></strong>
            </li>
            <?php endif; ?>
            <li class="list-group-item d-flex justify-content-between" style="background-color:#fff3cd;">
              <span><strong>Total (INR)</strong></span>
              <strong>₹<?php echo number_format($voucher_applied ? ($subtotal + $total_gst - $voucher_discount_amount) : ($subtotal + $total_gst),2); ?></strong>
            </li>
          </ul>
          <?php endif; ?>

          <a href="cart.php" class="w-100 btn btn-primary btn-lg">Continue to checkout</a>
        </div>
      </div>
    </div>
    
    <div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="offcanvasSearch" aria-labelledby="Search">
      <div class="offcanvas-header justify-content-center">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <div class="order-md-last">
          <h4 class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-primary">Search</span>
          </h4>
          <form role="search" action="index.html" method="get" class="d-flex mt-3 gap-0">
            <input class="form-control rounded-start rounded-0 bg-light" type="email" placeholder="What are you looking for?" aria-label="What are you looking for?">
            <button class="btn btn-dark rounded-end rounded-0" type="submit">Search</button>
          </form>
        </div>
      </div>
    </div>

    <header>
      <?php
      include 'header.php';
      ?>
    </header>
    
    <?php
    // prepare slider images: the script will scan a directory for files
    // and automatically build the carousel.  To change what appears in the
    // homepage slider just delete (or move) the old files and drop your new
    // high‑resolution pictures into the folder.  The default path is
    // `images/chocolat` but you can point it at any other directory if you
    // prefer (for example `images/slider_hd`).
    //
    // **Important:** make sure the HD pictures are sized appropriately (e.g.
    // 1920×800 or similar) so they fill the full width of the screen.  The
    // carousel markup below no longer includes fixed width/height attributes
    // so the browser will size them responsively.
    $carousel_images = getCarouselImages('images/chocolat');
    // $carousel_images will never be empty thanks to the helper's fallback

    // Show cart error message if set
    if (!empty($_SESSION['cart_error'])): ?>
      <div class="alert alert-danger" style="margin: 20px;">
        <?php echo htmlspecialchars($_SESSION['cart_error']); ?>
      </div>
    <?php unset($_SESSION['cart_error']); endif; ?>

    <!-- Show cart success message if set -->
    <?php if (!empty($_SESSION['cart_success'])): ?>
      <div class="alert alert-success" style="margin: 20px;">
        <?php echo htmlspecialchars($_SESSION['cart_success']); ?>
      </div>
    <?php unset($_SESSION['cart_success']); endif; ?>

    


<?php
// additional slider for banner cards below primary carousel
// For the secondary dessert carousel we simply reuse the same files defined above
$banner_images = $carousel_images;
?>
<?php if (!$show_all && $vendor_id == 0): ?>

    <!-- promotional images start -->
    <section class="py-2">
      <div class="container-fluid text-center">
        <div class="promo-images" style="width:100%; max-width:none;">
          <!-- Only the second slider (promoCarousel) is shown -->
          <div id="promoCarousel" class="carousel slide mt-0" data-bs-ride="carousel" style="margin: 0 auto;">
            <div class="carousel-inner">
              <?php foreach($carousel_images as $i => $img): ?>
                <div class="carousel-item<?php echo $i === 0 ? ' active' : ''; ?>">
                  <img src="<?php echo htmlspecialchars($img); ?>" alt="Promo <?php echo $i + 1; ?>" class="d-block w-100 rounded" loading="lazy">
                </div>
              <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next</span>
            </button>
          </div>
        </div>
      </div>
    </section>
    <!-- promotional images end -->

<!-- categories start  -->

    <section class="py-3">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">

            <div class="section-header d-flex flex-wrap justify-content-between mb-3">
              <h2 class="section-title" >Categories</h2>
              <div class="d-flex align-items-center">
                <a href="viewProduct.php" class="btn-link text-decoration-none">View All Categories →</a>
                <div class="swiper-buttons">
                  <button class="swiper-prev category-carousel-prev btn btn-primary">❮</button>
                  <button class="swiper-next category-carousel-next btn btn-primary">❯</button>
                </div>
              </div>
             
            </div>
            
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="category-carousel swiper">
              <div class="swiper-wrapper">
                <?php
                if(!empty($categories)):
                  foreach($categories as $cat):
                    $catId = isset($cat['categories_id']) ? $cat['categories_id'] : '';
                    $catName = isset($cat['categories_name']) ? $cat['categories_name'] : '';
                    // resolve vendor-supplied image first, else fall back to one of the default icons
                    if (!empty($cat['categories_image'])) {
                      $catImg = findImagePath($cat['categories_image'], '');
                    } else {
                      $catImg = '';
                    }

                    if (empty($catImg)) {
                      $mapKey = preg_replace('/[^a-z0-9]/', '', strtolower(str_replace('&','and',$catName)));
                      // choose sensible default pictures for the dessert categories;
                      // others will fall back to a generic placeholder
                      $icons = [
                        'cakes'      => 'images/chocolat/d9161098cfb44018eb751a017e9557c1.jpg',
                        'cookies'    => 'images/chocolat/b3ac7733c4c0f81d13b5024bebae3408.jpg',
                        'donuts'     => 'images/chocolat/858fbe5ead0dff780cf58ffdb0725096.jpg',
                        'pancake'    => 'images/chocolat/pancake.jpg',
                        'pastries'   => 'images/chocolat/pastries.jpg',
                        'waffles'    => 'images/chocolat/waffle.jpeg',
                        'fruitsveges'=> 'images/icon-vegetables-broccoli.png',
                        'fruitsandveges'=> 'images/icon-vegetables-broccoli.png',
                        'default'    => 'images/icon-vegetables-broccoli.png'
                      ];
                      $catImg = $icons[$mapKey] ?? $icons['default'];
                      if (!file_exists(__DIR__ . '/' . $catImg)) {
                          $catImg = $icons['default'];
                      }
                    }
                ?>
                <div class="swiper-slide">
                  <a href="viewCategory.php?id=<?php echo urlencode($catId); ?>" style="text-decoration:none;display:block;height:100%;">
                    <div style="background:#FFD4D8;border-radius:20px;padding:20px;text-align:center;height:280px;display:flex;flex-direction:column;justify-content:center;align-items:center;box-shadow:0 4px 12px rgba(0,0,0,0.1);transition:transform 0.3s ease;cursor:pointer;" onmouseover="this.style.transform='translateY(-8px)'" onmouseout="this.style.transform='translateY(0)'">
                      <img src="<?php echo htmlspecialchars($catImg); ?>" alt="<?php echo htmlspecialchars($catName); ?>" style="width:200px;height:200px;object-fit:cover;border-radius:16px;margin-bottom:12px;pointer-events:none;">
                      <h3 class="category-title" style="color:#333;font-weight:600;font-size:16px;margin:0;pointer-events:none;"><?php echo htmlspecialchars($catName); ?></h3>
                    </div>
                    
                  </a>
                </div>
                <?php
                  endforeach;
                endif;
                ?>
              </div>
            </div>

          </div>
        </div>
      </div>
    </section>
    <!-- categories end  -->
<?php endif; ?>

<?php if (!$show_all && $vendor_id == 0): ?>
  <!-- Newly Arrived Brands start -->
    <section class="py-3 overflow-hidden">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">

            <div class="section-header d-flex flex-wrap flex-wrap justify-content-between mb-2">
              
              <h2 class="section-title">Top 3 Shops</h2>

              <div class="d-flex align-items-center">
                <a href="vendor_products_list.php" class="btn-link text-decoration-none">View All Shops →</a>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="shops-grid">
              <?php if (!empty($top_vendors)): ?>
                <?php $rank = 1; foreach($top_vendors as $vend):
                        // resolve logo path, allow absolute URLs or existing files
                        // fallback to an online placeholder if no logo or lookup fails
                        $logo = 'https://via.placeholder.com/80?text=Shop';
                        if (!empty($vend['logo_path'])) {
                            $raw = $vend['logo_path'];
                            // if it looks like a remote URL, use directly
                            if (preg_match('#^(https?:)?//#i', $raw)) {
                                $logo = $raw;
                            } else {
                                // build candidate filesystem paths and corresponding URL
                                $candidates = [
                                    ['path'=>$raw,'url'=>$raw],
                                    ['path'=>'uploads/'.$raw,'url'=>'uploads/'.$raw],
                                    ['path'=>'uploads/vendors/'.$raw,'url'=>'uploads/vendors/'.$raw],
                                    ['path'=>'../uploads/'.$raw,'url'=>'../uploads/'.$raw],
                                    ['path'=>'../uploads/vendors/'.$raw,'url'=>'../uploads/vendors/'.$raw],
                                    ['path'=>'../admin/uploads/vendors/'.$raw,'url'=>'../admin/uploads/vendors/'.$raw],
                                ];
                                // if the stored value itself contains "admin/" prefix, try stripping it
                                if (strpos($raw, 'admin/') === 0) {
                                    $alt = substr($raw, strlen('admin/'));
                                    $candidates[] = ['path'=>$alt,'url'=>$alt];
                                    $candidates[] = ['path'=>'../'.$alt,'url'=>'../'.$alt];
                                }
                                foreach($candidates as $cand) {
                                    // check relative to current dir and parent dir
                                    if (file_exists(__DIR__.'/'.$cand['path']) || file_exists(__DIR__.'/../'.$cand['path'])) {
                                        $logo = $cand['url'];
                                        break;
                                    }
                                }
                                // if still not found but raw exists, use raw so browser tries
                                if ($logo === 'images/default-vendor.png' && !empty($raw)) {
                                    $logo = $raw;
                                }
                            }
                        }
                        $total_sold = intval($vend['total_products_sold'] ?? 0);
                ?>
                <div class="vendor-item-wrapper" style="position: relative; display: inline-block; text-align: center;">
                    <!-- Medal Badge -->
                    <?php 
                        $medals = ['🥇', '🥈', '🥉'];
                        $medal = isset($medals[$rank - 1]) ? $medals[$rank - 1] : '';
                    ?>
                    <?php if ($medal): ?>
                    <div style="position: absolute; top: -5px; right: -5px; font-size: 28px; z-index: 10; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <?php echo $medal; ?>
                    </div>
                    <?php endif; ?>
                    <a href="all_vendor_products.php?vendor_id=<?php echo intval($vend['vendor_id']); ?>" class="vendor-item" style="text-decoration:none;display:inline-block;">
                        <img src="<?php echo htmlspecialchars($logo); ?>" alt="Shop Logo" class="top-shop-logo" onerror="this.onerror=null;this.src='https://via.placeholder.com/80?text=Shop'">
                    </a>
                    <!-- Sales Info -->
                    <div style="margin-top: 8px; font-size: 12px; color: #666;">
                        <strong><?php echo htmlspecialchars($vend['shop_name']); ?></strong><br>
                        <span style="color: #28a745; font-weight: bold;">📦 <?php echo number_format($total_sold); ?> sold</span>
                    </div>
                </div>
                <?php $rank++; endforeach; ?>
              <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 20px;">
                  <em>No shops available</em>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- Newly Arrived Brands end -->
<?php endif; ?>

<?php if (false): ?>
  <?php endif; ?>

    <!-- <section class="py-5">
      <div class="container-fluid">
        <div class="row">
          
          <div class="col-md-6">
            <div class="banner-ad bg-danger mb-3" style="background: url('images/ad-image-3.png');background-repeat: no-repeat;background-position: right bottom;">
              <div class="banner-content p-5">

                <div class="categories text-primary fs-3 fw-bold">Upto 25% Off</div>
                <h3 class="banner-title">Luxa Dark Chocolate</h3>
                <p>Very tasty & creamy vanilla flavour creamy muffins.</p>
                <a href="#" class="btn btn-dark text-uppercase">Show Now</a>

              </div>
            
            </div>
          </div>
          <div class="col-md-6">
            <div class="banner-ad bg-info" style="background: url('images/ad-image-4.png');background-repeat: no-repeat;background-position: right bottom;">
              <div class="banner-content p-5">

                <div class="categories text-primary fs-3 fw-bold">Upto 25% Off</div>
                <h3 class="banner-title">Creamy Muffins</h3>
                <p>Very tasty & creamy vanilla flavour creamy muffins.</p>
                <a href="#" class="btn btn-dark text-uppercase">Show Now</a>

              </div>
            
            </div>
          </div>
             
        </div>
      </div>
    </section> -->

<?php if (!$show_all && $vendor_id == 0 && !empty($best_products)): ?>
    <!-- Best selling products start -->
    <section class="py-3 overflow-hidden">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
            <div class="section-header d-flex flex-wrap justify-content-between my-2">
              <h2 class="section-title">Best selling products</h2>
              <div class="d-flex align-items-center">
                <a href="#" class="btn-link text-decoration-none">View All Categories &rarr;</a>
                <div class="swiper-buttons">
                  <button class="swiper-prev products-carousel-prev btn btn-primary">❮</button>
                  <button class="swiper-next products-carousel-next btn btn-primary">❯</button>
                </div>  
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="products-carousel swiper">
              <div class="swiper-wrapper">
                <?php foreach ($best_products as $p):
                      $pid = $p['product_id'] ?? '';
                      $pname = $p['product_name'] ?? '';
                      $price = floatval($p['product_price'] ?? 0);
                      $pimg = findImagePath($p['product_image'] ?? '');
                      // compute discount similarly to other pages
                      $discountPct = 0;
                      if (isset($p['discount_percent']) && floatval($p['discount_percent']) > 0) {
                          $discountPct = floatval($p['discount_percent']);
                      } elseif (isset($p['discount_price']) && floatval($p['discount_price']) > 0 && $price > 0 && floatval($p['discount_price']) < $price) {
                          $discountPct = round((1 - floatval($p['discount_price']) / $price) * 100);
                      } elseif (isset($p['discount']) && floatval($p['discount']) > 0 && $price > 0) {
                          $discountPct = round(floatval($p['discount']) / $price * 100);
                      }
                      $vendorDisc = floatval($p['vendor_discount_percent'] ?? 0);
                      if ($vendorDisc > $discountPct) {
                          $discountPct = $vendorDisc;
                      }
                      $displayPrice = $price;
                      if ($discountPct > 0) {
                          $displayPrice = round($price * (1 - $discountPct/100),2);
                      }
                ?>
                <div class="product-item swiper-slide position-relative p-3 h-100">
                    <a href="#" class="btn-wishlist position-absolute" style="right:8px;top:8px;"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                    <figure class="text-center">
                        <a href="viewProduct.php?id=<?php echo urlencode($pid); ?>" title="<?php echo htmlspecialchars($pname); ?>">
                            <img src="<?php echo htmlspecialchars($pimg); ?>" class="tab-image img-fluid" style="max-height:200px; object-fit:contain;">
                        </a>
                    </figure>
                    <h3 class="h6 mt-2 text-center"><?php echo htmlspecialchars($pname); ?></h3>
                    <?php if ($discountPct>0): ?>
                        <div class="discount-text text-center mb-2"><?php echo htmlspecialchars($discountPct); ?>% off</div>
                    <?php endif; ?>
                    <span class="price d-block fw-bold mb-3 text-center">₹<?php echo htmlspecialchars(($discountPct>0) ? $displayPrice : $price); ?></span>
                    <form method="post" action="add_to_cart.php" class="d-flex align-items-center justify-content-between mt-auto m-0">
                        <div class="input-group product-qty" style="width:120px;">
                            <button type="button" class="quantity-left-minus btn btn-outline-secondary btn-number" data-type="minus">-</button>
                            <input type="text" id="quantity" name="quantity" value="1" class="form-control input-number text-center cart-qty-input" style="max-width:48px;" />
                            <button type="button" class="quantity-right-plus btn btn-outline-secondary btn-number" data-type="plus">+</button>
                        </div>
                        <div>
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($pid); ?>">
                            <input type="hidden" name="vendor_id" value="<?php echo htmlspecialchars($p['vendor_id'] ?? 0); ?>">
                            <input type="hidden" name="name" value="<?php echo htmlspecialchars($pname); ?>">
                            <input type="hidden" name="price" value="<?php echo htmlspecialchars(($discountPct>0) ? $displayPrice : $price); ?>">
                            <input type="hidden" name="image" value="<?php echo htmlspecialchars($pimg); ?>">
                            <button class="nav-link btn btn-sm btn-primary" style="padding:6px 10px;">Add to Cart</button>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <!-- / products-carousel -->
          </div>
        </div>
      </div>
    </section>
<?php endif; ?>

    <section class="py-3">
      <div class="container-fluid">

        <div class="bg-secondary py-4 my-3 rounded-5" style="background: url('images/bg-leaves-img-pattern.png') no-repeat;">
          <div class="container my-3">
            <div class="row">
              <div class="col-md-6 p-5">
                <div class="section-header">
                  <h2 class="section-title display-4">Get <span class="text-primary">15% Discount</span> on your first order</h2>
                </div>
                <!-- <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Dictumst amet, metus, sit massa posuere maecenas. At tellus ut nunc amet vel egestas.</p> -->
              </div>
              <div class="col-md-6 p-5">
                <?php if ($user_id > 0 && $voucher_status === 'not_claimed'): ?>
                  <!-- Logged in and haven't claimed voucher yet -->
                  <div id="voucher-section">
                    <h3 class="mb-4">Claim Your 15% Discount Voucher!</h3>
                    <div id="voucher-message" style="display:none;"></div>
                    <button type="button" id="claim-voucher-btn" class="btn btn-dark btn-lg w-100 mb-3">
                      Claim 15% Discount Voucher
                    </button>
                    <p class="text-muted small">
                      <strong>Voucher Details:</strong><br>
                      ✓ 15% discount on your first order<br>
                      ✓ Can be used only once<br>
                      ✓ Valid on all products
                    </p>
                  </div>
                <?php elseif ($user_id > 0 && $voucher_status === 'already_claimed'): ?>
                  <!-- Already claimed voucher -->
                  <div class="alert alert-info">
                    <h5 class="alert-heading">Voucher Already Claimed!</h5>
                    <p class="mb-0">You can use it only on your first order.</p>
                  </div>
                <?php else: ?>
                  <!-- Not logged in - show newsletter form -->
                  <form id="newsletter-form">
                    <div class="mb-3">
                      <label for="name" class="form-label">Name</label>
                      <input type="text" class="form-control form-control-lg" name="name" id="name" placeholder="Name">
                    </div>
                    <div class="mb-3">
                      <label for="email" class="form-label">Email</label>
                      <input type="email" class="form-control form-control-lg" name="email" id="email" placeholder="abc@mail.com">
                    </div>
                    <div class="form-check form-check-inline mb-3">
                      <label class="form-check-label" for="subscribe">
                      <input class="form-check-input" type="checkbox" id="subscribe" value="subscribe">
                      Subscribe to the newsletter</label>
                    </div>
                    <div class="d-grid gap-2">
                      <button type="submit" class="btn btn-dark btn-lg">Submit</button>
                    </div>
                  </form>
                <?php endif; ?>
              </div>
              
            </div>
            
          </div>
        </div>
        
      </div>
    </section>
<!-- 
    Most popular products start -->
    <!-- <section class="py-5 overflow-hidden">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">

            <div class="section-header d-flex justify-content-between">
              
              <h2 class="section-title">Most popular products</h2>

              <div class="d-flex align-items-center">
                <a href="#" class="btn-link text-decoration-none">View All Categories →</a>
                <div class="swiper-buttons">
                  <button class="swiper-prev products-carousel-prev btn btn-primary">❮</button>
                  <button class="swiper-next products-carousel-next btn btn-primary">❯</button>
                </div>  
              </div>
            </div>
            
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">

            <div class="products-carousel swiper">
              <div class="swiper-wrapper">
                
                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-tomatoes.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>

                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-tomatoketchup.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>

                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-bananas.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>

                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-bananas.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>
                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-tomatoes.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>

                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-tomatoketchup.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>

                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-bananas.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>

                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-bananas.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>
                
              </div>
            </div>
            <!-- / products-carousel -->

          </div>
        </div>
      </div>
    </section> 
    <!-- Most popular products end -->

    <!-- Just arrived start -->
    <!--<section class="py-5 overflow-hidden">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">

            <div class="section-header d-flex justify-content-between">
              
              <h2 class="section-title">Just arrived</h2>

              <div class="d-flex align-items-center">
                <a href="#" class="btn-link text-decoration-none">View All Categories →</a>
                <div class="swiper-buttons">
                  <button class="swiper-prev products-carousel-prev btn btn-primary">❮</button>
                  <button class="swiper-next products-carousel-next btn btn-primary">❯</button>
                </div>  
              </div>
            </div>
            
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">

            <div class="products-carousel swiper">
              <div class="swiper-wrapper">
                
                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-tomatoes.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>

                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-tomatoketchup.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>

                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-bananas.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>

                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-bananas.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>
                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-tomatoes.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>

                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-tomatoketchup.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>

                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-bananas.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>

                <div class="product-item swiper-slide">
                  <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                  <figure>
                    <a href="index.html" title="Product Title">
                      <img src="images/thumb-bananas.png"  class="tab-image">
                    </a>
                  </figure>
                  <h3>Sunstar Fresh Melon Juice</h3>
                  <span class="qty">1 Unit</span><span class="rating"><svg width="24" height="24" class="text-primary"><use xlink:href="#star-solid"></use></svg> 4.5</span>
                  <span class="price">$18.00</span>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="input-group product-qty">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                              <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                            </button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                        <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                            </button>
                        </span>
                    </div>
                    <a href="#" class="nav-link">Add to Cart <iconify-icon icon="uil:shopping-cart"></a>
                  </div>
                </div>
                
              </div>
            </div>
            

          </div>
        </div>
      </div>
    </section>-->
    <!-- Just arrived end --> 

    <!-- Our Recent Blog -->
    <!-- <section id="latest-blog" class="py-5">
      <div class="container-fluid">
        <div class="row">
          <div class="section-header d-flex align-items-center justify-content-between my-5">
            <h2 class="section-title">Our Recent Blog</h2>
            <div class="btn-wrap align-right">
              <a href="#" class="d-flex align-items-center nav-link">Read All Articles <svg width="24" height="24"><use xlink:href="#arrow-right"></use></svg></a>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <article class="post-item card border-0 shadow-sm p-3">
              <div class="image-holder zoom-effect">
                <a href="#">
                  <img src="images/post-thumb-1.jpg" alt="post" class="card-img-top">
                </a>
              </div>
              <div class="card-body">
                <div class="post-meta d-flex text-uppercase gap-3 my-2 align-items-center">
                  <div class="meta-date"><svg width="16" height="16"><use xlink:href="#calendar"></use></svg>22 Aug 2021</div>
                  <div class="meta-categories"><svg width="16" height="16"><use xlink:href="#category"></use></svg>tips & tricks</div>
                </div>
                <div class="post-header">
                  <h3 class="post-title">
                    <a href="#" class="text-decoration-none">Top 10 casual look ideas to dress up your kids</a>
                  </h3>
                  <p>Lorem ipsum dolor sit amet, consectetur adipi elit. Aliquet eleifend viverra enim tincidunt donec quam. A in arcu, hendrerit neque dolor morbi...</p>
                </div>
              </div>
            </article>
          </div>
          <div class="col-md-4">
            <article class="post-item card border-0 shadow-sm p-3">
              <div class="image-holder zoom-effect">
                <a href="#">
                  <img src="images/post-thumb-2.jpg" alt="post" class="card-img-top">
                </a>
              </div>
              <div class="card-body">
                <div class="post-meta d-flex text-uppercase gap-3 my-2 align-items-center">
                  <div class="meta-date"><svg width="16" height="16"><use xlink:href="#calendar"></use></svg>25 Aug 2021</div>
                  <div class="meta-categories"><svg width="16" height="16"><use xlink:href="#category"></use></svg>trending</div>
                </div>
                <div class="post-header">
                  <h3 class="post-title">
                    <a href="#" class="text-decoration-none">Latest trends of wearing street wears supremely</a>
                  </h3>
                  <p>Lorem ipsum dolor sit amet, consectetur adipi elit. Aliquet eleifend viverra enim tincidunt donec quam. A in arcu, hendrerit neque dolor morbi...</p>
                </div>
              </div>
            </article>
          </div>
          <div class="col-md-4">
            <article class="post-item card border-0 shadow-sm p-3">
              <div class="image-holder zoom-effect">
                <a href="#">
                  <img src="images/post-thumb-3.jpg" alt="post" class="card-img-top">
                </a>
              </div>
              <div class="card-body">
                <div class="post-meta d-flex text-uppercase gap-3 my-2 align-items-center">
                  <div class="meta-date"><svg width="16" height="16"><use xlink:href="#calendar"></use></svg>28 Aug 2021</div>
                  <div class="meta-categories"><svg width="16" height="16"><use xlink:href="#category"></use></svg>inspiration</div>
                </div>
                <div class="post-header">
                  <h3 class="post-title">
                    <a href="#" class="text-decoration-none">10 Different Types of comfortable clothes ideas for women</a>
                  </h3>
                  <p>Lorem ipsum dolor sit amet, consectetur adipi elit. Aliquet eleifend viverra enim tincidunt donec quam. A in arcu, hendrerit neque dolor morbi...</p>
                </div>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section> -->
    <!-- Our Recent Blog end -->

    <!-- <section class="py-5">
      <div class="container-fluid">
        <div class="row row-cols-1 row-cols-sm-3 row-cols-lg-5">
          <div class="col">
            <div class="card mb-3 border-0">
              <div class="row">
                <div class="col-md-2 text-dark">
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M21.5 15a3 3 0 0 0-1.9-2.78l1.87-7a1 1 0 0 0-.18-.87A1 1 0 0 0 20.5 4H6.8l-.33-1.26A1 1 0 0 0 5.5 2h-2v2h1.23l2.48 9.26a1 1 0 0 0 1 .74H18.5a1 1 0 0 1 0 2h-13a1 1 0 0 0 0 2h1.18a3 3 0 1 0 5.64 0h2.36a3 3 0 1 0 5.82 1a2.94 2.94 0 0 0-.4-1.47A3 3 0 0 0 21.5 15Zm-3.91-3H9L7.34 6H19.2ZM9.5 20a1 1 0 1 1 1-1a1 1 0 0 1-1 1Zm8 0a1 1 0 1 1 1-1a1 1 0 0 1-1 1Z"/></svg>
                </div>
                <div class="col-md-10">
                  <div class="card-body p-0">
                    <h5>Free delivery</h5>
                    <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipi elit.</p>
                  </div>
                </div>
              </div>
              </div>
          </div>
          <div class="col">
            <div class="card mb-3 border-0">
              <div class="row">
                <div class="col-md-2 text-dark">
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M19.63 3.65a1 1 0 0 0-.84-.2a8 8 0 0 1-6.22-1.27a1 1 0 0 0-1.14 0a8 8 0 0 1-6.22 1.27a1 1 0 0 0-.84.2a1 1 0 0 0-.37.78v7.45a9 9 0 0 0 3.77 7.33l3.65 2.6a1 1 0 0 0 1.16 0l3.65-2.6A9 9 0 0 0 20 11.88V4.43a1 1 0 0 0-.37-.78ZM18 11.88a7 7 0 0 1-2.93 5.7L12 19.77l-3.07-2.19A7 7 0 0 1 6 11.88v-6.3a10 10 0 0 0 6-1.39a10 10 0 0 0 6 1.39Zm-4.46-2.29l-2.69 2.7l-.89-.9a1 1 0 0 0-1.42 1.42l1.6 1.6a1 1 0 0 0 1.42 0L15 11a1 1 0 0 0-1.42-1.42Z"/></svg>
                </div>
                <div class="col-md-10">
                  <div class="card-body p-0">
                    <h5>100% secure payment</h5>
                    <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipi elit.</p>
                  </div>
                </div>
              </div>
              </div>
          </div>
          <div class="col">
            <div class="card mb-3 border-0">
              <div class="row">
                <div class="col-md-2 text-dark">
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M22 5H2a1 1 0 0 0-1 1v4a3 3 0 0 0 2 2.82V22a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-9.18A3 3 0 0 0 23 10V6a1 1 0 0 0-1-1Zm-7 2h2v3a1 1 0 0 1-2 0Zm-4 0h2v3a1 1 0 0 1-2 0ZM7 7h2v3a1 1 0 0 1-2 0Zm-3 4a1 1 0 0 1-1-1V7h2v3a1 1 0 0 1-1 1Zm10 10h-4v-2a2 2 0 0 1 4 0Zm5 0h-3v-2a4 4 0 0 0-8 0v2H5v-8.18a3.17 3.17 0 0 0 1-.6a3 3 0 0 0 4 0a3 3 0 0 0 4 0a3 3 0 0 0 4 0a3.17 3.17 0 0 0 1 .6Zm2-11a1 1 0 0 1-2 0V7h2ZM4.3 3H20a1 1 0 0 0 0-2H4.3a1 1 0 0 0 0 2Z"/></svg>
                </div>
                <div class="col-md-10">
                  <div class="card-body p-0">
                    <h5>Quality guarantee</h5>
                    <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipi elit.</p>
                  </div>
                </div>
              </div>
              </div>
          </div>
          <div class="col">
            <div class="card mb-3 border-0">
              <div class="row">
                <div class="col-md-2 text-dark">
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M12 8.35a3.07 3.07 0 0 0-3.54.53a3 3 0 0 0 0 4.24L11.29 16a1 1 0 0 0 1.42 0l2.83-2.83a3 3 0 0 0 0-4.24A3.07 3.07 0 0 0 12 8.35Zm2.12 3.36L12 13.83l-2.12-2.12a1 1 0 0 1 0-1.42a1 1 0 0 1 1.41 0a1 1 0 0 0 1.42 0a1 1 0 0 1 1.41 0a1 1 0 0 1 0 1.42ZM12 2A10 10 0 0 0 2 12a9.89 9.89 0 0 0 2.26 6.33l-2 2a1 1 0 0 0-.21 1.09A1 1 0 0 0 3 22h9a10 10 0 0 0 0-20Zm0 18H5.41l.93-.93a1 1 0 0 0 0-1.41A8 8 0 1 1 12 20Z"/></svg>
                </div>
                <div class="col-md-10">
                  <div class="card-body p-0">
                    <h5>guaranteed savings</h5>
                    <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipi elit.</p>
                  </div>
                </div>
              </div>
              </div>
          </div>
          <div class="col">
            <div class="card mb-3 border-0">
              <div class="row">
                <div class="col-md-2 text-dark">
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M18 7h-.35A3.45 3.45 0 0 0 18 5.5a3.49 3.49 0 0 0-6-2.44A3.49 3.49 0 0 0 6 5.5A3.45 3.45 0 0 0 6.35 7H6a3 3 0 0 0-3 3v2a1 1 0 0 0 1 1h1v6a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3v-6h1a1 1 0 0 0 1-1v-2a3 3 0 0 0-3-3Zm-7 13H8a1 1 0 0 1-1-1v-6h4Zm0-9H5v-1a1 1 0 0 1 1-1h5Zm0-4H9.5A1.5 1.5 0 1 1 11 5.5Zm2-1.5A1.5 1.5 0 1 1 14.5 7H13ZM17 19a1 1 0 0 1-1 1h-3v-7h4Zm2-8h-6V9h5a1 1 0 0 1 1 1Z"/></svg>
                </div>
                <div class="col-md-10">
                  <div class="card-body p-0">
                    <h5>Daily offers</h5>
                    <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipi elit.</p>
                  </div>
                </div>
              </div>
              </div>
          </div>
        </div>
      </div>
    </section> -->

    <footer class="py-5">
      <?php
     include 'footer.php';
     ?>
    </footer>
    
    
    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="js/plugins.js"></script>
    <script src="js/script.js"></script>
    <script>
      // Add to Cart Handler
      function addToCart(button, event) {
        if (event) {
          event.preventDefault();
          event.stopPropagation();
        }
        // Get the product item container
        const productItem = button.closest('.product-item');
        if (!productItem) return;
        // if vendor is offline, block
        if (productItem.getAttribute('data-vendor-online') === '0') {
            alert('This shop is currently offline; you cannot add its products to cart.');
            return;
        }
        // Extract product details
        const productId = productItem.querySelector('[data-product-id]')?.getAttribute('data-product-id') || '';
        const productName = productItem.querySelector('h3')?.textContent || '';
        const productImage = productItem.querySelector('img.tab-image')?.src || '';
        const productPrice = productItem.querySelector('.price:not([style*="text-decoration"])') || productItem.querySelector('.price');
        const priceText = productPrice?.textContent?.replace(/[^0-9.]/g, '') || '0';
        const quantityInput = productItem.querySelector('input.input-number');
        const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : 1;
        // Validate quantity (frontend)
        if (isNaN(quantity) || quantity < 1) {
          alert('Please add an appropriate quantity (minimum 1).');
          if (quantityInput) quantityInput.focus();
          return;
        }
        // Get vendor_id from data attribute
        const vendorId = productItem.getAttribute('data-vendor-id') || '0';
        if (!productId) {
          alert('Product ID not found');
          return;
        }
        // Create form data
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('vendor_id', vendorId);
        formData.append('name', productName);
        formData.append('price', priceText);
        formData.append('quantity', quantity);
        formData.append('image', productImage);
        // Submit to add_to_cart.php
        fetch('add_to_cart.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(() => {
          // Show success message and redirect
          alert('Product added to cart!');
          window.location.href = 'cart.php';
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error adding to cart. Please try again.');
        });
      }

      // Extra: Prevent submitting 0 or negative quantity via form submit (for non-JS add)
      document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form[action="add_to_cart.php"]').forEach(function(form) {
          form.addEventListener('submit', function(e) {
            var qtyInput = form.querySelector('input[name="quantity"]');
            var qty = parseInt(qtyInput.value, 10);
            if (isNaN(qty) || qty < 1) {
              alert('Please add an appropriate quantity (minimum 1).');
              qtyInput.focus();
              e.preventDefault();
            }
          });
        });
      });
    </script>

    <!-- Voucher Claim Script -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const claimBtn = document.getElementById('claim-voucher-btn');
        const voucherMsg = document.getElementById('voucher-message');
        
        if (claimBtn) {
          claimBtn.addEventListener('click', function() {
            claimBtn.disabled = true;
            claimBtn.innerHTML = 'Processing...';
            
            fetch('claim_voucher.php', {
              method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
              voucherMsg.style.display = 'block';
              if (data.success) {
                voucherMsg.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                claimBtn.innerHTML = 'Voucher Claimed! ✓';
                setTimeout(() => {
                  location.reload();
                }, 2000);
              } else {
                voucherMsg.innerHTML = '<div class="alert alert-warning">' + data.message + '</div>';
                claimBtn.disabled = false;
                claimBtn.innerHTML = 'Claim 15% Discount Voucher';
              }
            })
            .catch(error => {
              console.error('Error:', error);
              voucherMsg.innerHTML = '<div class="alert alert-danger">Error processing voucher. Please try again.</div>';
              voucherMsg.style.display = 'block';
              claimBtn.disabled = false;
              claimBtn.innerHTML = 'Claim 15% Discount Voucher';
            });
          });
        }
      });
    </script>
  </body>
</html>

