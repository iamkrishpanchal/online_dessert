<?php
$price=450.00;
$discount_percent=0.00;
$vendor_discount=5.00;
$original_discount=max(0,floatval($discount_percent));
$applied_discount=max($original_discount,$vendor_discount);
$discounted_price='';
if ($price !== '' && $applied_discount > 0) {
    $discounted_price=round(floatval($price)*(1-$applied_discount/100),2);
    if ($discounted_price >= floatval($price)) { $discounted_price=''; $applied_discount=0; }
}
var_dump($price,$original_discount,$vendor_discount,$applied_discount,$discounted_price);
?>
