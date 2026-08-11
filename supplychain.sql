-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2026 at 05:26 PM
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
-- Database: `supplychain`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `status_class` varchar(50) NOT NULL DEFAULT 'status-pill-info',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`log_id`, `label`, `status`, `status_class`, `created_at`) VALUES
(1, 'New stock allocation incoming from Batangas Hub', 'In Transit', 'status-pill-info', '2026-07-14 10:48:09'),
(2, 'Smart Warehousing visual audit batch completed', 'Verified', 'status-pill-success', '2026-07-14 10:48:09'),
(3, 'Purchase Order PO-2026-089 approved by Procurement', 'Approved', 'status-pill-success', '2026-07-14 10:48:09');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `item_id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(20) DEFAULT 'pcs',
  `reorder_level` int(11) NOT NULL DEFAULT 10,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `status` enum('Active','Low Stock','Out of Stock','Archived') NOT NULL DEFAULT 'Active',
  `warehouse_zone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`item_id`, `sku`, `item_name`, `category`, `supplier_id`, `quantity`, `unit`, `reorder_level`, `unit_price`, `status`, `warehouse_zone`, `created_at`, `updated_at`) VALUES
(1, 'SYS-1001', 'Steel Pallet Rack', 'Warehouse Equipment', 1, 120, 'pcs', 20, 1500.00, 'Active', 'Zone A', '2026-07-14 10:48:09', '2026-07-14 10:48:09'),
(2, 'SYS-1042', 'Barcode Scanner Unit', 'Electronics', 2, 8, 'pcs', 10, 2200.00, 'Low Stock', 'Zone B', '2026-07-14 10:48:09', '2026-07-14 10:48:09'),
(3, 'SYS-1077', 'Packing Tape Roll', 'Consumables', 2, 0, 'box', 15, 85.00, 'Out of Stock', 'Zone C', '2026-07-14 10:48:09', '2026-07-14 10:48:09'),
(4, 'SYS-1093', 'Legacy Telemetry Collector', 'Analytics', 3, 0, 'pcs', 5, 3400.00, 'Archived', 'Zone D', '2026-07-14 10:48:09', '2026-07-14 10:48:09');

-- --------------------------------------------------------

--
-- Table structure for table `logistics_manifests`
--

CREATE TABLE `logistics_manifests` (
  `manifest_id` int(11) NOT NULL,
  `manifest_number` varchar(30) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `carrier_name` varchar(150) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `dispatch_date` date DEFAULT NULL,
  `estimated_delivery` date DEFAULT NULL,
  `delivery_status` enum('Dispatched','In Transit','Out for Delivery','Delivered','Delayed') NOT NULL DEFAULT 'Dispatched',
  `document_url` varchar(255) DEFAULT 'manifest_copy.pdf',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logistics_manifests`
--

INSERT INTO `logistics_manifests` (`manifest_id`, `manifest_number`, `order_id`, `carrier_name`, `tracking_number`, `dispatch_date`, `estimated_delivery`, `delivery_status`, `document_url`, `created_at`) VALUES
(1, 'MNF-2026-021', 1, 'Batangas Hub Logistics', 'TRK-778210', '2026-07-12', '2026-07-20', 'In Transit', 'manifest_copy.pdf', '2026-07-14 10:48:09'),
(2, 'MNF-2026-022', 2, 'Metro Manila Supply Co.', 'MMS-556231', '2026-07-12', '2026-07-22', 'Dispatched', 'manifest_copy.pdf', '2026-07-14 10:48:09');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `order_date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `status` enum('Draft','Pending','Approved','In Transit','Delivered','Cancelled') NOT NULL DEFAULT 'Draft',
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_number`, `supplier_id`, `order_date`, `expected_date`, `status`, `total_amount`, `created_by`, `created_at`) VALUES
(1, 'PO-2026-089', 1, '2026-07-10', '2026-07-20', 'Approved', 180000.00, NULL, '2026-07-14 10:48:09'),
(2, 'PO-2026-090', 2, '2026-07-12', '2026-07-22', 'Pending', 17600.00, NULL, '2026-07-14 10:48:09');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requisitions`
--

CREATE TABLE `purchase_requisitions` (
  `requisition_id` int(11) NOT NULL,
  `requisition_number` varchar(30) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `estimated_cost` decimal(12,2) DEFAULT 0.00,
  `requested_by` varchar(100) DEFAULT NULL,
  `status` enum('Draft','Pending Approval','Sourced','Rejected') NOT NULL DEFAULT 'Pending Approval',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_requisitions`
--

INSERT INTO `purchase_requisitions` (`requisition_id`, `requisition_number`, `item_id`, `quantity`, `estimated_cost`, `requested_by`, `status`, `created_at`) VALUES
(1, 'REQ-2026-014', 2, 20, 44000.00, 'Jun dela Cruz', 'Pending Approval', '2026-07-14 10:48:09'),
(2, 'REQ-2026-015', 3, 50, 4250.00, 'Maria Santos', 'Sourced', '2026-07-14 10:48:09');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remember_tokens`
--

INSERT INTO `remember_tokens` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(10, 7, '92be127457411c25c22518d160a5f684af574bec8b8bd8625e96e91550245fc3', '2026-08-26 14:00:23', '2026-07-27 12:00:23');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movement_logs`
--

CREATE TABLE `stock_movement_logs` (
  `movement_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `from_zone` varchar(100) DEFAULT NULL,
  `to_zone` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `movement_type` enum('Transfer','Inbound','Outbound') NOT NULL DEFAULT 'Transfer',
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_movement_logs`
--

INSERT INTO `stock_movement_logs` (`movement_id`, `item_id`, `from_zone`, `to_zone`, `quantity`, `movement_type`, `logged_at`) VALUES
(1, 1, NULL, 'Zone A', 120, 'Inbound', '2026-07-14 10:48:09'),
(2, 2, 'Zone B', NULL, 4, 'Outbound', '2026-07-14 10:48:09'),
(3, 3, 'Zone C', 'Zone A', 15, 'Transfer', '2026-07-14 10:48:09');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `email`, `phone`, `address`, `rating`, `is_active`, `created_at`) VALUES
(1, 'Batangas Hub Logistics', 'Maria Santos', 'maria@batangashub.ph', '0917-123-4567', 'Batangas City, PH', 4.6, 1, '2026-07-14 10:48:09'),
(2, 'Metro Manila Supply Co.', 'Jun dela Cruz', 'jun@mmsupply.ph', '0917-987-6543', 'Quezon City, PH', 4.3, 1, '2026-07-14 10:48:09'),
(3, 'Cebu Trading Partners', 'Liza Reyes', 'liza@cebutrading.ph', '0932-555-1122', 'Cebu City, PH', 4.8, 1, '2026-07-14 10:48:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Administrator','Supply Chain Manager','Staff') NOT NULL DEFAULT 'Staff',
  `otp_secret` varchar(64) DEFAULT NULL,
  `otp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `role`, `otp_secret`, `otp_enabled`, `is_active`, `created_at`) VALUES
(7, 'FranJy', 'franvaliente24@gmail.com', '$2y$10$oDSXyhM9wNMt2lNMNXm.NuXqaJHMPy5prWFNuKTyqkkNKo4tqotgy', 'Administrator', NULL, 0, 1, '2026-07-27 10:57:18');

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_zones`
--

CREATE TABLE `warehouse_zones` (
  `zone_id` int(11) NOT NULL,
  `zone_name` varchar(100) NOT NULL,
  `rack_code` varchar(50) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `audit_status` enum('Verified','Pending Audit','Requires Attention') NOT NULL DEFAULT 'Pending Audit',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouse_zones`
--

INSERT INTO `warehouse_zones` (`zone_id`, `zone_name`, `rack_code`, `capacity`, `current_stock`, `audit_status`, `created_at`) VALUES
(1, 'Zone A', 'RK-A01', 500, 320, 'Verified', '2026-07-14 10:48:09'),
(2, 'Zone B', 'RK-B01', 300, 180, 'Pending Audit', '2026-07-14 10:48:09'),
(3, 'Zone C', 'RK-C01', 250, 40, 'Requires Attention', '2026-07-14 10:48:09'),
(4, 'Zone D', 'RK-D01', 150, 0, 'Verified', '2026-07-14 10:48:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `fk_item_supplier` (`supplier_id`);

--
-- Indexes for table `logistics_manifests`
--
ALTER TABLE `logistics_manifests`
  ADD PRIMARY KEY (`manifest_id`),
  ADD UNIQUE KEY `manifest_number` (`manifest_number`),
  ADD KEY `fk_manifest_order` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `fk_order_supplier` (`supplier_id`),
  ADD KEY `fk_order_user` (`created_by`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `fk_oi_order` (`order_id`),
  ADD KEY `fk_oi_item` (`item_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_reset_user` (`user_id`);

--
-- Indexes for table `purchase_requisitions`
--
ALTER TABLE `purchase_requisitions`
  ADD PRIMARY KEY (`requisition_id`),
  ADD UNIQUE KEY `requisition_number` (`requisition_number`),
  ADD KEY `fk_requisition_item` (`item_id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_remember_user` (`user_id`);

--
-- Indexes for table `stock_movement_logs`
--
ALTER TABLE `stock_movement_logs`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `fk_movement_item` (`item_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `warehouse_zones`
--
ALTER TABLE `warehouse_zones`
  ADD PRIMARY KEY (`zone_id`),
  ADD UNIQUE KEY `zone_name` (`zone_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `logistics_manifests`
--
ALTER TABLE `logistics_manifests`
  MODIFY `manifest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_requisitions`
--
ALTER TABLE `purchase_requisitions`
  MODIFY `requisition_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `stock_movement_logs`
--
ALTER TABLE `stock_movement_logs`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `warehouse_zones`
--
ALTER TABLE `warehouse_zones`
  MODIFY `zone_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `fk_item_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Constraints for table `logistics_manifests`
--
ALTER TABLE `logistics_manifests`
  ADD CONSTRAINT `fk_manifest_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_oi_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`),
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_requisitions`
--
ALTER TABLE `purchase_requisitions`
  ADD CONSTRAINT `fk_req_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_requisition_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON DELETE SET NULL;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `fk_remember_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movement_logs`
--
ALTER TABLE `stock_movement_logs`
  ADD CONSTRAINT `fk_move_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_movement_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
