# Order System Architecture Diagram

## Before Fix (Per-Vendor Orders) ❌

```
CUSTOMER CHECKOUT
┌─────────────────────────────────────────────────────────┐
│ Cart Items:                                              │
│ - Chocolate Cake (Shop A) × 2 @ ₹100 = ₹200            │
│ - Vanilla Pastry (Shop B) × 2 @ ₹50 = ₹100             │
│ - Fruit Tart (Shop C) × 1 @ ₹75 = ₹75                  │
│                                                          │
│ Total Subtotal: ₹375, GST: ₹18.75                       │
└─────────────────────────────────────────────────────────┘
                          ↓
CHECKOUT PROCESSING (OLD LOGIC)
┌─────────────────────────────────────────────────────────┐
│ Group by Vendor:                                         │
│ ├─ Shop A: [Item 1]                                      │
│ ├─ Shop B: [Item 2]                                      │
│ └─ Shop C: [Item 3]                                      │
│                                                          │
│ For each vendor group:                                   │
│ ├─ CREATE ORDER 1 (Shop A)                              │
│ │  ├─ INSERT INTO tbl_orders (subtotal=200, tax=10)     │
│ │  ├─ delivery_charges = ₹50 ← CHARGED HERE             │
│ │  └─ total = 260                                        │
│ │                                                        │
│ ├─ CREATE ORDER 2 (Shop B)                              │
│ │  ├─ INSERT INTO tbl_orders (subtotal=100, tax=5)      │
│ │  ├─ delivery_charges = ₹50 ← CHARGED HERE AGAIN!      │
│ │  └─ total = 155                                        │
│ │                                                        │
│ └─ CREATE ORDER 3 (Shop C)                              │
│    ├─ INSERT INTO tbl_orders (subtotal=75, tax=4)       │
│    ├─ delivery_charges = ₹50 ← CHARGED 3RD TIME!        │
│    └─ total = 129                                        │
│                                                          │
│ ❌ RESULTS:                                              │
│    Total Delivery Charges = ₹150 (Should be ₹50!)       │
│    Grand Total = ₹544 (Should be ₹443.75!)              │
└─────────────────────────────────────────────────────────┘
                          ↓
DATABASE (OLD)
┌────────────────────────────────────────────────────────────┐
│ tbl_orders:                                                │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ order_id=1 | user=5 | vendor=1 | total=260        │   │
│ ├─────────────────────────────────────────────────────┤   │
│ │ order_id=2 | user=5 | vendor=2 | total=155        │   │
│ ├─────────────────────────────────────────────────────┤   │
│ │ order_id=3 | user=5 | vendor=3 | total=129        │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                            │
│ tbl_order_items:                                          │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ order_item_id=1 | order_id=1 | product_id=10     │   │
│ ├─────────────────────────────────────────────────────┤   │
│ │ order_item_id=2 | order_id=2 | product_id=20     │   │
│ ├─────────────────────────────────────────────────────┤   │
│ │ order_item_id=3 | order_id=3 | product_id=30     │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                            │
│ ❌ PROBLEM: 3 separate orders for 1 checkout!             │
└────────────────────────────────────────────────────────────┘
                          ↓
MY ORDERS PAGE (OLD)
┌────────────────────────────────────────────────────────────┐
│ My Orders                                                  │
│ ┌────────────────────────────────────────────┐            │
│ │ Order #ORD123456 | Total: ₹260    | View  │            │
│ ├────────────────────────────────────────────┤            │
│ │ Order #ORD223456 | Total: ₹155    | View  │            │
│ ├────────────────────────────────────────────┤            │
│ │ Order #ORD323456 | Total: ₹129    | View  │            │
│ └────────────────────────────────────────────┘            │
│ ❌ 3 orders shown for 1 checkout!                          │
│ ❌ Confusing for customer                                  │
│ ❌ ₹150 delivery charged (should be ₹50)                   │
└────────────────────────────────────────────────────────────┘
```

---

## After Fix (Single Order with Items) ✅

```
CUSTOMER CHECKOUT
┌─────────────────────────────────────────────────────────┐
│ Cart Items:                                              │
│ - Chocolate Cake (Shop A) × 2 @ ₹100 = ₹200            │
│ - Vanilla Pastry (Shop B) × 2 @ ₹50 = ₹100             │
│ - Fruit Tart (Shop C) × 1 @ ₹75 = ₹75                  │
│                                                          │
│ Total Subtotal: ₹375, GST: ₹18.75                       │
└─────────────────────────────────────────────────────────┘
                          ↓
CHECKOUT PROCESSING (NEW LOGIC)
┌─────────────────────────────────────────────────────────┐
│ Collect vendors_involved = [1, 2, 3]                    │
│                                                          │
│ Calculate SINGLE ORDER TOTALS:                          │
│ ├─ Subtotal = ₹375 (200 + 100 + 75)                    │
│ ├─ Tax = 5% × 375 = ₹18.75                             │
│ ├─ Delivery = ₹50 ← ONLY CHARGED ONCE! ✓              │
│ └─ Total = 375 + 18.75 + 50 = ₹443.75                  │
│                                                          │
│ CREATE ONE ORDER:                                        │
│ └─ INSERT INTO tbl_orders                               │
│    ├─ order_id = 123                                    │
│    ├─ user_id = 5                                       │
│    ├─ vendor_id = 1 (first vendor)                      │
│    ├─ subtotal = 375                                    │
│    ├─ tax = 18.75                                       │
│    ├─ delivery_charges = 50 ← CORRECT!                 │
│    └─ total_amount = 443.75                             │
│                                                          │
│ ADD ALL ITEMS TO THIS ORDER:                            │
│ ├─ INSERT tbl_order_items (order_id=123, product=10)   │
│ ├─ INSERT tbl_order_items (order_id=123, product=20)   │
│ └─ INSERT tbl_order_items (order_id=123, product=30)   │
│                                                          │
│ NOTIFY INVOLVED PARTIES:                                │
│ ├─ User Notification: "Order #ORD123 confirmed"        │
│ ├─ Shop A Notification: "New order has item"           │
│ ├─ Shop B Notification: "New order has item"           │
│ └─ Shop C Notification: "New order has item"           │
│                                                          │
│ ✅ RESULTS:                                              │
│    1 Order Created                                       │
│    ₹50 Delivery (correct!)                              │
│    ₹443.75 Total (correct!)                             │
└─────────────────────────────────────────────────────────┘
                          ↓
DATABASE (NEW)
┌────────────────────────────────────────────────────────────┐
│ tbl_orders:                                                │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ order_id=123 | user=5 | vendor=1 | total=443.75  │   │
│ └─────────────────────────────────────────────────────┘   │
│ ✅ ONLY 1 ORDER!                                           │
│                                                            │
│ tbl_order_items:                                          │
│ ┌──────────────────────────────────────────────────────┐  │
│ │ order_item_id=1 | order_id=123 | product_id=10     │  │
│ ├──────────────────────────────────────────────────────┤  │
│ │ order_item_id=2 | order_id=123 | product_id=20     │  │
│ ├──────────────────────────────────────────────────────┤  │
│ │ order_item_id=3 | order_id=123 | product_id=30     │  │
│ └──────────────────────────────────────────────────────┘  │
│ ✅ ALL ITEMS linked to SAME order_id=123!                 │
│                                                            │
│ Database Relationship:                                    │
│        tbl_orders (1)                                     │
│            │                                              │
│            │                                              │
│       ┌────┴────┬────────────┬─────────┐                 │
│       ▼         ▼            ▼         ▼                  │
│     Item1    Item2        Item3     (all have            │
│   order_id  order_id     order_id   order_id=123)        │
└────────────────────────────────────────────────────────────┘
                          ↓
MY ORDERS PAGE (NEW) ✅
┌────────────────────────────────────────────────────────────┐
│ My Orders                                                  │
│ ┌────────────────────────────────────────────┐            │
│ │ Order #ORD123                              │            │
│ │ Total: ₹443.75                             │            │
│ │ Items: Chocolate Cake, Vanilla Pastry,     │            │
│ │        Fruit Tart (from 3 different shops) │            │
│ │ Status: Confirmed                          │            │
│ │ [View Bill] [Cancel] [Track]               │            │
│ └────────────────────────────────────────────┘            │
│ ✅ 1 order shown                                           │
│ ✅ All items listed under single order                    │
│ ✅ Correct ₹443.75 total                                  │
│ ✅ ₹50 delivery charge (not multiplied)                   │
└────────────────────────────────────────────────────────────┘
                          ↓
INVOICE PAGE (NEW) ✅
┌────────────────────────────────────────────────────────────┐
│ Invoice - Order #ORD123                                    │
│                                                            │
│ Items from All Shops:                                     │
│ ┌────────────────────────────────────────────┐            │
│ │ Chocolate Cake (Shop A) × 2      ₹200     │            │
│ │ Vanilla Pastry (Shop B) × 2      ₹100     │            │
│ │ Fruit Tart (Shop C) × 1          ₹75      │            │
│ └────────────────────────────────────────────┘            │
│                                                            │
│ Summary:                                                  │
│ Subtotal                           ₹375                  │
│ GST (5%)                           ₹18.75                │
│ Delivery (Fixed)                   ₹50                   │
│ ─────────────────────────────────────────                │
│ TOTAL                              ₹443.75               │
│                                                            │
│ ✅ ALL items on ONE invoice                               │
│ ✅ Correct totals                                         │
└────────────────────────────────────────────────────────────┘
```

---

## Key Differences

| Aspect | Before (❌) | After (✅) |
|--------|-------------|-----------|
| **Orders Created** | 3 (one per vendor) | 1 (for all items) |
| **Delivery Charge** | ₹150 (₹50×3) | ₹50 (fixed) |
| **Items Linked** | Scattered across 3 orders | All in 1 order |
| **My Orders Entries** | 3 separate entries | 1 combined entry |
| **Invoice Pages** | 3 invoices to merge | 1 clear invoice |
| **Customer Confusion** | High (multiple orders) | Low (single order) |
| **Order Totals** | ₹260 + ₹155 + ₹129 = ₹544 ❌ | ₹443.75 ✓ |
| **Database Normalization** | Poor | Proper (1:N) |
| **Query Complexity** | High (batch merging) | Simple (direct lookup) |
| **Performance** | Slower | Faster |
| **Vendor Notifications** | Automatic | Automatic |

---

## Data Flow Comparison

### Before (Complex ❌)
```
Checkout
  ↓
[Group by vendor]
  ↓
[For each vendor: Create order]
  ↓
[Multiple orders in database]
  ↓
[My Orders: Display 3 entries]
  ↓
[Invoice: Fetch 3 orders, merge items, recalculate totals]
  ↓
[Customer sees 3 orders with ₹150 delivery]
```

### After (Simple ✓)
```
Checkout
  ↓
[Calculate total for ALL items]
  ↓
[Create ONE order with ₹50 delivery]
  ↓
[Insert ALL items to order_items table]
  ↓
[Send notifications (user + vendors)]
  ↓
[My Orders: Display 1 entry]
  ↓
[Invoice: Simple lookup, show all items]
  ↓
[Customer sees 1 order with ₹50 delivery]
```

---

## Stock Deduction Flow

### Before (Multiple Decrements)
```
3 separate orders → 3 stock update queries → May cause issues
```

### After (Single Deduction per Item)
```
1 order → For each item: 1 stock update query → Clean & reliable
```

---

## Summary

**The fix transforms the system from:**
- ❌ Multiple orders per checkout
- ❌ Multiplied delivery charges
- ❌ Confusing invoice consolidation

**To:**
- ✅ Single order per checkout
- ✅ Fixed ₹50 delivery charge
- ✅ Simple, unified invoice

This diagram shows how the data flows through the system and how much simpler and cleaner the new approach is!
