<?php
session_start();

// If vendor is logged in, send to dashboard; otherwise show login page
if (!empty($_SESSION['vendor_id']) || !empty($_SESSION['islogin'])) {
    header('Location: login.php');
    exit;
}

header('Location: login.php');
exit;
