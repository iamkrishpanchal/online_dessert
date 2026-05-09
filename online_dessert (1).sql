-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 21, 2026 at 05:58 AM
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  PRIMARY KEY (`categories_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
-- Table structure for table `vendor_tbl`
--

DROP TABLE IF EXISTS `vendor_tbl`;
CREATE TABLE IF NOT EXISTS `vendor_tbl` (
  `vendor_id` int NOT NULL AUTO_INCREMENT,
  `vendor_name` varchar(25) NOT NULL,
  `vendor_email` varchar(25) NOT NULL,
  `vendor_address` varchar(100) NOT NULL,
  `vendor_business_name` varchar(25) NOT NULL,
  `vendor_password` varchar(25) NOT NULL,
  `register_date` date NOT NULL,
  PRIMARY KEY (`vendor_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
