<?php
// Redirect to role-specific earnings pages
session_start();

// Admin earnings
if (!empty($_SESSION['admin_id'])) {
    header('Location: admin/earnings.php');
    exit;
}

// Rider earnings
if (!empty($_SESSION['rider_id'])) {
    header('Location: rider/earnings.php');
    exit;
}

// Default: send to login
header('Location: login.php');
exit;
