# System Architecture Diagram

## Complete Order & Notification System Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        CUSTOMER CHECKOUT FLOW                               │
└─────────────────────────────────────────────────────────────────────────────┘

    CUSTOMER
       │
       ├────────────────────────────────────────┐
       │                                        │
    ┌──▼────────────────────────┐    ┌────────▼─────────┐
    │  CART                      │    │  CHECKOUT FORM   │
    │  - Product selection       │    │  - Address       │
    │  - Quantity               │    │  - City/PIN      │
    │  - Vendor grouping        │    │  - Phone         │
    └──┬────────────────────────┘    └────────┬─────────┘
       │                                        │
       └────────────┬──────────────────────────┘
                    │
         ┌──────────▼──────────┐
         │  PAYMENT METHOD     │
         │  SELECTION          │
         └──┬────────────────┬─┘
            │                │
      ┌─────▼───┐      ┌────▼──────┐
      │   COD   │      │   ONLINE   │
      │         │      │  PAYMENT   │
      └─────┬───┘      └────┬──────┘
            │                │
            │                ▼
            │        ┌──────────────────┐
            │        │ PAYMENT GATEWAY  │
            │        │ (Razorpay, etc) │
            │        └────┬──────┬─────┘
            │             │      │
            │          ┌──▼──┐┌─▼───┐
            │          │SUCC │FAIL  │
            │          └──┬──┘└─┬───┘
            │             │     │
            │             ▼     ▼
            │        ┌────────────────┐
            │        │payment_callback│
            │        │.php (HTTP POST)│
            │        └────┬────────┬──┘
            │             │        │
            │        ┌────▼─┐ ┌───▼────┐
            │        │ Paid │ │ Failed │
            │        └────┬─┘ └───┬────┘
            ▼             ▼        ▼
    ┌───────────────────────────────────┐
    │   DATABASE: CREATE ORDER          │
    │   ├─ tbl_orders                   │
    │   │  ├─ order_id                  │
    │   │  ├─ order_number (ORD...)    │
    │   │  ├─ user_id                   │
    │   │  ├─ vendor_id                 │
    │   │  ├─ total_amount              │
    │   │  ├─ ORDER_STATUS:             │
    │   │  │  ├─ COD → 'Confirmed'      │
    │   │  │  ├─ Online → 'Pending'     │
    │   │  │  └─ After Callback → 'Confirmed' (success)
    │   │  └─ PAYMENT_STATUS:           │
    │   │     ├─ 'pending' (COD, Online fail)
    │   │     └─ 'Paid' (Online success) │
    │   │                               │
    │   ├─ tbl_order_items              │
    │   │  ├─ order_id (FK)             │
    │   │  ├─ product_id, name          │
    │   │  ├─ quantity                  │
    │   │  └─ unit_price, subtotal      │
    │   │                               │
    │   └─ tbl_notifications            │
    │      ├─ user_id (FK)              │
    │      ├─ order_id (FK)             │
    │      ├─ title                     │
    │      ├─ message                   │
    │      └─ status: 'unread'/'read'   │
    └───────┬───────────────────────────┘
            │
    ┌───────▼──────────────────────────┐
    │  CUSTOMER NOTIFICATIONS           │
    │  ┌────────────────────────────┐   │
    │  │ Order Confirmed (COD)      │   │
    │  │ OR                         │   │
    │  │ Payment Received (Online)  │   │
    │  │ OR                         │   │
    │  │ Payment Failed (Retry)     │   │
    │  └────────────────────────────┘   │
    └───────┬──────────────────────────┘
            │
    ┌───────▼──────────────────────────┐
    │  CUSTOMER UI                      │
    │  ├─ header.php                    │
    │  │  ├─ Notification bell icon    │
    │  │  ├─ Unread count badge        │
    │  │  └─ Dropdown list              │
    │  │     ├─ 30s auto-refresh        │
    │  │     └─ Click to mark read      │
    │  │                                │
    │  └─ profile.php                   │
    │     ├─ All notifications          │
    │     ├─ Bold = unread              │
    │     ├─ Link to order              │
    │     └─ Click to read              │
    └────────────────────────────────────┘

└─────────────────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────────────┐
│                        ADMIN STATUS UPDATE FLOW                              │
└─────────────────────────────────────────────────────────────────────────────┘

    ADMIN/VENDOR
       │
    ┌──▼───────────────────────┐
    │  ADMIN PANEL              │
    │  ├─ Orders List           │
    │  └─ Status Dropdown       │
    │     ├─ Pending            │
    │     ├─ Confirmed ←────────┼─ UPDATE BUTTON
    │     ├─ Dispatched         │
    │     ├─ Completed          │
    │     └─ Cancelled          │
    └──┬─────────────────────────┘
       │
       │ (AJAX POST)
       │  action=update_status
       │  order_id=123
       │  new_status=Dispatched
       │
    ┌──▼────────────────────────────────┐
    │ /admin/update_order_status.php    │
    │ ├─ Validate admin/vendor          │
    │ ├─ Check permissions              │
    │ ├─ Validate status whitelist      │
    │ └─ Update tbl_orders              │
    └──┬────────────────────────────────┘
       │
       ├─ Update order_status column     
       │
       ├─ If → Confirmed:
       │  └─ Also set payment_status='Paid'
       │
       └─ Create notification
          │
    ┌─────▼───────────────────────────┐
    │ tbl_notifications INSERT        │
    │ ├─ user_id: customer's ID       │
    │ ├─ order_id: 123                │
    │ ├─ title: matches status        │
    │ ├─ message: "Order dispatched"  │
    │ └─ status: 'unread'             │
    └─────┬──────────────────────────┘
          │
    ┌─────▼──────────────────────┐
    │ CUSTOMER SEES:              │
    │ ├─ Badge +1 (new notif)     │
    │ ├─ Dropdown: new message    │
    │ └─ Profile:                 │
    │    "Your order dispatched"  │
    └────────────────────────────┘

└─────────────────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────────────┐
│                      NOTIFICATION SYSTEM ARCHITECTURE                        │
└─────────────────────────────────────────────────────────────────────────────┘

    EVENT TRIGGERS NOTIFICATION
    │
    ├─ Order Created (COD)
    │  └─ POST /add_notification.php
    │
    ├─ Payment Callback (Online)
    │  └─ POST /payment_callback.php
    │
    └─ Admin Status Update
       └─ POST /admin/update_order_status.php
                    │
                    ▼
        ┌──────────────────────────┐
        │ INSERT tbl_notifications │
        │ (user_id, order_id,      │
        │  title, message,         │
        │  status='unread')        │
        └──────────┬───────────────┘
                   │
    ┌──────────────┴──────────────┬──────────────┐
    │                             │              │
    ▼                             ▼              ▼
HEADER DATA        API & BADGE              PROFILE PAGE
get_unread_count   fetch_notifications   mark_notification_read
    │                     │                      │
    │ Returns count       │ Returns list         │ Takes notification_id
    │ for badge           │ with details        │ Sets status='read'
    │                     │                     │
    └─────────────┬───────┴──────────────┬──────┘
                  │                      │
            ┌─────▼──────────────────┐
            │ JAVASCRIPT AJAX        │
            │ /header.php:           │
            │ ├─ updateUnread()      │
            │ │  └─ Every 30 seconds │
            │ ├─ loadNotifications() │
            │ │  └─ On dropdown   │
            │ └─ markRead()          │
            │    └─ On click         │
            └──────────────────────┘

└─────────────────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────────────┐
│                        DATABASE SCHEMA                                       │
└─────────────────────────────────────────────────────────────────────────────┘

    tbl_users                         tbl_vendors
    ┌─────────────────┐              ┌─────────────────┐
    │ *user_id (PK)   │              │ *vendor_id (PK) │
    │  user_name      │              │  user_id (FK)   │
    │  email          │              │  shop_name      │
    │  phone          │              │  vendor_name    │
    │  address        │              │  ...            │
    │  ...            │              └────────┬────────┘
    └────────┬────────┘                       │
             │                                │
             │                                │
         ┌───┴────────────────────────────────┴────┐
         │                                         │
         ▼                                         ▼
    tbl_orders                                    FK
    ┌──────────────────────┐
    │ *order_id (PK)       │ ◄─── FK
    │  order_number        │
    │  user_id (FK) ────────────►┐
    │  vendor_id (FK) ───────────┤──► tbl_vendors
    │  subtotal            │     │
    │  tax                 │     │
    │  delivery_charges    │     │
    │  discount            │     │
    │  total_amount        │     │
    │  delivery_address    │     │
    │  delivery_city       │     │
    │  delivery_pincode    │     │
    │  phone               │     │
    │  order_status        │     │
    │    ENUM(           │
    │    'Pending',      │
    │    'Confirmed',    │
    │    'Dispatched',   │
    │    'Completed',    │
    │    'Cancelled')    │
    │  payment_status      │
    │    ENUM(           │
    │    'pending',      │
    │    'Paid',         │
    │    'Failed')       │
    │  payment_method      │
    │  created_at          │
    │  updated_at          │
    └────────┬─────────────┘
             │FK
             │
             ▼
    tbl_order_items
    ┌──────────────────┐
    │ *order_item_id   │
    │  order_id (FK)   │
    │  product_id (FK) │
    │  product_name    │
    │  quantity        │
    │  unit_price      │
    │  subtotal        │
    └──────────────────┘

    tbl_notifications
    ┌──────────────────┐
    │ *notification_id │
    │  user_id (FK)    │──────► tbl_users
    │  order_id (FK?) ─────┐
    │  title           │    │
    │  message         │    └──► tbl_orders (optional)
    │  status          │
    │    ENUM(        │
    │    'unread',    │
    │    'read')      │
    │  created_at      │
    │  updated_at      │
    └──────────────────┘

└─────────────────────────────────────────────────────────────────────────────┘


ORDER STATUS STATE MACHINE

    Pending ◄──────────────────────────────┐
    │                                      │
    │ (COD: skip to Confirmed)             │
    │ (Online: await callback)             │
    │                                      │
    ├─────success─┐               ┌──────fail──┐
    │             │               │            │
    ▼             ▼               ▼            │
Confirmed ◄──┘                  │◄───────────┘
(or stay Pending)               │(Failed)
    │                           │
    ├─ admin or auto            │ ◄─ retry payment
    │                           │    or re-checkout
    ▼
Dispatched
    │
    ├─ admin
    │
    ▼
Completed ◄─── Success Path
    │
    └─ End: No more changes

Any Status ──► Cancelled
    │          (any time)
    │
    └─ End: No more changes


NOTIFICATION LIFECYCLE

    ┌──────────────┐
    │ EVENT        │
    │ ├─ New Order   │
    │ ├─ Payment OK  │
    │ └─ Status Change
    └───────┬────────┘
            │
            ▼
    ┌──────────────────┐
    │ INSERT into      │
    │ tbl_notifications│
    │ status=UNREAD    │
    └───────┬──────────┘
            │
    ┌───────┴────────┬──────────────┐
    │                │              │
    ▼                ▼              ▼
BADGE         DROPDOWN         PROFILE
Fetched        Displayed       Listed
Every 30s      on click        
Shows unread   Shows all       Shows all
                current
                
    │                │              │
    └────────┬───────┴──────┬───────┘
             │              │
             ▼              ▼
    ┌─────────────────────────────┐
    │ CUSTOMER CLICKS             │
    │ mark_notification_read.php  │
    └──────────┬──────────────────┘
               │
               ▼
    ┌──────────────────────────┐
    │ UPDATE tbl_notifications │
    │ SET status='read'        │
    └──────────┬───────────────┘
               │
               ▼
    ┌──────────────────┐
    │ UNREAD -1        │
    │ Badge updates    │
    │ Item unbolds     │
    └──────────────────┘

```

---

## Summary

```
CHECKOUT → CREATE ORDER → PAYMENT PROCESS → STATUS → NOTIFICATIONS

   ↓          ↓              ↓              ↓          ↓
  Form      DB Store     Gateway       Admin Update   Customer
            Tables       Callback       Changes        Sees
                         Updates         Status        Realtime
```

**Every step is secured, logged, and notifies the customer in real-time!**

