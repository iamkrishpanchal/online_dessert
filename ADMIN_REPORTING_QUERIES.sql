-- =================================================================
-- Useful SQL Queries for Order Management & Reporting
-- =================================================================

-- ===================== 1. OVERVIEW QUERIES =====================

-- Total orders count
SELECT COUNT(*) as total_orders FROM tbl_orders;

-- Orders by status
SELECT 
    order_status,
    COUNT(*) as count,
    SUM(total_amount) as revenue
FROM tbl_orders
GROUP BY order_status;

-- Payment status overview
SELECT 
    payment_status,
    COUNT(*) as count,
    SUM(total_amount) as amount
FROM tbl_orders
GROUP BY payment_status;

-- Today's orders
SELECT 
    order_id, order_number, user_id, total_amount, 
    order_status, payment_status, created_at
FROM tbl_orders
WHERE DATE(created_at) = CURDATE()
ORDER BY created_at DESC;

-- Revenue by day (last 30 days)
SELECT 
    DATE(created_at) as order_date,
    COUNT(*) as orders,
    SUM(total_amount) as revenue,
    AVG(total_amount) as avg_order
FROM tbl_orders
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
AND order_status != 'Cancelled'
GROUP BY DATE(created_at)
ORDER BY order_date DESC;

-- ===================== 2. ORDER ANALYSIS =====================

-- Orders pending for more than 24 hours
SELECT 
    o.order_id, o.order_number, 
    u.user_name, u.email,
    o.total_amount,
    TIMEDIFF(NOW(), o.created_at) as pending_duration,
    o.order_status
FROM tbl_orders o
JOIN tbl_users u ON o.user_id = u.user_id
WHERE o.order_status = 'Pending'
AND o.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY o.created_at ASC;

-- Orders awaiting payment (status Confirmed but payment pending)
SELECT 
    o.order_id, o.order_number,
    u.user_name, u.phone,
    v.shop_name,
    o.total_amount,
    o.created_at
FROM tbl_orders o
JOIN tbl_users u ON o.user_id = u.user_id
JOIN tbl_vendors v ON o.vendor_id = v.vendor_id
WHERE o.order_status = 'Confirmed'
AND o.payment_status = 'pending'
AND o.payment_method = 'COD'
ORDER BY o.created_at ASC;

-- Orders with failed payments (retry candidates)
SELECT 
    o.order_id, o.order_number,
    u.user_name, u.email, u.phone,
    o.total_amount,
    o.updated_at
FROM tbl_orders o
JOIN tbl_users u ON o.user_id = u.user_id
WHERE o.payment_status = 'Failed'
AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY o.created_at DESC;

-- ===================== 3. VENDOR ANALYTICS =====================

-- Top vendors by order count
SELECT 
    v.vendor_id, v.shop_name, v.vendor_name,
    COUNT(o.order_id) as total_orders,
    SUM(o.total_amount) as revenue,
    AVG(o.total_amount) as avg_order_value
FROM tbl_vendors v
LEFT JOIN tbl_orders o ON v.vendor_id = o.vendor_id
    AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY v.vendor_id
ORDER BY revenue DESC;

-- Vendor's pending orders
SELECT 
    o.order_id, o.order_number,
    u.user_name, u.phone,
    o.total_amount,
    o.delivery_address,
    o.created_at
FROM tbl_orders o
JOIN tbl_users u ON o.user_id = u.user_id
WHERE o.vendor_id = 1  -- Replace 1 with vendor_id
AND o.order_status IN ('Pending', 'Confirmed')
ORDER BY o.created_at ASC;

-- ===================== 4. CUSTOMER ANALYTICS =====================

-- Top customers by spending
SELECT 
    u.user_id, u.user_name, u.email,
    COUNT(o.order_id) as total_orders,
    SUM(o.total_amount) as total_spent,
    AVG(o.total_amount) as avg_order,
    MAX(o.created_at) as last_order
FROM tbl_users u
LEFT JOIN tbl_orders o ON u.user_id = o.user_id
WHERE MONTH(u.created_at) = MONTH(NOW())
GROUP BY u.user_id
HAVING total_orders > 0
ORDER BY total_spent DESC
LIMIT 20;

-- Orders with items
SELECT 
    o.order_id, o.order_number,
    u.user_name,
    COUNT(oi.order_item_id) as item_count,
    GROUP_CONCAT(oi.product_name SEPARATOR ', ') as products,
    o.total_amount,
    o.order_status
FROM tbl_orders o
JOIN tbl_users u ON o.user_id = u.user_id
LEFT JOIN tbl_order_items oi ON o.order_id = oi.order_id
GROUP BY o.order_id
ORDER BY o.created_at DESC
LIMIT 50;

-- Orders by delivery city
SELECT 
    o.delivery_city,
    COUNT(*) as orders,
    SUM(o.total_amount) as revenue,
    AVG(o.total_amount) as avg_order
FROM tbl_orders o
WHERE o.delivery_city IS NOT NULL
AND o.delivery_city != ''
GROUP BY o.delivery_city
ORDER BY revenue DESC;

-- ===================== 5. NOTIFICATION ANALYTICS =====================

-- Unread notifications per user
SELECT 
    u.user_id, u.user_name, u.email,
    COUNT(CASE WHEN n.status = 'unread' THEN 1 END) as unread_count,
    COUNT(n.notification_id) as total_notifications,
    MAX(n.created_at) as latest_notification
FROM tbl_users u
LEFT JOIN tbl_notifications n ON u.user_id = n.user_id
GROUP BY u.user_id
HAVING unread_count > 0
ORDER BY unread_count DESC;

-- Most common notification types
SELECT 
    n.title,
    COUNT(*) as count,
    COUNT(CASE WHEN n.status = 'unread' THEN 1 END) as unread
FROM tbl_notifications n
GROUP BY n.title
ORDER BY count DESC;

-- Notification engagement (read vs unread)
SELECT 
    DATE(n.created_at) as date,
    COUNT(CASE WHEN n.status = 'read' THEN 1 END) as read_count,
    COUNT(CASE WHEN n.status = 'unread' THEN 1 END) as unread_count,
    ROUND(COUNT(CASE WHEN n.status = 'read' THEN 1 END) / COUNT(*) * 100, 2) as read_percentage
FROM tbl_notifications n
WHERE n.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(n.created_at)
ORDER BY date DESC;

-- ===================== 6. MAINTENANCE QUERIES =====================

-- Find orphaned order items (no corresponding order)
SELECT 
    oi.order_item_id, oi.order_id,
    oi.product_name, oi.quantity
FROM tbl_order_items oi
LEFT JOIN tbl_orders o ON oi.order_id = o.order_id
WHERE o.order_id IS NULL;

-- Find orders with orphaned items
SELECT 
    o.order_id, o.order_number,
    COUNT(oi.order_item_id) as item_count
FROM tbl_orders o
LEFT JOIN tbl_order_items oi ON o.order_id = oi.order_id
GROUP BY o.order_id
HAVING COUNT(oi.order_item_id) = 0;

-- Find notifications without linked orders
SELECT 
    n.notification_id,
    n.user_id,
    n.order_id,
    n.title,
    n.created_at
FROM tbl_notifications n
WHERE n.order_id IS NOT NULL
AND n.order_id NOT IN (SELECT order_id FROM tbl_orders);

-- Find orders with mismatched user and vendor
SELECT 
    o.order_id, o.order_number,
    o.user_id, u.user_name,
    o.vendor_id, v.shop_name
FROM tbl_orders o
LEFT JOIN tbl_users u ON o.user_id = u.user_id
LEFT JOIN tbl_vendors v ON o.vendor_id = v.vendor_id
WHERE u.user_id IS NULL OR v.vendor_id IS NULL;

-- Archive old notifications (before deleting)
-- Safe way: export first
SELECT *
INTO OUTFILE '/tmp/notifications_2024_old.csv'
FIELDS TERMINATED BY ','
FROM tbl_notifications
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Then delete
-- DELETE FROM tbl_notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- ===================== 7. DATA QUALITY CHECKS =====================

-- NULL checks
SELECT 
    'tbl_orders missing user_id' as issue, COUNT(*) as count
FROM tbl_orders WHERE user_id IS NULL
UNION ALL
SELECT 'tbl_orders missing vendor_id' as issue, COUNT(*) as count
FROM tbl_orders WHERE vendor_id IS NULL
UNION ALL
SELECT 'tbl_order_items missing product_name' as issue, COUNT(*) as count
FROM tbl_order_items WHERE product_name IS NULL OR product_name = '';

-- Duplicate order numbers
SELECT order_number, COUNT(*) as duplicates
FROM tbl_orders
GROUP BY order_number
HAVING COUNT(*) > 1;

-- Mismatched amounts (should equal subtotal+tax+charges-discount)
SELECT 
    o.order_id, o.order_number,
    o.total_amount,
    (o.subtotal + o.tax + o.delivery_charges - o.discount) as calculated_total,
    ABS(o.total_amount - (o.subtotal + o.tax + o.delivery_charges - o.discount)) as difference
FROM tbl_orders
WHERE o.total_amount != (o.subtotal + o.tax + o.delivery_charges - o.discount);

-- ===================== 8. DASHBOARD STATS =====================

-- Admin Dashboard Summary
SELECT 
    (SELECT COUNT(*) FROM tbl_orders WHERE DATE(created_at) = CURDATE()) as today_orders,
    (SELECT SUM(total_amount) FROM tbl_orders WHERE DATE(created_at) = CURDATE() AND order_status != 'Cancelled') as today_revenue,
    (SELECT COUNT(*) FROM tbl_orders WHERE order_status = 'Pending') as pending_orders,
    (SELECT COUNT(*) FROM tbl_orders WHERE order_status = 'Dispatched') as dispatched_orders,
    (SELECT COUNT(*) FROM tbl_notifications WHERE status = 'unread') as unread_notifications,
    (SELECT COUNT(*) FROM tbl_users WHERE DATE(created_at) = CURDATE()) as new_users_today;

-- ===================== 9. PERFORMANCE QUERIES =====================

-- Check if indexes exist and are being used
SHOW INDEX FROM tbl_orders;
SHOW INDEX FROM tbl_order_items;
SHOW INDEX FROM tbl_notifications;

-- Slow query analysis (if slow_query_log enabled)
-- SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;

-- ===================== 10. CUSTOM REPORTS =====================

-- Monthly sales report
SELECT 
    YEAR(o.created_at) as year,
    MONTH(o.created_at) as month,
    COUNT(*) as orders,
    SUM(o.total_amount) as revenue,
    AVG(o.total_amount) as avg_order,
    COUNT(DISTINCT o.user_id) as unique_customers
FROM tbl_orders o
WHERE o.order_status != 'Cancelled'
GROUP BY YEAR(o.created_at), MONTH(o.created_at)
ORDER BY year DESC, month DESC;

-- Payment method analysis
SELECT 
    o.payment_method,
    COUNT(*) as count,
    SUM(o.total_amount) as revenue,
    AVG(o.total_amount) as avg_order,
    COUNT(CASE WHEN o.payment_status = 'Paid' THEN 1 END) as paid_count,
    COUNT(CASE WHEN o.payment_status = 'Failed' THEN 1 END) as failed_count
FROM tbl_orders o
GROUP BY o.payment_method
ORDER BY revenue DESC;

-- Fulfillment status report
SELECT 
    o.order_status,
    COUNT(*) as count,
    AVG(DATEDIFF(o.updated_at, o.created_at)) as avg_days_in_status,
    MIN(o.created_at) as oldest_order,
    MAX(o.created_at) as newest_order
FROM tbl_orders o
WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY o.order_status
ORDER BY count DESC;

