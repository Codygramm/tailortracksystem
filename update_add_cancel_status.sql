-- SQL Migration: Add 'cancel' status to orders table
-- Date: 2025-12-18
-- Description: Updates the status enum to include 'cancel' option for cashiers

-- Use the tailortrackdb database
USE `tailortrackdb`;

-- Step 1: Modify the orders table to add 'cancel' to the status enum
ALTER TABLE `orders`
MODIFY COLUMN `status` ENUM('pending','assigned','in_progress','completed','paid','cancel') DEFAULT 'pending';

-- Step 2: Verify the changes
DESCRIBE `orders`;

-- Optional: Check if there are any existing orders
SELECT order_id, customer_name, status FROM `orders`;
