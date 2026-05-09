<?php
session_start();
include 'connection.php';

// Prevent browser caching
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$GST_RATE = 0.05; // 5% GST
$DELIVERY_CHARGE = 50; // Fixed delivery charge
$VOUCHER_DISCOUNT = 0.15; // 15% discount

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
$total_gst = 0;
$delivery_charge = 0;
$voucher_applied = false;
$voucher_discount_amount = 0;
$total = 0;

// Calculate subtotal and GST first
foreach($cart as $it) {
  $price = isset($it['price']) ? floatval(preg_replace('/[^0-9\.\-]/', '', (string)$it['price'])) : 0.0;
  $qty = isset($it['quantity']) ? intval($it['quantity']) : 1;
  $subtotal += $price * $qty;
}

$total_gst = round($subtotal * $GST_RATE, 2);
// Delivery charge should be 10% of (subtotal + GST)
$delivery_charge = $DELIVERY_CHARGE;

// Total includes cart subtotal + GST + delivery charge
$total_without_voucher = round($subtotal + $total_gst + $delivery_charge, 2);
$total = $total_without_voucher;

// Check if user has claimed voucher (for display only, not subtracting from shown total in cart)
$user_id = $_SESSION['user_id'] ?? 0;

// Ensure voucher tracking table exists (required for voucher-based discounts)
if ($user_id > 0) {
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

    // If the user has already placed an order, voucher should not apply anymore.
    $orderCountStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM tbl_orders WHERE user_id = ?");
    $has_prior_orders = false;
    if ($orderCountStmt) {
        mysqli_stmt_bind_param($orderCountStmt, 'i', $user_id);
        mysqli_stmt_execute($orderCountStmt);
        mysqli_stmt_bind_result($orderCountStmt, $orderCount);
        mysqli_stmt_fetch($orderCountStmt);
        mysqli_stmt_close($orderCountStmt);
        $has_prior_orders = ($orderCount > 0);
    }

    if ($has_prior_orders) {
        // Clear any claimed voucher from session (prevent showing discount)
        unset($_SESSION['voucher_claimed']);
        $voucher_applied = false;
    } else {
        // First check session for voucher (recently claimed)
        if (!empty($_SESSION['voucher_claimed'])) {
            $voucher_applied = true;
        } else {
            // Then check database
            $check_sql = "SELECT claim_id FROM tbl_voucher_claims WHERE user_id = ? AND voucher_code = '25PERCENT' AND status = 'active'";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, 'i', $user_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $voucher_applied = (mysqli_num_rows($check_result) > 0);
        }
    }
}

// Apply voucher discount if available
$final_total = 0;
if ($voucher_applied) {
    $voucher_discount_amount = round($total_without_voucher * $VOUCHER_DISCOUNT, 2);
    $final_total = round($total_without_voucher - $voucher_discount_amount, 2); // Deduct voucher from final total
} else {
    $voucher_discount_amount = 0;
    $final_total = $total_without_voucher;
}

// Debug: Log the values
error_log("DEBUG CART - Subtotal=$subtotal, GST=$total_gst, Delivery=$delivery_charge, Total_Without_Voucher=$total_without_voucher, Voucher_Applied=$voucher_applied, Discount=$voucher_discount_amount, Final_Total=$final_total");

?><!doctype html>
<html><head>
  <meta charset="utf-8"><title>Cart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    html, body {
      background: #ffffff !important;
      background-image: none !important;
      color: #222 !important;
      opacity: 1 !important;
      filter: none !important;
      box-shadow: none !important;
    }
    body::before, body::after, html::before, html::after {
      display: none !important;
      background: none !important;
      content: none !important;
    }
  </style>
</head><body>
<?php include 'header.php'; ?>
<main class="container py-4">
  <h2>Your Cart</h2>
  <?php if (!empty($_SESSION['cart_error'])): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['cart_error']); ?></div>
    <?php unset($_SESSION['cart_error']); ?>
  <?php endif; ?>
  <?php if (empty($cart)): ?>
    <div class="alert alert-info">Your cart is empty. Browse <a href="index.php">products</a>.</div>
  <?php else: ?>
    <!-- SINGLE SHOP NOTICE -->
    <?php 
      $cart_vendor_id = 0;
      $cart_vendor_name = '';
      if (!empty($cart)) {
        foreach ($cart as $item) {
          $cart_vendor_id = intval($item['vendor_id'] ?? 0);
          if ($cart_vendor_id > 0) {
            $vres = mysqli_query($conn, "SELECT shop_name FROM tbl_vendors WHERE vendor_id = $cart_vendor_id LIMIT 1");
            if ($vres && $vrow = mysqli_fetch_assoc($vres)) {
              $cart_vendor_name = $vrow['shop_name'] ?? 'Shop';
            }
            break;
          }
        }
      }
    ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center">
      <div>
        <strong>📦 Shop Order Policy:</strong> You are currently ordering from <strong>"<?php echo htmlspecialchars($cart_vendor_name); ?>"</strong> 
        <br><small>You can only add products from this shop in a single order. To order from a different shop, you must complete this order first.</small>
      </div>
      <a href="clear_cart.php?confirm=1" class="btn btn-warning btn-sm ms-3" onclick="return confirm('Clear cart and switch to a different shop?');">Clear Cart & Switch Shop</a>
    </div>
    
    <table class="table">
      <thead><tr><th></th><th>Product</th><th>Shop Name</th><th>Qty</th><th>Unit Price</th><th>Total Price</th><th>GST (5%)</th><th>Subtotal</th><th></th></tr></thead>
      <tbody>
      <?php foreach($cart as $pid => $it):
        $sub_price = isset($it['price']) ? floatval(preg_replace('/[^0-9\.-]/', '', (string)$it['price'])) : 0.0;
        $sub_qty = isset($it['quantity']) ? intval($it['quantity']) : 1;
        $item_total = $sub_price * $sub_qty;
        $item_gst = $item_total * $GST_RATE;
        $item_subtotal = $item_total + $item_gst;
        // Fetch shop name for this product
        $shop_name = '';
        $vendor_id = !empty($it['vendor_id']) ? intval($it['vendor_id']) : 0;
        // Fallback: if vendor_id missing, fetch from product table
        if ($vendor_id === 0) {
          $pid_int = intval($pid);
          $vquery = mysqli_query($conn, "SELECT vendor_id FROM tbl_products WHERE product_id = $pid_int LIMIT 1");
          if ($vquery && $vrow = mysqli_fetch_assoc($vquery)) {
            $vendor_id = intval($vrow['vendor_id']);
            // Optionally update session for future
            $_SESSION['cart'][$pid]['vendor_id'] = $vendor_id;
          }
        }
        if ($vendor_id > 0) {
          $shop_res = mysqli_query($conn, "SELECT shop_name FROM tbl_vendors WHERE vendor_id = $vendor_id LIMIT 1");
          if ($shop_res && $row = mysqli_fetch_assoc($shop_res)) {
            $shop_name = $row['shop_name'];
          }
        }
      ?>
        <tr>
          <td style="width:80px;"><img src="<?php echo htmlspecialchars($it['image'] ?? 'images/default-product.png'); ?>" style="max-width:70px;"></td>
          <td><?php echo htmlspecialchars($it['name']); ?></td>
          <td><?php echo htmlspecialchars($shop_name); ?></td>
          <td>
            <div class="d-flex align-items-center">
              <form method="get" action="update_cart.php" class="me-2">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($pid); ?>">
                <input type="hidden" name="action" value="dec">
                <button type="submit" class="btn btn-sm btn-outline-secondary">-</button>
              </form>
              <form method="post" action="update_cart.php" style="margin:0 4px;">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($pid); ?>">
                <input type="number" name="quantity" value="<?php echo htmlspecialchars($it['quantity']); ?>" min="1" max="100000" class="form-control form-control-sm" style="width:70px;text-align:center;" onchange="this.form.submit()">
              </form>
              <form method="get" action="update_cart.php" class="ms-2">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($pid); ?>">
                <input type="hidden" name="action" value="inc">
                <button type="submit" class="btn btn-sm btn-outline-secondary">+</button>
              </form>
            </div>
          </td>
          <td>₹<?php echo number_format($sub_price,2); ?></td>
          <td>₹<?php echo number_format($item_total,2); ?></td>
          <td>₹<?php echo number_format($item_gst,2); ?></td>
          <td><strong>₹<?php echo number_format($item_subtotal,2); ?></strong></td>
          <td><a href="remove_from_cart.php?product_id=<?php echo urlencode($pid); ?>" class="btn btn-sm btn-outline-danger">Remove</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="card mt-4" style="max-width: 400px; margin-left: auto;">
      <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
          <span>Subtotal (before GST):</span>
          <strong>₹<?php echo number_format($subtotal, 2); ?></strong>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span>GST (5%):</span>
          <strong>₹<?php echo number_format($total_gst, 2); ?></strong>
        </div>
        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
          <span><strong>Subtotal (after GST):</strong></span>
          <strong>₹<?php echo number_format($subtotal + $total_gst, 2); ?></strong>
        </div>
        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
          <span>Delivery Charges:</span>
          <strong>₹<?php echo number_format($delivery_charge, 2); ?></strong>
        </div>
        <?php if ($voucher_applied): ?>
        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
          <span class="text-success"><strong>Voucher Discount (15%):</strong></span>
          <strong class="text-success">-₹<?php echo number_format($voucher_discount_amount, 2); ?></strong>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between" style="font-size: 18px;">
          <span><strong>Final Total to Pay:</strong></span>
          <strong class="text-success">₹<?php echo number_format($final_total, 2); ?></strong>
          <!-- DEBUG: subtotal=<?php echo $subtotal; ?>, gst=<?php echo $total_gst; ?>, delivery=<?php echo $delivery_charge; ?>, total_without_voucher=<?php echo $total_without_voucher; ?>, discount=<?php echo $voucher_discount_amount; ?>, final=<?php echo $final_total; ?> -->
        </div>
      </div>
    </div>
    <div class="mt-4">
      <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="checkout.php" class="btn btn-success btn-lg">Proceed to Checkout</a>
      <?php else: ?>
        <a href="login.php?redirect=cart.php" class="btn btn-primary btn-lg">Login to Checkout</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>
<?php include 'footer.php'; ?>
</body></html>
