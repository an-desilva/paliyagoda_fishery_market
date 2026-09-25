-- Peliyagoda Central Fish Market ERP & POS System
-- Complete Production Database Schema with Settings, Loans, FK Constraints, and Seed Data

CREATE DATABASE IF NOT EXISTS `peliyagoda_fish_market` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `peliyagoda_fish_market`;

-- Disable FK checks for clean reset
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `supplier_loans`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `settlements`;
DROP TABLE IF EXISTS `credit_payments`;
DROP TABLE IF EXISTS `invoice_items`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `fish_items`;
DROP TABLE IF EXISTS `consignments`;
DROP TABLE IF EXISTS `buyers`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. System Settings Table (Dynamic Commission %, Default Tariffs, Helper Batta Baselines)
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Staff Users Table (RBAC: admin, cashier, offloader)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'cashier', 'offloader') NOT NULL DEFAULT 'cashier',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Suppliers Table (Boat Owners)
CREATE TABLE `suppliers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `supplier_code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `harbor` VARCHAR(50) NOT NULL,
  `boat_name` VARCHAR(100) DEFAULT NULL,
  `current_loan_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Supplier Loans Table (Advance Loan Disbursements & EOD Recoveries)
CREATE TABLE `supplier_loans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `supplier_id` INT NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `type` ENUM('disbursement', 'repayment') NOT NULL DEFAULT 'disbursement',
  `notes` VARCHAR(255) DEFAULT NULL,
  `issued_by` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_loan_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_loan_user` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Buyers Table (Wholesale Merchants with Defaulter Freeze Status)
CREATE TABLE `buyers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `buyer_code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `credit_limit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `current_credit_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Consignments Table (Inward Lorries & Offloading)
CREATE TABLE `consignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `consignment_no` VARCHAR(30) NOT NULL UNIQUE,
  `supplier_id` INT NOT NULL,
  `lorry_number` VARCHAR(20) NOT NULL,
  `harbor_origin` VARCHAR(50) NOT NULL,
  `crate_count` INT NOT NULL DEFAULT 0,
  `freight_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `helper_batta` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `coolie_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  `status` ENUM('unloaded', 'graded', 'auctioning', 'settled') NOT NULL DEFAULT 'unloaded',
  `arrival_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_consignment_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Fish Items Table (Graded & Tagged Bill Fish)
CREATE TABLE `fish_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tag_code` VARCHAR(40) NOT NULL UNIQUE,
  `consignment_id` INT NOT NULL,
  `species` VARCHAR(50) NOT NULL,
  `gross_weight` DECIMAL(8,2) NOT NULL,
  `tare_weight` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `net_weight` DECIMAL(8,2) NOT NULL,
  `grade` ENUM('A_export', 'B_local', 'C_canning') NOT NULL DEFAULT 'B_local',
  `status` ENUM('available', 'sold', 'settled') NOT NULL DEFAULT 'available',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fish_consignment` FOREIGN KEY (`consignment_id`) REFERENCES `consignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Invoices Table (POS Auction Sales)
CREATE TABLE `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_no` VARCHAR(30) NOT NULL UNIQUE,
  `buyer_id` INT NOT NULL,
  `payment_mode` ENUM('cash', 'credit') NOT NULL DEFAULT 'cash',
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `handling_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cutting_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `security_hash` VARCHAR(64) NOT NULL,
  `issued_by` INT NOT NULL,
  `status` ENUM('paid', 'pending', 'cancelled') NOT NULL DEFAULT 'paid',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_invoice_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`),
  CONSTRAINT `fk_invoice_user` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Invoice Items Table
CREATE TABLE `invoice_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL,
  `fish_item_id` INT NOT NULL,
  `weight_kg` DECIMAL(8,2) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `line_total` DECIMAL(12,2) NOT NULL,
  CONSTRAINT `fk_item_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_fish` FOREIGN KEY (`fish_item_id`) REFERENCES `fish_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Credit Payments Table (Buyer Credit Recoveries)
CREATE TABLE `credit_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `buyer_id` INT NOT NULL,
  `invoice_id` INT DEFAULT NULL,
  `amount_paid` DECIMAL(12,2) NOT NULL,
  `payment_method` VARCHAR(30) NOT NULL DEFAULT 'Cash',
  `notes` VARCHAR(255) DEFAULT NULL,
  `received_by` INT NOT NULL,
  `payment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_credit_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`),
  CONSTRAINT `fk_credit_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Settlements Table (Mudiyala End-of-Day Payout to Supplier)
CREATE TABLE `settlements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `settlement_no` VARCHAR(30) NOT NULL UNIQUE,
  `supplier_id` INT NOT NULL,
  `consignment_id` INT NOT NULL,
  `gross_revenue` DECIMAL(12,2) NOT NULL,
  `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 6.00,
  `commission_amount` DECIMAL(12,2) NOT NULL,
  `freight_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `helper_batta_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `coolie_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `loan_deduction` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `net_payable` DECIMAL(12,2) NOT NULL,
  `settlement_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_settlement_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `fk_settlement_consignment` FOREIGN KEY (`consignment_id`) REFERENCES `consignments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance Indexes
CREATE INDEX `idx_tag_code` ON `fish_items` (`tag_code`);
CREATE INDEX `idx_fish_status` ON `fish_items` (`status`);
CREATE INDEX `idx_consignment_status` ON `consignments` (`status`);
CREATE INDEX `idx_buyer_code` ON `buyers` (`buyer_code`);
CREATE INDEX `idx_supplier_code` ON `suppliers` (`supplier_code`);

-- Seed Data Insertion
-- Initial Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('default_commission_rate', '6.00', 'Standard Mudiyala commission rate percentage'),
('default_handling_fee', '250.00', 'Default floor handling fee per POS transaction (LKR)'),
('default_helper_batta', '3500.00', 'Default baseline Helper Batta allowance (LKR)'),
('default_coolie_charge', '2400.00', 'Default floor coolie unloading fee (LKR)');

-- Staff Users (Password: admin123)
INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$4y9pB/o0dO7V6Y.F6tU4n.4dY6gX0tU8K4a9N7b2v.8S7k4j2d.0O', 'Sunil Mudiyanse (Main Mudiyala)', 'admin', 'active'),
(2, 'cashier1', '$2y$10$4y9pB/o0dO7V6Y.F6tU4n.4dY6gX0tU8K4a9N7b2v.8S7k4j2d.0O', 'Kusal Perera (POS Cashier)', 'cashier', 'active'),
(3, 'offloader1', '$2y$10$4y9pB/o0dO7V6Y.F6tU4n.4dY6gX0tU8K4a9N7b2v.8S7k4j2d.0O', 'Nimal Karunaratne (Scales Inspector)', 'offloader', 'active');

-- Boat Owners (Suppliers)
INSERT INTO `suppliers` (`id`, `supplier_code`, `name`, `phone`, `harbor`, `boat_name`, `current_loan_balance`) VALUES
(1, 'SUP-101', 'Kamal Deepani Fisheries', '0771234567', 'Beruwala', 'Deepani-04', 25000.00),
(2, 'SUP-102', 'Dushan Fernadopulle', '0719876543', 'Trincomalee', 'Sayura Enterprise', 0.00),
(3, 'SUP-103', 'Mahinda Mirissa Trawlers', '0785554433', 'Mirissa', 'Ocean Queen-02', 45000.00),
(4, 'SUP-104', 'Anthony Negombo Multi-Day', '0763332211', 'Negombo', 'St. Anthony-07', 12000.00);

-- Supplier Loans
INSERT INTO `supplier_loans` (`id`, `supplier_id`, `amount`, `type`, `notes`, `issued_by`) VALUES
(1, 1, 25000.00, 'disbursement', 'Pre-season fuel and ice loan advance', 1),
(2, 3, 45000.00, 'disbursement', 'Engine repair cash advance', 1),
(3, 4, 12000.00, 'disbursement', 'Bait purchase advance', 1);

-- Wholesale Buyers (Merchants)
INSERT INTO `buyers` (`id`, `buyer_code`, `name`, `phone`, `credit_limit`, `current_credit_balance`, `status`) VALUES
(1, 'BUY-201', 'Lanka Supermarket Line', '0772221100', 500000.00, 125000.00, 'active'),
(2, 'BUY-202', 'Colombo Fresh Sea Food Ltd', '0714443322', 250000.00, 45000.00, 'active'),
(3, 'BUY-203', 'Kandy Wholesale Stall #14', '0758889900', 100000.00, 95000.00, 'active'),
(4, 'BUY-204', 'Galle Face Hotel Procurement', '0761112233', 300000.00, 0.00, 'active'),
(5, 'BUY-205', 'Silva Retail Traders', '0709998877', 50000.00, 52000.00, 'blocked');

-- Consignments
INSERT INTO `consignments` (`id`, `consignment_no`, `supplier_id`, `lorry_number`, `harbor_origin`, `crate_count`, `freight_cost`, `helper_batta`, `coolie_charges`, `notes`, `status`, `arrival_date`) VALUES
(1, 'CNS-2026-0001', 1, 'WP LE-4592', 'Beruwala', 24, 18000.00, 3500.00, 2400.00, 'High grade Yellowfin shipment', 'auctioning', NOW()),
(2, 'CNS-2026-0002', 2, 'EP GA-8821', 'Trincomalee', 30, 32000.00, 4500.00, 3000.00, 'Large Swordfish and Marlin consignment', 'graded', NOW());

-- Fish Items
INSERT INTO `fish_items` (`id`, `tag_code`, `consignment_id`, `species`, `gross_weight`, `tare_weight`, `net_weight`, `grade`, `status`) VALUES
(1, 'TAG-2026-0001', 1, 'Yellowfin Tuna (කෙලවල්ලා)', 48.50, 2.50, 46.00, 'A_export', 'available'),
(2, 'TAG-2026-0002', 1, 'Yellowfin Tuna (කෙලවල්ලා)', 52.00, 3.00, 49.00, 'A_export', 'available'),
(3, 'TAG-2026-0003', 1, 'Sailfish (මලින්)', 38.00, 2.00, 36.00, 'B_local', 'available'),
(4, 'TAG-2026-0004', 1, 'Swordfish (තලපත්)', 64.00, 4.00, 60.00, 'B_local', 'available'),
(5, 'TAG-2026-0005', 2, 'Swordfish (තලපත්)', 72.00, 5.00, 67.00, 'A_export', 'available'),
(6, 'TAG-2026-0006', 2, 'Marlin (කොප්පරා)', 55.00, 3.50, 51.50, 'C_canning', 'available');
