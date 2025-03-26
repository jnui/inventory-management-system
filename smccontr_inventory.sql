-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 10, 2025 at 12:23 PM
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

DROP TABLE IF EXISTS `consumable_materials`;
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
(1, 'Pipe', '4\" sock pipe', '50 foot coil of 4 inch sock pipe', 1, 'Coil', 'Feet', 50, 'Sock pipe', 5, 4),
(2, 'pipe', '6” sock pipe', '50 foot coils 6 inch sock pipe', 1, 'Coil', 'feet', 50, 'Sock pipe', 3, 2),
(3, 'Pipe', '4” styrofoam Perf pipe', 'French drain sock pipe styrofoam ', 1, 'Pipe', 'Feet', 10, 'ADS and styrofoam sock', 7, 5),
(4, 'Pipe', '12 inch GA pipe solid', '12 inch Georgia pipe solid', 1, 'Stick', 'Foot', 20, 'ADS Hardco solid', 22, 0),
(5, 'Pipe', '24 inch solid pipe', '24 inch ADS solid pipe', 3, 'Stick', 'Foot', 20, 'Hardco ads', 7, 0),
(6, 'Shoring', '2 foot by 16 foot Plastic ', 'Shore guard 16', 2, 'Panel', 'Foot', 2, 'Plastic', 79, 30),
(7, 'Shoring', '2 foot by 12 foot shoring', 'Shoreguard', 4, 'Panel', 'Foot', 2, 'Plastic', 105, 30),
(8, 'Shoring', 'Shoring corner adapter 16', '', 4, 'Stick', 'Foot', 16, 'Plastic', 1, 4),
(9, 'Shoring', 'Shoring adapter 12 feet', '12 foot shoring corner adapter', 4, 'Stick', 'Feet', 2, 'Plastic', 7, 4),
(10, 'Shoring ', 'Sheet shoring 2 foot by 12', 'Synthetic sheet piling, shoring, flat sided, 2 x 12', 5, 'Panel', 'Foot', 2, 'Plastic', 40, 10),
(11, 'Pipe', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 6, 'Stick', 'Feet', 20, 'Galv', 14, 0),
(12, 'Fitting', '12 inch 90 solid', '', 30, 'Each', 'Each', 1, 'ADS solid', 23, 20),
(13, '12 x 8 reducer', '12 x 8 reducer', '', 30, 'Each', 'Each', 1, '', 100, 30),
(14, 'Fitting', '12 inch 45', '', 30, 'Each', 'Each', 1, '', 20, 20),
(15, '12 x 8 Wyes', '12 x 8 Wyes', '', 30, 'Each', 'Each', 1, '', 75, 30),
(16, '12 inch ribbed 90s', '12 inch ribbed 90s', '', 30, 'Each', 'Each', 1, '', 30, 0),
(17, 'Fitting', '8 x 4 reducer ribbed', '', 30, 'Each', 'Each', 1, '', 12, 0),
(18, 'Fitting', '12 inch ribbed tees', '', 30, 'Each', 'Each', 1, '', 15, 0),
(19, 'Fitting', '10 inch 45s', '', 30, 'Each', 'Each', 1, '', 10, 5),
(20, 'Fitting', '12 x8 Tee', '', 29, 'Each', 'Each', 1, '', 1, 10),
(21, 'Fitting ', '12 inch, 90', '', 29, 'Each', 'Each', 1, '', 28, 30),
(22, 'Fitting', '12 x 12 Wye', '', 29, 'Each', 'Each', 1, '', 96, 40),
(23, 'Fitting ', '12 x 12 rib Wyes', '', 29, 'Each', 'Each', 1, '', 158, 10),
(24, 'Fitting ', '12 x 12 Tee', '', 29, 'Each', 'Each', 1, '', 13, 20),
(25, 'Fitting ', '15×15 ribbed tees', '', 28, 'Each', 'Each', 1, '', 15, 5),
(26, 'Fitting ', '15 inch 45 ribbed', '', 28, 'Each', 'Each', 1, '', 7, 10),
(27, 'Fitting ', '15 inch rib snap coupler', '', 28, 'Each', 'Each', 1, '', 3, 5),
(28, 'Fitting ', '15 x 12 Tee ribbed', '', 28, 'Each', 'Each', 1, '', 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `first_name`) VALUES
(3, '_SMC'),
(4, 'ABEL'),
(5, 'ABIGAIL'),
(6, 'ALBIN'),
(7, 'ALEXANDER'),
(8, 'ALLEN'),
(9, 'ANDREA'),
(10, 'ANDRES'),
(11, 'TONY P'),
(12, 'TONY Z'),
(13, 'APRIL'),
(14, 'ARIEL'),
(15, 'ASIEL'),
(16, 'BARRETT'),
(17, 'BRAULIO'),
(18, 'BRIAN'),
(19, 'CARLOS V'),
(20, 'CARLOS G'),
(21, 'CHRIS S'),
(22, 'DARYL'),
(23, 'DAVID'),
(24, 'DEBORAH'),
(25, 'DELVIS'),
(26, 'DENIS'),
(27, 'SPEEDO'),
(28, 'DK'),
(29, 'EDUARDO'),
(30, 'EDWARD'),
(31, 'ELI'),
(32, 'ELIZABETH'),
(33, 'ELOY'),
(34, 'EMILIO'),
(35, 'ENIMIAS'),
(36, 'ERICA'),
(37, 'FILIBERTO'),
(38, 'GERARD'),
(39, 'GINA'),
(40, 'HECTOR G'),
(41, 'HECTOR 2'),
(42, 'ISIAH'),
(43, 'COBO'),
(44, 'JAMES'),
(45, 'JAVIER'),
(46, 'JD'),
(47, 'JEFF B'),
(48, 'JEFF G'),
(49, 'JEOVANY'),
(50, 'JERRY'),
(51, 'JESUS G'),
(52, 'RAFAEL'),
(53, 'CLINT'),
(54, 'JOHN N'),
(55, 'ROCCO'),
(56, 'SHRIMP'),
(57, 'JONATHAN F'),
(58, 'JONATHAN C'),
(59, 'JORGE'),
(60, 'JOSE S'),
(61, 'JOSE V'),
(62, 'JOE'),
(63, 'JAY'),
(64, 'JOSUE'),
(65, 'JUAN 1'),
(66, 'JUAN 2'),
(67, 'JC'),
(68, 'JULIO 1'),
(69, 'JULIO 2'),
(70, 'PABLO'),
(71, 'JUSTIN'),
(72, 'KATELIN'),
(73, 'KATHY'),
(74, 'KIMO'),
(75, 'KYLE'),
(76, 'LONI'),
(77, 'WAYNE'),
(78, 'MANUEL'),
(79, 'MARIO'),
(80, 'MARLON'),
(81, 'MARTIN'),
(82, 'MIKE M'),
(83, 'NICOLAS'),
(84, 'OSMAN'),
(85, 'PAOLO'),
(86, 'PATRICK'),
(87, 'PEDRO'),
(88, 'PETE'),
(89, 'PHIL'),
(90, 'RAPHAEL'),
(91, 'RANUEL'),
(92, 'RENE'),
(93, 'ROALDI'),
(94, 'ROBERT'),
(95, 'ROGER'),
(96, 'SEBASTIEN'),
(97, 'SHERVIN'),
(98, 'STEVE B'),
(99, 'STEVE M'),
(100, 'TAMMY'),
(101, 'TERESO'),
(102, 'URI'),
(103, 'VICENTE'),
(104, 'VICTOR'),
(105, 'GUY'),
(106, 'WILLIAM'),
(107, 'WILLIE'),
(108, 'WILMER'),
(109, 'WIMER'),
(110, 'WINSTON'),
(111, 'YESIEL'),
(112, 'YISMEL'),
(113, 'HERMAN');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_change_entries`
--

DROP TABLE IF EXISTS `inventory_change_entries`;
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
  `employee_id` int DEFAULT NULL COMMENT 'Reference to the employee who made the change',
  `updated_by_user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `inventory_change_entries`
--

INSERT INTO `inventory_change_entries` (`id`, `consumable_material_id`, `machine_instance_id`, `item_short_code`, `item_name`, `item_description`, `item_notes`, `normal_item_location`, `reorder_threshold`, `change_date`, `items_added`, `items_removed`, `whole_quantity`, `employee_id`, `updated_by_user_id`) VALUES
(1, 1, NULL, 'fitting-1', '4\" 45 degree fitting', '45 degree fitting, 4\"', '', 2, 0, '2025-03-03 10:08:23', 10, 0, 10, NULL, NULL),
(2, 1, NULL, 'fitting-1', '4\" 45 degree fitting', '45 degree fitting, 4\"', '', 2, 0, '2025-03-03 10:23:41', 0, 11, 0, NULL, NULL),
(3, 1, NULL, 'fitting-1', '4\" 45 degree fitting', '45 degree fitting, 4\"', '', 2, 0, '2025-03-03 10:32:31', 100, 0, 100, NULL, NULL),
(4, 2, NULL, 'pipe-2', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 10:33:14', 0, 15, 0, NULL, NULL),
(5, 2, NULL, 'pipe-2', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 10:51:39', 12, 0, 12, NULL, NULL),
(6, 2, NULL, '', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 12:48:46', 0, 1, 11, NULL, NULL),
(7, 2, NULL, '', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 13:21:23', 0, 1, 10, NULL, NULL),
(8, 2, NULL, 'pipe-2', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 13:39:43', 0, 3, 7, NULL, NULL),
(9, 2, NULL, 'pipe-2', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 13:40:07', 0, 3, 4, NULL, NULL),
(10, 2, NULL, 'pipe-2', '4\" ADS pipe', '20 foot stick of 4\" ADS solid pipe', '', 2, 0, '2025-03-03 13:54:03', 0, 1, 3, NULL, NULL),
(11, 3, NULL, 'Fitting-3', '6” Tee', '6” ADS Tee', '', 3, 0, '2025-03-06 07:50:29', 50, 0, 50, 3, NULL),
(12, 3, NULL, 'Fitting-3', '6” Tee', '6” ADS Tee', '', 3, NULL, '2025-03-06 07:51:01', 5, 0, 55, 3, 1),
(13, 1, NULL, 'Pipe-1', '4\" perf pipe', '20’ stick', '', 11, NULL, '2025-03-07 08:30:22', 0, 82, 18, 3, 2),
(14, 2, NULL, 'pipe-2', '4\" ADS pipe solid', '20 foot stick of 4\" ADS solid pipe', '', 11, NULL, '2025-03-07 08:33:33', 19, 0, 22, 3, 2),
(15, 3, NULL, 'Fitting-3', '4” 45', '4 inch 45', '', 3, NULL, '2025-03-07 08:38:15', 8, 0, 63, 3, 2),
(16, 1, NULL, 'Pipe-1', '4\" sock pipe', '50 foot coil of 4 inch sock pipe', '', 1, NULL, '2025-03-07 09:36:40', 0, 12, 6, 3, 2),
(17, 2, NULL, 'pipe-2', '6” sock pipe', '50 foot coils 6 inch sock pipe', '', 1, NULL, '2025-03-07 09:40:50', 0, 19, 3, 3, 2),
(18, 3, NULL, 'Pipe-3', '4” Tampon pipe', 'French drain sock pipe styrofoam ', '', 1, NULL, '2025-03-07 09:43:31', 0, 54, 9, 3, 2),
(19, 3, NULL, 'Pipe-3', '4” Tampon pipe', 'French drain sock pipe styrofoam ', '', 1, NULL, '2025-03-07 09:43:56', 0, 2, 7, 3, 2),
(20, 4, NULL, 'Pipe-4', '12 inch GA pipe solid', '12 inch Georgia pipe solid', '', 1, NULL, '2025-03-07 09:46:37', 22, 0, 22, 3, 2),
(21, 5, NULL, 'Pipe-5', '24 inch solid pipe', '24 inch ADS solid pipe', '', 3, 0, '2025-03-07 11:41:02', 7, 0, 7, 54, 2),
(22, 7, NULL, 'Shoring-7', '2 foot by 12 foot shoring', 'Shoreguard', '', 4, NULL, '2025-03-07 09:55:18', 105, 0, 105, 3, 2),
(23, 6, NULL, 'Shoring-6', '2 foot by 16 foot Plastic ', 'Shore guard 16', '', 2, NULL, '2025-03-07 09:58:06', 79, 0, 79, 3, 2),
(24, 8, NULL, 'Shoring-8', 'Shoring corner adapter 16', '', '', 4, NULL, '2025-03-07 10:04:52', 1, 0, 1, 3, 2),
(25, 9, NULL, 'Shoring-9', 'Shoring adapter 12 feet', '12 foot shoring corner adapter', '', 4, NULL, '2025-03-07 10:06:48', 7, 0, 7, 3, 2),
(26, 10, NULL, 'Shoring -10', 'Sheet shoring 2 foot by 12', 'Synthetic sheet piling, shoring, flat sided, 2 x 12', '', 5, NULL, '2025-03-07 10:10:27', 40, 0, 40, 3, 2),
(27, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', '', 6, NULL, '2025-03-07 10:15:12', 0, 15, 0, 3, 2),
(28, 12, NULL, 'Fitting-12', '12 inch 90 solid', '', '', 30, NULL, '2025-03-07 11:18:53', 20, 0, 20, 3, 2),
(29, 12, NULL, 'Fitting-12', '12 inch 90 solid', '', '', 30, 0, '2025-03-07 11:27:43', 0, 20, 0, 3, 2),
(30, 12, NULL, 'Fitting-12', '12 inch 90 solid', '', '', 30, NULL, '2025-03-07 11:29:23', 23, 0, 23, 3, 2),
(31, 13, NULL, '12 x 8 reducer-13', '12 x 8 reducer', '', '', 30, NULL, '2025-03-07 11:40:33', 100, 0, 100, 3, 2),
(32, 14, NULL, 'Fitting-14', '12 inch 45', '', '', 30, NULL, '2025-03-07 11:49:32', 20, 0, 20, 3, 2),
(33, 15, NULL, '12 x 8 Wyes-15', '12 x 8 Wyes', '', '', 30, NULL, '2025-03-07 11:52:42', 75, 0, 75, 3, 2),
(34, 1, NULL, 'Pipe-1', '4\" sock pipe', '50 foot coil of 4 inch sock pipe', 'second test', 1, 0, '2025-03-07 12:01:02', 0, 1, 5, 3, 1),
(35, 16, NULL, '12 inch ribbed 90s-1', '12 inch ribbed 90s', '', '', 30, NULL, '2025-03-07 11:55:17', 30, 0, 30, 3, 2),
(36, 17, NULL, 'Fitting-17', '8 x 4 reducer ribbed', '', '', 30, NULL, '2025-03-07 11:58:49', 12, 0, 12, 3, 2),
(37, 18, NULL, 'Fitting-18', '12 inch ribbed tees', '', '', 30, NULL, '2025-03-07 12:01:03', 15, 0, 15, 3, 2),
(38, 19, NULL, 'Fitting-19', '10 inch 45s', '', '', 30, NULL, '2025-03-07 12:02:53', 10, 0, 10, 3, 2),
(39, 20, NULL, 'Fitting-20', '12 x8 Tee', '', '', 29, NULL, '2025-03-07 12:05:40', 1, 0, 1, 3, 2),
(40, 21, NULL, 'Fitting -21', '12 inch, 90', '', '', 29, NULL, '2025-03-07 12:07:27', 28, 0, 28, 3, 2),
(41, 22, NULL, '12 x 12 Wye-22', '12 x 12 Wye', '', '', 29, NULL, '2025-03-07 12:09:14', 96, 0, 96, 3, 2),
(42, 23, NULL, 'Fitting -23', '12 x 12 rib Wyes', '', '', 29, NULL, '2025-03-07 12:13:16', 96, 0, 96, 3, 2),
(43, 24, NULL, 'Fitting -24', '12 x 12 Tee', '', '', 29, NULL, '2025-03-07 12:14:42', 13, 0, 13, 3, 2),
(44, 23, NULL, 'Fitting -23', '12 x 12 rib Wyes', '', '', 29, 0, '2025-03-07 12:19:56', 0, 1, 95, 3, 2),
(45, 23, NULL, 'Fitting -23', '12 x 12 rib Wyes', '', '', 29, NULL, '2025-03-07 12:16:20', 158, 0, 412, 3, 2),
(46, 23, NULL, 'Fitting -23', '12 x 12 rib Wyes', '', '', 29, NULL, '2025-03-07 12:18:15', 0, 158, 254, 3, 2),
(47, 23, NULL, 'Fitting -23', '12 x 12 rib Wyes', '', '', 29, NULL, '2025-03-07 12:23:03', 158, 0, 253, 3, 2),
(48, 23, NULL, 'Fitting -23', '12 x 12 rib Wyes', '', '', 29, NULL, '2025-03-07 12:25:15', 0, 95, 158, 3, 2),
(49, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'just testing by johnny', 6, NULL, '2025-03-07 12:27:41', 1, 0, 1, 3, 1),
(50, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'second johnny test', 6, NULL, '2025-03-07 12:28:21', 14, 0, 15, 3, 1),
(51, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'fff', 6, NULL, '2025-03-07 12:29:26', 0, 1, 14, 3, 1),
(52, 25, NULL, 'Fitting -25', '15×15 ribbed tees', '', '', 28, NULL, '2025-03-07 12:29:58', 15, 0, 15, 3, 2),
(53, 26, NULL, 'Fitting -26', '15 inch 45 ribbed', '', '', 28, NULL, '2025-03-07 12:31:49', 7, 0, 7, 3, 2),
(54, 27, NULL, 'Fitting -27', '15 inch rib snap coupler', '', '', 28, NULL, '2025-03-07 12:34:53', 3, 0, 3, 3, 2),
(55, 28, NULL, 'Fitting -28', '15 x 12 Tee ribbed', '', '', 28, NULL, '2025-03-07 12:37:27', 2, 0, 2, 3, 2),
(56, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'Test test', 6, 0, '2025-03-07 13:19:28', 11, 0, 25, 54, 1),
(57, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'test', 6, NULL, '2025-03-07 13:28:35', 0, 1, 24, 54, 1),
(58, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'test', 6, NULL, '2025-03-07 13:34:06', 0, 10, 14, 3, 1),
(59, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'jj', 6, NULL, '2025-03-07 13:37:12', 1, 0, 15, 3, 1),
(60, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'jhonny test', 6, NULL, '2025-03-07 13:40:36', 0, 1, 14, 54, 1),
(61, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'johnny tesat', 6, NULL, '2025-03-07 13:47:39', 1, 0, 15, 54, 1),
(62, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'johnny test', 6, NULL, '2025-03-07 13:50:54', 0, 1, 14, 54, 1),
(63, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'johnny test', 6, NULL, '2025-03-07 13:52:56', 1, 0, 15, 54, 1),
(64, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'johnny test', 6, NULL, '2025-03-07 13:54:28', 0, 1, 14, 54, 1),
(65, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'Ttt', 6, NULL, '2025-03-07 13:57:01', 1, 0, 15, 54, 2),
(66, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'johnny test', 6, NULL, '2025-03-07 15:21:25', 0, 1, 14, 54, 1),
(67, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'johnny test', 6, NULL, '2025-03-07 15:23:34', 1, 0, 15, 54, 1),
(68, 11, NULL, 'Pipe-11', 'Misc galvanized corrugated pipe', 'Are you miscellaneous link dimensions of galvanized, corrugated pipe, perf, 18, 20,24', 'johnnyt test', 6, NULL, '2025-03-07 15:26:28', 0, 1, 14, 54, 1);

-- --------------------------------------------------------

--
-- Table structure for table `item_locations`
--

DROP TABLE IF EXISTS `item_locations`;
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
(1, 'PR1', 'Rack 1', 'Outdoor Pipe storage rack #1'),
(2, 'PR3', 'Rack 3', 'Outdoor Pipe storage rack #3'),
(3, 'PR2', 'Rack 2', 'Outdoor Pipe storage rack #2'),
(4, 'PR4', 'Rack 4', 'Pipe Rack location 4'),
(5, 'PR5', 'Rack 5', 'Pipe rack location 5'),
(6, 'PR6', 'Rack 6', 'Pipe rack location 6'),
(7, 'PR7', 'Rack 7', 'Pipe rack location 7'),
(8, 'PR8', 'Rack 8', 'Pipe rack location 8'),
(9, 'PR9', 'Rack 9', 'Pipe rack location 9'),
(10, 'PR10', 'Rack 10', 'Pipe rack location 10'),
(11, 'PR11', 'Rack 11', 'Pipe rack location 11'),
(12, 'PR12', 'Rack 12', 'Pipe rack location 12'),
(13, 'PR13', 'Rack 13', 'Pipe rack location 13'),
(14, 'PR14', 'Rack 14', 'Pipe rack location 14'),
(15, 'PR15', 'Rack 15', 'Pipe rack location 15'),
(16, 'PR16', 'Rack 16', 'Pipe rack location 16'),
(17, 'PR17', 'Rack 17', 'Pipe rack location 17'),
(18, 'PR18', 'Rack 18', 'Pipe rack location 18'),
(19, 'PR19', 'Rack 19', 'Pipe rack location 19'),
(20, 'PR20', 'Rack 20', 'Pipe rack location 20'),
(21, 'PR21', 'Rack 21', 'Pipe rack location 21'),
(22, 'PR22', 'Rack 22', 'Pipe rack location 22'),
(23, 'PR23', 'Rack 23', 'Pipe rack location 23'),
(24, 'PR24', 'Rack 24', 'Pipe rack location 24'),
(25, 'PR25', 'Rack 25', 'Pipe rack location 25'),
(26, 'PR26', 'Rack 26', 'Pipe rack location 26'),
(27, 'PR27', 'Rack 27', 'Pipe rack location 27'),
(28, 'PR28', 'Rack 28', 'Pipe rack location 28'),
(29, 'PR29', 'Rack 29', 'Pipe rack location 29'),
(30, 'PR30', 'Rack 30', 'Pipe rack location 30'),
(31, 'PR31', 'Rack 31', 'Pipe rack location 31'),
(32, 'PR32', 'Rack 32', 'Pipe rack location 32'),
(33, 'BAY1', 'Bay 1', 'Garage bay 1'),
(34, 'BAY2', 'Bay 2', 'Garage bay 2'),
(35, 'BAY3', 'Bay 3', 'Garage bay 3'),
(36, 'BAY4', 'Bay 4', 'Garage bay 4'),
(37, 'BAY5', 'Bay 5', 'Garage bay 5'),
(38, 'BAY6', 'Bay 6', 'Garage bay 6'),
(39, 'BAY7', 'Bay 7', 'Garage bay 7');

-- --------------------------------------------------------

--
-- Table structure for table `machine_item_instances`
--

DROP TABLE IF EXISTS `machine_item_instances`;
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

DROP TABLE IF EXISTS `reusable_item_types`;
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

DROP TABLE IF EXISTS `users`;
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
(3, 'KimO', 'KO', '$2y$10$KVALJlJSf.A.RegkKxZBxeYAB20TVKGIHdgbmcGlFtPdDW6iJ85RC', 'user', '2025-03-04 13:41:39', '2025-03-07 17:31:32');

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
  ADD KEY `fk_employee_id` (`employee_id`),
  ADD KEY `fk_updated_by_user_id` (`updated_by_user_id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `inventory_change_entries`
--
ALTER TABLE `inventory_change_entries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `item_locations`
--
ALTER TABLE `item_locations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

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
  ADD CONSTRAINT `fk_updated_by_user_id` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
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
