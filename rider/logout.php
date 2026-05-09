<?php
session_start();
// clear rider session
unset($_SESSION['rider_id'], $_SESSION['rider_name'], $_SESSION['rider_email']);
session_destroy();
header('Location: login.php');
exit;
