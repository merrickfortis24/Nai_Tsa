-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 20, 2025 at 05:42 PM
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
(1, 'John Merrick Fortis', '$2y$10$eYlTLYFOVdkRFrzCZUMzvOp4yx6xA1JkxkVCCcH6mVcHRG0u5XAN6', 'fortismerrick@gmail.com', 'Super Admin', '2025-06-16 19:04:56', '2025-06-19 14:56:21', 'Active', '1947a7589853b08142c2f1f7167ed8faff00efbdd13c1753ead432d80dbce551', '2025-06-19 09:56:21'),
(6, 'James Andrew P. Onaa', '$2y$10$ToMAHHjptSNVVqT/yIOr9uKEX/vyDegrQbIZO3hAelJgCZWjBlRxq', 'jamesona@gmail.com', 'Manager', '2025-06-17 22:54:05', '2025-06-17 23:20:31', 'Active', NULL, NULL);

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
(1, 'Snacks');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `Customer_ID` int(11) NOT NULL,
  `Customer_Name` varchar(100) NOT NULL,
  `Customer_Email` varchar(100) NOT NULL,
  `Customer_Password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`Customer_ID`, `Customer_Name`, `Customer_Email`, `Customer_Password`) VALUES
(1, 'qwe', 'fortismerrick@gmail.com', '$2y$10$/0edxS/Tfi2tVl6hl2/RkOeNr917uogepMkwz6lqjXJEkIadQDV82'),
(2, 'Guest', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `Order_ID` int(11) NOT NULL,
  `Order_Date` datetime NOT NULL DEFAULT current_timestamp(),
  `Order_Amount` decimal(10,2) NOT NULL,
  `Customer_ID` int(11) NOT NULL,
  `Street` varchar(255) DEFAULT NULL,
  `Barangay` varchar(100) DEFAULT NULL,
  `City` varchar(100) DEFAULT NULL,
  `Contact_Number` varchar(30) DEFAULT NULL,
  `order_status` enum('Pending','Processing','Delivered') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`Order_ID`, `Order_Date`, `Order_Amount`, `Customer_ID`, `Street`, `Barangay`, `City`, `Contact_Number`, `order_status`) VALUES
(63, '2025-06-20 17:21:01', 1050.00, 1, NULL, NULL, NULL, NULL, 'Delivered'),
(64, '2025-06-20 19:03:54', 300.00, 1, 'Blk 13, Lot 30, Cedar rd., St. Joseph Homes', 'Inosloban', 'Lipa City', '09940780881', 'Delivered'),
(65, '2025-06-20 20:09:18', 450.00, 1, NULL, NULL, NULL, NULL, 'Delivered'),
(66, '2025-06-20 20:09:31', 450.00, 1, 'Blk 13, Lot 30, Cedar rd., St. Joseph Homes', 'Inosloban', 'Lipa City', '099940780881', 'Delivered'),
(67, '2025-06-20 21:44:29', 450.00, 1, NULL, NULL, NULL, NULL, 'Delivered'),
(68, '2025-06-20 23:20:22', 300.00, 1, NULL, NULL, NULL, NULL, 'Pending');

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
(34, 63, 5, 3, 150.00),
(35, 63, 13, 4, 150.00),
(36, 64, 5, 2, 150.00),
(37, 65, 5, 3, 150.00),
(38, 66, 10, 3, 150.00),
(39, 67, 5, 3, 150.00),
(40, 68, 5, 2, 150.00);

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
(58, '2025-06-20 17:21:01', 'COD', 1050.00, 63, 1, 'Paid'),
(59, '2025-06-20 19:03:54', 'GCash', 300.00, 64, 1, 'Paid'),
(60, '2025-06-20 20:09:18', 'COD', 450.00, 65, 1, 'Paid'),
(61, '2025-06-20 20:09:31', 'Credit Card', 450.00, 66, 1, 'Paid'),
(62, '2025-06-20 21:44:29', 'COD', 450.00, 67, 1, 'Paid'),
(63, '2025-06-20 23:20:22', 'COD', 300.00, 68, 1, 'Unpaid');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `Product_ID` int(11) NOT NULL,
  `Product_Name` varchar(255) NOT NULL,
  `Product_desc` text DEFAULT NULL,
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

INSERT INTO `product` (`Product_ID`, `Product_Name`, `Product_desc`, `Product_Image`, `Created_at`, `Updated_at`, `Admin_ID`, `Category_ID`, `Price_ID`) VALUES
(5, 'Kape', 'Barako', 'prod_6850e473acda40.60150267.png', '2025-06-17 11:29:45', '2025-06-17 11:44:27', 1, 2, 2),
(10, 'Cake', 'Matamis', 'prod_6850e8dc40c311.17112224.jpg', '2025-06-17 12:02:36', '2025-06-17 12:02:36', 1, 2, 2),
(11, 'Sopas', 'Masabaw', 'prod_6850f0d2b41952.03284208.png', '2025-06-17 12:36:34', '2025-06-17 12:36:34', 1, 4, 2),
(12, 'Pizza', 'Creamy Pizza', 'prod_6850f1c8eddb47.39331295.jpg', '2025-06-17 12:40:40', '2025-06-17 12:40:40', 1, 1, 2),
(13, 'Ice Creams', 'Cold', 'prod_6850f2f3e164f2.47345723.jpg', '2025-06-17 12:45:39', '2025-06-17 23:20:06', 1, 3, 2),
(16, 'yuiop', 'sdvfsb', 'prod_685500e73e9f23.71753775.png', '2025-06-20 14:34:15', '2025-06-20 14:34:15', 1, 2, 2);

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
(4, 34.00, '2025-06-17', '2025-07-08');

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
  `Review_Date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`Review_ID`, `Product_ID`, `Customer_ID`, `Rating`, `Review_Text`, `Review_Date`) VALUES
(1, 5, 1, 5, 'hjgdytfcg', '2025-06-20 22:16:56'),
(2, 5, 1, 3, '', '2025-06-20 22:32:28'),
(3, 5, 1, 1, '', '2025-06-20 22:55:09'),
(4, 10, 1, 1, '', '2025-06-20 22:55:18'),
(5, 16, 1, 1, '', '2025-06-20 23:09:05');

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
(1, 63, 5, 3, 1050.00, '2025-06-20 20:08:06', 1),
(2, 63, 13, 4, 1050.00, '2025-06-20 20:08:06', 1),
(3, 65, 5, 3, 450.00, '2025-06-20 20:09:59', 1),
(4, 66, 10, 3, 450.00, '2025-06-20 21:25:46', 1),
(5, 67, 5, 3, 450.00, '2025-06-20 21:57:07', 1);

--
-- Indexes for dumped tables
--

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
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`Order_ID`),
  ADD KEY `Customer_ID` (`Customer_ID`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`Order_Item_ID`),
  ADD KEY `Order_ID` (`Order_ID`),
  ADD KEY `Product_ID` (`Product_ID`);

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
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `Category_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `Customer_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `Order_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `Order_Item_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `Payment_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `Product_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `product_price`
--
ALTER TABLE `product_price`
  MODIFY `Price_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `Review_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `Sale_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`Customer_ID`) REFERENCES `customer` (`Customer_ID`);

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`Order_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`);

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
