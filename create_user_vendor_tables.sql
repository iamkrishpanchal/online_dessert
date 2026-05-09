-- =====================================================
-- Complete User-Vendor-Product Database Schema
-- =====================================================

-- 1. USERS TABLE
DROP TABLE IF EXISTS `tbl_users`;
CREATE TABLE IF NOT EXISTS `tbl_users` (
  `user_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20),
  `address` TEXT,
  `city` VARCHAR(100),
  `pincode` VARCHAR(10),
  `profile_image` VARCHAR(255),
  `user_type` ENUM('customer', 'vendor', 'admin', 'rider') DEFAULT 'customer',
  `is_active` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. VENDORS TABLE (Enhanced)
DROP TABLE IF EXISTS `tbl_vendors`;
CREATE TABLE IF NOT EXISTS `tbl_vendors` (
  `vendor_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `vendor_name` VARCHAR(255) NOT NULL,
  `shop_name` VARCHAR(255) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20),
  `address` TEXT NOT NULL,
  `city` VARCHAR(100),
  `pincode` VARCHAR(10),
  `latitude` DECIMAL(10, 8),
  `longitude` DECIMAL(11, 8),
  `description` TEXT,
  `image_path` VARCHAR(255),
  `logo_path` VARCHAR(255),
  `cover_image` VARCHAR(255),
  `opening_time` TIME,
  `closing_time` TIME,
  `is_online` INT DEFAULT 0,
  `is_active` INT DEFAULT 1,
  `status` VARCHAR(20) DEFAULT 'active',
  `rating` DECIMAL(3, 2) DEFAULT 0.00,
  `vendor_discount_percent` DECIMAL(5,2) DEFAULT 0.00,
  `total_reviews` INT DEFAULT 0,
  `verification_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `tbl_users`(`user_id`) ON DELETE CASCADE,
  INDEX `shop_name_idx` (`shop_name`),
  INDEX `email_idx` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. CATEGORIES TABLE
DROP TABLE IF EXISTS `tbl_categories`;
CREATE TABLE IF NOT EXISTS `tbl_categories` (
  `categories_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `categories_name` VARCHAR(100) NOT NULL UNIQUE,
  `categories_description` TEXT,
  `categories_image` VARCHAR(255),
  `categories_status` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PRODUCTS TABLE (Enhanced)
DROP TABLE IF EXISTS `tbl_product`;
CREATE TABLE IF NOT EXISTS `tbl_product` (
  `product_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `vendor_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `category_id` INT NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `discount_price` DECIMAL(10, 2),
  `discount_percent` DECIMAL(5, 2),
  `quantity_available` INT DEFAULT 0,
  `product_image` VARCHAR(255),
  `is_vegetarian` INT DEFAULT 0,
  `is_vegan` INT DEFAULT 0,
  `ingredients` TEXT,
  `preparation_time` INT,
  `rating` DECIMAL(3, 2) DEFAULT 0.00,
  `total_ratings` INT DEFAULT 0,
  `is_active` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`vendor_id`) REFERENCES `tbl_vendors`(`vendor_id`) ON DELETE CASCADE,
  -- category_id references categories_id (app uses categories_id)
  FOREIGN KEY (`category_id`) REFERENCES `tbl_categories`(`categories_id`) ON DELETE RESTRICT,
  INDEX `vendor_idx` (`vendor_id`),
  INDEX `category_idx` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. CART TABLE
DROP TABLE IF EXISTS `tbl_cart`;
CREATE TABLE IF NOT EXISTS `tbl_cart` (
  `cart_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `vendor_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `tbl_users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`vendor_id`) REFERENCES `tbl_vendors`(`vendor_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `tbl_product`(`product_id`) ON DELETE CASCADE,
  INDEX `user_idx` (`user_id`),
  INDEX `vendor_idx` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ORDERS TABLE
DROP TABLE IF EXISTS `tbl_orders`;
CREATE TABLE IF NOT EXISTS `tbl_orders` (
  `order_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `vendor_id` INT NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  `tax` DECIMAL(10, 2) DEFAULT 0.00,
  `delivery_charges` DECIMAL(10, 2) DEFAULT 0.00,
  `discount` DECIMAL(10, 2) DEFAULT 0.00,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `delivery_address` TEXT NOT NULL,
  `delivery_city` VARCHAR(100),
  `delivery_pincode` VARCHAR(10),
  `phone` VARCHAR(20),
  `special_instructions` TEXT,
  `order_status` ENUM('pending', 'confirmed', 'preparing', 'dispatched', 'delivered', 'cancelled') DEFAULT 'pending',
  `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  `payment_method` VARCHAR(50),
  `transaction_id` VARCHAR(100),
  `estimated_delivery_time` DATETIME,
  `actual_delivery_time` DATETIME,
  `rider_id` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `tbl_users`(`user_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`vendor_id`) REFERENCES `tbl_vendors`(`vendor_id`) ON DELETE RESTRICT,
  INDEX `user_idx` (`user_id`),
  INDEX `vendor_idx` (`vendor_id`),
  INDEX `order_status_idx` (`order_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. ORDER ITEMS TABLE
DROP TABLE IF EXISTS `tbl_order_items`;
CREATE TABLE IF NOT EXISTS `tbl_order_items` (
  `order_item_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  `special_instructions` TEXT,
  FOREIGN KEY (`order_id`) REFERENCES `tbl_orders`(`order_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `tbl_product`(`product_id`) ON DELETE RESTRICT,
  INDEX `order_idx` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. REVIEWS/RATINGS TABLE
DROP TABLE IF EXISTS `tbl_reviews`;
CREATE TABLE IF NOT EXISTS `tbl_reviews` (
  `review_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `vendor_id` INT,
  `product_id` INT,
  `order_id` INT,
  `rating` INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
  `title` VARCHAR(255),
  `review_text` TEXT,
  `helpful_count` INT DEFAULT 0,
  `is_verified_purchase` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `tbl_users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`vendor_id`) REFERENCES `tbl_vendors`(`vendor_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `tbl_product`(`product_id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `tbl_orders`(`order_id`) ON DELETE CASCADE,
  INDEX `user_idx` (`user_id`),
  INDEX `vendor_idx` (`vendor_id`),
  INDEX `product_idx` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. ADDRESSES TABLE (Multiple addresses per user)
DROP TABLE IF EXISTS `tbl_addresses`;
CREATE TABLE IF NOT EXISTS `tbl_addresses` (
  `address_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `address_type` ENUM('home', 'office', 'other') DEFAULT 'home',
  `full_address` TEXT NOT NULL,
  `city` VARCHAR(100),
  `state` VARCHAR(100),
  `pincode` VARCHAR(10),
  `latitude` DECIMAL(10, 8),
  `longitude` DECIMAL(11, 8),
  `phone` VARCHAR(20),
  `is_default` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `tbl_users`(`user_id`) ON DELETE CASCADE,
  INDEX `user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. FAVORITES TABLE
DROP TABLE IF EXISTS `tbl_favorites`;
CREATE TABLE IF NOT EXISTS `tbl_favorites` (
  `favorite_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `vendor_id` INT,
  `product_id` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `tbl_users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`vendor_id`) REFERENCES `tbl_vendors`(`vendor_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `tbl_product`(`product_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_favorite` (`user_id`, `vendor_id`, `product_id`),
  INDEX `user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. COUPONS/OFFERS TABLE
DROP TABLE IF EXISTS `tbl_coupons`;
CREATE TABLE IF NOT EXISTS `tbl_coupons` (
  `coupon_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `coupon_code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` ENUM('percentage', 'fixed') DEFAULT 'percentage',
  `discount_value` DECIMAL(10, 2) NOT NULL,
  `minimum_order_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `maximum_discount` DECIMAL(10, 2),
  `vendor_id` INT,
  `valid_from` DATETIME,
  `valid_till` DATETIME,
  `max_uses` INT,
  `used_count` INT DEFAULT 0,
  `is_active` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vendor_id`) REFERENCES `tbl_vendors`(`vendor_id`) ON DELETE SET NULL,
  INDEX `coupon_code_idx` (`coupon_code`),
  INDEX `vendor_idx` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. NOTIFICATIONS TABLE (for user alerts)
DROP TABLE IF EXISTS `tbl_notifications`;
CREATE TABLE IF NOT EXISTS `tbl_notifications` (
  `notification_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `order_id` INT,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread','read') DEFAULT 'unread',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `tbl_users`(`user_id`) ON DELETE CASCADE,
  INDEX `user_idx` (`user_id`),
  INDEX `order_idx` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE: The notifications table is intentionally simple. Application
-- code (checkout/payment script) should insert rows via the
-- `add_notification.php` endpoint or direct INSERTs. If desired, you
-- can create a MySQL trigger that watches `tbl_orders` and auto-inserts
-- on payment completion.

-- =====================================================
-- Sample Data (Optional)
-- =====================================================

-- Insert sample categories
INSERT IGNORE INTO `tbl_categories` (`categories_name`, `categories_description`) VALUES
('Desserts', 'Sweet treats and desserts'),
('Beverages', 'Drinks and beverages'),
('Snacks', 'Light snacks and sides'),
('Meals', 'Main courses and meals');

-- =====================================================
-- Views for Easy Data Fetching
-- =====================================================

-- View: User-Vendor Details (for displaying vendor info to users)
CREATE OR REPLACE VIEW vw_vendor_details AS
SELECT 
    v.vendor_id,
    v.shop_name,
    v.vendor_name,
    v.phone,
    v.address,
    v.city,
    v.pincode,
    v.description,
    v.logo_path,
    v.cover_image,
    v.opening_time,
    v.closing_time,
    v.is_online,
    v.rating,
    v.total_reviews,
    v.verification_status,
    COUNT(DISTINCT p.product_id) as total_products,
    ROUND(AVG(r.rating), 2) as avg_rating
FROM `tbl_vendors` v
LEFT JOIN `tbl_product` p ON v.vendor_id = p.vendor_id AND p.is_active = 1
LEFT JOIN `tbl_reviews` r ON v.vendor_id = r.vendor_id
WHERE v.is_active = 1 AND v.verification_status = 'approved'
GROUP BY v.vendor_id, v.shop_name, v.vendor_name, v.phone, v.address, v.city, v.pincode, v.description, v.logo_path, v.cover_image, v.opening_time, v.closing_time, v.is_online, v.rating, v.total_reviews, v.verification_status;

-- View: Products with Vendor Details (for product listing)
CREATE OR REPLACE VIEW vw_products_with_vendor AS
SELECT 
    p.product_id,
    p.product_name,
    p.description,
    p.price,
    p.discount_price,
    p.discount_percent,
    p.product_image,
    p.rating,
    p.is_vegetarian,
    p.is_vegan,
    c.categories_id AS category_id,
    c.categories_name AS category_name,
    v.vendor_id,
    v.shop_name,
    v.vendor_name,
    v.logo_path,
    v.is_online,
    v.rating as vendor_rating
FROM `tbl_product` p
INNER JOIN `tbl_categories` c ON p.category_id = c.categories_id
INNER JOIN `tbl_vendors` v ON p.vendor_id = v.vendor_id
WHERE p.is_active = 1 AND c.is_active = 1 AND v.is_active = 1;

-- View: Order Details (for user and vendor dashboard)
CREATE OR REPLACE VIEW vw_order_summary AS
SELECT 
    o.order_id,
    o.order_number,
    u.user_id,
    u.name as customer_name,
    u.phone as customer_phone,
    v.vendor_id,
    v.shop_name,
    o.total_amount,
    o.order_status,
    o.payment_status,
    o.created_at,
    COUNT(oi.order_item_id) as item_count
FROM `tbl_orders` o
INNER JOIN `tbl_users` u ON o.user_id = u.user_id
INNER JOIN `tbl_vendors` v ON o.vendor_id = v.vendor_id
LEFT JOIN `tbl_order_items` oi ON o.order_id = oi.order_id
GROUP BY o.order_id, o.order_number, u.user_id, u.name, u.phone, v.vendor_id, v.shop_name, o.total_amount, o.order_status, o.payment_status, o.created_at;

-- =====================================================
-- Indexes for Performance
-- =====================================================

ALTER TABLE `tbl_users` ADD INDEX `email_idx` (`email`);
ALTER TABLE `tbl_product` ADD INDEX `price_idx` (`price`);
ALTER TABLE `tbl_orders` ADD INDEX `created_at_idx` (`created_at`);
ALTER TABLE `tbl_cart` ADD UNIQUE INDEX `cart_unique_idx` (`user_id`, `vendor_id`, `product_id`);

-- =====================================================
-- End of Schema
-- =====================================================
