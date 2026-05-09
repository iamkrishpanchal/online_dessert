-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 30, 2026 at 05:11 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

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
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT NULL,
  `order_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`admin_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_admin`
--

INSERT INTO `tbl_admin` (`admin_id`, `admin_name`, `admin_email`, `admin_password`, `admin_contact`, `reset_token`, `token_expiry`) VALUES
(1, 'Admin', 'admin@dessert.com', 'admin123', '1234567890', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_admin_earnings`
--

DROP TABLE IF EXISTS `tbl_admin_earnings`;
CREATE TABLE IF NOT EXISTS `tbl_admin_earnings` (
  `earning_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `admin_id` int NOT NULL DEFAULT '1',
  `order_amount` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '15.00',
  `commission_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`earning_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_admin_earnings`
--

INSERT INTO `tbl_admin_earnings` (`earning_id`, `order_id`, `admin_id`, `order_amount`, `commission_rate`, `commission_amount`, `created_at`) VALUES
(43, 201, 1, 448.88, 15.00, 67.33, '2026-04-20 05:31:17'),
(44, 204, 1, 698.25, 15.00, 104.74, '2026-04-20 05:56:44'),
(45, 203, 1, 798.00, 15.00, 119.70, '2026-04-20 05:57:51'),
(46, 205, 1, 359.10, 15.00, 53.87, '2026-04-20 08:23:35');

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
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(31, 'Pavlova', '1', 14, 'Pavlova is a light, airy meringue dessert that has a crisp outer shell and a soft, marshmallow-like center inside. It is usually topped with fresh whipped cream and a generous layer of fresh fruits like strawberries, kiwi, mango, or berries.', '1771777275_Delicious Pavlova Recipe with Crispy Meringue and Fresh Fruit Toppings for Every Occasion.jpg'),
(27, 'Pastries', '1', 14, 'Mini treats packed with big flavors—perfect for quick dessert cravings.', '1770406257_2aeddb3628e19bc72fd4c076aa7bf38e.jpg'),
(29, 'Ice Cream', '0', 13, 'Ice cream is a creamy, frozen dessert made from rich milk, fresh cream, and premium ingredients blended to perfection.', '1771686159_download (7).jpg'),
(30, 'Puddings & Custards', '1', 13, 'Puddings & Custards are soft, creamy desserts known for their smooth texture and rich taste. They are usually made with milk or cream as the base and thickened using ingredients like cornflour, eggs, or gelatin. These desserts are loved for their melt-in-the-mouth feel and comforting sweetness.', '1771770668_La Recette du Crème Caramel Classique Soyeux et Facile à Réussir - Taste Dessert France.jpg'),
(32, 'Waffles', '1', 14, 'Waffles are crispy, golden-brown desserts made from a slightly sweet batter cooked in a waffle iron that gives them their signature square grid pattern.\r\nThey are usually served warm and fluffy inside, while the outside stays lightly crisp.', '1771777388_Heart-Shaped Waffles with Chocolate Sauce (Easy & Romantic Breakfast).jpg'),
(33, 'Thickshakes', '1', 16, 'Rich, creamy, and extra thick — a smooth dessert drink blended to perfection. 🥤✨', '1771782884_Ultimate Chocolate Overload Shake 🍫🍦.jpg'),
(38, 'Thickshakes', '1', 24, 'jdcfjdhchcc', '1774425789_Nutella Shake Nutella Fix.jpg'),
(41, 'ice cream scoops', '1', 36, 'A classic dessert made with rich and creamy ice cream served in single or multiple scoops. Customers can choose their favorite flavor and enjoy it in a bowl or cone. It is simple, refreshing, and perfect for any time of the day', '1775564814_WhatsApp Image 2026-04-07 at 5.56.09 PM.jpeg'),
(43, 'Falooda', '1', 36, 'Falooda is a rich and refreshing dessert drink made with layers of sweet rose milk, soft vermicelli (sev), basil seeds (sabja), and creamy ice cream. It is served in a tall glass and topped with rose syrup, jelly, dry fruits, and sometimes a scoop of vanilla ice cream. Falooda is popular for its sweet taste, colorful layers, and cool refreshing flavor, making it a perfect dessert for hot days.', '1775566082_WhatsApp Image 2026-04-07 at 6.16.16 PM (1).jpeg'),
(44, 'Spanish Dessert', '1', 37, 'authentic spanish dessert', '1775568307_Crema catalana 🍮.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_contacts`
--

DROP TABLE IF EXISTS `tbl_contacts`;
CREATE TABLE IF NOT EXISTS `tbl_contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_contacts`
--

INSERT INTO `tbl_contacts` (`id`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(1, 'Krushiv Salunke', 'salunkekrushiv1@gmail.com', 'bbvc', 'good products', '2026-04-11 14:45:16');

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
-- Table structure for table `tbl_notifications`
--

DROP TABLE IF EXISTS `tbl_notifications`;
CREATE TABLE IF NOT EXISTS `tbl_notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('unread','read') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'unread',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `vendor_id` int DEFAULT NULL,
  `admin_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `user_idx` (`user_id`),
  KEY `order_idx` (`order_id`),
  KEY `notif_vendor_idx` (`vendor_id`),
  KEY `notif_admin_idx` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=788 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_notifications`
--

INSERT INTO `tbl_notifications` (`notification_id`, `user_id`, `order_id`, `title`, `message`, `status`, `created_at`, `vendor_id`, `admin_id`, `updated_at`) VALUES
(416, NULL, 144, 'New Order Received', 'A new order #ORD1776026617510 has been placed and is ready for processing.', 'unread', '2026-04-12 20:43:37', 13, NULL, NULL),
(419, NULL, 144, 'Order #ORD1776026617510 Update', 'Order #ORD1776026617510: Rider accepted the order and picked it up.', 'unread', '2026-04-12 20:45:08', 13, NULL, NULL),
(421, NULL, 144, 'Order #ORD1776026617510 Update', 'Order #ORD1776026617510: Rider is en route to deliver the order.', 'unread', '2026-04-12 20:45:10', 13, NULL, NULL),
(423, NULL, 144, 'Order #ORD1776026617510 Update', 'Order #ORD1776026617510: Order delivered successfully.', 'unread', '2026-04-12 20:45:14', 13, NULL, NULL),
(425, NULL, 145, 'New Order Received', 'A new order #ORD1776063911478 has been placed and is ready for processing.', 'unread', '2026-04-13 07:05:11', 13, NULL, NULL),
(428, NULL, 145, 'Order #ORD1776063911478 Update', 'Order #ORD1776063911478: Rider accepted the order and picked it up.', 'unread', '2026-04-13 07:05:50', 13, NULL, NULL),
(430, NULL, 145, 'Order #ORD1776063911478 Update', 'Order #ORD1776063911478: Rider is en route to deliver the order.', 'unread', '2026-04-13 07:05:53', 13, NULL, NULL),
(432, NULL, 146, 'New Order Received', 'A new order #ORD1776063992988 has been placed and is ready for processing.', 'unread', '2026-04-13 07:06:32', 36, NULL, NULL),
(435, NULL, 145, 'Order #ORD1776063911478 Update', 'Order #ORD1776063911478: Order delivered successfully.', 'unread', '2026-04-13 07:07:01', 13, NULL, NULL),
(437, NULL, 146, 'Order #ORD1776063992988 Update', 'Order #ORD1776063992988: Rider accepted the order and picked it up.', 'unread', '2026-04-13 07:09:32', 36, NULL, NULL),
(439, NULL, 146, 'Order #ORD1776063992988 Update', 'Order #ORD1776063992988: Rider is en route to deliver the order.', 'unread', '2026-04-13 07:09:35', 36, NULL, NULL),
(441, NULL, 146, 'Order #ORD1776063992988 Update', 'Order #ORD1776063992988: Order delivered successfully.', 'unread', '2026-04-13 07:09:37', 36, NULL, NULL),
(443, NULL, 147, 'New Order Received', 'A new order #ORD1776091935431 has been placed and is ready for processing.', 'unread', '2026-04-13 14:52:15', 13, NULL, NULL),
(445, NULL, 148, 'New Order Received', 'A new order #ORD1776091935482 has been placed and is ready for processing.', 'unread', '2026-04-13 14:52:15', 36, NULL, NULL),
(447, NULL, 149, 'New Order Received', 'A new order #ORD1776091935233 has been placed and is ready for processing.', 'unread', '2026-04-13 14:52:15', 37, NULL, NULL),
(449, NULL, 150, 'New Order Received', 'A new order #ORD1776091935231 has been placed and is ready for processing.', 'unread', '2026-04-13 14:52:15', 15, NULL, NULL),
(451, NULL, 151, 'New Order Received', 'A new order #ORD1776095301860 has been placed. Check your items in the order.', 'unread', '2026-04-13 15:48:22', 13, NULL, NULL),
(452, NULL, 151, 'New Order Received', 'A new order #ORD1776095301860 has been placed. Check your items in the order.', 'unread', '2026-04-13 15:48:22', 37, NULL, NULL),
(453, NULL, 151, 'New Order Received', 'A new order #ORD1776095301860 has been placed. Check your items in the order.', 'unread', '2026-04-13 15:48:22', 15, NULL, NULL),
(455, NULL, 152, 'New Order Received', 'A new order #ORD1776095727626 has been placed. Check your items in the order.', 'unread', '2026-04-13 15:55:27', 13, NULL, NULL),
(456, NULL, 152, 'New Order Received', 'A new order #ORD1776095727626 has been placed. Check your items in the order.', 'unread', '2026-04-13 15:55:27', 36, NULL, NULL),
(457, NULL, 152, 'New Order Received', 'A new order #ORD1776095727626 has been placed. Check your items in the order.', 'unread', '2026-04-13 15:55:27', 37, NULL, NULL),
(458, NULL, 152, 'New Order Received', 'A new order #ORD1776095727626 has been placed. Check your items in the order.', 'unread', '2026-04-13 15:55:27', 15, NULL, NULL),
(464, NULL, 153, 'New Order Received', 'A new order #ORD1776104856782 has been placed. Check your items in the order.', 'unread', '2026-04-13 18:27:36', 14, NULL, NULL),
(467, NULL, 153, 'Order #ORD1776104856782 Update', 'Order #ORD1776104856782: Rider accepted the order and picked it up.', 'unread', '2026-04-13 18:28:49', 14, NULL, NULL),
(469, NULL, 153, 'Order #ORD1776104856782 Update', 'Order #ORD1776104856782: Rider is en route to deliver the order.', 'unread', '2026-04-13 18:28:50', 14, NULL, NULL),
(471, NULL, 153, 'Order #ORD1776104856782 Update', 'Order #ORD1776104856782: Order delivered successfully.', 'unread', '2026-04-13 18:28:52', 14, NULL, NULL),
(473, NULL, 154, 'New Order Received', 'A new order #ORD1776172503653 has been placed. Check your items in the order.', 'unread', '2026-04-14 13:15:03', 14, NULL, NULL),
(475, NULL, 155, 'New Order Received', 'A new order #ORD1776172576801 has been placed. Check your items in the order.', 'unread', '2026-04-14 13:16:16', 14, NULL, NULL),
(478, NULL, 155, 'Order #ORD1776172576801 Update', 'Order #ORD1776172576801: Rider accepted the order and picked it up.', 'unread', '2026-04-14 13:17:29', 14, NULL, NULL),
(480, NULL, 155, 'Order #ORD1776172576801 Update', 'Order #ORD1776172576801: Rider is en route to deliver the order.', 'unread', '2026-04-14 13:17:31', 14, NULL, NULL),
(482, NULL, 155, 'Order #ORD1776172576801 Update', 'Order #ORD1776172576801: Order delivered successfully.', 'unread', '2026-04-14 13:17:33', 14, NULL, NULL),
(484, NULL, 156, 'New Order Received', 'A new order #ORD1776177741929 has been placed. Check your items in the order.', 'unread', '2026-04-14 14:42:21', 14, NULL, NULL),
(487, NULL, 156, 'Order #ORD1776177741929 Update', 'Order #ORD1776177741929: Rider accepted the order and picked it up.', 'unread', '2026-04-14 14:45:25', 14, NULL, NULL),
(489, NULL, 156, 'Order #ORD1776177741929 Update', 'Order #ORD1776177741929: Rider is en route to deliver the order.', 'unread', '2026-04-14 14:45:27', 14, NULL, NULL),
(491, NULL, 156, 'Order #ORD1776177741929 Update', 'Order #ORD1776177741929: Order delivered successfully.', 'unread', '2026-04-14 14:45:30', 14, NULL, NULL),
(493, NULL, 157, 'New Order Received', 'A new order #ORD1776178627516 has been placed. Check your items in the order.', 'unread', '2026-04-14 14:57:07', 14, NULL, NULL),
(496, NULL, 157, 'Order #ORD1776178627516 Update', 'Order #ORD1776178627516: Rider accepted the order and picked it up.', 'unread', '2026-04-14 14:57:37', 14, NULL, NULL),
(498, NULL, 157, 'Order #ORD1776178627516 Update', 'Order #ORD1776178627516: Rider is en route to deliver the order.', 'unread', '2026-04-14 14:57:39', 14, NULL, NULL),
(500, NULL, 157, 'Order #ORD1776178627516 Update', 'Order #ORD1776178627516: Order delivered successfully.', 'unread', '2026-04-14 14:57:41', 14, NULL, NULL),
(502, NULL, 158, 'New Order Received', 'A new order #ORD1776178890402 has been placed. Check your items in the order.', 'unread', '2026-04-14 15:01:30', 14, NULL, NULL),
(505, NULL, 158, 'Order #ORD1776178890402 Update', 'Order #ORD1776178890402: Rider accepted the order and picked it up.', 'unread', '2026-04-14 15:02:10', 14, NULL, NULL),
(507, NULL, 158, 'Order #ORD1776178890402 Update', 'Order #ORD1776178890402: Rider is en route to deliver the order.', 'unread', '2026-04-14 15:02:31', 14, NULL, NULL),
(509, NULL, 158, 'Order #ORD1776178890402 Update', 'Order #ORD1776178890402: Order delivered successfully.', 'unread', '2026-04-14 15:02:34', 14, NULL, NULL),
(511, NULL, 159, 'New Order Received', 'A new order #ORD1776179325576 has been placed. Check your items in the order.', 'unread', '2026-04-14 15:08:45', 14, NULL, NULL),
(514, NULL, 159, 'Order #ORD1776179325576 Update', 'Order #ORD1776179325576: Rider accepted the order and picked it up.', 'unread', '2026-04-14 15:09:09', 14, NULL, NULL),
(516, NULL, 159, 'Order #ORD1776179325576 Update', 'Order #ORD1776179325576: Rider is en route to deliver the order.', 'unread', '2026-04-14 15:09:11', 14, NULL, NULL),
(518, NULL, 159, 'Order #ORD1776179325576 Update', 'Order #ORD1776179325576: Order delivered successfully.', 'unread', '2026-04-14 15:09:13', 14, NULL, NULL),
(520, NULL, 160, 'New Order Received', 'A new order #ORD1776179520926 has been placed. Check your items in the order.', 'unread', '2026-04-14 15:12:00', 15, NULL, NULL),
(523, NULL, 160, 'Order #ORD1776179520926 Update', 'Order #ORD1776179520926: Rider accepted the order and picked it up.', 'unread', '2026-04-14 15:13:07', 15, NULL, NULL),
(525, NULL, 160, 'Order #ORD1776179520926 Update', 'Order #ORD1776179520926: Rider is en route to deliver the order.', 'unread', '2026-04-14 15:13:10', 15, NULL, NULL),
(527, NULL, 160, 'Order #ORD1776179520926 Update', 'Order #ORD1776179520926: Order delivered successfully.', 'unread', '2026-04-14 15:13:13', 15, NULL, NULL),
(529, NULL, 161, 'New Order Received', 'A new order #ORD1776180200404 has been placed. Check your items in the order.', 'unread', '2026-04-14 15:23:20', 15, NULL, NULL),
(532, NULL, 161, 'Order #ORD1776180200404 Update', 'Order #ORD1776180200404: Rider accepted the order and picked it up.', 'unread', '2026-04-14 15:23:51', 15, NULL, NULL),
(534, NULL, 161, 'Order #ORD1776180200404 Update', 'Order #ORD1776180200404: Rider is en route to deliver the order.', 'unread', '2026-04-14 15:23:53', 15, NULL, NULL),
(536, NULL, 161, 'Order #ORD1776180200404 Update', 'Order #ORD1776180200404: Order delivered successfully.', 'unread', '2026-04-14 15:23:55', 15, NULL, NULL),
(538, NULL, 162, 'New Order Received', 'A new order #ORD1776252220556 has been placed. Check your items in the order.', 'unread', '2026-04-15 11:23:40', 13, NULL, NULL),
(539, NULL, 162, 'New Order Received', 'A new order #ORD1776252220556 has been placed. Check your items in the order.', 'unread', '2026-04-15 11:23:40', 37, NULL, NULL),
(540, NULL, 162, 'New Order Received', 'A new order #ORD1776252220556 has been placed. Check your items in the order.', 'unread', '2026-04-15 11:23:40', 15, NULL, NULL),
(542, NULL, 163, 'New Order Received', 'A new order #ORD1776252303523 has been placed. Check your items in the order.', 'unread', '2026-04-15 11:25:03', 13, NULL, NULL),
(543, NULL, 163, 'New Order Received', 'A new order #ORD1776252303523 has been placed. Check your items in the order.', 'unread', '2026-04-15 11:25:03', 37, NULL, NULL),
(544, NULL, 163, 'New Order Received', 'A new order #ORD1776252303523 has been placed. Check your items in the order.', 'unread', '2026-04-15 11:25:03', 15, NULL, NULL),
(546, NULL, 164, 'New Order Received', 'A new order #ORD1776252337880 has been placed. Check your items in the order.', 'unread', '2026-04-15 11:25:37', 13, NULL, NULL),
(547, NULL, 164, 'New Order Received', 'A new order #ORD1776252337880 has been placed. Check your items in the order.', 'unread', '2026-04-15 11:25:37', 37, NULL, NULL),
(548, NULL, 164, 'New Order Received', 'A new order #ORD1776252337880 has been placed. Check your items in the order.', 'unread', '2026-04-15 11:25:37', 15, NULL, NULL),
(553, NULL, 165, 'New Order Received', 'A new order #ORD1776252811911 has been placed. Check your items in the order.', 'unread', '2026-04-15 11:33:31', 14, NULL, NULL),
(554, NULL, 165, 'New Order Received', 'A new order #ORD1776252811911 has been placed. Check your items in the order.', 'unread', '2026-04-15 11:33:31', 36, NULL, NULL),
(555, NULL, 165, 'New Order Received', 'A new order #ORD1776252811911 has been placed. Check your items in the order.', 'unread', '2026-04-15 11:33:31', 37, NULL, NULL),
(561, NULL, 166, 'New Order Received', 'A new order #ORD1776271532931 has been placed. Check your items in the order.', 'unread', '2026-04-15 16:45:32', 36, NULL, NULL),
(562, NULL, 166, 'New Order Received', 'A new order #ORD1776271532931 has been placed. Check your items in the order.', 'unread', '2026-04-15 16:45:32', 13, NULL, NULL),
(568, NULL, 167, 'New Order Received', 'A new order #ORD1776273309576 has been placed. Check your items in the order.', 'unread', '2026-04-15 17:15:09', 13, NULL, NULL),
(569, NULL, 167, 'New Order Received', 'A new order #ORD1776273309576 has been placed. Check your items in the order.', 'unread', '2026-04-15 17:15:09', 36, NULL, NULL),
(571, NULL, 168, 'New Order Received', 'A new order #ORD1776274601970 has been placed. Check your items in the order.', 'unread', '2026-04-15 17:36:41', 15, NULL, NULL),
(572, NULL, 168, 'New Order Received', 'A new order #ORD1776274601970 has been placed. Check your items in the order.', 'unread', '2026-04-15 17:36:41', 14, NULL, NULL),
(573, NULL, 168, 'New Order Received', 'A new order #ORD1776274601970 has been placed. Check your items in the order.', 'unread', '2026-04-15 17:36:41', 13, NULL, NULL),
(579, NULL, 169, 'New Order Received', 'A new order #ORD1776278871382 has been placed. Check your items in the order.', 'unread', '2026-04-15 18:47:52', 13, NULL, NULL),
(582, NULL, 169, 'Order #ORD1776278871382 Update', 'Order #ORD1776278871382: Rider accepted the order and picked it up.', 'unread', '2026-04-15 18:50:37', 13, NULL, NULL),
(584, NULL, 169, 'Order #ORD1776278871382 Update', 'Order #ORD1776278871382: Rider is en route to deliver the order.', 'unread', '2026-04-15 18:50:39', 13, NULL, NULL),
(586, NULL, 169, 'Order #ORD1776278871382 Update', 'Order #ORD1776278871382: Order delivered successfully.', 'unread', '2026-04-15 18:50:42', 13, NULL, NULL),
(588, NULL, 170, 'New Order Received', 'A new order #ORD1776440763938 has been placed. Check your items in the order.', 'unread', '2026-04-17 15:46:03', 15, NULL, NULL),
(590, NULL, 171, 'New Order Received', 'A new order #ORD1776441060148 has been placed. Check your items in the order.', 'unread', '2026-04-17 15:51:00', 15, NULL, NULL),
(592, NULL, 172, 'New Order Received', 'A new order #ORD1776441128972 has been placed. Check your items in the order.', 'unread', '2026-04-17 15:52:08', 15, NULL, NULL),
(594, NULL, 173, 'New Order Received', 'A new order #ORD1776441258562 has been placed. Check your items in the order.', 'unread', '2026-04-17 15:54:18', 15, NULL, NULL),
(596, NULL, 174, 'New Order Received', 'A new order #ORD1776441784409 has been placed. Check your items in the order.', 'unread', '2026-04-17 16:03:04', 15, NULL, NULL),
(598, NULL, 175, 'New Order Received', 'A new order #ORD1776441793923 has been placed. Check your items in the order.', 'unread', '2026-04-17 16:03:13', 15, NULL, NULL),
(600, NULL, 176, 'New Order Received', 'A new order #ORD1776441805554 has been placed. Check your items in the order.', 'unread', '2026-04-17 16:03:25', 15, NULL, NULL),
(602, NULL, 177, 'New Order Received', 'A new order #ORD1776441922170 has been placed. Check your items in the order.', 'unread', '2026-04-17 16:05:22', 15, NULL, NULL),
(604, NULL, 178, 'New Order Received', 'A new order #ORD1776441949651 has been placed. Check your items in the order.', 'unread', '2026-04-17 16:05:49', 15, NULL, NULL),
(606, NULL, 179, 'New Order Received', 'A new order #ORD1776442092365 has been placed. Check your items in the order.', 'unread', '2026-04-17 16:08:12', 15, NULL, NULL),
(608, NULL, 180, 'New Order Received', 'A new order #ORD1776442224287 has been placed. Check your items in the order.', 'unread', '2026-04-17 16:10:24', 15, NULL, NULL),
(610, NULL, 181, 'New Order Received', 'A new order #ORD1776442569934 has been placed. Check your items in the order.', 'unread', '2026-04-17 16:16:09', 37, NULL, NULL),
(612, NULL, 182, 'New Order Received', 'A new order #ORD1776442678105 has been placed. Check your items in the order.', 'unread', '2026-04-17 16:17:58', 37, NULL, NULL),
(614, NULL, 182, 'hetansh rejected order #ORD1776442678105', 'hetansh rejected this order for Krushiv Salunke.', 'unread', '2026-04-17 16:20:41', NULL, 1, NULL),
(616, NULL, 182, 'Order #ORD1776442678105 Update', 'Order #ORD1776442678105: Rider rejected the order assignment.', 'unread', '2026-04-17 16:20:41', 37, NULL, NULL),
(619, NULL, 182, 'Order #ORD1776442678105 Update', 'Order #ORD1776442678105: Rider accepted the order and picked it up.', 'unread', '2026-04-17 16:21:42', 37, NULL, NULL),
(621, NULL, 182, 'Order #ORD1776442678105 Update', 'Order #ORD1776442678105: Rider is en route to deliver the order.', 'unread', '2026-04-17 16:23:03', 37, NULL, NULL),
(623, NULL, 182, 'Order #ORD1776442678105 Update', 'Order #ORD1776442678105: Order delivered successfully.', 'unread', '2026-04-17 16:23:06', 37, NULL, NULL),
(625, NULL, 183, 'New Order Received', 'A new order #ORD1776449619605 has been placed. Check your items in the order.', 'unread', '2026-04-17 18:13:39', 37, NULL, NULL),
(627, NULL, 183, 'Order Cancelled', 'Order #ORD1776449619605 has been cancelled by the customer.', 'unread', '2026-04-17 18:15:55', 37, NULL, NULL),
(629, NULL, 184, 'New Order Received', 'A new order #ORD1776449868139 has been placed. Check your items in the order.', 'unread', '2026-04-17 18:17:48', 36, NULL, NULL),
(632, NULL, 184, 'Order #ORD1776449868139 Update', 'Order #ORD1776449868139: Rider accepted the order and picked it up.', 'unread', '2026-04-17 18:19:06', 36, NULL, NULL),
(634, NULL, 184, 'Order #ORD1776449868139 Update', 'Order #ORD1776449868139: Rider is en route to deliver the order.', 'unread', '2026-04-17 18:21:06', 36, NULL, NULL),
(636, NULL, 184, 'Order #ORD1776449868139 Update', 'Order #ORD1776449868139: Order delivered successfully.', 'unread', '2026-04-17 18:21:09', 36, NULL, NULL),
(638, NULL, 185, 'New Order Received', 'A new order #ORD1776452369920 has been placed. Check your items in the order.', 'unread', '2026-04-17 18:59:29', 13, NULL, NULL),
(641, NULL, 185, 'Order #ORD1776452369920 Update', 'Order #ORD1776452369920: Rider accepted the order and picked it up.', 'unread', '2026-04-17 19:00:28', 13, NULL, NULL),
(643, NULL, 185, 'Order #ORD1776452369920 Update', 'Order #ORD1776452369920: Rider is en route to deliver the order.', 'unread', '2026-04-17 19:00:30', 13, NULL, NULL),
(645, NULL, 185, 'Order #ORD1776452369920 Update', 'Order #ORD1776452369920: Order delivered successfully.', 'unread', '2026-04-17 19:00:31', 13, NULL, NULL),
(647, NULL, 186, 'New Order Received', 'A new order #ORD1776532368981 has been placed. Check your items in the order.', 'unread', '2026-04-18 17:12:48', 12, NULL, NULL),
(650, NULL, 186, 'Order #ORD1776532368981 Update', 'Order #ORD1776532368981: Rider accepted the order and picked it up.', 'unread', '2026-04-18 17:13:28', 12, NULL, NULL),
(652, NULL, 186, 'Order #ORD1776532368981 Update', 'Order #ORD1776532368981: Rider is en route to deliver the order.', 'unread', '2026-04-18 17:13:30', 12, NULL, NULL),
(654, NULL, 186, 'Order #ORD1776532368981 Update', 'Order #ORD1776532368981: Order delivered successfully.', 'unread', '2026-04-18 17:13:31', 12, NULL, NULL),
(656, NULL, 187, 'New Order Received', 'A new order #ORD1776532696532 has been placed. Check your items in the order.', 'unread', '2026-04-18 17:18:16', 15, NULL, NULL),
(659, NULL, 187, 'Order #ORD1776532696532 Update', 'Order #ORD1776532696532: Rider accepted the order and picked it up.', 'unread', '2026-04-18 17:18:45', 15, NULL, NULL),
(661, NULL, 187, 'Order #ORD1776532696532 Update', 'Order #ORD1776532696532: Rider is en route to deliver the order.', 'unread', '2026-04-18 17:18:47', 15, NULL, NULL),
(663, NULL, 187, 'Order #ORD1776532696532 Update', 'Order #ORD1776532696532: Order delivered successfully.', 'unread', '2026-04-18 17:18:49', 15, NULL, NULL),
(665, NULL, 188, 'New Order Received', 'A new order #ORD1776533284398 has been placed. Check your items in the order.', 'unread', '2026-04-18 17:28:04', 12, NULL, NULL),
(668, NULL, 188, 'Order #ORD1776533284398 Update', 'Order #ORD1776533284398: Rider accepted the order and picked it up.', 'unread', '2026-04-18 17:28:23', 12, NULL, NULL),
(670, NULL, 188, 'Order #ORD1776533284398 Update', 'Order #ORD1776533284398: Rider is en route to deliver the order.', 'unread', '2026-04-18 17:28:25', 12, NULL, NULL),
(672, NULL, 188, 'Order #ORD1776533284398 Update', 'Order #ORD1776533284398: Order delivered successfully.', 'unread', '2026-04-18 17:28:26', 12, NULL, NULL),
(674, NULL, 189, 'New Order Received', 'A new order #ORD1776533403930 has been placed. Check your items in the order.', 'unread', '2026-04-18 17:30:03', 15, NULL, NULL),
(677, NULL, 189, 'Order #ORD1776533403930 Update', 'Order #ORD1776533403930: Rider accepted the order and picked it up.', 'unread', '2026-04-18 17:31:22', 15, NULL, NULL),
(679, NULL, 189, 'Order #ORD1776533403930 Update', 'Order #ORD1776533403930: Rider is en route to deliver the order.', 'unread', '2026-04-18 17:31:24', 15, NULL, NULL),
(681, NULL, 189, 'Order #ORD1776533403930 Update', 'Order #ORD1776533403930: Order delivered successfully.', 'unread', '2026-04-18 17:31:25', 15, NULL, NULL),
(683, NULL, 190, 'New Order Received', 'A new order #ORD1776533609880 has been placed. Check your items in the order.', 'unread', '2026-04-18 17:33:29', 15, NULL, NULL),
(686, NULL, 190, 'Order #ORD1776533609880 Update', 'Order #ORD1776533609880: Rider accepted the order and picked it up.', 'unread', '2026-04-18 17:33:45', 15, NULL, NULL),
(688, NULL, 190, 'Order #ORD1776533609880 Update', 'Order #ORD1776533609880: Rider is en route to deliver the order.', 'unread', '2026-04-18 17:33:47', 15, NULL, NULL),
(690, NULL, 190, 'Order #ORD1776533609880 Update', 'Order #ORD1776533609880: Order delivered successfully.', 'unread', '2026-04-18 17:33:48', 15, NULL, NULL),
(692, NULL, 191, 'New Order Received', 'A new order #ORD1776534797192 has been placed. Check your items in the order.', 'unread', '2026-04-18 17:53:17', 37, NULL, NULL),
(695, NULL, 191, 'Order #ORD1776534797192 Update', 'Order #ORD1776534797192: Rider accepted the order and picked it up.', 'unread', '2026-04-18 17:54:05', 37, NULL, NULL),
(697, NULL, 191, 'Order #ORD1776534797192 Update', 'Order #ORD1776534797192: Rider is en route to deliver the order.', 'unread', '2026-04-18 17:54:06', 37, NULL, NULL),
(699, NULL, 191, 'Order #ORD1776534797192 Update', 'Order #ORD1776534797192: Order delivered successfully.', 'unread', '2026-04-18 17:54:07', 37, NULL, NULL),
(701, NULL, 192, 'New Order Received', 'A new order #ORD1776535017966 has been placed. Check your items in the order.', 'unread', '2026-04-18 17:56:57', 15, NULL, NULL),
(704, NULL, 192, 'Order #ORD1776535017966 Update', 'Order #ORD1776535017966: Rider accepted the order and picked it up.', 'unread', '2026-04-18 17:57:13', 15, NULL, NULL),
(706, NULL, 192, 'Order #ORD1776535017966 Update', 'Order #ORD1776535017966: Rider is en route to deliver the order.', 'unread', '2026-04-18 17:57:15', 15, NULL, NULL),
(708, NULL, 192, 'Order #ORD1776535017966 Update', 'Order #ORD1776535017966: Order delivered successfully.', 'unread', '2026-04-18 17:57:16', 15, NULL, NULL),
(710, NULL, 193, 'New Order Received', 'A new order #ORD1776653812132 has been placed. Check your items in the order.', 'unread', '2026-04-20 02:56:52', 15, NULL, NULL),
(713, NULL, 194, 'New Order Received', 'A new order #ORD1776658304356 has been placed. Check your items in the order.', 'unread', '2026-04-20 04:11:44', 13, NULL, NULL),
(716, NULL, 195, 'New Order Received', 'A new order #ORD1776661021984 has been placed. Check your items in the order.', 'unread', '2026-04-20 04:57:01', 14, NULL, NULL),
(719, NULL, 195, 'Order #ORD1776661021984 Update', 'Order #ORD1776661021984: Rider accepted the order and picked it up.', 'unread', '2026-04-20 05:00:54', 14, NULL, NULL),
(721, NULL, 195, 'Order #ORD1776661021984 Update', 'Order #ORD1776661021984: Rider is en route to deliver the order.', 'unread', '2026-04-20 05:01:08', 14, NULL, NULL),
(723, NULL, 195, 'Order #ORD1776661021984 Update', 'Order #ORD1776661021984: Order delivered successfully.', 'unread', '2026-04-20 05:01:18', 14, NULL, NULL),
(725, NULL, 196, 'New Order Received', 'A new order #ORD1776661351984 has been placed. Check your items in the order.', 'unread', '2026-04-20 05:02:31', 14, NULL, NULL),
(728, NULL, 196, 'Order #ORD1776661351984 Update', 'Order #ORD1776661351984: Rider accepted the order and picked it up.', 'unread', '2026-04-20 05:03:01', 14, NULL, NULL),
(730, NULL, 196, 'Order #ORD1776661351984 Update', 'Order #ORD1776661351984: Rider is en route to deliver the order.', 'unread', '2026-04-20 05:03:11', 14, NULL, NULL),
(732, NULL, 196, 'Order #ORD1776661351984 Update', 'Order #ORD1776661351984: Order delivered successfully.', 'unread', '2026-04-20 05:03:13', 14, NULL, NULL),
(734, NULL, 197, 'New Order Received', 'A new order #ORD1776661532251 has been placed. Check your items in the order.', 'unread', '2026-04-20 05:05:32', 14, NULL, NULL),
(737, NULL, 197, 'Order #ORD1776661532251 Update', 'Order #ORD1776661532251: Rider accepted the order and picked it up.', 'unread', '2026-04-20 05:06:19', 14, NULL, NULL),
(739, NULL, 197, 'Order #ORD1776661532251 Update', 'Order #ORD1776661532251: Rider is en route to deliver the order.', 'unread', '2026-04-20 05:06:21', 14, NULL, NULL),
(741, NULL, 197, 'Order #ORD1776661532251 Update', 'Order #ORD1776661532251: Order delivered successfully.', 'unread', '2026-04-20 05:06:22', 14, NULL, NULL),
(742, 21, 198, 'Order Placed', 'Your order #ORD1776662128259 has been received and is confirmed.', 'unread', '2026-04-20 05:15:28', NULL, NULL, NULL),
(743, NULL, 198, 'New Order Received', 'A new order #ORD1776662128259 has been placed. Check your items in the order.', 'unread', '2026-04-20 05:15:28', 14, NULL, NULL),
(744, 20, 199, 'Order Placed', 'Your order #ORD1776662598479 has been received and is confirmed.', 'unread', '2026-04-20 05:23:18', NULL, NULL, NULL),
(745, NULL, 199, 'New Order Received', 'A new order #ORD1776662598479 has been placed. Check your items in the order.', 'unread', '2026-04-20 05:23:18', 14, NULL, NULL),
(746, 20, 200, 'Order Placed', 'Your order #ORD1776662624837 has been received and is confirmed.', 'unread', '2026-04-20 05:23:44', NULL, NULL, NULL),
(747, NULL, 200, 'New Order Received', 'A new order #ORD1776662624837 has been placed. Check your items in the order.', 'unread', '2026-04-20 05:23:44', 14, NULL, NULL),
(748, 22, 201, 'Order Placed', 'Your order #ORD1776663010885 has been received and is confirmed.', 'unread', '2026-04-20 05:30:10', NULL, NULL, NULL),
(749, NULL, 201, 'New Order Received', 'A new order #ORD1776663010885 has been placed. Check your items in the order.', 'unread', '2026-04-20 05:30:10', 12, NULL, NULL),
(751, 22, 201, 'Order #ORD1776663010885 Update', 'Order #ORD1776663010885: Rider accepted the order and picked it up.', 'unread', '2026-04-20 05:31:05', NULL, NULL, NULL),
(752, NULL, 201, 'Order #ORD1776663010885 Update', 'Order #ORD1776663010885: Rider accepted the order and picked it up.', 'unread', '2026-04-20 05:31:05', 12, NULL, NULL),
(753, 22, 201, 'Order #ORD1776663010885 Update', 'Order #ORD1776663010885: Rider is en route to deliver the order.', 'unread', '2026-04-20 05:31:16', NULL, NULL, NULL),
(754, NULL, 201, 'Order #ORD1776663010885 Update', 'Order #ORD1776663010885: Rider is en route to deliver the order.', 'unread', '2026-04-20 05:31:16', 12, NULL, NULL),
(755, 22, 201, 'Order #ORD1776663010885 Update', 'Order #ORD1776663010885: Order delivered successfully.', 'unread', '2026-04-20 05:31:17', NULL, NULL, NULL),
(756, NULL, 201, 'Order #ORD1776663010885 Update', 'Order #ORD1776663010885: Order delivered successfully.', 'unread', '2026-04-20 05:31:17', 12, NULL, NULL),
(757, 22, 202, 'Order Placed', 'Your order #ORD1776663150286 has been received and is confirmed.', 'unread', '2026-04-20 05:32:30', NULL, NULL, NULL),
(758, NULL, 202, 'New Order Received', 'A new order #ORD1776663150286 has been placed. Check your items in the order.', 'unread', '2026-04-20 05:32:30', 13, NULL, NULL),
(761, 21, 203, 'Order Placed', 'Your order #ORD1776664282780 has been received and is confirmed.', 'unread', '2026-04-20 05:51:22', NULL, NULL, NULL),
(762, NULL, 203, 'New Order Received', 'A new order #ORD1776664282780 has been placed. Check your items in the order.', 'unread', '2026-04-20 05:51:22', 13, NULL, NULL),
(763, 21, 204, 'Order Placed', 'Your order #ORD1776664505963 has been received and is confirmed.', 'unread', '2026-04-20 05:55:05', NULL, NULL, NULL),
(764, NULL, 204, 'New Order Received', 'A new order #ORD1776664505963 has been placed. Check your items in the order.', 'unread', '2026-04-20 05:55:05', 13, NULL, NULL),
(766, 21, 204, 'Order #ORD1776664505963 Update', 'Order #ORD1776664505963: Rider accepted the order and picked it up.', 'unread', '2026-04-20 05:56:39', NULL, NULL, NULL),
(767, NULL, 204, 'Order #ORD1776664505963 Update', 'Order #ORD1776664505963: Rider accepted the order and picked it up.', 'unread', '2026-04-20 05:56:39', 13, NULL, NULL),
(768, 21, 204, 'Order #ORD1776664505963 Update', 'Order #ORD1776664505963: Rider is en route to deliver the order.', 'unread', '2026-04-20 05:56:42', NULL, NULL, NULL),
(769, NULL, 204, 'Order #ORD1776664505963 Update', 'Order #ORD1776664505963: Rider is en route to deliver the order.', 'unread', '2026-04-20 05:56:42', 13, NULL, NULL),
(770, 21, 204, 'Order #ORD1776664505963 Update', 'Order #ORD1776664505963: Order delivered successfully.', 'unread', '2026-04-20 05:56:44', NULL, NULL, NULL),
(771, NULL, 204, 'Order #ORD1776664505963 Update', 'Order #ORD1776664505963: Order delivered successfully.', 'unread', '2026-04-20 05:56:44', 13, NULL, NULL),
(773, 21, 203, 'Order #ORD1776664282780 Update', 'Order #ORD1776664282780: Rider accepted the order and picked it up.', 'unread', '2026-04-20 05:57:48', NULL, NULL, NULL),
(774, NULL, 203, 'Order #ORD1776664282780 Update', 'Order #ORD1776664282780: Rider accepted the order and picked it up.', 'unread', '2026-04-20 05:57:48', 13, NULL, NULL),
(775, 21, 203, 'Order #ORD1776664282780 Update', 'Order #ORD1776664282780: Rider is en route to deliver the order.', 'unread', '2026-04-20 05:57:49', NULL, NULL, NULL),
(776, NULL, 203, 'Order #ORD1776664282780 Update', 'Order #ORD1776664282780: Rider is en route to deliver the order.', 'unread', '2026-04-20 05:57:50', 13, NULL, NULL),
(777, 21, 203, 'Order #ORD1776664282780 Update', 'Order #ORD1776664282780: Order delivered successfully.', 'unread', '2026-04-20 05:57:51', NULL, NULL, NULL),
(778, NULL, 203, 'Order #ORD1776664282780 Update', 'Order #ORD1776664282780: Order delivered successfully.', 'unread', '2026-04-20 05:57:51', 13, NULL, NULL),
(779, 21, 205, 'Order Placed', 'Your order #ORD1776672917654 has been received and is confirmed.', 'unread', '2026-04-20 08:15:17', NULL, NULL, NULL),
(780, NULL, 205, 'New Order Received', 'A new order #ORD1776672917654 has been placed. Check your items in the order.', 'unread', '2026-04-20 08:15:17', 12, NULL, NULL),
(782, 21, 205, 'Order #ORD1776672917654 Update', 'Order #ORD1776672917654: Rider accepted the order and picked it up.', 'unread', '2026-04-20 08:23:04', NULL, NULL, NULL),
(783, NULL, 205, 'Order #ORD1776672917654 Update', 'Order #ORD1776672917654: Rider accepted the order and picked it up.', 'unread', '2026-04-20 08:23:04', 12, NULL, NULL),
(784, 21, 205, 'Order #ORD1776672917654 Update', 'Order #ORD1776672917654: Rider is en route to deliver the order.', 'unread', '2026-04-20 08:23:23', NULL, NULL, NULL),
(785, NULL, 205, 'Order #ORD1776672917654 Update', 'Order #ORD1776672917654: Rider is en route to deliver the order.', 'unread', '2026-04-20 08:23:23', 12, NULL, NULL),
(786, 21, 205, 'Order #ORD1776672917654 Update', 'Order #ORD1776672917654: Order delivered successfully.', 'unread', '2026-04-20 08:23:35', NULL, NULL, NULL),
(787, NULL, 205, 'Order #ORD1776672917654 Update', 'Order #ORD1776672917654: Order delivered successfully.', 'unread', '2026-04-20 08:23:35', 12, NULL, NULL);

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
  `user_id` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `delivery_charges` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL,
  `delivery_address` text NOT NULL,
  `delivery_city` varchar(100) DEFAULT NULL,
  `delivery_pincode` varchar(10) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rider_id` int DEFAULT NULL,
  `delivery_status` varchar(50) DEFAULT 'not_assigned',
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `batch_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_orders`
--

INSERT INTO `tbl_orders` (`order_id`, `order_number`, `customer_name`, `customer_email`, `vendor_id`, `customer_phone`, `product_id`, `quantity`, `order_date`, `order_status`, `total_amount`, `payment_method`, `notes`, `user_id`, `subtotal`, `tax`, `delivery_charges`, `discount`, `delivery_address`, `delivery_city`, `delivery_pincode`, `phone`, `payment_status`, `created_at`, `updated_at`, `rider_id`, `delivery_status`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `batch_id`) VALUES
(198, 'ORD1776662128259', '', NULL, 14, NULL, NULL, 1, '2026-04-20 10:45:28', 'Confirmed', 848.00, '', NULL, 21, 760.00, 38.00, 50.00, 0.00, 'Palanpur cenal road', 'Surat', '', '8855223366', 'pending', '2026-04-20 05:15:28', '2026-04-20 05:15:28', NULL, 'not_assigned', NULL, NULL, NULL, NULL),
(199, 'ORD1776662598479', '', NULL, 14, NULL, NULL, 1, '2026-04-20 10:53:18', 'Confirmed', 299.38, '', NULL, 20, 237.50, 11.88, 50.00, 0.00, 'Nakshatra Platinum\r\nB6 504', 'Surat', '', '7788996633', 'pending', '2026-04-20 05:23:18', '2026-04-20 05:23:18', NULL, 'not_assigned', NULL, NULL, NULL, NULL),
(200, 'ORD1776662624837', '', NULL, 14, NULL, NULL, 1, '2026-04-20 10:53:44', 'Confirmed', 548.75, '', NULL, 20, 475.00, 23.75, 50.00, 0.00, 'Nakshatra Platinum\r\nB6 504', 'Surat', '', '7788996633', 'pending', '2026-04-20 05:23:44', '2026-04-20 05:35:43', 1, 'assigned', NULL, NULL, NULL, NULL),
(201, 'ORD1776663010885', '', NULL, 12, NULL, NULL, 1, '2026-04-20 11:00:10', 'Completed', 498.88, '', NULL, 22, 427.50, 21.38, 50.00, 0.00, 'Palanpur cenal road', 'Surat', '', '95784784515', 'Paid', '2026-04-20 05:30:10', '2026-04-20 05:31:17', 2, 'delivered', NULL, NULL, NULL, NULL),
(202, 'ORD1776663150286', '', NULL, 13, NULL, NULL, 1, '2026-04-20 11:02:30', 'Confirmed', 349.25, '', NULL, 22, 285.00, 14.25, 50.00, 0.00, 'Palanpur cenal road', 'Surat', '', '95784784515', 'pending', '2026-04-20 05:32:30', '2026-04-20 05:33:38', 2, 'assigned', NULL, NULL, NULL, NULL),
(203, 'ORD1776664282780', '', NULL, 13, NULL, NULL, 1, '2026-04-20 11:21:22', 'Completed', 848.00, '', NULL, 21, 760.00, 38.00, 50.00, 0.00, 'Palanpur cenal road', 'Surat', '', '8855223366', 'Paid', '2026-04-20 05:51:22', '2026-04-20 05:57:51', 3, 'delivered', NULL, 'pay_SfdcOxcgSpvZIO', NULL, NULL),
(204, 'ORD1776664505963', '', NULL, 13, NULL, NULL, 1, '2026-04-20 11:25:05', 'Completed', 748.25, '', NULL, 21, 665.00, 33.25, 50.00, 0.00, 'Nakshatra Platinum\r\nB6 504', 'Surat', '', '8855223366', 'Paid', '2026-04-20 05:55:05', '2026-04-20 05:56:44', 3, 'delivered', NULL, NULL, NULL, NULL),
(205, 'ORD1776672917654', '', NULL, 12, NULL, NULL, 1, '2026-04-20 13:45:17', 'Completed', 409.10, '', NULL, 21, 342.00, 17.10, 50.00, 0.00, 'Palanpur cenal road', 'Surat', '', '8855223366', 'Paid', '2026-04-20 08:15:17', '2026-04-20 08:23:35', 3, 'delivered', NULL, 'pay_Sfg4Tfpj0vrRyK', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_order_items`
--

DROP TABLE IF EXISTS `tbl_order_items`;
CREATE TABLE IF NOT EXISTS `tbl_order_items` (
  `order_item_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `special_instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`order_item_id`),
  KEY `order_idx` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=235 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_order_items`
--

INSERT INTO `tbl_order_items` (`order_item_id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`, `special_instructions`) VALUES
(26, 28, 92, 'Brownie Ice cream cake', 1, 620.00, 620.00, NULL),
(27, 29, 61, '. Butterscotch Crunch Cake', 1, 450.00, 450.00, NULL),
(28, 30, 57, 'Blueberry Pancake', 1, 280.00, 280.00, NULL),
(29, 31, 73, 'Chocolate Pancake', 1, 320.00, 320.00, NULL),
(30, 32, 92, 'Brownie Ice cream cake', 1, 620.00, 620.00, NULL),
(31, 33, 120, 'Banana Thickshake', 1, 300.00, 300.00, NULL),
(32, 34, 120, 'Banana Thickshake', 1, 300.00, 300.00, NULL),
(33, 35, 61, '. Butterscotch Crunch Cake', 3, 450.00, 1350.00, NULL),
(34, 36, 61, '. Butterscotch Crunch Cake', 1, 405.00, 405.00, NULL),
(35, 37, 55, 'Black Forest Cake', 1, 405.00, 405.00, NULL),
(36, 38, 57, 'Blueberry Pancake', 2, 350.00, 700.00, NULL),
(37, 39, 56, 'Chocolate Cookie', 1, 150.00, 150.00, NULL),
(38, 40, 64, 'Mango Magic Cake', 1, 585.00, 585.00, NULL),
(39, 41, 78, 'Black Current ', 2, 240.00, 480.00, NULL),
(40, 42, 73, 'Chocolate Pancake', 2, 320.00, 640.00, NULL),
(41, 43, 78, 'Black Current ', 1, 240.00, 240.00, NULL),
(42, 44, 57, 'Blueberry Pancake', 3, 280.00, 840.00, NULL),
(43, 45, 77, 'Chocolate Almond', 10, 160.00, 1600.00, NULL),
(44, 46, 77, 'Chocolate Almond', 10, 160.00, 1600.00, NULL),
(45, 47, 57, 'Blueberry Pancake', 3, 280.00, 840.00, NULL),
(46, 48, 76, 'Vanilla', 1, 120.00, 120.00, NULL),
(47, 49, 84, 'Mango Custard', 2, 280.00, 560.00, NULL),
(48, 50, 57, 'Blueberry Pancake', 2, 280.00, 560.00, NULL),
(49, 51, 78, 'Black Current ', 1, 240.00, 240.00, NULL),
(50, 52, 57, 'Blueberry Pancake', 1, 280.00, 280.00, NULL),
(51, 53, 110, 'Strawberry Cream Waffle', 1, 200.00, 200.00, NULL),
(52, 54, 78, 'Black Current ', 1, 240.00, 240.00, NULL),
(53, 55, 78, 'Black Current ', 2, 240.00, 480.00, NULL),
(54, 56, 55, 'Black Forest Cake', 1, 405.00, 405.00, NULL),
(55, 57, 106, 'Black Forest Pastry', 1, 450.00, 450.00, NULL),
(56, 58, 96, 'Biscoff Donut', 1, 332.50, 332.50, NULL),
(57, 59, 57, 'Blueberry Pancake', 1, 280.00, 280.00, NULL),
(58, 60, 68, 'Black Forest Pastry', 1, 108.00, 108.00, NULL),
(59, 61, 112, 'Belgian Chocolate Waffle', 1, 280.00, 280.00, NULL),
(60, 62, 61, '. Butterscotch Crunch Cake', 1, 405.00, 405.00, NULL),
(61, 63, 96, 'Biscoff Donut', 1, 332.50, 332.50, NULL),
(62, 64, 57, 'Blueberry Pancake', 2, 280.00, 560.00, NULL),
(63, 65, 57, 'Blueberry Pancake', 1, 332.50, 332.50, NULL),
(64, 66, 61, '. Butterscotch Crunch Cake', 2, 427.50, 855.00, NULL),
(65, 67, 61, '. Butterscotch Crunch Cake', 1, 427.50, 427.50, NULL),
(66, 67, 68, 'Black Forest Pastry', 1, 114.00, 114.00, NULL),
(67, 68, 68, 'Black Forest Pastry', 2, 114.00, 228.00, NULL),
(68, 69, 78, 'Black Current ', 1, 285.00, 285.00, NULL),
(69, 69, 82, 'Caramel Pudding', 1, 256.50, 256.50, NULL),
(70, 70, 73, 'Chocolate Pancake', 1, 380.00, 380.00, NULL),
(71, 70, 75, 'Chocolate chips Pancake', 1, 446.50, 446.50, NULL),
(72, 71, 92, 'Brownie Ice cream cake', 1, 589.00, 589.00, NULL),
(73, 72, 61, '. Butterscotch Crunch Cake', 2, 427.50, 855.00, NULL),
(74, 73, 112, 'Belgian Chocolate Waffle', 1, 266.00, 266.00, NULL),
(75, 74, 88, 'Cheesecake ', 1, 380.00, 380.00, NULL),
(76, 75, 78, 'Black Current ', 1, 285.00, 285.00, NULL),
(77, 76, 78, 'Black Current ', 1, 285.00, 285.00, NULL),
(78, 77, 78, 'Black Current ', 1, 285.00, 285.00, NULL),
(79, 78, 78, 'Black Current ', 1, 285.00, 285.00, NULL),
(80, 79, 112, 'Belgian Chocolate Waffle', 1, 266.00, 266.00, NULL),
(81, 80, 112, 'Belgian Chocolate Waffle', 1, 266.00, 266.00, NULL),
(82, 81, 112, 'Belgian Chocolate Waffle', 1, 266.00, 266.00, NULL),
(83, 82, 112, 'Belgian Chocolate Waffle', 1, 266.00, 266.00, NULL),
(84, 83, 94, 'Boston Cream Donut', 1, 256.50, 256.50, NULL),
(85, 84, 112, 'Belgian Chocolate Waffle', 1, 266.00, 266.00, NULL),
(86, 85, 94, 'Boston Cream Donut', 1, 256.50, 256.50, NULL),
(87, 86, 112, 'Belgian Chocolate Waffle', 1, 266.00, 266.00, NULL),
(88, 87, 94, 'Boston Cream Donut', 1, 256.50, 256.50, NULL),
(89, 88, 61, '. Butterscotch Crunch Cake', 1, 427.50, 427.50, NULL),
(90, 89, 63, 'Oreo Cookies & Cream Cake', 1, 475.00, 475.00, NULL),
(91, 90, 83, 'Strawberry custard', 1, 285.00, 285.00, NULL),
(92, 91, 57, 'Blueberry Pancake', 1, 332.50, 332.50, NULL),
(93, 92, 56, 'Chocolate Cookie', 1, 142.50, 142.50, NULL),
(94, 93, 55, 'Black Forest Cake', 1, 427.50, 427.50, NULL),
(95, 94, 61, '. Butterscotch Crunch Cake', 2, 427.50, 855.00, NULL),
(96, 95, 57, 'Blueberry Pancake', 1, 332.50, 332.50, NULL),
(97, 96, 78, 'Black Current ', 1, 285.00, 285.00, NULL),
(98, 97, 57, 'Blueberry Pancake', 1, 332.50, 332.50, NULL),
(99, 98, 78, 'Black Current ', 2, 285.00, 570.00, NULL),
(100, 99, 78, 'Black Current ', 2, 285.00, 570.00, NULL),
(101, 100, 77, 'Chocolate Almond', 1, 190.00, 190.00, NULL),
(102, 101, 78, 'Black Current ', 1, 285.00, 285.00, NULL),
(103, 102, 123, 'Basundi', 1, 190.00, 190.00, NULL),
(104, 103, 123, 'Basundi', 1, 190.00, 190.00, NULL),
(105, 104, 57, 'Blueberry Pancake', 2, 332.50, 665.00, NULL),
(106, 105, 61, '. Butterscotch Crunch Cake', 2, 427.50, 855.00, NULL),
(107, 106, 78, 'Black Current ', 1, 285.00, 285.00, NULL),
(108, 107, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(109, 108, 86, 'KitKat ', 2, 285.00, 570.00, NULL),
(110, 109, 78, 'Black Current ', 1, 285.00, 285.00, NULL),
(111, 110, 82, 'Caramel Pudding', 1, 256.50, 256.50, NULL),
(112, 111, 61, '. Butterscotch Crunch Cake', 2, 427.50, 855.00, NULL),
(113, 112, 61, '. Butterscotch Crunch Cake', 2, 427.50, 855.00, NULL),
(114, 113, 61, '. Butterscotch Crunch Cake', 3, 427.50, 1282.50, NULL),
(115, 114, 82, 'Caramel Pudding', 1, 256.50, 256.50, NULL),
(116, 115, 61, '. Butterscotch Crunch Cake', 1, 427.50, 427.50, NULL),
(117, 116, 75, 'Chocolate chips Pancake', 1, 446.50, 446.50, NULL),
(118, 117, 77, 'Chocolate Almond', 1, 190.00, 190.00, NULL),
(119, 118, 61, '. Butterscotch Crunch Cake', 1, 427.50, 427.50, NULL),
(120, 119, 73, 'Chocolate Pancake', 1, 380.00, 380.00, NULL),
(121, 120, 79, 'Ferrero Rocher', 1, 617.50, 617.50, NULL),
(122, 121, 83, 'Strawberry custard', 1, 285.00, 285.00, NULL),
(123, 122, 143, 'chocolate  Falooda', 1, 246.05, 246.05, NULL),
(124, 122, 137, 'chocolate scoops (3 scoops)', 1, 332.50, 332.50, NULL),
(125, 123, 141, 'Royal Falooda', 1, 236.55, 236.55, NULL),
(126, 123, 142, 'Mango Falooda', 1, 284.05, 284.05, NULL),
(127, 124, 136, 'venilla scoop (2 scoops)', 1, 237.50, 237.50, NULL),
(128, 125, 138, 'mango scoops (2 scoops)', 1, 380.00, 380.00, NULL),
(129, 126, 136, 'venilla scoop (2 scoops)', 1, 237.50, 237.50, NULL),
(130, 127, 139, 'kesar pista scoops (2 scoops)', 1, 475.00, 475.00, NULL),
(131, 128, 144, 'kulfi Falooda', 1, 322.05, 322.05, NULL),
(132, 129, 143, 'chocolate  Falooda', 10, 246.05, 2460.50, NULL),
(133, 130, 143, 'chocolate  Falooda', 1, 246.05, 246.05, NULL),
(134, 130, 141, 'Royal Falooda', 2, 236.55, 473.10, NULL),
(135, 130, 137, 'chocolate scoops (3 scoops)', 2, 332.50, 665.00, NULL),
(136, 131, 143, 'chocolate  Falooda', 2, 246.05, 492.10, NULL),
(137, 131, 137, 'chocolate scoops (3 scoops)', 2, 332.50, 665.00, NULL),
(138, 131, 142, 'Mango Falooda', 2, 284.05, 568.10, NULL),
(139, 132, 137, 'chocolate scoops (3 scoops)', 2, 332.50, 665.00, NULL),
(140, 133, 139, 'kesar pista scoops (2 scoops)', 12, 475.00, 5700.00, NULL),
(141, 134, 143, 'chocolate  Falooda', 10, 246.05, 2460.50, NULL),
(142, 135, 57, 'Blueberry Pancake', 2, 332.50, 665.00, NULL),
(143, 136, 57, 'Blueberry Pancake', 1, 332.50, 332.50, NULL),
(144, 137, 73, 'Chocolate Pancake', 1, 380.00, 380.00, NULL),
(145, 138, 82, 'Caramel Pudding', 2, 256.50, 513.00, NULL),
(146, 139, 147, 'Arroz con Leche', 1, 380.00, 380.00, NULL),
(147, 140, 57, 'Blueberry Pancake', 1, 332.50, 332.50, NULL),
(148, 141, 143, 'chocolate  Falooda', 1, 246.05, 246.05, NULL),
(149, 142, 143, 'chocolate  Falooda', 2, 246.05, 492.10, NULL),
(150, 143, 143, 'chocolate  Falooda', 1, 246.05, 246.05, NULL),
(151, 144, 78, 'Black Current ', 1, 285.00, 285.00, NULL),
(152, 145, 57, 'Blueberry Pancake', 1, 332.50, 332.50, NULL),
(153, 145, 73, 'Chocolate Pancake', 1, 380.00, 380.00, NULL),
(154, 146, 137, 'chocolate scoops (3 scoops)', 1, 332.50, 332.50, NULL),
(155, 147, 78, 'Black Current ', 1, 285.00, 285.00, NULL),
(156, 148, 139, 'kesar pista scoops (2 scoops)', 1, 475.00, 475.00, NULL),
(157, 149, 151, 'Ensaimada', 1, 465.50, 465.50, NULL),
(158, 150, 93, 'Chocolate Glazed  Donut', 1, 190.00, 190.00, NULL),
(159, 151, 78, 'Black Current ', 2, 285.00, 570.00, NULL),
(160, 151, 147, 'Arroz con Leche', 1, 380.00, 380.00, NULL),
(161, 151, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(162, 151, 94, 'Boston Cream Donut', 1, 256.50, 256.50, NULL),
(163, 152, 75, 'Chocolate chips Pancake', 1, 446.50, 446.50, NULL),
(164, 152, 136, 'venilla scoop (2 scoops)', 1, 237.50, 237.50, NULL),
(165, 152, 149, 'Leche Frita', 1, 455.05, 455.05, NULL),
(166, 152, 88, 'Cheesecake ', 1, 380.00, 380.00, NULL),
(167, 153, 108, 'Nuttela Waffle', 2, 152.00, 304.00, NULL),
(168, 154, 112, 'Belgian Chocolate Waffle', 2, 266.00, 532.00, NULL),
(169, 155, 58, 'Chocolate Donut', 2, 152.00, 304.00, NULL),
(170, 156, 58, 'Chocolate Donut', 2, 152.00, 304.00, NULL),
(171, 157, 58, 'Chocolate Donut', 2, 152.00, 304.00, NULL),
(172, 158, 58, 'Chocolate Donut', 2, 152.00, 304.00, NULL),
(173, 159, 58, 'Chocolate Donut', 2, 152.00, 304.00, NULL),
(174, 160, 97, 'Caramel Crunch Donut', 2, 361.00, 722.00, NULL),
(175, 161, 97, 'Caramel Crunch Donut', 2, 361.00, 722.00, NULL),
(176, 162, 81, 'Chocolate Pudding', 1, 237.50, 237.50, NULL),
(177, 162, 148, 'Turron', 1, 427.50, 427.50, NULL),
(178, 162, 90, 'Ferrero Rocher cake', 1, 646.00, 646.00, NULL),
(179, 163, 81, 'Chocolate Pudding', 1, 237.50, 237.50, NULL),
(180, 163, 148, 'Turron', 1, 427.50, 427.50, NULL),
(181, 163, 90, 'Ferrero Rocher cake', 1, 646.00, 646.00, NULL),
(182, 164, 81, 'Chocolate Pudding', 1, 237.50, 237.50, NULL),
(183, 164, 148, 'Turron', 1, 427.50, 427.50, NULL),
(184, 164, 90, 'Ferrero Rocher cake', 1, 646.00, 646.00, NULL),
(185, 165, 105, 'Butterscotch Pastry', 1, 380.00, 380.00, NULL),
(186, 165, 143, 'chocolate  Falooda', 1, 246.05, 246.05, NULL),
(187, 165, 150, 'Natillas', 1, 350.55, 350.55, NULL),
(188, 166, 137, 'chocolate scoops (3 scoops)', 2, 332.50, 665.00, NULL),
(189, 166, 77, 'Chocolate Almond', 1, 190.00, 190.00, NULL),
(190, 167, 81, 'Chocolate Pudding', 1, 237.50, 237.50, NULL),
(191, 167, 142, 'Mango Falooda', 1, 284.05, 284.05, NULL),
(192, 168, 86, 'KitKat ', 1, 285.00, 285.00, NULL),
(193, 168, 110, 'Strawberry Cream Waffle', 1, 190.00, 190.00, NULL),
(194, 168, 57, 'Blueberry Pancake', 1, 332.50, 332.50, NULL),
(195, 169, 57, 'Blueberry Pancake', 1, 332.50, 332.50, NULL),
(196, 170, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(197, 171, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(198, 172, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(199, 173, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(200, 174, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(201, 175, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(202, 176, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(203, 177, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(204, 178, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(205, 179, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(206, 180, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(207, 181, 151, 'Ensaimada', 1, 465.50, 465.50, NULL),
(208, 182, 149, 'Leche Frita', 1, 455.05, 455.05, NULL),
(209, 183, 147, 'Arroz con Leche', 1, 380.00, 380.00, NULL),
(210, 184, 143, 'chocolate  Falooda', 1, 246.05, 246.05, NULL),
(211, 185, 74, 'Nutella pancake', 3, 427.50, 1282.50, NULL),
(212, 185, 76, 'Vanilla', 1, 142.50, 142.50, NULL),
(213, 185, 84, 'Mango Custard', 1, 332.50, 332.50, NULL),
(214, 186, 61, '. Butterscotch Crunch Cake', 1, 427.50, 427.50, NULL),
(215, 187, 96, 'Biscoff Donut', 1, 332.50, 332.50, NULL),
(216, 188, 68, 'Black Forest Pastry', 1, 114.00, 114.00, NULL),
(217, 189, 96, 'Biscoff Donut', 1, 332.50, 332.50, NULL),
(218, 190, 88, 'Cheesecake ', 1, 380.00, 380.00, NULL),
(219, 191, 147, 'Arroz con Leche', 1, 380.00, 380.00, NULL),
(220, 192, 96, 'Biscoff Donut', 1, 332.50, 332.50, NULL),
(221, 193, 59, 'Biscoff Cake', 1, 427.50, 427.50, NULL),
(222, 194, 77, 'Chocolate Almond', 1, 190.00, 190.00, NULL),
(223, 195, 106, 'Black Forest Pastry', 2, 427.50, 855.00, NULL),
(224, 195, 105, 'Butterscotch Pastry', 2, 380.00, 760.00, NULL),
(225, 196, 100, 'Mixed Berry pavlova', 5, 399.00, 1995.00, NULL),
(226, 197, 106, 'Black Forest Pastry', 17, 427.50, 7267.50, NULL),
(227, 198, 105, 'Butterscotch Pastry', 2, 380.00, 760.00, NULL),
(228, 199, 103, 'Chocolate Tuffle Pastry', 1, 237.50, 237.50, NULL),
(229, 200, 102, 'Pistachio Rose Pavlova', 1, 475.00, 475.00, NULL),
(230, 201, 55, 'Black Forest Cake', 1, 427.50, 427.50, NULL),
(231, 202, 78, 'Black Current ', 1, 285.00, 285.00, NULL),
(232, 203, 73, 'Chocolate Pancake', 2, 380.00, 760.00, NULL),
(233, 204, 84, 'Mango Custard', 2, 332.50, 665.00, NULL),
(234, 205, 69, 'Mango Pastry', 2, 171.00, 342.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_order_tracking`
--

DROP TABLE IF EXISTS `tbl_order_tracking`;
CREATE TABLE IF NOT EXISTS `tbl_order_tracking` (
  `tracking_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `rider_id` int DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tracking_id`),
  KEY `order_idx` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=338 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_order_tracking`
--

INSERT INTO `tbl_order_tracking` (`tracking_id`, `order_id`, `rider_id`, `status`, `message`, `created_at`) VALUES
(320, 201, 2, 'assigned', 'Rider has been assigned to the order', '2026-04-20 05:30:39'),
(321, 201, 2, 'picked_up', 'Rider accepted the order and picked it up.', '2026-04-20 05:31:05'),
(322, 201, 2, 'out_for_delivery', 'Rider is en route to deliver the order.', '2026-04-20 05:31:16'),
(323, 201, 2, 'delivered', 'Order delivered successfully.', '2026-04-20 05:31:17'),
(324, 202, 2, 'assigned', 'Rider has been assigned to the order', '2026-04-20 05:33:38'),
(325, 200, 1, 'assigned', 'Rider has been assigned to the order', '2026-04-20 05:35:43'),
(326, 204, 3, 'assigned', 'Rider has been assigned to the order', '2026-04-20 05:56:25'),
(327, 204, 3, 'picked_up', 'Rider accepted the order and picked it up.', '2026-04-20 05:56:39'),
(328, 204, 3, 'out_for_delivery', 'Rider is en route to deliver the order.', '2026-04-20 05:56:42'),
(329, 204, 3, 'delivered', 'Order delivered successfully.', '2026-04-20 05:56:44'),
(330, 203, 3, 'assigned', 'Rider has been assigned to the order', '2026-04-20 05:57:25'),
(331, 203, 3, 'picked_up', 'Rider accepted the order and picked it up.', '2026-04-20 05:57:48'),
(332, 203, 3, 'out_for_delivery', 'Rider is en route to deliver the order.', '2026-04-20 05:57:49'),
(333, 203, 3, 'delivered', 'Order delivered successfully.', '2026-04-20 05:57:51'),
(334, 205, 3, 'assigned', 'Rider has been assigned to the order', '2026-04-20 08:20:46'),
(335, 205, 3, 'picked_up', 'Rider accepted the order and picked it up.', '2026-04-20 08:23:04'),
(336, 205, 3, 'out_for_delivery', 'Rider is en route to deliver the order.', '2026-04-20 08:23:23'),
(337, 205, 3, 'delivered', 'Order delivered successfully.', '2026-04-20 08:23:35');

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
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=154 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_products`
--

INSERT INTO `tbl_products` (`product_id`, `product_name`, `product_description`, `category_id`, `vendor_id`, `product_price`, `product_stock`, `product_status`, `product_image`, `created_at`, `updated_at`, `stock`, `discount_percent`, `discount_price`, `discount`) VALUES
(55, 'Black Forest Cake', '', 19, 12, 450.00, 0, '1', '17713187259706.jpg', '2026-02-17 08:58:45', '2026-04-20 05:30:10', 32, 0.00, 0.00, 0.00),
(56, 'Chocolate Cookie', '', 20, 12, 150.00, 0, '1', '17713190542569.jpg', '2026-02-17 09:04:14', '2026-03-28 13:35:03', 148, 0.00, 0.00, 0.00),
(57, 'Blueberry Pancake', '', 22, 13, 350.00, 0, '1', '17713195465261.jpg', '2026-02-17 09:12:26', '2026-04-15 18:47:52', 13, 5.00, 0.00, 0.00),
(58, 'Chocolate Donut', '', 26, 14, 160.00, 0, '1', '17713202783351.jpg', '2026-02-17 09:24:38', '2026-04-14 15:08:45', 40, 0.00, 0.00, 0.00),
(59, 'Biscoff Cake', '', 24, 15, 450.00, 0, '1', '17713204615723.jpg', '2026-02-17 09:27:41', '2026-04-20 02:56:52', 21, 0.00, 0.00, 0.00),
(60, 'Red Velvet Cake', '', 19, 12, 550.00, 0, '1', '17714885783522.jpg', '2026-02-19 08:09:38', '2026-02-19 08:09:38', 30, 0.00, 0.00, 0.00),
(61, '. Butterscotch Crunch Cake', '', 19, 12, 450.00, 0, '1', '17714887084173.jpg', '2026-02-19 08:11:48', '2026-04-18 17:12:48', 9, 0.00, 0.00, 0.00),
(62, 'KitKat Chocolate Cake', '', 19, 12, 650.00, 0, '1', '17714887918389.jpg', '2026-02-19 08:13:11', '2026-02-19 08:13:11', 20, 0.00, 0.00, 0.00),
(63, 'Oreo Cookies & Cream Cake', '', 19, 12, 500.00, 0, '1', '17714891013903.jpg', '2026-02-19 08:18:21', '2026-03-28 13:22:02', 19, 0.00, 0.00, 0.00),
(64, 'Mango Magic Cake', '', 19, 12, 650.00, 0, '1', '17714893923046.jpg', '2026-02-19 08:23:12', '2026-03-07 17:59:28', 19, 0.00, 0.00, 0.00),
(65, 'Double Chocolate Cookies', '', 20, 12, 250.00, 0, '1', '17714895747157.jpg', '2026-02-19 08:26:14', '2026-02-19 08:26:14', 50, 0.00, 0.00, 0.00),
(66, 'Oatmeal Raisin Cookies', '', 20, 12, 280.00, 0, '1', '17714902975509.jpg', '2026-02-19 08:38:17', '2026-02-19 08:38:17', 50, 0.00, 0.00, 0.00),
(67, 'Peanut Butter Cookies', '', 20, 12, 300.00, 0, '1', '17714904105901.jpg', '2026-02-19 08:40:10', '2026-02-19 08:40:10', 30, 0.00, 0.00, 0.00),
(68, 'Black Forest Pastry', '', 21, 12, 120.00, 0, '1', '17714906874685.jpg', '2026-02-19 08:44:47', '2026-04-20 08:17:50', 6, 0.00, 0.00, 0.00),
(69, 'Mango Pastry', '', 21, 12, 180.00, 0, '1', '17714908595402.jpg', '2026-02-19 08:47:39', '2026-04-20 08:15:17', 13, 0.00, 0.00, 0.00),
(70, 'Strawberry Delight Cupcake', '', 28, 15, 160.00, 0, '1', '17714923377277. ________________________________________ 📝 Preparation Method 1️⃣ Bake the____', '2026-02-19 09:12:17', '2026-04-05 16:41:04', 29, 0.00, 0.00, 0.00),
(71, 'Chocolate Ice Cream', '', 29, 13, 125.00, 0, '1', '17716866885515.jpg', '2026-02-21 15:11:28', '2026-02-21 15:11:28', 0, 0.00, 0.00, 0.00),
(72, 'Classic Pancakes', '', 22, 13, 250.00, 0, '1', '17717709094552.jpg', '2026-02-22 14:35:09', '2026-02-22 14:35:09', 20, 0.00, 0.00, 0.00),
(73, 'Chocolate Pancake', '', 22, 13, 400.00, 0, '1', '17717710055156.jpg', '2026-02-22 14:36:45', '2026-04-20 05:51:22', 12, 0.00, 0.00, 0.00),
(74, 'Nutella pancake', '', 22, 13, 450.00, 0, '1', '17717712276535.jpg', '2026-02-22 14:40:27', '2026-04-17 18:59:29', 17, 0.00, 0.00, 0.00),
(75, 'Chocolate chips Pancake', '', 22, 13, 470.00, 0, '1', '17717713968697.jpg', '2026-02-22 14:43:16', '2026-04-13 15:55:27', 17, 0.00, 0.00, 0.00),
(76, 'Vanilla', '', 29, 13, 150.00, 0, '1', '17717715736783.jpg', '2026-02-22 14:46:13', '2026-04-17 18:59:29', 18, 0.00, 0.00, 0.00),
(77, 'Chocolate Almond', '', 29, 13, 200.00, 0, '1', '17717716946314.jpg', '2026-02-22 14:48:14', '2026-04-20 04:11:44', 6, 0.00, 0.00, 0.00),
(78, 'Black Current ', '', 29, 13, 300.00, 0, '1', '17717718435268.jpg', '2026-02-22 14:50:43', '2026-04-20 05:32:30', 15, 0.00, 0.00, 0.00),
(79, 'Ferrero Rocher', '', 29, 13, 650.00, 0, '1', '17717720854299.jpg', '2026-02-22 14:54:45', '2026-04-07 11:13:43', 19, 0.00, 0.00, 0.00),
(80, 'Kesar Pista', '', 29, 13, 550.00, 0, '1', '17717722134771.jpg', '2026-02-22 14:56:53', '2026-02-22 14:56:53', 0, 0.00, 0.00, 0.00),
(81, 'Chocolate Pudding', '', 30, 13, 250.00, 0, '1', '17717727528107.jpg', '2026-02-22 15:05:52', '2026-04-15 17:15:09', 6, 0.00, 0.00, 0.00),
(82, 'Caramel Pudding', '', 30, 13, 270.00, 0, '1', '17717728387421.jpg', '2026-02-22 15:07:18', '2026-04-11 09:16:28', 5, 0.00, 0.00, 0.00),
(83, 'Strawberry custard', '', 30, 13, 300.00, 0, '1', '17717730412001.jpg', '2026-02-22 15:10:41', '2026-04-07 11:16:58', 8, 0.00, 0.00, 0.00),
(84, 'Mango Custard', '', 30, 13, 350.00, 0, '1', '17717735312104.jpg', '2026-02-22 15:18:51', '2026-04-20 05:55:05', 5, 0.00, 0.00, 0.00),
(85, 'Red Velvet  ', '', 28, 15, 270.00, 0, '1', '17717739714699.jpg', '2026-02-22 15:26:11', '2026-02-22 15:26:11', 25, 0.00, 0.00, 0.00),
(86, 'KitKat ', '', 28, 15, 300.00, 0, '1', '17717741154003.jpg', '2026-02-22 15:28:35', '2026-04-15 17:36:41', 22, 0.00, 0.00, 0.00),
(87, 'Mango ', '', 28, 15, 350.00, 0, '1', '17717742968184.jpg', '2026-02-22 15:31:36', '2026-02-22 15:31:36', 25, 0.00, 0.00, 0.00),
(88, 'Cheesecake ', '', 28, 15, 400.00, 0, '1', '17717752939206.jpg', '2026-02-22 15:48:13', '2026-04-18 17:33:29', 22, 0.00, 0.00, 0.00),
(89, 'Kiwi cake', '', 24, 15, 650.00, 0, '1', '17717756344905.jpeg', '2026-02-22 15:53:54', '2026-02-22 15:53:54', 30, 0.00, 0.00, 0.00),
(90, 'Ferrero Rocher cake', '', 24, 15, 680.00, 0, '1', '17717758312239.jpeg', '2026-02-22 15:57:11', '2026-04-15 11:25:37', 17, 0.00, 0.00, 0.00),
(91, 'Mango Cake', '', 24, 15, 600.00, 0, '1', '17717759159997.jpeg', '2026-02-22 15:58:35', '2026-02-22 15:58:35', 20, 0.00, 0.00, 0.00),
(92, 'Brownie Ice cream cake', '', 24, 15, 620.00, 0, '1', '17717760802398.jpeg', '2026-02-22 16:01:20', '2026-03-15 11:42:27', 19, 0.00, 0.00, 0.00),
(93, 'Chocolate Glazed  Donut', '', 25, 15, 200.00, 0, '1', '17717763097813.jpg', '2026-02-22 16:05:09', '2026-04-13 14:52:15', 14, 0.00, 0.00, 0.00),
(94, 'Boston Cream Donut', '', 25, 15, 270.00, 0, '1', '17717763848603.jpg', '2026-02-22 16:06:24', '2026-04-13 15:48:22', 11, 0.00, 0.00, 0.00),
(95, 'Strawberry Sprinkles Donut', '', 25, 15, 320.00, 0, '1', '17717764616845.jpg', '2026-02-22 16:07:41', '2026-02-22 16:07:41', 15, 0.00, 0.00, 0.00),
(96, 'Biscoff Donut', '', 25, 15, 350.00, 0, '1', '17717765235525.jpg', '2026-02-22 16:08:43', '2026-04-18 17:56:57', 10, 0.00, 0.00, 0.00),
(97, 'Caramel Crunch Donut', '', 25, 15, 380.00, 0, '1', '17717767917800.jpeg', '2026-02-22 16:13:11', '2026-04-14 15:23:20', 11, 0.00, 0.00, 0.00),
(98, 'Mango Passion Pavlova', '', 31, 14, 300.00, 0, '1', '17717775071705.jpg', '2026-02-22 16:25:07', '2026-02-22 16:25:07', 20, 0.00, 0.00, 0.00),
(99, 'Strawberry Cream Pavlova', '', 31, 14, 370.00, 0, '1', '17717775784976.jpg', '2026-02-22 16:26:18', '2026-02-22 16:26:18', 20, 0.00, 0.00, 0.00),
(100, 'Mixed Berry pavlova', '', 31, 14, 420.00, 0, '1', '17717776872284.jpg', '2026-02-22 16:28:07', '2026-04-20 05:02:31', 15, 0.00, 0.00, 0.00),
(101, 'Chocolate Hazelnut Pavlova', '', 31, 14, 480.00, 0, '1', '17717777648985.jpg', '2026-02-22 16:29:24', '2026-02-22 16:29:24', 20, 0.00, 0.00, 0.00),
(102, 'Pistachio Rose Pavlova', '', 31, 14, 500.00, 0, '1', '17717778783224.jpg', '2026-02-22 16:31:18', '2026-04-20 05:23:44', 19, 0.00, 0.00, 0.00),
(103, 'Chocolate Tuffle Pastry', '', 27, 14, 250.00, 0, '1', '17717780036673.jpg', '2026-02-22 16:33:23', '2026-04-20 05:23:18', 19, 0.00, 0.00, 0.00),
(104, 'Red Valvet Pastry', '', 27, 14, 350.00, 0, '1', '17717780763276.jpg', '2026-02-22 16:34:36', '2026-02-22 16:34:36', 25, 0.00, 0.00, 0.00),
(105, 'Butterscotch Pastry', '', 27, 14, 400.00, 0, '1', '17717781767190.jpg', '2026-02-22 16:36:16', '2026-04-20 05:15:28', 15, 0.00, 0.00, 0.00),
(106, 'Black Forest Pastry', '', 27, 14, 450.00, 0, '1', '17717782479058.jpg', '2026-02-22 16:37:27', '2026-04-20 05:06:37', 20, 0.00, 0.00, 0.00),
(107, 'Pineapple Pastry', '', 27, 14, 500.00, 0, '1', '17717783186551.jpg', '2026-02-22 16:38:38', '2026-02-22 16:38:38', 20, 0.00, 0.00, 0.00),
(108, 'Nuttela Waffle', '', 32, 14, 160.00, 0, '1', '17717785701646.jpg', '2026-02-22 16:42:50', '2026-04-13 18:27:36', 8, 0.00, 0.00, 0.00),
(109, 'Chocolate Overload Waffle', '', 32, 14, 185.00, 0, '1', '17717786454451.jpg', '2026-02-22 16:44:05', '2026-02-22 16:54:34', 10, 0.00, 0.00, 0.00),
(110, 'Strawberry Cream Waffle', '', 32, 14, 200.00, 0, '1', '17717788677192.jpg', '2026-02-22 16:47:47', '2026-04-15 17:36:41', 8, 0.00, 0.00, 0.00),
(111, 'Oreo Crunch Waffle', '', 32, 14, 220.00, 0, '1', '17717791329815.jpg', '2026-02-22 16:52:12', '2026-02-22 16:52:12', 10, 0.00, 0.00, 0.00),
(112, 'Belgian Chocolate Waffle', '', 32, 14, 280.00, 0, '1', '17717792145650.jpg', '2026-02-22 16:53:34', '2026-04-14 13:15:03', 0, 0.00, 0.00, 0.00),
(113, 'Mango Thickshake', '', 33, 16, 250.00, 0, '1', '17717829958568.jpg', '2026-02-22 17:56:35', '2026-02-22 17:56:35', 25, 0.00, 0.00, 0.00),
(114, 'Strawberry Thickshake', '', 33, 16, 220.00, 0, '1', '17717831625877.jpg', '2026-02-22 17:59:22', '2026-02-22 17:59:22', 25, 0.00, 0.00, 0.00),
(115, 'Chocolate Thickshake', '', 33, 16, 280.00, 0, '1', '17717832122147.jpg', '2026-02-22 18:00:12', '2026-02-22 18:00:12', 25, 0.00, 0.00, 0.00),
(116, 'Oreo Thickshake', '', 33, 16, 350.00, 0, '1', '17717833116125.jpg', '2026-02-22 18:01:51', '2026-02-22 18:01:51', 25, 0.00, 0.00, 0.00),
(117, 'KitKat Thickshake', '', 33, 16, 380.00, 0, '1', '17717835285596.jpg', '2026-02-22 18:05:28', '2026-02-22 18:05:28', 25, 0.00, 0.00, 0.00),
(118, 'Nutella Thickshake', '', 33, 16, 270.00, 0, '1', '17717841444808.jpg', '2026-02-22 18:15:44', '2026-02-22 18:15:44', 30, 0.00, 0.00, 0.00),
(119, 'Vanilla Thickshake', '', 33, 16, 220.00, 0, '1', '17717843032986.jpg', '2026-02-22 18:18:23', '2026-02-22 18:18:23', 15, 0.00, 0.00, 0.00),
(120, 'Banana Thickshake', '', 33, 16, 300.00, 0, '1', '17717843482499.jpg', '2026-02-22 18:19:08', '2026-02-22 18:19:08', 19, 0.00, 0.00, 0.00),
(132, 'Mango Thickshake', '', 38, 24, 200.00, 0, '1', '17744258563068.jpg', '2026-03-25 08:04:16', '2026-03-25 08:04:16', 15, 5.00, 0.00, 0.00),
(136, 'venilla scoop (2 scoops)', '', 41, 36, 250.00, 0, '1', '17755649934115.jpg', '2026-04-07 12:29:53', '2026-04-13 15:55:27', 37, 5.00, 0.00, 0.00),
(137, 'chocolate scoops (3 scoops)', '', 41, 36, 350.00, 0, '1', '17755650651996.jpg', '2026-04-07 12:31:06', '2026-04-15 16:45:32', 30, 5.00, 0.00, 0.00),
(138, 'mango scoops (2 scoops)', '', 41, 36, 400.00, 0, '1', '17755651414268.jpg', '2026-04-07 12:32:21', '2026-04-07 15:11:48', 39, 5.00, 0.00, 0.00),
(139, 'kesar pista scoops (2 scoops)', '', 41, 36, 500.00, 0, '1', '17755652362470.jpg', '2026-04-07 12:33:56', '2026-04-13 14:52:15', 26, 5.00, 0.00, 0.00),
(141, 'Royal Falooda', '', 43, 36, 249.00, 0, '1', '17755661815065.jpg', '2026-04-07 12:49:41', '2026-04-09 06:30:49', 27, 5.00, 0.00, 0.00),
(142, 'Mango Falooda', '', 43, 36, 299.00, 0, '1', '17755662559488.jpg', '2026-04-07 12:50:55', '2026-04-15 17:15:09', 26, 5.00, 0.00, 0.00),
(143, 'chocolate  Falooda', '', 43, 36, 259.00, 0, '1', '17755663399951.jpg', '2026-04-07 12:52:19', '2026-04-17 18:17:48', 0, 5.00, 0.00, 0.00),
(144, 'kulfi Falooda', '', 43, 36, 339.00, 0, '1', '17755664297771.jpg', '2026-04-07 12:53:49', '2026-04-09 05:59:17', 29, 5.00, 0.00, 0.00),
(145, 'Churros', '', 44, 37, 350.00, 0, '1', '17755684695880.jpg', '2026-04-07 13:26:46', '2026-04-07 13:27:49', 20, 5.00, 0.00, 0.00),
(146, 'Crema Catalana', '', 44, 37, 389.00, 0, '1', '17755685594717.jpg', '2026-04-07 13:29:19', '2026-04-07 13:29:19', 30, 5.00, 0.00, 0.00),
(147, 'Arroz con Leche', '', 44, 37, 400.00, 0, '1', '17755686518661.jpg', '2026-04-07 13:30:51', '2026-04-18 17:53:17', 26, 5.00, 0.00, 0.00),
(148, 'Turron', '', 44, 37, 450.00, 0, '1', '17755687619365.jpg', '2026-04-07 13:32:41', '2026-04-15 11:25:37', 27, 5.00, 0.00, 0.00),
(149, 'Leche Frita', '', 44, 37, 479.00, 0, '1', '17755688687518.jpg', '2026-04-07 13:34:28', '2026-04-17 16:17:58', 28, 5.00, 0.00, 0.00),
(150, 'Natillas', '', 44, 37, 369.00, 0, '1', '17755689329956.jpg', '2026-04-07 13:35:32', '2026-04-15 11:33:31', 29, 5.00, 0.00, 0.00),
(151, 'Ensaimada', '', 44, 37, 490.00, 0, '1', '17755690078943.jpg', '2026-04-07 13:36:47', '2026-04-17 16:16:09', 28, 5.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_reviews`
--

DROP TABLE IF EXISTS `tbl_reviews`;
CREATE TABLE IF NOT EXISTS `tbl_reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `vendor_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `rating` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `review_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `helpful_count` int DEFAULT '0',
  `is_verified_purchase` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_reviews`
--

INSERT INTO `tbl_reviews` (`review_id`, `user_id`, `vendor_id`, `product_id`, `order_id`, `rating`, `title`, `review_text`, `helpful_count`, `is_verified_purchase`, `created_at`, `updated_at`) VALUES
(12, 21, 13, 84, 204, 5, NULL, NULL, 0, 1, '2026-04-20 05:56:51', '2026-04-20 05:56:51'),
(13, 21, 13, 73, 203, 4, NULL, NULL, 0, 1, '2026-04-20 05:58:01', '2026-04-20 05:58:01');

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
-- Table structure for table `tbl_riders`
--

DROP TABLE IF EXISTS `tbl_riders`;
CREATE TABLE IF NOT EXISTS `tbl_riders` (
  `rider_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `vehicle_number` varchar(50) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `profile_image` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`rider_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_riders`
--

INSERT INTO `tbl_riders` (`rider_id`, `name`, `email`, `phone`, `password`, `vehicle_type`, `vehicle_number`, `latitude`, `longitude`, `is_online`, `status`, `created_at`, `profile_image`, `reset_token`, `token_expiry`) VALUES
(1, 'krish', 'krish@gmail.com', '9265987456', '$2y$10$akSK26kNJKMTBJp6Rmp8peKEIMoei97u8V1eThZwOnshZF1p16M1W', NULL, NULL, NULL, NULL, 0, 'active', '2026-03-07 14:43:53', NULL, NULL, NULL),
(2, 'kunal', 'kunal@gmail.com', '9856321473', '$2y$10$ZVjfcmlponMuNlFSE4VeSO8GZA0d55XpAJT3Vf2Cv0BoK67StbFoq', 'bike', 'GJ-1-AB-9842', 21.1989970, 72.7752000, 0, 'active', '2026-03-07 14:50:24', 'uploads/riders/rider_2_1776453904.jpg', NULL, NULL),
(3, 'hetansh', 'hetansh@gmail.com', '9854789545', '$2y$10$1e8PZkLHE.OBaIK9XhFUVe25CxMpnKxhYqGlGKX.cBc/AsnwdfBV2', 'bike', 'GJ-1-AB-1245', 21.1989897, 72.7752450, 0, 'active', '2026-03-07 14:51:54', 'uploads/riders/rider_3_1773154264.png', NULL, NULL),
(4, 'rudra', 'rudra@gmail.com', '97563214569', '$2y$10$X.Dbgm.Wkv7EIJQODWaWjuFcj4Il5iTzqoYbwPX5pJASIzHd9pHOi', NULL, NULL, NULL, NULL, 0, 'active', '2026-03-07 14:54:52', NULL, NULL, NULL),
(5, 'het', 'het@gmail.com', '96523987412', '$2y$10$snJpsy5UZSI5YSAydPYIi.C3hbufjXysP4Y6rsFv7nx6w3EdJMwEy', 'bike', 'GJ052345', NULL, NULL, 0, 'inactive', '2026-03-09 19:26:07', NULL, NULL, NULL),
(6, 'raju patel', 'raju@gmail.com', '7897898798', '$2y$10$SkRjKL0j9g4/ZSqqfTtzKOCQIWvgpqoJyADXhEcUIOb4pfJJ1xjw2', 'bike', 'GJ-02-AB-3232', NULL, NULL, 0, 'inactive', '2026-03-10 18:38:21', 'uploads/riders/rider_6_1773148934.png', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_rider_earnings`
--

DROP TABLE IF EXISTS `tbl_rider_earnings`;
CREATE TABLE IF NOT EXISTS `tbl_rider_earnings` (
  `earning_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `rider_id` int NOT NULL,
  `delivery_charge` decimal(10,2) NOT NULL DEFAULT '50.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`earning_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_rider` (`rider_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_rider_earnings`
--

INSERT INTO `tbl_rider_earnings` (`earning_id`, `order_id`, `rider_id`, `delivery_charge`, `created_at`) VALUES
(1, 131, 3, 50.00, '2026-04-09 06:49:03'),
(2, 130, 3, 50.00, '2026-04-09 06:55:34'),
(3, 133, 3, 50.00, '2026-04-09 07:09:18'),
(4, 134, 3, 50.00, '2026-04-09 07:11:41'),
(5, 132, 2, 50.00, '2026-04-10 18:45:38'),
(6, 136, 2, 50.00, '2026-04-10 18:51:10'),
(7, 138, 2, 50.00, '2026-04-11 09:25:46'),
(8, 137, 2, 50.00, '2026-04-11 11:51:15'),
(9, 139, 2, 50.00, '2026-04-11 11:51:33'),
(10, 140, 2, 50.00, '2026-04-11 11:51:40'),
(11, 142, 3, 50.00, '2026-04-12 11:29:58'),
(12, 141, 3, 50.00, '2026-04-12 11:35:43'),
(13, 143, 3, 50.00, '2026-04-12 11:37:01'),
(14, 144, 3, 50.00, '2026-04-12 20:45:14'),
(15, 145, 3, 50.00, '2026-04-13 07:07:00'),
(16, 146, 2, 50.00, '2026-04-13 07:09:36'),
(17, 152, 2, 50.00, '2026-04-13 16:27:57'),
(18, 153, 2, 50.00, '2026-04-13 18:28:52'),
(19, 155, 2, 50.00, '2026-04-14 13:17:33'),
(20, 156, 2, 50.00, '2026-04-14 14:45:30'),
(21, 157, 2, 50.00, '2026-04-14 14:57:41'),
(22, 158, 2, 50.00, '2026-04-14 15:02:33'),
(23, 159, 2, 50.00, '2026-04-14 15:09:13'),
(24, 160, 2, 50.00, '2026-04-14 15:13:13'),
(25, 161, 2, 50.00, '2026-04-14 15:23:55'),
(26, 165, 2, 50.00, '2026-04-15 11:51:26'),
(27, 166, 3, 50.00, '2026-04-15 16:50:57'),
(28, 168, 3, 50.00, '2026-04-15 17:58:58'),
(29, 169, 2, 50.00, '2026-04-15 18:50:42'),
(30, 182, 2, 50.00, '2026-04-17 16:23:06'),
(31, 184, 3, 50.00, '2026-04-17 18:21:09'),
(32, 185, 2, 50.00, '2026-04-17 19:00:31'),
(33, 186, 2, 50.00, '2026-04-18 17:13:31'),
(34, 187, 2, 50.00, '2026-04-18 17:18:49'),
(35, 188, 2, 50.00, '2026-04-18 17:28:26'),
(36, 189, 2, 50.00, '2026-04-18 17:31:25'),
(37, 190, 2, 50.00, '2026-04-18 17:33:48'),
(38, 191, 2, 50.00, '2026-04-18 17:54:07'),
(39, 192, 2, 50.00, '2026-04-18 17:57:16'),
(40, 195, 1, 50.00, '2026-04-20 05:01:18'),
(41, 196, 1, 50.00, '2026-04-20 05:03:13'),
(42, 197, 1, 50.00, '2026-04-20 05:06:22'),
(43, 201, 2, 50.00, '2026-04-20 05:31:17'),
(44, 204, 3, 50.00, '2026-04-20 05:56:44'),
(45, 203, 3, 50.00, '2026-04-20 05:57:51'),
(46, 205, 3, 50.00, '2026-04-20 08:23:35');

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
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_stock_management`
--

INSERT INTO `tbl_stock_management` (`stock_id`, `product_id`, `quantity_added`, `previous_quantity`, `new_quantity`, `stock_date`, `notes`, `created_by`) VALUES
(1, 78, 20, 0, 20, '2026-04-11 20:44:55', '', 13),
(2, 106, 20, 0, 20, '2026-04-20 10:36:37', '', 14),
(3, 68, 1, 5, 6, '2026-04-20 13:47:50', '', 12);

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
  `first_order_discount_claimed` tinyint(1) NOT NULL DEFAULT '0',
  `first_order_discount_applied` tinyint(1) NOT NULL DEFAULT '0',
  `reset_token` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `reset_token` (`reset_token`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`user_id`, `user_name`, `email`, `password`, `phone`, `address`, `profile_image`, `created_at`, `first_order_discount_claimed`, `first_order_discount_applied`, `reset_token`) VALUES
(20, 'pushti shah', 'pushti@gmail.com', '$2y$10$1Pm7lr7QYspLqlNsDD9EeevV5d/uoxIBAskBcHc2625AYqp1I2fOq', '7788996633', NULL, NULL, '2026-04-20 05:11:53', 0, 0, NULL),
(21, 'honey ganwani', 'honey@gmail.com', '$2y$10$K/0/p/5CksFTr2/DTvpwDOEwQ93bKZ0Wu4UkG1.2aEtftbEeGOk6i', '8855223366', NULL, NULL, '2026-04-20 05:13:34', 0, 0, NULL),
(22, 'krushiv', 'krushiv@gmail.com', '$2y$10$D5NSUj.475kC1puuSe/TkeX778ezTX4WbHE612cjIg7q2HKV6z0uK', '95784784515', NULL, NULL, '2026-04-20 05:29:42', 0, 0, NULL);

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
  `status` enum('pending','active','inactive','suspended') NOT NULL DEFAULT 'pending',
  `vendor_discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`vendor_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_vendors`
--

INSERT INTO `tbl_vendors` (`vendor_id`, `vendor_name`, `email`, `password`, `phone`, `address`, `shop_name`, `image_path`, `logo_path`, `created_at`, `is_online`, `last_active`, `city`, `status`, `vendor_discount_percent`) VALUES
(12, 'Honey', 'honey1@gmail.com', '$2y$10$yCr2gs0jr4hbwbWHbjbWOeXj7xFSHAjCRKHXEyTwCLrCnLA9ABuGG', '9854789874', 'Shop No. 12, Ghod Dod Road, Athwa, Surat, Gujarat – 395007', 'Sugar Rush Desserts', 'profile_1776454178_69e28a2288cb4.jpg', 'logo_1771487500_6996c10c9e472.png', '2026-02-06 18:36:34', 1, '2026-04-20 13:46:29', NULL, 'active', 5.00),
(13, 'Pushti', 'pushti@gmail.com', '$2y$10$3ZrywvdmNZ1ZnVvN9VeBbeRFu6uJJjF6DRZVSLZSg3/cEOvcsibPG', '9254789874', 'Shop No. 5, City Light Road, Surat, Gujarat – 395009', 'Choco Heaven', 'profile_1776454096_69e289d0db314.webp', 'logo_1770442924_6986d0ace8df1.png', '2026-02-06 18:58:05', 1, '2026-04-20 11:35:36', NULL, 'active', 5.00),
(14, 'Krish', 'krish@gmail.com', '$2y$10$U1/trfctX3u05a2MpB8icOOMCg8SnGMQAN6gYkl9ryTqoA0F4xR8y', '9358747898', '41, Ring Road, Near Sahara Darwaja, Surat, Gujarat – 395002', 'Creamy Crust', 'profile_1776669812_69e5d4743a3e7.jpg', 'logo_1770443277_6986d20d55b43.png', '2026-02-06 19:23:22', 1, '2026-04-20 12:52:33', NULL, 'active', 5.00),
(15, 'Krushiv', 'krushiv@gmail.com', '$2y$10$omxc1IhrFcdmKfWkwQQ0zOEKhBSMrdQSKNCjYthXYqqNiGkBNZmWK', '9854789874', '34, Adajan Gam Road, Adajan, Surat, Gujarat – 395009', 'Urban Treats', 'profile_1776454500_69e28b64a89f4.jpg', 'logo_1771492369_6996d41170697.png', '2026-02-06 19:24:44', 1, '2026-04-18 01:06:01', NULL, 'active', 5.00),
(16, 'Kunal', 'kunal@gmail.com', '$2y$10$4vz43Qg5opQSIFDUBr0oMerow.Yumh7p6BKEWHk93kchxq2jFBIh2', '8866784785', '102, Shyam Chambers, Adajan, Surat, Gujarat 395009', 'Sugar Bloom ThickShake', 'vendor_699b41e77bde2.jpg', 'shop_logo_699b41e77cbf8.png', '2026-02-22 17:50:31', 0, '2026-02-22 23:59:05', NULL, 'active', 5.00),
(36, 'shreyash parmar', 'shreyash@gmail.com', '$2y$10$i5emlXWExgFugqwujRMqsuKDLVbY8GuYUTOCxEaIkra0/yokYy5di', '9659648548', 'B-015/ palanpur patiya,surat', 'Frosty Bites', 'vendor_69d4f6076f421.jpeg', 'shop_logo_69d4f60772cb3.jpeg', '2026-04-07 12:18:15', 1, '2026-04-15 22:37:41', NULL, 'active', 5.00),
(37, 'Ruchika Patel', 'ruchika@gmail.com', '$2y$10$wKQSLlOMRutH9AzDLtyBxO3aksu/sr1G.3r5IpBkLdW9ttum./Yhm', '9785785978', 'A-048  Ramnagar, Surat', 'House Of Dessert', 'profile_1775568103_69d504e72225e.avif', 'logo_1775568103_69d504e7252fd.jpeg', '2026-04-07 13:19:42', 1, '2026-04-18 01:06:20', NULL, 'active', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_vendor_earnings`
--

DROP TABLE IF EXISTS `tbl_vendor_earnings`;
CREATE TABLE IF NOT EXISTS `tbl_vendor_earnings` (
  `earning_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `vendor_id` int NOT NULL,
  `order_amount` decimal(10,2) NOT NULL,
  `admin_commission` decimal(10,2) NOT NULL,
  `delivery_charge` decimal(10,2) NOT NULL DEFAULT '50.00',
  `net_earning` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`earning_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_vendor` (`vendor_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_vendor_earnings`
--

INSERT INTO `tbl_vendor_earnings` (`earning_id`, `order_id`, `vendor_id`, `order_amount`, `admin_commission`, `delivery_charge`, `net_earning`, `created_at`) VALUES
(1, 131, 36, 1861.46, 279.22, 50.00, 1532.24, '2026-04-09 06:49:03'),
(2, 130, 36, 1503.36, 225.50, 50.00, 1227.86, '2026-04-09 06:55:34'),
(3, 133, 36, 6035.00, 905.25, 50.00, 5079.75, '2026-04-09 07:09:18'),
(4, 134, 36, 2633.53, 395.03, 50.00, 2188.50, '2026-04-09 07:11:41'),
(5, 132, 36, 748.25, 112.24, 50.00, 586.01, '2026-04-10 18:45:38'),
(6, 136, 13, 399.13, 59.87, 50.00, 289.26, '2026-04-10 18:51:10'),
(7, 138, 13, 588.65, 88.30, 50.00, 450.35, '2026-04-11 09:25:46'),
(8, 137, 13, 449.00, 67.35, 50.00, 331.65, '2026-04-11 11:51:15'),
(9, 139, 37, 449.00, 67.35, 50.00, 331.65, '2026-04-11 11:51:33'),
(10, 140, 13, 399.13, 59.87, 50.00, 289.26, '2026-04-11 11:51:40'),
(11, 142, 36, 566.71, 85.01, 50.00, 431.70, '2026-04-12 11:29:58'),
(12, 141, 36, 308.35, 46.25, 50.00, 212.10, '2026-04-12 11:35:43'),
(13, 143, 36, 308.35, 46.25, 50.00, 212.10, '2026-04-12 11:37:01'),
(14, 144, 13, 349.25, 52.39, 50.00, 246.86, '2026-04-12 20:45:14'),
(15, 145, 13, 798.13, 119.72, 50.00, 628.41, '2026-04-13 07:07:00'),
(16, 146, 36, 399.13, 59.87, 50.00, 289.26, '2026-04-13 07:09:36'),
(17, 152, 0, 1645.00, 246.75, 50.00, 1348.25, '2026-04-13 16:27:57'),
(18, 153, 14, 369.20, 55.38, 50.00, 263.82, '2026-04-13 18:28:52'),
(19, 155, 14, 369.20, 55.38, 50.00, 263.82, '2026-04-14 13:17:33'),
(20, 156, 14, 369.20, 55.38, 50.00, 263.82, '2026-04-14 14:45:30'),
(21, 157, 14, 369.20, 55.38, 50.00, 263.82, '2026-04-14 14:57:41'),
(22, 158, 14, 369.20, 55.38, 50.00, 263.82, '2026-04-14 15:02:33'),
(23, 159, 14, 369.20, 55.38, 50.00, 263.82, '2026-04-14 15:09:13'),
(24, 160, 15, 808.10, 121.22, 50.00, 636.89, '2026-04-14 15:13:13'),
(25, 161, 15, 758.10, 113.72, 50.00, 644.39, '2026-04-14 15:23:55'),
(26, 165, 0, 1025.43, 153.81, 50.00, 871.62, '2026-04-15 11:51:26'),
(27, 166, 0, 897.75, 134.66, 50.00, 763.09, '2026-04-15 16:50:57'),
(28, 168, 0, 847.88, 127.18, 50.00, 720.70, '2026-04-15 17:58:58'),
(29, 169, 13, 349.13, 52.37, 50.00, 296.76, '2026-04-15 18:50:42'),
(30, 182, 37, 477.80, 71.67, 50.00, 406.13, '2026-04-17 16:23:06'),
(31, 184, 36, 258.35, 38.75, 50.00, 219.60, '2026-04-17 18:21:09'),
(32, 185, 13, 1845.38, 276.81, 50.00, 1568.57, '2026-04-17 19:00:31'),
(33, 186, 12, 448.88, 67.33, 50.00, 381.55, '2026-04-18 17:13:31'),
(34, 187, 15, 349.13, 52.37, 50.00, 296.76, '2026-04-18 17:18:49'),
(35, 188, 12, 119.70, 17.96, 50.00, 101.75, '2026-04-18 17:28:26'),
(36, 189, 15, 349.13, 52.37, 50.00, 296.76, '2026-04-18 17:31:25'),
(37, 190, 15, 399.00, 59.85, 50.00, 339.15, '2026-04-18 17:33:48'),
(38, 191, 37, 399.00, 59.85, 50.00, 339.15, '2026-04-18 17:54:07'),
(39, 192, 15, 349.13, 52.37, 50.00, 296.76, '2026-04-18 17:57:16'),
(40, 195, 14, 1695.75, 254.36, 50.00, 1441.39, '2026-04-20 05:01:18'),
(41, 196, 14, 2094.75, 314.21, 50.00, 1780.54, '2026-04-20 05:03:13'),
(42, 197, 14, 7630.88, 1144.63, 50.00, 6486.25, '2026-04-20 05:06:22'),
(43, 201, 12, 448.88, 67.33, 50.00, 381.55, '2026-04-20 05:31:17'),
(44, 204, 13, 698.25, 104.74, 50.00, 593.51, '2026-04-20 05:56:44'),
(45, 203, 13, 798.00, 119.70, 50.00, 678.30, '2026-04-20 05:57:51'),
(46, 205, 12, 359.10, 53.87, 50.00, 305.24, '2026-04-20 08:23:35');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_voucher_claims`
--

DROP TABLE IF EXISTS `tbl_voucher_claims`;
CREATE TABLE IF NOT EXISTS `tbl_voucher_claims` (
  `claim_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `voucher_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '25PERCENT',
  `claimed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `used_in_order_id` int DEFAULT NULL,
  `status` enum('active','used') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`claim_id`),
  UNIQUE KEY `unique_user_voucher` (`user_id`,`voucher_code`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_voucher_claims`
--

INSERT INTO `tbl_voucher_claims` (`claim_id`, `user_id`, `voucher_code`, `claimed_at`, `used_in_order_id`, `status`) VALUES
(1, 9, '25PERCENT', '2026-03-07 17:57:15', 0, 'used'),
(2, 16, '25PERCENT', '2026-04-13 18:38:26', NULL, 'active'),
(3, 18, '25PERCENT', '2026-04-17 18:12:21', 183, 'used');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_wishlist`
--

DROP TABLE IF EXISTS `tbl_wishlist`;
CREATE TABLE IF NOT EXISTS `tbl_wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_user_product` (`user_id`,`product_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_wishlist`
--

INSERT INTO `tbl_wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(1, 1, 61, '2026-03-12 19:51:49');

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
) ENGINE=InnoDB AUTO_INCREMENT=290 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(33, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 21:31:09'),
(34, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 23:02:21'),
(35, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 20:29:23'),
(36, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 13:32:28'),
(37, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 15:26:46'),
(38, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 19:46:28'),
(39, 14, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 19:46:56'),
(40, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 19:47:13'),
(41, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 19:49:26'),
(42, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 19:51:13'),
(43, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 19:54:30'),
(44, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 19:56:12'),
(45, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 19:56:27'),
(46, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 20:49:19'),
(47, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 20:51:07'),
(48, 15, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:44:24'),
(49, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:44:34'),
(50, 16, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 23:20:51'),
(51, 16, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 23:50:16'),
(52, 16, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 23:50:54'),
(53, 16, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 23:59:05'),
(54, 17, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 00:07:47'),
(55, 17, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 00:38:45'),
(56, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 00:39:02'),
(57, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 00:50:29'),
(58, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 00:50:47'),
(59, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 00:54:26'),
(60, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 00:54:40'),
(61, 15, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 00:56:25'),
(62, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 00:56:37'),
(63, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 10:33:08'),
(64, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 20:45:28'),
(65, 14, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 20:45:37'),
(66, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 20:46:01'),
(67, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-04 16:05:20'),
(68, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-04 18:40:59'),
(69, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 11:44:29'),
(70, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 21:04:02'),
(71, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 09:51:33'),
(72, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 09:53:01'),
(73, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 10:16:31'),
(74, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 23:12:10'),
(75, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 14:06:12'),
(76, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-07 23:40:45'),
(77, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 00:07:32'),
(78, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 00:52:23'),
(79, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 01:02:51'),
(80, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 01:15:19'),
(81, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 01:29:54'),
(82, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 19:06:58'),
(83, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 19:31:28'),
(84, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 20:07:55'),
(85, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 20:43:57'),
(86, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 11:25:38'),
(87, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 14:01:49'),
(88, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 14:12:44'),
(89, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-10 14:32:27'),
(90, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 11:02:14'),
(91, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 11:22:44'),
(92, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 19:12:43'),
(93, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-11 20:14:18'),
(94, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 12:18:28'),
(95, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 13:02:09'),
(96, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 13:06:19'),
(97, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 14:23:39'),
(98, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 14:25:45'),
(99, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 14:26:58'),
(100, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 14:27:33'),
(101, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 14:36:22'),
(102, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 16:03:04'),
(103, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 16:03:53'),
(104, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 19:24:48'),
(105, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 00:14:39'),
(106, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 00:32:40'),
(107, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 01:56:03'),
(108, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 01:57:18'),
(109, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 02:00:43'),
(110, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 08:40:23'),
(111, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 09:04:10'),
(112, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 09:35:08'),
(113, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 09:35:11'),
(114, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 09:35:36'),
(115, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 11:47:59'),
(116, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 11:49:46'),
(117, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 12:06:54'),
(118, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 13:33:49'),
(119, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-15 17:02:24'),
(120, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-15 19:44:17'),
(121, 18, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 21:01:16'),
(122, 18, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 21:15:10'),
(123, 23, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 22:12:16'),
(124, 23, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 22:15:27'),
(125, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 22:15:45'),
(126, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 21:30:35'),
(127, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 21:31:42'),
(128, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 23:59:07'),
(129, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 23:59:13'),
(130, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 23:59:35'),
(131, 15, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 23:59:39'),
(132, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 23:59:50'),
(133, 14, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 00:00:13'),
(134, 24, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 13:28:27'),
(135, 24, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 13:46:00'),
(136, 25, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 15:48:01'),
(137, 25, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 15:48:23'),
(138, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 16:13:32'),
(139, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 16:14:49'),
(140, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 16:15:24'),
(141, 26, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 16:19:15'),
(142, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 19:06:36'),
(143, 15, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 19:06:41'),
(144, 27, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 19:09:54'),
(145, 27, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 19:10:10'),
(146, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 19:11:56'),
(147, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 19:52:33'),
(148, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 19:57:08'),
(149, 28, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-25 20:02:38'),
(150, 29, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 11:52:50'),
(151, 29, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 11:53:19'),
(152, 30, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 11:58:47'),
(153, 30, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 12:10:32'),
(154, 31, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 12:11:58'),
(155, 31, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 12:12:03'),
(156, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 17:45:33'),
(157, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 18:09:16'),
(158, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 18:09:30'),
(159, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 18:11:15'),
(160, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 18:11:36'),
(161, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 18:13:57'),
(162, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 18:14:16'),
(163, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 18:15:04'),
(164, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 18:49:04'),
(165, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 18:50:35'),
(166, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 23:59:44'),
(167, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 00:06:06'),
(168, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 00:37:45'),
(169, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 15:33:57'),
(170, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 15:46:03'),
(171, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 15:46:24'),
(172, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 15:46:31'),
(173, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 15:47:04'),
(174, 15, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 15:47:09'),
(175, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 16:24:28'),
(176, 33, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 21:23:12'),
(177, 34, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 23:20:22'),
(178, 34, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 23:51:11'),
(179, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 23:52:13'),
(180, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 00:13:12'),
(181, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 00:13:36'),
(182, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 00:16:16'),
(183, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 00:17:14'),
(184, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 00:24:35'),
(185, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 00:25:07'),
(186, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 00:29:12'),
(187, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 00:29:29'),
(188, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 20:35:12'),
(189, 15, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 23:38:23'),
(190, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-03 23:38:36'),
(191, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 11:26:56'),
(192, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 11:54:05'),
(193, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 18:45:00'),
(194, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 18:51:22'),
(195, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 20:14:10'),
(196, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 20:14:36'),
(197, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 20:14:48'),
(198, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 20:15:12'),
(199, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-05 21:24:29'),
(200, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 12:24:47'),
(201, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 14:05:22'),
(202, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 19:17:45'),
(203, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 21:22:23'),
(204, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 23:04:43'),
(205, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 23:53:21'),
(206, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:06:44'),
(207, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:06:56'),
(208, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:08:58'),
(209, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:09:12'),
(210, 14, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:11:52'),
(211, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 00:12:04'),
(212, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 16:40:45'),
(213, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 17:38:47'),
(214, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 17:51:49'),
(215, 36, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 18:36:07'),
(216, 37, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 18:51:09'),
(217, 37, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 19:13:05'),
(218, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 20:40:52'),
(219, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-07 20:42:32'),
(220, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 11:26:39'),
(221, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 12:04:27'),
(222, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 12:12:06'),
(223, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 16:16:17'),
(224, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 16:21:37'),
(225, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 16:25:33'),
(226, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-09 20:54:31'),
(227, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 20:34:49'),
(228, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 00:22:53'),
(229, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:02:34'),
(230, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:04:51'),
(231, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 20:22:59'),
(232, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 20:32:16'),
(233, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 20:33:19'),
(234, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 20:47:04'),
(235, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 02:01:44'),
(236, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 02:07:38'),
(237, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 02:07:51'),
(238, 15, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 02:08:06'),
(239, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 02:12:55'),
(240, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 11:12:26'),
(241, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 12:50:27'),
(242, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-13 23:54:18'),
(243, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 18:42:56'),
(244, 14, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 20:40:14'),
(245, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-14 20:40:33'),
(246, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 16:51:13'),
(247, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 17:06:19'),
(248, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 17:06:48'),
(249, 14, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 17:18:05'),
(250, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 17:18:56'),
(251, 36, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 17:20:27'),
(252, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 22:17:41'),
(253, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 22:26:02'),
(254, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 22:26:16'),
(255, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 22:27:08'),
(256, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 22:27:31'),
(257, 36, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 22:37:41'),
(258, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 22:37:52'),
(259, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-15 23:04:24'),
(260, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-16 00:19:17'),
(261, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 18:48:54'),
(262, 37, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-17 21:54:00'),
(263, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 00:02:41'),
(264, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 00:26:19'),
(265, 15, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 00:27:23'),
(266, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 00:27:36'),
(267, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 00:58:30'),
(268, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 00:58:45'),
(269, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 00:59:43'),
(270, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 00:59:55'),
(271, 15, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 01:01:36'),
(272, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 01:01:51'),
(273, 14, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 01:04:22'),
(274, 15, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 01:04:33'),
(275, 15, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 01:06:01'),
(276, 37, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 01:06:20'),
(277, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 10:05:33'),
(278, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 10:22:14'),
(279, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 10:25:39'),
(280, 14, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 10:37:17'),
(281, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 10:37:46'),
(282, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 11:23:26'),
(283, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 11:28:06'),
(284, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 11:28:21'),
(285, 12, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 11:30:00'),
(286, 13, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 11:30:11'),
(287, 13, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 11:35:36'),
(288, 14, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 12:52:33'),
(289, 12, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 13:46:29');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_admin_earnings`
--
ALTER TABLE `tbl_admin_earnings`
  ADD CONSTRAINT `tbl_admin_earnings_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `tbl_orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_cart`
--
ALTER TABLE `tbl_cart`
  ADD CONSTRAINT `tbl_cart_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `tbl_cart` (`cart_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_cart_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tbl_cart` (`cart_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_notifications`
--
ALTER TABLE `tbl_notifications`
  ADD CONSTRAINT `tbl_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_orders`
--
ALTER TABLE `tbl_orders`
  ADD CONSTRAINT `tbl_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_order_tracking`
--
ALTER TABLE `tbl_order_tracking`
  ADD CONSTRAINT `tbl_order_tracking_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `tbl_orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_reviews`
--
ALTER TABLE `tbl_reviews`
  ADD CONSTRAINT `tbl_reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
