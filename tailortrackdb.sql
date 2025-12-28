-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 23, 2025 at 01:16 PM
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
-- Database: `tailortrackdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `lower_body_measurements`
--

CREATE TABLE `lower_body_measurements` (
  `lower_id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `waist` decimal(5,2) DEFAULT NULL,
  `hip` decimal(5,2) DEFAULT NULL,
  `bottom_length` decimal(5,2) DEFAULT NULL,
  `inseam` decimal(5,2) DEFAULT NULL,
  `outseam` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lower_body_measurements`
--

INSERT INTO `lower_body_measurements` (`lower_id`, `order_id`, `waist`, `hip`, `bottom_length`, `inseam`, `outseam`) VALUES
(13, 'TT-20251223-070', 10.00, 10.00, 10.00, 10.00, 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` varchar(20) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `order_type` enum('set_baju_melayu','set_baju_kurung','set_baju_kebaya','baju_kurta','repair') NOT NULL,
  `repair_type` enum('upper','lower','both') DEFAULT NULL,
  `status` enum('pending','assigned','in_progress','completed','paid','cancel') DEFAULT 'pending',
  `payment_status` enum('pending','paid') DEFAULT 'pending',
  `assigned_tailor` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `deposit_amount` decimal(10,2) DEFAULT 0.00,
  `deposit_paid` enum('no','yes') DEFAULT 'no',
  `deposit_paid_at` timestamp NULL DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `tailor_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_name`, `customer_phone`, `customer_email`, `order_type`, `repair_type`, `status`, `payment_status`, `assigned_tailor`, `created_by`, `created_at`, `updated_at`, `total_amount`, `deposit_amount`, `deposit_paid`, `deposit_paid_at`, `amount_paid`, `tailor_notes`) VALUES
('TT-20251222-962', 'badrul', '0142712568', 'haikalsamsi10@gmail.com', 'repair', 'upper', 'completed', 'pending', 10, 2, '2025-12-22 15:31:57', '2025-12-22 17:10:04', 25.00, 10.00, 'yes', '2025-12-22 15:32:00', 0.00, ''),
('TT-20251223-070', 'badrul', '0142712568', 'haikalsamsi10@gmail.com', 'set_baju_melayu', NULL, 'in_progress', 'pending', 12, 2, '2025-12-23 12:02:36', '2025-12-23 12:06:41', 150.00, 50.00, 'yes', '2025-12-23 12:02:44', 0.00, ''),
('TT-20251223-313', 'badrul', '0138763076', 'zul@gmail.com', 'repair', 'upper', 'assigned', 'pending', 11, 2, '2025-12-23 05:49:39', '2025-12-23 05:50:50', 25.00, 10.00, 'yes', '2025-12-23 05:49:42', 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `receipt_id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `receipt_number` varchar(30) NOT NULL,
  `receipt_type` enum('deposit','full_payment') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','online_transfer') DEFAULT 'cash',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipts`
--

INSERT INTO `receipts` (`receipt_id`, `order_id`, `receipt_number`, `receipt_type`, `amount`, `payment_method`, `created_by`, `created_at`) VALUES
(28, 'TT-20251222-962', 'RCP-20251222-9605', 'deposit', 10.00, 'cash', 2, '2025-12-22 15:32:00'),
(29, 'TT-20251223-313', 'RCP-20251223-5214', 'deposit', 10.00, 'cash', 2, '2025-12-23 05:49:42'),
(30, 'TT-20251223-070', 'RCP-20251223-1140', 'deposit', 50.00, 'cash', 2, '2025-12-23 12:02:44');

-- --------------------------------------------------------

--
-- Table structure for table `upper_body_measurements`
--

CREATE TABLE `upper_body_measurements` (
  `upper_id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `shoulder` decimal(5,2) DEFAULT NULL,
  `chest` decimal(5,2) DEFAULT NULL,
  `waist` decimal(5,2) DEFAULT NULL,
  `sleeve_length` decimal(5,2) DEFAULT NULL,
  `armhole` decimal(5,2) DEFAULT NULL,
  `wrist` decimal(5,2) DEFAULT NULL,
  `neck` decimal(5,2) DEFAULT NULL,
  `top_length` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `upper_body_measurements`
--

INSERT INTO `upper_body_measurements` (`upper_id`, `order_id`, `shoulder`, `chest`, `waist`, `sleeve_length`, `armhole`, `wrist`, `neck`, `top_length`) VALUES
(35, 'TT-20251222-962', 0.10, 1.00, 1.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(36, 'TT-20251223-313', 1.00, 1.00, 1.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(37, 'TT-20251223-070', 10.00, 10.00, 10.00, 10.00, 10.00, 10.00, 10.00, 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier','tailor') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `full_name`, `email`, `phone`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin User', 'admin@tailortrack.com', '012-345 6789', '2025-11-12 01:57:33'),
(2, 'cashier1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier', 'Ahmad Bin Ismail', 'ahmad@tailortrack.com', '012-345 6790', '2025-11-12 01:57:33'),
(3, 'cashier2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier', 'Siti Nurhaliza', 'siti@tailortrack.com', '012-345 6791', '2025-11-12 01:57:33'),
(10, 'tailor2', '$2y$10$WRUE9f.m4pWBZqsv3ytoDeYJ4nJ5T6Vsj987vBsibsyEsJyLdi.gu', 'tailor', 'Mariam Binti Abdullah', 'tailor@gmail.com', '0123456789', '2025-12-22 15:30:29'),
(11, 'tailor1', '$2y$10$4iF4GHyzIPDHq5pXo7ppVO3QkRs2x1NQ0jZgkIC.6cmib.EAgivY.', 'tailor', 'Ahmad Bin Ismail', 'tailor@gmail.com', '0123456789', '2025-12-22 15:33:14'),
(12, 'tailor3', '$2y$10$hMencQhIiAg3NPs7gWUDTeuhRmac9jgsI1ikyH5PbRjJ3m2e8REaG', 'tailor', 'Samsiah Binti Abdullah', 'tailor@gmail.com', '0123456781', '2025-12-22 15:49:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `lower_body_measurements`
--
ALTER TABLE `lower_body_measurements`
  ADD PRIMARY KEY (`lower_id`),
  ADD KEY `idx_lower_body_order_id` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_assigned_tailor` (`assigned_tailor`),
  ADD KEY `idx_orders_created_by` (`created_by`),
  ADD KEY `idx_orders_created_at` (`created_at`);

--
-- Indexes for table `receipts`
--
ALTER TABLE `receipts`
  ADD PRIMARY KEY (`receipt_id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `idx_receipts_order_id` (`order_id`),
  ADD KEY `idx_receipts_created_by` (`created_by`);

--
-- Indexes for table `upper_body_measurements`
--
ALTER TABLE `upper_body_measurements`
  ADD PRIMARY KEY (`upper_id`),
  ADD KEY `idx_upper_body_order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `lower_body_measurements`
--
ALTER TABLE `lower_body_measurements`
  MODIFY `lower_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `upper_body_measurements`
--
ALTER TABLE `upper_body_measurements`
  MODIFY `upper_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lower_body_measurements`
--
ALTER TABLE `lower_body_measurements`
  ADD CONSTRAINT `lower_body_measurements_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`assigned_tailor`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `receipts`
--
ALTER TABLE `receipts`
  ADD CONSTRAINT `receipts_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receipts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `upper_body_measurements`
--
ALTER TABLE `upper_body_measurements`
  ADD CONSTRAINT `upper_body_measurements_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
