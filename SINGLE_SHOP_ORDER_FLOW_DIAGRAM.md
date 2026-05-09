# Single Shop Order Feature - Flow Diagram

## Complete User Journey

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     USER SHOPPING JOURNEY                               │
└─────────────────────────────────────────────────────────────────────────┘

SCENARIO 1: SAME SHOP (Normal Flow)
═════════════════════════════════════════════════════════════════════════

  User Login
      │
      ├─→ Browse Shop A
      │       │
      │       ├─→ Add Product 1 to cart
      │       │   └─→ ✅ Added (first item, no lock yet)
      │       │
      │       ├─→ Add Product 2 to cart
      │       │   └─→ ✅ Added (same shop, lock created)
      │       │
      │       └─→ Go to Cart
      │           └─→ 🔒 Vendor Lock Banner: "Shop A"
      │
      ├─→ View Cart
      │   ├─→ See all items from Shop A
      │   ├─→ See vendor lock banner
      │   └─→ Click "Proceed to Checkout"
      │
      ├─→ Checkout
      │   ├─→ Validation: All from Shop A ✅
      │   ├─→ Validation: No conflicting orders ✅
      │   └─→ Create Order
      │
      └─→ ✅ ORDER PLACED (Shop A)
          │
          ├─→ Order Status: Confirmed
          └─→ Awaiting Delivery


SCENARIO 2: DIFFERENT SHOP (With Cart Items)
═════════════════════════════════════════════════════════════════════════

  User Login
      │
      ├─→ Browse Shop A
      │       │
      │       └─→ Add Product 1 to cart ✅
      │           └─→ Cart: [Shop A item]
      │
      ├─→ Navigate to Shop B
      │       │
      │       └─→ Try to add Product from Shop B
      │           │
      │           ├─→ add_to_cart.php validation:
      │           │   ✓ Cart has Shop A items
      │           │   ✓ Trying to add from Shop B
      │           │   ✗ BLOCKED
      │           │
      │           └─→ 🚫 ERROR MESSAGE
      │               "You have items from 'Shop A' in your cart.
      │                You can only buy from one shop per order."
      │
      ├─→ OPTIONS:
      │
      ├─────────────────────────────────────────────
      │ OPTION A: Continue with Shop A
      │ │
      │ └─→ Go to Cart → Checkout → Place Order ✅
      │
      └─────────────────────────────────────────────
        OPTION B: Switch to Shop B
        │
        └─→ Go to Cart → Click "Clear Cart" 🗑️
            ├─→ Confirm deletion
            └─→ ✅ Cart cleared
                │
                └─→ Browse Shop B again
                    └─→ Add Product from Shop B ✅
                        └─→ Checkout & Order ✅


SCENARIO 3: INCOMPLETE ORDER (Different Shop)
═════════════════════════════════════════════════════════════════════════

  GIVEN: User has incomplete order from Shop A
  ┌─────────────────────────────────────────┐
  │ tbl_orders                              │
  │ ├─ user_id: 5                           │
  │ ├─ vendor_id: 10 (Shop A)               │
  │ ├─ order_status: "confirmed" (NOT done) │
  │ └─ order_number: ORD123456789           │
  └─────────────────────────────────────────┘
      │
      ├─→ User tries to add Shop B product to NEW cart
      │       │
      │       ├─→ add_to_cart.php checks:
      │       │   1. getUserIncompleteOrders()
      │       │   2. Finds incomplete order from Shop A
      │       │   3. Comparing vendor IDs:
      │       │      - Order vendor: Shop A (10)
      │       │      - New product: Shop B (20)
      │       │      - CONFLICT! ✗
      │       │
      │       └─→ 🚫 ERROR MESSAGE
      │           "You have an incomplete order from 'Shop A'
      │            (Order: ORD123456789123).
      │            Please complete that order before placing
      │            orders from other shops."
      │
      └─→ BLOCKED: Must wait for Shop A order completion
          │
          └─→ WAITING OPTIONS:
              │
              ├─→ A) Wait for delivery
              │       └─→ Order status: "delivered"
              │           └─→ Lock removed ✅
              │               └─→ Can shop from Shop B ✅
              │
              └─→ B) Cancel order (in My Orders)
                  └─→ Order status: "cancelled"
                      └─→ Lock removed ✅
                          └─→ Can shop from Shop B ✅


SCENARIO 4: CHECKOUT VALIDATION
═════════════════════════════════════════════════════════════════════════

  User clicks "Proceed to Checkout"
      │
      ├─→ checkout.php validation checks:
      │
      ├─→ CHECK 1: Cart not empty?
      │   ├─→ ✅ Yes → Continue
      │   └─→ ❌ No → Error: "Cart is empty"
      │
      ├─→ CHECK 2: All items have vendor_id?
      │   ├─→ ✅ Yes → Continue
      │   └─→ ❌ No → Error: "Invalid vendor information"
      │
      ├─→ CHECK 3: All items from SAME vendor?
      │   ├─→ ✅ Yes → Continue
      │   └─→ ❌ No → Error: "Items from multiple shops"
      │
      ├─→ CHECK 4: Incomplete orders from OTHER vendors?
      │   ├─→ ✅ None → Continue to checkout ✅
      │   └─→ ❌ Yes → Error: "Complete previous order first"
      │
      └─→ ✅ ALL CHECKS PASS → ORDER CREATED


SUMMARY: VENDOR LOCK STATE MACHINE
═════════════════════════════════════════════════════════════════════════

                            [BROWSING]
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
                Add from  Add from  Add from
                Shop A    Shop B    Shop C
                    │            │            │
                    ↓            ↓            ↓
              [LOCKED TO   ❌ERROR    ❌ERROR
               SHOP A]     (Can't mix  (Can't mix
                 │         vendors)   vendors)
                 │
            Add more from ──→ ✅ ADD (same vendor)
            Shop A
                 │
                 └──→ [CHECKOUT]
                      │
                  Place Order
                      │
                      ↓
                  [ORDER PLACED]
                  (Status: Confirmed)
                      │
         ┌────────────┴────────────┐
         │                         │
    Waiting for delivery      Order cancelled
         │                         │
         └────────────┬────────────┘
                      │
                      ↓
              [LOCK RELEASED]
                      │
              Can shop from
              other vendors
                      │
              ┌───────┴───────┐
              │               │
           Shop B          Shop C
              │               │
              ↓               ↓
           ✅ ADD          ✅ ADD


SYSTEM DATABASE CHECKS
═════════════════════════════════════════════════════════════════════════

When user tries to ADD PRODUCT:
   └─→ SELECT FROM tbl_orders
       ├─ WHERE user_id = [user_id]
       ├─ AND vendor_id ≠ [current_vendor]
       ├─ AND status NOT IN ('delivered', 'cancelled')
       └─ → If found: BLOCK with error message

When user tries to CHECKOUT:
   └─→ VALIDATE cart items
       ├─ All from same vendor? → Proceed / Block
       └─→ SELECT FROM tbl_orders
           ├─ WHERE user_id = [user_id]
           ├─ AND vendor_id ≠ [cart_vendor]
           ├─ AND status NOT IN ('delivered', 'cancelled')
           └─ → If found: BLOCK with error message


CLEAR CART WORKFLOW
═════════════════════════════════════════════════════════════════════════

  Click "Clear Cart" button
      │
      └─→ clear_cart.php
          │
          ├─→ Show confirmation dialog
          │   "Are you sure you want to clear your cart?"
          │
          ├─→ User confirms
          │   │
          │   └─→ unset($_SESSION['cart'])
          │       $_SESSION['cart'] = []
          │       │
          │       └─→ Show success: "Cart cleared!"
          │           Redirect to index.php
          │
          └─→ User cancels
              └─→ Stay on cart page


═════════════════════════════════════════════════════════════════════════
```

## File Interaction Diagram

```
┌────────────────────────────────────────────────────────────────────┐
│                         USER INTERFACE                             │
├────────────────────────────────────────────────────────────────────┤
│  index.php  → all_products.php  → viewProduct.php  → header.php   │
│      │                 │               │                 │        │
│      └─────────────────┴───────────────┴─────────────────┘        │
│                        │                                           │
│                  Add to Cart button                                │
│                        │                                           │
│                        ↓                                           │
├────────────────────────────────────────────────────────────────────┤
│                   add_to_cart.php (MODIFIED)                      │
│  ┌──────────────────────────────────────────────────────────────┐ │
│  │ 1. Include vendor_lock_helper.php                            │ │
│  │ 2. Get product & vendor info from POST                       │ │
│  │ 3. Call canAddProductFromVendor()                            │ │
│  │ 4. If allowed → Add to $_SESSION['cart']                    │ │
│  │ 5. If blocked → Set error & redirect to referer             │ │
│  └──────────────────────────────────────────────────────────────┘ │
│                        │                                           │
└────────────────────────────────────────────────────────────────────┘
                         │
                    SESSION['cart']
                         │
         ┌───────────────┼───────────────┐
         │               │               │
         ↓               ↓               ↓
   ┌─────────────┐ ┌─────────────┐ ┌──────────────┐
   │  cart.php   │ │checkout.php │ │ clear_cart  │
   │ (MODIFIED)  │ │ (MODIFIED)  │ │    .php     │
   └─────────────┘ └─────────────┘ └──────────────┘
         │               │               │
         ├──────────────┬┴───────────────┘
         │              │
         ↓              ↓
   ┌────────────────────────────┐
   │ vendor_lock_helper.php     │
   │ ┌─────────────────────────┐│
   │ │ Core Functions:         ││
   │ │ - canAdd...()           ││
   │ │ - getIncomplete...()    ││
   │ │ - getLockedVendor...()  ││
   │ │ - getUserIncomplete...()││
   │ └─────────────────────────┘│
   └────────────────────────────┘
              │
              ↓
   ┌────────────────────────────┐
   │    Database (MySQL)        │
   │ ┌─────────────────────────┐│
   │ │ tbl_orders              ││
   │ │ tbl_vendors             ││
   │ │ tbl_products            ││
   │ │ tbl_order_items         ││
   │ └─────────────────────────┘│
   └────────────────────────────┘
```

---

**Visual Guide Created:** April 15, 2026
