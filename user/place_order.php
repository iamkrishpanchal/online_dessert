<?php
// legacy entry point – forward everything to the central checkout logic
session_start();

// if there is POST data we can keep it but the real work happens in checkout.php
// which handles multi‑vendor orders, item inserts and stores rows in tbl_orders.
// redirect immediately; the checkout page uses the same session cart and user_id.
header('Location: checkout.php');
exit;
?>