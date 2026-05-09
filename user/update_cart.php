<?php
session_start();
include 'connection.php';

// Update quantity in cart (increment / decrement)
$product_id = intval($_REQUEST['product_id'] ?? 0);
action:
$action = trim((string)($_REQUEST['action'] ?? ''));

if ($product_id <= 0 || $action === '') {
    header('Location: cart.php');
    exit;
}

$key = (string)$product_id;
if (!isset($_SESSION['cart'][$key])) {
    header('Location: cart.php');
    exit;
}

// Determine current quantity
$currentQty = intval($_SESSION['cart'][$key]['quantity'] ?? 0);
$newQty = $currentQty;

if ($action === 'inc') {
    $newQty = $currentQty + 1;
} elseif ($action === 'dec') {
    $newQty = $currentQty - 1;
}

if ($newQty <= 0) {
    unset($_SESSION['cart'][$key]);
    header('Location: cart.php');
    exit;
}

// If we have stock info, ensure we don't exceed available stock.
$stockColumn = null;
$tablesRes = mysqli_query($conn, "SHOW TABLES");
$existingTables = [];
while ($tr = mysqli_fetch_row($tablesRes)) {
    $existingTables[] = $tr[0];
}

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
            if ($newQty > $stock) {
                $_SESSION['cart_error'] = 'Not enough stock available for this product.';
                header('Location: cart.php');
                exit;
            }
        }
    }
}

// Update the cart quantity
$_SESSION['cart'][$key]['quantity'] = $newQty;

header('Location: cart.php');
exit;
