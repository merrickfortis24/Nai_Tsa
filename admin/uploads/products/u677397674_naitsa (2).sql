-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 09, 2025 at 11:11 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u677397674_naitsa`
--

-- --------------------------------------------------------

--
-- Table structure for table `addons`
--

CREATE TABLE `addons` (
  `Addon_ID` int(11) NOT NULL,
  `Addon_Name` varchar(100) NOT NULL,
  `Addon_Price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `Created_At` datetime NOT NULL DEFAULT current_timestamp(),
  `Updated_At` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addons`
--

INSERT INTO `addons` (`Addon_ID`, `Addon_Name`, `Addon_Price`, `Status`, `Created_At`, `Updated_At`) VALUES
(1, 'Yakult', 15.00, 'Active', '2025-10-08 14:32:47', '2025-10-08 14:32:47'),
(2, 'Blueberry Popping Boba', 15.00, 'Active', '2025-10-08 14:36:35', '2025-10-08 14:36:35'),
(3, 'Strawberry Popping Boba', 15.00, 'Active', '2025-10-08 14:36:52', '2025-10-08 14:36:52'),
(4, 'Lychee Popping Boba', 15.00, 'Active', '2025-10-08 14:37:18', '2025-10-08 14:37:18'),
(5, 'Creamcheese', 10.00, 'Active', '2025-10-08 14:37:30', '2025-10-08 14:37:30'),
(6, 'Coconut Kelle/Nata', 10.00, 'Active', '2025-10-08 14:37:54', '2025-10-08 14:37:54'),
(7, 'Pearl', 10.00, 'Active', '2025-10-08 14:38:03', '2025-10-08 14:38:03');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `Admin_ID` int(11) NOT NULL,
  `Admin_Name` varchar(100) NOT NULL,
  `Admin_Password` varchar(100) NOT NULL,
  `Admin_Email` varchar(100) NOT NULL,
  `Admin_Role` enum('Super Admin','Manager','Staff') NOT NULL,
  `Created_At` datetime NOT NULL DEFAULT current_timestamp(),
  `Updated_At` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `Reset_Token` varchar(255) DEFAULT NULL,
  `Reset_Expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`Admin_ID`, `Admin_Name`, `Admin_Password`, `Admin_Email`, `Admin_Role`, `Created_At`, `Updated_At`, `Status`, `Reset_Token`, `Reset_Expires`) VALUES
(1, 'John Merrick Fortis', '$2y$10$eYlTLYFOVdkRFrzCZUMzvOp4yx6xA1JkxkVCCcH6mVcHRG0u5XAN6', 'fortismerrick@gmail.com', 'Super Admin', '2025-06-16 19:04:56', '2025-09-04 20:51:16', 'Active', '1edf410016f0f2cbfcf024dd948d666f1638b62ff1cf0adf2a109e388ddb5ffc', '2025-09-04 15:51:16'),
(6, 'James Andrew P. Onaa', '$2y$10$ToMAHHjptSNVVqT/yIOr9uKEX/vyDegrQbIZO3hAelJgCZWjBlRxq', 'jamesona@gmail.com', 'Manager', '2025-06-17 22:54:05', '2025-06-17 23:20:31', 'Active', NULL, NULL),
(11, 'DTG', '$2y$10$M8/Tong70TKT.7wa5Yy.DedICbevoPzBYrKxMt99lXWDWRfF.fF7u', 'dtg@gmail.com', 'Super Admin', '2025-06-23 13:54:34', NULL, 'Active', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `blocked_users`
--

CREATE TABLE `blocked_users` (
  `customer_id` int(11) NOT NULL,
  `blocked_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reason` varchar(255) NOT NULL,
  `blocked_by` int(11) DEFAULT NULL,
  `auto_block` tinyint(1) NOT NULL DEFAULT 1,
  `last_eval` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_users_log`
--

CREATE TABLE `blocked_users_log` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `action` enum('BLOCK','UNBLOCK','EVALUATE') NOT NULL,
  `reason` varchar(255) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `blocked_users_log`
--

INSERT INTO `blocked_users_log` (`id`, `customer_id`, `action`, `reason`, `admin_id`, `created_at`) VALUES
(10, 3, 'BLOCK', 'Unpaid streak', NULL, '2025-10-07 08:29:34'),
(11, 3, 'UNBLOCK', 'Manual unblock', 1, '2025-10-07 08:30:40'),
(12, 3, 'BLOCK', 'Unpaid streak', NULL, '2025-10-07 08:30:41'),
(13, 3, 'UNBLOCK', 'Manual unblock', 1, '2025-10-08 13:00:36'),
(14, 3, 'BLOCK', 'Unpaid streak', NULL, '2025-10-08 13:00:40'),
(15, 3, 'UNBLOCK', 'Manual unblock', 1, '2025-10-08 13:00:43'),
(16, 3, 'BLOCK', 'Unpaid streak', NULL, '2025-10-08 13:00:45'),
(17, 3, 'UNBLOCK', 'Manual unblock', 1, '2025-10-08 23:50:16'),
(18, 3, 'BLOCK', 'Unpaid streak', NULL, '2025-10-08 23:56:08'),
(19, 3, 'UNBLOCK', 'Manual unblock', 1, '2025-10-08 23:56:32');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `Category_ID` int(11) NOT NULL,
  `Category_Name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`Category_ID`, `Category_Name`) VALUES
(3, 'Barkada Bundle'),
(1, 'Frappes'),
(2, 'Fruit Tea'),
(8, 'Ice Cream'),
(4, 'Iced Coffee'),
(9, 'Milk Base'),
(5, 'Milktea'),
(6, 'Snacks'),
(7, 'Tropical Soda');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `Customer_ID` int(11) NOT NULL,
  `Customer_Name` varchar(100) NOT NULL,
  `Customer_Email` varchar(100) NOT NULL,
  `Contact_Number` varchar(30) DEFAULT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `Customer_Password` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(128) DEFAULT NULL,
  `verification_sent_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`Customer_ID`, `Customer_Name`, `Customer_Email`, `Contact_Number`, `is_blocked`, `Customer_Password`, `reset_token`, `reset_expires`, `is_verified`, `verification_token`, `verification_sent_at`, `verified_at`) VALUES
(1, 'John Merrick Faigmani Fortis', 'fortismerrick@gmail.com', NULL, 1, '$2y$10$DcgElXbigZ5cFI3CYpP/NOfJm33st8IgxaxXM8xrCnm9MSvE5K55y', NULL, NULL, 1, NULL, '2025-10-07 00:20:10', '2025-10-07 00:20:28'),
(2, 'John Merrick Faigmani Fortis', 'fortisjohnmerrick@gmail.com', '09940780881', 0, '$2y$10$KCrVlnv1gruGMHwWLyDaZOHS7m.cAuFtSlpjk9ciliTFabuoR7AGe', NULL, NULL, 1, NULL, '2025-10-08 07:02:26', '2025-10-08 07:02:46'),
(3, 'John Merrick Faigmani Fortis', 'jamesona2904@gmail.com', '09940780881', 0, '$2y$10$cB.xVHA51OVDYBE5Kg5ZFOazr9O9yT98MIU0xzy/mkvTYlWeVzQ3e', NULL, NULL, 1, NULL, '2025-10-07 01:10:40', '2025-10-07 01:11:02'),
(4, 'james', 'topg41137@gmail.com', NULL, 0, '$2y$10$lcbsWdwODQWcrqbM6fPCqOncyl5btqw.s6vphJQ63F/oQoe3sVyZK', NULL, NULL, 1, NULL, '2025-10-08 04:48:05', '2025-10-08 04:48:23'),
(7, 'John', 'merrickfortis2004@gmail.com', NULL, 0, '$2y$10$Y/ODhsdbFeO4NNz92Zi2BeO5wbsA/H06S.ld6vq0clVFoql.heKH.', NULL, NULL, 1, NULL, '2025-10-09 07:01:16', '2025-10-09 07:01:41'),
(8, 'Queen Trixia Fallarme', 'trixiafallarme@gmail.com', NULL, 0, '$2y$10$3PN/uT3lo.vzP5r3ysafBO8FC8/ELyk4YTHmptCjHHNMnGWwZwHJu', NULL, NULL, 1, NULL, '2025-10-09 21:49:57', '2025-10-09 21:50:20');

-- --------------------------------------------------------

--
-- Table structure for table `customer_address`
--

CREATE TABLE `customer_address` (
  `Customer_ID` int(11) NOT NULL,
  `Street` varchar(255) DEFAULT NULL,
  `Barangay` varchar(255) DEFAULT NULL,
  `City` varchar(255) DEFAULT NULL,
  `Contact_Number` varchar(50) DEFAULT NULL,
  `Updated_At` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `customer_address`
--

INSERT INTO `customer_address` (`Customer_ID`, `Street`, `Barangay`, `City`, `Contact_Number`, `Updated_At`) VALUES
(2, 'Maple Road, Saint Joseph Homes', 'Pusil', 'Lipa', '09940780881', '2025-10-08 09:41:39'),
(3, 'Ayala Highway, Sabang', 'Sabang', 'Lipa', '09940780881', '2025-10-07 07:46:37');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `Driver_ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Gmail` varchar(255) NOT NULL,
  `Password_Hash` varchar(255) NOT NULL,
  `Api_Token` varchar(128) DEFAULT NULL,
  `Token_Expires` datetime DEFAULT NULL,
  `Created_At` datetime DEFAULT current_timestamp(),
  `Reset_Token` varchar(255) DEFAULT NULL,
  `Reset_Expires` datetime DEFAULT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `Last_Login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`Driver_ID`, `Name`, `Gmail`, `Password_Hash`, `Api_Token`, `Token_Expires`, `Created_At`, `Reset_Token`, `Reset_Expires`, `Status`, `Last_Login`) VALUES
(6, 'John Merrick Faigmani Fortis', 'john@gmail.com', '$2y$10$kaB2oUxLAtEuZ2DMkPDSnuugYjb1kLZBbXfbjr38kn.8Ti/KlIvwe', '1a4c65c962dd02cae2403ed5cabc4d98014efe89137de11fa51b8437daeebf7d', '2025-10-19 11:51:02', '2025-09-19 06:43:06', NULL, NULL, 'Active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `Notification_ID` int(11) NOT NULL,
  `Type` varchar(30) NOT NULL,
  `Title` varchar(100) NOT NULL,
  `Message` text NOT NULL,
  `Created_At` datetime NOT NULL DEFAULT current_timestamp(),
  `Read_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`Notification_ID`, `Type`, `Title`, `Message`, `Created_At`, `Read_At`) VALUES
(1, '', 'Payment Confirmed', 'Driver Rider One confirmed payment for Order #95', '2025-08-31 01:38:09', NULL),
(2, '', 'Payment Confirmed', 'Driver Rider One confirmed payment for Order #95', '2025-08-31 01:38:10', NULL),
(3, '', 'Payment Confirmed', 'Driver John confirmed payment for Order #99', '2025-09-02 13:48:07', NULL),
(4, '', 'Payment Confirmed', 'Driver John confirmed payment for Order #97', '2025-09-02 13:48:10', NULL),
(5, '', 'Payment Confirmed', 'Driver John confirmed payment for Order #101', '2025-09-03 12:36:28', NULL),
(6, '', 'Payment Confirmed', 'Driver John confirmed payment for Order #94', '2025-09-03 12:40:26', NULL),
(7, '', 'Payment Confirmed', 'Driver John confirmed payment for Order #93', '2025-09-03 12:40:34', NULL),
(8, '', 'Payment Confirmed', 'Driver John confirmed payment for Order #92', '2025-09-03 12:40:40', NULL),
(9, '', 'Payment Confirmed', 'Driver John confirmed payment for Order #91', '2025-09-03 12:40:45', NULL),
(10, '', 'Payment Confirmed', 'Driver John confirmed payment for Order #106', '2025-09-03 14:27:23', NULL),
(11, 'fraud', 'Fraud block', 'Customer #1 blocked: Unpaid streak', '2025-10-05 15:15:57', NULL),
(12, 'fraud', 'Fraud block', 'Customer #1 blocked: Unpaid streak', '2025-10-05 15:16:56', NULL),
(13, 'fraud', 'Fraud block', 'Customer #1 blocked: Unpaid streak', '2025-10-05 15:18:02', NULL),
(14, 'fraud', 'Fraud block', 'Customer #1 blocked: Unpaid streak', '2025-10-05 15:18:12', NULL),
(15, 'fraud', 'Fraud block', 'Customer #1 blocked: Unpaid streak', '2025-10-05 15:42:06', NULL),
(16, 'fraud', 'Fraud block', 'Customer #1 blocked: Unpaid streak', '2025-10-07 00:54:27', NULL),
(17, 'fraud', 'Fraud block', 'Customer #1 blocked: Unpaid streak', '2025-10-07 01:03:34', NULL),
(18, 'fraud', 'Fraud block', 'Customer #1 blocked: Unpaid streak', '2025-10-07 01:03:57', NULL),
(19, 'fraud', 'Fraud block', 'Customer #1 blocked: Unpaid streak', '2025-10-07 01:04:03', NULL),
(20, 'fraud', 'Fraud block', 'Customer #1 blocked: Unpaid streak', '2025-10-07 01:04:40', NULL),
(21, 'fraud', 'Fraud block', 'Customer #3 blocked: Unpaid streak', '2025-10-07 08:29:34', NULL),
(22, 'fraud', 'Fraud block', 'Customer #3 blocked: Unpaid streak', '2025-10-07 08:30:41', NULL),
(23, 'fraud', 'Fraud block', 'Customer #3 blocked: Unpaid streak', '2025-10-08 13:00:40', NULL),
(24, 'fraud', 'Fraud block', 'Customer #3 blocked: Unpaid streak', '2025-10-08 13:00:45', NULL),
(25, 'fraud', 'Fraud block', 'Customer #3 blocked: Unpaid streak', '2025-10-08 23:56:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `Order_ID` int(11) NOT NULL,
  `Order_Date` datetime NOT NULL DEFAULT current_timestamp(),
  `Order_Amount` decimal(10,2) NOT NULL,
  `Customer_ID` int(11) NOT NULL,
  `Driver_ID` int(11) DEFAULT NULL,
  `order_type` enum('Pickup','Delivery') NOT NULL DEFAULT 'Delivery',
  `order_status` enum('Pending','Processing','Ready to pick up','Received','Ready to deliver','On the way','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `Driver_Status` enum('assigned','accepted','on_the_way','picked_up','delivered','rejected') NOT NULL DEFAULT 'assigned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`Order_ID`, `Order_Date`, `Order_Amount`, `Customer_ID`, `Driver_ID`, `order_type`, `order_status`, `Driver_Status`) VALUES
(1, '2025-10-07 00:29:44', 70.00, 1, 6, 'Pickup', 'Received', 'assigned'),
(5, '2025-10-07 01:11:56', 70.00, 3, 6, 'Pickup', 'Received', 'assigned'),
(6, '2025-10-07 01:13:48', 159.00, 3, 6, 'Delivery', 'Processing', 'assigned'),
(7, '2025-10-07 01:21:18', 139.00, 3, 6, 'Delivery', 'Processing', 'assigned'),
(8, '2025-10-07 04:23:10', 139.00, 3, 6, 'Delivery', 'Processing', 'assigned'),
(9, '2025-10-07 04:39:07', 159.00, 3, 6, 'Delivery', 'On the way', 'accepted'),
(10, '2025-10-07 04:41:49', 225.00, 3, 6, 'Delivery', 'On the way', 'on_the_way'),
(11, '2025-10-07 07:46:37', 139.00, 3, 6, 'Delivery', 'Delivered', 'on_the_way'),
(12, '2025-10-08 09:41:39', 199.00, 2, 6, 'Delivery', 'Processing', 'assigned'),
(13, '2025-10-09 01:00:35', 149.00, 2, 6, 'Delivery', 'Delivered', 'assigned'),
(14, '2025-10-09 21:51:27', 180.00, 8, NULL, 'Pickup', 'Received', 'assigned'),
(15, '2025-10-09 22:57:26', 60.00, 7, NULL, 'Pickup', 'Ready to pick up', 'assigned'),
(16, '2025-10-09 23:09:49', 140.00, 7, NULL, 'Pickup', 'Pending', 'assigned');

-- --------------------------------------------------------

--
-- Table structure for table `order_address`
--

CREATE TABLE `order_address` (
  `Order_ID` int(11) NOT NULL,
  `Address_ID` int(11) NOT NULL,
  `Street` varchar(255) DEFAULT NULL,
  `Barangay` varchar(100) DEFAULT NULL,
  `City` varchar(100) DEFAULT NULL,
  `customer_lat` decimal(10,7) DEFAULT NULL,
  `customer_lng` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_address`
--

INSERT INTO `order_address` (`Order_ID`, `Address_ID`, `Street`, `Barangay`, `City`, `customer_lat`, `customer_lng`) VALUES
(6, 1, 'Ayala Highway, Sabang', 'Sabang', 'Lipa', 13.9539142, 121.1645474),
(7, 2, 'Ayala Highway, Sabang', 'Sabang', 'Lipa', 13.9553419, 121.1624588),
(8, 3, 'Lipa City Grand Terminal (UV Express), Balintawak', 'Balintawak', 'Lipa', 13.9553552, 121.1622899),
(9, 4, 'Cedar Road, Saint Joseph Homes', 'Pusil', 'Lipa', 13.9808070, 121.1647746),
(10, 5, 'Guevarra Street, San Sebastian Village', 'Hidalgo', 'Tanauan', 14.0809911, 121.1537315),
(11, 6, 'Ayala Highway, Sabang', 'Sabang', 'Lipa', 13.9553340, 121.1624435),
(12, 7, 'Maple Road, Saint Joseph Homes', 'Pusil', 'Lipa', 13.9807282, 121.1646573),
(13, 8, 'Maple Road, Saint Joseph Homes', 'Pusil', 'Lipa', 13.9814018, 121.1643047);

-- --------------------------------------------------------

--
-- Table structure for table `order_delivery`
--

CREATE TABLE `order_delivery` (
  `Order_ID` int(11) NOT NULL,
  `Delivery_ID` int(11) NOT NULL,
  `Delivery_Fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Delivery_Distance_Km` decimal(6,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_delivery`
--

INSERT INTO `order_delivery` (`Order_ID`, `Delivery_ID`, `Delivery_Fee`, `Delivery_Distance_Km`) VALUES
(6, 1, 89.00, 8.03),
(7, 2, 69.00, 7.87),
(8, 3, 69.00, 7.86),
(9, 4, 89.00, 9.48),
(10, 5, 155.00, 18.01),
(11, 6, 69.00, 7.87),
(12, 7, 89.00, 9.47),
(13, 8, 89.00, 9.48);

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `Order_Item_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Size_Variant_ID` int(11) DEFAULT NULL,
  `Flavor_Variant_ID` int(11) DEFAULT NULL,
  `Quantity` int(11) NOT NULL CHECK (`Quantity` > 0),
  `Price` decimal(10,2) NOT NULL,
  `Unit_Price` decimal(10,2) DEFAULT NULL,
  `Size_Code` varchar(20) DEFAULT NULL,
  `Size_Price` decimal(10,2) DEFAULT NULL,
  `Instruction` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`Order_Item_ID`, `Order_ID`, `Product_ID`, `Size_Variant_ID`, `Flavor_Variant_ID`, `Quantity`, `Price`, `Unit_Price`, `Size_Code`, `Size_Price`, `Instruction`) VALUES
(1, 1, 1, NULL, NULL, 1, 70.00, 70.00, NULL, 70.00, NULL),
(5, 5, 1, NULL, NULL, 1, 70.00, 70.00, NULL, 70.00, NULL),
(6, 6, 1, NULL, NULL, 1, 70.00, 70.00, NULL, 70.00, NULL),
(7, 7, 1, NULL, NULL, 1, 70.00, 70.00, NULL, 70.00, NULL),
(8, 8, 2, NULL, NULL, 1, 70.00, 70.00, NULL, 70.00, NULL),
(9, 9, 2, NULL, NULL, 1, 70.00, 70.00, NULL, 70.00, NULL),
(10, 10, 4, NULL, NULL, 1, 70.00, 70.00, NULL, 70.00, NULL),
(11, 11, 4, NULL, NULL, 1, 70.00, 70.00, NULL, 70.00, NULL),
(12, 12, 44, NULL, NULL, 2, 55.00, 55.00, '22oz', 55.00, NULL),
(13, 13, 4, NULL, NULL, 1, 60.00, NULL, '16oz', 60.00, NULL),
(14, 14, 3, NULL, NULL, 1, 80.00, NULL, '22oz', 80.00, NULL),
(15, 14, 45, NULL, NULL, 1, 100.00, NULL, 'overload', 100.00, NULL),
(16, 15, 4, NULL, NULL, 1, 60.00, NULL, '16oz', 60.00, NULL),
(17, 16, 3, NULL, NULL, 2, 70.00, NULL, '16oz', 70.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_item_addons`
--

CREATE TABLE `order_item_addons` (
  `Order_Item_Addon_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `Order_Item_ID` int(11) DEFAULT NULL,
  `Product_ID` int(11) NOT NULL,
  `Addon_ID` int(11) NOT NULL,
  `Addon_Name` varchar(100) NOT NULL,
  `Addon_Price` decimal(10,2) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_payment_receipt`
--

CREATE TABLE `order_payment_receipt` (
  `Order_ID` int(11) NOT NULL,
  `Payment_Receipt_ID` int(11) NOT NULL,
  `payment_received_at` datetime DEFAULT NULL,
  `payment_received_by` varchar(100) DEFAULT NULL,
  `Proof_Photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `Order_Status_Event_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `Event_Type` enum('picked_up','on_the_way','delivered') NOT NULL,
  `Occurred_At` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `Payment_ID` int(11) NOT NULL,
  `Payment_Date` datetime NOT NULL DEFAULT current_timestamp(),
  `Payment_Method` varchar(50) NOT NULL,
  `Payment_Amount` decimal(10,2) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `Admin_ID` int(11) NOT NULL,
  `payment_status` enum('Unpaid','Paid') NOT NULL DEFAULT 'Unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`Payment_ID`, `Payment_Date`, `Payment_Method`, `Payment_Amount`, `Order_ID`, `Admin_ID`, `payment_status`) VALUES
(1, '2025-10-07 00:29:44', 'COD', 70.00, 1, 1, 'Paid'),
(5, '2025-10-07 01:11:56', 'COD', 70.00, 5, 1, 'Paid'),
(6, '2025-10-07 01:13:48', 'COD', 159.00, 6, 1, 'Unpaid'),
(7, '2025-10-07 01:21:18', 'COD', 139.00, 7, 1, 'Unpaid'),
(8, '2025-10-07 04:23:10', 'COD', 139.00, 8, 1, 'Unpaid'),
(9, '2025-10-07 04:39:07', 'COD', 159.00, 9, 1, 'Unpaid'),
(10, '2025-10-07 04:41:49', 'COD', 225.00, 10, 1, 'Unpaid'),
(11, '2025-10-07 07:46:37', 'COD', 139.00, 11, 1, 'Paid'),
(12, '2025-10-08 09:41:39', 'COD', 199.00, 12, 1, 'Unpaid'),
(13, '2025-10-09 01:00:35', 'COD', 149.00, 13, 1, 'Paid'),
(14, '2025-10-09 21:51:27', 'COD', 180.00, 14, 1, 'Paid'),
(15, '2025-10-09 22:57:26', 'COD', 60.00, 15, 1, 'Unpaid'),
(16, '2025-10-09 23:09:49', 'COD', 140.00, 16, 1, 'Unpaid');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `Product_ID` int(11) NOT NULL,
  `Product_Name` varchar(255) NOT NULL,
  `Product_desc` text DEFAULT NULL,
  `Product_allergens` varchar(255) DEFAULT NULL,
  `Product_Image` varchar(255) DEFAULT NULL,
  `Created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `Updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `Admin_ID` int(11) NOT NULL,
  `Category_ID` int(11) NOT NULL,
  `Price_ID` int(11) NOT NULL,
  `Primary_Size_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`Product_ID`, `Product_Name`, `Product_desc`, `Product_allergens`, `Product_Image`, `Created_at`, `Updated_at`, `Admin_ID`, `Category_ID`, `Price_ID`, `Primary_Size_ID`) VALUES
(1, 'Avocado Frappe', 'The Avocado Frappe from Nai Tsa is a creamy and refreshing beverage that blends the rich, buttery flavor of ripe avocados into a smooth icy treat. Its velvety texture makes every sip indulgent, with the natural taste of avocado perfectly balanced by a subtle sweetness. Topped with a generous swirl of whipped cream, it adds an extra layer of creaminess to the drink. A drizzle of matcha syrup cascades over the whipped cream, enhancing both the flavor and visual appeal with a vibrant green touch. To complete the experience, golden cookie crumbs are sprinkled on top, giving a delightful crunch. This frappe delivers the perfect harmony of indulgence and nourishment, making it a unique treat that is both flavorful and refreshing. Served in a clear cup, its vibrant green color shines through, showcasing its freshness. Whether enjoyed on a hot day or as a satisfying pick-me-up, it brings comfort and delight in every sip. Perfect for sharing with friends or savoring alone, the Avocado Frappe is more than just a drink—it’s a creamy masterpiece crafted with care. With every cup, Nai Tsa continues to redefine refreshment and indulgence.', '', '68e45c9cc5f77_avocado frappes.png', '2025-10-07 00:19:40', NULL, 1, 1, 2, NULL),
(2, 'Cookies And Cream', 'The Cookies and Cream Frappe from Nai Tsa is a rich and creamy delight made for cookie lovers. Blended with crushed chocolate cookies and smooth cream, it delivers the perfect balance of sweetness and crunch in every sip. Its velvety texture is both refreshing and indulgent, making it a favorite choice for dessert-style drinks. Topped with a swirl of whipped cream, it adds extra creaminess that enhances the overall experience. Generous cookie crumbs are sprinkled on top, giving every sip a satisfying crunch. This frappe is not only visually tempting but also packed with irresistible flavor that cookie fans can’t resist. Served in a clear cup, the mix of creamy white and cookie specks creates a deliciously inviting look. It’s the perfect treat for cooling down on a warm day or enjoying as a sweet pick-me-up anytime. Whether you’re sharing with friends or treating yourself, it’s a drink that never disappoints. With every Cookies and Cream Frappe, Nai Tsa serves up indulgence and refreshment in one delightful cup.', '', '68e45d4302a33_cookies and cream frappes.png', '2025-10-07 00:22:27', NULL, 1, 1, 2, NULL),
(3, 'Barbie', 'The Barbie Frappe from Nai Tsa is a vibrant and playful drink that captures sweetness and style in every cup. Its striking pink color instantly stands out, making it as fun to look at as it is to enjoy. Blended to creamy perfection, this frappe offers a smooth and refreshing taste that feels like a sweet escape. Topped with fluffy whipped cream, it adds a rich layer of creaminess to balance the icy blend. A drizzle of pink syrup cascades over the whipped cream, enhancing both its flavor and aesthetic appeal. Sprinkles and candy toppings complete the look, giving it a whimsical, picture-perfect finish. This frappe is designed not only to satisfy your taste buds but also to brighten your day with its cheerful presentation. Served in a clear cup, its bold pink hue is a statement of fun and indulgence. Perfect for those who love sweet, creamy treats, it’s a must-try for anyone who enjoys unique and Instagram-worthy drinks. With every sip, Nai Tsa’s Barbie Frappe delivers a delightful blend of flavor, color, and joy.', '', '68e45da773948_barbie frappes.png', '2025-10-07 00:24:07', NULL, 1, 1, 3, NULL),
(4, 'Cappuccino', 'The Cappuccino Frappe from Nai Tsa is a bold and refreshing twist on the classic coffee favorite. Crafted with rich espresso flavors, it delivers the perfect balance of strong coffee and creamy sweetness in every sip. Blended with ice to achieve a smooth and velvety texture, this frappe offers both energy and indulgence. Topped with a swirl of whipped cream, it adds a touch of creaminess that complements the coffee’s boldness. A drizzle of chocolate or caramel syrup enhances the flavor, creating a delightful finish. The frappe is perfect for coffee lovers who want a cool and satisfying way to enjoy their daily caffeine fix. Served in a clear cup, its creamy brown tones showcase its rich and inviting flavor. Ideal for busy mornings, afternoon pick-me-ups, or late-night cravings, it’s a versatile drink for any time of day. Each sip combines the familiar comfort of cappuccino with the refreshing chill of a frappe. With the Cappuccino Frappe, Nai Tsa turns your favorite coffee into a creamy, energizing masterpiece.', '', '68e45e015758e_cappuccino frappes.png', '2025-10-07 00:25:37', NULL, 1, 1, 2, NULL),
(5, 'Caramel Macchiato', 'The Caramel Macchiato Frappe from Nai Tsa is a sweet and energizing blend crafted for coffee lovers with a love for caramel indulgence. Smooth espresso is perfectly combined with creamy milk and ice, creating a rich and velvety texture in every sip. The bold coffee notes are balanced by the buttery sweetness of caramel, offering a harmonious flavor that’s both refreshing and satisfying. Topped with fluffy whipped cream, it adds an extra layer of creaminess that enhances the experience. A generous drizzle of golden caramel sauce flows over the whipped cream, giving every sip a sweet finish. Served in a clear cup, its layered tones of coffee and caramel create a drink as beautiful as it is delicious. This frappe is perfect for those who crave the classic caramel macchiato but with an icy twist. It’s a versatile treat, ideal as a morning boost, an afternoon delight, or even a dessert-like indulgence. Each sip delivers the perfect mix of bold coffee richness and sweet caramel comfort. With the Caramel Macchiato Frappe, Nai Tsa transforms a café classic into a refreshing masterpiece.', '', '68e45e603e4e9_caramel macchiato frappes.png', '2025-10-07 00:27:12', NULL, 1, 1, 2, NULL),
(7, 'Blue Lemonade', 'The Blue Lemonade from Nai Tsa is a refreshing and vibrant drink that’s as eye-catching as it is thirst-quenching. Its bold blue hue instantly stands out, making it a fun and energizing choice for any occasion. Crafted with the zesty tang of fresh lemons, it offers a perfect balance of sweet and sour flavors. Served over ice, it delivers a crisp and cooling sensation with every sip. The bright citrus taste is both uplifting and revitalizing, making it ideal for hot days or as a lively pick-me-up. Its sparkling freshness leaves you feeling recharged and satisfied. Presented in a clear cup, the striking color of the drink creates a visually appealing and Instagram-worthy look. Whether enjoyed with a meal or on its own, it pairs well with both sweet and savory treats. Light, fruity, and full of flavor, it’s a great alternative to traditional soft drinks. With the Blue Lemonade, Nai Tsa gives you a colorful way to stay refreshed and energized.', '', '68e46039b6b5d_Blue Lemonade.png', '2025-10-07 00:35:05', NULL, 1, 2, 3, NULL),
(8, 'Blueberry', 'The Blueberry Fruit Tea from Nai Tsa is a fruity and refreshing drink bursting with natural flavor. Made with real tea and infused with the sweetness of ripe blueberries, it creates a perfectly balanced taste that is both light and satisfying. The vibrant blueberry flavor adds a deliciously tangy twist, making every sip bright and invigorating. Served over ice, it delivers a crisp and cooling experience that’s perfect for warm days. Its deep, inviting color reflects its rich fruity essence, making it as beautiful as it is tasty. With the natural antioxidants of tea and the refreshing notes of blueberry, this drink is both nourishing and energizing. Whether enjoyed as a midday refresher or a fun companion to your meal, it never fails to satisfy. Presented in a clear cup, the blend of tea and fruit creates a refreshing look that’s Instagram-worthy. It’s a versatile drink that appeals to both tea lovers and fruit drink fans alike. With every sip, Nai Tsa’s Blueberry Fruit Tea delivers the perfect harmony of fruity sweetness and tea’s calming refreshment.', '', '68e460a5e4a4d_Blueberry.png', '2025-10-07 00:36:53', NULL, 1, 2, 2, NULL),
(9, 'Green Apple', 'The Green Apple Fruit Tea from Nai Tsa is a crisp and refreshing drink that bursts with fruity goodness. Blending the natural taste of brewed tea with the sweet and tangy flavor of green apples, it creates a lively and energizing sip. Its refreshing tartness is perfectly balanced by the smoothness of the tea, making it both flavorful and light. Served over ice, it offers a cooling sensation that’s ideal for warm days or as a midday refresher. The bright green hue of the drink makes it visually stunning and instantly appealing. Each sip delivers a mix of fruity zest and calming tea notes that awaken your senses. With the added benefits of antioxidants and natural fruit flavors, it’s a drink that feels as good as it tastes. Perfect on its own or paired with your favorite snacks, it’s a versatile choice for any occasion. Whether you’re craving something sweet, tangy, or revitalizing, this tea has it all. With the Green Apple Fruit Tea, Nai Tsa brings you a drink that’s crisp, colorful, and completely refreshing.', '', '68e4619e9c54a_Green Apple.png', '2025-10-07 00:41:02', NULL, 1, 2, 2, NULL),
(10, 'Choco Kisses', 'The Choco Kisses Frappe from Nai Tsa is a decadent chocolate lover’s dream in a cup. Blended with rich chocolate, ice, and cream, it delivers a smooth and velvety texture that’s both refreshing and indulgent. Each sip bursts with the familiar sweetness of chocolate, making it a comforting and irresistible treat. Topped with a swirl of whipped cream, it adds an extra layer of creaminess to the drink. A drizzle of chocolate syrup cascades over the top, enhancing the flavor while making it visually delightful. To complete the indulgence, chocolate chips or candy bits are sprinkled, adding a satisfying crunch. Its deep, rich color in a clear cup showcases the chocolate goodness inside. Perfect for any time of the day, it’s a sweet pick-me-up that doubles as a dessert in drink form. Whether you’re sharing with friends or treating yourself, it guarantees pure chocolate happiness. With the Choco Kisses Frappe, Nai Tsa serves up a chocolate masterpiece that will make every sip unforgettable.', '', '68e46d1a7b13b_choco kisses frappes.png', '2025-10-07 01:30:02', NULL, 1, 1, 2, NULL),
(11, 'Choco Mousse', 'The Choco Mousse Frappe from Nai Tsa is a rich and creamy chocolate indulgence crafted to satisfy every sweet craving. Blended with smooth chocolate and ice, it creates a velvety texture that’s both refreshing and decadent. Each sip bursts with deep cocoa flavor, perfectly balanced with a creamy sweetness. Topped with a swirl of whipped cream, it adds a light and airy contrast to the drink. A drizzle of chocolate syrup runs over the top, making it both visually appealing and extra indulgent. Finished with a sprinkle of chocolate shavings or bits, it delivers the mousse-like richness that chocolate lovers adore. Served in a clear cup, its layered shades of chocolate highlight its irresistible appeal. Ideal as a dessert drink or a sweet pick-me-up, it’s perfect for any occasion. Whether enjoyed alone or shared, it promises a truly satisfying chocolate experience. With the Choco Mousse Frappe, Nai Tsa turns a classic mousse dessert into a refreshing frappe masterpiece.', '', '68e46dc2328fb_choco mousse frappes.png', '2025-10-07 01:32:50', NULL, 1, 1, 2, NULL),
(12, 'Coffee Crumble', 'The Coffee Crumble Frappe from Nai Tsa is a delightful fusion of bold coffee flavor and sweet, crunchy goodness. Blended with freshly brewed coffee, cream, and ice, it creates a smooth and refreshing texture that energizes with every sip. The robust taste of coffee is perfectly balanced with a hint of sweetness, making it both satisfying and indulgent. Topped with fluffy whipped cream, it adds a rich, creamy layer to complement the drink. A drizzle of chocolate or caramel syrup flows over the top, enhancing both flavor and presentation. Generous cookie or biscuit crumbles are sprinkled on the whipped cream, giving each sip a crunchy surprise. Its inviting coffee-brown color in a clear cup makes it as tempting to the eyes as it is to the taste buds. Perfect for mornings, afternoons, or even late-night cravings, it’s a versatile drink for coffee lovers. Whether you’re after a caffeine boost or a dessert-like treat, it’s an indulgence worth savoring. With the Coffee Crumble Frappe, Nai Tsa brings you a bold, creamy, and crunchy masterpiece in every cup.', '', '68e46e11644ca_coffee crumble frappes.png', '2025-10-07 01:34:09', NULL, 1, 1, 2, NULL),
(13, 'Double Dutch', 'The Double Dutch Frappe from Nai Tsa is a fun and flavorful treat that combines a medley of sweet chocolate goodness in one refreshing drink. Blended with creamy chocolate, marshmallows, nuts, and candy bits, it delivers a playful mix of textures and flavors in every sip. Its smooth and velvety base is perfectly complemented by the crunchy and chewy surprises hidden inside. Topped with a swirl of whipped cream, it adds an extra layer of creaminess to balance the rich blend. A drizzle of chocolate syrup cascades over the whipped cream, making the drink both indulgent and visually appealing. Sprinkles of candy bits and marshmallow chunks complete the topping, giving it a delightful finish. Served in a clear cup, the frappe’s layers of chocolate and colorful toppings make it an instant eye-catcher. Perfect as a dessert drink or a sweet pick-me-up, it’s loved by those who crave variety in every sip. Whether enjoyed alone or with friends, it brings fun, flavor, and sweetness together in one cup. With the Double Dutch Frappe, Nai Tsa turns a classic ice cream favorite into a refreshing frappe masterpiece.', '', '68e46e887a7b7_double dutch frappes.png', '2025-10-07 01:36:08', NULL, 1, 1, 2, NULL),
(14, 'Mango Graham', 'The Mango Graham Frappe from Nai Tsa is a tropical-inspired delight that blends the sweetness of ripe mangoes with the creamy goodness of graham crackers. Each sip is smooth and refreshing, offering the perfect balance of fruity freshness and dessert-like indulgence. The natural mango flavor shines through, giving the drink a vibrant, tropical taste that feels like summer in a cup. Blended with ice and cream, it creates a velvety texture that is both cooling and satisfying. Topped with fluffy whipped cream, it adds an extra layer of creaminess to the frappe. A drizzle of mango syrup cascades over the whipped topping, enhancing its fruity sweetness. Crushed graham crackers are generously sprinkled on top, giving every sip a delightful crunch. Served in a clear cup, its golden-yellow color with layers of graham makes it as inviting to the eyes as it is to the taste buds. Perfect for hot days or as a sweet pick-me-up, it’s a drink that refreshes and indulges at the same time. With the Mango Graham Frappe, Nai Tsa turns a classic Filipino favorite into a refreshing frappe masterpiece.', '', '68e46ed0338f8_mango graham frappes.png', '2025-10-07 01:37:20', NULL, 1, 1, 2, NULL),
(15, 'Mocha', 'The Mocha Frappe from Nai Tsa is the perfect blend of bold coffee and rich chocolate, crafted into one refreshing drink. Each sip delivers the smooth bitterness of espresso balanced with the sweetness of creamy chocolate. Blended with ice, it creates a velvety texture that’s both energizing and indulgent. Topped with fluffy whipped cream, it adds a creamy finish that complements the mocha base. A drizzle of chocolate syrup enhances the flavor while adding a tempting visual touch. Served in a clear cup, its deep brown tones highlight the richness of both coffee and cocoa. This frappe is a go-to choice for those who crave the harmony of coffee and chocolate in one cup. Whether enjoyed in the morning, as an afternoon boost, or as a sweet treat after meals, it satisfies both energy needs and dessert cravings. Refreshing yet rich, it’s a versatile drink fit for any occasion. With the Mocha Frappe, Nai Tsa brings together two timeless favorites in a deliciously cool masterpiece.', '', '68e46f1acd223_mocha frappes.png', '2025-10-07 01:38:34', NULL, 1, 1, 2, NULL),
(16, 'Rocky Road', 'The Rocky Road Frappe from Nai Tsa is a decadent chocolate creation packed with fun textures and bold flavors. Blended with rich chocolate, ice, and cream, it creates a smooth and indulgent base that chocolate lovers will adore. Each sip is made exciting with the addition of marshmallows, nuts, and chocolate bits, giving it that classic rocky road experience. Topped with a swirl of whipped cream, it adds a creamy contrast to the crunchy and chewy mix-ins. A drizzle of chocolate syrup cascades over the top, making it extra tempting and irresistible. Sprinkled with mini marshmallows and crushed nuts, every sip delivers a surprise that keeps you coming back for more. Served in a clear cup, its layers of chocolate and toppings make it as beautiful as it is delicious. Perfect as a dessert drink or a sweet afternoon indulgence, it satisfies both chocolate cravings and the love for texture-filled treats. Whether enjoyed alone or with friends, it’s a drink that combines comfort and fun in every sip. With the Rocky Road Frappe, Nai Tsa turns a beloved ice cream classic into a refreshing frappe masterpiece.', '', '68e46f6b55643_rocky road frappes.png', '2025-10-07 01:39:55', NULL, 1, 1, 2, NULL),
(17, 'Kiwi', 'The Kiwi Fruit Tea from Nai Tsa is a refreshing and zesty drink that captures the sweet-tart flavor of fresh kiwi in every sip. Blended with premium tea, it offers a perfect balance of fruity brightness and smooth tea notes. The natural tang of kiwi makes it a vibrant and energizing choice, especially for hot days. Lightly sweetened to enhance its tropical taste, it delivers a crisp and thirst-quenching experience. Served over ice, it’s both cooling and invigorating, making it an ideal pick-me-up anytime. Fresh kiwi bits or pulp add texture, giving each sip a burst of real fruit goodness. Its lively green hue in a clear cup makes it visually striking and appetizing. This fruit tea is not only delicious but also packed with refreshing vitamins and antioxidants from kiwi. Perfect for those who prefer a lighter, fruit-forward drink, it’s a healthier alternative to heavy, creamy beverages. With the Kiwi Fruit Tea, Nai Tsa serves up a drink that’s as revitalizing as it is flavorful.', '', '68e4701934ccd_Kiwi.png', '2025-10-07 01:42:49', NULL, 1, 2, 2, NULL),
(18, 'Lemon Tea', 'The Lemon Fruit Tea from Nai Tsa is a bright and refreshing drink that brings out the natural zest of freshly squeezed lemon. Infused with premium tea, it combines a smooth tea base with the citrusy tang of lemon for a perfectly balanced flavor. Each sip is crisp and invigorating, making it the ultimate thirst-quencher on warm days. Lightly sweetened, it enhances the lemon’s natural tartness without overpowering its freshness. Served over ice, it delivers a cooling sensation that revitalizes you instantly. Thin slices of lemon or lemon pulp add a burst of real citrus flavor and visual appeal. Its golden-yellow hue in a clear cup makes it look as refreshing as it tastes. Packed with vitamin C and antioxidants, it’s not only delicious but also a healthier beverage choice. Whether enjoyed as a mid-day refresher or a light pairing with snacks, it’s always a crowd-pleaser. With the Lemon Fruit Tea, Nai Tsa turns a simple classic into a refreshing and flavorful masterpiece.', '', '68e4705540b29_Lemon Tea.png', '2025-10-07 01:43:49', NULL, 1, 2, 2, NULL),
(19, 'Lychee', 'The Lychee Fruit Tea from Nai Tsa is a sweet and fragrant refreshment that captures the delicate tropical flavor of lychee in every sip. Infused with premium tea, it offers a smooth balance between the floral sweetness of lychee and the refreshing notes of tea. Light and aromatic, it delivers a uniquely elegant taste that stands out among fruit teas. Served over ice, it’s a cooling and revitalizing drink perfect for warm days or light afternoon breaks. Fresh lychee bits or pulp add a juicy burst of flavor, making each sip even more delightful. Its subtle sweetness makes it refreshing without being heavy, appealing to those who enjoy fruity yet light beverages. The drink’s soft golden hue with hints of lychee pulp makes it visually inviting in a clear cup. Naturally rich in antioxidants and vitamins, it’s a healthier alternative that still feels indulgent. Ideal for fruit tea lovers who want a smooth and exotic twist, it’s a drink that satisfies with elegance. With the Lychee Fruit Tea, Nai Tsa brings a tropical touch to every refreshing sip.', '', '68e470a9b098d_Lychee.png', '2025-10-07 01:45:13', NULL, 1, 2, 2, NULL),
(20, 'Passion Fruit', 'The Passion Fruit Tea from Nai Tsa is a vibrant and tangy refreshment bursting with tropical flavor. Infused with premium tea, it perfectly balances the bright tartness of passion fruit with the smooth, mellow notes of tea. Each sip is crisp, zesty, and naturally uplifting, making it an ideal drink for hot and busy days. Lightly sweetened to highlight the fruit’s natural tang, it offers a refreshing taste without being overwhelming. Served over ice, it cools you instantly while keeping the fruity flavor bold and lively. Real passion fruit seeds or pulp add texture and authenticity, giving every sip a tropical surprise. Its golden-orange color in a clear cup makes it as eye-catching as it is delicious. Packed with antioxidants and vitamin C, it’s not only refreshing but also nourishing. Perfect as a pick-me-up or a fruity alternative to creamy drinks, it’s a crowd favorite for fruit tea lovers. With the Passion Fruit Tea, Nai Tsa captures the essence of the tropics in a refreshing masterpiece.', '', '68e470fb96215_Passion Fruit.png', '2025-10-07 01:46:35', NULL, 1, 2, 2, NULL),
(21, 'Stawberry', 'The Strawberry Fruit Tea from Nai Tsa is a sweet and refreshing drink that highlights the natural juiciness of ripe strawberries. Infused with premium tea, it combines the fruity sweetness of strawberries with the smooth, crisp notes of tea for a perfectly balanced flavor. Each sip is light, cooling, and irresistibly delicious, making it a favorite for fruit tea lovers. Served over ice, it delivers a burst of freshness that’s perfect for hot days or casual hangouts. Real strawberry bits or puree add texture and authenticity, making every sip full of fruity goodness. Its rosy-red color in a clear cup makes it visually inviting and Instagram-worthy. Lightly sweetened, it enhances the fruit’s natural flavor without overpowering its freshness. Rich in antioxidants and vitamin C, it’s not only a tasty drink but also a refreshing and nourishing choice. Ideal as a mid-day treat or a light companion to snacks, it suits any occasion. With the Strawberry Fruit Tea, Nai Tsa turns a classic fruit flavor into a refreshing and delightful masterpiece.', '', '68e4714340f56_Strawberry.png', '2025-10-07 01:47:47', NULL, 1, 2, 2, NULL),
(22, 'Caramel Macchiato', 'The Caramel Macchiato Iced Coffee from Nai Tsa is a smooth and energizing drink that blends the bold taste of espresso with the creamy sweetness of caramel. Each sip delivers the perfect balance of strong coffee and velvety milk poured over refreshing ice. The layers of espresso and milk create a rich, inviting flavor that feels both indulgent and satisfying. A drizzle of golden caramel flows throughout the drink, adding a buttery sweetness that complements the coffee’s boldness. Served in a clear cup, its layered tones of coffee, milk, and caramel create a beautiful presentation that’s as appealing as it is delicious. Topped with extra caramel drizzle, it gives the drink a tempting finish. Refreshing yet full-bodied, it’s the perfect iced coffee for busy mornings, afternoon boosts, or late-night cravings. Lightly sweetened, it appeals to both coffee lovers and those who enjoy dessert-like drinks. Its smooth texture and balanced flavor make it a customer favorite. With the Caramel Macchiato Iced Coffee, Nai Tsa turns a café classic into a refreshing iced masterpiece.', '', '68e471e5be9d9_caramel macchiato coffee.png', '2025-10-07 01:50:29', NULL, 1, 4, 2, NULL),
(23, 'Cream Cheese Mocha', 'The Cream Cheese Mocha Iced Coffee from Nai Tsa is a bold and indulgent drink that combines rich espresso, smooth chocolate, and a luscious cream cheese topping. Every sip brings the perfect harmony of coffee’s strong notes with the sweetness of mocha, creating a refreshing yet decadent experience. Served over ice, it delivers a cool and energizing boost that’s perfect for coffee lovers on the go. The highlight of this drink is its velvety cream cheese foam, adding a lightly salty and creamy finish that balances the sweetness of mocha. Each layer—coffee, milk, chocolate, and cream cheese—comes together to create a complex and satisfying flavor profile. Its deep brown tones topped with the pale cream cheese layer make it visually striking in a clear cup. Lightly sweetened, it maintains the boldness of espresso while still feeling dessert-like. Refreshing yet indulgent, it’s ideal for both coffee purists and those who love unique twists. Perfect for mornings, afternoon breaks, or late-night cravings, it’s a versatile treat for any mood. With the Cream Cheese Mocha Iced Coffee, Nai Tsa reimagines iced coffee into a rich, creamy, and refreshing masterpiece.', '', '68e47238aa586_cream cheese mocha coffee.png', '2025-10-07 01:51:52', NULL, 1, 4, 2, NULL),
(24, 'Dalgona Caramel', 'The Dalgona Caramel Iced Coffee from Nai Tsa is a creamy and refreshing twist on the viral coffee trend. Made with hand-whipped dalgona foam, it creates a light and fluffy layer that sits beautifully on top of chilled milk and espresso. Each sip blends the smooth bitterness of coffee with the buttery sweetness of caramel, making it both energizing and indulgent. The caramel drizzle swirls through the drink, giving it a rich golden sweetness that pairs perfectly with the bold coffee flavor. Served over ice, it’s a refreshing pick-me-up that cools and satisfies at the same time. The airy dalgona topping adds a velvety texture, making the experience as enjoyable as the flavor itself. Its layered look of milk, caramel, coffee, and foam makes it visually stunning in a clear cup. Perfect for coffee lovers who want something trendy yet comforting, it’s ideal for any time of the day. Lightly sweet yet bold, it balances dessert-like richness with refreshing iced coffee energy. With the Dalgona Caramel Iced Coffee, Nai Tsa turns a coffee sensation into a creamy, golden masterpiece.', '', '68e49fb1b05f4_dalgona caramel coffee.png', '2025-10-07 05:05:53', NULL, 1, 4, 2, NULL),
(25, 'Frappuccino Java', 'The Frappuccino Java Iced Coffee from Nai Tsa is a bold and refreshing coffee drink crafted for true coffee lovers. Made with freshly brewed espresso, it delivers a smooth and rich flavor that pairs perfectly with creamy milk. The iced coffee is blended with a hint of chocolate to create that signature java taste, making it both energizing and indulgent. Served over ice, it keeps every sip cold and refreshing, perfect for hot days or when you need a pick-me-up. The balance of bittersweet coffee and sweet chocolate notes creates a satisfying depth of flavor. Its smooth texture makes it easy to enjoy, while the coffee kick keeps you energized throughout the day. Topped with a touch of froth or chocolate drizzle, it adds a visual appeal that matches its bold flavor. The Frappuccino Java Iced Coffee is designed to give you the best of both worlds—refreshing iced coffee with a dessert-like twist. It’s the ideal choice for those who love a little sweetness with their strong coffee base. With every sip, Nai Tsa delivers a delightful harmony of java richness and iced coffee refreshment.', '', '68e4a00b2402b_frappuccino java coffee.png', '2025-10-07 05:07:23', NULL, 1, 4, 2, NULL),
(26, 'Salted', 'The Salted Iced Coffee from Nai Tsa is a refreshing twist on your classic iced coffee, bringing together bold flavors with a touch of sophistication. Brewed with rich, full-bodied coffee, it’s poured over ice to deliver a crisp and energizing drink. What makes it unique is the subtle addition of salted cream, creating the perfect balance between sweetness and a hint of savory depth. Each sip starts with the bold taste of coffee, then smoothly transitions into the silky, slightly salty finish. The salted topping enhances the natural richness of the coffee, making it more indulgent yet still refreshing. Served chilled, it’s perfect for hot days when you want a drink that both cools and wakes you up. Its layered look of coffee and cream makes it visually tempting and satisfying to enjoy. The flavor is both complex and comforting, appealing to adventurous drinkers and classic coffee lovers alike. It’s not overly sweet, making it a great option for those who prefer a more balanced iced coffee experience. With the Salted Iced Coffee, Nai Tsa turns a simple drink into a bold and refreshing specialty.', '', '68e4a0bd512c5_salted coffee.png', '2025-10-07 05:10:21', NULL, 1, 4, 2, NULL),
(27, 'Shaken Affogato', 'The Shaken Affogato Iced Coffee from Nai Tsa is a delightful fusion of dessert and coffee, perfect for those who love indulgence with a refreshing twist. It starts with bold espresso shots, poured over creamy vanilla, then shaken with ice to create a smooth, frothy texture. The process blends the richness of coffee with the sweetness of the affogato, giving each sip a decadent balance of flavors. Served icy cold, it’s both energizing and satisfying, making it ideal for hot days or as an afternoon treat. The velvety foam on top adds a luxurious touch, enhancing its dessert-like appeal. Each sip delivers the bold kick of espresso, mellowed by the creamy sweetness of vanilla, creating a harmony of flavors. The shaken method ensures a well-mixed, refreshing drink with a lively texture. Its layered look and inviting aroma make it as pleasing to the eyes as it is to the taste buds. Light yet indulgent, it’s a coffee experience that feels special with every sip. With the Shaken Affogato Iced Coffee, Nai Tsa elevates a classic Italian dessert into a refreshing iced coffee masterpiece.', '', '68e4a120a8bc5_shaken affogato coffee.png', '2025-10-07 05:12:00', NULL, 1, 4, 2, NULL),
(28, 'Spanish Latte', 'The Spanish Latte Iced Coffee from Nai Tsa is a creamy, indulgent drink that perfectly balances sweetness and bold coffee flavor. Made with rich espresso and a blend of fresh milk and condensed milk, it delivers a smooth, velvety texture in every sip. The sweetness is gentle yet distinct, complementing the strong coffee base without overpowering it. Served over ice, it’s a refreshing pick-me-up that cools you down while energizing your day. The layered presentation of milk and coffee creates a visually inviting drink that looks as good as it tastes. Its flavor profile is both comforting and sophisticated, appealing to those who enjoy a sweeter twist on classic iced coffee. Each sip begins with a smooth creaminess and finishes with a satisfying coffee kick. It’s an excellent choice for anyone who loves a drink that’s both dessert-like and refreshing. Perfect for any time of day, it’s equally enjoyable as a morning boost or an afternoon treat. With the Spanish Latte Iced Coffee, Nai Tsa brings you a deliciously smooth, sweet, and energizing coffee experience.', '', '68e4a16677f6a_spanish latte coffee.png', '2025-10-07 05:13:10', NULL, 1, 4, 2, NULL),
(29, 'White Mocha', 'The White Mocha Iced Coffee from Nai Tsa is a smooth and creamy blend that combines bold espresso with the sweet richness of white chocolate. Each sip delivers a perfect balance of strong coffee flavor and velvety sweetness, making it both energizing and indulgent. Served over ice, it’s refreshing and satisfying, perfect for cooling down while getting your coffee fix. The white mocha sauce gives the drink a dessert-like quality, without being overly heavy or overwhelming. Its silky texture makes every sip enjoyable, while the coffee kick keeps you alert and energized. The layered mix of milk, espresso, and white mocha creates a visually pleasing drink in a clear cup. It’s ideal for coffee lovers who enjoy a sweeter, creamier twist on their iced coffee. Each sip starts with a bold espresso note, followed by the smooth sweetness of white chocolate. Light yet decadent, it’s a drink that feels like a treat any time of the day. With the White Mocha Iced Coffee, Nai Tsa turns a classic coffeehouse favorite into a refreshing, indulgent delight.', '', '68e68f24ca6dd_white mocha coffee.png', '2025-10-07 05:18:57', '2025-10-08 16:19:48', 1, 4, 2, NULL),
(30, 'Black Forest', 'The Black Forest Milk Tea from Nai Tsa is a delightful fusion of creamy milk tea and the indulgent flavors of the classic Black Forest dessert. Made with premium brewed tea as its base, it’s blended with smooth milk to create a rich and satisfying texture. The drink is layered with notes of chocolate and cherry, bringing out a unique sweetness that sets it apart from traditional milk teas. Each sip offers a balanced combination of bold tea flavor, velvety creaminess, and a hint of fruity richness. Topped with chocolate drizzle or bits, it adds both texture and visual appeal to the drink. The Black Forest twist makes it taste dessert-like, while still refreshing enough to enjoy anytime. Served over ice, it’s perfect for those who love sweet and indulgent milk tea with a refreshing finish. The chocolate undertone gives it depth, while the cherry notes brighten the flavor profile. It’s a treat that feels both familiar and exciting, appealing to milk tea fans who want something new. With the Black Forest Milk Tea, Nai Tsa turns a classic cake flavor into a refreshing milk tea experience.', '', '68e4a3adb54d8_black forest milktea.png', '2025-10-07 05:22:53', NULL, 1, 5, 2, NULL),
(31, 'Caramel Sugar', 'The Caramel Sugar Milk Tea from Nai Tsa is a sweet and creamy classic that combines the rich flavor of brewed tea with the indulgence of caramelized sugar. Made with freshly steeped tea, it delivers a bold base that’s perfectly balanced by smooth, creamy milk. The addition of caramel sugar syrup creates a deep sweetness with a slightly toasty flavor that makes every sip special. Served over ice, it’s both refreshing and indulgent, perfect for any time of day. The caramel swirls not only add a golden richness to the taste but also make the drink visually appealing. Each sip offers the perfect balance of sweet caramel, creamy milk, and robust tea. It’s a comforting flavor that feels familiar yet exciting, appealing to both milk tea enthusiasts and newcomers. The velvety texture makes it smooth and satisfying, leaving you wanting another sip. Lightly sweet yet rich, it’s the ideal drink for those who love caramel’s warm and indulgent taste. With Caramel Sugar Milk Tea, Nai Tsa delivers a refreshing twist on a beloved milk tea favorite.', 'Milk', '68e4a469354c4_caramel sugar milktea.png', '2025-10-07 05:26:01', NULL, 1, 5, 2, NULL),
(32, 'Cheesecake', 'The Cheesecake Milk Tea from Nai Tsa is a creamy, dessert-inspired drink that blends the rich taste of milk tea with the indulgence of cheesecake flavor. Made with freshly brewed tea, it provides a strong and satisfying base that balances perfectly with smooth milk. A layer of cheesecake cream topping adds velvety richness, giving each sip a decadent twist. The drink combines sweet, tangy, and creamy notes that make it taste like a dessert in a cup. Served over ice, it’s a refreshing treat that still feels indulgent and filling. The cheesecake topping adds a light fluffiness and a savory edge, making the drink unique compared to traditional milk teas. Its layered look of tea, milk, and cream makes it visually appealing in every cup. Each sip is smooth and delightful, starting with bold tea and ending with cheesecake creaminess. It’s perfect for anyone craving both a milk tea and a dessert at the same time. With Cheesecake Milk Tea, Nai Tsa brings a delicious and indulgent twist to the milk tea experience.', '', '68e4a4acf26e7_cheesecake milktea.png', '2025-10-07 05:27:08', NULL, 1, 5, 2, NULL),
(33, 'Cookies and Cream', 'The Cookies and Cream Milk Tea from Nai Tsa is a deliciously sweet and creamy blend that turns a classic favorite into a refreshing drink. It starts with a base of freshly brewed tea, mixed with smooth milk for a rich and velvety texture. Crushed cookies are blended into the drink, adding a chocolatey crunch and extra sweetness. Each sip offers the perfect balance of bold tea flavor, creamy milk, and the indulgent taste of cookies and cream. Served over ice, it’s both refreshing and dessert-like, making it an ideal choice for any time of day. The bits of cookies give the drink a fun texture, making it even more enjoyable. Its flavor is familiar yet exciting, appealing to both milk tea lovers and cookie fans alike. The creamy finish makes it smooth and satisfying, while the cookie pieces add playful indulgence. It’s a crowd-pleaser that feels both comforting and special. With Cookies and Cream Milk Tea, Nai Tsa transforms a beloved treat into a refreshing milk tea experience.', '', '68e4a56309933_cookies n cream milktea.png', '2025-10-07 05:30:11', NULL, 1, 5, 2, NULL),
(34, 'Dalgona', 'The Dalgona Milk Tea from Nai Tsa is a creamy and fluffy twist on the milk tea experience, inspired by the popular dalgona trend. It begins with a base of freshly brewed tea, blended with smooth milk to create a rich and satisfying flavor. On top sits a layer of hand-whipped dalgona foam, giving the drink a light and airy texture. Each sip combines the bold, refreshing taste of milk tea with the sweet and slightly bitter flavor of whipped coffee. Served over ice, it’s both energizing and refreshing, making it perfect for warm days or as a special treat. The contrast of smooth milk tea below and fluffy dalgona cream above makes it visually stunning in a clear cup. The balance of flavors is unique, offering sweetness, creaminess, and a touch of coffee-like bitterness all in one drink. Its velvety foam topping adds a luxurious touch that makes every sip indulgent. It’s ideal for those who enjoy playful, trendy drinks with a rich flavor profile. With Dalgona Milk Tea, Nai Tsa transforms a viral favorite into a refreshing and creamy milk tea delight.', '', '68e4a5b7dd63f_dalgona milktea.png', '2025-10-07 05:31:35', NULL, 1, 5, 2, NULL),
(35, 'Dark Choco', 'The Dark Choco Milk Tea from Nai Tsa is a bold and indulgent drink that combines the richness of premium brewed tea with the deep flavor of dark chocolate. Each sip balances the slight bitterness of dark chocolate with the creamy sweetness of milk, creating a satisfying harmony. The tea base adds a refreshing note that keeps the drink from being overly heavy, making it enjoyable anytime. Served over ice, it’s both refreshing and indulgent, perfect for chocolate lovers seeking a twist on classic milk tea. The dark chocolate flavor gives the drink depth and sophistication, appealing to those who prefer a less sweet, more intense taste. Its smooth texture and velvety finish make every sip delightful. The drink is visually tempting with its chocolate swirls blending beautifully with milk tea. It’s both comforting and energizing, making it a versatile choice for any occasion. The boldness of dark chocolate pairs perfectly with the creaminess of milk tea, creating a unique experience. With Dark Choco Milk Tea, Nai Tsa delivers a rich and refreshing treat that stands out on the menu.', '', '68e4a6001cd07_dark choco milktea.png', '2025-10-07 05:32:48', NULL, 1, 5, 2, NULL),
(36, 'Hazelnut', 'The Hazelnut Milk Tea from Nai Tsa is a smooth and nutty twist on the classic milk tea experience. It begins with freshly brewed tea, blended with creamy milk to create a rich and velvety base. A swirl of hazelnut flavor adds a warm, roasted nuttiness that perfectly complements the bold tea taste. Each sip delivers a balance of sweetness and earthy depth, making it both refreshing and indulgent. Served over ice, it’s the perfect drink for anyone who loves a comforting yet energizing treat. The hazelnut flavor brings a unique aroma that makes every cup feel extra special. Its creamy texture and nutty undertones create a drink that’s both familiar and exciting. The layered look of milk tea with hazelnut syrup adds visual appeal, making it as inviting to the eyes as it is to the taste buds. It’s an excellent choice for those who enjoy a nutty sweetness paired with tea’s natural richness. With Hazelnut Milk Tea, Nai Tsa offers a deliciously smooth and aromatic drink that stands out on the menu.', '', '68e4a64f5845b_hazelnut milktea.png', '2025-10-07 05:34:07', NULL, 1, 5, 2, NULL),
(37, 'Hokkaido', 'The Hokkaido Milk Tea from Nai Tsa is a rich and creamy classic inspired by the famous flavors of Japan’s Hokkaido region. It starts with freshly brewed tea, blended with premium milk for a smooth and velvety texture. The signature caramelized sweetness of Hokkaido milk adds depth and a gentle roasted flavor that makes the drink unique. Each sip is both sweet and mellow, balancing the boldness of tea with the comforting richness of milk. Served over ice, it’s a refreshing yet indulgent drink perfect for any time of the day. Its caramel undertones give it a dessert-like quality without being overly heavy. The creamy finish lingers on the palate, making every sip satisfying and memorable. Visually, it’s inviting with its golden caramel swirls blending beautifully with milk tea. It’s a great choice for milk tea lovers who enjoy a classic flavor with a rich twist. With Hokkaido Milk Tea, Nai Tsa brings a smooth, sweet, and comforting taste that captures the essence of indulgence.', '', '68e4a68d18bfb_hokkaido milktea.png', '2025-10-07 05:35:09', NULL, 1, 5, 2, NULL),
(38, 'Mango Cheesecake', 'The Mango Cheesecake Milk Tea from Nai Tsa is a fruity and creamy delight that combines the refreshing taste of mango with the indulgence of cheesecake. It begins with freshly brewed tea blended with smooth milk, creating a rich and velvety base. Sweet mango flavor adds a tropical twist, bringing a bright and refreshing taste to every sip. On top, a layer of cheesecake cream creates a tangy, creamy finish that balances perfectly with the sweetness of mango. Served over ice, it’s a refreshing yet indulgent drink perfect for hot days or as a dessert-like treat. The combination of mango and cheesecake offers a unique flavor harmony—fruity, creamy, and slightly tangy. Its layered look of golden mango and creamy cheesecake topping makes it visually stunning. Each sip delivers a playful mix of bold tea, juicy mango, and velvety cheesecake cream. It’s perfect for those who love fruity flavors with a creamy twist. With Mango Cheesecake Milk Tea, Nai Tsa transforms a tropical favorite into a rich and refreshing milk tea masterpiece.', '', '68e4a73e061e1_mango cheesecake milktea.png', '2025-10-07 05:38:06', NULL, 1, 5, 2, NULL),
(39, 'Matcha', 'The Matcha Milk Tea from Nai Tsa is a refreshing and earthy drink that highlights the natural goodness of premium green tea. Made with finely ground matcha, it delivers a rich and distinct flavor that is both calming and energizing. Blended with creamy milk, it creates a smooth and velvety texture that balances the slight bitterness of matcha. Each sip offers a harmony of earthy notes and gentle sweetness, making it both unique and satisfying. Served over ice, it’s a cool and invigorating treat perfect for tea lovers. The vibrant green color of matcha makes it visually striking, adding to its appeal. Its flavor is both sophisticated and comforting, offering a healthier-tasting option among milk teas. The creamy finish enhances the bold matcha taste, making every sip refreshing yet indulgent. It’s an excellent choice for those who enjoy a drink that’s both energizing and soothing. With Matcha Milk Tea, Nai Tsa brings a classic Japanese-inspired favorite into a refreshing milk tea experience.', '', '68e4a77a6aa4a_matcha milktea.png', '2025-10-07 05:39:06', NULL, 1, 5, 2, NULL),
(40, 'Okinawa', 'The Okinawa Milk Tea from Nai Tsa is a smooth and caramelized classic inspired by the unique flavors of Okinawa, Japan. Made with freshly brewed tea and creamy milk, it’s elevated with the rich sweetness of Okinawa brown sugar. Each sip delivers a deep, toasty flavor that perfectly balances the bold tea base. The brown sugar creates a natural sweetness that feels comforting without being overly heavy. Served over ice, it’s both refreshing and indulgent, making it perfect for any time of the day. The golden brown sugar syrup swirls beautifully through the milk tea, giving it an eye-catching presentation. Its flavor is warm and satisfying, offering a dessert-like quality with every sip. The creamy texture makes it smooth, while the earthy sweetness of brown sugar lingers on the palate. It’s an excellent choice for those who enjoy a richer, sweeter milk tea with a unique character. With Okinawa Milk Tea, Nai Tsa brings a comforting and flavorful Japanese-inspired favorite to your cup.', '', '68e4a7b0521f9_okinawa milktea.png', '2025-10-07 05:40:00', NULL, 1, 5, 2, NULL),
(41, 'Red Velvet', 'The Red Velvet Milk Tea is a perfect fusion of elegance and indulgence in one refreshing cup. It combines the rich, cocoa-infused flavor of classic red velvet with the smooth creaminess of fresh milk. Each sip offers a comforting balance of sweetness and bold tea notes that dance on your taste buds. The drink is topped with ice to keep it cool and refreshing, perfect for any time of the day. At the bottom, chewy jellies add a fun and satisfying texture to every sip. Its deep red-brown color and irresistible aroma make it a true treat for both the eyes and the senses. Whether you’re craving dessert or a midday energy boost, this drink is the perfect choice. Every cup is carefully crafted to deliver the signature Nai Tsa taste that customers love. It’s a drink that brings comfort, joy, and a little luxury with every sip. Enjoy the Red Velvet Milk Tea—a deliciously smooth experience only from Nai Tsa Bubble Tea | Food Hub.', '', '68e4aa9d8c404_red velvet milktea.png', '2025-10-07 05:52:29', '2025-10-08 11:04:56', 1, 5, 2, 2),
(42, 'Taro', 'The Taro Milk Tea is a creamy and delightful classic that never goes out of style. Made from real taro blended with smooth milk and premium tea, it delivers a rich, nutty, and slightly sweet flavor that’s both comforting and satisfying. Each sip is velvety and refreshing, offering the perfect balance between tea aroma and taro goodness. Its beautiful pastel purple color makes it not only delicious but also visually irresistible. Served over ice, it’s the ideal drink to cool down on a warm day or to simply brighten your mood. At the bottom, chewy sinkers add a fun texture that makes every sip even more enjoyable. Whether you’re new to milk tea or a long-time fan, this drink captures the heart of what makes milk tea special. Every cup is carefully prepared to bring out the signature Nai Tsa quality and flavor. It’s a drink that feels like comfort in a cup—smooth, creamy, and full of flavor. Experience the Taro Milk Tea, a timeless favorite only from Nai Tsa Bubble Tea | Food Hub.', '', '68e4aaecccbc7_taro milktea.png', '2025-10-07 05:53:48', NULL, 1, 5, 2, NULL),
(43, 'Wintermelon', 'The Wintermelon Milk Tea from Nai Tsa is a refreshing and naturally sweet favorite that captures the soothing essence of wintermelon. Made with freshly brewed tea and creamy milk, it’s infused with the mellow, honey-like flavor of wintermelon that brings a light and comforting sweetness to every sip. The smooth blend of milk and tea highlights the delicate taste of wintermelon, creating a drink that’s both refreshing and satisfying. Its gentle sweetness is perfectly balanced—not too rich, yet full of character and depth. Served over ice, it’s a cool and revitalizing treat that’s perfect for any time of day. The golden hue of the wintermelon syrup swirls beautifully through the milk tea, making it as inviting to look at as it is to drink. Each sip offers a smooth, crisp flavor that leaves a refreshing aftertaste on the palate. The creamy texture complements the subtle sweetness, making every cup soothing and enjoyable. It’s the kind of drink that feels like a calm, sweet escape in the middle of a busy day. With Wintermelon Milk Tea, Nai Tsa brings you a classic blend that’s light, smooth, and effortlessly satisfying.', '', '68e4ab7d98ff1_wintermelon milktea.png', '2025-10-07 05:56:13', NULL, 1, 5, 2, NULL);
INSERT INTO `product` (`Product_ID`, `Product_Name`, `Product_desc`, `Product_allergens`, `Product_Image`, `Created_at`, `Updated_at`, `Admin_ID`, `Category_ID`, `Price_ID`, `Primary_Size_ID`) VALUES
(44, 'Zago Nata', 'The Zago Nata Milk Tea from Nai Tsa is a delightful blend of creamy tea and chewy goodness that creates a truly satisfying drink experience. Crafted with freshly brewed tea and rich, smooth milk, it delivers a comforting flavor that’s both balanced and indulgent. The highlight of this drink is the nata de coco—soft, chewy coconut jellies that add a fun texture to every sip. Each mouthful combines the creamy sweetness of milk tea with the refreshing bite of nata, creating a perfect harmony of flavors and textures. Served over ice, it’s a refreshing choice for milk tea lovers who enjoy something both cool and chewy. Its inviting color and glossy nata pieces at the bottom make it as visually appealing as it is delicious. The subtle tea aroma blends beautifully with the milk’s creaminess, making every sip feel smooth and luxurious. Light yet flavorful, it’s a drink that you’ll want to enjoy again and again. Whether as an afternoon pick-me-up or a sweet treat, it’s perfect for any moment of the day. With Zago Nata Milk Tea, Nai Tsa brings you a creamy, chewy, and irresistibly refreshing classic that never disappoints.', '', '68e605a52d901_zago nata milktea.png', '2025-10-07 05:58:03', '2025-10-08 10:57:04', 1, 5, 10, 2),
(45, 'Fries', 'The Fries from Nai Tsa are a golden, crispy favorite that perfectly complements any drink or meal. Each piece is fried to perfection—crispy on the outside and soft and fluffy on the inside. Lightly seasoned with just the right amount of salt, they deliver a simple yet irresistible flavor that keeps you coming back for more. Served hot and fresh, these fries make the perfect snack for sharing or enjoying on your own. Their golden color and crunchy texture make them a visual and flavorful delight. Every bite offers that satisfying crunch followed by a warm, savory softness that melts in your mouth. Pair them with your favorite Nai Tsa milk tea for the ultimate comfort combo. Whether you dip them in ketchup, cheese, or eat them plain, they’re delicious every single time. Ideal for any craving, they’re a go-to snack that’s both comforting and addicting. With Nai Tsa Fries, simple moments become flavorful experiences worth savoring.', '', '68e4ad48563f6_Fries.png', '2025-10-07 06:03:52', NULL, 1, 6, 2, NULL),
(46, 'Nachos Overload', 'The Nachos Overload from Nai Tsa is a bold and flavorful snack that’s perfect for sharing—or enjoying all to yourself. Each crunchy nacho chip is generously topped with a mouthwatering mix of melted cheese, savory ground beef, and creamy dressing. The combination of textures creates an explosion of flavor in every bite—crispy, cheesy, and satisfyingly rich. Freshly chopped onions, tomatoes, and peppers add a burst of freshness that perfectly balances the creamy and savory layers. Every serving is built to be indulgent, ensuring you get the perfect mix of toppings with every scoop. The gooey cheese drips over the chips beautifully, making it a feast for both the eyes and the appetite. Served hot and fresh, it’s the ultimate comfort snack for hangouts, study breaks, or movie nights. The flavors blend perfectly, giving that irresistible mix of salty, tangy, and creamy goodness. Whether paired with your favorite Nai Tsa milk tea or enjoyed on its own, it’s guaranteed to satisfy your cravings. With Nachos Overload, Nai Tsa takes snacking to the next level—loaded, flavorful, and absolutely unforgettable.', '', '68e4adcb33839_Nachos overload.png', '2025-10-07 06:06:03', '2025-10-08 12:17:34', 1, 6, 2, 6),
(47, 'Sandwiches', 'The Sandwiches from Nai Tsa are a deliciously satisfying choice for anyone craving a hearty yet comforting meal. Each sandwich is made with soft, freshly toasted bread that perfectly complements its flavorful fillings. Packed with layers of juicy meat, crisp vegetables, and creamy dressing, every bite is a balance of texture and taste. The combination of savory and fresh ingredients makes it both filling and refreshing. Served warm and golden, it’s the kind of snack that feels homemade yet crafted with café-quality care. The bread’s slight crunch contrasts beautifully with the softness of the fillings, creating a perfect bite every time. Whether you’re in the mood for a quick snack or a light meal, it’s an easy favorite that never disappoints. Pair it with your favorite Nai Tsa milk tea for a satisfying combo that hits the spot. Each sandwich is made fresh to order, ensuring quality and flavor in every serving. With Nai Tsa Sandwiches, you get a comforting, flavorful, and perfectly crafted bite that makes every meal moment more enjoyable.', '', '68e4adffdf49e_Sandwiches.png', '2025-10-07 06:06:55', NULL, 1, 6, 2, NULL),
(48, 'Siomai', 'The Siomai from Nai Tsa is a savory and satisfying bite that brings comfort and flavor in every piece. Each siomai is carefully steamed to perfection, locking in the juicy goodness of its seasoned meat filling. The blend of tender pork, spices, and aromatics creates a rich and mouthwatering taste that’s hard to resist. Its soft and delicate wrapper perfectly complements the meaty texture inside, making every bite both flavorful and smooth. Served hot and fresh, it’s best enjoyed with a drizzle of soy sauce, calamansi, and chili for that extra kick. The aroma alone is enough to make your mouth water, promising a delicious experience from the first bite to the last. Whether as a quick snack or a light meal, it’s a comfort food favorite that never goes out of style. Each serving is crafted with care, ensuring the authentic taste of classic siomai in every piece. Pair it with your favorite Nai Tsa milk tea for a satisfying combo of savory and sweet. With Nai Tsa Siomai, every bite delivers warmth, flavor, and the simple joy of good food done right.', '', '68e690c21fa53_Siomai.png', '2025-10-07 06:07:55', '2025-10-08 16:26:42', 1, 6, 21, 121),
(49, 'Swapping', 'The Swapping from Nai Tsa is a unique and flavorful treat that perfectly combines crispiness, softness, and a touch of sweetness. Each piece is freshly cooked to achieve a golden-brown crust with a warm, tender center. It’s drizzled with a rich, savory sauce that enhances its delicious flavor and gives it that irresistible glossy finish. The texture is delightfully satisfying—crispy on the outside, yet soft and chewy inside. Every bite bursts with flavor, making it a snack that’s both filling and comforting. Served warm, it’s best enjoyed fresh off the plate for that perfect crunch and aroma. Its beautiful presentation, sliced into bite-sized pieces, makes it ideal for sharing with friends or enjoying solo. The balance of savory and slightly sweet flavors creates a taste that’s uniquely Nai Tsa. Whether as a light snack, a quick meal, or a treat to pair with your favorite milk tea, it never fails to satisfy. With Swapping, Nai Tsa brings you a delicious twist on comfort food—crispy, flavorful, and made to delight every bite.', '', '68e4ae8ec99d9_Swapping.png', '2025-10-07 06:09:18', NULL, 1, 6, 2, NULL),
(50, 'Bundle 1', '(SAVE 20)\r\n2 Milktea (16oz)\r\n1 Nachos Overload', '', '68e4b8d03d185_Bundle 1 .png', '2025-10-07 06:49:14', '2025-10-07 06:53:04', 1, 3, 4, NULL),
(51, 'Bundle 2', '(SAVE 35)\r\n2 Milktea (16oz)\r\n1 Fries\r\n1 Nachos Overload', '', '68e4b92171248_Bundle 2.png', '2025-10-07 06:54:25', NULL, 1, 3, 5, NULL),
(52, 'Bundle 3', '(SAVE 35)\r\n3 Milktea (16oz)\r\n1 Fries\r\n1 Swapping\r\n5 Siomai', '', '68e4b9bfc91db_Bundle 3.png', '2025-10-07 06:57:03', NULL, 1, 3, 6, NULL),
(53, 'Bundle 4', '(SAVE 30)\r\n3 Milktea (16oz)\r\n1 Fries\r\n1 Nachos Overload', '', '68e4ba5765688_Bundle 4.png', '2025-10-07 06:59:35', NULL, 1, 3, 7, NULL),
(54, 'Bundle 5', '(SAVE 25)\r\n5 Milktea (16oz)\r\n1 Fries\r\n1 Nachos Overload', '', '68e4baa994e67_Bundle 5.png', '2025-10-07 07:00:57', NULL, 1, 3, 8, NULL),
(55, 'Bundle 6', '(SAVE 35)\r\n5 Milktea (16oz)\r\n1 Fries\r\n1 Swapping\r\n5 Siomai\r\n1 Nachos Overload', '', '68e4baf1ce40d_Bundle 6.png', '2025-10-07 07:02:09', NULL, 1, 3, 9, NULL),
(57, 'Mango', 'At Naitsa, our Mango Ice Cream is a tropical delight that captures the sweet taste of summer in every scoop. Made from fresh, ripe mangoes, it offers a naturally rich and creamy flavor that instantly refreshes your mood. Each bite melts smoothly on your tongue, giving a perfect balance of sweetness and creaminess. We craft it with care to make sure every serving feels like a special treat. Whether you’re cooling down after a busy day or craving something light and fruity, our Mango Ice Cream is the perfect choice. It’s not just dessert—it’s an experience that reminds you of sunny days and happy moments. The bright color and aroma of real mangoes make it both beautiful and delicious. Pair it with your favorite Naitsa milk tea or coffee for a perfect combination of flavors. It’s a crowd favorite among our customers who love fruity and refreshing desserts. At Naitsa, we believe that every scoop of Mango Ice Cream should make you smile and feel a little closer to paradise.', '', '68e66c728b768_Mango Ice Cream.png', '2025-10-08 13:51:46', NULL, 1, 8, 1, NULL),
(58, 'Chocoberry', 'At Naitsa, our Chocoberry Ice Cream is a sweet fusion of rich chocolate and fresh strawberries that will make your taste buds fall in love. Each scoop combines the smooth creaminess of chocolate with the fruity sweetness of real berries, creating a perfectly balanced flavor. We use quality ingredients to ensure every bite is velvety, refreshing, and full of indulgence. The chocolate gives a deep, satisfying taste while the strawberries add a light, tangy twist. It’s the perfect dessert for those who can’t choose between chocolate or fruit — because now you can have both! Our Chocoberry Ice Cream is carefully prepared to give a melt-in-your-mouth experience that feels comforting and exciting at the same time. Its pink and brown swirls make it as beautiful as it is delicious. Whether you’re sharing it with friends or enjoying it solo, it’s guaranteed to make your day sweeter. Pair it with your favorite Naitsa drink for the ultimate treat. At Naitsa, every scoop of Chocoberry Ice Cream is made to bring joy, flavor, and a little bit of love in every bite.', '', '68e66cec332ae_chocoberry Ice Cream.png', '2025-10-08 13:53:48', NULL, 1, 8, 1, NULL),
(59, 'Blue Lemonade Soda', 'The Blue Lemonade Soda from Nai Tsa is a vibrant and refreshing drink that’s as eye-catching as it is delicious. Bursting with the zesty flavor of lemonade, it’s perfectly blended with a fizzy soda base for a crisp and energizing experience. The blue hue gives it a fun and tropical vibe, making it stand out on the menu and in your cup. Each sip is tangy, sweet, and bubbly, delivering the ultimate thirst quencher. Served ice-cold, it’s perfect for hot days when you want something refreshing yet playful. The combination of citrus and fizz creates a lively drink that instantly lifts your mood. Its bold flavor balances sweetness and sourness, making it enjoyable for all ages. The sparkling texture makes every sip light and exciting. It’s more than just a drink—it’s a colorful treat that brings a splash of fun to your day. With Blue Lemonade Soda, Nai Tsa turns a classic lemonade into a sparkling, vibrant refreshment.', '', '68e6787ede03e_Blue Lemonade Soda.png', '2025-10-08 14:42:17', '2025-10-08 14:44:11', 1, 7, 20, 2),
(62, 'Blueberry Soda', 'The Blueberry Soda from Nai Tsa is a sparkling refreshment bursting with fruity sweetness and fizzy excitement. Made with the juicy flavor of ripe blueberries, it’s blended with soda to create a crisp and bubbly drink. Each sip offers a balance of tangy berry notes and refreshing effervescence, making it both sweet and thirst-quenching. Its deep purple-blue hue makes it visually striking, adding a fun and stylish touch to your drink. Served ice-cold, it’s perfect for hot days or whenever you need a flavorful pick-me-up. The natural berry flavor pairs perfectly with the soda’s fizz, creating a lively and playful texture. It’s a drink that feels light yet indulgent, appealing to both fruit lovers and soda fans alike. The sweetness of blueberries is enhanced by the refreshing sparkle, leaving a delicious aftertaste. Every sip feels energizing, refreshing, and fun. With Blueberry Soda, Nai Tsa brings a fruity twist to classic sparkling refreshment.', '', '68e67ad26e0f5_Blueberry Soda.png', '2025-10-08 14:53:06', '2025-10-08 14:53:51', 1, 7, 20, 2),
(63, 'Green Apple Soda', 'The Green Apple Soda from Nai Tsa is a crisp and refreshing drink that delivers the perfect balance of sweet and tangy flavors. Infused with the bright, juicy taste of green apple, it’s paired with fizzy soda to create a lively and energizing refreshment. Each sip bursts with a tart apple kick that’s instantly thirst-quenching and fun. Its vibrant green color makes it stand out, adding a playful and eye-catching touch to your cup. Served over ice, it’s an ideal choice for hot days or when you want a light and fruity treat. The effervescence of the soda enhances the bold apple flavor, making every sip sparkling and exciting. It’s both sweet and tangy, offering a refreshing twist that appeals to all ages. The crisp flavor profile makes it a drink that feels clean, fresh, and energizing. Its bubbly texture keeps the experience lively and fun from start to finish. With Green Apple Soda, Nai Tsa turns a simple fruity flavor into a sparkling sensation.', '', '68e67b7c1be13_Green Apple Soda.png', '2025-10-08 14:55:56', NULL, 1, 7, 20, NULL),
(64, 'Kiwi Soda', 'The Kiwi Soda from Nai Tsa is a sparkling refreshment bursting with tropical sweetness and zesty tang. Made with the unique flavor of ripe kiwi, it offers a balance of fruity sweetness and light tartness that excites the taste buds. The effervescent soda base adds a bubbly kick, making every sip crisp and energizing. Its bright green color creates a refreshing and fun visual appeal. Served cold over ice, it’s the perfect drink to beat the heat or enjoy as a lively pick-me-up. The kiwi’s natural tropical notes blend seamlessly with the fizz, creating a smooth yet vibrant taste. It’s light, fruity, and refreshing, making it enjoyable for all ages. The playful mix of tart and sweet ensures the flavor never feels too heavy. Each sip leaves a clean, sparkling finish that keeps you wanting more. With Kiwi Soda, Nai Tsa turns a tropical fruit favorite into a fizzy, fun, and thirst-quenching treat.', '', '68e67c2be5acd_Kiwi Soda.png', '2025-10-08 14:58:13', '2025-10-08 14:58:51', 1, 7, 20, NULL),
(65, 'Lemon Tea Soda', 'The Lemon Tea Soda from Nai Tsa is a refreshing fusion of zesty citrus and sparkling fizz. Combining the natural sharpness of lemon with the smooth, soothing notes of tea, it creates a perfectly balanced drink. The soda adds an effervescent kick, making each sip light, crisp, and invigorating. Its golden hue and lively bubbles give it an appealing and refreshing look. Served over ice, it’s the ultimate thirst-quencher on a hot day or as a lively complement to any meal. The tangy lemon brightens the palate while the tea adds depth and smoothness. This unique combination makes it both energizing and soothing at the same time. Each sip delivers a playful balance of tart, sweet, and fizzy flavors. The clean finish leaves you feeling refreshed and satisfied. With Lemon Tea Soda, Nai Tsa transforms a classic tea into a bubbly, citrus-infused delight.', '', '68e67cff6d2d1_Lemon Tea Soda.png', '2025-10-08 15:02:23', NULL, 1, 7, 20, NULL),
(66, 'Lychee Soda', 'The Lychee Soda from Nai Tsa is a tropical refreshment that blends the exotic sweetness of lychee with sparkling soda. Its floral aroma and light, fruity flavor make it both unique and irresistible. Each sip delivers a delicate balance of natural sweetness and fizzy crispness. The refreshing bubbles highlight the juicy taste of lychee, making it a fun and energizing drink. Its crystal-clear look with a slight fruity tint adds to its elegant appeal. Served cold over ice, it instantly cools you down and uplifts your mood. Perfect for those who enjoy sweet yet refreshing flavors, it offers a smooth and satisfying finish. The drink’s effervescent quality makes it great for celebrations or simply as a daily treat. Its tropical charm makes every sip feel like a mini escape. With Lychee Soda, Nai Tsa brings you a sparkling taste of paradise in a cup.', '', '68e67d953d3ec_Lychee Soda.png', '2025-10-08 15:04:53', NULL, 1, 7, 20, NULL),
(67, 'Passion Fruit Soda', 'The Passion Fruit Soda from Nai Tsa is a vibrant drink bursting with tropical flavor and sparkling refreshment. It combines the tangy-sweet taste of passion fruit with the lively fizz of soda, creating a bold and energizing beverage. Each sip delivers a refreshing balance of tartness and sweetness that excites the palate. Its golden-yellow hue and bubbly texture make it as visually appealing as it is delicious. The crisp soda elevates the tropical notes, leaving a light and invigorating finish. Served over ice, it’s the perfect thirst-quencher on warm days or whenever you need a refreshing boost. The natural fruitiness of passion fruit adds a playful, exotic twist that sets it apart. Its bright and fizzy character makes it both fun and satisfying to drink. Whether enjoyed on its own or paired with food, it always leaves you refreshed. With Passion Fruit Soda, Nai Tsa captures the bold essence of the tropics in every sparkling sip.', '', '68e67e032dcfc_Passion Fruit Soda.png', '2025-10-08 15:06:43', NULL, 1, 7, 20, NULL),
(68, 'Stawberry Soda', 'The Strawberry Soda from Nai Tsa is a sweet, fizzy delight that captures the essence of ripe, juicy strawberries. Its refreshing blend of fruity sweetness and sparkling soda makes it a fun and uplifting drink. Each sip delivers a burst of strawberry flavor balanced by the light, bubbly fizz. The drink’s vibrant pink color adds a cheerful and inviting touch. Served over ice, it’s the perfect way to cool down and enjoy a refreshing moment. The natural strawberry taste makes it both familiar and exciting to the palate. Its bubbly character gives a playful twist, making it great for any occasion. Whether paired with snacks or enjoyed on its own, it never fails to satisfy. The balance of sweet and fizzy notes creates a smooth, refreshing finish. With Strawberry Soda, Nai Tsa turns a classic fruit into a sparkling, irresistible refreshment.', '', '68e67ea40e677_Strawberry Soda.png', '2025-10-08 15:09:24', NULL, 1, 7, 20, NULL),
(69, 'Chessy Hotdog Sandwich ', 'At Naitsa, our Cheesy Hotdog Sandwich is a savory favorite that’s perfect for any time of the day. Each sandwich features a juicy, flavorful hotdog wrapped in a warm, freshly toasted bun. We top it with rich, melted cheese that adds a creamy and irresistible taste to every bite. The combination of smoky hotdog and gooey cheese creates a satisfying snack that’s both comforting and delicious. It’s made to be simple yet packed with flavor that everyone will enjoy. Whether you’re grabbing a quick bite with your coffee or pairing it with a refreshing milk tea, this sandwich always hits the spot. We prepare it fresh to ensure that every serving is warm, cheesy, and perfectly balanced. Its aroma and texture make it one of our best-loved snacks. It’s a classic comfort food with a Naitsa twist — made with care and quality ingredients. At Naitsa, our Cheesy Hotdog Sandwich isn’t just a snack, it’s a warm and tasty companion to your favorite drinks.', '', '68e6810a50650_Chessy Hotdog Sandwich.png', '2025-10-08 15:19:38', NULL, 1, 6, 15, NULL),
(70, 'Matcha Latte', 'At Naitsa, our Matcha Latte is a creamy blend of authentic matcha and fresh milk that delivers a soothing taste in every sip. The rich green tea flavor provides a calm and refreshing feeling, perfect for relaxing moments. We use premium matcha powder to ensure a smooth, earthy aroma that pairs beautifully with our velvety milk base. Each cup is carefully prepared to bring out the natural bitterness and sweetness of matcha. It’s the ideal drink for those who love a balanced flavor that’s not too strong or too light. The vibrant green color adds to its charm, making it both visually pleasing and delicious. Whether served iced or hot, it’s guaranteed to lift your mood and refresh your senses. It’s best paired with our pastries or light snacks for a complete Naitsa experience. Every sip is crafted with care to give you comfort and energy at the same time. At Naitsa, our Matcha Latte is not just a drink — it’s a calming moment in a cup.', '', '68e682a84a4ce_Matcha latte milkbase.png', '2025-10-08 15:25:25', '2025-10-08 15:26:32', 1, 9, 14, NULL),
(71, 'Strawberry Milk', 'At Naitsa, our Strawberry Milk is a sweet and creamy treat made with real strawberry puree and fresh milk. Each sip bursts with the natural flavor of strawberries, giving a refreshing and delightful taste. The pink hue makes it visually appealing and perfect for your aesthetic cravings. It’s a drink that brings both comfort and happiness, especially for strawberry lovers. We make sure every cup is perfectly blended for that smooth, fruity consistency. It’s great for cooling down on warm days or as a sweet treat any time you want to indulge. Pair it with your favorite Naitsa dessert or enjoy it on its own — it’s delicious either way. The mix of fresh milk and real fruit gives it a unique homemade taste. Every sip is rich, creamy, and naturally satisfying. At Naitsa, our Strawberry Milk is more than a drink — it’s a taste of pure joy in every glass.', '', '68e68294a3b89_Strawberry milk milkbase.png', '2025-10-08 15:26:12', NULL, 1, 9, 14, NULL),
(72, 'Blueberry Matcha Latte', 'Our Blueberry Matcha Latte at Naitsa is a unique twist on the classic matcha drink, blending earthy matcha with the sweet-tart flavor of blueberries. Each cup bursts with a refreshing balance of fruit and tea that keeps you coming back for more. The smooth milk base ties both flavors together perfectly for a creamy finish. It’s a drink that surprises you with every sip — first the matcha, then the berry goodness. We use real blueberry syrup and premium matcha for authentic taste and color. It’s the perfect choice for those who want something exciting yet soothing. The beautiful mix of green and purple hues makes it a true showstopper. Enjoy it chilled for a refreshing boost or warm for a cozy experience. It’s light, flavorful, and made to delight your senses. At Naitsa, our Blueberry Matcha Latte is the perfect harmony of freshness and indulgence.', '', '68e683417b010_Blueberry matcha latte milkbase.png', '2025-10-08 15:27:05', '2025-10-08 15:29:05', 1, 9, 19, NULL),
(73, 'Matcha Berry', 'At Naitsa, our Matcha Berry drink is a delicious fusion of rich matcha and sweet berries. The earthy tone of matcha blends beautifully with the fruity flavor, creating a balanced and refreshing experience. Each sip delivers a creamy, smooth texture that feels comforting and energizing. We carefully prepare it to ensure both flavors complement rather than overpower each other. It’s a drink that satisfies both tea lovers and fruit drink fans alike. The bright green and pink swirls make it as stunning as it is delicious. It’s perfect for those who crave something new, unique, and refreshing. Whether you’re relaxing at Naitsa or taking it on the go, it’s sure to lift your mood. Pair it with one of our light snacks for the ultimate treat. At Naitsa, our Matcha Berry represents the perfect blend of freshness, color, and creativity in every cup.', '', '68e6833337236_Matcha berry milkbase.png', '2025-10-08 15:27:39', '2025-10-08 15:28:51', 1, 9, 19, NULL),
(74, 'Chuckie Berry', 'Our Chuckie Berry at Naitsa is a fun, flavorful blend of rich chocolate and sweet berry goodness. It combines the comforting taste of chocolate milk with the fruity sweetness of berries for a one-of-a-kind treat. Each sip brings back childhood memories with a modern twist. The creamy milk base enhances both flavors, making it smooth and perfectly balanced. We use high-quality cocoa and real berry syrup for a naturally rich taste. It’s a drink that’s playful, comforting, and full of personality. The mix of brown and pink hues makes it visually charming too. Whether you’re a chocolate lover or just craving something unique, Chuckie Berry won’t disappoint. It’s great on its own or paired with your favorite Naitsa snack. At Naitsa, our Chuckie Berry is all about fun, flavor, and nostalgia in one refreshing cup.', '', '68e6831592a2c_Chuckie berry milkbase.png', '2025-10-08 15:28:21', NULL, 1, 9, 19, NULL),
(75, 'Taro Berry', 'At Naitsa, our Taro Berry is a beautiful combination of creamy taro and fruity berry flavor. The smooth texture of taro blends perfectly with the sweetness of berries, creating a drink that’s both colorful and delicious. Each sip offers a balance of earthy and fruity notes that’s soothing yet exciting. We use high-quality taro and berry ingredients to achieve its naturally vibrant purple color. The milk base adds a layer of creaminess that makes it rich and satisfying. It’s a drink that stands out not just for its taste but also for its dreamy look. Whether you’re a taro lover or looking for something new, this blend will surely impress. It’s best enjoyed cold for a refreshing burst of flavor. Every cup is crafted with care to ensure smoothness and quality in every sip. At Naitsa, our Taro Berry is a true masterpiece — sweet, creamy, and made to make you smile.', '', '68e68365224dc_Taro berry milkbase.png', '2025-10-08 15:29:41', NULL, 1, 9, 19, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_addons`
--

CREATE TABLE `product_addons` (
  `Product_ID` int(11) NOT NULL,
  `Addon_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_price`
--

CREATE TABLE `product_price` (
  `Price_ID` int(11) NOT NULL,
  `Price_Amount` decimal(10,2) NOT NULL,
  `Effective_From` date NOT NULL,
  `Effective_To` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_price`
--

INSERT INTO `product_price` (`Price_ID`, `Price_Amount`, `Effective_From`, `Effective_To`) VALUES
(1, 85.00, '2025-10-07', '2026-10-07'),
(2, 70.00, '2025-10-07', '2026-10-07'),
(3, 80.00, '2025-10-07', '2026-10-07'),
(4, 215.00, '2025-10-07', '2026-10-07'),
(5, 245.00, '2025-10-07', '2026-10-07'),
(6, 250.00, '2025-10-07', '2026-10-07'),
(7, 295.00, '2025-10-07', '2026-10-07'),
(8, 345.00, '2025-10-07', '2026-10-07'),
(9, 485.00, '2025-10-07', '2026-10-07'),
(10, 45.00, '2025-10-08', NULL),
(11, 10.00, '2025-10-08', '2026-10-08'),
(12, 55.00, '2025-10-08', '2026-10-08'),
(13, 89.00, '2025-10-08', '2026-10-08'),
(14, 60.00, '2025-10-08', NULL),
(15, 30.00, '2025-10-08', '2026-10-08'),
(16, 50.00, '2025-10-08', '2026-10-08'),
(17, 20.00, '2025-10-08', '2026-10-08'),
(18, 70.00, '2025-10-08', '2026-10-08'),
(19, 75.00, '2025-10-08', '2026-10-08'),
(20, 65.00, '2025-10-08', NULL),
(21, 5.00, '2025-10-08', NULL),
(22, 39.00, '2025-10-08', NULL),
(23, 95.00, '2025-10-09', '2026-10-09'),
(24, 105.00, '2025-10-09', '2026-10-09'),
(25, 90.00, '2025-10-09', '2026-10-09'),
(26, 15.00, '2025-10-09', '2026-10-09'),
(27, 25.00, '2025-10-09', '2026-10-09');

-- --------------------------------------------------------

--
-- Table structure for table `product_sizes`
--

CREATE TABLE `product_sizes` (
  `Product_Size_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Size_Code` varchar(20) NOT NULL,
  `Price_Amount` decimal(10,2) NOT NULL,
  `Is_Absolute` tinyint(1) NOT NULL DEFAULT 1,
  `Created_At` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `product_sizes`
--

INSERT INTO `product_sizes` (`Product_Size_ID`, `Product_ID`, `Size_Code`, `Price_Amount`, `Is_Absolute`, `Created_At`) VALUES
(1, 5, '16oz', 60.00, 1, '2025-10-07 14:12:31'),
(2, 5, '22oz', 70.00, 1, '2025-10-07 14:12:31'),
(4, 44, '16oz', 45.00, 0, '2025-10-07 15:12:18'),
(5, 43, '16oz', 55.00, 0, '2025-10-07 15:12:53'),
(6, 42, '16oz', 45.00, 0, '2025-10-07 15:13:11'),
(7, 41, '16oz', 55.00, 0, '2025-10-07 15:13:28'),
(8, 40, '16oz', 45.00, 0, '2025-10-07 15:13:52'),
(9, 39, '16oz', 55.00, 0, '2025-10-07 15:14:07'),
(10, 38, '16oz', 45.00, 0, '2025-10-07 15:14:23'),
(11, 37, '16oz', 55.00, 0, '2025-10-07 15:14:32'),
(12, 36, '16oz', 45.00, 0, '2025-10-07 15:14:44'),
(13, 35, '16oz', 55.00, 0, '2025-10-07 15:14:59');

-- --------------------------------------------------------

--
-- Table structure for table `product_size_price`
--

CREATE TABLE `product_size_price` (
  `Product_Size_Price_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Size_ID` int(11) NOT NULL,
  `Price_Mode` enum('ABS','DELTA') NOT NULL DEFAULT 'ABS',
  `Price_Value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Price_Source_ID` int(11) DEFAULT NULL,
  `Is_Anchor` tinyint(1) NOT NULL DEFAULT 0,
  `Created_At` datetime NOT NULL DEFAULT current_timestamp(),
  `Updated_At` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `product_size_price`
--

INSERT INTO `product_size_price` (`Product_Size_Price_ID`, `Product_ID`, `Size_ID`, `Price_Mode`, `Price_Value`, `Price_Source_ID`, `Is_Anchor`, `Created_At`, `Updated_At`) VALUES
(1, 44, 1, 'ABS', 45.00, 10, 1, '2025-10-08 04:47:41', '2025-10-08 08:52:13'),
(3, 44, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 09:19:35', '2025-10-08 09:19:35'),
(4, 40, 1, 'ABS', 45.00, 10, 1, '2025-10-08 10:56:01', '2025-10-08 10:56:01'),
(5, 40, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 10:56:26', '2025-10-08 10:56:26'),
(6, 43, 1, 'ABS', 55.00, 12, 1, '2025-10-08 10:58:48', '2025-10-08 10:58:48'),
(7, 43, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 10:59:05', '2025-10-08 10:59:05'),
(8, 42, 1, 'ABS', 45.00, 10, 1, '2025-10-08 10:59:42', '2025-10-08 10:59:42'),
(9, 42, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 10:59:54', '2025-10-08 10:59:54'),
(10, 41, 1, 'ABS', 55.00, 12, 1, '2025-10-08 11:00:26', '2025-10-08 11:00:26'),
(11, 41, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 11:00:36', '2025-10-08 11:00:36'),
(12, 39, 1, 'ABS', 55.00, 12, 1, '2025-10-08 11:01:10', '2025-10-08 11:01:10'),
(13, 39, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 11:01:22', '2025-10-08 11:01:22'),
(14, 38, 1, 'ABS', 45.00, 10, 1, '2025-10-08 11:01:52', '2025-10-08 11:01:52'),
(15, 38, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 11:02:02', '2025-10-08 11:02:02'),
(16, 37, 1, 'ABS', 55.00, 12, 1, '2025-10-08 11:02:56', '2025-10-08 11:02:56'),
(17, 37, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 11:03:06', '2025-10-08 11:03:06'),
(18, 36, 1, 'ABS', 45.00, 10, 1, '2025-10-08 11:03:39', '2025-10-08 11:03:39'),
(19, 36, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 11:03:55', '2025-10-08 11:03:55'),
(20, 35, 1, 'ABS', 55.00, 12, 1, '2025-10-08 11:04:14', '2025-10-08 11:04:14'),
(21, 35, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 11:04:24', '2025-10-08 11:04:24'),
(22, 34, 1, 'ABS', 45.00, 10, 1, '2025-10-08 11:07:30', '2025-10-08 11:07:30'),
(23, 34, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 11:07:39', '2025-10-08 11:07:39'),
(24, 33, 1, 'ABS', 45.00, 10, 1, '2025-10-08 11:07:55', '2025-10-08 11:07:55'),
(25, 33, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 11:08:03', '2025-10-08 11:08:03'),
(26, 32, 1, 'ABS', 55.00, 12, 1, '2025-10-08 11:08:20', '2025-10-08 11:08:20'),
(27, 32, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 11:08:28', '2025-10-08 11:08:28'),
(28, 31, 1, 'ABS', 55.00, 12, 1, '2025-10-08 11:08:45', '2025-10-08 11:08:45'),
(29, 31, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 11:08:54', '2025-10-08 11:08:54'),
(30, 30, 1, 'ABS', 45.00, 10, 1, '2025-10-08 11:09:30', '2025-10-08 11:09:30'),
(31, 30, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 11:09:44', '2025-10-08 11:09:44'),
(32, 46, 3, 'ABS', 89.00, 13, 1, '2025-10-08 12:15:55', '2025-10-08 12:15:55'),
(33, 46, 6, 'DELTA', 60.00, 14, 0, '2025-10-08 12:17:06', '2025-10-08 12:17:06'),
(34, 45, 3, 'ABS', 30.00, 15, 1, '2025-10-08 12:22:18', '2025-10-08 12:22:18'),
(35, 45, 8, 'DELTA', 20.00, 17, 0, '2025-10-08 12:23:48', '2025-10-08 12:23:48'),
(36, 45, 9, 'DELTA', 70.00, 18, 0, '2025-10-08 12:24:44', '2025-10-08 12:24:44'),
(37, 16, 1, 'ABS', 60.00, 14, 1, '2025-10-08 13:20:57', '2025-10-08 13:20:57'),
(38, 16, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 13:21:17', '2025-10-08 13:21:17'),
(39, 15, 1, 'ABS', 60.00, 14, 1, '2025-10-08 13:21:44', '2025-10-08 13:21:44'),
(40, 15, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 13:21:59', '2025-10-08 13:21:59'),
(41, 14, 1, 'ABS', 75.00, 19, 1, '2025-10-08 13:33:48', '2025-10-08 14:04:08'),
(42, 14, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 13:34:28', '2025-10-08 13:34:28'),
(43, 13, 1, 'ABS', 60.00, 14, 1, '2025-10-08 14:01:23', '2025-10-08 14:01:23'),
(44, 13, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:02:02', '2025-10-08 14:02:02'),
(45, 3, 1, 'ABS', 70.00, 2, 1, '2025-10-08 14:05:25', '2025-10-08 14:05:25'),
(46, 3, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:05:47', '2025-10-08 14:05:47'),
(47, 12, 1, 'ABS', 60.00, 14, 1, '2025-10-08 14:08:07', '2025-10-08 14:08:07'),
(48, 12, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:08:35', '2025-10-08 14:08:35'),
(49, 11, 1, 'ABS', 60.00, 14, 1, '2025-10-08 14:08:58', '2025-10-08 14:08:58'),
(50, 11, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:09:17', '2025-10-08 14:09:17'),
(51, 10, 1, 'ABS', 60.00, 14, 1, '2025-10-08 14:09:38', '2025-10-08 14:09:38'),
(52, 10, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:09:48', '2025-10-08 14:09:48'),
(53, 5, 1, 'ABS', 60.00, 14, 1, '2025-10-08 14:10:05', '2025-10-08 14:10:05'),
(54, 5, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:10:22', '2025-10-08 14:10:22'),
(55, 4, 1, 'ABS', 60.00, 14, 1, '2025-10-08 14:10:57', '2025-10-08 14:10:57'),
(56, 4, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:11:10', '2025-10-08 14:11:10'),
(57, 2, 1, 'ABS', 60.00, 14, 1, '2025-10-08 14:12:05', '2025-10-08 14:12:05'),
(58, 2, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:12:20', '2025-10-08 14:12:20'),
(59, 1, 1, 'ABS', 60.00, 14, 1, '2025-10-08 14:12:53', '2025-10-08 14:12:53'),
(60, 1, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:13:04', '2025-10-08 14:13:04'),
(61, 58, 1, 'ABS', 85.00, 1, 1, '2025-10-08 14:16:34', '2025-10-08 14:16:34'),
(62, 58, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:16:48', '2025-10-08 14:16:48'),
(63, 57, 1, 'ABS', 85.00, 1, 1, '2025-10-08 14:16:57', '2025-10-08 14:16:57'),
(64, 57, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:17:06', '2025-10-08 14:17:06'),
(65, 21, 1, 'ABS', 50.00, 16, 1, '2025-10-08 14:18:02', '2025-10-08 14:18:02'),
(66, 21, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:18:11', '2025-10-08 14:18:11'),
(67, 20, 1, 'ABS', 50.00, 16, 1, '2025-10-08 14:18:24', '2025-10-08 14:18:24'),
(68, 20, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:19:03', '2025-10-08 14:19:03'),
(69, 19, 1, 'ABS', 50.00, 16, 1, '2025-10-08 14:19:17', '2025-10-08 14:19:17'),
(70, 19, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:19:27', '2025-10-08 14:19:27'),
(71, 18, 1, 'ABS', 50.00, 16, 1, '2025-10-08 14:19:36', '2025-10-08 14:19:36'),
(72, 18, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:19:56', '2025-10-08 14:19:56'),
(73, 17, 1, 'ABS', 50.00, 16, 1, '2025-10-08 14:20:08', '2025-10-08 14:20:08'),
(74, 17, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:20:19', '2025-10-08 14:20:19'),
(75, 9, 1, 'ABS', 50.00, 16, 1, '2025-10-08 14:20:28', '2025-10-08 14:20:28'),
(76, 9, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:20:36', '2025-10-08 14:20:36'),
(77, 8, 1, 'ABS', 50.00, 16, 1, '2025-10-08 14:20:47', '2025-10-08 14:20:47'),
(78, 8, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:20:56', '2025-10-08 14:20:56'),
(79, 7, 1, 'ABS', 50.00, 16, 1, '2025-10-08 14:21:10', '2025-10-08 14:21:10'),
(80, 7, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:21:20', '2025-10-08 14:21:20'),
(81, 59, 1, 'ABS', 65.00, 20, 1, '2025-10-08 14:43:36', '2025-10-08 14:43:36'),
(82, 59, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:44:02', '2025-10-08 14:44:02'),
(83, 62, 1, 'ABS', 65.00, 20, 1, '2025-10-08 14:53:29', '2025-10-08 14:53:29'),
(84, 62, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:53:43', '2025-10-08 14:53:43'),
(85, 63, 1, 'ABS', 65.00, 20, 1, '2025-10-08 14:56:25', '2025-10-08 14:56:25'),
(86, 63, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 14:56:50', '2025-10-08 14:56:50'),
(87, 64, 1, 'ABS', 65.00, 20, 1, '2025-10-08 14:59:18', '2025-10-08 14:59:18'),
(88, 64, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 15:00:41', '2025-10-08 15:00:41'),
(89, 65, 1, 'ABS', 65.00, 20, 1, '2025-10-08 15:02:42', '2025-10-08 15:02:42'),
(90, 65, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 15:03:01', '2025-10-08 15:03:01'),
(91, 66, 1, 'ABS', 65.00, 20, 1, '2025-10-08 15:07:08', '2025-10-08 15:07:08'),
(92, 66, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 15:07:26', '2025-10-08 15:07:26'),
(93, 67, 1, 'ABS', 65.00, 20, 1, '2025-10-08 15:07:36', '2025-10-08 15:07:36'),
(94, 67, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 15:07:49', '2025-10-08 15:07:49'),
(95, 68, 1, 'ABS', 65.00, 20, 1, '2025-10-08 15:09:51', '2025-10-08 15:09:51'),
(96, 68, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 15:10:08', '2025-10-08 15:10:08'),
(98, 69, 3, 'ABS', 39.00, 22, 1, '2025-10-08 15:23:02', '2025-10-08 15:23:02'),
(99, 69, 6, 'DELTA', 10.00, 11, 0, '2025-10-08 15:23:38', '2025-10-08 15:23:38'),
(101, 70, 1, 'ABS', 60.00, 14, 1, '2025-10-08 15:47:49', '2025-10-08 15:47:49'),
(102, 70, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 15:48:10', '2025-10-08 15:48:10'),
(103, 71, 1, 'ABS', 60.00, 14, 1, '2025-10-08 15:48:30', '2025-10-08 15:48:30'),
(104, 71, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 15:48:42', '2025-10-08 15:48:42'),
(105, 72, 1, 'ABS', 75.00, 19, 1, '2025-10-08 15:48:59', '2025-10-08 15:49:22'),
(106, 72, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 15:49:34', '2025-10-08 15:49:34'),
(107, 73, 1, 'ABS', 75.00, 19, 1, '2025-10-08 15:50:10', '2025-10-08 15:50:10'),
(108, 73, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 15:50:32', '2025-10-08 15:50:32'),
(109, 74, 1, 'ABS', 75.00, 19, 1, '2025-10-08 15:50:51', '2025-10-08 15:50:51'),
(110, 74, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 15:51:04', '2025-10-08 15:51:04'),
(111, 75, 1, 'ABS', 75.00, 19, 1, '2025-10-08 15:51:28', '2025-10-08 15:51:28'),
(112, 75, 2, 'DELTA', 10.00, 11, 0, '2025-10-08 15:51:37', '2025-10-08 15:51:37'),
(113, 29, 1, 'ABS', 95.00, 23, 1, '2025-10-08 16:06:45', '2025-10-08 16:06:45'),
(114, 29, 2, 'DELTA', 15.00, 26, 0, '2025-10-08 16:07:16', '2025-10-08 16:20:19'),
(115, 28, 1, 'ABS', 75.00, 19, 1, '2025-10-08 16:08:02', '2025-10-08 16:08:02'),
(116, 28, 2, 'DELTA', 15.00, 26, 0, '2025-10-08 16:10:35', '2025-10-08 16:10:35'),
(117, 27, 1, 'ABS', 105.00, 24, 1, '2025-10-08 16:10:57', '2025-10-08 16:10:57'),
(118, 27, 2, 'DELTA', 15.00, 26, 0, '2025-10-08 16:11:22', '2025-10-08 16:11:22'),
(119, 26, 1, 'ABS', 75.00, 19, 1, '2025-10-08 16:11:52', '2025-10-08 16:11:52'),
(120, 26, 2, 'DELTA', 15.00, 26, 0, '2025-10-08 16:12:15', '2025-10-08 16:12:15'),
(121, 25, 1, 'ABS', 90.00, 25, 1, '2025-10-08 16:14:27', '2025-10-08 16:14:27'),
(122, 25, 2, 'DELTA', 25.00, 27, 0, '2025-10-08 16:14:47', '2025-10-08 16:14:47'),
(123, 24, 1, 'ABS', 95.00, 23, 1, '2025-10-08 16:15:21', '2025-10-08 16:15:21'),
(124, 24, 2, 'DELTA', 15.00, 26, 0, '2025-10-08 16:15:47', '2025-10-08 16:15:47'),
(125, 23, 1, 'ABS', 95.00, 23, 1, '2025-10-08 16:16:12', '2025-10-08 16:16:12'),
(126, 23, 2, 'DELTA', 15.00, 26, 0, '2025-10-08 16:16:35', '2025-10-08 16:16:35'),
(127, 22, 1, 'ABS', 95.00, 23, 1, '2025-10-08 16:17:23', '2025-10-08 16:17:23'),
(128, 22, 2, 'DELTA', 15.00, 26, 0, '2025-10-08 16:17:44', '2025-10-08 16:17:44');

-- --------------------------------------------------------

--
-- Table structure for table `product_variant`
--

CREATE TABLE `product_variant` (
  `Variant_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `variant_type` enum('size','flavor') NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `price_mode` enum('ABSOLUTE','DELTA') NOT NULL DEFAULT 'DELTA',
  `price_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variant`
--

INSERT INTO `product_variant` (`Variant_ID`, `Product_ID`, `variant_type`, `code`, `label`, `price_mode`, `price_value`, `is_primary`, `active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 48, 'flavor', 'Pork', 'Pork', 'ABSOLUTE', 5.00, 1, 1, 0, '2025-10-08 16:18:16', '2025-10-08 16:20:03'),
(2, 48, 'flavor', 'Chicken', 'Chicken', 'ABSOLUTE', 5.00, 0, 1, 1, '2025-10-08 16:19:41', '2025-10-08 16:20:03'),
(3, 47, 'flavor', 'Tuna', 'Tuna', 'ABSOLUTE', 110.00, 1, 1, 0, '2025-10-08 17:36:28', '2025-10-08 17:36:32'),
(4, 47, 'flavor', 'Ham', 'Ham', 'ABSOLUTE', 108.00, 0, 1, 1, '2025-10-08 17:36:57', '2025-10-08 17:36:57'),
(5, 47, 'flavor', 'Bacon', 'Bacon', 'ABSOLUTE', 118.00, 0, 1, 2, '2025-10-08 17:37:28', '2025-10-08 17:37:28'),
(6, 47, 'flavor', 'Chicken', 'Chicken', 'ABSOLUTE', 128.00, 0, 1, 3, '2025-10-08 17:38:03', '2025-10-08 17:38:03'),
(7, 49, 'flavor', 'Egg', 'Egg', 'ABSOLUTE', 70.00, 1, 1, 0, '2025-10-08 17:38:31', '2025-10-08 17:38:31'),
(8, 49, 'flavor', 'Corn & Cheese', 'Corn & Cheese', 'ABSOLUTE', 85.00, 0, 1, 1, '2025-10-08 17:39:11', '2025-10-08 17:39:11'),
(9, 49, 'flavor', 'Ham & Cheese', 'Ham & Cheese', 'ABSOLUTE', 85.00, 0, 1, 2, '2025-10-08 17:40:06', '2025-10-08 17:40:06'),
(10, 49, 'flavor', 'Tuna & Cheese', 'Tuna & Cheese', 'ABSOLUTE', 90.00, 0, 1, 3, '2025-10-08 17:40:37', '2025-10-08 17:40:37'),
(11, 49, 'flavor', 'Mushroom & Cheese', 'Mushroom & Cheese', 'ABSOLUTE', 90.00, 0, 1, 4, '2025-10-08 17:41:09', '2025-10-08 17:41:09');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `selector` char(18) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `account_type` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `expires_at` datetime NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_used` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `remember_tokens`
--

INSERT INTO `remember_tokens` (`id`, `selector`, `token_hash`, `user_id`, `account_type`, `expires_at`, `user_agent`, `ip_address`, `created_at`, `last_used`) VALUES
(2, '61e014ec03ee7d1e2d', 'c99ec57930ae8e09242ebfa2b00180f3affd5feedd8e981152037ec013095b54', 3, 'customer', '2025-11-08 01:55:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '111.90.221.3', '2025-10-07 01:11:31', '2025-10-09 01:55:11'),
(3, '169e15fb5b83e319db', '88ecdc23d2546545223ce9d3607e8d02b66c325713b0042a14ffb8b41d4beed1', 2, 'customer', '2025-11-07 07:50:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '136.158.79.217', '2025-10-08 07:50:20', NULL),
(4, '943adfb2301a474ba9', '724e7d780b97b305ffcbe685816c119304c9df29596aeb7cb1ccc07bf039927f', 2, 'customer', '2025-11-07 07:51:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '136.158.79.217', '2025-10-08 07:51:15', NULL),
(5, '2063f3b36f6acb6ebf', '6ef5b0bc03a681d45716ef9222a23400188ad490e85f0075cedf1d4ae7614741', 2, 'customer', '2025-11-07 07:52:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '136.158.79.217', '2025-10-08 07:52:02', NULL),
(6, 'eebb88390bf19fd000', '7697d3d71ce7c75fe050ca0610e73e059510e56801660671b81ca9f14044fdb0', 2, 'customer', '2025-11-07 07:53:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '136.158.79.217', '2025-10-08 07:53:06', NULL),
(7, 'bb9be6c643d77c0367', 'f2c6c38b5c74d35f95a4b64007191826e9ccf7ceea65462d6f1ea0cc85ef1eaf', 2, 'customer', '2025-11-07 08:00:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '136.158.79.217', '2025-10-08 08:00:56', NULL),
(8, '4725e1899e462f72a2', '5695224f7c70908f84fbea3b18d2de7650925919922f3a716f9e78b185b8f7bc', 2, 'customer', '2025-11-07 10:54:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '136.158.79.217', '2025-10-08 08:50:01', '2025-10-08 10:54:47'),
(9, '544c88db300a59dcee', 'a04f7217568f31cb4432a731c74750dc742d6d7e4658d360c847c46cbac026c5', 2, 'customer', '2025-11-07 10:57:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '136.158.79.27', '2025-10-08 10:57:33', NULL),
(10, '70ab67de6967ec29f9', '21d27cefceecdaa7ca1e8f23518e71ce3a82f9a44344e9aa69c43b9b44eed6c6', 4, 'customer', '2025-11-07 16:44:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '136.239.183.128', '2025-10-08 16:44:10', NULL),
(13, 'f915e5ac46c1b728f8', 'aee9ca5663939833f6e0d495dfa14807e224ea1f7e7a6366ec5b886f51c12e75', 2, 'customer', '2025-11-08 02:51:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '136.158.79.170', '2025-10-09 02:51:47', NULL),
(15, 'e7961759ce7de74ec1', '5a6f87fd79abfa9f4e6720be09cbce4183ef260a7b4bc231c207ab1ff41a8131', 4, 'customer', '2025-11-08 06:09:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '136.239.183.128', '2025-10-09 06:09:17', NULL),
(16, 'fe71ec31826994c1ea', '5fee154c85e1884d5012add891389aecb331c12993c1506e3ad449e956f3eb23', 7, 'customer', '2025-11-08 07:01:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '136.158.79.170', '2025-10-09 07:01:53', NULL),
(18, 'bc704bc69a0292f756', '5911b7cda204d1e28de9023bd259bcb23d24b250c3638756eace94394a70c6f8', 8, 'customer', '2025-11-08 21:56:57', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.2 Mobile/15E148 Safari/604.1', '175.176.24.107', '2025-10-09 21:54:53', '2025-10-09 21:56:57'),
(19, 'c51de0fce7c09885ff', '81c3256c477f07c0a5dc064069be7fd7eadc45f1b0b2ff87980a23c4e1191aaa', 7, 'customer', '2025-11-08 22:56:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '131.226.104.112', '2025-10-09 22:56:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `Review_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Customer_ID` int(11) NOT NULL,
  `Rating` int(11) NOT NULL CHECK (`Rating` between 1 and 5),
  `Review_Text` text DEFAULT NULL,
  `Review_Date` datetime NOT NULL DEFAULT current_timestamp(),
  `Updated_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`Review_ID`, `Product_ID`, `Customer_ID`, `Rating`, `Review_Text`, `Review_Date`, `Updated_At`) VALUES
(1, 1, 3, 5, 'Bat ganun sya', '2025-10-07 01:13:05', '2025-10-07 01:13:05');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `Sale_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Total_Amount` decimal(10,2) NOT NULL,
  `Sale_Date` datetime NOT NULL,
  `Admin_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`Sale_ID`, `Order_ID`, `Product_ID`, `Quantity`, `Total_Amount`, `Sale_Date`, `Admin_ID`) VALUES
(1, 1, 1, 1, 70.00, '2025-10-07 00:29:58', 1),
(2, 5, 1, 1, 70.00, '2025-10-07 01:12:46', 1),
(3, 13, 4, 1, 149.00, '2025-10-09 21:46:02', 1),
(4, 11, 4, 1, 139.00, '2025-10-09 21:49:40', 1),
(5, 14, 3, 1, 180.00, '2025-10-09 21:56:52', 1),
(6, 14, 45, 1, 180.00, '2025-10-09 21:56:52', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sizes`
--

CREATE TABLE `sizes` (
  `Size_ID` int(11) NOT NULL,
  `Size_Code` varchar(32) NOT NULL,
  `Display_Name` varchar(64) NOT NULL,
  `Category_Scope` varchar(64) DEFAULT NULL,
  `Sort_Order` int(11) NOT NULL DEFAULT 0,
  `Created_At` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `sizes`
--

INSERT INTO `sizes` (`Size_ID`, `Size_Code`, `Display_Name`, `Category_Scope`, `Sort_Order`, `Created_At`) VALUES
(1, '16oz', '16oz', NULL, 3, '2025-10-07 17:50:47'),
(2, '22oz', '22oz', NULL, 1, '2025-10-07 17:50:47'),
(3, 'small', 'Small', NULL, 2, '2025-10-07 18:26:11'),
(6, 'big', 'Big', NULL, 0, '2025-10-07 18:48:54'),
(8, 'medium', 'Medium', NULL, 0, '2025-10-07 18:52:31'),
(9, 'overload', 'Overload', NULL, 0, '2025-10-07 18:52:55'),
(13, 'regular', 'Regular', NULL, 0, '2025-10-07 19:21:42'),
(120, 'pork', 'Pork', NULL, 0, '2025-10-08 15:14:19'),
(121, 'chicken', 'Chicken', NULL, 4, '2025-10-08 15:14:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addons`
--
ALTER TABLE `addons`
  ADD PRIMARY KEY (`Addon_ID`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`Admin_ID`),
  ADD UNIQUE KEY `Admin_Email` (`Admin_Email`);

--
-- Indexes for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD PRIMARY KEY (`customer_id`),
  ADD KEY `idx_blocked_at` (`blocked_at`);

--
-- Indexes for table `blocked_users_log`
--
ALTER TABLE `blocked_users_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cid` (`customer_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`Category_ID`),
  ADD UNIQUE KEY `Category_Name` (`Category_Name`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`Customer_ID`),
  ADD UNIQUE KEY `Customer_Email` (`Customer_Email`);

--
-- Indexes for table `customer_address`
--
ALTER TABLE `customer_address`
  ADD PRIMARY KEY (`Customer_ID`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`Driver_ID`),
  ADD UNIQUE KEY `Phone` (`Gmail`),
  ADD KEY `idx_api_token` (`Api_Token`(64));

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`Notification_ID`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`Order_ID`),
  ADD KEY `Customer_ID` (`Customer_ID`),
  ADD KEY `fk_orders_driver` (`Driver_ID`);

--
-- Indexes for table `order_address`
--
ALTER TABLE `order_address`
  ADD PRIMARY KEY (`Address_ID`),
  ADD UNIQUE KEY `uq_order_address_order_id` (`Order_ID`);

--
-- Indexes for table `order_delivery`
--
ALTER TABLE `order_delivery`
  ADD PRIMARY KEY (`Delivery_ID`),
  ADD UNIQUE KEY `uq_order_delivery_order_id` (`Order_ID`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`Order_Item_ID`),
  ADD KEY `Order_ID` (`Order_ID`),
  ADD KEY `Product_ID` (`Product_ID`),
  ADD KEY `fk_order_item_size_variant` (`Size_Variant_ID`),
  ADD KEY `fk_order_item_flavor_variant` (`Flavor_Variant_ID`);

--
-- Indexes for table `order_item_addons`
--
ALTER TABLE `order_item_addons`
  ADD PRIMARY KEY (`Order_Item_Addon_ID`),
  ADD KEY `idx_oia_order` (`Order_ID`),
  ADD KEY `fk_oia_product` (`Product_ID`),
  ADD KEY `fk_oia_addon` (`Addon_ID`);

--
-- Indexes for table `order_payment_receipt`
--
ALTER TABLE `order_payment_receipt`
  ADD PRIMARY KEY (`Payment_Receipt_ID`),
  ADD UNIQUE KEY `uq_order_payrec_order_id` (`Order_ID`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`Order_Status_Event_ID`),
  ADD KEY `Order_ID` (`Order_ID`,`Event_Type`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`Payment_ID`),
  ADD KEY `Order_ID` (`Order_ID`),
  ADD KEY `Admin_ID` (`Admin_ID`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`Product_ID`),
  ADD KEY `Admin_ID` (`Admin_ID`),
  ADD KEY `Category_ID` (`Category_ID`),
  ADD KEY `Price_ID` (`Price_ID`),
  ADD KEY `Primary_Size_ID` (`Primary_Size_ID`);

--
-- Indexes for table `product_addons`
--
ALTER TABLE `product_addons`
  ADD PRIMARY KEY (`Product_ID`,`Addon_ID`),
  ADD KEY `fk_pa_addon` (`Addon_ID`);

--
-- Indexes for table `product_price`
--
ALTER TABLE `product_price`
  ADD PRIMARY KEY (`Price_ID`);

--
-- Indexes for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD PRIMARY KEY (`Product_Size_ID`),
  ADD UNIQUE KEY `uq_prod_size` (`Product_ID`,`Size_Code`);

--
-- Indexes for table `product_size_price`
--
ALTER TABLE `product_size_price`
  ADD PRIMARY KEY (`Product_Size_Price_ID`),
  ADD UNIQUE KEY `uq_prod_size` (`Product_ID`,`Size_ID`),
  ADD KEY `Product_ID` (`Product_ID`),
  ADD KEY `Size_ID` (`Size_ID`),
  ADD KEY `Price_Source_ID` (`Price_Source_ID`),
  ADD KEY `Is_Anchor` (`Is_Anchor`);

--
-- Indexes for table `product_variant`
--
ALTER TABLE `product_variant`
  ADD PRIMARY KEY (`Variant_ID`),
  ADD UNIQUE KEY `uq_product_variant_code` (`Product_ID`,`variant_type`,`code`),
  ADD KEY `idx_variant_type_product` (`variant_type`,`Product_ID`),
  ADD KEY `idx_variant_product_active` (`Product_ID`,`active`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`Review_ID`),
  ADD KEY `Product_ID` (`Product_ID`),
  ADD KEY `Customer_ID` (`Customer_ID`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`Sale_ID`),
  ADD KEY `Order_ID` (`Order_ID`),
  ADD KEY `Product_ID` (`Product_ID`),
  ADD KEY `Admin_ID` (`Admin_ID`);

--
-- Indexes for table `sizes`
--
ALTER TABLE `sizes`
  ADD PRIMARY KEY (`Size_ID`),
  ADD UNIQUE KEY `Size_Code` (`Size_Code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addons`
--
ALTER TABLE `addons`
  MODIFY `Addon_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `blocked_users_log`
--
ALTER TABLE `blocked_users_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `Category_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `Customer_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `Driver_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `Notification_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `Order_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `order_address`
--
ALTER TABLE `order_address`
  MODIFY `Address_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_delivery`
--
ALTER TABLE `order_delivery`
  MODIFY `Delivery_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `Order_Item_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `order_item_addons`
--
ALTER TABLE `order_item_addons`
  MODIFY `Order_Item_Addon_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_payment_receipt`
--
ALTER TABLE `order_payment_receipt`
  MODIFY `Payment_Receipt_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `Order_Status_Event_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `Payment_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `Product_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `product_price`
--
ALTER TABLE `product_price`
  MODIFY `Price_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `product_sizes`
--
ALTER TABLE `product_sizes`
  MODIFY `Product_Size_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_size_price`
--
ALTER TABLE `product_size_price`
  MODIFY `Product_Size_Price_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `product_variant`
--
ALTER TABLE `product_variant`
  MODIFY `Variant_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `Review_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `Sale_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sizes`
--
ALTER TABLE `sizes`
  MODIFY `Size_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customer_address`
--
ALTER TABLE `customer_address`
  ADD CONSTRAINT `fk_ca_customer` FOREIGN KEY (`Customer_ID`) REFERENCES `customer` (`Customer_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_driver` FOREIGN KEY (`Driver_ID`) REFERENCES `drivers` (`Driver_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`Customer_ID`) REFERENCES `customer` (`Customer_ID`);

--
-- Constraints for table `order_address`
--
ALTER TABLE `order_address`
  ADD CONSTRAINT `fk_order_address_order` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`Order_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_delivery`
--
ALTER TABLE `order_delivery`
  ADD CONSTRAINT `fk_order_delivery_order` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`Order_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `fk_order_item_flavor_variant` FOREIGN KEY (`Flavor_Variant_ID`) REFERENCES `product_variant` (`Variant_ID`),
  ADD CONSTRAINT `fk_order_item_size_variant` FOREIGN KEY (`Size_Variant_ID`) REFERENCES `product_variant` (`Variant_ID`),
  ADD CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`Order_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`);

--
-- Constraints for table `order_item_addons`
--
ALTER TABLE `order_item_addons`
  ADD CONSTRAINT `fk_oia_addon` FOREIGN KEY (`Addon_ID`) REFERENCES `addons` (`Addon_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_oia_order` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`Order_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_oia_product` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_payment_receipt`
--
ALTER TABLE `order_payment_receipt`
  ADD CONSTRAINT `fk_order_payrec_order` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`Order_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `fk_osh_order` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`Order_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`Order_ID`),
  ADD CONSTRAINT `payment_ibfk_3` FOREIGN KEY (`Admin_ID`) REFERENCES `admin` (`Admin_ID`);

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`Admin_ID`) REFERENCES `admin` (`Admin_ID`),
  ADD CONSTRAINT `product_ibfk_2` FOREIGN KEY (`Category_ID`) REFERENCES `category` (`Category_ID`),
  ADD CONSTRAINT `product_ibfk_3` FOREIGN KEY (`Price_ID`) REFERENCES `product_price` (`Price_ID`);

--
-- Constraints for table `product_addons`
--
ALTER TABLE `product_addons`
  ADD CONSTRAINT `fk_pa_addon` FOREIGN KEY (`Addon_ID`) REFERENCES `addons` (`Addon_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pa_product` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD CONSTRAINT `fk_ps_product` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_size_price`
--
ALTER TABLE `product_size_price`
  ADD CONSTRAINT `fk_psp_product` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_psp_size` FOREIGN KEY (`Size_ID`) REFERENCES `sizes` (`Size_ID`) ON DELETE CASCADE;

--
-- Constraints for table `product_variant`
--
ALTER TABLE `product_variant`
  ADD CONSTRAINT `fk_variant_product` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`Customer_ID`) REFERENCES `customer` (`Customer_ID`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`Order_ID`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`),
  ADD CONSTRAINT `sales_ibfk_3` FOREIGN KEY (`Admin_ID`) REFERENCES `admin` (`Admin_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
