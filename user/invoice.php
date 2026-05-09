<?php
session_start();
include 'connection.php';

// determine invoice source: session data after placing COD order, or specific order by id
$bills = [];
$vendor_names = [];

if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    // ensure the order belongs to the logged-in user
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Fetch the single order (now we always create only one order per checkout)
    $order_res = mysqli_query($conn, "SELECT * FROM tbl_orders WHERE order_id=$order_id AND user_id=$user_id LIMIT 1");
    if ($order_res && $ord = mysqli_fetch_assoc($order_res)) {
        // Fetch all items for this order
        $items_res = mysqli_query($conn, "SELECT oi.*, p.vendor_id, v.shop_name AS vendor_name
                                           FROM tbl_order_items oi
                                           LEFT JOIN tbl_products p ON oi.product_id = p.product_id
                                           LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id
                                           WHERE oi.order_id=$order_id");
        $items = [];
        while ($row = mysqli_fetch_assoc($items_res)) {
            $items[] = $row;
        }
        $ord['items'] = $items;
        $bills[] = $ord;
    }
    
    if (empty($bills)) {
        // nothing found or not authorized
        header('Location: orders.php');
        exit;
    }
} else {
    // invoice page shows details of the last order(s) placed with COD
    if (empty($_SESSION['last_invoice']) || !is_array($_SESSION['last_invoice'])) {
        header('Location: orders.php');
        exit;
    }
    $bills = $_SESSION['last_invoice'];
    // clear it so refresh doesn't repeat
    unset($_SESSION['last_invoice']);
}

// optionally fetch vendor names for display from items
foreach ($bills as $bill) {
    foreach ($bill['items'] as $it) {
        $vid = intval($it['vendor_id'] ?? 0);
        if ($vid && !isset($vendor_names[$vid])) {
            if (!empty($it['vendor_name'])) {
                $vendor_names[$vid] = $it['vendor_name'];
            } else {
                // fallback to tbl_vendors if name not included
                $r = mysqli_query($conn, "SELECT shop_name FROM tbl_vendors WHERE vendor_id=$vid");
                if ($r && $row = mysqli_fetch_assoc($r)) {
                    $vendor_names[$vid] = $row['shop_name'];
                } else {
                    $vendor_names[$vid] = 'Vendor ' . $vid;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - FoodMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
      .invoice-card {
        border: 1px solid #dbe2ef;
        box-shadow: 0 6px 20px rgba(40, 56, 103, 0.08);
        border-radius: 14px;
        background: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }
      .invoice-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(40, 56, 103, 0.16);
      }
      .invoice-card .card-title {
        color: #1f3c88;
        font-weight: 700;
      }
      .invoice-card table thead th {
        background-color: #e9f0ff;
        color: #1a436a;
        border-bottom: 2px solid #b0c7ef;
      }
      .invoice-card .d-flex strong {
        color: #183b76;
      }
      .btn-view-orders {
        background: linear-gradient(90deg, #0f2461, #1b3a73);
        border-color: #0f2461;
        color: #f5f7fb;
        font-weight: 700;
      }
      .btn-view-orders:hover {
        background: linear-gradient(90deg, #152f80, #1f4a8f);
        color: #fff;
      }
      h4 {
        color: #0f2461;
        font-weight: 800;
        letter-spacing: 0.6px;
        margin-bottom: 1rem;
        border-left: 5px solid #1f3c88;
        padding-left: 0.75rem;
      }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<main class="container py-5">
    <h4>Invoice</h4>
    <?php foreach ($bills as $bill): ?>
        <div class="card mb-4 invoice-card">
            <div class="card-body">
                <h5 class="card-title">Order #<?php echo htmlspecialchars($bill['order_number']); ?></h5>
                <p class="card-text">
                    Delivery: <?php echo htmlspecialchars($bill['delivery_address'] ?? ''); ?>,
                    <?php echo htmlspecialchars($bill['delivery_city'] ?? ''); ?>
                    <?php echo htmlspecialchars($bill['delivery_pincode'] ?? ''); ?><br>
                    Phone: <?php echo htmlspecialchars($bill['phone'] ?? ''); ?>
                <table class="table">
                    <thead><tr><th>Product</th><th>Shop Name</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
                    <tbody>
                        <?php foreach ($bill['items'] as $it):
                            // price field can be 'price' from session or 'unit_price' from DB
                            $priceRaw = $it['price'] ?? $it['unit_price'] ?? 0;
                            $pr = floatval(preg_replace('/[^0-9\.-]/','',(string)$priceRaw));
                            // quantity field may be 'quantity' or 'qty'
                            $qty = intval($it['quantity'] ?? ($it['qty'] ?? 1));
                            $sub = $pr * $qty;
                            // item name may be in 'name' (from session) or 'product_name' (from DB)
                            $itemName = $it['name'] ?? $it['product_name'] ?? '';
                            // shop name from join
                            $shopName = $it['vendor_name'] ?? '';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($itemName); ?></td>
                            <td><?php echo htmlspecialchars($shopName); ?></td>
                            <td><?php echo $qty; ?></td>
                            <td>₹<?php echo number_format($pr,2); ?></td>
                            <td>₹<?php echo number_format($sub,2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="d-flex justify-content-between mb-2"><span>Subtotal:</span><strong>₹<?php echo number_format($bill['subtotal'],2); ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>GST (5%):</span><strong>₹<?php echo number_format($bill['tax'],2); ?></strong></div>
                <div class="d-flex justify-content-between mb-3 border-bottom pb-2"><span><strong>Subtotal (after GST):</strong></span><strong>₹<?php echo number_format($bill['subtotal'] + $bill['tax'],2); ?></strong></div>
                <?php if (!empty($bill['delivery_charges']) || $bill['delivery_charges'] === 0): ?>
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2"><span>Delivery Charges:</span><strong>₹<?php echo number_format($bill['delivery_charges'],2); ?></strong></div>
                <?php endif; ?>
                <div class="d-flex justify-content-between" style="font-size: 18px;"><span><strong>Final Total to Pay:</strong></span><strong class="text-success">₹<?php echo number_format($bill['total_amount'],2); ?></strong></div>
            </div>
        </div>
    <?php endforeach; ?>
    <a href="orders.php" class="btn btn-view-orders">View Orders</a>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>