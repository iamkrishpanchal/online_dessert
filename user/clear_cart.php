<?php
session_start();

// Cart clearing endpoint - supports both GET and POST
// Clears the cart so user can switch shops

// Handle GET request with confirm parameter
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['confirm']) && $_GET['confirm'] === '1') {
    unset($_SESSION['cart']);
    $_SESSION['cart'] = [];
    
    $_SESSION['cart_success'] = 'Cart cleared successfully. You can now browse products from other shops.';
    
    header('Location: index.php');
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear the cart
    unset($_SESSION['cart']);
    $_SESSION['cart'] = [];
    
    $_SESSION['cart_success'] = 'Cart cleared successfully. You can now browse products from other shops.';
    
    header('Location: index.php');
    exit;
}

// If accessed directly without parameters, redirect to cart
header('Location: cart.php');
exit;
?>
