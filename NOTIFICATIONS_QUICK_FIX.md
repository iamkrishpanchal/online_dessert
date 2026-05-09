# 🔧 Notifications Not Showing - Quick Fix Guide

## Problem
"Notifications can't show" or "No notifications appear in the bell icon"

---

## Solution (3 Steps)

### **STEP 1: Run the Migration** 
Visit this link in your browser:
```
http://localhost/Sem-6 Project/migrate_notification_dismissal.php
```

This adds the required database columns:
- `is_dismissed` - Tracks manual dismissal
- `auto_dismiss_at` - Tracks auto-expire time

**Expected Result:** You should see ✓ checkmarks next to both columns being added

---

### **STEP 2: Place an Order**
1. Go to the shop
2. Add products to cart
3. Checkout and place an order
4. Choose payment method (COD or Online)

**Expected Result:** Order should be created successfully

---

### **STEP 3: Complete/Cancel the Order**

#### As Admin:
1. Go to Admin Panel
2. Find the order in the orders list
3. Click "Completed" or "Cancelled" button
4. Order status updates

#### As User (Cancel):
1. Go to "My Orders"
2. Click "Cancel Order" on any active order
3. Order status changes to Cancelled

#### As Vendor:
1. Go to Vendor Dashboard
2. Find the order
3. Update status to "Completed"

**Expected Result:** Notification should immediately appear in the bell icon (top right)

---

## Verify It's Working

### **Check Test Dashboard**
Visit: `http://localhost/Sem-6 Project/test_api.php`

Should show ✓ for:
- ✓ User logged in
- ✓ Database connected
- ✓ tbl_notifications exists
- ✓ Columns available
- ✓ Orders exist
- ✓ Notifications exist

---

## Troubleshooting

### **Issue: Still No Notifications**

**Try This:**
1. Clear browser cache: `Ctrl+Shift+Delete` → Clear everything → Refresh page (`Ctrl+F5`)
2. Make sure you're logged in to user account
3. Check the test_api.php page - see if all checks are passing
4. Try placing a new test order

**Check the Browser Console:**
1. Press `F12` to open Developer Tools
2. Click "Console" tab
3. Look for any red error messages
4. Take a screenshot and check

### **Issue: Migration Failed**

If you see red ✗ in migration:
1. Check if database connection is working
2. Make sure the `tbl_notifications` table exists
3. Try running these SQL commands directly in phpMyAdmin:

```sql
ALTER TABLE tbl_notifications ADD COLUMN is_dismissed TINYINT(1) DEFAULT 0 AFTER status;
ALTER TABLE tbl_notifications ADD COLUMN auto_dismiss_at DATETIME NULL AFTER is_dismissed;
```

### **Issue: Old Orders Not Showing**

Old orders won't have auto_dismiss_at set. That's OK. They'll show as regular notifications that stay until dismissed manually.

---

## How Notifications Work Now

### When Order Status Changes to "Completed":
1. ✓ System creates notification
2. ✓ Notification appears in bell icon (green border)
3. ✓ User can click ✕ to dismiss immediately
4. ✓ OR notification auto-disappears after 5 minutes

### When Order Status Changes to "Cancelled":
1. ✗ System creates notification  
2. ✗ Notification appears in bell icon (red border)
3. ✗ User can click ✕ to dismiss immediately
4. ✗ OR notification auto-disappears after 5 minutes

### Other Status Changes (Confirmed, Dispatched):
- Notification appears
- Stays until dismissed
- No auto-disappear

---

## Quick Links

| Action | Link |
|--------|------|
| Run Migration | [migrate_notification_dismissal.php](http://localhost/Sem-6%20Project/migrate_notification_dismissal.php) |
| Test API | [test_api.php](http://localhost/Sem-6%20Project/test_api.php) |
| Full Diagnostics | [test_notifications.php](http://localhost/Sem-6%20Project/test_notifications.php) |
| Back to Shop | [user/index.php](http://localhost/Sem-6%20Project/user/index.php) |

---

## Contact Support

If notifications still don't show after these steps:
1. Check browser console (F12) for errors
2. Visit test_api.php page
3. Take screenshot of errors
4. Check database columns exist using phpMyAdmin

---

**Last Updated:** April 18, 2026  
**System:** Dessert Mag - E-commerce Platform
