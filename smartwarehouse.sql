-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2026 at 06:21 PM
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
-- Database: `smartwarehouse`
--

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `position_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `full_name`, `position_name`, `phone`, `email`) VALUES
(1, 'Somchai Prasert', 'Inbound Checking', '0812457631', 'somchai.rfid@example.com'),
(2, 'Orathai Suksan', 'RFID Scanning / Moving', '0829184402', 'orathai.rfid@example.com'),
(3, 'Pitchit Anurak', 'Shelf Capacity Review', '0893341208', 'pitchit.rfid@example.com'),
(4, 'Ratchanee Boonsri', 'Outbound Approval', '0867715534', 'ratchanee.rfid@example.com');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `reorder_point` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `sku`, `product_name`, `reorder_point`, `price`) VALUES
(12, 'RING-01', 'แหวน', 100, 100.00),
(13, 'DISH-01', 'จาน', 10, 99.00),
(14, 'spoon-001', 'ช้อน', 20, 99.00);

-- --------------------------------------------------------

--
-- Table structure for table `product_stock`
--

CREATE TABLE `product_stock` (
  `stock_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `current_qty` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_stock`
--

INSERT INTO `product_stock` (`stock_id`, `product_id`, `current_qty`) VALUES
(12, 12, 200),
(13, 13, 200),
(14, 14, 20);

-- --------------------------------------------------------

--
-- Table structure for table `rfid_tags`
--

CREATE TABLE `rfid_tags` (
  `rfid_id` int(11) NOT NULL,
  `rfid_code` varchar(100) NOT NULL,
  `product_id` int(11) NOT NULL,
  `shelf_id` int(11) DEFAULT NULL,
  `STATUS` enum('In-Stock','Moving','Shipped') NOT NULL DEFAULT 'In-Stock'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rfid_tags`
--

INSERT INTO `rfid_tags` (`rfid_id`, `rfid_code`, `product_id`, `shelf_id`, `STATUS`) VALUES
(7, 'RING-01', 12, 11, 'Moving'),
(8, 'DISH-01', 13, 12, 'In-Stock'),
(9, '0010093268', 14, 11, 'Moving');

-- --------------------------------------------------------

--
-- Table structure for table `shelves`
--

CREATE TABLE `shelves` (
  `shelf_id` int(11) NOT NULL,
  `zone_id` int(11) NOT NULL,
  `shelf_code` varchar(50) NOT NULL,
  `shelf_capacity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shelves`
--

INSERT INTO `shelves` (`shelf_id`, `zone_id`, `shelf_code`, `shelf_capacity`) VALUES
(11, 1, 'A-1', 250),
(12, 2, 'B-1', 250);

-- --------------------------------------------------------

--
-- Table structure for table `stock_logs`
--

CREATE TABLE `stock_logs` (
  `stock_log_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `qty_before` int(11) NOT NULL,
  `qty_change` int(11) NOT NULL,
  `qty_after` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_logs`
--

INSERT INTO `stock_logs` (`stock_log_id`, `product_id`, `transaction_id`, `qty_before`, `qty_change`, `qty_after`, `created_at`) VALUES
(3, 12, 5, 250, 20, 270, '2026-04-11 17:25:49'),
(4, 12, 6, 270, -70, 200, '2026-04-11 17:34:31'),
(5, 14, 7, 200, -180, 20, '2026-04-21 17:21:37');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

CREATE TABLE `stock_transactions` (
  `transaction_id` int(11) NOT NULL,
  `transaction_type` enum('IN','OUT') NOT NULL,
  `document_no` varchar(100) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rfid_id` int(11) NOT NULL,
  `shelf_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `transaction_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `employee_id` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_transactions`
--

INSERT INTO `stock_transactions` (`transaction_id`, `transaction_type`, `document_no`, `product_id`, `rfid_id`, `shelf_id`, `quantity`, `transaction_datetime`, `employee_id`, `note`) VALUES
(5, 'IN', 'P0-1', 12, 7, 11, 20, '2026-04-12 00:25:49', 2, ''),
(6, 'OUT', 'Sell-01', 12, 7, 11, 70, '2026-04-12 00:34:31', 3, ''),
(7, 'OUT', 'ISSUE-20260422-0021-348', 14, 9, 11, 180, '2026-04-22 00:21:37', 2, '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_name` varchar(50) DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role_name`, `created_at`) VALUES
(1, 'Test', 'test@gmail.com', '$2y$10$ClYEQ6ez4rUqEaV6w9Y/ZO8.ziXnXsVqvnHrnez8JbyxDZZsh3MWK', 'staff', '2026-04-11 15:07:48');

-- --------------------------------------------------------

--
-- Table structure for table `zones`
--

CREATE TABLE `zones` (
  `zone_id` int(11) NOT NULL,
  `zone_code` varchar(20) NOT NULL,
  `zone_name` varchar(100) NOT NULL,
  `total_capacity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zones`
--

INSERT INTO `zones` (`zone_id`, `zone_code`, `zone_name`, `total_capacity`) VALUES
(1, 'A', 'Zone A', 500),
(2, 'B', 'Zone B', 500);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `product_stock`
--
ALTER TABLE `product_stock`
  ADD PRIMARY KEY (`stock_id`),
  ADD UNIQUE KEY `product_id` (`product_id`);

--
-- Indexes for table `rfid_tags`
--
ALTER TABLE `rfid_tags`
  ADD PRIMARY KEY (`rfid_id`),
  ADD UNIQUE KEY `rfid_code` (`rfid_code`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_rfid_tags_shelf` (`shelf_id`);

--
-- Indexes for table `shelves`
--
ALTER TABLE `shelves`
  ADD PRIMARY KEY (`shelf_id`),
  ADD UNIQUE KEY `zone_id` (`zone_id`,`shelf_code`);

--
-- Indexes for table `stock_logs`
--
ALTER TABLE `stock_logs`
  ADD PRIMARY KEY (`stock_log_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `rfid_id` (`rfid_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `fk_stock_transactions_shelf` (`shelf_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `zones`
--
ALTER TABLE `zones`
  ADD PRIMARY KEY (`zone_id`),
  ADD UNIQUE KEY `zone_code` (`zone_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `product_stock`
--
ALTER TABLE `product_stock`
  MODIFY `stock_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `rfid_tags`
--
ALTER TABLE `rfid_tags`
  MODIFY `rfid_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `shelves`
--
ALTER TABLE `shelves`
  MODIFY `shelf_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `stock_logs`
--
ALTER TABLE `stock_logs`
  MODIFY `stock_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `zones`
--
ALTER TABLE `zones`
  MODIFY `zone_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `product_stock`
--
ALTER TABLE `product_stock`
  ADD CONSTRAINT `product_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `rfid_tags`
--
ALTER TABLE `rfid_tags`
  ADD CONSTRAINT `fk_rfid_tags_shelf` FOREIGN KEY (`shelf_id`) REFERENCES `shelves` (`shelf_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rfid_tags_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `shelves`
--
ALTER TABLE `shelves`
  ADD CONSTRAINT `shelves_ibfk_1` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`zone_id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_logs`
--
ALTER TABLE `stock_logs`
  ADD CONSTRAINT `stock_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_logs_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `stock_transactions` (`transaction_id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `fk_stock_transactions_shelf` FOREIGN KEY (`shelf_id`) REFERENCES `shelves` (`shelf_id`),
  ADD CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`rfid_id`) REFERENCES `rfid_tags` (`rfid_id`),
  ADD CONSTRAINT `stock_transactions_ibfk_4` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
