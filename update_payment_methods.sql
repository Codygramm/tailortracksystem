-- Update payment methods and add receipt file upload capability
-- Run this SQL in your phpMyAdmin or MySQL console

-- Step 1: Modify payment_method enum in receipts table to include new methods
ALTER TABLE `receipts`
MODIFY COLUMN `payment_method` ENUM('cash','online_payment','qr_code') DEFAULT 'cash';

-- Step 2: Add receipt_file column to store uploaded receipt proof
ALTER TABLE `receipts`
ADD COLUMN `receipt_file` VARCHAR(255) NULL AFTER `payment_method`;

-- Step 3: Update orders table payment_method if needed
-- Check if payment_method exists in orders table, if yes, update it
-- (This is optional - only if you track payment method in orders table too)
