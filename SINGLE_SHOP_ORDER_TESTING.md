# Single Shop Order Feature - Testing Guide

## Quick Test Checklist

### ✅ Test 1: Normal Single Shop Purchase
**Goal:** User can add and purchase from one shop

Steps:
1. Login to user account
2. Navigate to any shop (e.g., Cake Shop)
3. Add 2-3 different products to cart ✓
4. Go to Cart page - should show vendor lock banner with shop name ✓
5. Proceed to checkout ✓
6. Place order ✓

**Expected Result:** Order placed successfully with all items from same shop

---

### ✅ Test 2: Prevent Cross-Shop Addition
**Goal:** User cannot add items from different shops to cart

Steps:
1. Add product from Shop A to cart
2. Go to Shop B products page
3. Try to add product from Shop B
4. Check cart error message

**Expected Result:** 
- Error appears: "You have items from 'Shop A' in your cart..."
- Product NOT added to cart
- Cart still contains only Shop A items

---

### ✅ Test 3: Clear Cart to Switch Shops
**Goal:** User can clear cart to switch shops

Steps:
1. Add products from Shop A to cart
2. Go to Cart page
3. Click "Clear Cart" button
4. Confirm in popup dialog
5. Try to add from Shop B

**Expected Result:**
- Cart becomes empty with success message ✓
- Can now add from Shop B ✓

---

### ✅ Test 4: Incomplete Order Block
**Goal:** User cannot shop from different shop if previous order incomplete

Steps:
1. Login as user
2. Place order from Shop A (COD or online payment)
3. **Do NOT mark it as delivered in admin**
4. Try to add product from Shop B to new cart
5. Go to add_to_cart.php or try "Add to Cart"

**Expected Result:**
- Error message appears: "You have an incomplete order from 'Shop A' (Order: ORD...)"
- Cannot add Shop B items
- Must wait for Shop A order to be delivered or cancelled

---

### ✅ Test 5: After Order Completion - Can Shop from Different Shop
**Goal:** After previous order completes, user can shop from other shops

Steps:
1. Have incomplete Shop A order (from Test 4)
2. Admin marks order as "delivered"
3. User tries to add Shop B product to new cart
4. Add to cart should succeed ✓

**Expected Result:**
- Shop B product added successfully
- Can proceed with new shop purchase

---

### ✅ Test 6: Cart Banner Display
**Goal:** Vendor lock banner shows correct information

Steps:
1. Add products from "Honey Baker" shop
2. Go to cart page
3. Look at vendor lock banner

**Expected Result:**
- Banner shows: "🔒 Single Shop Order"
- Displays shop name: "Honey Baker"
- Explains policy clearly
- Clear Cart button visible

---

### ✅ Test 7: Checkout Validation
**Goal:** System prevents checkout if issues detected

Steps:
1. Manually add items from multiple vendors to session (for edge case)
2. Access checkout.php directly
3. Observe validation behavior

**Expected Result:**
- Error message about multiple vendors
- Redirected to cart.php
- Clear error explaining issue

---

## Test Database Queries

Check database state during testing:

```sql
-- Check user's incomplete orders
SELECT order_id, order_number, vendor_id, order_status 
FROM tbl_orders 
WHERE user_id = [USER_ID] 
AND order_status NOT IN ('delivered', 'cancelled');

-- Check specific vendor's shop name
SELECT shop_name FROM tbl_vendors WHERE vendor_id = [VENDOR_ID];

-- Check order items for verification
SELECT * FROM tbl_order_items WHERE order_id = [ORDER_ID];
```

---

## Common Test Users/Scenarios

### User A: New Customer
- No prior orders
- Can freely choose any shop
- Restrictions apply after first order

### User B: Returning Customer
- Has prior orders (some may be incomplete)
- Subject to vendor lock
- Test incomplete order blocking

### User C: Cart with items
- Already has items in cart from Shop X
- Try to add from Shop Y
- Should see error

---

## Error Messages to Verify

During testing, you should see these exact error messages at appropriate times:

1. **Adding from wrong vendor:**
   ```
   "You have items from 'Shop Name' in your cart. You can only buy from 
   one shop per order. Please checkout first or clear your cart to switch shops."
   ```

2. **Incomplete order from different vendor:**
   ```
   "You have an incomplete order from 'Shop Name' (Order: ORD123456789123). 
   Please complete that order before placing orders from other shops."
   ```

3. **Checkout validation - multiple vendors:**
   ```
   "Your cart contains items from multiple shops. Please ensure all items 
   are from the same shop. Clear your cart and start fresh."
   ```

---

## Success Indicators

✅ Feature working if:
- Users can add multiple items from ONE shop
- Cannot add items from DIFFERENT shops
- Cannot checkout if incomplete orders from other vendors exist
- Can clear cart to switch shops
- Error messages are clear and helpful
- Vendor lock banner displays in cart
- After order completes, can shop from other vendors

❌ Feature NOT working if:
- User can mix items from multiple shops
- No error messages appear when mixing shops
- Cart allows items from different vendors
- User can checkout with mixed vendor items

---

## Performance Considerations

The feature uses these queries (all have proper indexes):
- tbl_orders.user_id + order_status (indexed)
- tbl_vendors.vendor_id (PRIMARY KEY)
- tbl_products.vendor_id (should have index)

If performance issues occur, check:
- Database has appropriate indexes
- No missing database columns
- Connection parameters are optimal

---

## Notes for Testing

- Clear browser cache between tests if needed
- Test with multiple browsers/incognito windows
- Test on mobile devices for UI/UX
- Verify database state after each test
- Test edge cases (null values, deleted vendors, etc.)

**Last Updated:** April 15, 2026
