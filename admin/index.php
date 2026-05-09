<?php
session_start();

// If already logged in, go to dashboard
if (isset($_SESSION['islogin']) && $_SESSION['islogin'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Redirect to project admin login page
header('Location: ../login.php');
exit;
?>
