<?php

session_start();
include 'connection.php';

// Prevent browser caching
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$total_subtotal = 0;
$total_gst_amount = 0;
$voucher_applied = false;
$voucher_discount = 0;
$DELIVERY_CHARGE = 50; // Fixed delivery charge (same as cart.php)

if(isset($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $item) {
        $price = isset($item['price']) ? floatval(preg_replace('/[^0-9\.\-]/', '', (string)$item['price'])) : 0.0;
        $qty = intval($item['quantity'] ?? 1);
        $total_subtotal += $price * $qty;
    }
}

$total_gst_amount = $total_subtotal * 0.05;
$delivery_charges_total = $DELIVERY_CHARGE; // Use fixed delivery charge
$total = $total_subtotal + $total_gst_amount + $delivery_charges_total;

$_SESSION['order_total'] = $total;



// simple checkout page that creates orders (one per vendor) and items
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
if ($user_id <= 0) {
    // Invalid user session; force login
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Voucher is only valid on the user's first order.
$has_prior_orders = false;
$voucher_claim_attempt_on_second_order = false;
$orderCountStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM tbl_orders WHERE user_id = ?");
if ($orderCountStmt) {
    mysqli_stmt_bind_param($orderCountStmt, 'i', $user_id);
    mysqli_stmt_execute($orderCountStmt);
    mysqli_stmt_bind_result($orderCountStmt, $orderCount);
    mysqli_stmt_fetch($orderCountStmt);
    mysqli_stmt_close($orderCountStmt);
    $has_prior_orders = ($orderCount > 0);
}
if ($has_prior_orders) {
    // Check if user has a claimed/active voucher and is trying to use on second+ order
    $voucherCheckStmt = mysqli_prepare($conn, "SELECT claim_id FROM tbl_voucher_claims WHERE user_id = ? AND voucher_code = '25PERCENT' AND status = 'active'");
    if ($voucherCheckStmt) {
        mysqli_stmt_bind_param($voucherCheckStmt, 'i', $user_id);
        mysqli_stmt_execute($voucherCheckStmt);
        $voucherResult = mysqli_stmt_get_result($voucherCheckStmt);
        if (mysqli_num_rows($voucherResult) > 0) {
            $voucher_claim_attempt_on_second_order = true;
        }
        mysqli_stmt_close($voucherCheckStmt);
    }
    
    // Prevent repeated application of the voucher if the session still carries it.
    unset($_SESSION['voucher_claimed']);

    // Ensure any active voucher claim is marked as used so it cannot be applied later.
    $markUsedStmt = mysqli_prepare($conn, "UPDATE tbl_voucher_claims SET status = 'used' WHERE user_id = ? AND voucher_code = '25PERCENT' AND status = 'active'");
    if ($markUsedStmt) {
        mysqli_stmt_bind_param($markUsedStmt, 'i', $user_id);
        mysqli_stmt_execute($markUsedStmt);
        mysqli_stmt_close($markUsedStmt);
    }
}

// Check if user has claimed voucher (only for first-time order)
if (!$has_prior_orders) {
    // First check session for voucher (recently claimed)
    if (!empty($_SESSION['voucher_claimed'])) {
        $voucher_applied = true;
        $voucher_discount = round($total * 0.15, 2); // 15% discount on total including GST
    } else {
        // Then check database
        $check_voucher = mysqli_query($conn, "SELECT claim_id FROM tbl_voucher_claims WHERE user_id = $user_id AND voucher_code = '25PERCENT' AND status = 'active'");
        if ($check_voucher && mysqli_num_rows($check_voucher) > 0) {
            $voucher_applied = true;
            $voucher_discount = round($total * 0.15, 2); // 15% discount on total including GST
        }
    }
}
// ensure users table exists (some installations may not have created it yet)
$tblUsersChk = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_users'");
if ($tblUsersChk && mysqli_num_rows($tblUsersChk) === 0) {
    mysqli_query($conn,
        "CREATE TABLE IF NOT EXISTS tbl_users (
            user_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_name VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            city VARCHAR(100),
            pincode VARCHAR(10),
            profile_image VARCHAR(255),
            user_type ENUM('customer','vendor','admin','rider') DEFAULT 'customer',
            is_active INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );
}

// fetch user data for default address/phone (old schema lacks city/pincode)
$user = [];
$stmt = mysqli_prepare($conn, "SELECT user_name,email,phone,address FROM tbl_users WHERE user_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res) ?: [];
    mysqli_stmt_close($stmt);
}

// If the session user_id no longer exists in tbl_users (e.g., account deleted), force logout.
if (empty($user)) {
    session_unset();
    session_destroy();
    header('Location: login.php?error=invalid_user');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

// **SINGLE SHOP ENFORCEMENT**: Validate all cart items are from the same vendor
$cart_vendor_ids = [];
$all_same_vendor = true;
foreach ($cart as $item) {
    $vid = intval($item['vendor_id'] ?? 0);
    if ($vid > 0 && !in_array($vid, $cart_vendor_ids)) {
        $cart_vendor_ids[] = $vid;
    }
}

if (count($cart_vendor_ids) > 1) {
    // Multiple vendors detected - this should never happen if add_to_cart.php works correctly
    // But this is a safety measure
    $_SESSION['cart_error'] = 'Error: Your cart contains products from multiple shops. This is not allowed. Your cart has been cleared. Please start fresh.';
    unset($_SESSION['cart']);
    header('Location: cart.php');
    exit;
}

$errors = [];

// Check if user tried to use voucher on non-first order
if ($voucher_claim_attempt_on_second_order) {
    $errors[] = 'You can claim the voucher on the first order only. This voucher is no longer valid.';
}
// ensure no vendor involved is currently offline
foreach ($cart as $item) {
    $vid = intval($item['vendor_id'] ?? 0);
    if ($vid > 0) {
        $vres = mysqli_query($conn, "SELECT is_online, shop_name FROM tbl_vendors WHERE vendor_id = $vid LIMIT 1");
        if ($vres && $vrow = mysqli_fetch_assoc($vres)) {
            if (intval($vrow['is_online']) === 0) {
                $errors[] = 'Cannot place order. Shop "' . ($vrow['shop_name'] ?? '') . '" is currently offline.';
                break;
            }
        }
    }
}

// Determine which stock column is in use (newer schema uses `stock`, older uses `product_stock`).
$hasStockColumn = false;
$hasProductStockColumn = false;
$colRes = mysqli_query($conn, "SHOW COLUMNS FROM tbl_products LIKE 'stock'");
if ($colRes && mysqli_num_rows($colRes) > 0) {
    $hasStockColumn = true;
}
$colRes2 = mysqli_query($conn, "SHOW COLUMNS FROM tbl_products LIKE 'product_stock'");
if ($colRes2 && mysqli_num_rows($colRes2) > 0) {
    $hasProductStockColumn = true;
}

// Ensure there is enough stock for each item in the cart before placing the order.
foreach ($cart as $item) {
    $product_id = intval($item['product_id'] ?? 0);
    $qty = max(1, intval($item['quantity'] ?? 1));
    if ($product_id <= 0) {
        continue;
    }

    $selectCols = ['product_name'];
    if ($hasStockColumn) {
        $selectCols[] = 'stock';
    }
    if ($hasProductStockColumn) {
        $selectCols[] = 'product_stock';
    }
    $selectCols = array_unique($selectCols);
    $stmt = mysqli_prepare($conn, "SELECT " . implode(', ', $selectCols) . " FROM tbl_products WHERE product_id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $product_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if ($row) {
            $stock = 0;
            if ($hasStockColumn && isset($row['stock'])) {
                $stock = intval($row['stock']);
            } elseif ($hasProductStockColumn && isset($row['product_stock'])) {
                $stock = intval($row['product_stock']);
            }

            if ($stock < $qty) {
                $errors[] = sprintf("'%s' only has %d left in stock, but you requested %d.",
                    $row['product_name'] ?? 'Product', $stock, $qty);
            }
        } else {
            $errors[] = "One of the products in your cart is no longer available.";
        }
    }
}

// default payment method for form display
$payment_method = 'COD';

// ensure tbl_orders has the columns our code expects (older dumps use a simpler structure)
$neededCols = [
    'user_id INT NOT NULL',
    'vendor_id INT NOT NULL',
    'subtotal DECIMAL(10,2) NOT NULL',
    'tax DECIMAL(10,2) NOT NULL',
    'delivery_charges DECIMAL(10,2) NOT NULL',
    'discount DECIMAL(10,2) NOT NULL',
    'total_amount DECIMAL(10,2) NOT NULL',
    'delivery_address TEXT NOT NULL',
    'delivery_city VARCHAR(100)',
    'delivery_pincode VARCHAR(10)',
    'phone VARCHAR(20)',
    'order_status VARCHAR(50)',
    'payment_status VARCHAR(50)',
    'payment_method VARCHAR(50)',
    'rider_id INT DEFAULT NULL',
    "delivery_status VARCHAR(50) DEFAULT 'not_assigned'",
    'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
];
foreach ($neededCols as $colDef) {
    list($col)=explode(' ', $colDef,2);
    $col = trim($col);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM tbl_orders LIKE '$col'");
    if ($res && mysqli_num_rows($res) === 0) {
        // add column
        mysqli_query($conn, "ALTER TABLE tbl_orders ADD COLUMN $colDef");
    }
}

// ensure order_items table exists (older schema didn't have it)
// also make sure tbl_orders and tbl_product use InnoDB so foreign keys can work
foreach (['tbl_orders','tbl_product'] as $t) {
    $engineRes = mysqli_query($conn, "SHOW TABLE STATUS WHERE Name='$t'");
    if ($engineRes) {
        $row = mysqli_fetch_assoc($engineRes);
        if (isset($row['Engine']) && strtoupper($row['Engine']) !== 'INNODB') {
            mysqli_query($conn, "ALTER TABLE $t ENGINE=InnoDB");
        }
    }
}

$tblRes = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_order_items'");
if ($tblRes && mysqli_num_rows($tblRes) === 0) {
    // if either tbl_orders or tbl_product are not InnoDB after attempted conversion, skip FK creation
    $skipFK = false;
    $engineRes2 = mysqli_query($conn, "SHOW TABLE STATUS WHERE Name='tbl_orders'");
    if ($engineRes2) {
        $row2 = mysqli_fetch_assoc($engineRes2);
        if (!isset($row2['Engine']) || strtoupper($row2['Engine']) !== 'INNODB') {
            $skipFK = true;
        }
    }
    $engineRes3 = mysqli_query($conn, "SHOW TABLE STATUS WHERE Name='tbl_product'");
    if ($engineRes3) {
        $row3 = mysqli_fetch_assoc($engineRes3);
        if (!isset($row3['Engine']) || strtoupper($row3['Engine']) !== 'INNODB') {
            $skipFK = true;
        }
    }

    $createItems = "CREATE TABLE IF NOT EXISTS tbl_order_items (
        order_item_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        special_instructions TEXT";
    if (!$skipFK) {
        $createItems .= ",
        FOREIGN KEY (order_id) REFERENCES tbl_orders(order_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES tbl_product(product_id) ON DELETE RESTRICT";
    }
    $createItems .= ",
        INDEX order_idx (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    mysqli_query($conn, $createItems);
}

success:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // check for payment completion callback from Razorpay
    if (isset($_POST['payment_complete']) && !empty($_SESSION['razorpay_orders'])) {
        $ids = $_SESSION['razorpay_orders'];
        $idlist = implode(",", array_map('intval', $ids));
        mysqli_query($conn, "UPDATE tbl_orders SET payment_status='paid' WHERE order_id IN ($idlist)");
        // once paid, clear cart and session marker
        unset($_SESSION['cart']);
        unset($_SESSION['razorpay_orders']);
        // optionally record razorpay_payment_id for audit
        if (!empty($_POST['razorpay_payment_id'])) {
            $payid = mysqli_real_escape_string($conn, $_POST['razorpay_payment_id']);
            mysqli_query($conn, "UPDATE tbl_orders SET razorpay_payment_id='$payid' WHERE order_id IN ($idlist)");
        }
        // return simple response for AJAX
        echo 'OK';
        exit;
    }

    // gather shipping details (user may override)
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    // existing user table may not have city/pincode
    $delivery_city = trim($_POST['delivery_city'] ?? 'Surat');
    $delivery_pincode = '';  // No longer collecting pincode
    $phone = trim($_POST['phone'] ?? ($user['phone'] ?? ''));
    $payment_method = trim($_POST['payment_method'] ?? 'COD');

    if ($delivery_address === '') {
        $errors[] = 'Delivery address is required.';
    }
    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    }

    if (empty($errors)) {
        // create notifications table if missing (older dumps lack it)
        // ensure tbl_users is InnoDB for FK, otherwise omit FK
        $notifRes = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_notifications'");
        if ($notifRes && mysqli_num_rows($notifRes) === 0) {
            $skipUserFK = false;
            $uEngine = mysqli_query($conn, "SHOW TABLE STATUS WHERE Name='tbl_users'");
            if ($uEngine) {
                $ur = mysqli_fetch_assoc($uEngine);
                if (!isset($ur['Engine']) || strtoupper($ur['Engine']) !== 'INNODB') {
                    // try convert
                    mysqli_query($conn, "ALTER TABLE tbl_users ENGINE=InnoDB");
                    // re-check
                    $uEngine2 = mysqli_query($conn, "SHOW TABLE STATUS WHERE Name='tbl_users'");
                    $ur2 = $uEngine2 ? mysqli_fetch_assoc($uEngine2) : null;
                    if (!$ur2 || strtoupper($ur2['Engine']) !== 'INNODB') {
                        $skipUserFK = true;
                    }
                }
            }

            $createNotif = "CREATE TABLE IF NOT EXISTS tbl_notifications (
                notification_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                vendor_id INT DEFAULT NULL,
                admin_id INT DEFAULT NULL,
                order_id INT,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                status ENUM('unread','read') DEFAULT 'unread',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL";
            if (!$skipUserFK) {
                $createNotif .= ",
                FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE CASCADE";
            }
            $createNotif .= ",
                INDEX user_idx (user_id),
                INDEX vendor_idx (vendor_id),
                INDEX admin_idx (admin_id),
                INDEX order_idx (order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            mysqli_query($conn, $createNotif);
        }
        // ensure batch_id column exists on orders so we can group multi-vendor purchases
        $colChk = mysqli_query($conn, "SHOW COLUMNS FROM tbl_orders LIKE 'batch_id'");
        if ($colChk && mysqli_num_rows($colChk) === 0) {
            mysqli_query($conn, "ALTER TABLE tbl_orders ADD COLUMN batch_id VARCHAR(50) NULL");
        }

        // Create a SINGLE order with all items (instead of one per vendor)
        // Collect all vendors involved
        $vendors_involved = [];
        foreach ($cart as $item) {
            $vid = intval($item['vendor_id'] ?? 0);
            if ($vid && !in_array($vid, $vendors_involved)) {
                $vendors_involved[] = $vid;
            }
        }

        // compute totals for single order
        $subtotal = 0.0;
        foreach ($cart as $it) {
            $price = isset($it['price']) ? floatval(preg_replace('/[^0-9\.-]/', '', (string)$it['price'])) : 0;
            $qty = intval($it['quantity'] ?? 1);
            $subtotal += $price * $qty;
        }
        
        $tax = 0.05 * $subtotal;
        // Single delivery charge for the entire order
        $delivery_charges = $DELIVERY_CHARGE;
        
        // Apply voucher discount to the entire order
        $discount = 0.00;
        if ($voucher_applied) {
            $discount = $voucher_discount;
        }
        
        $total_amount = $subtotal + $tax + $delivery_charges - $discount;

        // Create single order (vendor_id will be NULL for multi-vendor orders, or use first vendor if single vendor)
        $order_number = 'ORD' . time() . rand(100, 999);
        $order_status = 'Confirmed';
        $payment_status = 'pending';
        $delivery_status = 'not_assigned';
        
        // For vendor_id, use the first vendor (for legacy compatibility) or NULL if multi-vendor
        $single_vendor_id = (count($vendors_involved) === 1) ? $vendors_involved[0] : NULL;

        $sql = "INSERT INTO tbl_orders
            (order_number,user_id,vendor_id,subtotal,tax,delivery_charges,discount,total_amount,
             delivery_address,delivery_city,delivery_pincode,phone,order_status,payment_status,payment_method,delivery_status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = mysqli_prepare($conn, $sql);
        $created_orders = [];
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'siidddddssssssss',
                $order_number, $user_id, $single_vendor_id,
                $subtotal, $tax, $delivery_charges, $discount, $total_amount,
                $delivery_address, $delivery_city, $delivery_pincode, $phone,
                $order_status, $payment_status, $payment_method, $delivery_status
            );
            if (mysqli_stmt_execute($stmt)) {
                $order_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                $created_orders[] = $order_id;
                
                error_log("✅ SINGLE ORDER CREATED: Order ID=$order_id, Order #=$order_number, User=$user_id, Vendors=" . implode(',', $vendors_involved));

                // Insert ALL order items under this single order
                foreach ($cart as $it) {
                    $product_id = intval($it['product_id'] ?? 0);
                    $name = $it['name'] ?? '';
                    $qty = intval($it['quantity'] ?? 1);
                    $unit_price = isset($it['price']) ? floatval(preg_replace('/[^0-9\.-]/', '', (string)$it['price'])) : 0;
                    $subtotal_item = $unit_price * $qty;

                    $item_sql = "INSERT INTO tbl_order_items
                        (order_id,product_id,product_name,quantity,unit_price,subtotal)
                        VALUES (?,?,?,?,?,?)";
                    $item_stmt = mysqli_prepare($conn, $item_sql);
                    if ($item_stmt) {
                        mysqli_stmt_bind_param($item_stmt, 'iisidd',
                            $order_id, $product_id, $name, $qty, $unit_price, $subtotal_item
                        );
                        mysqli_stmt_execute($item_stmt);
                        mysqli_stmt_close($item_stmt);

                        // Decrement stock for the product
                        if ($hasStockColumn) {
                            $update_stock = mysqli_prepare($conn, "UPDATE tbl_products SET stock = GREATEST(stock - ?, 0) WHERE product_id = ?");
                            if ($update_stock) {
                                mysqli_stmt_bind_param($update_stock, 'ii', $qty, $product_id);
                                mysqli_stmt_execute($update_stock);
                                mysqli_stmt_close($update_stock);
                            }
                        }

                        if ($hasProductStockColumn) {
                            $update_stock = mysqli_prepare($conn, "UPDATE tbl_products SET product_stock = GREATEST(product_stock - ?, 0) WHERE product_id = ?");
                            if ($update_stock) {
                                mysqli_stmt_bind_param($update_stock, 'ii', $qty, $product_id);
                                mysqli_stmt_execute($update_stock);
                                mysqli_stmt_close($update_stock);
                            }
                        }
                    }
                }

                // Notify user that order has been placed
                $not_sql = "INSERT INTO tbl_notifications
                    (user_id,order_id,title,message) VALUES (?,?,?,?)";
                $not_stmt = mysqli_prepare($conn, $not_sql);
                if ($not_stmt) {
                    $title = 'Order Placed';
                    $message = "Your order #{$order_number} has been received and is confirmed.";
                    mysqli_stmt_bind_param($not_stmt, 'iiss', $user_id, $order_id, $title, $message);
                    mysqli_stmt_execute($not_stmt);
                    mysqli_stmt_close($not_stmt);
                }

                // Notify each vendor involved about the new order items they need to fulfill
                foreach ($vendors_involved as $vendor_id) {
                    $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'vendor_id'");
                    if ($colCheck && mysqli_num_rows($colCheck) > 0) {
                        $vsql = "INSERT INTO tbl_notifications
                            (vendor_id,order_id,title,message) VALUES (?,?,?,?)";
                        $vstmt = mysqli_prepare($conn, $vsql);
                        if ($vstmt) {
                            $vtitle = 'New Order Received';
                            $vmessage = "A new order #{$order_number} has been placed. Check your items in the order.";
                            mysqli_stmt_bind_param($vstmt, 'iiss', $vendor_id, $order_id, $vtitle, $vmessage);
                            mysqli_stmt_execute($vstmt);
                            mysqli_stmt_close($vstmt);
                        }
                    }
                }

            } else {
                $errors[] = 'Failed to create order: ' . mysqli_error($conn);
            }
        } else {
            $errors[] = 'Database error preparing order insert.';
        }

        if (empty($errors)) {
            // Mark voucher as used after order is created
            if ($voucher_applied) {
                $check_voucher = mysqli_query($conn, "SELECT claim_id FROM tbl_voucher_claims WHERE user_id = $user_id AND voucher_code = '25PERCENT' AND status = 'active'");
                if ($check_voucher && mysqli_num_rows($check_voucher) > 0) {
                    $voucher_row = mysqli_fetch_assoc($check_voucher);
                    $claim_id = $voucher_row['claim_id'];
                    $first_order_id = $created_orders[0] ?? 0;
                    mysqli_query($conn, "UPDATE tbl_voucher_claims SET status = 'used', used_in_order_id = $first_order_id WHERE claim_id = $claim_id");
                }
            }
            
            // Store payment method in session for display on orders page
            $_SESSION['order_payment_method'] = $payment_method;
            
            // Build invoice data from the single order
            $bills = [];
            if (!empty($created_orders)) {
                $order_id = $created_orders[0];
                $order_res = mysqli_query($conn, "SELECT * FROM tbl_orders WHERE order_id = $order_id");
                if ($order_res && $ord = mysqli_fetch_assoc($order_res)) {
                    $items_res = mysqli_query($conn, "SELECT * FROM tbl_order_items WHERE order_id = $order_id");
                    $items = [];
                    while ($row = mysqli_fetch_assoc($items_res)) {
                        $items[] = $row;
                    }
                    $ord['items'] = $items;
                    $bills[] = $ord;
                }
            }
            
            // after order has been created
            if ($payment_method === 'COD') {
                // clear cart immediately and show invoice on orders page
                unset($_SESSION['cart']);
                $_SESSION['last_invoice'] = $bills;
                header('Location: orders.php?msg=ordered&invoice=1');
                exit;
            } elseif ($payment_method === 'Razorpay') {
                // keep order in session until payment completes
                $_SESSION['razorpay_orders'] = $created_orders;
                // compute total amount for checkout script, accounting for voucher discount
                $final_razor_total = $voucher_applied ? ($total - $voucher_discount) : $total;
                $razor_total = intval($final_razor_total * 100);
                // output minimal page with razorpay checkout invocation
                ?>
                <!DOCTYPE html>
                <html lang="en">
                <head><meta charset="utf-8"><title>Processing Payment</title></head>
                <body>
                <p>Redirecting to payment gateway...</p>
                <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
                <script>
                var options = {
                    key: "rzp_test_SJGMOFzjFC8P8G",
                    amount: <?php echo $razor_total; ?>,
                    currency: "INR",
                    name: "Dessert Magic",
                    description: "Order Payment",
                    handler: function(response) {
                        // notify server of successful payment
                        var xhr=new XMLHttpRequest();
                        xhr.open('POST','checkout.php',true);
                        xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
                        xhr.onload=function(){
                            window.location='orders.php?msg=paid';
                        };
                        xhr.send('payment_complete=1&razorpay_payment_id='+encodeURIComponent(response.razorpay_payment_id));
                    }
                };
                var rzp=new Razorpay(options);
                rzp.open();
                </script>
                </body>
                </html>
                <?php
                exit;
            } else {
                // unknown payment method, just clear and redirect
                unset($_SESSION['cart']);
                header('Location: orders.php?msg=ordered');
                exit;
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
    <title>Checkout - FoodMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: radial-gradient(circle at 20% 20%, #d9ecff, #edf6ff 45%, #f6f8fb 100%);
            color: #212529;
            min-height: 100vh;
            overflow-x: hidden;
        }
        main.container {
            position: relative;
            z-index: 2;
            max-width: 980px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #e7ecf0;
            border-radius: 11px;
            box-shadow: 0 14px 35px rgba(34, 50, 74, 0.15);
            padding: 30px;
            backdrop-filter: blur(6px);
        }
        main.container::before {
            content: "";
            position: absolute;
            top: -40px;
            left: -40px;
            width: 220px;
            height: 220px;
            background: linear-gradient(135deg, rgba(100, 135, 255, 0.35), rgba(147, 217, 255, 0.15));
            border-radius: 50%;
            filter: blur(30px);
            z-index: -1;
        }
        main.container::after {
            content: "";
            position: absolute;
            bottom: -50px;
            right: -40px;
            width: 240px;
            height: 240px;
            background: linear-gradient(135deg, rgba(255, 181, 119, 0.25), rgba(255, 104, 123, 0.15));
            border-radius: 50%;
            filter: blur(30px);
            z-index: -1;
        }
        h4, h5, h3 {
            color: #343a40;
            letter-spacing: 0.02em;
        }
        .form-control, .form-check .form-check-input, .form-check .form-check-label {
            border-radius: 8px;
        }
        .form-control:focus {
            border-color: #7f5cf0;
            box-shadow: 0 0 0 0.2rem rgba(127, 92, 240, 0.25);
        }
        table.table {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        table.table thead {
            background: #f5f7fa;
            color: #495057;
        }
        table.table tbody tr:hover {
            background: #f8f9fa;
        }
        .btn-success {
            background: linear-gradient(120deg, #34ca8f, #28a745);
            border: 1px solid #28a745;
        }
        .btn-secondary {
            border-radius: 8px;
        }
        .d-flex.justify-content-between span {
            font-size: 0.95rem;
            color: #495057;
        }
        .d-flex.justify-content-between strong {
            font-size: 1rem;
        }
        .alert {
            border-radius: 8px;
        }

        .checkout-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2f2f8f;
            background: linear-gradient(90deg, #e3d4ab, #d4dca3);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            box-shadow: 0 8px 18px rgba(44, 62, 123, 0.12);
        }
        .summary-panel {
            background: #f8fbff;
            border: 1px solid #d4e4f7;
            border-radius: 10px;
            padding: 15px;
            margin-top: 18px;
        }
        .checkout-total {
            font-size: 1.25rem;
            color: #04396f;
        }
        .payment-method-text {
            margin-top: 6px;
            color: #5a5a5a;
            font-weight: 600;
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        .payment-method-text {
            margin-top: 6px;
            color: #5a5a5a;
            font-weight: 600;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<main class="container py-5">
    <h4 class="checkout-title mb-4">Checkout</h4>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Delivery Address</label>
            <textarea name="delivery_address" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['delivery_address'] ?? $user['address'] ?? ''); ?></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">City</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['delivery_city'] ?? $user['city'] ?? 'Surat'); ?>" disabled>
                <input type="hidden" name="delivery_city" value="Surat">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? ''); ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Payment Method</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="payment_method" id="pm_cod" value="COD" <?php if(empty($payment_method) || ($payment_method ?? '') === 'COD') echo 'checked'; ?>>
                <label class="form-check-label" for="pm_cod">Cash on Delivery</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="payment_method" id="pm_razorpay" value="Razorpay" <?php if(($payment_method ?? '') === 'Razorpay') echo 'checked'; ?>>
                <label class="form-check-label" for="pm_razorpay">Pay Online (Razorpay)</label>
            </div>
        </div>
        <hr>
        <h5>Items</h5>
        <table class="table">
            <thead><tr><th>Product</th><th>Shop Name</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
            <tbody>
                <?php $gst_rate = 0.05; $grand = 0; foreach ($cart as $it):
                    $pr = isset($it['price']) ? floatval(preg_replace('/[^0-9\.-]/','',(string)$it['price'])) : 0;
                    $qty = intval($it['quantity']);
                    $sub = $pr * $qty;
                    $grand += $sub;
                    // Fetch shop name for this product
                    $shop_name = '';
                    if (!empty($it['vendor_id'])) {
                        $vendor_id = intval($it['vendor_id']);
                        $shop_res = mysqli_query($conn, "SELECT shop_name FROM tbl_vendors WHERE vendor_id = $vendor_id LIMIT 1");
                        if ($shop_res && $row = mysqli_fetch_assoc($shop_res)) {
                            $shop_name = $row['shop_name'];
                        }
                    }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($it['name']); ?></td>
                    <td><?php echo htmlspecialchars($shop_name); ?></td>
                    <td><?php echo $qty; ?></td>
                    <td>₹<?php echo number_format($pr,2); ?></td>
                    <td>₹<?php echo number_format($sub,2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-between mb-3">
            <span>Subtotal:</span><strong>₹<?php echo number_format($total_subtotal,2); ?></strong>
        </div>
        <div class="d-flex justify-content-between mb-3">
            <span>GST (5%):</span><strong>₹<?php echo number_format($total_gst_amount,2); ?></strong>
        </div>
        <?php
            // Use fixed delivery charge (₹50)
            $delivery_charges_display = $DELIVERY_CHARGE;
        ?>
        <div class="d-flex justify-content-between mb-3">
            <span>Delivery Charges:</span><strong>₹<?php echo number_format($delivery_charges_display, 2); ?></strong>
        </div>
        <?php if ($voucher_applied): ?>
        <div class="d-flex justify-content-between mb-3 text-success">
            <span><strong>Voucher Discount (15%):</strong></span><strong>-₹<?php echo number_format($voucher_discount,2); ?></strong>
        </div>
        <?php endif; ?>
        <div class="summary-panel">
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span><strong>₹<?php echo number_format($total_subtotal,2); ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>GST (5%):</span><strong>₹<?php echo number_format($total_gst_amount,2); ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Delivery Charges:</span><strong>₹<?php echo number_format($delivery_charges_display, 2); ?></strong>
            </div>
            <?php if ($voucher_applied): ?>
            <div class="d-flex justify-content-between mb-2 text-success">
                <span><strong>Voucher Discount (15%):</strong></span><strong>-₹<?php echo number_format($voucher_discount,2); ?></strong>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between border-top pt-3">
                <span><strong class="checkout-total">Total:</strong></span><strong class="checkout-total">₹<?php echo number_format($voucher_applied ? ($total_subtotal + $total_gst_amount + $delivery_charges_display - $voucher_discount) : ($total_subtotal + $total_gst_amount + $delivery_charges_display), 2); ?></strong>
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="btn btn-success btn-lg me-2">Place Order</button>
            <a href="cart.php" class="btn btn-secondary btn-lg">Back to Cart</a>
        </div>

        <script>
            // change button label depending on payment method
            const placeBtn = document.querySelector('button[type="submit"]');
            const radios = document.querySelectorAll('input[name="payment_method"]');
            function updateLabel() {
                const pm = document.querySelector('input[name="payment_method"]:checked').value;
                if (pm === 'Razorpay') {
                    placeBtn.textContent = 'Proceed to Payment';
                } else {
                    placeBtn.textContent = 'Place Order';
                }
            }
            radios.forEach(r=>r.addEventListener('change', updateLabel));
            updateLabel();

            // Disable button after form submission to prevent duplicate orders
            const form = placeBtn.closest('form');
            if (form) {
                form.addEventListener('submit', function() {
                    placeBtn.disabled = true;
                    placeBtn.textContent = 'Processing...';
                    placeBtn.style.opacity = '0.6';
                    placeBtn.style.cursor = 'not-allowed';
                });
            }
        </script>

<!-- </form> -->














            <?php if (($payment_method ?? '') === 'Razorpay'): ?>
                <!-- <form action="payment_success.php" method="POST"> -->
                <script
                    src="https://checkout.razorpay.com/v1/checkout.js"
                    data-key="rzp_test_SJGMOFzjFC8P8G"
                    data-amount="<?php echo $total * 100; ?>"
                    data-currency="INR"
                    data-buttontext="Pay Now" 
                    data-name="Dessert Magic"
                    data-description="Order Payment"
                    data-theme.color="#F37254">
                </script>
                <input type="hidden" name="hidden">
                <!-- </form> -->
            <?php endif; ?>
    </form>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>