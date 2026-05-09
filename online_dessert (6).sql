-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 20, 2026 at 05:04 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `online_dessert`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_admin`
--

DROP TABLE IF EXISTS `tbl_admin`;
CREATE TABLE IF NOT EXISTS `tbl_admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(255) NOT NULL,
  `admin_email` varchar(255) NOT NULL,
  `admin_password` varchar(50) NOT NULL,
  `admin_contact` varchar(50) NOT NULL,
  PRIMARY KEY (`admin_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_admin`
--

INSERT INTO `tbl_admin` (`admin_id`, `admin_name`, `admin_email`, `admin_password`, `admin_contact`) VALUES
(1, 'Admin', 'admin@dessert.com', 'admin123', '1234567890');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cart`
--

DROP TABLE IF EXISTS `tbl_cart`;
CREATE TABLE IF NOT EXISTS `tbl_cart` (
  `cart_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `qty` int NOT NULL,
  `total` int NOT NULL,
  `final_price` int NOT NULL,
  `vendor_id` int NOT NULL,
  `cart_category_name` text NOT NULL,
  `cart_status` tinyint NOT NULL,
  PRIMARY KEY (`cart_id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_categories`
--

DROP TABLE IF EXISTS `tbl_categories`;
CREATE TABLE IF NOT EXISTS `tbl_categories` (
  `categories_id` int NOT NULL AUTO_INCREMENT,
  `categories_name` varchar(25) NOT NULL,
  `categories_status` enum('1','0') NOT NULL,
  `vendor_id` int DEFAULT '1',
  `categories_description` text NOT NULL,
  `categories_image` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`categories_id`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_categories`
--

INSERT INTO `tbl_categories` (`categories_id`, `categories_name`, `categories_status`, `vendor_id`, `categories_description`, `categories_image`) VALUES
(11, 'Cookies', '1', 1, 'Freshly baked cookies made with premium ingredients for a perfectly soft center and crisp edges. Each cookie is rich in flavor, mildly sweet, and crafted to melt in your mouth. Ideal for tea-time snacks, dessert cravings, or gifting.', '1770270629_cb1843ce6339adb0646c8d9226e10fcd.jpg'),
(12, 'Pastries', '0', 1, 'Soft, creamy, and totally irresistible! These pastries are made fresh to turn any moment into a sweet celebration.', '1770270450_f4dea3e8cbb21d792ca452be26e88c4a.jpg'),
(13, 'waffles', '1', 4, ';oidf', '1770305966_pexels-photo-789327.jpeg'),
(14, 'Cakes', '1', 5, 'bbdcjhdjv', '1770313920_f4dea3e8cbb21d792ca452be26e88c4a.jpg'),
(15, 'Pancake', '1', 6, 'Soft, melt-in-the-mouth pancakes drizzled with rich syrup and topped with fresh fruits for a comforting, indulgent treat.', '1770316460_3dd0d5e8fe8ac76862a2fb5545431964.jpg'),
(16, 'Cookies', '1', 10, 'delicious!!', '1770370794_4f6ab318972e2122f285b11772dc675d.jpg'),
(17, 'Cookies', '1', 11, 'qwertyuiopolkjhgfdsazxcvbnm', '1770374304_4aadd0138812f33e14d0b2eeb6ef3836.jpg'),
(18, 'Pastries', '1', 11, 'sdfaqswdrfgtfds', '1770374356_f4dea3e8cbb21d792ca452be26e88c4a.jpg'),
(19, 'Cakes', '1', 12, 'Beautifully designed cakes that turn every celebration into a sweet memory.', '1770403222_ddad6bb76bcd515f6782a3317419b84d.jpg'),
(20, 'Cookies', '1', 12, 'Freshly baked, crunchy-on-the-outside and soft-inside cookies loaded with delightful flavors—perfect with tea, coffee, or anytime snacking.', '1770403307_cb1843ce6339adb0646c8d9226e10fcd.jpg'),
(21, 'Pastries', '0', 12, 'Light, fluffy pastries filled with creamy layers and delicate flavors, crafted for a melt-in-the-mouth experience in every bite.', '1770403368_f4dea3e8cbb21d792ca452be26e88c4a.jpg'),
(22, 'Pancake', '1', 13, 'Soft, fluffy pancakes cooked to golden perfection and served with delicious toppings for a comforting treat.', '1771319450_3dd0d5e8fe8ac76862a2fb5545431964.jpg'),
(28, 'Cupcakes', '1', 15, 'Soft sponge base with smooth frosting — sweet, light, and satisfying.', '1771492224_Gourmet Cupcakes.jpg'),
(24, 'Cakes', '0', 15, 'Soft, moist, and beautifully layered cakes made with rich ingredients and topped with irresistible frostings—perfect for celebrations or sweet cravings.', '1770405990_11017f281be5886debe3f79884528078.jpg'),
(25, 'Donuts', '1', 15, 'Golden, fluffy donuts loaded with flavor and topped with pure happiness in every bite.', '1770406041_858fbe5ead0dff780cf58ffdb0725096.jpg'),
(26, 'Donuts', '1', 14, 'Perfectly fried donuts with a light, airy texture, finished with rich glazes and indulgent fillings.', '1770406200_bbda93ce008fb5e102eb431ccfacf0b5.jpg'),
(27, 'Pastries', '1', 14, 'Mini treats packed with big flavors—perfect for quick dessert cravings.', '1770406257_2aeddb3628e19bc72fd4c076aa7bf38e.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customert_payment`
--

DROP TABLE IF EXISTS `tbl_customert_payment`;
CREATE TABLE IF NOT EXISTS `tbl_customert_payment` (
  `cust_add_payment_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `address` text NOT NULL,
  `payment_id` int NOT NULL,
  PRIMARY KEY (`cust_add_payment_id`),
  KEY `customer_id` (`customer_id`),
  KEY `payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer_reg`
--

DROP TABLE IF EXISTS `tbl_customer_reg`;
CREATE TABLE IF NOT EXISTS `tbl_customer_reg` (
  `customer_id` int NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(25) NOT NULL,
  `customer_email` varchar(25) NOT NULL,
  `customer_password` varchar(25) NOT NULL,
  `customer_contact` varchar(15) NOT NULL,
  `register_date` datetime NOT NULL,
  `customer_status` enum('0','1') NOT NULL,
  PRIMARY KEY (`customer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_order`
--

DROP TABLE IF EXISTS `tbl_order`;
CREATE TABLE IF NOT EXISTS `tbl_order` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `time` time NOT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_orders`
--

DROP TABLE IF EXISTS `tbl_orders`;
CREATE TABLE IF NOT EXISTS `tbl_orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `vendor_id` int DEFAULT '1',
  `customer_phone` varchar(20) DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `order_status` enum('Pending','Confirmed','Dispatched','Completed','Cancelled','Rejected') DEFAULT 'Pending',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('Cash','Online') DEFAULT 'Cash',
  `notes` text,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment`
--

DROP TABLE IF EXISTS `tbl_payment`;
CREATE TABLE IF NOT EXISTS `tbl_payment` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `payment_mode` varchar(25) NOT NULL,
  `payment_type` varchar(25) NOT NULL,
  `payment_date_time` datetime NOT NULL,
  `transcation_id` text NOT NULL,
  `payment_status` enum('0','1') NOT NULL,
  PRIMARY KEY (`payment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_products`
--

DROP TABLE IF EXISTS `tbl_products`;
CREATE TABLE IF NOT EXISTS `tbl_products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `product_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int NOT NULL,
  `vendor_id` int DEFAULT '1',
  `product_price` decimal(10,2) NOT NULL,
  `product_stock` int NOT NULL DEFAULT '0',
  `product_status` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `product_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `stock` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_products`
--

INSERT INTO `tbl_products` (`product_id`, `product_name`, `product_description`, `category_id`, `vendor_id`, `product_price`, `product_stock`, `product_status`, `product_image`, `created_at`, `updated_at`, `stock`) VALUES
(55, 'Black Forest Cake', '', 19, 12, 450.00, 0, '1', '17713187259706.jpg', '2026-02-17 08:58:45', '2026-02-19 08:48:31', 35),
(56, 'Chocolate Cookie', '', 20, 12, 150.00, 0, '1', '17713190542569.jpg', '2026-02-17 09:04:14', '2026-02-17 09:04:14', 150),
(57, 'Blueberry Pancake', '', 22, 13, 350.00, 0, '1', '17713195465261.jpg', '2026-02-17 09:12:26', '2026-02-17 09:12:26', 40),
(58, 'Chocolate Donut', '', 26, 14, 160.00, 0, '1', '17713202783351.jpg', '2026-02-17 09:24:38', '2026-02-17 09:24:38', 50),
(59, 'Biscoff Cake', '', 24, 15, 450.00, 0, '1', '17713204615723.jpg', '2026-02-17 09:27:41', '2026-02-17 09:27:41', 35),
(60, 'Red Velvet Cake', '', 19, 12, 550.00, 0, '1', '17714885783522.jpg', '2026-02-19 08:09:38', '2026-02-19 08:09:38', 30),
(61, '. Butterscotch Crunch Cake', '', 19, 12, 450.00, 0, '1', '17714887084173.jpg', '2026-02-19 08:11:48', '2026-02-19 08:11:48', 30),
(62, 'KitKat Chocolate Cake', '', 19, 12, 650.00, 0, '1', '17714887918389.jpg', '2026-02-19 08:13:11', '2026-02-19 08:13:11', 20),
(63, 'Oreo Cookies & Cream Cake', '', 19, 12, 500.00, 0, '1', '17714891013903.jpg', '2026-02-19 08:18:21', '2026-02-19 08:18:21', 20),
(64, 'Mango Magic Cake', '', 19, 12, 650.00, 0, '1', '17714893923046.jpg', '2026-02-19 08:23:12', '2026-02-19 08:23:12', 20),
(65, 'Double Chocolate Cookies', '', 20, 12, 250.00, 0, '1', '17714895747157.jpg', '2026-02-19 08:26:14', '2026-02-19 08:26:14', 50),
(66, 'Oatmeal Raisin Cookies', '', 20, 12, 280.00, 0, '1', '17714902975509.jpg', '2026-02-19 08:38:17', '2026-02-19 08:38:17', 50),
(67, 'Peanut Butter Cookies', '', 20, 12, 300.00, 0, '1', '17714904105901.jpg', '2026-02-19 08:40:10', '2026-02-19 08:40:10', 30),
(68, 'Black Forest Pastry', '', 21, 12, 120.00, 0, '1', '17714906874685.jpg', '2026-02-19 08:44:47', '2026-02-19 08:44:47', 10),
(69, 'Mango Pastry', '', 21, 12, 180.00, 0, '1', '17714908595402.jpg', '2026-02-19 08:47:39', '2026-02-19 08:47:39', 15),
(70, 'Strawberry Delight Cupcake', '', 28, 15, 160.00, 0, '1', '17714923377277. ________________________________________ 📝 Preparation Method 1️⃣ Bake the____', '2026-02-19 09:12:17', '2026-02-19 09:12:17', 19);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_rider`
--

DROP TABLE IF EXISTS `tbl_rider`;
CREATE TABLE IF NOT EXISTS `tbl_rider` (
  `rider_id` int NOT NULL AUTO_INCREMENT,
  `rider_name` varchar(25) NOT NULL,
  `rider_email` varchar(25) NOT NULL,
  `rider_contact` varchar(15) NOT NULL,
  `rider_password` varchar(25) NOT NULL,
  `register_date` datetime NOT NULL,
  `rider_status` enum('available','unavailable') NOT NULL,
  `occupie_free_status` enum('0','1') NOT NULL,
  `rider_block_status` enum('0','1') NOT NULL,
  PRIMARY KEY (`rider_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_stock_management`
--

DROP TABLE IF EXISTS `tbl_stock_management`;
CREATE TABLE IF NOT EXISTS `tbl_stock_management` (
  `stock_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `quantity_added` int NOT NULL DEFAULT '0',
  `previous_quantity` int NOT NULL DEFAULT '0',
  `new_quantity` int NOT NULL DEFAULT '0',
  `stock_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `notes` text,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`stock_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

DROP TABLE IF EXISTS `tbl_users`;
CREATE TABLE IF NOT EXISTS `tbl_users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_vendors`
--

DROP TABLE IF EXISTS `tbl_vendors`;
CREATE TABLE IF NOT EXISTS `tbl_vendors` (
  `vendor_id` int NOT NULL AUTO_INCREMENT,
  `vendor_name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `shop_name` varchar(255) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_online` tinyint(1) DEFAULT '0',
  `last_active` datetime DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'inactive',
  PRIMARY KEY (`vendor_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_vendors`
--

INSERT INTO `tbl_vendors` (`vendor_id`, `vendor_name`, `email`, `password`, `phone`, `address`, `shop_name`, `image_path`, `logo_path`, `created_at`, `is_online`, `last_active`, `city`, `status`) VALUES
(12, 'Honey', 'honey1@gmail.com', '$2y$10$yCr2gs0jr4hbwbWHbjbWOeXj7xFSHAjCRKHXEyTwCLrCnLA9ABuGG', '9854789874', 'Shop No. 12, Ghod Dod Road, Athwa, Surat, Gujarat – 395007', 'Sugar Rush Desserts', 'vendor_698634b2acf20.jpg', 'logo_1771487500_6996c10c9e472.png', '2026-02-06 18:36:34', 1, '2026-02-20 20:31:14', NULL, 'active'),
(13, 'Pushti', 'pushti@gmail.com', '$2y$10$3ZrywvdmNZ1ZnVvN9VeBbeRFu6uJJjF6DRZVSLZSg3/cEOvcsibPG', '9254789874', 'Shop No. 5, City Light Road, Surat, Gujarat – 395007', 'Choco Heaven', 'vendor_698639bd33760.jpg', 'logo_1770442924_6986d0ace8df1.png', '2026-02-06 18:58:05', 1, '2026-02-20 21:31:09', NULL, 'active'),
(14, 'Krish', 'krish@gmail.com', '$2y$10$U1/trfctX3u05a2MpB8icOOMCg8SnGMQAN6gYkl9ryTqoA0F4xR8y', '9358747898', '41, Ring Road, Near Sahara Darwaja, Surat, Gujarat – 395002', 'Creamy Crust', 'vendor_69863faa28f66.jpg', 'logo_1770443277_6986d20d55b43.png', '2026-02-06 19:23:22', 0, '2026-02-19 13:20:59', NULL, 'inactive'),
(15, 'Krushiv', 'krushiv@gmail.com', '$2y$10$omxc1IhrFcdmKfWkwQQ0zOEKhBSMrdQSKNCjYthXYqqNiGkBNZmWK', '9854789874', '34, Adajan Gam Road, Adajan, Surat, Gujarat – 395009', 'Urban Treats', '1770441303_ai-generated-8635685_1280.png', 'logo_1771492369_6996d41170697.png', '2026-02-06 19:24:44', 1, '2026-02-19 14:43:53', NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_audit`
--

DROP TABLE IF EXISTS `vendor_audit`;
CREATE TABLE IF NOT EXISTS `vendor_audit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vendor_id` int NOT NULL,
  `action` varchar(32) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vendor_audit`
--

INSERT INTO `vendor_audit` (`id`, `vendor_id`, `action`, `ip`, `user_agent`, `created_at`) VALUES
(1, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 14:54:29'),
(2, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 15:01:26'),
(3, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 15:26:08'),
(4, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 15:27:28'),
(5, 14, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 15:28:50'),
(6, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 15:29:12'),
(7, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 22:08:51'),
(8, 14, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 22:09:00'),
(9, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 22:09:25'),
(10, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 22:09:27'),
(11, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 22:09:57'),
(12, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 22:10:01'),
(13, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-17 14:08:52'),
(14, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-17 14:27:22'),
(15, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-17 14:38:58'),
(16, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-17 14:39:43'),
(17, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-17 14:52:55'),
(18, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-17 14:53:14'),
(19, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-17 14:56:21'),
(20, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-17 15:15:00'),
(21, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 21:51:13'),
(22, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 13:12:45'),
(23, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 13:13:31'),
(24, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 13:14:23'),
(25, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 13:14:53'),
(26, 14, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 13:20:59'),
(27, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 13:21:17'),
(28, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 14:27:51'),
(29, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 14:28:27'),
(30, 15, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 14:42:57'),
(31, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 14:43:53'),
(32, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 20:31:14'),
(33, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 21:31:09');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_cart`
--
ALTER TABLE `tbl_cart`
  ADD CONSTRAINT `tbl_cart_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `tbl_cart` (`cart_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_cart_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tbl_cart` (`cart_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_customert_payment`
--
ALTER TABLE `tbl_customert_payment`
  ADD CONSTRAINT `tbl_customert_payment_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customert_payment` (`cust_add_payment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_customert_payment_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `tbl_customert_payment` (`cust_add_payment_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
