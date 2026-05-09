# 📋 Admin Panel - Order Management System

## Overview

The admin panel now features a comprehensive **Order Management Dashboard** that allows admins and vendors to view, filter, and manage orders organized by their status.

---

## 📁 New Files Created

### 1. **orders_dashboard.php**
**Location:** `/admin/orders_dashboard.php`

**Purpose:** Main dashboard showing all orders with real-time status filtering

**Features:**
- 📊 Statistics cards showing count of orders by status
  - Pending
  - Confirmed
  - Dispatched
  - Completed
  - Cancelled
- 🔍 Quick filter buttons for each status
- 📋 Comprehensive table displaying:
  - Order number
  - Customer name & phone
  - Total amount
  - Order status (with color-coded badge)
  - Payment status
  - Order date/time
  - Action buttons (Status transitions, View Details)
- ⚙️ Role-based access (Admin sees all, Vendors see only their orders)

**Status Flow:**
```
Pending → Confirmed → Dispatched → Completed
         ↓
       Cancelled (can be canceled from any non-completed state)
```

**Database Query:** Uses prepared statements with vendor authorization
```php
// For Admin
SELECT o.order_id, o.order_number, u.user_name, ...
FROM tbl_orders o
JOIN tbl_users u ON o.user_id = u.user_id

// For Vendor (filters by vendor_id)
WHERE o.vendor_id = ?
```

---

### 2. **admin_update_order_status.php**
**Location:** `/admin/admin_update_order_status.php`

**Purpose:** API endpoint for updating order status

**Triggered By:** Clicking status transition buttons in dashboard/details page

**Functionality:**
1. ✅ Validates new status against allowed values
2. ✅ Checks authorization (admin or order vendor only)
3. ✅ Updates `tbl_orders.order_status`
4. ✅ Creates notification in `tbl_notifications`
5. ✅ (Optional) Sends email notification to customer
6. ✅ Redirects back with success/error message

**Status-Specific Messages:**
- **Confirmed:** "Your order #[ORDER_NUMBER] has been confirmed!"
- **Dispatched:** "Your order #[ORDER_NUMBER] is on the way!"
- **Completed:** "Your order #[ORDER_NUMBER] has been delivered. Thank you!"
- **Cancelled:** "Your order #[ORDER_NUMBER] has been cancelled."

**Example URL:**
```
admin_update_order_status.php?order_id=123&status=Confirmed&redirect=orders_dashboard.php
```

---

### 3. **order_details.php**
**Location:** `/admin/order_details.php`

**Purpose:** Detailed view of a single order

**Displays:**
- 📦 **Order Items Section**
  - Product image, name, quantity, price
  - Individual item totals
  
- 💰 **Pricing Summary**
  - Subtotal
  - Discount amount
  - Delivery charge
  - Tax
  - **Total amount**

- 👤 **Customer Information**
  - Full name
  - Email (clickable link)
  - Phone number (clickable link)

- 📍 **Delivery Address**
  - Full address with city & pincode
  - Falls back to user's default address if not specified

- 💳 **Payment Details**
  - Payment method (COD/Online)
  - Payment status (Pending/Paid/Failed)

- 🔄 **Status Transition Buttons**
  - Dynamic buttons based on current status
  - Clicking updates status and sends notification

**Access Control:**
- Admin: Can view any order
- Vendor: Can only view their own orders (via vendor_id)

---

## 🔗 Updated Menu Integration

**Location:** `/admin/sideMenu.php`

**New Menu Structure:**
```
Orders
├── All Orders → orders_dashboard.php
├── Pending Orders → orders_dashboard.php?filter=Pending
├── Confirmed Orders → orders_dashboard.php?filter=Confirmed
├── Dispatched Orders → orders_dashboard.php?filter=Dispatched
├── Completed Orders → orders_dashboard.php?filter=Completed
└── Cancelled Orders → orders_dashboard.php?filter=Cancelled
```

---

## 📊 Database Requirements

### Required Tables:

**1. tbl_orders**
```sql
CREATE TABLE tbl_orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE,
    user_id INT,
    vendor_id INT,
    order_status ENUM('Pending', 'Confirmed', 'Dispatched', 'Completed', 'Cancelled'),
    payment_status ENUM('pending', 'Paid', 'Failed'),
    payment_method VARCHAR(50),
    delivery_address TEXT,
    delivery_city VARCHAR(100),
    delivery_pincode VARCHAR(10),
    phone VARCHAR(20),
    subtotal DECIMAL(10,2),
    discount_amount DECIMAL(10,2),
    delivery_charge DECIMAL(10,2),
    tax_amount DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    special_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tbl_users(user_id),
    FOREIGN KEY (vendor_id) REFERENCES tbl_vendors(vendor_id)
);
```

**2. tbl_order_items**
```sql
CREATE TABLE tbl_order_items (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    quantity INT,
    price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES tbl_orders(order_id),
    FOREIGN KEY (product_id) REFERENCES tbl_products(product_id)
);
```

**3. tbl_notifications**
```sql
CREATE TABLE tbl_notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    title VARCHAR(255),
    message TEXT,
    type VARCHAR(50),
    reference_id INT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tbl_users(user_id)
);
```

**4. tbl_users**
```sql
CREATE TABLE tbl_users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    user_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    ...
);
```

---

## 🚀 How to Use

### For Admin Users:

1. **Navigate to Orders Dashboard**
   - Click "Orders" in sidebar → "All Orders"
   - Or click specific status filter (Pending, Confirmed, etc.)

2. **View Order Summary**
   - See count cards for each status at the top
   - Click any count to filter by that status

3. **Update Order Status**
   - Find order in table
   - Click appropriate action button:
     - **Confirm** (for Pending orders)
     - **Dispatch** (for Confirmed orders)
     - **Complete** (for Dispatched orders)
     - **Cancel** (for any uncompleted orders)

4. **View Order Details**
   - Click "Details" button to see full order information
   - Access from details page:
     - Complete order breakdown
     - Customer contact information
     - Delivery address
     - Status update buttons

### For Vendor Users:

- Same interface but **only shows their own orders**
- Filtered by `vendor_id` in database queries
- Can transition own order statuses
- Notifications sent to customers on each status change

---

## 🔐 Authorization & Security

**Access Control:**
```php
// Check authorization
if (empty($_SESSION['admin_id']) && empty($_SESSION['vendor_id'])) {
    header('Location: login.php');
    exit;
}

// Vendor can only see their orders
if (!$is_admin && $order['vendor_id'] !== $vendor_id) {
    die("Access denied");
}
```

**SQL Injection Prevention:**
- All queries use prepared statements
- Parameters bound via `mysqli_stmt_bind_param()`
- No direct string concatenation

**CSRF Protection:**
- Consider adding token validation for status update requests

---

## 📧 Notifications System

When order status changes:

1. **Database Entry Created**
   ```sql
   INSERT INTO tbl_notifications 
   (user_id, title, message, type, reference_id, created_at)
   VALUES (?, 'Confirmed', 'Your order... has been confirmed!', 'order_status_update', ?, NOW())
   ```

2. **User Notifications Available At:**
   - User profile → Notification center (dropdown)
   - Real-time badge update on header
   - (Optional) Email notification sent

3. **Notification Status Lifecycle:**
   - Created as `is_read = 0` (unread)
   - Marked as read when viewed
   - Displayed in reverse chronological order (newest first)

---

## 🎨 UI Features

### Color-Coded Status Badges:
- 🟡 **Pending** - Yellow (#ffc107)
- 🔵 **Confirmed** - Blue (#17a2b8)
- 🟠 **Dispatched** - Orange (#fd7e14)
- 🟢 **Completed** - Green (#28a745)
- 🔴 **Cancelled** - Red (#dc3545)

### Interactive Elements:
- Hover effects on statistic cards
- Responsive table with action buttons
- Modal/alert feedback for status changes
- Breadcrumb navigation

---

## ⚡ Performance Optimization

**Database Queries:**
- Statistics query uses `COUNT(*)` GROUP BY (indexed)
- Order list limited to 100 records per page
- Indexes recommended on:
  - `tbl_orders.order_status`
  - `tbl_orders.vendor_id`
  - `tbl_orders.user_id`
  - `tbl_orders.created_at`

**Frontend:**
- Bootstrap CDN for styling
- No heavy JavaScript dependencies
- Prepared statements reduce query complexity

---

## 🧪 Testing Checklist

- [ ] Can view all orders in dashboard
- [ ] Status count cards display correct numbers
- [ ] Filter by each status works (Pending, Confirmed, etc.)
- [ ] Order details page loads correctly
- [ ] Status update buttons appear based on current status
- [ ] Clicking status button updates order and sends notification
- [ ] Vendor can only see own orders
- [ ] Admin can see all orders
- [ ] Notifications appear in user profile center
- [ ] Customer receives notification messages
- [ ] Action buttons show correctly in table

---

## 🔧 Customization Guide

### Modify Status Values:
Edit in `tbl_orders` schema:
```sql
ALTER TABLE tbl_orders MODIFY order_status 
ENUM('Draft', 'Pending', 'Confirmed', 'Dispatched', 'Completed', 'Cancelled');
```

### Add Custom Notification Messages:
Update `admin_update_order_status.php`:
```php
$notification_messages = [
    'YourStatus' => 'Your custom message here',
    // ...
];
```

### Change Color Schemes:
Edit CSS in dashboard pages:
```css
.badge-pending { background: #YOUR_COLOR; }
```

---

## 📞 Support & Troubleshooting

**Issue:** Orders not appearing
- Check `vendor_id` matches in sessions
- Verify `tbl_orders` and `tbl_users` relationship
- Check prepared statement binding

**Issue:** Notifications not sending
- Verify `tbl_notifications` table exists
- Check user_id foreign key relationship
- Enable email in `admin_update_order_status.php` (uncomment mail function)

**Issue:** Status buttons not showing
- Verify current order status value matches enum
- Check HTML button generation logic
- Inspect browser console for JavaScript errors

---

## 📝 Summary

This admin order management system provides:
✅ Complete order lifecycle management
✅ Real-time status tracking
✅ Customer notifications on status changes
✅ Role-based access control (Admin/Vendor)
✅ Detailed order information views
✅ Quick filtering and search
✅ Professional dashboard UI

Status flow: **Pending → Confirmed → Dispatched → Completed**
with option to **Cancel** at any stage.
