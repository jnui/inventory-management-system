-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 03, 2025 at 09:21 AM
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
  `composition_description` text COLLATE utf8mb3_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `consumable_materials`
--

INSERT INTO `consumable_materials` (`id`, `item_type`, `item_name`, `item_description`, `normal_item_location`, `item_units_whole`, `item_units_part`, `qty_parts_per_whole`, `composition_description`) VALUES
(1, 'fitting', '4\" 45 degree fitting', '45 degree fitting, 4\"', 2, 'each', 'each', 1, 'ADS'),
(2, 'pipe', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', 2, 'stick', 'feet', 20, 'ADS');

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
(1, 'Abigail');

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
  `change_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

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
  ADD KEY `machine_instance_id` (`machine_instance_id`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `consumable_materials`
--
ALTER TABLE `consumable_materials`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_change_entries`
--
ALTER TABLE `inventory_change_entries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
