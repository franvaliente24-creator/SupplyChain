-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 30, 2026 at 09:40 PM
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
-- Database: `db_core`
--
CREATE DATABASE IF NOT EXISTS `db_core` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_core`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `status_class` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--

CREATE TABLE `login_history` (
  `history_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_status` enum('success','failed') NOT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_history`
--

INSERT INTO `login_history` (`history_id`, `user_id`, `username`, `ip_address`, `user_agent`, `login_status`, `failure_reason`, `created_at`) VALUES
(1, 8, 'Mae_Joy', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'success', NULL, '2026-08-26 04:59:05'),
(2, 8, 'Mae_Joy', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'success', NULL, '2026-08-27 10:36:15');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `remember_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remember_tokens`
--

INSERT INTO `remember_tokens` (`remember_id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(10, 7, '92be127457411c25c22518d160a5f684af574bec8b8bd8625e96e91550245fc3', '2026-08-26 14:00:23', '2026-07-27 12:00:23');

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
(7, 'FranJy', 'franvaliente24@gmail.com', '$2y$10$oDSXyhM9wNMt2lNMNXm.NuXqaJHMPy5prWFNuKTyqkkNKo4tqotgy', 'Administrator', NULL, 0, 1, '2026-07-27 10:57:18'),
(8, 'Mae_Joy', 'mae575552@gmail.com', '$2y$10$nAHcb0NsOySidtyr9ivl3OCqGVrtSu1B0kZm0e4ZQ/E98gZtmUxS2', 'Administrator', NULL, 0, 1, '2026-08-25 18:26:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_login_status` (`login_status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD KEY `idx_pr_user_id` (`user_id`),
  ADD KEY `idx_pr_token` (`token`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`remember_id`),
  ADD KEY `idx_rt_user_id` (`user_id`),
  ADD KEY `idx_rt_token` (`token`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `remember_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `fk_rt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
--
-- Database: `phpmyadmin`
--
CREATE DATABASE IF NOT EXISTS `phpmyadmin` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- Table structure for table `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';

-- --------------------------------------------------------

--
-- Table structure for table `pma__central_columns`
--

CREATE TABLE `pma__central_columns` (
  `db_name` varchar(64) NOT NULL,
  `col_name` varchar(64) NOT NULL,
  `col_type` varchar(64) NOT NULL,
  `col_length` text DEFAULT NULL,
  `col_collation` varchar(64) NOT NULL,
  `col_isNull` tinyint(1) NOT NULL,
  `col_extra` varchar(255) DEFAULT '',
  `col_default` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `column_name` varchar(64) NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `transformation` varchar(255) NOT NULL DEFAULT '',
  `transformation_options` varchar(255) NOT NULL DEFAULT '',
  `input_transformation` varchar(255) NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) NOT NULL,
  `settings_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Settings related to Designer';

-- --------------------------------------------------------

--
-- Table structure for table `pma__export_templates`
--

CREATE TABLE `pma__export_templates` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `export_type` varchar(10) NOT NULL,
  `template_name` varchar(64) NOT NULL,
  `template_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved export templates';

-- --------------------------------------------------------

--
-- Table structure for table `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Favorite tables';

-- --------------------------------------------------------

--
-- Table structure for table `pma__history`
--

CREATE TABLE `pma__history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db` varchar(64) NOT NULL DEFAULT '',
  `table` varchar(64) NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp(),
  `sqlquery` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__navigationhiding`
--

CREATE TABLE `pma__navigationhiding` (
  `username` varchar(64) NOT NULL,
  `item_name` varchar(64) NOT NULL,
  `item_type` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Recently accessed tables';

--
-- Dumping data for table `pma__recent`
--

INSERT INTO `pma__recent` (`username`, `tables`) VALUES
('root', '[{\"db\":\"supplychain\",\"table\":\"users\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `pma__relation`
--

CREATE TABLE `pma__relation` (
  `master_db` varchar(64) NOT NULL DEFAULT '',
  `master_table` varchar(64) NOT NULL DEFAULT '',
  `master_field` varchar(64) NOT NULL DEFAULT '',
  `foreign_db` varchar(64) NOT NULL DEFAULT '',
  `foreign_table` varchar(64) NOT NULL DEFAULT '',
  `foreign_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- Table structure for table `pma__savedsearches`
--

CREATE TABLE `pma__savedsearches` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `search_name` varchar(64) NOT NULL DEFAULT '',
  `search_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved searches';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_coords`
--

CREATE TABLE `pma__table_coords` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT 0,
  `x` float UNSIGNED NOT NULL DEFAULT 0,
  `y` float UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `display_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_uiprefs`
--

CREATE TABLE `pma__table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tables'' UI preferences';

-- --------------------------------------------------------

--
-- Table structure for table `pma__tracking`
--

CREATE TABLE `pma__tracking` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text NOT NULL,
  `schema_sql` text DEFAULT NULL,
  `data_sql` longtext DEFAULT NULL,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') DEFAULT NULL,
  `tracking_active` int(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- Dumping data for table `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2026-08-30 17:19:02', '{\"Console\\/Mode\":\"collapse\",\"lang\":\"en_GB\"}');

-- --------------------------------------------------------

--
-- Table structure for table `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- Table structure for table `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Users and their assignments to user groups';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pma__central_columns`
--
ALTER TABLE `pma__central_columns`
  ADD PRIMARY KEY (`db_name`,`col_name`);

--
-- Indexes for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`);

--
-- Indexes for table `pma__designer_settings`
--
ALTER TABLE `pma__designer_settings`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`);

--
-- Indexes for table `pma__favorite`
--
ALTER TABLE `pma__favorite`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__history`
--
ALTER TABLE `pma__history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`db`,`table`,`timevalue`);

--
-- Indexes for table `pma__navigationhiding`
--
ALTER TABLE `pma__navigationhiding`
  ADD PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`);

--
-- Indexes for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  ADD PRIMARY KEY (`page_nr`),
  ADD KEY `db_name` (`db_name`);

--
-- Indexes for table `pma__recent`
--
ALTER TABLE `pma__recent`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__relation`
--
ALTER TABLE `pma__relation`
  ADD PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  ADD KEY `foreign_field` (`foreign_db`,`foreign_table`);

--
-- Indexes for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`);

--
-- Indexes for table `pma__table_coords`
--
ALTER TABLE `pma__table_coords`
  ADD PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`);

--
-- Indexes for table `pma__table_info`
--
ALTER TABLE `pma__table_info`
  ADD PRIMARY KEY (`db_name`,`table_name`);

--
-- Indexes for table `pma__table_uiprefs`
--
ALTER TABLE `pma__table_uiprefs`
  ADD PRIMARY KEY (`username`,`db_name`,`table_name`);

--
-- Indexes for table `pma__tracking`
--
ALTER TABLE `pma__tracking`
  ADD PRIMARY KEY (`db_name`,`table_name`,`version`);

--
-- Indexes for table `pma__userconfig`
--
ALTER TABLE `pma__userconfig`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__usergroups`
--
ALTER TABLE `pma__usergroups`
  ADD PRIMARY KEY (`usergroup`,`tab`,`allowed`);

--
-- Indexes for table `pma__users`
--
ALTER TABLE `pma__users`
  ADD PRIMARY KEY (`username`,`usergroup`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__history`
--
ALTER TABLE `pma__history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  MODIFY `page_nr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Database: `schema_updates`
--
CREATE DATABASE IF NOT EXISTS `schema_updates` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `schema_updates`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--
-- Error reading structure for table schema_updates.activity_log: #1932 - Table &#039;schema_updates.activity_log&#039; doesn&#039;t exist in engine
-- Error reading data for table schema_updates.activity_log: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `schema_updates`.`activity_log`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `asset_assignments`
--
-- Error reading structure for table schema_updates.asset_assignments: #1932 - Table &#039;schema_updates.asset_assignments&#039; doesn&#039;t exist in engine
-- Error reading data for table schema_updates.asset_assignments: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `schema_updates`.`asset_assignments`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `asset_condition_history`
--
-- Error reading structure for table schema_updates.asset_condition_history: #1932 - Table &#039;schema_updates.asset_condition_history&#039; doesn&#039;t exist in engine
-- Error reading data for table schema_updates.asset_condition_history: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `schema_updates`.`asset_condition_history`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--
-- Error reading structure for table schema_updates.login_history: #1932 - Table &#039;schema_updates.login_history&#039; doesn&#039;t exist in engine
-- Error reading data for table schema_updates.login_history: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `schema_updates`.`login_history`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `tech_assets`
--
-- Error reading structure for table schema_updates.tech_assets: #1932 - Table &#039;schema_updates.tech_assets&#039; doesn&#039;t exist in engine
-- Error reading data for table schema_updates.tech_assets: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `schema_updates`.`tech_assets`&#039; at line 1
--
-- Database: `supplychain`
--
CREATE DATABASE IF NOT EXISTS `supplychain` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `supplychain`;

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
-- Table structure for table `carriers`
--

CREATE TABLE `carriers` (
  `carrier_id` int(11) NOT NULL,
  `carrier_name` varchar(150) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `service_type` enum('Freight','Courier','Inter-island Ferry','Air Cargo','Trucking','Other') NOT NULL DEFAULT 'Other',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customs_records`
--

CREATE TABLE `customs_records` (
  `record_id` int(11) NOT NULL,
  `manifest_id` int(11) NOT NULL,
  `document_type` enum('Import Permit','Customs Declaration','Certificate of Origin','Bureau of Customs Hold','Quarantine Clearance','Other') NOT NULL DEFAULT 'Other',
  `reference_number` varchar(100) DEFAULT NULL,
  `hold_reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cleared_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_events`
--

CREATE TABLE `delivery_events` (
  `event_id` int(11) NOT NULL,
  `tracking_id` int(11) NOT NULL,
  `event_type` enum('Created','Picked Up','In Transit','Out for Delivery','Delivered','Failed','Delayed') NOT NULL,
  `event_location` varchar(255) DEFAULT NULL,
  `event_notes` text DEFAULT NULL,
  `event_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `recorded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_archive`
--

CREATE TABLE `document_archive` (
  `archive_id` int(11) NOT NULL,
  `original_tracking_id` int(11) DEFAULT NULL,
  `tracking_number` varchar(50) DEFAULT NULL,
  `document_type` varchar(100) DEFAULT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `final_status` varchar(50) DEFAULT NULL,
  `archived_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` int(11) DEFAULT NULL,
  `retention_until` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_tracking`
--

CREATE TABLE `document_tracking` (
  `tracking_id` int(11) NOT NULL,
  `tracking_number` varchar(50) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `recipient_address` text DEFAULT NULL,
  `current_status` enum('Created','Out for Delivery','In Transit','Delivered','Failed','Delayed') DEFAULT 'Created',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expected_delivery_date` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipts`
--

CREATE TABLE `goods_receipts` (
  `receipt_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `received_by` varchar(150) NOT NULL,
  `notes` text DEFAULT NULL,
  `received_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipt_items`
--

CREATE TABLE `goods_receipt_items` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipt_logs`
--

CREATE TABLE `goods_receipt_logs` (
  `receipt_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `expected_quantity` int(11) NOT NULL,
  `received_quantity` int(11) NOT NULL,
  `discrepancy_quantity` int(11) DEFAULT 0,
  `discrepancy_reason` text DEFAULT NULL,
  `received_by` int(11) NOT NULL,
  `received_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `location_suggested` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `transaction_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `transaction_type` enum('Stock In','Stock Out','Adjustment','Transfer','Requisition') NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `previous_quantity` int(11) DEFAULT NULL,
  `new_quantity` int(11) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--

CREATE TABLE `login_history` (
  `history_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_status` enum('success','failed') NOT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_history`
--

INSERT INTO `login_history` (`history_id`, `user_id`, `username`, `ip_address`, `user_agent`, `login_status`, `failure_reason`, `created_at`) VALUES
(1, 8, 'Mae_Joy', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'success', NULL, '2026-08-26 04:59:05'),
(2, 8, 'Mae_Joy', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'success', NULL, '2026-08-27 10:36:15');

-- --------------------------------------------------------

--
-- Table structure for table `logistics_manifests`
--

CREATE TABLE `logistics_manifests` (
  `manifest_id` int(11) NOT NULL,
  `manifest_number` varchar(30) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `carrier_name` varchar(150) DEFAULT NULL,
  `carrier_id` int(11) DEFAULT NULL,
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

INSERT INTO `logistics_manifests` (`manifest_id`, `manifest_number`, `order_id`, `carrier_name`, `carrier_id`, `tracking_number`, `dispatch_date`, `estimated_delivery`, `delivery_status`, `document_url`, `created_at`) VALUES
(1, 'MNF-2026-021', 1, 'Batangas Hub Logistics', NULL, 'TRK-778210', '2026-07-12', '2026-07-20', 'In Transit', 'manifest_copy.pdf', '2026-07-14 10:48:09'),
(2, 'MNF-2026-022', 2, 'Metro Manila Supply Co.', NULL, 'MMS-556231', '2026-07-12', '2026-07-22', 'Dispatched', 'manifest_copy.pdf', '2026-07-14 10:48:09');

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
  `unit_price` decimal(10,2) NOT NULL,
  `quantity_received` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `item_id`, `quantity`, `unit_price`, `quantity_received`) VALUES
(1, 1, 1, 20, 1500.00, 0);

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
-- Table structure for table `po_qr_codes`
--

CREATE TABLE `po_qr_codes` (
  `qr_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `qr_code` varchar(255) NOT NULL,
  `generated_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `scanned` tinyint(1) DEFAULT 0,
  `scanned_date` timestamp NULL DEFAULT NULL,
  `scanned_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `procurement_templates`
--

CREATE TABLE `procurement_templates` (
  `template_id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `default_budget` decimal(15,2) DEFAULT NULL,
  `required_approvals` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_approvals`)),
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurring_frequency` enum('Monthly','Quarterly','Annually') DEFAULT NULL,
  `created_by` int(11) NOT NULL,
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
-- Table structure for table `rfps`
--

CREATE TABLE `rfps` (
  `rfp_id` int(11) NOT NULL,
  `rfp_number` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `budget_limit` decimal(15,2) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('Draft','Published','Closed','Awarded') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rfp_responses`
--

CREATE TABLE `rfp_responses` (
  `response_id` int(11) NOT NULL,
  `rfp_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `quote_amount` decimal(15,2) DEFAULT NULL,
  `proposal_document` text DEFAULT NULL,
  `submitted_date` date DEFAULT NULL,
  `status` enum('Submitted','Under Review','Accepted','Rejected') DEFAULT 'Submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_documents`
--

CREATE TABLE `shipment_documents` (
  `doc_id` int(11) NOT NULL,
  `manifest_id` int(11) NOT NULL,
  `doc_type` enum('Bill of Lading','Packing Slip','Customs Form','Delivery Receipt','Invoice','Other') NOT NULL DEFAULT 'Other',
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `uploaded_by` varchar(150) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `stock_requisitions`
--

CREATE TABLE `stock_requisitions` (
  `requisition_id` int(11) NOT NULL,
  `requisition_number` varchar(50) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `quantity_requested` int(11) NOT NULL,
  `quantity_approved` int(11) DEFAULT NULL,
  `request_date` date NOT NULL,
  `approval_status` enum('Pending','Approved','Rejected','Partially Fulfilled') DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `vendor_type` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `status` enum('Active','Pending Approval','Suspended','Blacklisted') NOT NULL DEFAULT 'Active',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `vendor_type`, `contact_person`, `email`, `phone`, `address`, `rating`, `status`, `is_active`, `created_at`) VALUES
(1, 'Batangas Hub Logistics', 'Product Supplier', 'Maria Santos', 'maria@batangashub.ph', '0917-123-4567', 'Batangas City, PH', 4.6, 'Active', 1, '2026-07-14 10:48:09'),
(2, 'Metro Manila Supply Co.', 'Product Supplier', 'Jun dela Cruz', 'jun@mmsupply.ph', '0917-987-6543', 'Quezon City, PH', 4.3, 'Active', 1, '2026-07-14 10:48:09'),
(3, 'Cebu Trading Partners', 'Product Supplier', 'Liza Reyes', 'liza@cebutrading.ph', '0932-555-1122', 'Cebu City, PH', 4.8, 'Active', 1, '2026-07-14 10:48:09');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_documents`
--

CREATE TABLE `supplier_documents` (
  `document_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `upload_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Active','Expired','Pending Renewal') DEFAULT 'Active',
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_transactions`
--

CREATE TABLE `supplier_transactions` (
  `transaction_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `transaction_type` enum('Quote','Purchase Order','Delivery','Payment','Return') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
(7, 'FranJy', 'franvaliente24@gmail.com', '$2y$10$oDSXyhM9wNMt2lNMNXm.NuXqaJHMPy5prWFNuKTyqkkNKo4tqotgy', 'Administrator', NULL, 0, 1, '2026-07-27 10:57:18'),
(8, 'Mae_Joy', 'mae575552@gmail.com', '$2y$10$nAHcb0NsOySidtyr9ivl3OCqGVrtSu1B0kZm0e4ZQ/E98gZtmUxS2', 'Administrator', NULL, 0, 1, '2026-08-25 18:26:19');

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
-- Indexes for table `carriers`
--
ALTER TABLE `carriers`
  ADD PRIMARY KEY (`carrier_id`);

--
-- Indexes for table `customs_records`
--
ALTER TABLE `customs_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `idx_manifest_id` (`manifest_id`),
  ADD KEY `idx_document_type` (`document_type`);

--
-- Indexes for table `delivery_events`
--
ALTER TABLE `delivery_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_tracking_id` (`tracking_id`),
  ADD KEY `idx_event_timestamp` (`event_timestamp`);

--
-- Indexes for table `document_archive`
--
ALTER TABLE `document_archive`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `idx_tracking_number` (`tracking_number`);

--
-- Indexes for table `document_tracking`
--
ALTER TABLE `document_tracking`
  ADD PRIMARY KEY (`tracking_id`),
  ADD UNIQUE KEY `tracking_number` (`tracking_number`),
  ADD KEY `idx_tracking_number` (`tracking_number`),
  ADD KEY `idx_current_status` (`current_status`);

--
-- Indexes for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD PRIMARY KEY (`receipt_id`),
  ADD KEY `idx_order_id` (`order_id`);

--
-- Indexes for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_receipt_id` (`receipt_id`),
  ADD KEY `idx_order_item_id` (`order_item_id`);

--
-- Indexes for table `goods_receipt_logs`
--
ALTER TABLE `goods_receipt_logs`
  ADD PRIMARY KEY (`receipt_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `idx_order_id` (`order_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `fk_item_supplier` (`supplier_id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_login_status` (`login_status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `logistics_manifests`
--
ALTER TABLE `logistics_manifests`
  ADD PRIMARY KEY (`manifest_id`),
  ADD UNIQUE KEY `manifest_number` (`manifest_number`),
  ADD KEY `fk_manifest_order` (`order_id`),
  ADD KEY `carrier_id` (`carrier_id`);

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
-- Indexes for table `po_qr_codes`
--
ALTER TABLE `po_qr_codes`
  ADD PRIMARY KEY (`qr_id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `idx_qr_code` (`qr_code`),
  ADD KEY `idx_order_id` (`order_id`);

--
-- Indexes for table `procurement_templates`
--
ALTER TABLE `procurement_templates`
  ADD PRIMARY KEY (`template_id`),
  ADD KEY `created_by` (`created_by`);

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
-- Indexes for table `rfps`
--
ALTER TABLE `rfps`
  ADD PRIMARY KEY (`rfp_id`),
  ADD UNIQUE KEY `rfp_number` (`rfp_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_rfp_number` (`rfp_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `rfp_responses`
--
ALTER TABLE `rfp_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `idx_rfp_id` (`rfp_id`);

--
-- Indexes for table `shipment_documents`
--
ALTER TABLE `shipment_documents`
  ADD PRIMARY KEY (`doc_id`),
  ADD KEY `idx_manifest_id` (`manifest_id`),
  ADD KEY `idx_doc_type` (`doc_type`);

--
-- Indexes for table `stock_movement_logs`
--
ALTER TABLE `stock_movement_logs`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `fk_movement_item` (`item_id`);

--
-- Indexes for table `stock_requisitions`
--
ALTER TABLE `stock_requisitions`
  ADD PRIMARY KEY (`requisition_id`),
  ADD UNIQUE KEY `requisition_number` (`requisition_number`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `idx_requisition_number` (`requisition_number`),
  ADD KEY `idx_approval_status` (`approval_status`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `supplier_documents`
--
ALTER TABLE `supplier_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `idx_supplier_id` (`supplier_id`),
  ADD KEY `idx_document_type` (`document_type`);

--
-- Indexes for table `supplier_transactions`
--
ALTER TABLE `supplier_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_supplier_id` (`supplier_id`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_transaction_date` (`transaction_date`);

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
-- AUTO_INCREMENT for table `document_archive`
--
ALTER TABLE `document_archive`
  MODIFY `archive_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_tracking`
--
ALTER TABLE `document_tracking`
  MODIFY `tracking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receipt_logs`
--
ALTER TABLE `goods_receipt_logs`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `po_qr_codes`
--
ALTER TABLE `po_qr_codes`
  MODIFY `qr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procurement_templates`
--
ALTER TABLE `procurement_templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `rfps`
--
ALTER TABLE `rfps`
  MODIFY `rfp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rfp_responses`
--
ALTER TABLE `rfp_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipment_documents`
--
ALTER TABLE `shipment_documents`
  MODIFY `doc_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movement_logs`
--
ALTER TABLE `stock_movement_logs`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stock_requisitions`
--
ALTER TABLE `stock_requisitions`
  MODIFY `requisition_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `supplier_documents`
--
ALTER TABLE `supplier_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_transactions`
--
ALTER TABLE `supplier_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tech_assets`
--
ALTER TABLE `tech_assets`
  MODIFY `asset_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
-- Constraints for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD CONSTRAINT `goods_receipts_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD CONSTRAINT `goods_receipt_items_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipts` (`receipt_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `goods_receipt_items_ibfk_2` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`order_item_id`) ON DELETE CASCADE;

--
-- Constraints for table `goods_receipt_logs`
--
ALTER TABLE `goods_receipt_logs`
  ADD CONSTRAINT `goods_receipt_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `goods_receipt_logs_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`);

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `fk_item_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`);

--
-- Constraints for table `logistics_manifests`
--
ALTER TABLE `logistics_manifests`
  ADD CONSTRAINT `fk_manifest_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `logistics_manifests_ibfk_1` FOREIGN KEY (`carrier_id`) REFERENCES `carriers` (`carrier_id`) ON DELETE SET NULL;

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
-- Constraints for table `po_qr_codes`
--
ALTER TABLE `po_qr_codes`
  ADD CONSTRAINT `po_qr_codes_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `po_qr_codes_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`);

--
-- Constraints for table `procurement_templates`
--
ALTER TABLE `procurement_templates`
  ADD CONSTRAINT `procurement_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

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
-- Constraints for table `rfps`
--
ALTER TABLE `rfps`
  ADD CONSTRAINT `rfps_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `rfp_responses`
--
ALTER TABLE `rfp_responses`
  ADD CONSTRAINT `rfp_responses_ibfk_1` FOREIGN KEY (`rfp_id`) REFERENCES `rfps` (`rfp_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rfp_responses_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `shipment_documents`
--
ALTER TABLE `shipment_documents`
  ADD CONSTRAINT `shipment_documents_ibfk_1` FOREIGN KEY (`manifest_id`) REFERENCES `logistics_manifests` (`manifest_id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movement_logs`
--
ALTER TABLE `stock_movement_logs`
  ADD CONSTRAINT `fk_move_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_movement_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_requisitions`
--
ALTER TABLE `stock_requisitions`
  ADD CONSTRAINT `stock_requisitions_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `stock_requisitions_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`);

--
-- Constraints for table `supplier_documents`
--
ALTER TABLE `supplier_documents`
  ADD CONSTRAINT `supplier_documents_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_transactions`
--
ALTER TABLE `supplier_transactions`
  ADD CONSTRAINT `supplier_transactions_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;
--
-- Database: `supply_chain`
--
CREATE DATABASE IF NOT EXISTS `supply_chain` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `supply_chain`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--
-- Error reading structure for table supply_chain.activity_log: #1932 - Table &#039;supply_chain.activity_log&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.activity_log: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`activity_log`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--
-- Error reading structure for table supply_chain.api_keys: #1932 - Table &#039;supply_chain.api_keys&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.api_keys: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`api_keys`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `carriers`
--
-- Error reading structure for table supply_chain.carriers: #1932 - Table &#039;supply_chain.carriers&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.carriers: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`carriers`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `customs_records`
--
-- Error reading structure for table supply_chain.customs_records: #1932 - Table &#039;supply_chain.customs_records&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.customs_records: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`customs_records`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `delivery_confirmations`
--
-- Error reading structure for table supply_chain.delivery_confirmations: #1932 - Table &#039;supply_chain.delivery_confirmations&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.delivery_confirmations: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`delivery_confirmations`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipts`
--
-- Error reading structure for table supply_chain.goods_receipts: #1932 - Table &#039;supply_chain.goods_receipts&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.goods_receipts: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`goods_receipts`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipt_items`
--
-- Error reading structure for table supply_chain.goods_receipt_items: #1932 - Table &#039;supply_chain.goods_receipt_items&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.goods_receipt_items: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`goods_receipt_items`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `integration_events`
--
-- Error reading structure for table supply_chain.integration_events: #1932 - Table &#039;supply_chain.integration_events&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.integration_events: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`integration_events`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--
-- Error reading structure for table supply_chain.inventory_items: #1932 - Table &#039;supply_chain.inventory_items&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.inventory_items: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`inventory_items`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `logistics_manifests`
--
-- Error reading structure for table supply_chain.logistics_manifests: #1932 - Table &#039;supply_chain.logistics_manifests&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.logistics_manifests: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`logistics_manifests`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--
-- Error reading structure for table supply_chain.orders: #1932 - Table &#039;supply_chain.orders&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.orders: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`orders`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--
-- Error reading structure for table supply_chain.order_items: #1932 - Table &#039;supply_chain.order_items&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.order_items: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`order_items`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--
-- Error reading structure for table supply_chain.password_resets: #1932 - Table &#039;supply_chain.password_resets&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.password_resets: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`password_resets`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requisitions`
--
-- Error reading structure for table supply_chain.purchase_requisitions: #1932 - Table &#039;supply_chain.purchase_requisitions&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.purchase_requisitions: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`purchase_requisitions`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--
-- Error reading structure for table supply_chain.remember_tokens: #1932 - Table &#039;supply_chain.remember_tokens&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.remember_tokens: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`remember_tokens`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `shipment_documents`
--
-- Error reading structure for table supply_chain.shipment_documents: #1932 - Table &#039;supply_chain.shipment_documents&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.shipment_documents: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`shipment_documents`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `stock_movement_logs`
--
-- Error reading structure for table supply_chain.stock_movement_logs: #1932 - Table &#039;supply_chain.stock_movement_logs&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.stock_movement_logs: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`stock_movement_logs`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--
-- Error reading structure for table supply_chain.suppliers: #1932 - Table &#039;supply_chain.suppliers&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.suppliers: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`suppliers`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `supplier_categories`
--
-- Error reading structure for table supply_chain.supplier_categories: #1932 - Table &#039;supply_chain.supplier_categories&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.supplier_categories: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`supplier_categories`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `supplier_category_map`
--
-- Error reading structure for table supply_chain.supplier_category_map: #1932 - Table &#039;supply_chain.supplier_category_map&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.supplier_category_map: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`supplier_category_map`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `supplier_documents`
--
-- Error reading structure for table supply_chain.supplier_documents: #1932 - Table &#039;supply_chain.supplier_documents&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.supplier_documents: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`supplier_documents`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
-- Error reading structure for table supply_chain.users: #1932 - Table &#039;supply_chain.users&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.users: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`users`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_zones`
--
-- Error reading structure for table supply_chain.warehouse_zones: #1932 - Table &#039;supply_chain.warehouse_zones&#039; doesn&#039;t exist in engine
-- Error reading data for table supply_chain.warehouse_zones: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `supply_chain`.`warehouse_zones`&#039; at line 1
--
-- Database: `test`
--
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `test`;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
