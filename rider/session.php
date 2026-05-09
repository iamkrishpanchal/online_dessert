<?php
// Centralized session guard for rider area
include_once __DIR__ . '/../session.php';

if (empty($_SESSION['rider_id'])) {
    header('Location: login.php');
    exit;
}
