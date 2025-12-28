-- SQL Migration: Add deposit functionality and receipts table
-- Date: 2025-12-18
-- Description: Adds deposit amount field to orders table and creates receipts table

USE `tailortrackdb`;

-- Step 1: Add deposit_amount field to orders table
ALTER TABLE `orders`
ADD COLUMN `deposit_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `total_amount`,
ADD COLUMN `deposit_paid` ENUM('no','yes') DEFAULT 'no' AFTER `deposit_amount`,
ADD COLUMN `deposit_paid_at` TIMESTAMP NULL DEFAULT NULL AFTER `deposit_paid`;

-- Step 2: Create receipts table
CREATE TABLE IF NOT EXISTS `receipts` (
  `receipt_id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` VARCHAR(20) NOT NULL,
  `receipt_number` VARCHAR(30) NOT NULL,
  `receipt_type` ENUM('deposit','full_payment') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('cash','card','online_transfer') DEFAULT 'cash',
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`receipt_id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `idx_receipts_order_id` (`order_id`),
  KEY `idx_receipts_created_by` (`created_by`),
  CONSTRAINT `receipts_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `receipts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Step 3: Verify the changes
DESCRIBE `orders`;
DESCRIBE `receipts`;

-- Optional: Check existing orders
SELECT order_id, customer_name, total_amount, deposit_amount, deposit_paid FROM `orders`;
