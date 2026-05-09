# Order & Notification System Integration Guide

## 1. Database Structure

### **tbl_orders** (Core Order Table)
```sql
CREATE TABLE IF NOT EXISTS tbl_orders (
  order_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(50) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  vendor_id INT NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  tax DECIMAL(10,2) NOT NULL,
  delivery_charges DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  delivery_address TEXT NOT NULL,
  delivery_city VARCHAR(100),
  delivery_pincode VARCHAR(10),
  phone VARCHAR(20),
  order_status ENUM('Pending', 'Confirmed', 'Dispatched', 'Completed', 'Cancelled') DEFAULT 'Pending',
  payment_status ENUM('pending', 'Paid', 'Failed') DEFAULT 'pending',
  payment_method VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX user_idx (user_id),
  INDEX vendor_idx (vendor_id),
  INDEX status_idx (order_status),
  FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (vendor_id) REFERENCES tbl_vendors(vendor_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### **tbl_order_items** (Order Line Items)
```sql
CREATE TABLE IF NOT EXISTS tbl_order_items (
  order_item_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  special_instructions TEXT,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX order_idx (order_id),
  INDEX product_idx (product_id),
  FOREIGN KEY (order_id) REFERENCES tbl_orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES tbl_product(product_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### **tbl_notifications** (Notification Log)
```sql
CREATE TABLE IF NOT EXISTS tbl_notifications (
  notification_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  order_id INT,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('unread', 'read') DEFAULT 'unread',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX user_idx (user_id),
  INDEX order_idx (order_id),
  FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 2. Order Status Workflow

```
    Customer Checkout
         |
         v
   Payment Method
     /       \
   COD       Online Payment
    |            |
    v            v
Confirmed    Pending (awaiting callback)
    |            |
    +---- <------+  (callback success)
         |
         v
    Confirmed  ------------> Dispatched ---------> Completed
         |
         +---- Cancelled (anytime)
```

---

## 3. Payment Flow

### **Case 1: Cash on Delivery (COD)**
1. User selects COD at checkout
2. Order created with:
   - `order_status = 'Pending'` (all new orders start pending)
   - `payment_status = 'pending'` (payment still due on delivery)
3. Notification sent: *"Your order has been placed and is pending vendor confirmation."*
4. Vendor must log in and confirm the order; confirming sets `order_status='Confirmed'` and
   (for COD) updates `payment_status='Paid'`.

### **Case 2: Online Payment**
1. User selects online payment gateway at checkout
2. Order created with:
   - `order_status = 'Pending'` (waiting for payment)
   - `payment_status = 'pending'` (payment not yet processed)
3. User redirected to payment gateway
4. Gateway processes payment and calls back to `payment_callback.php`

**On Success:**
- UPDATE: `order_status = 'Confirmed'`, `payment_status = 'Paid'`
- Notification: *"Payment Received"* → *Your order has been confirmed.*

**On Failure:**
- UPDATE: `order_status = 'Pending'` (or 'Failed'), `payment_status = 'Failed'`
- Notification: *"Payment Failed"* → *Please try again.*

---

## 4. Backend Endpoints

### **A. Payment Gateway Callback**
**File:** `user/payment_callback.php`

**Purpose:** Handles payment gateway responses (success/failure)

**Expected Parameters (POST):**
- `order_id` (int) — The order to update
- `status` (string) — 'success' or 'fail'

**Example Request:**
```php
POST /user/payment_callback.php HTTP/1.1
Content-Type: application/x-www-form-urlencoded

order_id=123&status=success
```

**PHP Logic:**
```php
if ($status === 'success') {
    // Update order
    UPDATE tbl_orders SET order_status = 'Confirmed', payment_status = 'Paid'
    
    // Insert notification
    INSERT INTO tbl_notifications (user_id, order_id, title, message)
    VALUES (?, ?, 'Payment Received', 'Payment for order #... successful...')
}
```

---

### **B. Admin Order Status Update**
**File (Vendor):** `admin/vendor/updateOrderStatus.php`
**File (Main Admin):** `admin/update_order_status.php`

**Purpose:** Admin updates order status and triggers customer notification

**HTTP Method:** POST (from form) or via AJAX

**Parameters:**
- `order_id` (int)
- `new_status` (string) — One of: 'Confirmed', 'Dispatched', 'Completed', 'Cancelled'

**Workflow:**
```php
1. Validate status is in allowed list
2. UPDATE tbl_orders SET order_status = ?
3. Based on new status:
      - Confirmed → Insert notification, Update payment_status to 'Paid'
      - Dispatched → Insert notification
      - Completed → Insert notification
      - Cancelled → Insert notification
```

**Notification Messages by Status:**
| Status | Title | Message |
|--------|-------|---------|
| Confirmed | "Your order has been confirmed." | "Your order has been confirmed and is being prepared." |
| Dispatched | "Your order has been dispatched." | "Your order is on the way to you." |
| Completed | "Your order has been delivered." | "Thank you for your purchase. Order complete." |
| Cancelled | "Your order has been cancelled." | "Order has been cancelled. Contact support for help." |

---

## 5. Notification System

### **Display Notifications (User Profile)**
**File:** `user/profile.php`

Shows all notifications for logged-in user:
```sql
SELECT * FROM tbl_notifications 
WHERE user_id = ? 
ORDER BY created_at DESC 
LIMIT 50
```

Features:
- ✅ Bold/highlighted text for unread notifications
- ✅ Timestamp display
- ✅ Click to mark as read
- ✅ Linked to order_id (if applicable)

---

### **Notification Badge (Header)**
**File:** `user/header.php` + `user/get_unread_count.php`

Displays count of unread notifications:
```js
// Fetches every 30 seconds
fetch('get_unread_count.php')
  .then(r => r.json())
  .then(data => {
    // Show badge if unread > 0
    document.getElementById('notif-count').textContent = data.unread;
  })
```

**Backend Query:**
```php
$stmt = mysqli_prepare($conn, 
  "SELECT COUNT(*) as cnt FROM tbl_notifications WHERE user_id=? AND status='unread'");
```

---

### **Mark Notification as Read**
**File:** `user/mark_notification_read.php`

**Call via AJAX (POST):**
```js
fetch('mark_notification_read.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/x-www-form-urlencoded'},
  body: 'notification_id=' + id
})
```

**PHP:**
```php
UPDATE tbl_notifications SET status = 'read' WHERE notification_id = ? AND user_id = ?
```

---

## 6. Security Measures

### **Prepared Statements**
All database queries use prepared statements to prevent SQL injection:
```php
$stmt = mysqli_prepare($conn, "UPDATE tbl_orders SET order_status = ? WHERE order_id = ?");
mysqli_stmt_bind_param($stmt, 'si', $new_status, $order_id);
mysqli_stmt_execute($stmt);
```

### **Session Validation**
Admin endpoints verify user session before allowing status updates:
```php
if (empty($_SESSION['admin_id']) && empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
```

### **Input Validation**
- Status values checked against whitelist
- Order ID validated as integer
- User ID must own the notification before marking as read

---

## 7. Integration Checklist

- [ ] Ensure `tbl_orders` has `order_status` and `payment_status` columns
- [ ] Ensure `tbl_order_items` exists with FK to `tbl_orders`
- [ ] Ensure `tbl_notifications` exists with FK to `tbl_users` and `tbl_orders`
- [ ] Deploy `user/payment_callback.php` 
- [ ] Deploy `admin/update_order_status.php` (if using main admin panel)
- [ ] Update vendor `admin/vendor/updateOrderStatus.php` with notification logic
- [ ] Verify `user/header.php` has notification bell icon & JS
- [ ] Verify `user/profile.php` displays notifications
- [ ] Ensure `user/get_unread_count.php` exists
- [ ] Ensure `user/fetch_notifications.php` exists
- [ ] Ensure `user/mark_notification_read.php` exists
- [ ] Test COD flow (should auto-confirm)
- [ ] Test online payment flow (with gateway callback)
- [ ] Test admin status update notifications

---

## 8. Configuration for Payment Gateway

Your payment gateway (Razorpay, PayPal, Stripe, etc.) should be configured to POST back to:

```
https://yourdomain.com/user/payment_callback.php
```

With minimal parameters:
```
order_id=<your_internal_order_id>
status=success|fail
```

**Example (Razorpay webhook):**
```php
// In your Razorpay success handler:
if ($payment_response['success']) {
    $ch = curl_init('https://yourdomain.com/user/payment_callback.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 
        http_build_query(['order_id' => $order_id, 'status' => 'success'])
    );
    curl_exec($ch);
    curl_close($ch);
}
```

---

## 9. Testing

### **Test COD Order:**
1. Login as customer
2. Add items to cart
3. Proceed to checkout
4. Select "Cash on Delivery"
5. Confirm order
6. Verify `order_status = 'Confirmed'` in database
7. Check profile → notifications (should see confirmation message)

### **Test Online Payment:**
1. Login as customer
2. Add items to cart
3. Proceed to checkout
4. Select "Online Payment"
5. Confirm order (status should be 'Pending')
6. Complete payment in gateway
7. Verify callback was called: `order_status = 'Confirmed'`, `payment_status = 'Paid'`
8. Check profile → notifications (should see payment success)

### **Test Status Update:**
1. Login as admin/vendor
2. Go to orders page
3. Update order from 'Confirmed' → 'Dispatched'
4. As customer, check profile → notifications
5. Should see: *"Your order has been dispatched."*

---

## File Summary

| File | Purpose | Type |
|------|---------|------|
| `user/checkout.php` | Order creation (sets status based on payment method) | PHP |
| `user/payment_callback.php` | Handles gateway payment callbacks | PHP |
| `admin/vendor/updateOrderStatus.php` | Vendor updates order + sends notification | PHP |
| `admin/update_order_status.php` | Main admin updates order + sends notification | PHP |
| `user/profile.php` | Displays notifications to user | PHP/HTML |
| `user/header.php` | Notification bell + AJAX loader | PHP/JS |
| `user/get_unread_count.php` | Returns unread count (JSON) | PHP |
| `user/fetch_notifications.php` | Returns list of notifications (JSON) | PHP |
| `user/mark_notification_read.php` | Marks notification as read | PHP |

