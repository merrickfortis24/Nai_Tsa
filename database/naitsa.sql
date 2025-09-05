-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 03, 2025 at 06:56 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `naitsa`
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
(1, 'Tea', 42.00, 'Active', '2025-08-29 01:20:41', '2025-08-29 01:20:41'),
(2, 'Boba', 12.00, 'Active', '2025-08-29 07:47:44', '2025-08-29 07:47:44');

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
(1, 'John Merrick Fortis', '$2y$10$eYlTLYFOVdkRFrzCZUMzvOp4yx6xA1JkxkVCCcH6mVcHRG0u5XAN6', 'fortismerrick@gmail.com', 'Super Admin', '2025-06-16 19:04:56', '2025-06-23 13:53:29', 'Active', 'ad6c91df3337b62c241904eef8bc76602edf65c3d8e0c4df9a31c2636042bbbb', '2025-06-23 08:53:29'),
(6, 'James Andrew P. Onaa', '$2y$10$ToMAHHjptSNVVqT/yIOr9uKEX/vyDegrQbIZO3hAelJgCZWjBlRxq', 'jamesona@gmail.com', 'Manager', '2025-06-17 22:54:05', '2025-06-17 23:20:31', 'Active', NULL, NULL),
(11, 'DTG', '$2y$10$M8/Tong70TKT.7wa5Yy.DedICbevoPzBYrKxMt99lXWDWRfF.fF7u', 'dtg@gmail.com', 'Super Admin', '2025-06-23 13:54:34', NULL, 'Active', NULL, NULL);

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
(2, 'Coffee'),
(3, 'Dessert'),
(4, 'Juice'),
(8, 'Sharpe'),
(1, 'Snacks');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `Customer_ID` int(11) NOT NULL,
  `Customer_Name` varchar(100) NOT NULL,
  `Customer_Email` varchar(100) NOT NULL,
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

INSERT INTO `customer` (`Customer_ID`, `Customer_Name`, `Customer_Email`, `Customer_Password`, `reset_token`, `reset_expires`) VALUES
(1, 'qwe', 'fortismerrick@gmail.com', '$2y$10$/0edxS/Tfi2tVl6hl2/RkOeNr917uogepMkwz6lqjXJEkIadQDV82', '58838a2d625825f31b14e9a1f105ad4847426b028c5aa3974259f4621b7719b2', '2025-08-21 17:40:26'),
(2, 'Guest', '', '', NULL, NULL),
(3, 'hihi', 'alc@gmail.com', '$2y$10$mNIO1dDkd355QCpetMSLEOn/hH1Z/F.RWqfN80GIZ.xGIqF7BPlFq', NULL, NULL),
(4, 'qwe', 'alc899@gmail.com', '$2y$10$HWtAV8wmScZkhXBBPldrAemSuwGcZBEYJDFmEG4HxI3/.Se/fgXZ6', NULL, NULL),
(5, 'qwe', 'fortismerrick24@gmail.com', '$2y$10$w2nSVetp50h7kSAvy9raF.TSJ4ExdRYcdLF1gyKjuRuWsZYvIHaL6', NULL, NULL),
(6, 'rgerbetnjhvhv', 'jamesona@gmail.com', '$2y$10$nwmJnir6lvw.jDRhvqQn3.V0cwqacjUCv6qkezn8MYW371k3eEm6S', NULL, NULL),
(7, 'Wigs', 'fortismerrick123@gmail.com', '$2y$10$AfGTqKiLk359SbgxiS2rGO0lRMmlAvCFI/O1QlUQSage3neeVzS86', NULL, NULL),
(8, 'Wigs', 'fortismerrick12345@gmail.com', '$2y$10$urd30ROvyux9YO6znBz67ebrGCg571qj72pYh.B9v09QIi3dlI7FS', NULL, NULL),
(9, 'Ona', 'ona@gmail.com', '$2y$10$gxlGQC7B5ChfhpNo.gDWCuPdNzaiiecTshKlRe10XxwcOKhdICkGq', NULL, NULL),
(10, 'Queen', 'fortis@gmail.com', '$2y$10$MOFipycND3GhgNKND2vfQOV0cTY0XuD4dBbc9J1JOJYEKvg1WBJce', NULL, NULL),
(11, 'Queen', 'fortism@gmail.com', '$2y$10$vz7TFUxALzIWkkTOfw8h.O3mcmjbvhC3NBOsuIFMiwTfL6tbctUXm', NULL, NULL),
(12, 'John Merrick Faigmani Fortis', 'forti@gmail.com', '$2y$10$UU3vNM4iaD9Ny5FJr7dpQ.wDT/YyycLfdEl2qLmjcrx4dFDAjbjs6', NULL, NULL),
(13, 'sfgfd', 'asdf@gmai.com', '$2y$10$Y2R6jWYSpAi83mjhFX1dWeD7Ha9OaZ.adyTV1ER7dffxQ3bebS892', NULL, NULL),
(14, 'Jaime', 'jaime@gmail.com', '$2y$10$uDzk0c6khn9dCdF54etNoeiS0misPm.yxCX4w2eZOh2frWqFvoUsS', NULL, NULL);

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
  `Created_At` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`Driver_ID`, `Name`, `Gmail`, `Password_Hash`, `Api_Token`, `Token_Expires`, `Created_At`) VALUES
(1, 'Rider One', '09123456789', '$2y$10$SR/6wAzw7uNgvqi1swRBeuDHk94Im0GylysWpuql/pyQHuuLqHZYm', '6e25456e1908df93534e4cdc41918a8d9f417419f3674c86', '2025-09-06 19:36:49', '2025-08-31 00:30:48'),
(2, 'John Merrick Faigmani Fortis', '09940780881', '$2y$10$BJnmwxbRMlqZvjldOB0.fu6gT0XwRP/PAxytpREQa7NTz90SXVZ0.', NULL, NULL, '2025-08-31 12:17:18'),
(3, 'John', 'john@gmail.com', '$2y$10$Ncb3ySu2wM7C5hq2rdRSO.AIopRVm9N8xRSf/hQKcAE/.iSEz1ktS', 'f1a84d0128cb8972d88965481ebe3e89c23a33be45410db8852f4ed813de43f9', '2025-09-30 12:14:03', '2025-08-31 14:52:38');

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
(9, '', 'Payment Confirmed', 'Driver John confirmed payment for Order #91', '2025-09-03 12:40:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `Order_ID` int(11) NOT NULL,
  `Order_Date` datetime NOT NULL DEFAULT current_timestamp(),
  `Order_Amount` decimal(10,2) NOT NULL,
  `Customer_ID` int(11) NOT NULL,
  `Assigned_Driver_ID` int(11) DEFAULT NULL,
  `Street` varchar(255) DEFAULT NULL,
  `Barangay` varchar(100) DEFAULT NULL,
  `City` varchar(100) DEFAULT NULL,
  `Contact_Number` varchar(30) DEFAULT NULL,
  `order_type` enum('Pickup','Delivery') NOT NULL DEFAULT 'Delivery',
  `order_status` enum('Pending','Processing','Ready to pick up','Received','Ready to deliver','On the way','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `Driver_Status` enum('assigned','accepted','on_the_way','picked_up','delivered','rejected') NOT NULL DEFAULT 'assigned',
  `Picked_Up_At` datetime DEFAULT NULL,
  `On_The_Way_At` datetime DEFAULT NULL,
  `Delivered_At` datetime DEFAULT NULL,
  `Delivery_Fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Delivery_Distance_Km` decimal(6,2) DEFAULT NULL,
  `payment_received_at` datetime DEFAULT NULL,
  `payment_received_by` varchar(100) DEFAULT NULL,
  `Proof_Photo` varchar(255) DEFAULT NULL
) ;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`Order_ID`, `Order_Date`, `Order_Amount`, `Customer_ID`, `Assigned_Driver_ID`, `Street`, `Barangay`, `City`, `Contact_Number`, `order_type`, `order_status`, `Driver_Status`, `Picked_Up_At`, `On_The_Way_At`, `Delivered_At`, `Delivery_Fee`, `Delivery_Distance_Km`, `payment_received_at`, `payment_received_by`, `Proof_Photo`) VALUES
(70, '2025-06-22 11:31:42', 300.00, 7, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Received', 'delivered', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(71, '2025-06-22 13:17:25', 1050.00, 1, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Pending', 'assigned', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(72, '2025-06-23 08:38:05', 450.00, 1, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Pending', 'assigned', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(73, '2025-06-23 09:15:05', 450.00, 9, NULL, 'St Joseph Homes, St Joseph, Lipa, Batangas, Philippines', 'Inosloban', 'Lipa', '34564567568', 'Delivery', 'Delivered', 'delivered', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(74, '2025-06-23 12:41:56', 150.00, 9, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Pending', 'assigned', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(75, '2025-06-23 13:56:24', 300.00, 9, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Pending', 'assigned', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(76, '2025-08-14 21:44:04', 600.00, 12, NULL, 'POBLACION, CONCEPCION, ROMBLON', 'Inos', 'Lipa City', '09940780881', 'Delivery', 'Delivered', 'delivered', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(77, '2025-08-14 21:48:41', 150.00, 12, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Received', 'delivered', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(78, '2025-08-15 07:14:05', 150.00, 12, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Received', 'delivered', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(79, '2025-08-15 08:42:57', 660.00, 12, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Received', 'delivered', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(80, '2025-08-22 00:01:08', 199.99, 12, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Received', 'delivered', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(81, '2025-08-22 00:25:17', 150.00, 12, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Received', 'delivered', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(82, '2025-08-27 14:27:14', 150.00, 12, NULL, 'duhatan', '4', 'Lipa City', '09940780881', 'Delivery', 'Delivered', 'delivered', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(83, '2025-08-29 00:05:22', 150.00, 12, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Received', 'delivered', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(84, '2025-08-29 00:07:18', 600.00, 12, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Processing', 'accepted', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(85, '2025-08-29 00:07:53', 150.00, 12, NULL, 'duhatan', '4', 'Lipa City', '09940780881', 'Delivery', 'Cancelled', 'rejected', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(86, '2025-08-29 00:10:40', 300.00, 12, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Received', 'delivered', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(87, '2025-08-29 00:27:38', 300.00, 12, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Cancelled', 'rejected', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(88, '2025-08-29 01:25:27', 192.00, 12, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Cancelled', 'rejected', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(89, '2025-08-29 08:32:42', 457.00, 14, NULL, 'duhatan', '4', 'Lipa City', '09940780881', 'Delivery', 'Cancelled', 'rejected', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(90, '2025-08-29 08:33:12', 661.00, 14, NULL, 'duhatan', '4', 'Lipa City', '09940780881', 'Delivery', 'Delivered', 'delivered', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(91, '2025-08-29 10:20:59', 349.00, 14, 3, 'duhatan', '4ere', 'Lipa City', '09940780881', 'Delivery', 'Delivered', 'delivered', '2025-09-03 12:40:43', NULL, NULL, 49.00, NULL, '2025-09-03 12:40:45', 'Driver #3 - John', NULL),
(92, '2025-08-29 10:21:36', 199.00, 14, 3, 'Blk. 13 Lot 30, Cedar rd., St. Joseph Homes, Inosloban, Lipa City, Batangas', 'Inosloban', 'Lipa City, Batangas, Philippines', '09940780881', 'Delivery', 'Delivered', 'delivered', '2025-09-03 12:40:38', NULL, NULL, 49.00, NULL, '2025-09-03 12:40:40', 'Driver #3 - John', NULL),
(93, '2025-08-29 10:22:17', 199.00, 14, 3, 'Blk. 13 Lot 30, Cedar rd., St. Joseph Homes, Inosloban, Lipa City, Batangas', 'Inosloban', 'Lipa City, Batangas, Philippines', '09940780881', 'Delivery', 'Delivered', 'delivered', '2025-09-03 12:40:33', NULL, NULL, 49.00, NULL, '2025-09-03 12:40:34', 'Driver #3 - John', NULL),
(94, '2025-08-29 10:32:45', 239.00, 14, 3, 'Blk. 13 Lot 30, Cedar rd., St. Joseph Homes, Inosloban, Lipa City, Batangas', 'Inosloban', 'Lipa City, Batangas, Philippines', '09940780881', 'Delivery', 'Delivered', 'delivered', '2025-09-03 12:40:25', NULL, NULL, 89.00, 9.93, '2025-09-03 12:40:26', 'Driver #3 - John', NULL),
(95, '2025-08-30 22:36:16', 596.98, 14, NULL, 'POBLACION, CONCEPCION, ROMBLON', '4', 'Lipa City', '09940780881', 'Delivery', 'Delivered', 'delivered', NULL, NULL, NULL, 89.00, 9.60, '2025-08-31 01:38:10', 'Driver #1 - Rider One', NULL),
(96, '2025-08-31 18:23:26', 1029.00, 14, NULL, 'POBLACION, CONCEPCION, ROMBLON', 'Inos', 'Lipa City', '09940780881', 'Delivery', 'Delivered', 'delivered', NULL, NULL, NULL, 69.00, 7.05, NULL, NULL, NULL),
(97, '2025-09-01 13:28:26', 179.00, 14, 3, 'POBLACION, CONCEPCION, ROMBLON', 'Inos', 'Lipa City', '09940780881', 'Delivery', 'Delivered', 'delivered', '2025-09-02 13:46:37', NULL, NULL, 29.00, 0.00, '2025-09-02 13:48:10', 'Driver #3 - John', NULL),
(98, '2025-09-01 14:13:46', 199.99, 14, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Cancelled', 'rejected', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(99, '2025-09-02 13:47:00', 199.99, 14, 3, NULL, NULL, NULL, NULL, 'Pickup', 'Received', 'delivered', '2025-09-02 13:47:58', NULL, NULL, 0.00, NULL, '2025-09-02 13:48:07', 'Driver #3 - John', NULL),
(100, '2025-09-02 13:49:27', 150.00, 14, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Received', 'assigned', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(101, '2025-09-02 13:52:32', 288.99, 14, 3, 'POBLACION, CONCEPCION, ROMBLON', 'Inos', 'Lipa City', '09940780881', 'Delivery', 'Delivered', 'delivered', '2025-09-03 12:36:26', NULL, NULL, 89.00, 8.28, '2025-09-03 12:36:28', 'Driver #3 - John', NULL),
(102, '2025-09-03 12:31:59', 450.00, 14, NULL, NULL, NULL, NULL, NULL, 'Pickup', 'Received', 'assigned', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL),
(103, '2025-09-03 12:45:14', 239.00, 14, NULL, 'POBLACION, CONCEPCION, ROMBLON', 'Inos', 'Lipa City', '09940780881', 'Delivery', 'Pending', 'assigned', NULL, NULL, NULL, 89.00, 9.48, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `Order_Item_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL CHECK (`Quantity` > 0),
  `Price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`Order_Item_ID`, `Order_ID`, `Product_ID`, `Quantity`, `Price`) VALUES
(42, 70, 5, 2, 150.00),
(43, 71, 5, 2, 150.00),
(44, 71, 10, 1, 150.00),
(45, 71, 11, 1, 150.00),
(46, 71, 12, 1, 150.00),
(47, 71, 13, 1, 150.00),
(48, 71, 16, 1, 150.00),
(49, 72, 11, 3, 150.00),
(50, 73, 11, 3, 150.00),
(51, 74, 10, 1, 150.00),
(52, 75, 10, 2, 150.00),
(53, 76, 5, 4, 150.00),
(54, 77, 10, 1, 150.00),
(55, 78, 12, 1, 150.00),
(56, 79, 19, 3, 120.00),
(57, 79, 16, 2, 150.00),
(58, 80, 22, 1, 199.99),
(59, 81, 10, 1, 150.00),
(60, 82, 26, 1, 150.00),
(61, 83, 10, 1, 150.00),
(62, 84, 10, 2, 150.00),
(63, 84, 16, 2, 150.00),
(64, 85, 16, 1, 150.00),
(65, 86, 11, 2, 150.00),
(66, 87, 5, 2, 150.00),
(67, 88, 11, 1, 150.00),
(68, 89, 11, 2, 150.00),
(69, 90, 16, 3, 150.00),
(70, 91, 16, 2, 150.00),
(71, 92, 11, 1, 150.00),
(72, 93, 11, 1, 150.00),
(73, 94, 16, 1, 150.00),
(74, 95, 22, 2, 199.99),
(75, 96, 11, 5, 150.00),
(76, 97, 11, 1, 150.00),
(77, 98, 22, 1, 199.99),
(78, 99, 22, 1, 199.99),
(79, 100, 11, 1, 150.00),
(80, 101, 22, 1, 199.99),
(81, 102, 16, 3, 150.00),
(82, 103, 11, 1, 150.00);

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

--
-- Dumping data for table `order_item_addons`
--

INSERT INTO `order_item_addons` (`Order_Item_Addon_ID`, `Order_ID`, `Order_Item_ID`, `Product_ID`, `Addon_ID`, `Addon_Name`, `Addon_Price`, `Quantity`) VALUES
(1, 88, 67, 11, 1, 'Tea', 42.00, 1),
(2, 89, 68, 11, 2, 'Boba', 12.00, 2),
(3, 89, 68, 11, 1, 'Tea', 42.00, 2),
(4, 90, 69, 16, 2, 'Boba', 12.00, 3),
(5, 90, 69, 16, 1, 'Tea', 42.00, 3),
(6, 95, 74, 22, 2, 'Boba', 12.00, 2),
(7, 95, 74, 22, 1, 'Tea', 42.00, 2),
(8, 96, 75, 11, 1, 'Tea', 42.00, 5);

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
(65, '2025-06-22 11:31:42', 'COD', 300.00, 70, 1, 'Paid'),
(66, '2025-06-22 13:17:25', 'GCash', 1050.00, 71, 1, 'Unpaid'),
(67, '2025-06-23 08:38:05', 'COD', 450.00, 72, 1, 'Unpaid'),
(68, '2025-06-23 09:15:05', 'GCash', 450.00, 73, 1, 'Paid'),
(69, '2025-06-23 12:41:56', 'COD', 150.00, 74, 1, 'Unpaid'),
(70, '2025-06-23 13:56:24', 'COD', 300.00, 75, 1, 'Unpaid'),
(71, '2025-08-14 21:44:04', 'COD', 600.00, 76, 1, 'Paid'),
(72, '2025-08-14 21:48:41', 'COD', 150.00, 77, 1, 'Paid'),
(73, '2025-08-15 07:14:05', 'COD', 150.00, 78, 1, 'Paid'),
(74, '2025-08-15 08:42:57', 'COD', 660.00, 79, 1, 'Paid'),
(75, '2025-08-22 00:01:08', 'COD', 199.99, 80, 1, 'Paid'),
(76, '2025-08-22 00:25:17', 'COD', 150.00, 81, 1, 'Paid'),
(77, '2025-08-27 14:27:14', 'COD', 150.00, 82, 1, 'Paid'),
(78, '2025-08-29 00:05:22', 'COD', 150.00, 83, 1, 'Unpaid'),
(79, '2025-08-29 00:07:18', 'COD', 600.00, 84, 1, 'Unpaid'),
(80, '2025-08-29 00:07:53', 'COD', 150.00, 85, 1, 'Unpaid'),
(81, '2025-08-29 00:10:40', 'COD', 300.00, 86, 1, 'Unpaid'),
(82, '2025-08-29 00:27:38', 'COD', 300.00, 87, 1, 'Unpaid'),
(83, '2025-08-29 01:25:27', 'COD', 192.00, 88, 1, 'Unpaid'),
(84, '2025-08-29 08:32:42', 'COD', 457.00, 89, 1, 'Unpaid'),
(85, '2025-08-29 08:33:12', 'COD', 661.00, 90, 1, 'Paid'),
(86, '2025-08-29 10:20:59', 'COD', 349.00, 91, 1, 'Unpaid'),
(87, '2025-08-29 10:21:36', 'COD', 199.00, 92, 1, 'Unpaid'),
(88, '2025-08-29 10:22:17', 'COD', 199.00, 93, 1, 'Unpaid'),
(89, '2025-08-29 10:32:45', 'COD', 239.00, 94, 1, 'Unpaid'),
(90, '2025-08-30 22:36:17', 'COD', 596.98, 95, 1, 'Paid'),
(91, '2025-08-31 18:23:26', 'COD', 1029.00, 96, 1, 'Paid'),
(92, '2025-09-01 13:28:26', 'COD', 179.00, 97, 1, 'Unpaid'),
(93, '2025-09-01 14:13:46', 'COD', 199.99, 98, 1, 'Unpaid'),
(94, '2025-09-02 13:47:00', 'COD', 199.99, 99, 1, 'Unpaid'),
(95, '2025-09-02 13:49:27', 'COD', 150.00, 100, 1, 'Unpaid'),
(96, '2025-09-02 13:52:32', 'COD', 288.99, 101, 1, 'Unpaid'),
(97, '2025-09-03 12:31:59', 'COD', 450.00, 102, 1, 'Unpaid'),
(98, '2025-09-03 12:45:14', 'COD', 239.00, 103, 1, 'Unpaid');

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
  `Price_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`Product_ID`, `Product_Name`, `Product_desc`, `Product_allergens`, `Product_Image`, `Created_at`, `Updated_at`, `Admin_ID`, `Category_ID`, `Price_ID`) VALUES
(5, 'Kape', 'Barako', 'Milk', '68a731a198d8a_Screenshot 2024-01-23 215224.png', '2025-06-17 11:29:45', '2025-08-21 22:48:01', 1, 2, 2),
(10, 'Cake', 'Matamis', NULL, 'prod_6850e8dc40c311.17112224.jpg', '2025-06-17 12:02:36', '2025-06-17 12:02:36', 1, 2, 2),
(11, 'Sopas', 'Masabaw', NULL, 'prod_6850f0d2b41952.03284208.png', '2025-06-17 12:36:34', '2025-06-17 12:36:34', 1, 4, 2),
(12, 'Pizza', 'Creamy Pizza', NULL, 'prod_6850f1c8eddb47.39331295.jpg', '2025-06-17 12:40:40', '2025-06-17 12:40:40', 1, 1, 2),
(13, 'Ice Creams', 'Cold', NULL, 'prod_6850f2f3e164f2.47345723.jpg', '2025-06-17 12:45:39', '2025-06-23 09:36:08', 1, 3, 3),
(16, 'yuiop', 'sdvfsb', NULL, 'prod_685500e73e9f23.71753775.png', '2025-06-20 14:34:15', '2025-06-23 09:36:01', 1, 2, 2),
(18, 'srgerg', 'erget', NULL, 'prod_689e754ef22e71.36440808.png', '2025-08-15 07:46:23', '2025-08-15 07:46:23', 1, 4, 4),
(19, 'vfebw r', 'trherh', NULL, 'prod_689e755f7b8a09.50278017.png', '2025-08-15 07:46:39', '2025-08-15 07:46:39', 1, 8, 3),
(20, 'ytjymyj', 'yjmtu kj', NULL, 'prod_689e75d4546b04.19479591.png', '2025-08-15 07:48:36', '2025-08-15 07:48:36', 1, 8, 2),
(21, 'asdfghjkl', 'Lorem ipsum dolor sit amet consectetur adipiscing elit. Consectetur adipiscing elit quisque faucibus ex sapien vitae. Ex sapien vitae pellentesque sem placerat in id. Placerat in id cursus mi pretium tellus duis. Pretium tellus duis convallis tempus leo eu aenean.', NULL, 'prod_68a3b41c039f60.53908056.png', '2025-08-19 07:15:40', '2025-08-19 07:15:40', 1, 4, 1),
(22, 'kvghkvh', 'jhvkhvhkg', NULL, 'prod_68a3b55c832e90.21216738.png', '2025-08-19 07:21:00', '2025-08-19 07:21:00', 1, 8, 1),
(23, 'ehrtmjmy', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'Milk,Eggs,Peanuts', '', '2025-08-19 07:44:40', '2025-08-19 07:45:01', 1, 3, 2),
(24, 'ehrtmjmy', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'Eggs', 'prod_68a3bd0deda623.59805094.png', '2025-08-19 07:53:49', NULL, 1, 4, 1),
(25, 'dgjrhtgnmy', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'Milk,Eggs,Peanuts,Soy,Wheat,Tree nuts,Fish,Shellfish', 'prod_68a3bdc52b3088.74728156.png', '2025-08-19 07:56:53', '2025-08-19 07:56:53', 1, 4, 2),
(26, 'dhyjyrj', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'Milk,Eggs', 'prod_68a3bf0bc5ede3.97912759.png', '2025-08-19 08:02:19', '2025-08-19 08:02:19', 1, 3, 2),
(27, 'milk coffee', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'Milk', '68a51f3f00df9_maps.png', '2025-08-19 09:03:50', '2025-08-20 09:05:03', 1, 2, 1),
(32, 'Ice Creams', 'Cold', 'Milk,Eggs,Peanuts,Soy,Wheat,Tree nuts,Fish,Shellfish', '68a3f088cb242_maps.png', '2025-08-19 11:24:34', '2025-08-19 11:33:28', 1, 3, 3),
(34, 'ergt', 'dfbg', 'Fish', '68aea9c36bd4c_Screenshot 2023-10-14 163225.png', '2025-08-27 14:46:27', NULL, 1, 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_addons`
--

CREATE TABLE `product_addons` (
  `Product_ID` int(11) NOT NULL,
  `Addon_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_addons`
--

INSERT INTO `product_addons` (`Product_ID`, `Addon_ID`) VALUES
(5, 1),
(11, 1),
(32, 1),
(32, 2);

-- --------------------------------------------------------

--
-- Table structure for table `product_price`
--

CREATE TABLE `product_price` (
  `Price_ID` int(11) NOT NULL,
  `Price_Amount` decimal(10,2) NOT NULL,
  `Effective_From` date NOT NULL,
  `Effective_To` date DEFAULT NULL
) ;

--
-- Dumping data for table `product_price`
--

INSERT INTO `product_price` (`Price_ID`, `Price_Amount`, `Effective_From`, `Effective_To`) VALUES
(1, 199.99, '2024-06-01', NULL),
(2, 150.00, '2025-06-17', '2025-07-17'),
(3, 120.00, '2025-06-17', '2025-09-17'),
(4, 34.00, '2025-06-17', '2025-07-08'),
(5, 90.00, '2025-06-24', '2025-07-24');

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
(6, 5, 9, 5, 'jhohoiho', '2025-06-23 12:24:05', NULL),
(7, 10, 12, 5, 'tasty', '2025-08-14 21:10:03', NULL),
(8, 5, 12, 1, 'mapait', '2025-08-20 08:43:24', NULL),
(9, 5, 12, 1, 'mapait', '2025-08-20 08:43:24', NULL),
(10, 5, 12, 5, 'jcjwd', '2025-08-20 08:53:21', NULL),
(11, 5, 12, 5, 'jcjwd', '2025-08-20 08:53:21', NULL),
(12, 5, 12, 5, 'jcjebce', '2025-08-20 08:53:38', NULL),
(13, 5, 12, 5, 'jcjebce', '2025-08-20 08:53:38', NULL),
(14, 26, 12, 5, 'Masarap', '2025-08-28 21:44:51', '2025-08-28 21:44:51'),
(15, 26, 12, 5, 'Masarap', '2025-08-28 21:45:07', '2025-08-28 21:45:07'),
(16, 22, 12, 5, 'Tasty', '2025-08-28 22:05:34', '2025-08-28 22:05:34'),
(17, 16, 14, 5, 'madnsd', '2025-08-29 10:02:03', '2025-08-29 10:02:03');

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
(6, 70, 5, 2, 300.00, '2025-06-22 11:33:57', 1),
(7, 73, 11, 3, 450.00, '2025-06-23 09:16:21', 1),
(8, 76, 5, 4, 600.00, '2025-08-14 21:47:01', 1),
(9, 78, 12, 1, 150.00, '2025-08-22 00:17:42', 1);

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
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`Driver_ID`),
  ADD UNIQUE KEY `Phone` (`Gmail`);

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
  ADD KEY `fk_orders_driver` (`Assigned_Driver_ID`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`Order_Item_ID`),
  ADD KEY `Order_ID` (`Order_ID`),
  ADD KEY `Product_ID` (`Product_ID`);

--
-- Indexes for table `order_item_addons`
--
ALTER TABLE `order_item_addons`
  ADD PRIMARY KEY (`Order_Item_Addon_ID`),
  ADD KEY `idx_oia_order` (`Order_ID`),
  ADD KEY `fk_oia_product` (`Product_ID`),
  ADD KEY `fk_oia_addon` (`Addon_ID`);

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
  ADD KEY `Price_ID` (`Price_ID`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addons`
--
ALTER TABLE `addons`
  MODIFY `Addon_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `Category_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `Customer_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `Driver_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `Notification_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `Order_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `Order_Item_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `order_item_addons`
--
ALTER TABLE `order_item_addons`
  MODIFY `Order_Item_Addon_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `Payment_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `Product_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `product_price`
--
ALTER TABLE `product_price`
  MODIFY `Price_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `Review_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `Sale_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_driver` FOREIGN KEY (`Assigned_Driver_ID`) REFERENCES `drivers` (`Driver_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`Customer_ID`) REFERENCES `customer` (`Customer_ID`);

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
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
