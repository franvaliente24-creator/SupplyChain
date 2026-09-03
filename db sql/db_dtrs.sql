-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 05:56 PM
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
-- Database: `db_dtrs`
--
CREATE DATABASE IF NOT EXISTS `db_dtrs` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_dtrs`;

-- --------------------------------------------------------

--
-- Table structure for table `carriers`
--

CREATE TABLE `carriers` (
  `carrier_id` int(11) NOT NULL,
  `carrier_name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `service_type` varchar(50) DEFAULT 'Other',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customs_records`
--

CREATE TABLE `customs_records` (
  `record_id` int(11) NOT NULL,
  `manifest_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `hold_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cleared_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_events`
--

CREATE TABLE `delivery_events` (
  `event_id` int(11) NOT NULL,
  `tracking_id` int(11) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `event_location` varchar(150) DEFAULT NULL,
  `event_notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_tracking`
--

CREATE TABLE `document_tracking` (
  `tracking_id` int(11) NOT NULL,
  `tracking_number` varchar(100) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `recipient_name` varchar(150) NOT NULL,
  `recipient_address` text DEFAULT NULL,
  `current_status` varchar(50) DEFAULT 'Created',
  `created_by` int(11) DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logistics_manifests`
--

CREATE TABLE `logistics_manifests` (
  `manifest_id` int(11) NOT NULL,
  `manifest_number` varchar(50) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `carrier_id` int(11) DEFAULT NULL,
  `carrier_name` varchar(150) NOT NULL,
  `tracking_number` varchar(100) NOT NULL,
  `dispatch_date` date DEFAULT NULL,
  `estimated_delivery` date DEFAULT NULL,
  `delivery_status` enum('Dispatched','In Transit','Out for Delivery','Delivered','Delayed') DEFAULT 'Dispatched',
  `document_url` varchar(255) DEFAULT 'manifest_copy.pdf',
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_documents`
--

CREATE TABLE `shipment_documents` (
  `doc_id` int(11) NOT NULL,
  `manifest_id` int(11) NOT NULL,
  `doc_type` varchar(100) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(255) NOT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carriers`
--
ALTER TABLE `carriers`
  ADD PRIMARY KEY (`carrier_id`);

--
-- Indexes for table `customs_records`
--
ALTER TABLE `customs_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `manifest_id` (`manifest_id`);

--
-- Indexes for table `delivery_events`
--
ALTER TABLE `delivery_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `tracking_id` (`tracking_id`);

--
-- Indexes for table `document_tracking`
--
ALTER TABLE `document_tracking`
  ADD PRIMARY KEY (`tracking_id`),
  ADD UNIQUE KEY `tracking_number` (`tracking_number`);

--
-- Indexes for table `logistics_manifests`
--
ALTER TABLE `logistics_manifests`
  ADD PRIMARY KEY (`manifest_id`),
  ADD UNIQUE KEY `manifest_number` (`manifest_number`);

--
-- Indexes for table `shipment_documents`
--
ALTER TABLE `shipment_documents`
  ADD PRIMARY KEY (`doc_id`),
  ADD KEY `manifest_id` (`manifest_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carriers`
--
ALTER TABLE `carriers`
  MODIFY `carrier_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customs_records`
--
ALTER TABLE `customs_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_events`
--
ALTER TABLE `delivery_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_tracking`
--
ALTER TABLE `document_tracking`
  MODIFY `tracking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logistics_manifests`
--
ALTER TABLE `logistics_manifests`
  MODIFY `manifest_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipment_documents`
--
ALTER TABLE `shipment_documents`
  MODIFY `doc_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customs_records`
--
ALTER TABLE `customs_records`
  ADD CONSTRAINT `customs_records_ibfk_1` FOREIGN KEY (`manifest_id`) REFERENCES `logistics_manifests` (`manifest_id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_events`
--
ALTER TABLE `delivery_events`
  ADD CONSTRAINT `delivery_events_ibfk_1` FOREIGN KEY (`tracking_id`) REFERENCES `document_tracking` (`tracking_id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_documents`
--
ALTER TABLE `shipment_documents`
  ADD CONSTRAINT `shipment_documents_ibfk_1` FOREIGN KEY (`manifest_id`) REFERENCES `logistics_manifests` (`manifest_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
