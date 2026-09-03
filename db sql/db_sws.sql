-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 08:42 AM
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
-- Database: `db_sws`
--
CREATE DATABASE IF NOT EXISTS `db_sws` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_sws`;

-- --------------------------------------------------------

--
-- Table structure for table `asset_assignments`
--

CREATE TABLE `asset_assignments` (
  `assignment_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `candidate_name` varchar(255) NOT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `assigned_date` date NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `return_condition` enum('Brand New','Good','Fair','Defective','Repaired') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_condition_history`
--

CREATE TABLE `asset_condition_history` (
  `history_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `old_condition` varchar(50) DEFAULT NULL,
  `new_condition` varchar(50) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cycle_counts`
--

CREATE TABLE `cycle_counts` (
  `count_id` int(11) NOT NULL,
  `zone_id` int(11) NOT NULL,
  `bin_id` int(11) DEFAULT NULL,
  `expected_quantity` int(11) NOT NULL,
  `counted_quantity` int(11) DEFAULT NULL,
  `variance` int(11) GENERATED ALWAYS AS (`counted_quantity` - `expected_quantity`) STORED,
  `counted_by` int(11) DEFAULT NULL,
  `count_status` enum('Scheduled','In Progress','Completed','Variance Found') NOT NULL DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `movement_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `source_location` varchar(100) DEFAULT NULL,
  `destination_location` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `movement_type` enum('Inbound','Outbound','Transfer') NOT NULL,
  `task_status` enum('Queued','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Queued',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tech_assets`
--

CREATE TABLE `tech_assets` (
  `asset_id` int(11) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `asset_type` varchar(100) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `condition_status` enum('Brand New','Good','Fair','Defective','Repaired') DEFAULT 'Brand New',
  `current_status` enum('In Storage','Deployed','In Transit','Maintenance','Retired') DEFAULT 'In Storage',
  `assigned_to` varchar(255) DEFAULT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `zone_location` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_bins`
--

CREATE TABLE `warehouse_bins` (
  `bin_id` int(11) NOT NULL,
  `zone_id` int(11) NOT NULL,
  `bin_code` varchar(50) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `used_units` int(11) NOT NULL DEFAULT 0,
  `status` enum('Empty','Occupied','Reserved','Blocked') NOT NULL DEFAULT 'Empty',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_zones`
--

CREATE TABLE `warehouse_zones` (
  `zone_id` int(11) NOT NULL,
  `zone_code` varchar(10) NOT NULL,
  `zone_name` varchar(100) DEFAULT NULL,
  `rack_code` varchar(50) DEFAULT NULL,
  `total_capacity` int(11) NOT NULL DEFAULT 0,
  `used_capacity` int(11) NOT NULL DEFAULT 0,
  `status` enum('Verified','Pending Audit','Requires Attention') NOT NULL DEFAULT 'Pending Audit',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouse_zones`
--

INSERT INTO `warehouse_zones` (`zone_id`, `zone_code`, `zone_name`, `rack_code`, `total_capacity`, `used_capacity`, `status`, `created_at`, `updated_at`) VALUES
(1, 'A', 'Zone A', 'RACK-A1', 1000, 0, 'Pending Audit', '2026-08-31 06:26:00', '2026-08-31 06:26:00'),
(2, 'B', 'Zone B', 'RACK-B1', 1000, 0, 'Pending Audit', '2026-08-31 06:26:00', '2026-08-31 06:26:00'),
(3, 'C', 'Zone C', 'RACK-C1', 1000, 0, 'Pending Audit', '2026-08-31 06:26:00', '2026-08-31 06:26:00'),
(4, 'D', 'Zone D', 'RACK-D1', 1000, 0, 'Pending Audit', '2026-08-31 06:26:00', '2026-08-31 06:26:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `asset_assignments`
--
ALTER TABLE `asset_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `idx_asset_id` (`asset_id`),
  ADD KEY `idx_candidate_name` (`candidate_name`);

--
-- Indexes for table `asset_condition_history`
--
ALTER TABLE `asset_condition_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_asset_id` (`asset_id`);

--
-- Indexes for table `cycle_counts`
--
ALTER TABLE `cycle_counts`
  ADD PRIMARY KEY (`count_id`),
  ADD KEY `bin_id` (`bin_id`),
  ADD KEY `idx_zone_id` (`zone_id`),
  ADD KEY `idx_count_status` (`count_status`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `idx_task_status` (`task_status`),
  ADD KEY `idx_movement_type` (`movement_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `tech_assets`
--
ALTER TABLE `tech_assets`
  ADD PRIMARY KEY (`asset_id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `asset_qr_code` (`qr_code`),
  ADD KEY `asset_status` (`current_status`),
  ADD KEY `asset_type` (`asset_type`);

--
-- Indexes for table `warehouse_bins`
--
ALTER TABLE `warehouse_bins`
  ADD PRIMARY KEY (`bin_id`),
  ADD UNIQUE KEY `uq_bin_code` (`bin_code`),
  ADD KEY `idx_zone_id` (`zone_id`),
  ADD KEY `idx_sku` (`sku`);

--
-- Indexes for table `warehouse_zones`
--
ALTER TABLE `warehouse_zones`
  ADD PRIMARY KEY (`zone_id`),
  ADD UNIQUE KEY `uq_zone_code` (`zone_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `asset_assignments`
--
ALTER TABLE `asset_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_condition_history`
--
ALTER TABLE `asset_condition_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cycle_counts`
--
ALTER TABLE `cycle_counts`
  MODIFY `count_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tech_assets`
--
ALTER TABLE `tech_assets`
  MODIFY `asset_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warehouse_bins`
--
ALTER TABLE `warehouse_bins`
  MODIFY `bin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warehouse_zones`
--
ALTER TABLE `warehouse_zones`
  MODIFY `zone_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `asset_assignments`
--
ALTER TABLE `asset_assignments`
  ADD CONSTRAINT `asset_assignments_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `tech_assets` (`asset_id`) ON DELETE CASCADE;

--
-- Constraints for table `asset_condition_history`
--
ALTER TABLE `asset_condition_history`
  ADD CONSTRAINT `asset_condition_history_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `tech_assets` (`asset_id`) ON DELETE CASCADE;

--
-- Constraints for table `cycle_counts`
--
ALTER TABLE `cycle_counts`
  ADD CONSTRAINT `cycle_counts_ibfk_1` FOREIGN KEY (`zone_id`) REFERENCES `warehouse_zones` (`zone_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cycle_counts_ibfk_2` FOREIGN KEY (`bin_id`) REFERENCES `warehouse_bins` (`bin_id`) ON DELETE SET NULL;

--
-- Constraints for table `warehouse_bins`
--
ALTER TABLE `warehouse_bins`
  ADD CONSTRAINT `warehouse_bins_ibfk_1` FOREIGN KEY (`zone_id`) REFERENCES `warehouse_zones` (`zone_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
