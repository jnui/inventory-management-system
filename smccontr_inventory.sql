-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 04, 2025 at 12:26 PM
-- Server version: 8.0.41
-- PHP Version: 8.3.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smccontr_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `consumable_materials`
--

CREATE TABLE `consumable_materials` (
  `id` int NOT NULL,
  `item_type` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `item_name` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `item_description` text COLLATE utf8mb3_unicode_ci,
  `normal_item_location` int DEFAULT NULL,
  `item_units_whole` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `item_units_part` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `qty_parts_per_whole` int DEFAULT NULL,
  `composition_description` text COLLATE utf8mb3_unicode_ci,
  `whole_quantity` int DEFAULT '0' COMMENT 'Running total of whole pieces',
  `reorder_threshold` int DEFAULT '0' COMMENT 'Threshold at which items should be reordered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `consumable_materials`
--

INSERT INTO `consumable_materials` (`id`, `item_type`, `item_name`, `item_description`, `normal_item_location`, `item_units_whole`, `item_units_part`, `qty_parts_per_whole`, `composition_description`, `whole_quantity`, `reorder_threshold`) VALUES
(1, 'fitting', '4\" 45 degree fitting', '45 degree fitting, 4\"', 2, 'each', 'each', 1, 'ADS', 100, 90),
(2, 'pipe', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', 2, 'stick', 'feet', 20, 'ADS', 3, 10),
(3, 'Fitting', '6” Tee', '6” ADS Tee', 3, 'each', 'Each', 1, 'ADS', 50, 40);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `first_name`) VALUES
(1, 'Abigail'),
(2, 'Albin'),
(3, '_SMC');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_change_entries`
--

CREATE TABLE `inventory_change_entries` (
  `id` int NOT NULL,
  `consumable_material_id` int DEFAULT NULL,
  `machine_instance_id` int DEFAULT NULL,
  `item_short_code` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `item_name` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `item_description` text COLLATE utf8mb3_unicode_ci,
  `item_notes` text COLLATE utf8mb3_unicode_ci,
  `normal_item_location` int DEFAULT NULL,
  `reorder_threshold` int DEFAULT NULL,
  `change_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `items_added` int DEFAULT '0' COMMENT 'Number of items added to inventory',
  `items_removed` int DEFAULT '0' COMMENT 'Number of items removed from inventory',
  `whole_quantity` int DEFAULT '0' COMMENT 'Running total of whole pieces',
  `employee_id` int DEFAULT NULL COMMENT 'Reference to the employee who made the change'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `inventory_change_entries`
--

INSERT INTO `inventory_change_entries` (`id`, `consumable_material_id`, `machine_instance_id`, `item_short_code`, `item_name`, `item_description`, `item_notes`, `normal_item_location`, `reorder_threshold`, `change_date`, `items_added`, `items_removed`, `whole_quantity`, `employee_id`) VALUES
(1, 1, NULL, 'fitting-1', '4\" 45 degree fitting', '45 degree fitting, 4\"', '', 2, 0, '2025-03-03 10:08:23', 10, 0, 10, NULL),
(2, 1, NULL, 'fitting-1', '4\" 45 degree fitting', '45 degree fitting, 4\"', '', 2, 0, '2025-03-03 10:23:41', 0, 11, 0, NULL),
(3, 1, NULL, 'fitting-1', '4\" 45 degree fitting', '45 degree fitting, 4\"', '', 2, 0, '2025-03-03 10:32:31', 100, 0, 100, NULL),
(4, 2, NULL, 'pipe-2', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 10:33:14', 0, 15, 0, NULL),
(5, 2, NULL, 'pipe-2', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 10:51:39', 12, 0, 12, NULL),
(6, 2, NULL, '', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 12:48:46', 0, 1, 11, NULL),
(7, 2, NULL, '', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 13:21:23', 0, 1, 10, 1),
(8, 2, NULL, 'pipe-2', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 13:39:43', 0, 3, 7, 1),
(9, 2, NULL, 'pipe-2', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 13:40:07', 0, 3, 4, 1),
(10, 2, NULL, 'pipe-2', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 13:54:03', 0, 1, 3, 2),
(11, 3, NULL, 'Fitting-3', '6” Tee', '6” ADS Tee', '', 3, 0, '2025-03-03 16:20:57', 50, 0, 50, 3);

-- --------------------------------------------------------

--
-- Table structure for table `item_locations`
--

CREATE TABLE `item_locations` (
  `id` int NOT NULL,
  `location_short_code` varchar(20) COLLATE utf8mb3_unicode_ci NOT NULL,
  `location_name` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `location_description` text COLLATE utf8mb3_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `item_locations`
--

INSERT INTO `item_locations` (`id`, `location_short_code`, `location_name`, `location_description`) VALUES
(1, 'BR1', 'Back Rack 1', 'Outdoor Pipe storage rack #1'),
(2, 'BR2', 'Back Rack 2', 'Outdoor Pipe storage rack #2'),
(3, 'BR3', 'Back Rack 3', 'Outdoor Pipe storage rack #3');

-- --------------------------------------------------------

--
-- Table structure for table `machine_item_instances`
--

CREATE TABLE `machine_item_instances` (
  `id` int NOT NULL,
  `instance_name` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `instance_serial_number` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `assigned_to` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reusable_item_types`
--

CREATE TABLE `reusable_item_types` (
  `id` int NOT NULL,
  `item_type_code` varchar(20) COLLATE utf8mb3_unicode_ci NOT NULL,
  `item_type_name` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `item_type_description` text COLLATE utf8mb3_unicode_ci,
  `item_type_location` int DEFAULT NULL,
  `isMachine` tinyint(1) DEFAULT '0',
  `isHandtool` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `initials` varchar(10) COLLATE utf8mb3_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `role` enum('user','admin') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `initials`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'ADMIN', '$2y$10$zKLfDirLurr5dq.N4AJiAepYr6fEPRvWpt5bkGtz63zPLzFmIINAG', 'admin', '2025-03-04 13:28:57', '2025-03-04 13:39:10'),
(2, 'DK', 'DK', '$2y$10$dcVrcBoVUV7yv/xgiqPZG.BZOjaoIZ65EU9bH4Sp6mWXVWjiIH3Za', 'user', '2025-03-04 13:41:16', '2025-03-04 14:06:34'),
(3, 'KimO', 'KO', '$2y$10$MjLq4KY77V10tTbvqflPfOx5xB.RVHivRF.8lB1LTOjA9PcprTYu.', 'user', '2025-03-04 13:41:39', '2025-03-04 13:41:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `consumable_materials`
--
ALTER TABLE `consumable_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `normal_item_location` (`normal_item_location`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_change_entries`
--
ALTER TABLE `inventory_change_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `normal_item_location` (`normal_item_location`),
  ADD KEY `consumable_material_id` (`consumable_material_id`),
  ADD KEY `machine_instance_id` (`machine_instance_id`),
  ADD KEY `fk_employee_id` (`employee_id`);

--
-- Indexes for table `item_locations`
--
ALTER TABLE `item_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `machine_item_instances`
--
ALTER TABLE `machine_item_instances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `reusable_item_types`
--
ALTER TABLE `reusable_item_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_type_location` (`item_type_location`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `consumable_materials`
--
ALTER TABLE `consumable_materials`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory_change_entries`
--
ALTER TABLE `inventory_change_entries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `item_locations`
--
ALTER TABLE `item_locations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `machine_item_instances`
--
ALTER TABLE `machine_item_instances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reusable_item_types`
--
ALTER TABLE `reusable_item_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `consumable_materials`
--
ALTER TABLE `consumable_materials`
  ADD CONSTRAINT `consumable_materials_ibfk_1` FOREIGN KEY (`normal_item_location`) REFERENCES `item_locations` (`id`);

--
-- Constraints for table `inventory_change_entries`
--
ALTER TABLE `inventory_change_entries`
  ADD CONSTRAINT `fk_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_change_entries_ibfk_1` FOREIGN KEY (`normal_item_location`) REFERENCES `item_locations` (`id`),
  ADD CONSTRAINT `inventory_change_entries_ibfk_2` FOREIGN KEY (`consumable_material_id`) REFERENCES `consumable_materials` (`id`),
  ADD CONSTRAINT `inventory_change_entries_ibfk_3` FOREIGN KEY (`machine_instance_id`) REFERENCES `machine_item_instances` (`id`);

--
-- Constraints for table `machine_item_instances`
--
ALTER TABLE `machine_item_instances`
  ADD CONSTRAINT `machine_item_instances_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `employees` (`id`);

--
-- Constraints for table `reusable_item_types`
--
ALTER TABLE `reusable_item_types`
  ADD CONSTRAINT `reusable_item_types_ibfk_1` FOREIGN KEY (`item_type_location`) REFERENCES `item_locations` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
