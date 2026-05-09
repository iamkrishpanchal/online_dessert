<?php
// Centralized session guard for vendor area
include_once __DIR__ . '/../../session.php';

if (!isset($_SESSION['vendor_id'])) {
    header('Location: login.php');
    exit;
}
?>
