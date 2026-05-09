<?php
// this endpoint is no longer used – Razorpay callbacks are handled inside checkout.php
// keep a redirect in case external links still point here
session_start();
header('Location: checkout.php');
exit;
?>