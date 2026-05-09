# Order System Fix - Single Order for Multi-Shop Checkout

## Implementation Complete ✓

### Problem Statement
When users placed orders with products from multiple shops, the system:
- Created separate orders for each product/shop 
- Applied ₹50 delivery charge per shop (instead of per order)
- Displayed multiple entries in "My Orders"
- Made invoice consolidation complex

### Solution Overview
**Changed checkout logic to create ONE order containing ALL items from multiple vendors**

---

## Changes Made

### 1. File: `checkout.php` (Lines ~420-560)

#### Previous Logic (Per-Vendor Orders)
```php
// Grouped cart items by vendor
foreach ($vendor_groups as $vendor_id => $items) {
    // CREATE SEPARATE ORDER FOR EACH VENDOR
    // Each order had separate delivery charges
    // Each order created own notifications
}
```

#### New Logic (Single Order)
```php
// Collect all vendors involved
$vendors_involved = [];
foreach ($cart as $item) {
    $vid = intval($item['vendor_id'] ?? 0);
    if ($vid && !in_array($vid, $vendors_involved)) {
        $vendors_involved[] = $vid;
    }
}

// CREATE ONE ORDER with all items
$subtotal = 0.0;
foreach ($cart as $it) { ... } // Calculate total across all items

// Single delivery charge for entire order
$delivery_charges = $DELIVERY_CHARGE; // ₹50 (not per vendor)

// Single voucher discount applied to whole order
$discount = $voucher_applied ? $voucher_discount : 0.00;

// INSERT ONE ORDER
INSERT INTO tbl_orders 
(order_number, user_id, vendor_id, subtotal, tax, 
 delivery_charges, discount, total_amount, ...)

// INSERT ALL ITEMS FOR SAME ORDER_ID
foreach ($cart as $item) {
    INSERT INTO tbl_order_items 
    (order_id, product_id, product_name, quantity, unit_price, subtotal)
}

// NOTIFY EACH VENDOR individually
foreach ($vendors_involved as $vendor_id) {
    INSERT INTO tbl_notifications (vendor_id, order_id, ...)
}
```

#### Key Changes
✓ Single ORDER creation (not per vendor)
✓ All items added to `tbl_order_items` with same `order_id`
✓ Fixed ₹50 delivery charge for entire order
✓ Voucher discount applied to complete order
✓ Each vendor still notified separately
✓ `vendor_id` field = first vendor (or NULL if multi-vendor) for backward compatibility

---

### 2. File: `invoice.php` (Lines ~1-65)

#### Removed
- Batch-based order lookup logic
- Multi-order merging logic
- Complex batch_id matching

#### New Simplified Logic
```php
// Query single order directly
$order_res = mysqli_query($conn, 
    "SELECT * FROM tbl_orders WHERE order_id=$order_id AND user_id=$user_id LIMIT 1");

// Fetch all items for this order
$items_res = mysqli_query($conn, 
    "SELECT oi.*, p.vendor_id, v.shop_name 
     FROM tbl_order_items oi
     LEFT JOIN tbl_products p ON oi.product_id = p.product_id
     LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id
     WHERE oi.order_id=$order_id");
```

#### Benefits
✓ Simpler, more maintainable code
✓ Direct order lookup (no batch searching)
✓ All order items displayed under single order
✓ Correct totals (not accumulated from multiple orders)

---

### 3. File: `orders.php`
**NO CHANGES REQUIRED**
- Already queries unique orders from `tbl_orders`
- Session invoice display handles single orders correctly
- Feedback, cancellation, tracking all work with single order

---

## Database Structure
**No schema changes. Uses existing tables:**

### tbl_orders
```
order_id → PRIMARY KEY
order_number → UNIQUE
user_id → FK to tbl_users
vendor_id → First vendor (backward compat) / NULL if multi-vendor
subtotal → Total for ALL items
tax → 5% of subtotal
delivery_charges → FIXED ₹50 per order
discount → Voucher discount (if any)
total_amount → subtotal + tax + delivery - discount
payment_method → COD / Razorpay
...
```

### tbl_order_items (One-to-Many)
```
order_item_id → PRIMARY KEY
order_id → FK to tbl_orders (same for all items in order)
product_id → FK to tbl_products
product_name
quantity
unit_price
subtotal → unit_price * quantity
```

**Relationship:** `1 tbl_orders : Many tbl_order_items`

---

## Verification Checklist

### 1. Single Order Creation ✓
- [ ] Place order with 2+ products from different shops
- [ ] Check `tbl_orders` → Should have 1 new order
- [ ] Check `tbl_order_items` → Should have N items for same order_id

### 2. Delivery Charges ✓
- [ ] Subtotal (e.g., ₹200) + GST (₹10) + Delivery (₹50) = ₹260
- [ ] NOT: ₹50 × number_of_vendors

### 3. My Orders Page ✓
- [ ] Should show ONE entry for the checkout
- [ ] Order total = subtotal + tax + delivery (₹50 fixed)
- [ ] No duplicate order numbers

### 4. Invoice Page ✓
- [ ] Click "Bill" → Shows single invoice
- [ ] Lists ALL products (even from different shops)
- [ ] Correct totals displayed
- [ ] Delivery charge = ₹50 (not multiplied)

### 5. Voucher Application ✓
- [ ] First order with voucher → 15% discount on total
- [ ] Discount applied to: (subtotal + tax + delivery) * 0.15
- [ ] NOT distributed per vendor
- [ ] Marked as used after order creation

### 6. Shop Names Display ✓
- [ ] Checkout page → Shows all shops for each product ✓
- [ ] Invoice → Shows vendor names for each item ✓

### 7. Stock Management ✓
- [ ] Check `tbl_products.stock` decrements correctly
- [ ] Should decrement once per order (not per vendor)

### 8. Notifications ✓
- [ ] User notification → 1 notification for the order
- [ ] Each vendor notification → 1 notification about their items in order
- [ ] Order details show in notification

### 9. Payment Processing ✓
- [ ] COD: Cart cleared, invoice shown, redirects to orders.php
- [ ] Razorpay: Payment gateway called with total (₹50 delivery, not multiplied)
- [ ] Payment complete → All items marked 'paid'

### 10. Backward Compatibility ✓
- [ ] Single-vendor orders still work
- [ ] Existing orders in database not affected
- [ ] `batch_id` column ignored (kept for compatibility)
- [ ] `vendor_id` field populated correctly

---

## Testing SQL Queries

### Check orders created after fix:
```sql
-- Should show 1 order per checkout
SELECT o.order_id, o.order_number, u.email, 
       COUNT(oi.order_item_id) as item_count,
       o.total_amount
FROM tbl_orders o
JOIN tbl_users u ON o.user_id = u.user_id
LEFT JOIN tbl_order_items oi ON o.order_id = oi.order_id
WHERE o.created_at >= NOW() - INTERVAL 1 DAY
GROUP BY o.order_id
ORDER BY o.created_at DESC;
```

### Verify item distribution:
```sql
-- Each order should have clear item breakdown
SELECT o.order_id, o.order_number, 
       oi.product_name, oi.quantity, oi.unit_price,
       p.vendor_id, v.shop_name
FROM tbl_orders o
JOIN tbl_order_items oi ON o.order_id = oi.order_id
LEFT JOIN tbl_products p ON oi.product_id = p.product_id
LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id
WHERE o.order_id = ?
ORDER BY oi.order_item_id;
```

### Verify delivery charges:
```sql
-- Should be ₹50 per order, not per item or vendor
SELECT o.order_id, o.order_number, 
       COUNT(oi.order_item_id) as items,
       COUNT(DISTINCT p.vendor_id) as vendors,
       o.delivery_charges
FROM tbl_orders o
LEFT JOIN tbl_order_items oi ON o.order_id = oi.order_id
LEFT JOIN tbl_products p ON oi.product_id = p.product_id
WHERE o.created_at >= NOW() - INTERVAL 1 DAY
GROUP BY o.order_id
HAVING delivery_charges != 50;
-- Should return EMPTY → all orders have ₹50 delivery
```

---

## Important Notes

### Multi-Vendor Handling
- Products from different shops → Single order with all items
- Each vendor gets their own notification about items they need to fulfill
- Admin can see all vendors involved by checking `tbl_order_items` join with `tbl_products`

### Delivery Address
- Single delivery address for entire order
- Collected on checkout page
- Used for ALL items in order

### Payment
- Single payment for entire order (including all vendors' items)
- ₹50 delivery charge fixed per order
- Works with COD and Razorpay

### Performance
- One order INSERT instead of N vendor INSERTs
- Simpler JOIN queries (no batch matching)
- Faster invoice retrieval

---

## Rollback (if needed)
If issues arise, implement per-vendor orders again:
1. Restore checkout.php from git history
2. Restore invoice.php batch logic
3. Existing orders will continue to work (one order per vendor still supported)

---

## Files Modified
1. ✓ `user/checkout.php` - Create single order logic
2. ✓ `user/invoice.php` - Remove batch multit-order merging

## Files NOT Modified
- ✓ `user/orders.php` - Already compatible
- ✓ All database tables - No schema changes needed
- ✓ Cart, payment, notification logic - No changes needed

---

**Last Updated:** April 13, 2026  
**Status:** Implementation Complete - Ready for Testing
