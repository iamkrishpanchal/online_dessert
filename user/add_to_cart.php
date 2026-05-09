<?php
session_start();
include 'connection.php';

$product_id = intval($_POST['product_id'] ?? 0);
$vendor_id = intval($_POST['vendor_id'] ?? 0);
$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? 0;
$qty = intval($_POST['quantity'] ?? 1);

// Validate quantity is at least 1
if ($qty < 1) {
    $_SESSION['cart_error'] = 'Please add an appropriate quantity (minimum 1).';
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header('Location: ' . $redirect);
    exit;
}
$image = $_POST['image'] ?? '';

if ($product_id <= 0) { header('Location: index.php'); exit; }

// ensure vendor is online
if ($vendor_id > 0) {
    $vres = mysqli_query($conn, "SELECT is_online, shop_name FROM tbl_vendors WHERE vendor_id = $vendor_id LIMIT 1");
    if ($vres && $vrow = mysqli_fetch_assoc($vres)) {
        if (intval($vrow['is_online']) === 0) {
            $_SESSION['cart_error'] = 'Sorry, the shop "' . ($vrow['shop_name'] ?? '') . '" is currently offline.';
            $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
            header('Location: ' . $redirect);
            exit;
        }
    }
}

// Prevent adding when stock is not available
$stockColumn = null;
$tablesRes = mysqli_query($conn, "SHOW TABLES");
$existingTables = [];
while ($tr = mysqli_fetch_row($tablesRes)) {
    $existingTables[] = $tr[0];
}

// Prefer tbl_products (newer schema), fallback to tbl_product (older schema)
$prodTable = in_array('tbl_products', $existingTables) ? 'tbl_products' : (in_array('tbl_product', $existingTables) ? 'tbl_product' : null);
if ($prodTable) {
    $colRes = mysqli_query($conn, "SHOW COLUMNS FROM {$prodTable} LIKE 'stock'");
    if ($colRes && mysqli_num_rows($colRes) > 0) {
        $stockColumn = 'stock';
    } else {
        $colRes2 = mysqli_query($conn, "SHOW COLUMNS FROM {$prodTable} LIKE 'product_stock'");
        if ($colRes2 && mysqli_num_rows($colRes2) > 0) {
            $stockColumn = 'product_stock';
        }
    }
}

if ($stockColumn) {
    $stmt = mysqli_prepare($conn, "SELECT {$stockColumn} FROM {$prodTable} WHERE product_id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $product_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        if ($row) {
            $stock = intval($row[$stockColumn] ?? 0);
            if ($stock <= 0) {
                $_SESSION['cart_error'] = 'This product is currently out of stock.';
                $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
                header('Location: ' . $redirect);
                exit;
            }
            // Check if requested quantity exceeds available stock
            $existing_qty = isset($_SESSION['cart'][(string)$product_id]['quantity']) ? intval($_SESSION['cart'][(string)$product_id]['quantity']) : 0;
            $total_requested = $existing_qty + $qty;
            if ($total_requested > $stock) {
                $_SESSION['cart_error'] = 'Stock not available. Please reduce the quantity.';
                $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
                header('Location: ' . $redirect);
                exit;
            }
        }
    }
}

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// **SINGLE SHOP RESTRICTION**: Check if product is from the same vendor as existing cart items
if (!empty($_SESSION['cart']) && $vendor_id > 0) {
    // Get the vendor_id of the first item already in the cart
    $existing_vendor_id = null;
    foreach ($_SESSION['cart'] as $item) {
        $existing_vendor_id = intval($item['vendor_id'] ?? 0);
        break; // Get first item's vendor
    }
    
    // If the existing cart has items from a different vendor, show error
    if ($existing_vendor_id > 0 && $existing_vendor_id !== $vendor_id) {
        // Get shop names for better error message
        $existing_shop_name = '';
        $new_shop_name = '';
        
        $existing_vendor_res = mysqli_query($conn, "SELECT shop_name FROM tbl_vendors WHERE vendor_id = $existing_vendor_id LIMIT 1");
        if ($existing_vendor_res && $existing_row = mysqli_fetch_assoc($existing_vendor_res)) {
            $existing_shop_name = $existing_row['shop_name'] ?? 'Shop';
        }
        
        $new_vendor_res = mysqli_query($conn, "SELECT shop_name FROM tbl_vendors WHERE vendor_id = $vendor_id LIMIT 1");
        if ($new_vendor_res && $new_row = mysqli_fetch_assoc($new_vendor_res)) {
            $new_shop_name = $new_row['shop_name'] ?? 'Shop';
        }
        
        $_SESSION['cart_error'] = "You can only order from one shop per order. Your cart has items from \"{$existing_shop_name}\" but you're trying to add an item from \"{$new_shop_name}\". Please complete your current order first, then you can order from a different shop.";
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header('Location: ' . $redirect);
        exit;
    }
}

$key = (string)$product_id;
if (isset($_SESSION['cart'][$key])) {
    $_SESSION['cart'][$key]['quantity'] += $qty;
} else {
    $_SESSION['cart'][$key] = [
        'product_id' => $product_id,
        'vendor_id' => $vendor_id,
        'name' => $name,
        'price' => $price,
        'quantity' => $qty,
        'image' => $image
    ];
}

header('Location: cart.php');
exit;
