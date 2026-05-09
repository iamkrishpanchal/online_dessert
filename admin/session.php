<?php
// Centralized session guard
include_once __DIR__ . '/../session.php';

if (!isset($_SESSION['islogin']) || $_SESSION['islogin'] !== true) {
    header('Location: index.php');
    exit;
}
?>