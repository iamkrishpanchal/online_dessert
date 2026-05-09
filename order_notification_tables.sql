-- =====================================================
-- Order & Notification System Tables
-- =====================================================
-- Run this file to ensure all required tables exist with proper structure

-- =====================================================
-- 1. ORDERS TABLE (Enhanced)
-- =====================================================
DROP TABLE IF EXISTS `tbl_orders`;
CREATE TABLE IF NOT EXISTS `tbl_orders` (
  `order_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `vendor_id` INT NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `delivery_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `delivery_address` TEXT NOT NULL,
  `delivery_city` VARCHAR(100),
  `delivery_pincode` VARCHAR(10),
  `phone` VARCHAR(20),
  `order_status` ENUM('Pending', 'Confirmed', 'Dispatched', 'Completed', 'Cancelled') DEFAULT 'Pending',
  `payment_status` ENUM('pending', 'Paid', 'Failed') DEFAULT 'pending',
  `payment_method` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `user_idx` (`user_id`),
  INDEX `vendor_idx` (`vendor_id`),
  INDEX `order_status_idx` (`order_status`),
  INDEX `payment_status_idx` (`payment_status`),
  KEY `created_at_idx` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `tbl_users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`vendor_id`) REFERENCES `tbl_vendors`(`vendor_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. ORDER ITEMS TABLE
-- =====================================================
DROP TABLE IF EXISTS `tbl_order_items`;
CREATE TABLE IF NOT EXISTS `tbl_order_items` (
  `order_item_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `special_instructions` TEXT,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `order_item_order_idx` (`order_id`),
  INDEX `product_item_idx` (`product_id`),
  FOREIGN KEY (`order_id`) REFERENCES `tbl_orders`(`order_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `tbl_product`(`product_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. NOTIFICATIONS TABLE
-- =====================================================
DROP TABLE IF EXISTS `tbl_notifications`;
CREATE TABLE IF NOT EXISTS `tbl_notifications` (
  `notification_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `vendor_id` INT NULL,
  `admin_id` INT NULL,
  `order_id` INT,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread', 'read') DEFAULT 'unread',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `notif_user_idx` (`user_id`),
  INDEX `notif_vendor_idx` (`vendor_id`),
  INDEX `notif_admin_idx` (`admin_id`),
  INDEX `notif_order_idx` (`order_id`),
  INDEX `notif_status_idx` (`status`),
  KEY `notif_created_idx` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `tbl_users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`vendor_id`) REFERENCES `tbl_vendors`(`vendor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- OPTIONAL: STORED PROCEDURES for common operations
-- =====================================================

-- Procedure to create notification when order status changes
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_notify_order_status_change`(
  IN p_order_id INT,
  IN p_new_status VARCHAR(50)
)
BEGIN
  DECLARE v_user_id INT;
  DECLARE v_order_num VARCHAR(50);
  DECLARE v_title VARCHAR(255);
  DECLARE v_message TEXT;
  
  -- Fetch order details
  SELECT o.user_id, o.order_number 
  INTO v_user_id, v_order_num
  FROM tbl_orders o
  WHERE o.order_id = p_order_id;
  
  -- Determine notification message based on status
  CASE p_new_status
    WHEN 'Confirmed' THEN
      SET v_title = 'Your order has been confirmed.';
      SET v_message = 'Your order has been confirmed and is being prepared.';
    WHEN 'Dispatched' THEN
      SET v_title = 'Your order has been dispatched.';
      SET v_message = 'Your order is on the way to you.';
    WHEN 'Completed' THEN
      SET v_title = 'Your order has been delivered successfully.';
      SET v_message = 'Thank you for your order. Delivery is complete.';
    WHEN 'Cancelled' THEN
      SET v_title = 'Your order has been cancelled.';
      SET v_message = 'Your order has been cancelled. Please contact support.';
    ELSE
      SET v_title = 'Order Status Update';
      SET v_message = CONCAT('Your order status is now: ', p_new_status);
  END CASE;
  
  -- Insert notification
  IF v_user_id IS NOT NULL THEN
    INSERT INTO tbl_notifications (user_id, order_id, title, message, status)
    VALUES (v_user_id, p_order_id, v_title, v_message, 'unread');
  END IF;
END //
DELIMITER ;

-- Procedure to get unread notification count for a user or vendor
-- pass NULL for the unused id parameter
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_get_unread_count`(
  IN p_user_id INT,
  IN p_vendor_id INT,
  OUT p_count INT
)
BEGIN
  IF p_vendor_id IS NOT NULL THEN
    SELECT COUNT(*) 
    INTO p_count
    FROM tbl_notifications 
    WHERE vendor_id = p_vendor_id AND status = 'unread';
  ELSE
    SELECT COUNT(*) 
    INTO p_count
    FROM tbl_notifications 
    WHERE user_id = p_user_id AND status = 'unread';
  END IF;
END //
DELIMITER ;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================
-- All indexes are created above in the table definitions
-- Additional indexes can be added as needed:

-- For filtering orders by date range:
-- CREATE INDEX idx_orders_date_range ON tbl_orders (user_id, created_at DESC);

-- For reporting by vendor and status:
-- CREATE INDEX idx_orders_vendor_status ON tbl_orders (vendor_id, order_status, created_at DESC);

-- For notification cleanup queries:
-- CREATE INDEX idx_notifications_cleanup ON tbl_notifications (created_at, status);

-- =====================================================
-- SAMPLE DATA (for testing)
-- =====================================================
-- Uncomment and modify as needed for test data

/*
-- Insert a test order
INSERT INTO tbl_orders 
(order_number, user_id, vendor_id, subtotal, tax, delivery_charges, discount, total_amount, 
 delivery_address, delivery_city, delivery_pincode, phone, order_status, payment_status, payment_method)
VALUES 
('ORD20230101001', 1, 1, 500.00, 90.00, 50.00, 0.00, 640.00, 
 '123 Main Street', 'Mumbai', '400001', '9876543210', 'Confirmed', 'Paid', 'Online');

-- Insert test order items
INSERT INTO tbl_order_items (order_id, product_id, product_name, quantity, unit_price, subtotal)
VALUES (1, 1, 'Chocolate Cake', 2, 250.00, 500.00);

-- Insert test notification
INSERT INTO tbl_notifications (user_id, order_id, title, message, status)
VALUES (1, 1, 'Order Confirmed', 'Your order ORD20230101001 has been confirmed.', 'unread');
*/

-- =====================================================
-- DOCUMENTATION
-- =====================================================
/*

ORDER STATUS FLOW:
------------------
1. Customer places order at checkout:
   - All new orders (COD or Online) start with order_status = 'Pending', payment_status = 'pending'
     and require vendor confirmation.  Past behavior automatically confirmed COD orders.

2. Payment Processing (Online only):
   - Gateway callback updates: order_status = 'Confirmed', payment_status = 'Paid'
   - Or on failure: order_status = 'Pending', payment_status = 'Failed'

3. Admin/Vendor workflow:
   - Confirmed → Dispatched → Completed (success path)
   - Cancelled (anytime)

4. Each status change triggers:
   - INSERT notification to tbl_notifications
   - Customer sees update in profile dashboard

NOTIFICATION FIELDS:
--------------------
- notification_id: Unique identifier
- user_id: Customer who receives the notification
- order_id: Associated order (nullable for non-order notifications)
- title: Short summary
- message: Full message text
- status: 'unread' or 'read'
- created_at: Timestamp

PAYMENT STATUS VALUES:
---------------------
- pending: Waiting for payment (COD or unpaid online)
- Paid: Payment received
- Failed: Payment failed

ORDER STATUS VALUES:
-------------------
- Pending: Awaiting payment (online orders) or awaiting confirmation
- Confirmed: Order is confirmed and being prepared
- Dispatched: Order left for delivery
- Completed: Order delivered
- Cancelled: Order was cancelled

*/
