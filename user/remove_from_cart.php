<?php
session_start();
$pid = isset($_GET['product_id']) ? (string)intval($_GET['product_id']) : '';
if ($pid && isset($_SESSION['cart'][$pid])) {
    unset($_SESSION['cart'][$pid]);
}
header('Location: cart.php');
exit;
