-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 05:16 PM
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
-- Database: `db_psm`
--
CREATE DATABASE IF NOT EXISTS `db_psm` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_psm`;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requisitions`
--

CREATE TABLE `purchase_requisitions` (
  `requisition_id` int(11) NOT NULL,
  `requisition_number` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `requested_by` varchar(150) NOT NULL,
  `item_description` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `estimated_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `priority` enum('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
  `status` enum('Pending Approval','Approved','Sourced','Rejected') NOT NULL DEFAULT 'Pending Approval',
  `justification` text DEFAULT NULL,
  `approved_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_requisitions`
--

INSERT INTO `purchase_requisitions` (`requisition_id`, `requisition_number`, `department`, `requested_by`, `item_description`, `quantity`, `estimated_cost`, `priority`, `status`, `justification`, `approved_by`, `created_at`) VALUES
(1, 'REQ-2026-001', 'Operations', 'Jun dela Cruz', 'Heavy Duty Pallet Storage Racks', 10, 15000.00, 'High', 'Pending Approval', 'Required for new inventory intake in Zone E', NULL, '2026-08-31 13:40:15'),
(2, 'REQ-2026-002', 'IT Support', 'Maria Santos', 'Handheld 2D Barcode Scanners', 15, 33000.00, 'Urgent', 'Approved', 'Replacements for damaged picking floor scanners', NULL, '2026-08-31 13:40:15'),
(3, 'REQ-2026-003', 'Human Resources', 'Sarah Jenkins', 'Ergonomic Task Workstation Chairs', 8, 27200.00, 'Normal', 'Sourced', 'Onboarding gear for candidate workstation batch', NULL, '2026-08-31 13:40:15');

-- --------------------------------------------------------

--
-- Table structure for table `rfqs`
--

CREATE TABLE `rfqs` (
  `rfq_id` int(11) NOT NULL,
  `rfq_number` varchar(50) NOT NULL,
  `requisition_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `budget_limit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deadline` date NOT NULL,
  `status` enum('Draft','Open','Closed','Awarded') NOT NULL DEFAULT 'Open',
  `specifications` text DEFAULT NULL,
  `created_by` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rfqs`
--

INSERT INTO `rfqs` (`rfq_id`, `rfq_number`, `requisition_id`, `title`, `category`, `budget_limit`, `deadline`, `status`, `specifications`, `created_by`, `created_at`) VALUES
(1, 'RFQ-2026-101', 2, 'Procurement of 15x Wireless Scanners', 'Electronics', 35000.00, '2026-09-15', 'Open', 'Must support QR code, Code128, and wireless USB dongle connectivity', 'Supply Chain Manager', '2026-08-31 13:40:16'),
(2, 'RFQ-2026-102', 3, 'Ergonomic Office Seating Batch', 'Furniture', 30000.00, '2026-09-10', 'Awarded', 'Adjustable lumbar support with breathable mesh back', 'Supply Chain Manager', '2026-08-31 13:40:16');

-- --------------------------------------------------------

--
-- Table structure for table `rfq_bids`
--

CREATE TABLE `rfq_bids` (
  `bid_id` int(11) NOT NULL,
  `rfq_id` int(11) NOT NULL,
  `vendor_name` varchar(255) NOT NULL,
  `quote_amount` decimal(12,2) NOT NULL,
  `lead_time_days` int(11) NOT NULL DEFAULT 7,
  `proposal_notes` text DEFAULT NULL,
  `is_selected` tinyint(1) NOT NULL DEFAULT 0,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rfq_bids`
--

INSERT INTO `rfq_bids` (`bid_id`, `rfq_id`, `vendor_name`, `quote_amount`, `lead_time_days`, `proposal_notes`, `is_selected`, `submitted_at`) VALUES
(1, 1, 'Metro Manila Tech Supplies', 32500.00, 5, 'Includes 1-year replacement warranty', 0, '2026-08-31 13:40:16'),
(2, 1, 'Global Hardware Express', 34200.00, 3, 'Fast dispatch within 72 hours', 0, '2026-08-31 13:40:16'),
(3, 2, 'Prime Ergonomics Phils', 26800.00, 7, 'Direct distributor pricing with free assembly', 1, '2026-08-31 13:40:16');

-- --------------------------------------------------------

--
-- Table structure for table `sourcing_projects`
--

CREATE TABLE `sourcing_projects` (
  `project_id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `target_supplier` varchar(255) DEFAULT NULL,
  `contract_type` enum('Fixed Price','Time & Materials','Recurring Retainer') NOT NULL DEFAULT 'Fixed Price',
  `target_completion` date NOT NULL,
  `status` enum('Planning','Evaluating Quotes','Contracting','Completed','Cancelled') NOT NULL DEFAULT 'Planning',
  `estimated_savings` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sourcing_projects`
--

INSERT INTO `sourcing_projects` (`project_id`, `project_name`, `category`, `target_supplier`, `contract_type`, `target_completion`, `status`, `estimated_savings`, `created_at`) VALUES
(1, 'Warehouse Packing Materials Bulk Contract', 'Consumables', 'Batangas Packaging Corp', 'Recurring Retainer', '2026-10-01', 'Evaluating Quotes', 12500.00, '2026-08-31 13:40:16'),
(2, 'Fleet Telemetry & Tracking Units', 'Hardware', 'NaviTrack Philippines', 'Fixed Price', '2026-09-20', 'Contracting', 8400.00, '2026-08-31 13:40:16');

-- --------------------------------------------------------

--
-- Table structure for table `spend_logs`
--

CREATE TABLE `spend_logs` (
  `spend_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `po_number` varchar(50) DEFAULT NULL,
  `vendor_name` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `spend_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spend_logs`
--

INSERT INTO `spend_logs` (`spend_id`, `category`, `department`, `po_number`, `vendor_name`, `amount`, `spend_date`, `created_at`) VALUES
(1, 'Warehouse Equipment', 'Operations', 'PO-2026-089', 'Batangas Hub Logistics', 180000.00, '2026-07-10', '2026-08-31 13:40:16'),
(2, 'Electronics', 'IT Support', 'PO-2026-090', 'Metro Manila Supply Co.', 17600.00, '2026-07-12', '2026-08-31 13:40:16'),
(3, 'Consumables', 'Logistics', 'PO-2026-091', 'Cebu Trading Partners', 8500.00, '2026-08-01', '2026-08-31 13:40:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `purchase_requisitions`
--
ALTER TABLE `purchase_requisitions`
  ADD PRIMARY KEY (`requisition_id`),
  ADD UNIQUE KEY `requisition_number` (`requisition_number`),
  ADD KEY `idx_req_no` (`requisition_number`),
  ADD KEY `idx_req_status` (`status`);

--
-- Indexes for table `rfqs`
--
ALTER TABLE `rfqs`
  ADD PRIMARY KEY (`rfq_id`),
  ADD UNIQUE KEY `rfq_number` (`rfq_number`),
  ADD KEY `idx_rfq_no` (`rfq_number`),
  ADD KEY `idx_rfq_status` (`status`),
  ADD KEY `fk_rfq_req` (`requisition_id`);

--
-- Indexes for table `rfq_bids`
--
ALTER TABLE `rfq_bids`
  ADD PRIMARY KEY (`bid_id`),
  ADD KEY `idx_bid_rfq` (`rfq_id`);

--
-- Indexes for table `sourcing_projects`
--
ALTER TABLE `sourcing_projects`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `idx_sourcing_status` (`status`);

--
-- Indexes for table `spend_logs`
--
ALTER TABLE `spend_logs`
  ADD PRIMARY KEY (`spend_id`),
  ADD KEY `idx_spend_cat` (`category`),
  ADD KEY `idx_spend_dept` (`department`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `purchase_requisitions`
--
ALTER TABLE `purchase_requisitions`
  MODIFY `requisition_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rfqs`
--
ALTER TABLE `rfqs`
  MODIFY `rfq_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rfq_bids`
--
ALTER TABLE `rfq_bids`
  MODIFY `bid_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sourcing_projects`
--
ALTER TABLE `sourcing_projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `spend_logs`
--
ALTER TABLE `spend_logs`
  MODIFY `spend_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rfqs`
--
ALTER TABLE `rfqs`
  ADD CONSTRAINT `fk_rfq_req` FOREIGN KEY (`requisition_id`) REFERENCES `purchase_requisitions` (`requisition_id`) ON DELETE SET NULL;

--
-- Constraints for table `rfq_bids`
--
ALTER TABLE `rfq_bids`
  ADD CONSTRAINT `fk_bid_rfq` FOREIGN KEY (`rfq_id`) REFERENCES `rfqs` (`rfq_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
