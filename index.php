<?php
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // User is logged in, redirect to home page
    header("Location: user/index.php");
    exit;
} else {
    // User is not logged in, redirect to login page
    header("Location: user/login.php");
    exit;
}
?>
