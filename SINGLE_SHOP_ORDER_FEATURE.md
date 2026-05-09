# Single Shop Order Feature - One Shop at a Time

## Overview

This feature ensures that users can only buy products from **ONE SHOP** at a time. If users want to buy from different shops, they must complete their order from the first shop before placing a new order from another shop.

## Key Features

### ✅ What This Does

1. **Prevents Cart Mixing**: Users cannot add products from different shops to the same cart
2. **Incomplete Order Lock**: If a user has an incomplete order from Shop A, they cannot add products from Shop B to their cart
3. **Smart Validation**: Works at both cart and checkout stages
4. **User-Friendly Messaging**: Clear error messages explaining the restriction
5. **Cart Management**: Users can clear their cart to switch shops

### 🎯 User Experience Flow

#### Scenario 1: Single Shop Purchase (Normal)
```
User browsing Shop A
  ↓
Add products from Shop A to cart ✓ (Allowed)
  ↓
Checkout and place order ✓ (Allowed)
  ↓
Order from Shop A completed
```

#### Scenario 2: Multi-Shop Purchase (Restricted)
```
User browsing Shop A
  ↓
Add products from Shop A to cart ✓ (Allowed)
  ↓
User wants to add from Shop B
  ✗ ERROR: "You have items from Shop A in your cart"
  
Options:
A) Proceed with Shop A (recommended)
   └→ Checkout → Complete order from Shop A
   └→ Now can shop from other shops

B) Switch shops
   └→ Clear Cart button
   └→ Cart cleared
   └→ Now can add products from Shop B
```

#### Scenario 3: Incomplete Order from Different Shop
```
User placed order from Shop A but not yet delivered
  ↓
User wants to add products from Shop B to cart
  ✗ ERROR: "You have an incomplete order from Shop A (Order #ORD...)"
  
Must wait until:
- Order is delivered, OR
- Order is cancelled
  ↓
Then can shop from Shop B
```

## Technical Implementation

### Files Modified

1. **vendor_lock_helper.php** (NEW)
   - Reusable helper functions for vendor checking
   - Functions:
     - `getIncompleteOrderFromVendor()` - Check incomplete orders from specific vendor
     - `getUserIncompleteOrders()` - Get all incomplete orders
     - `getLockedVendorId()` - Get locked vendor from orders or cart
     - `canAddProductFromVendor()` - Main validation function
     - `getVendorLockMessage()` - Generate display messages

2. **add_to_cart.php** (MODIFIED)
   - Added vendor lock validation
   - Checks if product can be added from this vendor
   - Prevents cross-vendor cart items
   - Returns user-friendly error if not allowed

3. **cart.php** (MODIFIED)
   - Shows vendor lock information banner
   - Displays which shop the cart is locked to
   - Added "Clear Cart" button for shop switching
   - Displays clear warning about single-shop policy

4. **checkout.php** (MODIFIED)
   - Validates all cart items are from same vendor
   - Prevents checkout if mixing vendors
   - Prevents checkout if incomplete orders from other vendors exist
   - Enhanced security validation

5. **clear_cart.php** (NEW)
   - Simple utility to clear user's cart
   - Allows users to switch shops
   - Requires confirmation from user
   - Shows success message after clearing

## Database Queries

The implementation uses these database checks:

```sql
-- Get incomplete orders for a specific vendor
SELECT * FROM tbl_orders 
WHERE user_id = ? AND vendor_id = ? 
AND order_status NOT IN ('delivered', 'cancelled')

-- Get all incomplete orders for user
SELECT * FROM tbl_orders 
WHERE user_id = ? 
AND order_status NOT IN ('delivered', 'cancelled')

-- Get vendor info for display
SELECT shop_name FROM tbl_vendors WHERE vendor_id = ?
```

## Error Messages Shown to Users

### 1. Product has vendor lock from incomplete order
```
"You have an incomplete order from 'Shop Name' (Order: ORD123456789123).
Please complete that order before placing orders from other shops."
```

### 2. Product is from different vendor than cart
```
"You have items from 'Shop Name' in your cart. You can only buy from 
one shop per order. Please checkout first or clear your cart to switch shops."
```

### 3. Cart has items from multiple vendors (checkout validation)
```
"Your cart contains items from multiple shops. Please ensure all items 
are from the same shop. Clear your cart and start fresh."
```

### 4. Incomplete order from different vendor at checkout
```
"You have an incomplete order from 'Shop Name'. Please complete that 
order before placing orders from other shops."
```

## Order Status Considerations

### Incomplete Order Statuses
Orders are considered **INCOMPLETE** if status is NOT:
- `delivered` - Order completed and received
- `cancelled` - Order was cancelled

### Incomplete Statuses Include:
- `pending` - Order just placed
- `confirmed` - Vendor confirmed the order
- `preparing` - Vendor preparing items
- `dispatched` - Order sent to delivery
- `out_for_delivery` - Rider delivering order
- Any other non-terminal status

## UI/UX Elements

### Cart Page Banner
When user has items from a shop in cart:
```
🔒 Single Shop Order
You're currently adding items from 'Shop Name'.
You can only buy from one shop per order. 
Complete this order before shopping from other shops.
```

### Buttons
- **Proceed to Checkout** - Normal checkout (if only one vendor)
- **Clear Cart** - Clear cart to switch shops (with confirmation)
- **Login to Checkout** - Required if not logged in

## Testing the Feature

### Test Case 1: Basic Single Shop
1. Login as user
2. Add products from Shop A ✓
3. Go to cart - see Shop A lock banner
4. Proceed to checkout ✓

### Test Case 2: Prevent Cross-Shop Addition
1. Add 2 products from Shop A to cart
2. Navigate to Shop B products
3. Try to add product from Shop B
4. See error message ✓
5. Must clear cart to add from Shop B

### Test Case 3: Incomplete Order Block
1. Complete order from Shop A (but not delivered yet)
2. Try to add products from Shop B to new cart
3. See incomplete order error message ✓
4. Cannot add until Shop A order is delivered/cancelled

### Test Case 4: Cart Clearing
1. Have items from Shop A in cart
2. Click "Clear Cart" button
3. Confirm clearing in dialog
4. Cart becomes empty
5. Can now add from Shop B ✓

## Rollback/Disable

To disable this feature:
1. Remove `include 'vendor_lock_helper.php';` from:
   - add_to_cart.php
   - cart.php
   - checkout.php
2. Remove the vendor check blocks from add_to_cart.php and checkout.php
3. Remove clear_cart.php if not needed

## Benefits

✅ **For Business:**
- Ensures orders are from single vendor (simpler logistics)
- Clearer order tracking and fulfillment
- Better vendor performance metrics

✅ **For Users:**
- Clear, simple ordering rules
- Prevents accidental multi-vendor orders
- Easy to understand error messages
- Option to clear cart and switch shops

✅ **For System:**
- Fewer complex multi-vendor orders
- Easier order management and routing
- Better analytics per shop
- Reduced support issues

## Future Enhancements

Possible improvements to this feature:

1. **Allow Multi-Vendor Orders** - Add admin setting to allow multi-vendor orders
2. **Group Orders** - Let users create multiple orders for different shops simultaneously
3. **Smart Cart** - Automatically split cart into multiple orders per vendor
4. **Auto-Retry** - Notify user when previous order delivered, offer to switch shops
5. **Shop Queue** - Show users a queue of shops they want to order from

## Related Files

- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) - See tbl_orders table for status values
- [ORDER_SYSTEM_ARCHITECTURE_DIAGRAM.md](ORDER_SYSTEM_ARCHITECTURE_DIAGRAM.md) - Order flow overview
- [ADMIN_QUICK_REFERENCE.md](ADMIN_QUICK_REFERENCE.md) - Admin order management

---

**Implementation Date:** April 15, 2026  
**Status:** ✅ Production Ready
