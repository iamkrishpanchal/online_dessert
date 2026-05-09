<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}
session_destroy();

// Redirect to home page
header('Location: index.php');
exit;
?>
