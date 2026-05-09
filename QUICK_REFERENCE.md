# Quick Reference Card - Order & Notification System

## 🎯 What You Just Got

A complete **Order Processing + Real-time Notification System** with:
- ✅ COD auto-confirmation
- ✅ Online payment gateway integration  
- ✅ Admin status updates with notifications
- ✅ Real-time notification badge
- ✅ Notification center in user profile
- ✅ Read/unread tracking

---

## 🚀 Get Started in 3 Steps

### **Step 1: Create Tables (One-time)**
Run this SQL:
```sql
-- Copy contents of order_notification_tables.sql and run in phpMyAdmin or MySQL CLI
mysql -u root -p your_database < order_notification_tables.sql
```

### **Step 2: Test COD Orders**
```
1. Login as customer
2. Add items → Checkout → Select "Cash on Delivery"
3. Check database: order_status should be "Confirmed"
4. Check profile: should see notification
```

### **Step 3: Test Online Payments**
```
1. Set up payment gateway (Razorpay, PayPal, etc)
2. Configure webhook to: https://yourdomain.com/user/payment_callback.php
3. Test with sandbox credentials
```

---

## 📊 Order Status Flow

```
COD Path:                      Online Payment Path:
Order Created                  Order Created
   ↓                              ↓
Confirmed (INSTANT)            Pending (awaiting payment)
   ↓                              ↓
[Admin] Dispatched          Payment Gateway→Success/Fail
   ↓                              ↓
[Admin] Completed           Confirmed OR Pending
   ↓                              ↓
[Customer Delivered]        [Admin] Dispatched
                               ↓
                           [Admin] Completed
```

---

## 🔔 Notification Messages

| When | Title | Message |
|------|-------|---------|
| COD placed | "Order Confirmed" | "Your order is confirmed..." |
| Payment OK | "Payment Received" | "Payment successful" |
| Payment Failed | "Payment Failed" | "Payment could not be processed" |
| Status→Dispatched | "Order Dispatched" | "Order is on the way" |
| Status→Completed | "Order Delivered" | "Thank you for your purchase" |
| Status→Cancelled | "Order Cancelled" | "Order was cancelled" |

---

## 📁 Key Files

| File | Purpose |
|------|---------|
| `order_notification_tables.sql` | Database schema (run this first!) |
| `user/checkout.php` | Order creation with COD/online logic |
| `user/payment_callback.php` | Payment gateway callback handler |
| `admin/update_order_status.php` | Admin updates order + notifies |
| `user/header.php` | Notification bell icon |
| `user/profile.php` | Notifications display |
| `NOTIFICATION_AND_ORDERS_GUIDE.md` | Detailed documentation |
| `PAYMENT_GATEWAY_INTEGRATION.md` | Payment examples |

---

## 🔑 Configuration

### **Order Status Enum**
```sql
ENUM('Pending', 'Confirmed', 'Dispatched', 'Completed', 'Cancelled')
```
⚠️ Case-sensitive! Must be capitalized.

### **Payment Status Enum**
```sql
ENUM('pending', 'Paid', 'Failed')
```
⚠️ Note: 'pending' is lowercase, others are capitalized.

### **Payment Gateway Webhook**
Configure to POST to:
```
https://yourdomain.com/Sem-6%20Project/user/payment_callback.php
```

With parameters:
```
order_id=123&status=success
```

---

## 🧪 Quick Test Commands

### **Simulate Payment Success**
```bash
curl -X POST http://localhost/Sem-6%20Project/user/payment_callback.php \
  -d "order_id=1&status=success"
```

### **Simulate Payment Failure**
```bash
curl -X POST http://localhost/Sem-6%20Project/user/payment_callback.php \
  -d "order_id=2&status=fail"
```

### **Check Orders in Database**
```sql
SELECT order_id, order_number, order_status, payment_status, created_at 
FROM tbl_orders 
ORDER BY created_at DESC LIMIT 10;
```

### **Check Notifications**
```sql
SELECT * FROM tbl_notifications 
WHERE user_id = 1 
ORDER BY created_at DESC;
```

---

## 🔒 Security Features

✅ **Prepared Statements** - All SQL queries are parameterized  
✅ **Session Validation** - Only logged-in users can access  
✅ **Permission Checks** - Vendors can only update own orders  
✅ **Input Validation** - Status values checked against whitelist  
✅ **User Isolation** - Users see only their own notifications  

---

## 📱 API Endpoints

### **Get Unread Count** (for badge)
```
GET /user/get_unread_count.php
Returns: { "success": true, "unread": 3 }
```

### **Fetch Notifications** (for dropdown)
```
GET /user/fetch_notifications.php
Returns: { "success": true, "notifications": [...] }
```

### **Mark as Read** (on click)
```
POST /user/mark_notification_read.php
Body: notification_id=5
```

### **Update Order Status** (admin)
```
POST /admin/update_order_status.php
Body: order_id=1&new_status=Dispatched
```

---

## ⚠️ Common Issues & Fixes

| Issue | Fix |
|-------|-----|
| Orders not creating | Check `tbl_orders` has correct columns (run order_notification_tables.sql) |
| Notifications missing | Verify `tbl_notifications` exists, check INSERT queries |
| Payment callback not firing | Ensure callback URL is public (not localhost), check firewall |
| Admin can't update orders | Check session has `admin_id`, vendor owns order |
| Badge shows 0 always | Check `SELECT COUNT(*) FROM tbl_notifications WHERE status='unread'` |
| Discount columns missing | Run `user/index.php` once to auto-create them |

---

## 🎓 Integration Example (Razor pay)

```php
<?php
session_start();
$order_id = 123;
$amount = 64000; // paise
?>

<form id="rzp-form">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
    var options = {
        key: "YOUR_RAZORPAY_KEY",
        amount: <?php echo $amount; ?>,
        currency: "INR",
        handler: function(response) {
            // Success - call our callback
            fetch('payment_callback.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'order_id=<?php echo $order_id; ?>&status=success'
            }).then(() => {
                window.location = 'orders.php';
            });
        }
    };
    new Razorpay(options).open();
    </script>
</form>
```

---

## 📋 Checklist Before Going Live

- [ ] Run `order_notification_tables.sql`
- [ ] Test COD orders (should auto-confirm)
- [ ] Test notification badge appears
- [ ] Test mark notification as read
- [ ] Choose payment gateway
- [ ] Get API credentials (key, secret)
- [ ] Configure webhook/callback URL
- [ ] Test with sandbox credentials
- [ ] Verify order status updates work
- [ ] Check admin notifications send correctly
- [ ] Review security settings
- [ ] Backup production database

---

## 📞 Support References

**Documentation files in your project:**
- `IMPLEMENTATION_SUMMARY.md` - Complete overview
- `NOTIFICATION_AND_ORDERS_GUIDE.md` - Detailed guide
- `PAYMENT_GATEWAY_INTEGRATION.md` - Payment examples
- `order_notification_tables.sql` - Database schema

**Payment Gateway Docs:**
- Razorpay: https://razorpay.com/docs/
- PayPal: https://developer.paypal.com/
- Stripe: https://stripe.com/docs
- CCAvenue: https://www.ccavenue.com/

---

## 💡 Pro Tips

1. **Use sandbox first** - Always test with test/sandbox credentials
2. **Log everything** - Save payment callbacks: `file_put_contents('callback.log', ...)`
3. **Monitor notifications** - Archive old ones monthly
4. **Backup orders** - Critical data, back up daily
5. **Test edge cases** - What if order deleted before callback?
6. **Monitor performance** - Add indexes if >10k orders

---

**✅ Your system is ready to deploy!**

Questions? Check the documentation files or the implementation in your code.

