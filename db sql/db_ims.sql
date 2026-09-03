-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 05:17 PM
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
-- Database: `db_ims`
--
CREATE DATABASE IF NOT EXISTS `db_ims` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_ims`;

-- --------------------------------------------------------

--
-- Table structure for table `asset_dispositions`
--

CREATE TABLE `asset_dispositions` (
  `disposition_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `disposition_type` enum('Scrapped','Sold/Liquidated','Donated','Recycled','Written Off') NOT NULL,
  `quantity` int(11) NOT NULL,
  `salvage_value` decimal(12,2) DEFAULT 0.00,
  `reason` text NOT NULL,
  `disposed_by` varchar(100) NOT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Approved','Completed') NOT NULL DEFAULT 'Pending',
  `disposition_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_master`
--

CREATE TABLE `item_master` (
  `item_id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'pcs',
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reorder_level` int(11) NOT NULL DEFAULT 10,
  `safety_stock` int(11) NOT NULL DEFAULT 5,
  `status` enum('Active','Low Stock','Out of Stock','Discontinued') NOT NULL DEFAULT 'Active',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_master`
--

INSERT INTO `item_master` (`item_id`, `sku`, `item_name`, `category`, `unit`, `unit_cost`, `reorder_level`, `safety_stock`, `status`, `description`, `created_at`, `updated_at`) VALUES
(1, 'SYS-1001', 'Steel Pallet Rack Heavy Duty', 'Warehouse Equipment', 'pcs', 1500.00, 20, 10, 'Active', 'Standard modular heavy-duty steel pallet rack', '2026-08-31 13:00:38', '2026-08-31 13:00:38'),
(2, 'SYS-1042', 'Wireless Barcode Scanner Unit', 'Electronics', 'pcs', 2200.00, 10, 5, 'Low Stock', 'Handheld 2D/QR code wireless scanner', '2026-08-31 13:00:38', '2026-08-31 13:00:38'),
(3, 'SYS-1077', 'Heavy Duty Packing Tape 50m', 'Consumables', 'box', 85.00, 15, 5, 'Out of Stock', 'Standard packaging transparent tape box', '2026-08-31 13:00:38', '2026-08-31 13:00:38'),
(4, 'SYS-1093', 'Ergonomic Task Chair Standard', 'Furniture', 'pcs', 3400.00, 5, 2, 'Active', 'Office workstation adjustable ergonomic chair', '2026-08-31 13:00:38', '2026-08-31 13:00:38');

-- --------------------------------------------------------

--
-- Table structure for table `item_usage_logs`
--

CREATE TABLE `item_usage_logs` (
  `usage_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `department` varchar(100) NOT NULL,
  `quantity_used` int(11) NOT NULL,
  `issued_to` varchar(150) NOT NULL,
  `usage_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_usage_logs`
--

INSERT INTO `item_usage_logs` (`usage_id`, `item_id`, `department`, `quantity_used`, `issued_to`, `usage_date`, `created_at`) VALUES
(1, 2, 'Warehouse Operations', 4, 'John Cruz', '2026-08-25', '2026-08-31 13:00:39'),
(2, 4, 'Administration', 6, 'Jane Smith', '2026-08-27', '2026-08-31 13:00:39'),
(3, 1, 'Logistics Hub', 10, 'Mark Reyes', '2026-08-29', '2026-08-31 13:00:39');

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustments`
--

CREATE TABLE `stock_adjustments` (
  `adjustment_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `adjustment_type` enum('Addition','Deduction','Correction','Damage/Loss') NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `previous_qty` int(11) NOT NULL,
  `new_qty` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `adjusted_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_inventory`
--

CREATE TABLE `stock_inventory` (
  `stock_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_on_hand` int(11) NOT NULL DEFAULT 0,
  `quantity_reserved` int(11) NOT NULL DEFAULT 0,
  `warehouse_location` varchar(100) DEFAULT 'Main Warehouse',
  `last_restocked_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_inventory`
--

INSERT INTO `stock_inventory` (`stock_id`, `item_id`, `quantity_on_hand`, `quantity_reserved`, `warehouse_location`, `last_restocked_at`, `updated_at`) VALUES
(1, 1, 120, 15, 'Zone A - Rack 01', '2026-08-15 02:00:00', '2026-08-31 13:00:39'),
(2, 2, 8, 2, 'Zone B - Shelf 04', '2026-08-10 06:30:00', '2026-08-31 13:00:39'),
(3, 3, 0, 0, 'Zone C - Bin 12', '2026-07-20 01:15:00', '2026-08-31 13:00:39'),
(4, 4, 42, 5, 'Zone D - Section 02', '2026-08-28 03:20:00', '2026-08-31 13:00:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `asset_dispositions`
--
ALTER TABLE `asset_dispositions`
  ADD PRIMARY KEY (`disposition_id`),
  ADD KEY `idx_disp_item` (`item_id`);

--
-- Indexes for table `item_master`
--
ALTER TABLE `item_master`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_sku` (`sku`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `item_usage_logs`
--
ALTER TABLE `item_usage_logs`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `idx_usage_item` (`item_id`);

--
-- Indexes for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD PRIMARY KEY (`adjustment_id`),
  ADD KEY `idx_adj_item` (`item_id`);

--
-- Indexes for table `stock_inventory`
--
ALTER TABLE `stock_inventory`
  ADD PRIMARY KEY (`stock_id`),
  ADD UNIQUE KEY `uq_item_location` (`item_id`,`warehouse_location`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `asset_dispositions`
--
ALTER TABLE `asset_dispositions`
  MODIFY `disposition_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_master`
--
ALTER TABLE `item_master`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `item_usage_logs`
--
ALTER TABLE `item_usage_logs`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `adjustment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_inventory`
--
ALTER TABLE `stock_inventory`
  MODIFY `stock_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `asset_dispositions`
--
ALTER TABLE `asset_dispositions`
  ADD CONSTRAINT `fk_disp_item` FOREIGN KEY (`item_id`) REFERENCES `item_master` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `item_usage_logs`
--
ALTER TABLE `item_usage_logs`
  ADD CONSTRAINT `fk_usage_item` FOREIGN KEY (`item_id`) REFERENCES `item_master` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD CONSTRAINT `fk_adj_item` FOREIGN KEY (`item_id`) REFERENCES `item_master` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_inventory`
--
ALTER TABLE `stock_inventory`
  ADD CONSTRAINT `fk_stock_item` FOREIGN KEY (`item_id`) REFERENCES `item_master` (`item_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
