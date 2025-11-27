-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 27, 2025 at 03:23 PM
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
-- Database: `catering_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `LogID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `UserType` enum('admin','client') DEFAULT NULL,
  `Action` varchar(100) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`LogID`, `UserID`, `UserType`, `Action`, `Description`, `IPAddress`, `CreatedAt`) VALUES
(1, 10, 'client', 'registration', 'New client registered with email verification', '::1', '2025-10-25 07:41:26'),
(2, 11, 'client', 'registration', 'New client registered with email verification', '::1', '2025-10-25 07:44:16'),
(3, 1, 'admin', 'login', 'Admin logged in successfully', '::1', '2025-11-06 04:23:50'),
(4, 1, 'admin', 'client_created', 'Created client #12', '::1', '2025-11-06 04:56:29'),
(5, 1, 'admin', 'booking_created', 'Created booking #1', '::1', '2025-11-06 04:56:29'),
(6, 1, 'admin', 'quotation_created', 'Created quotation #1 for booking #2', '::1', '2025-11-06 05:05:34'),
(7, 1, 'admin', 'menu_created', 'Created menu item #9 - Dumplings', '::1', '2025-11-06 05:06:58'),
(8, 1, 'admin', 'client_created', 'Created client #13', '::1', '2025-11-06 05:07:58'),
(9, 1, 'admin', 'quotation_updated', 'Updated quotation #1', '::1', '2025-11-06 05:08:27'),
(10, 1, 'admin', 'quotation_created', 'Created quotation #2 for booking #3', '::1', '2025-11-06 11:08:58'),
(11, 1, 'admin', 'menu_deleted', 'Deleted menu item #8', '::1', '2025-11-20 07:58:46'),
(12, 1, 'admin', 'menu_deleted', 'Deleted menu item #5', '::1', '2025-11-20 07:58:54'),
(13, 1, 'admin', 'menu_created', 'Created menu item #10 - Lechon', '::1', '2025-11-20 09:50:13'),
(14, 1, 'admin', 'menu_updated', 'Updated menu item #2', '::1', '2025-11-20 12:22:02'),
(15, 1, 'admin', 'menu_updated', 'Updated menu item #2', '::1', '2025-11-20 12:22:14'),
(16, 1, 'admin', 'menu_updated', 'Updated menu item #2', '::1', '2025-11-20 12:22:39'),
(17, 1, 'admin', 'menu_updated', 'Updated menu item #2', '::1', '2025-11-20 12:23:48'),
(18, 1, 'admin', 'menu_updated', 'Updated menu item #2', '::1', '2025-11-20 12:24:05'),
(19, 1, 'admin', 'menu_updated', 'Updated menu item #2', '::1', '2025-11-20 12:24:35'),
(20, 1, 'admin', 'menu_updated', 'Updated menu item #2', '::1', '2025-11-20 12:26:27'),
(21, 1, 'admin', 'menu_updated', 'Updated menu item #2', '::1', '2025-11-20 12:36:16'),
(22, 1, 'admin', 'menu_updated', 'Updated menu item #2', '::1', '2025-11-20 12:36:44'),
(23, 1, 'admin', 'menu_updated', 'Updated menu item #9', '::1', '2025-11-20 12:40:10'),
(24, 1, 'admin', 'menu_updated', 'Updated menu item #9', '::1', '2025-11-20 12:40:27'),
(25, 4, 'client', 'booking_created', 'Created booking #4', '::1', '2025-11-21 13:07:46'),
(26, 1, 'admin', 'quotation_updated', 'Updated quotation #2', '::1', '2025-11-21 13:14:13'),
(27, 4, 'client', 'booking_created', 'Created booking #5 with quotation #3', '::1', '2025-11-21 13:15:22'),
(28, 4, 'client', 'booking_created', 'Created booking #6 with quotation #4', '::1', '2025-11-21 13:15:54'),
(29, 4, 'client', 'booking_created', 'Created booking #7 with quotation #5', '::1', '2025-11-21 14:13:38'),
(30, 4, 'client', 'booking_created', 'Created booking #8 with quotation #6', '::1', '2025-11-21 14:13:38'),
(31, 4, 'client', 'order_created', 'Created food order #1 with booking #9 and quotation #7', '::1', '2025-11-21 14:38:44'),
(32, 4, 'client', 'booking_created', 'Created booking #10 with quotation #8', '::1', '2025-11-21 14:39:12'),
(33, 4, 'client', 'booking_created', 'Created booking #11 with quotation #9', '::1', '2025-11-22 03:53:42'),
(34, 4, 'client', 'booking_created', 'Created booking #12 with quotation #10', '::1', '2025-11-22 03:53:49'),
(35, 4, 'client', 'order_created', 'Created food order #2 with booking #13 and quotation #11', '::1', '2025-11-22 03:58:11'),
(36, 1, 'admin', 'menu_created', 'Created menu item #11 - Siomai', '::1', '2025-11-22 03:58:59'),
(37, 4, 'client', 'booking_created', 'Created booking #14 with quotation #12', '::1', '2025-11-22 04:00:53'),
(38, 4, 'client', 'booking_created', 'Created booking #15 with quotation #13', '::1', '2025-11-22 04:01:03'),
(39, 4, 'client', 'booking_created', 'Created booking #16 with quotation #14', '::1', '2025-11-22 04:10:56'),
(40, 4, 'client', 'booking_created', 'Created booking #17 with quotation #15', '::1', '2025-11-22 04:11:44'),
(41, 4, 'client', 'booking_created', 'Created booking #18 with quotation #16', '::1', '2025-11-22 04:11:54'),
(42, 4, 'client', 'order_created', 'Created food order #3 with booking #19 and quotation #17. Payment: GCash', '::1', '2025-11-22 04:13:19'),
(43, 4, 'client', 'order_created', 'Created food order #4 with booking #20 and quotation #18. Payment: GCash', '::1', '2025-11-22 04:14:26'),
(44, 4, 'client', 'booking_created', 'Created booking #21 with quotation #19', '::1', '2025-11-22 04:18:49'),
(45, 4, 'client', 'booking_created', 'Created booking #22 with quotation #20', '::1', '2025-11-22 04:22:45'),
(46, 4, 'client', 'booking_created', 'Created booking #23 with quotation #21', '::1', '2025-11-22 04:25:37'),
(47, 1, 'client', 'booking_created', 'Created booking #24 with quotation #22', '::1', '2025-11-23 12:58:45'),
(48, 1, 'client', 'booking_cancelled', 'Cancelled booking #24', '::1', '2025-11-23 15:39:52'),
(49, 1, 'client', 'booking_created', 'Created booking #25 with quotation #23', '::1', '2025-11-23 15:44:15'),
(50, 1, 'client', 'booking_cancelled', 'Cancelled booking #25', '::1', '2025-11-23 15:44:27'),
(51, 1, 'client', 'booking_created', 'Created booking #26 with quotation #24', '::1', '2025-11-23 15:48:21'),
(52, 1, 'client', 'booking_cancelled', 'Cancelled booking #26', '::1', '2025-11-23 15:48:35'),
(53, 14, 'client', 'registration', 'New client registered with email verification', '::1', '2025-11-23 15:57:21'),
(54, 1, 'client', 'booking_created', 'Created booking #27 with quotation #25', '::1', '2025-11-23 16:56:02'),
(55, 1, 'admin', 'booking_updated', 'Updated booking #27', '::1', '2025-11-23 17:02:11'),
(56, 1, 'client', 'order_created', 'Created food order #5 with booking #28 and quotation #26. Payment: GCash', '::1', '2025-11-23 17:17:29'),
(57, 1, 'client', 'booking_created', 'Created booking #29 with quotation #27', '::1', '2025-11-25 15:27:06'),
(58, 1, 'admin', 'booking_updated', 'Updated booking #29', '::1', '2025-11-25 15:32:08'),
(59, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #29', '::1', '2025-11-25 15:46:10'),
(60, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #29', '::1', '2025-11-25 15:46:21'),
(61, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #29', '::1', '2025-11-25 15:50:00'),
(62, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #29', '::1', '2025-11-25 15:50:09'),
(63, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #29', '::1', '2025-11-25 15:53:10'),
(64, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #29', '::1', '2025-11-25 15:53:22'),
(65, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #29', '::1', '2025-11-25 16:13:25'),
(66, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #29', '::1', '2025-11-25 16:19:49'),
(67, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #29', '::1', '2025-11-25 16:19:59'),
(68, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #29', '::1', '2025-11-25 16:20:08'),
(69, 1, 'admin', 'booking_updated', 'Updated booking #29', '::1', '2025-11-26 10:31:17'),
(70, 1, 'admin', 'quotation_updated', 'Updated quotation #27', '::1', '2025-11-26 10:33:43'),
(71, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #29', '::1', '2025-11-26 10:41:14'),
(72, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #29', '::1', '2025-11-26 10:45:10'),
(73, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #29', '::1', '2025-11-26 11:17:04'),
(74, 1, 'client', 'booking_created', 'Created booking #30 with quotation #28', '::1', '2025-11-26 11:28:02'),
(75, 1, 'admin', 'booking_updated', 'Updated booking #30', '::1', '2025-11-26 11:42:02'),
(76, 1, 'admin', 'booking_updated', 'Updated booking #30', '::1', '2025-11-26 11:54:42'),
(77, 1, 'admin', 'quotation_auto_approved', 'Auto-approved quotation for booking #30', '::1', '2025-11-26 11:54:52'),
(78, 1, 'admin', 'booking_updated', 'Updated booking #30', '::1', '2025-11-26 11:54:52'),
(79, 1, 'admin', 'quotation_updated', 'Updated quotation #28', '::1', '2025-11-26 11:57:11'),
(80, 1, 'client', 'booking_created', 'Created booking #31 with quotation #29', '::1', '2025-11-26 11:57:52'),
(81, 1, 'admin', 'quotation_auto_approved', 'Auto-approved quotation for booking #31', '::1', '2025-11-26 11:58:10'),
(82, 1, 'admin', 'booking_updated', 'Updated booking #31', '::1', '2025-11-26 11:58:10'),
(83, 1, 'client', 'booking_created', 'Created booking #32 with quotation #30', '::1', '2025-11-26 13:20:58'),
(84, 1, 'admin', 'booking_updated', 'Updated booking #32', '::1', '2025-11-26 13:46:36'),
(85, 1, 'admin', 'quotation_updated', 'Updated quotation #30', '::1', '2025-11-26 14:17:50'),
(86, 1, 'admin', 'quotation_updated', 'Updated quotation #30', '::1', '2025-11-26 14:25:10'),
(87, 1, 'admin', 'quotation_updated', 'Updated quotation #30', '::1', '2025-11-26 15:40:51'),
(88, 1, 'admin', 'quotation_updated', 'Updated quotation #30', '::1', '2025-11-26 15:41:00'),
(89, 1, 'admin', 'quotation_updated', 'Updated quotation #30', '::1', '2025-11-26 15:41:16'),
(90, 1, 'admin', 'booking_auto_confirmed', 'Auto-confirmed booking when quotation #30 was approved', '::1', '2025-11-26 15:41:22'),
(91, 1, 'admin', 'quotation_updated', 'Updated quotation #30', '::1', '2025-11-26 15:41:22'),
(92, 1, 'admin', 'booking_updated', 'Updated booking #32', '::1', '2025-11-26 15:41:42'),
(93, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #32', '::1', '2025-11-26 16:49:12'),
(94, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #30', '::1', '2025-11-26 17:55:32'),
(95, 1, 'client', 'payment_method_updated', 'Updated payment method to GCash for booking #30', '::1', '2025-11-26 18:16:35'),
(96, 1, 'admin', 'quotation_updated', 'Updated quotation #26', '::1', '2025-11-26 18:17:48'),
(97, 1, 'admin', 'booking_auto_confirmed', 'Auto-confirmed booking when quotation #26 was approved', '::1', '2025-11-26 18:18:00'),
(98, 1, 'admin', 'quotation_updated', 'Updated quotation #26', '::1', '2025-11-26 18:18:00'),
(99, 1, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #30', '::1', '2025-11-26 19:08:35'),
(100, 1, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #30', '::1', '2025-11-26 19:08:40'),
(101, 1, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #30', '::1', '2025-11-26 19:09:53'),
(102, 1, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #30', '::1', '2025-11-26 19:09:58'),
(103, 1, 'client', 'payment_method_selected', 'Selected payment method: Bank Transfer for booking #30', '::1', '2025-11-26 19:16:44'),
(104, 1, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #31', '::1', '2025-11-26 19:18:05'),
(105, 1, 'admin', 'payment_confirmed', 'Confirmed payment for booking #30 via Bank Transfer', '::1', '2025-11-26 19:24:10'),
(106, 1, 'client', 'payment_method_selected', 'Selected payment method: Cash for booking #32', '::1', '2025-11-26 19:39:39'),
(107, 1, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #29', '::1', '2025-11-26 20:21:40'),
(108, 1, 'client', 'payment_method_selected', 'Selected payment method: Bank Transfer for booking #29', '::1', '2025-11-26 20:22:06'),
(109, 1, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #29', '::1', '2025-11-26 20:23:40'),
(110, 1, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #29', '::1', '2025-11-26 20:24:30'),
(111, 1, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #29', '::1', '2025-11-26 20:24:47'),
(112, 11, 'client', 'booking_created', 'Created booking #33 with quotation #31', '::1', '2025-11-27 00:40:45'),
(113, 1, 'admin', 'booking_auto_confirmed', 'Auto-confirmed booking when quotation #31 was approved', '::1', '2025-11-27 00:43:02'),
(114, 1, 'admin', 'quotation_updated', 'Updated quotation #31', '::1', '2025-11-27 00:43:02'),
(115, 11, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #33', '::1', '2025-11-27 01:06:25'),
(116, 11, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #33', '::1', '2025-11-27 01:22:54'),
(117, 11, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #33', '::1', '2025-11-27 01:31:39'),
(118, 11, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #33', '::1', '2025-11-27 01:38:40'),
(119, 11, 'client', 'gcash_reference_submitted', 'Submitted GCash reference number for booking #33', '::1', '2025-11-27 01:38:44'),
(120, 11, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #33', '::1', '2025-11-27 01:40:11'),
(121, 11, 'client', 'booking_created', 'Created booking #34 with quotation #32', '::1', '2025-11-27 01:43:37'),
(122, 1, 'admin', 'quotation_updated', 'Updated quotation #32', '::1', '2025-11-27 01:45:14'),
(123, 1, 'admin', 'booking_auto_confirmed', 'Auto-confirmed booking when quotation #32 was approved', '::1', '2025-11-27 01:45:27'),
(124, 1, 'admin', 'quotation_updated', 'Updated quotation #32', '::1', '2025-11-27 01:45:27'),
(125, 11, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #34', '::1', '2025-11-27 01:46:00'),
(126, 11, 'client', 'gcash_reference_submitted', 'Submitted GCash reference number for booking #34', '::1', '2025-11-27 01:46:17'),
(127, 11, 'client', 'payment_method_selected', 'Selected payment method: Bank Transfer for booking #34', '::1', '2025-11-27 01:51:57'),
(128, 11, 'client', 'booking_created', 'Created booking #35 with quotation #33', '::1', '2025-11-27 01:56:34'),
(129, 1, 'admin', 'booking_auto_confirmed', 'Auto-confirmed booking when quotation #33 was approved', '::1', '2025-11-27 01:56:53'),
(130, 1, 'admin', 'quotation_updated', 'Updated quotation #33', '::1', '2025-11-27 01:56:53'),
(131, 11, 'client', 'payment_method_selected', 'Selected payment method: Bank Transfer for booking #35', '::1', '2025-11-27 01:57:20'),
(132, 11, 'client', 'payment_method_selected', 'Selected payment method: Bank Transfer for booking #35', '::1', '2025-11-27 01:58:11'),
(133, 11, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #34', '::1', '2025-11-27 01:59:37'),
(134, 11, 'client', 'gcash_reference_submitted', 'Submitted GCash reference number for booking #34', '::1', '2025-11-27 01:59:41'),
(135, 11, 'client', 'payment_method_selected', 'Selected payment method: Bank Transfer for booking #34', '::1', '2025-11-27 02:24:05'),
(136, 11, 'client', 'bank_transfer_submitted', 'Submitted bank transfer reference for booking #34 - Ref: 12345', '::1', '2025-11-27 02:24:22'),
(137, 1, 'admin', 'payment_confirmed', 'Confirmed payment for booking #34 via Bank Transfer', '::1', '2025-11-27 02:39:39'),
(138, 11, 'client', 'payment_method_selected', 'Selected payment method: Bank Transfer for booking #35', '::1', '2025-11-27 02:45:22'),
(139, 1, 'admin', 'payment_confirmed', 'Confirmed payment for booking #29 via GCash', '::1', '2025-11-27 05:03:47'),
(140, 11, 'client', 'order_created', 'Created food order #6 with booking #36 and quotation #34. Payment: GCash', '::1', '2025-11-27 05:14:29'),
(141, 11, 'client', 'booking_created', 'Created booking #37 with quotation #35', '::1', '2025-11-27 05:15:29'),
(142, 1, 'admin', 'booking_auto_confirmed', 'Auto-confirmed booking when quotation #35 was approved', '::1', '2025-11-27 05:15:41'),
(143, 1, 'admin', 'quotation_updated', 'Updated quotation #35', '::1', '2025-11-27 05:15:41'),
(144, 11, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #37', '::1', '2025-11-27 05:16:05'),
(145, 11, 'client', 'gcash_reference_submitted', 'Submitted GCash reference number for booking #37', '::1', '2025-11-27 05:16:12'),
(146, 11, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #37', '::1', '2025-11-27 05:28:42'),
(147, 11, 'client', 'booking_created', 'Created booking #38 with quotation #36', '::1', '2025-11-27 05:44:21'),
(148, 1, 'admin', 'booking_auto_confirmed', 'Auto-confirmed booking when quotation #36 was approved', '::1', '2025-11-27 05:44:42'),
(149, 1, 'admin', 'quotation_updated', 'Updated quotation #36', '::1', '2025-11-27 05:44:42'),
(150, 11, 'client', 'payment_method_selected', 'Selected payment method: Bank Transfer for booking #38', '::1', '2025-11-27 05:44:54'),
(151, 11, 'client', 'payment_method_selected', 'Selected payment method: Bank Transfer for booking #35', '::1', '2025-11-27 06:01:11'),
(152, 11, 'client', 'order_created', 'Created food order #7 with booking #39 and quotation #37. Payment: GCash', '::1', '2025-11-27 06:08:06'),
(153, 1, 'admin', 'booking_auto_confirmed', 'Auto-confirmed booking when quotation #37 was approved', '::1', '2025-11-27 06:09:11'),
(154, 1, 'admin', 'quotation_updated', 'Updated quotation #37', '::1', '2025-11-27 06:09:11'),
(155, 11, 'client', 'order_created', 'Created food order #8 with booking #40 and quotation #38. Payment: GCash', '::1', '2025-11-27 06:29:49'),
(156, 11, 'client', 'order_created', 'Created food order #9 with booking #41 and quotation #39. Payment: GCash', '::1', '2025-11-27 07:50:20'),
(157, 11, 'client', 'order_created', 'Created food order #10 with booking #42 and quotation #40. Payment: GCash', '::1', '2025-11-27 08:00:40'),
(158, 11, 'client', 'order_created', 'Created food order #11 with booking #43 and quotation #41. Payment: Bank Transfer', '::1', '2025-11-27 08:02:35'),
(159, 11, 'client', 'booking_created', 'Created booking #44 with quotation #42', '::1', '2025-11-27 08:05:56'),
(160, 1, 'admin', 'booking_auto_confirmed', 'Auto-confirmed booking when quotation #42 was approved', '::1', '2025-11-27 08:06:26'),
(161, 1, 'admin', 'quotation_updated', 'Updated quotation #42', '::1', '2025-11-27 08:06:26'),
(162, 11, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #44', '::1', '2025-11-27 08:07:02'),
(163, 11, 'client', 'payment_method_selected', 'Selected payment method: GCash for booking #44', '::1', '2025-11-27 08:08:18'),
(164, 11, 'client', 'order_created', 'Created food order #12 with booking #45 and quotation #43. Payment: GCash', '::1', '2025-11-27 08:11:48'),
(165, 11, 'client', 'payment_reference_added', 'Added GCash reference for order #12 (Booking #45)', '::1', '2025-11-27 08:11:56'),
(166, 11, 'client', 'order_created', 'Created food order #13 with booking #46 and quotation #44. Payment: GCash', '::1', '2025-11-27 08:58:43'),
(167, 11, 'client', 'payment_reference_added', 'Added GCash reference for order #13 (Booking #46)', '::1', '2025-11-27 08:59:00'),
(168, 11, 'client', 'order_created', 'Created food order #14 with booking #47 and quotation #45. Payment: GCash', '::1', '2025-11-27 10:03:20'),
(169, 11, 'client', 'payment_reference_added', 'Added GCash reference for order #14 (Booking #47)', '::1', '2025-11-27 10:03:28'),
(170, 11, 'client', 'order_created', 'Created food order #15 with booking #48 and quotation #46. Payment: Bank Transfer', '::1', '2025-11-27 10:03:54'),
(171, 11, 'client', 'order_created', 'Created food order #16 with booking #49 and quotation #47. Payment: Cash', '::1', '2025-11-27 10:14:50');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `AdminID` int(11) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `user_role` enum('admin','client') NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`AdminID`, `Name`, `Email`, `Password`, `user_role`) VALUES
(1, 'AdminSofia', 'adminsofia@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `agreement`
--

CREATE TABLE `agreement` (
  `AgreementID` int(11) NOT NULL,
  `BookingID` int(11) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `ContractFile` varchar(255) DEFAULT NULL,
  `SignedDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `BookingID` int(11) NOT NULL,
  `ClientID` int(11) DEFAULT NULL,
  `EventType` varchar(50) DEFAULT NULL,
  `DateBooked` date DEFAULT NULL,
  `EventDate` date DEFAULT NULL,
  `EventLocation` text DEFAULT NULL,
  `NumberOfGuests` int(11) DEFAULT NULL,
  `SpecialRequests` text DEFAULT NULL,
  `Status` enum('Pending','Confirmed','Completed','Cancelled') DEFAULT NULL,
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `PaymentStatus` enum('Pending Payment','Processing','Paid','Failed') DEFAULT 'Pending Payment',
  `PaymentMethod` enum('Cash','GCash','Bank Transfer') DEFAULT NULL,
  `BankReferenceNumber` varchar(100) DEFAULT NULL,
  `BankSenderName` varchar(255) DEFAULT NULL,
  `PaymentDate` datetime DEFAULT NULL,
  `GCashReference` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`BookingID`, `ClientID`, `EventType`, `DateBooked`, `EventDate`, `EventLocation`, `NumberOfGuests`, `SpecialRequests`, `Status`, `TotalAmount`, `CreatedAt`, `UpdatedAt`, `PaymentStatus`, `PaymentMethod`, `BankReferenceNumber`, `BankSenderName`, `PaymentDate`, `GCashReference`) VALUES
(1, 12, 'Birthday', '2025-11-06', '2025-12-28', 'Lian,Batangas', 25, '', 'Pending', 0.00, '2025-11-06 04:56:29', '2025-11-06 04:56:29', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(2, 12, 'Birthday', '2025-11-06', '2025-11-28', 'Lian,Batangas', 25, '', 'Confirmed', 7500.00, '2025-11-06 05:05:34', '2025-11-06 05:08:27', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(3, 6, 'Corporate', '2025-11-06', '2026-01-06', 'Nasugbu, Batangas', 35, '', 'Confirmed', 10000.00, '2025-11-06 11:08:58', '2025-11-21 13:14:13', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(4, 4, 'Corporate Signature Buffet', '2025-11-21', '2026-01-29', 'Lian, Batangas', 30, 'None', 'Pending', 26970.00, '2025-11-21 13:07:46', '2025-11-21 13:07:46', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(5, 4, NULL, '2025-11-21', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-21 13:15:22', '2025-11-21 13:15:22', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(6, 4, NULL, '2025-11-21', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-21 13:15:54', '2025-11-21 13:15:54', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(7, 4, NULL, '2025-11-21', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-21 14:13:38', '2025-11-21 14:13:38', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(8, 4, 'Corporate Signature Buffet', '2025-11-21', '2025-12-28', 'Lian, Batangas', 30, 'None', 'Pending', 26970.00, '2025-11-21 14:13:38', '2025-11-21 14:13:38', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(9, 4, 'Food Order', '2025-11-21', '2025-11-28', 'To Be Determined', 0, 'Food Menu Order:\n- Grilled Fish (medium) x1\n', 'Pending', 392.00, '2025-11-21 14:38:44', '2025-11-21 14:38:44', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(10, 4, NULL, '2025-11-21', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-21 14:39:12', '2025-11-21 14:39:12', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(11, 4, NULL, '2025-11-22', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-22 03:53:42', '2025-11-22 03:53:42', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(12, 4, NULL, '2025-11-22', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-22 03:53:49', '2025-11-22 03:53:49', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(13, 4, 'Food Order', '2025-11-22', '2025-11-29', 'To Be Determined', 0, 'Food Menu Order:\n- Grilled Fish (small) x1\n', 'Pending', 280.00, '2025-11-22 03:58:11', '2025-11-22 03:58:11', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(14, 4, NULL, '2025-11-22', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-22 04:00:53', '2025-11-22 04:00:53', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(15, 4, NULL, '2025-11-22', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-22 04:01:03', '2025-11-22 04:01:03', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(16, 4, NULL, '2025-11-22', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-22 04:10:56', '2025-11-22 04:10:56', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(17, 4, NULL, '2025-11-22', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-22 04:11:44', '2025-11-22 04:11:44', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(18, 4, NULL, '2025-11-22', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-22 04:11:54', '2025-11-22 04:11:54', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(19, 4, 'Food Order', '2025-11-22', '2025-11-29', 'To Be Determined', 0, 'Food Menu Order (Payment: GCash):\n- Siomai (small) x1\n', 'Pending', 200.00, '2025-11-22 04:13:19', '2025-11-22 04:13:19', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(20, 4, 'Food Order', '2025-11-22', '2025-11-29', 'To Be Determined', 0, 'Food Menu Order (Payment: GCash):\n- Siomai (small) x1\n', 'Pending', 200.00, '2025-11-22 04:14:26', '2025-11-22 04:14:26', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(21, 4, NULL, '2025-11-22', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-22 04:18:49', '2025-11-22 04:18:49', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(22, 4, NULL, '2025-11-22', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-22 04:22:45', '2025-11-22 04:22:45', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(23, 4, NULL, '2025-11-22', NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-22 04:25:37', '2025-11-22 04:25:37', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(24, 1, 'Birthday Package', '2025-11-23', '2025-11-30', 'Secret', 50, 'Payment Method: GCash\n\nnone', 'Cancelled', 22500.00, '2025-11-23 12:58:45', '2025-11-23 15:39:52', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(25, 1, 'Wedding Package', '2025-11-23', '2025-11-30', 'secret', 100, 'Payment Method: Cash\n\n', 'Cancelled', 55000.00, '2025-11-23 15:44:15', '2025-11-23 15:44:27', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(26, 1, 'Birthday Package', '2025-11-23', '2025-11-30', 'secret', 60, 'Payment Method: Cash\n\n', 'Cancelled', 27000.00, '2025-11-23 15:48:21', '2025-11-23 15:48:35', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(27, 1, 'Wedding', '2025-11-24', '2025-11-30', 'Secret', 50, 'Payment Method: Cash\n\nnone', 'Confirmed', 22500.00, '2025-11-23 16:56:02', '2025-11-23 17:02:11', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(28, 1, 'Food Order', '2025-11-24', '2025-11-30', 'To Be Determined', 0, 'Food Menu Order (Payment: GCash):\n- Lumpiang Shanghai (small) x1\n', 'Confirmed', 2200.00, '2025-11-23 17:17:29', '2025-11-26 18:18:00', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(29, 1, 'Wedding', '2025-11-25', '2025-12-02', 'secret', 30, 'Payment Method: GCash\n\nla naman', 'Confirmed', 26970.00, '2025-11-25 15:27:06', '2025-11-27 05:03:47', 'Paid', 'GCash', NULL, NULL, '2025-11-27 13:03:47', NULL),
(30, 1, 'Wedding', '2025-11-26', '2025-12-03', 'secrettt', 130, 'Payment Method: GCash\n\nnone', 'Confirmed', 71500.00, '2025-11-26 11:28:02', '2025-11-26 19:24:10', 'Paid', 'Bank Transfer', NULL, NULL, '2025-11-27 03:24:10', NULL),
(31, 1, 'Wedding', '2025-11-26', '2025-12-04', 'secret', 50, 'what', 'Confirmed', 22500.00, '2025-11-26 11:57:52', '2025-11-26 19:18:05', 'Processing', 'GCash', NULL, NULL, NULL, NULL),
(32, 1, 'Wedding', '2025-11-26', '2025-12-07', 'secrettttt', 100, 'Payment Method: GCash\n\nwala lang', 'Confirmed', 60200.00, '2025-11-26 13:20:58', '2025-11-26 19:39:39', 'Processing', 'Cash', NULL, NULL, NULL, NULL),
(33, 11, 'Wedding Package', '2025-11-27', '2025-12-13', 'wawa', 101, 'pusa', 'Confirmed', 55550.00, '2025-11-27 00:40:45', '2025-11-27 05:40:45', 'Processing', 'GCash', NULL, NULL, NULL, '1234567'),
(34, 11, 'Birthday Package', '2025-11-27', '2025-12-14', 'wawa', 51, 'pusa', 'Confirmed', 22951.00, '2025-11-27 01:43:37', '2025-11-27 05:40:45', 'Paid', 'Bank Transfer', '12345', 'lei', '2025-11-27 10:39:39', '1234567'),
(35, 11, 'Christening Package', '2025-11-27', '2025-12-15', 'wawa', 91, 'pusa', 'Confirmed', 40950.00, '2025-11-27 01:56:34', '2025-11-27 06:01:16', 'Processing', 'Bank Transfer', '333333', 'lei', '2025-11-27 14:01:16', NULL),
(36, 11, 'Food Order', '2025-11-27', '2025-12-04', 'To Be Determined', 0, 'Food Menu Order (Payment: GCash):\n- Dumplings (small) x1\n', 'Pending', 200.00, '2025-11-27 05:14:29', '2025-11-27 05:14:29', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(37, 11, 'Enchanted Vows Catering', '2025-11-27', '2025-12-25', 'wawa', 31, 'pusa', 'Confirmed', 44950.00, '2025-11-27 05:15:29', '2025-11-27 05:28:59', 'Processing', 'GCash', NULL, NULL, '2025-11-27 13:28:59', '111111111'),
(38, 11, 'Wedding Package', '2025-11-27', '2025-12-31', 'wawa', 101, 'pusa', 'Confirmed', 55550.00, '2025-11-27 05:44:21', '2025-11-27 05:45:00', 'Processing', 'Bank Transfer', '222222', 'lei', '2025-11-27 13:45:00', NULL),
(39, 11, 'Food Order', '2025-11-27', '2025-12-04', 'To Be Determined', 0, 'Food Menu Order (Payment: GCash):\n- Dumplings (small) x1\n', 'Confirmed', 200.00, '2025-11-27 06:08:06', '2025-11-27 06:09:11', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(40, 11, 'Food Order', '2025-11-27', '2025-12-04', 'To Be Determined', 0, 'Food Menu Order (Payment: GCash):\n- Lechon (small) x1\n', 'Pending', 2500.00, '2025-11-27 06:29:49', '2025-11-27 06:29:49', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(41, 11, 'Food Order', '2025-11-27', '2025-12-04', 'To Be Determined', 0, 'Food Menu Order (Payment: GCash):\n- Dumplings (small) x1\n- Lechon (small) x1\n', 'Pending', 2700.00, '2025-11-27 07:50:20', '2025-11-27 07:50:20', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(42, 11, 'Food Order', '2025-11-27', '2025-12-04', 'To Be Determined', 0, 'Food Menu Order (Payment: GCash):\n- Grilled Fish (small) x1\n- Lechon (small) x1\n', 'Pending', 2780.00, '2025-11-27 08:00:40', '2025-11-27 08:00:40', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(43, 11, 'Food Order', '2025-11-27', '2025-12-04', 'To Be Determined', 0, 'Food Menu Order (Payment: Bank Transfer):\n- Lechon Kawali (small) x1\n\nBank Transfer Details:\nCardholder: lei\nCard (last 4): 1111', 'Pending', 320.00, '2025-11-27 08:02:35', '2025-11-27 08:02:35', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(44, 11, 'Team Building Package', '2025-11-27', '2025-12-30', 'wawa', 31, 'pusacat', 'Confirmed', 12400.00, '2025-11-27 08:05:56', '2025-11-27 08:08:25', 'Processing', 'GCash', NULL, NULL, '2025-11-27 16:08:25', '1111111'),
(45, 11, 'Food Order', '2025-11-27', '2025-12-04', 'To Be Determined', 0, 'Food Menu Order (Payment: GCash):\n- Lechon (small) x1\n- Lechon Kawali (small) x1\n', 'Pending', 2820.00, '2025-11-27 08:11:48', '2025-11-27 08:11:56', 'Processing', 'GCash', NULL, NULL, '2025-11-27 16:11:56', '5555aaaa'),
(46, 11, 'Food Order', '2025-11-27', '2025-12-04', 'To Be Determined', 0, 'Food Menu Order (Payment: GCash):\n- Grilled Fish (small) x1\n- Lumpiang Shanghai (small) x1\n', 'Pending', 480.00, '2025-11-27 08:58:43', '2025-11-27 08:59:00', 'Processing', 'GCash', NULL, NULL, '2025-11-27 16:59:00', '12345678'),
(47, 11, 'Food Order', '2025-11-27', '2025-12-04', 'To Be Determined', 0, 'Food Menu Order (Payment: GCash):\n- Grilled Fish (small) x1\n', 'Pending', 280.00, '2025-11-27 10:03:20', '2025-11-27 10:03:28', 'Processing', 'GCash', NULL, NULL, '2025-11-27 18:03:28', '1234567'),
(48, 11, 'Food Order', '2025-11-27', '2025-12-04', 'To Be Determined', 0, 'Food Menu Order (Payment: Bank Transfer):\n- Lumpiang Shanghai (small) x1\n\nBank Transfer Details:\nCardholder: lei\nCard (last 4): 1111', 'Pending', 200.00, '2025-11-27 10:03:54', '2025-11-27 10:03:54', 'Pending Payment', NULL, NULL, NULL, NULL, NULL),
(49, 11, 'Food Order', '2025-11-27', '2025-12-04', 'To Be Determined', 0, 'Food Menu Order (Payment: Cash):\n- Grilled Fish (small) x1\n- Dumplings (small) x1\n', 'Pending', 480.00, '2025-11-27 10:14:50', '2025-11-27 10:14:50', 'Pending Payment', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `booking_menu`
--

CREATE TABLE `booking_menu` (
  `BookingID` int(11) NOT NULL,
  `MenuID` int(11) NOT NULL,
  `Quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_package`
--

CREATE TABLE `booking_package` (
  `BookingID` int(11) NOT NULL,
  `PackageID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_service`
--

CREATE TABLE `booking_service` (
  `BookingID` int(11) NOT NULL,
  `ServiceID` int(11) NOT NULL,
  `Quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `CartID` int(11) NOT NULL,
  `ClientID` int(11) NOT NULL,
  `ItemType` enum('menu','package') NOT NULL DEFAULT 'menu',
  `ItemName` varchar(255) NOT NULL,
  `ItemDescription` text DEFAULT NULL,
  `Size` varchar(50) DEFAULT NULL,
  `PricePerUnit` decimal(10,2) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1,
  `Subtotal` decimal(10,2) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`CartID`, `ClientID`, `ItemType`, `ItemName`, `ItemDescription`, `Size`, `PricePerUnit`, `Quantity`, `Subtotal`, `CreatedAt`) VALUES
(1, 4, 'menu', 'Grilled Fish', 'Fresh fish marinated and grilled', 'medium', 392.00, 1, 392.00, '2025-11-21 14:38:44'),
(2, 4, 'menu', 'Grilled Fish', 'Fresh fish marinated and grilled', 'small', 280.00, 1, 280.00, '2025-11-22 03:58:11'),
(3, 4, 'menu', 'Siomai', 'AWJHKDAWHJda', 'small', 200.00, 1, 200.00, '2025-11-22 04:13:19'),
(4, 4, 'menu', 'Siomai', 'AWJHKDAWHJda', 'small', 200.00, 1, 200.00, '2025-11-22 04:14:26'),
(5, 1, 'menu', 'Lumpiang Shanghai', 'Filipino spring rolls (20pcs)', 'small', 200.00, 1, 200.00, '2025-11-23 17:17:29'),
(6, 11, 'menu', 'Dumplings', 'Mandu', 'small', 200.00, 1, 200.00, '2025-11-27 05:14:29'),
(7, 11, 'menu', 'Dumplings', 'Mandu', 'small', 200.00, 1, 200.00, '2025-11-27 06:08:06'),
(9, 11, 'menu', 'Lechon', 'Crispy baboy', 'small', 2500.00, 1, 2500.00, '2025-11-27 06:29:49'),
(10, 11, 'menu', 'Dumplings', 'Mandu', 'small', 200.00, 1, 200.00, '2025-11-27 07:50:20'),
(11, 11, 'menu', 'Lechon', 'Crispy baboy', 'small', 2500.00, 1, 2500.00, '2025-11-27 07:50:20'),
(12, 11, 'menu', 'Grilled Fish', 'Fresh fish marinated and grilled', 'small', 280.00, 1, 280.00, '2025-11-27 08:00:40'),
(13, 11, 'menu', 'Lechon', 'Crispy baboy', 'small', 2500.00, 1, 2500.00, '2025-11-27 08:00:40'),
(14, 11, 'menu', 'Lechon Kawali', 'Crispy pork belly', 'small', 320.00, 1, 320.00, '2025-11-27 08:02:35'),
(15, 11, 'menu', 'Lechon', 'Crispy baboy', 'small', 2500.00, 1, 2500.00, '2025-11-27 08:11:48'),
(16, 11, 'menu', 'Lechon Kawali', 'Crispy pork belly', 'small', 320.00, 1, 320.00, '2025-11-27 08:11:48'),
(17, 11, 'menu', 'Grilled Fish', 'Fresh fish marinated and grilled', 'small', 280.00, 1, 280.00, '2025-11-27 08:58:43'),
(18, 11, 'menu', 'Lumpiang Shanghai', 'Filipino spring rolls (20pcs)', 'small', 200.00, 1, 200.00, '2025-11-27 08:58:43'),
(19, 11, 'menu', 'Grilled Fish', 'Fresh fish marinated and grilled', 'small', 280.00, 1, 280.00, '2025-11-27 10:03:20'),
(20, 11, 'menu', 'Lumpiang Shanghai', 'Filipino spring rolls (20pcs)', 'small', 200.00, 1, 200.00, '2025-11-27 10:03:54'),
(21, 11, 'menu', 'Grilled Fish', 'Fresh fish marinated and grilled', 'small', 280.00, 1, 280.00, '2025-11-27 10:14:50'),
(22, 11, 'menu', 'Dumplings', 'Mandu', 'small', 200.00, 1, 200.00, '2025-11-27 10:14:50');

-- --------------------------------------------------------

--
-- Table structure for table `cart_orders`
--

CREATE TABLE `cart_orders` (
  `OrderID` int(11) NOT NULL,
  `ClientID` int(11) NOT NULL,
  `BookingID` int(11) DEFAULT NULL,
  `QuotationID` int(11) DEFAULT NULL,
  `OrderDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `TotalAmount` decimal(10,2) NOT NULL,
  `Status` enum('Pending','Confirmed','Completed','Cancelled') DEFAULT 'Pending',
  `PaymentMethod` enum('Cash','GCash','Bank Transfer') DEFAULT NULL,
  `PaymentStatus` enum('Pending','Processing','Paid','Failed') DEFAULT 'Pending',
  `GCashReference` varchar(100) DEFAULT NULL,
  `BankReferenceNumber` varchar(100) DEFAULT NULL,
  `BankSenderName` varchar(255) DEFAULT NULL,
  `PaymentDate` datetime DEFAULT NULL,
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_orders`
--

INSERT INTO `cart_orders` (`OrderID`, `ClientID`, `BookingID`, `QuotationID`, `OrderDate`, `TotalAmount`, `Status`, `PaymentMethod`, `PaymentStatus`, `GCashReference`, `BankReferenceNumber`, `BankSenderName`, `PaymentDate`, `Notes`) VALUES
(1, 4, 9, 7, '2025-11-21 14:38:44', 392.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 1 items'),
(2, 4, 13, 11, '2025-11-22 03:58:11', 280.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 1 items'),
(3, 4, 19, 17, '2025-11-22 04:13:19', 200.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 1 items. Payment: GCash'),
(4, 4, 20, 18, '2025-11-22 04:14:26', 200.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 1 items. Payment: GCash'),
(5, 1, 28, 26, '2025-11-23 17:17:29', 200.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 1 items. Payment: GCash'),
(6, 11, 36, 34, '2025-11-27 05:14:29', 200.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 1 items. Payment: GCash'),
(7, 11, 39, 37, '2025-11-27 06:08:06', 200.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 1 items. Payment: GCash'),
(8, 11, 40, 38, '2025-11-27 06:29:49', 2500.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 1 items. Payment: GCash'),
(9, 11, 41, 39, '2025-11-27 07:50:20', 2700.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 2 items. Payment: GCash'),
(10, 11, 42, 40, '2025-11-27 08:00:40', 2780.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 2 items. Payment: GCash'),
(11, 11, 43, 41, '2025-11-27 08:02:35', 320.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 1 items. Payment: Bank Transfer'),
(12, 11, 45, 43, '2025-11-27 08:11:48', 2820.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 2 items. Payment: GCash'),
(13, 11, 46, 44, '2025-11-27 08:58:43', 480.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 2 items. Payment: GCash'),
(14, 11, 47, 45, '2025-11-27 10:03:20', 280.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 1 items. Payment: GCash'),
(15, 11, 48, 46, '2025-11-27 10:03:54', 200.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 1 items. Payment: Bank Transfer'),
(16, 11, 49, 47, '2025-11-27 10:14:50', 480.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, NULL, 'Food menu order with 2 items. Payment: Cash');

-- --------------------------------------------------------

--
-- Table structure for table `cart_order_items`
--

CREATE TABLE `cart_order_items` (
  `OrderItemID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `ItemType` enum('menu','package') NOT NULL DEFAULT 'menu',
  `ItemName` varchar(255) NOT NULL,
  `ItemDescription` text DEFAULT NULL,
  `Size` varchar(50) DEFAULT NULL,
  `PricePerUnit` decimal(10,2) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1,
  `Subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_order_items`
--

INSERT INTO `cart_order_items` (`OrderItemID`, `OrderID`, `ItemType`, `ItemName`, `ItemDescription`, `Size`, `PricePerUnit`, `Quantity`, `Subtotal`) VALUES
(1, 1, 'menu', 'Grilled Fish', 'Fresh fish marinated and grilled', 'medium', 392.00, 1, 392.00),
(2, 2, 'menu', 'Grilled Fish', 'Fresh fish marinated and grilled', 'small', 280.00, 1, 280.00),
(3, 3, 'menu', 'Siomai', 'AWJHKDAWHJda', 'small', 200.00, 1, 200.00),
(4, 4, 'menu', 'Siomai', 'AWJHKDAWHJda', 'small', 200.00, 1, 200.00),
(5, 5, 'menu', 'Lumpiang Shanghai', 'Filipino spring rolls (20pcs)', 'small', 200.00, 1, 200.00),
(6, 6, 'menu', 'Dumplings', 'Mandu', 'small', 200.00, 1, 200.00),
(7, 7, 'menu', 'Dumplings', 'Mandu', 'small', 200.00, 1, 200.00),
(8, 8, 'menu', 'Lechon', 'Crispy baboy', 'small', 2500.00, 1, 2500.00),
(9, 9, 'menu', 'Dumplings', 'Mandu', 'small', 200.00, 1, 200.00),
(10, 9, 'menu', 'Lechon', 'Crispy baboy', 'small', 2500.00, 1, 2500.00),
(11, 10, 'menu', 'Grilled Fish', 'Fresh fish marinated and grilled', 'small', 280.00, 1, 280.00),
(12, 10, 'menu', 'Lechon', 'Crispy baboy', 'small', 2500.00, 1, 2500.00),
(13, 11, 'menu', 'Lechon Kawali', 'Crispy pork belly', 'small', 320.00, 1, 320.00),
(14, 12, 'menu', 'Lechon', 'Crispy baboy', 'small', 2500.00, 1, 2500.00),
(15, 12, 'menu', 'Lechon Kawali', 'Crispy pork belly', 'small', 320.00, 1, 320.00),
(16, 13, 'menu', 'Grilled Fish', 'Fresh fish marinated and grilled', 'small', 280.00, 1, 280.00),
(17, 13, 'menu', 'Lumpiang Shanghai', 'Filipino spring rolls (20pcs)', 'small', 200.00, 1, 200.00),
(18, 14, 'menu', 'Grilled Fish', 'Fresh fish marinated and grilled', 'small', 280.00, 1, 280.00),
(19, 15, 'menu', 'Lumpiang Shanghai', 'Filipino spring rolls (20pcs)', 'small', 200.00, 1, 200.00),
(20, 16, 'menu', 'Grilled Fish', 'Fresh fish marinated and grilled', 'small', 280.00, 1, 280.00),
(21, 16, 'menu', 'Dumplings', 'Mandu', 'small', 200.00, 1, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `ClientID` int(11) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `IsEmailVerified` tinyint(1) NOT NULL DEFAULT 0,
  `EmailVerifiedAt` datetime DEFAULT NULL,
  `ContactNumber` varchar(20) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `ProfileImage` varchar(255) DEFAULT NULL,
  `user_role` enum('admin','client') NOT NULL DEFAULT 'client',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`ClientID`, `Name`, `Email`, `Password`, `IsEmailVerified`, `EmailVerifiedAt`, `ContactNumber`, `Address`, `ProfileImage`, `user_role`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Sofia Tapongco', 'sofia@gmail.com', 'sofiatapongco', 0, NULL, '09167898776', 'Nasugbu, Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(2, 'Erich Castillo', 'erichn@gmail.com', 'erichcastillo', 0, NULL, '09167074350', 'Lian, Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(3, 'John Lei', 'Lei@gmail.com', 'leitorres', 0, NULL, '09167898765', 'Nasugbu, Lian Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(4, 'Yeoj Valdez', 'Yeoj@gmail.com', 'yeojvaldez', 0, NULL, '091654536577', 'Lian, Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(5, 'Ara Felicisimo', 'arafelicisimo@gmail.com', 'arafelicisimo', 0, NULL, '09694335266', 'Malaruhatan, Lian Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(6, 'Elmira Despo', 'elmiradespo@gmail.com', 'elmiradespo', 0, NULL, '096789876544', 'Malaruhatan, Lian Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(7, 'Aaron James', 'aaron@gmail.com', 'aaronjames', 0, NULL, '09786565434', 'Lian,Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(8, 'Rheyven Bausas', 'rheyven@gmail.com', 'rheyvenbausas', 0, NULL, '09165456787', 'Prenza, Lian Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(9, 'Lea Castro', 'leacastro@gmail.com', 'leacastro', 0, NULL, '09165456787', 'Nasugbu, Lian Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(10, 'jem ganda', 'jemsalvacion28@gmail.com', '$2y$10$4FegFUcQK2K9KJxk6/RBmu5Lq/voV8wXY/JPdaLJ1wzz/cx7sZqBW', 0, NULL, '09851590335', 'Pantalan', NULL, 'client', '2025-10-25 07:41:26', '2025-10-25 07:41:26'),
(11, 'lei', 'leitorres030@gmail.com', '$2y$10$vKradLUBcBYxLqy4/ID30.x05KHWCNs2ekpeGkMuH2EnricHOP/.2', 0, NULL, '09851590335', 'wawa', NULL, 'client', '2025-10-25 07:44:16', '2025-10-25 07:44:16'),
(12, 'Shirley Valdez', 'shirley@gmail.com', '$2y$10$C7tlo44Rz6YEkpFWsmNChuK1fry1YoPDq5TwIRtcgfWxz9g70h/AW', 0, NULL, '09654536823', '', NULL, 'client', '2025-11-06 04:56:29', '2025-11-06 04:56:29'),
(13, 'Jared Abellera', 'jared@gmail.com', '$2y$10$hQ1kVk.R/0hOAC2bQUWIwOvtqZ/LE1WB39Z1sbxJmlh21xAWj8Dtq', 0, NULL, '0968456703', 'Lian, Batangas', NULL, 'client', '2025-11-06 05:07:58', '2025-11-06 05:07:58'),
(14, 'Tapongco, Sofia La-arni B', '23-75707@g.batstate-u.edu.ph', '$2y$10$zegO4jL4/ft.6zVyS8k8I.zb4qqZyj108RDgjKK5HWs1xBgPeVqAO', 0, NULL, '09676875867', 'Sitio Centr, Brgy. Cogunan', NULL, 'client', '2025-11-23 15:57:21', '2025-11-23 15:57:21');

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `EmailID` int(11) NOT NULL,
  `RecipientEmail` varchar(100) NOT NULL,
  `Subject` varchar(255) NOT NULL,
  `Body` text NOT NULL,
  `Status` enum('pending','sent','failed') DEFAULT 'pending',
  `Attempts` int(11) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `SentAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verification`
--

CREATE TABLE `email_verification` (
  `VerificationID` int(11) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Code` varchar(6) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `ExpiresAt` datetime NOT NULL,
  `IsUsed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verification`
--

INSERT INTO `email_verification` (`VerificationID`, `Email`, `Code`, `CreatedAt`, `ExpiresAt`, `IsUsed`) VALUES
(2, 'jemsalvacion28@gmail.com', '651097', '2025-10-25 07:40:42', '2025-10-25 09:55:42', 1),
(3, 'leitorres030@gmail.com', '233343', '2025-10-25 07:43:16', '2025-10-25 09:58:16', 1),
(6, 'leanie@gmail.com', '372584', '2025-10-25 07:55:50', '2025-10-25 10:10:50', 0),
(7, '23-75707@g.batstate-u.edu.ph', '598847', '2025-11-23 15:56:56', '2025-11-23 17:11:56', 1),
(8, 'bullettapongco@gmail.com', '388870', '2025-11-23 16:46:59', '2025-11-23 18:01:59', 0),
(9, 'royaljohnlei@gmail.com', '187048', '2025-11-27 00:36:36', '2025-11-27 01:51:36', 0);

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `MenuID` int(11) NOT NULL,
  `DishName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `ImageURL` varchar(500) DEFAULT NULL,
  `Category` enum('beef','pork','chicken','pancit','other') DEFAULT 'other',
  `MenuPrice` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`MenuID`, `DishName`, `Description`, `ImageURL`, `Category`, `MenuPrice`) VALUES
(1, 'Roast Chicken', 'Herb-roasted chicken with vegetables', NULL, 'chicken', 250.00),
(2, 'Beef Caldereta (boneless)', 'Traditional Filipino beef stew', '', 'pork', 350.00),
(3, 'Pancit Canton', 'Stir-fried noodles with vegetables and meat', NULL, 'pancit', 180.00),
(4, 'Lumpiang Shanghai', 'Filipino spring rolls (20pcs)', NULL, 'other', 200.00),
(6, 'Lechon Kawali', 'Crispy pork belly', NULL, 'pork', 320.00),
(7, 'Grilled Fish', 'Fresh fish marinated and grilled', NULL, 'other', 280.00),
(9, 'Dumplings', 'Mandu', '', 'other', 200.00),
(10, 'Lechon', 'Crispy baboy', '', 'pork', 2500.00),
(11, 'Siomai', 'AWJHKDAWHJda', '', 'pork', 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `NotificationID` int(11) NOT NULL,
  `ClientID` int(11) NOT NULL,
  `BookingID` int(11) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `Message` text DEFAULT NULL,
  `SentDate` datetime DEFAULT current_timestamp(),
  `Type` enum('Confirmation','Reminder','Change','QuotationApproved') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package`
--

CREATE TABLE `package` (
  `PackageID` int(11) NOT NULL,
  `PackageType` enum('celebration','bento','packed') NOT NULL DEFAULT 'celebration',
  `PackageName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `PackPrice` decimal(10,2) NOT NULL,
  `MinimumPax` int(11) DEFAULT NULL,
  `ImageURL` varchar(255) DEFAULT NULL,
  `Features` text DEFAULT NULL COMMENT 'JSON array of package features',
  `IsPopular` tinyint(1) NOT NULL DEFAULT 0,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package`
--

INSERT INTO `package` (`PackageID`, `PackageType`, `PackageName`, `Description`, `PackPrice`, `MinimumPax`, `ImageURL`, `Features`, `IsPopular`, `IsActive`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'celebration', 'Basic Celebration Package', 'Perfect for small gatherings (30-50 pax): 3 main dishes, 1 dessert, drinks', 12000.00, 30, NULL, '[\"3 main dishes\", \"1 dessert\", \"Unlimited drinks\", \"Basic table setup\", \"Disposable plates & utensils\"]', 0, 1, '2025-11-19 13:25:25', '2025-11-19 13:25:25'),
(2, 'celebration', 'Premium Wedding Package', 'Complete wedding catering (100-150 pax): 5 main dishes, 2 desserts, drinks, setup', 45000.00, 100, NULL, '[\"5 main dishes\", \"2 desserts\", \"Unlimited drinks\", \"Premium beverages\", \"Elegant table setup\", \"Reusable dinnerware\", \"Basic decoration\"]', 1, 1, '2025-11-19 13:25:25', '2025-11-19 13:25:25'),
(3, 'celebration', 'Corporate Event Package', 'Professional business events (50-80 pax): 4 main dishes, snacks, drinks', 25000.00, 50, NULL, '[\"4 main dishes\", \"Snacks included\", \"Unlimited drinks\", \"Professional setup\", \"Corporate presentation\"]', 0, 1, '2025-11-19 13:25:25', '2025-11-19 13:25:25'),
(4, 'celebration', 'Debut Package', 'Elegant debut celebration (80-100 pax): 4 main dishes, cake, drinks, decorations', 35000.00, 80, NULL, '[\"4 main dishes\", \"Birthday cake included\", \"Unlimited drinks\", \"Elegant decorations\", \"Party favors setup\"]', 0, 1, '2025-11-19 13:25:25', '2025-11-19 13:25:25'),
(5, 'bento', 'Bento Meal Package', 'Individual boxed meals (per person): 1 main, 1 side, rice, dessert', 150.00, 1, NULL, NULL, 0, 1, '2025-11-19 13:25:25', '2025-11-19 13:25:25'),
(6, 'celebration', 'Budget-Friendly Package', 'Affordable for any occasion (30-50 pax): 2 main dishes, drinks', 8000.00, 30, NULL, '[\"2 main dishes\", \"Unlimited drinks\", \"Basic setup\", \"Budget-friendly option\"]', 0, 1, '2025-11-19 13:25:25', '2025-11-19 13:25:25');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PaymentID` int(11) NOT NULL,
  `BookingID` int(11) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `AmountPaid` decimal(10,2) DEFAULT NULL,
  `PaymentDate` date DEFAULT NULL,
  `PaymentMethod` enum('Cash','GCash') DEFAULT NULL,
  `Status` enum('Pending','Paid','Refunded') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotation`
--

CREATE TABLE `quotation` (
  `QuotationID` int(11) NOT NULL,
  `BookingID` int(11) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `SpecialRequest` text DEFAULT NULL,
  `EstimatedPrice` decimal(10,2) DEFAULT NULL,
  `Status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `SpecialRequestPrice` decimal(10,2) DEFAULT 0.00,
  `SpecialRequestItems` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`SpecialRequestItems`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotation`
--

INSERT INTO `quotation` (`QuotationID`, `BookingID`, `AdminID`, `SpecialRequest`, `EstimatedPrice`, `Status`, `SpecialRequestPrice`, `SpecialRequestItems`) VALUES
(1, 2, 1, '', 7500.00, 'Approved', 0.00, NULL),
(2, 3, 1, '', 10000.00, 'Approved', 0.00, NULL),
(3, 5, 1, NULL, NULL, 'Pending', 0.00, NULL),
(4, 6, 1, NULL, NULL, 'Pending', 0.00, NULL),
(5, 7, 1, NULL, NULL, 'Pending', 0.00, NULL),
(6, 8, 1, 'None', 26970.00, 'Pending', 0.00, NULL),
(7, 9, 1, 'Food Menu Order:\n- Grilled Fish (medium) x1\n', 392.00, 'Pending', 0.00, NULL),
(8, 10, 1, NULL, NULL, 'Pending', 0.00, NULL),
(9, 11, 1, NULL, NULL, 'Pending', 0.00, NULL),
(10, 12, 1, NULL, NULL, 'Pending', 0.00, NULL),
(11, 13, 1, 'Food Menu Order:\n- Grilled Fish (small) x1\n', 280.00, 'Pending', 0.00, NULL),
(12, 14, 1, NULL, NULL, 'Pending', 0.00, NULL),
(13, 15, 1, NULL, NULL, 'Pending', 0.00, NULL),
(14, 16, 1, NULL, NULL, 'Pending', 0.00, NULL),
(15, 17, 1, NULL, NULL, 'Pending', 0.00, NULL),
(16, 18, 1, NULL, NULL, 'Pending', 0.00, NULL),
(17, 19, 1, 'Food Menu Order (Payment: GCash):\n- Siomai (small) x1\n', 200.00, 'Pending', 0.00, NULL),
(18, 20, 1, 'Food Menu Order (Payment: GCash):\n- Siomai (small) x1\n', 200.00, 'Pending', 0.00, NULL),
(19, 21, 1, NULL, NULL, 'Pending', 0.00, NULL),
(20, 22, 1, NULL, NULL, 'Pending', 0.00, NULL),
(21, 23, 1, NULL, NULL, 'Pending', 0.00, NULL),
(22, 24, 1, 'none', 22500.00, '', 0.00, NULL),
(23, 25, 1, '', 55000.00, '', 0.00, NULL),
(24, 26, 1, '', 27000.00, '', 0.00, NULL),
(25, 27, 1, 'none', 22500.00, 'Pending', 0.00, NULL),
(26, 28, 1, 'Food Menu Order (Payment: GCash):\n- Lumpiang Shanghai (small) x1\n', 200.00, 'Approved', 2000.00, '[{\"name\":\"Lumpiang Shanghai\",\"price\":2000}]'),
(27, 29, 1, 'la naman', 26970.00, 'Approved', 0.00, NULL),
(28, 30, 1, 'none', 71500.00, 'Approved', 0.00, NULL),
(29, 31, 1, 'what', 22500.00, 'Approved', 0.00, NULL),
(30, 32, 1, 'wala lang', 55000.00, 'Approved', 5200.00, '[{\"name\":\"Premium Bar\",\"price\":5000},{\"name\":\"Extra Cupcakes\",\"price\":200}]'),
(31, 33, 1, 'pusa', 55550.00, 'Approved', 0.00, NULL),
(32, 34, 1, 'pusa', 22950.00, 'Approved', 1.00, '[{\"name\":\"pusa\",\"price\":1}]'),
(33, 35, 1, 'pusa', 40950.00, 'Approved', 0.00, NULL),
(34, 36, 1, 'Food Menu Order (Payment: GCash):\n- Dumplings (small) x1\n', 200.00, 'Pending', 0.00, NULL),
(35, 37, 1, 'pusa', 44950.00, 'Approved', 0.00, NULL),
(36, 38, 1, 'pusa', 55550.00, 'Approved', 0.00, NULL),
(37, 39, 1, 'Food Menu Order (Payment: GCash):\n- Dumplings (small) x1\n', 200.00, 'Approved', 0.00, NULL),
(38, 40, 1, 'Food Menu Order (Payment: GCash):\n- Lechon (small) x1\n', 2500.00, 'Pending', 0.00, NULL),
(39, 41, 1, 'Food Menu Order (Payment: GCash):\n- Dumplings (small) x1\n- Lechon (small) x1\n', 2700.00, 'Pending', 0.00, NULL),
(40, 42, 1, 'Food Menu Order (Payment: GCash):\n- Grilled Fish (small) x1\n- Lechon (small) x1\n', 2780.00, 'Pending', 0.00, NULL),
(41, 43, 1, 'Food Menu Order (Payment: Bank Transfer):\n- Lechon Kawali (small) x1\n\nBank Transfer Details:\nCardholder: lei\nCard (last 4): 1111', 320.00, 'Pending', 0.00, NULL),
(42, 44, 1, 'pusacat', 12400.00, 'Approved', 0.00, NULL),
(43, 45, 1, 'Food Menu Order (Payment: GCash):\n- Lechon (small) x1\n- Lechon Kawali (small) x1\n', 2820.00, 'Pending', 0.00, NULL),
(44, 46, 1, 'Food Menu Order (Payment: GCash):\n- Grilled Fish (small) x1\n- Lumpiang Shanghai (small) x1\n', 480.00, 'Pending', 0.00, NULL),
(45, 47, 1, 'Food Menu Order (Payment: GCash):\n- Grilled Fish (small) x1\n', 280.00, 'Pending', 0.00, NULL),
(46, 48, 1, 'Food Menu Order (Payment: Bank Transfer):\n- Lumpiang Shanghai (small) x1\n\nBank Transfer Details:\nCardholder: lei\nCard (last 4): 1111', 200.00, 'Pending', 0.00, NULL),
(47, 49, 1, 'Food Menu Order (Payment: Cash):\n- Grilled Fish (small) x1\n- Dumplings (small) x1\n', 480.00, 'Pending', 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `salesreport`
--

CREATE TABLE `salesreport` (
  `ReportID` int(11) NOT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `DateGenerated` date DEFAULT NULL,
  `TotalSales` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `ServiceID` int(11) NOT NULL,
  `ServiceName` varchar(100) NOT NULL,
  `ServiceType` enum('wedding','birthday','christening','house_blessing','team_building','reunion','corporate','other') NOT NULL DEFAULT 'other',
  `PricePerPerson` decimal(10,2) NOT NULL,
  `MinimumGuests` int(11) DEFAULT 30,
  `Description` text DEFAULT NULL,
  `ImageURL` varchar(255) DEFAULT NULL,
  `IconClass` varchar(50) DEFAULT 'bi-star-fill' COMMENT 'Bootstrap icon class',
  `Inclusions` text DEFAULT NULL COMMENT 'JSON array of service inclusions',
  `IsPopular` tinyint(1) NOT NULL DEFAULT 0,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `DisplayOrder` int(11) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`ServiceID`, `ServiceName`, `ServiceType`, `PricePerPerson`, `MinimumGuests`, `Description`, `ImageURL`, `IconClass`, `Inclusions`, `IsPopular`, `IsActive`, `DisplayOrder`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Wedding Package', 'wedding', 550.00, 100, 'Make your dream day unforgettable with our complete wedding catering service', NULL, 'bi-heart-fill', '[\"Steamed rice\", \"1 Pork dish\", \"1 Chicken dish\", \"1 Seafood dish\", \"1 Beef dish\", \"1 Vegetables dish\", \"1 Pasta\", \"1 Dessert\", \"2 Drinks\", \"Complete buffet setup\", \"Set of chinawares/utensils\", \"Tables & chairs with centerpiece\", \"Tables for cakes/giveaways\", \"Tables for drinks\", \"Photo booth backdrop\", \"Decors and styling with theme color\", \"Professional service waiters\"]', 1, 1, 1, '2025-11-19 13:25:25', '2025-11-19 13:25:25'),
(2, 'Birthday Package', 'birthday', 450.00, 50, 'Celebrate in style with our birthday catering packages', NULL, 'bi-balloon-heart-fill', '[\"Steamed rice\", \"1 Pork dish\", \"1 Chicken dish\", \"1 Seafood dish\", \"1 Beef dish\", \"1 Vegetables dish\", \"1 Pasta\", \"1 Dessert\", \"2 Drinks\", \"Complete buffet setup\", \"Set of chinawares/utensils\", \"Tables & chairs with centerpiece\", \"Birthday decorations\", \"Cake table setup\", \"Professional service waiters\"]', 0, 1, 2, '2025-11-19 13:25:25', '2025-11-19 13:25:25'),
(3, 'Christening Package', 'christening', 450.00, 50, 'Bless your special day with our christening catering services', NULL, 'bi-gift-fill', '[\"Steamed rice\", \"1 Pork dish\", \"1 Chicken dish\", \"1 Seafood dish\", \"1 Beef dish\", \"1 Vegetables dish\", \"1 Pasta\", \"1 Dessert\", \"2 Drinks\", \"Complete buffet setup\", \"Set of chinawares/utensils\", \"Tables & chairs with centerpiece\", \"Elegant decorations\", \"Giveaway tables\", \"Professional service waiters\"]', 0, 1, 3, '2025-11-19 13:25:25', '2025-11-19 13:25:25'),
(4, 'House Blessing Package', 'house_blessing', 450.00, 50, 'Welcome to your new home with our house blessing catering', NULL, 'bi-house-heart-fill', '[\"Steamed rice\", \"1 Pork dish\", \"1 Chicken dish\", \"1 Seafood dish\", \"1 Beef dish\", \"1 Vegetables dish\", \"1 Pasta\", \"1 Dessert\", \"2 Drinks\", \"Complete buffet setup\", \"Set of chinawares/utensils\", \"Tables & chairs\", \"Simple decorations\", \"Professional service waiters\"]', 0, 1, 4, '2025-11-19 13:25:25', '2025-11-19 13:25:25'),
(5, 'Team Building Package', 'team_building', 400.00, 30, 'Perfect for corporate events and team building activities', NULL, 'bi-people-fill', '[\"Steamed rice\", \"1 Pork dish\", \"1 Chicken dish\", \"1 Seafood dish\", \"1 Beef dish\", \"1 Vegetables dish\", \"1 Pasta\", \"2 Drinks\", \"Complete buffet setup\", \"Set of chinawares/utensils\", \"Tables & chairs\", \"Corporate setup\", \"Professional service waiters\"]', 0, 1, 5, '2025-11-19 13:25:25', '2025-11-19 13:25:25'),
(6, 'Reunion Package', 'reunion', 400.00, 30, 'Reconnect with loved ones with our reunion catering packages', NULL, 'bi-calendar-heart-fill', '[\"Steamed rice\", \"1 Pork dish\", \"1 Chicken dish\", \"1 Seafood dish\", \"1 Beef dish\", \"1 Vegetables dish\", \"1 Pasta\", \"2 Drinks\", \"Complete buffet setup\", \"Set of chinawares/utensils\", \"Tables & chairs\", \"Simple decorations\", \"Professional service waiters\"]', 0, 1, 6, '2025-11-19 13:25:25', '2025-11-19 13:25:25'),
(8, 'Enchanted Vows Catering', 'wedding', 1450.00, 30, 'A romantic wedding catering package featuring curated dishes, elegant table décor, and a seamless dining experience designed for your special day.', NULL, 'bi-star-fill', '[\"Gourmet buffet\",\"Signature drinks\",\"Couples’ VIP table styling\",\"Floral table accents\",\"Full service team\",\"Dessert bites platter\"]', 0, 1, 7, '2025-11-21 12:31:24', '2025-11-21 12:32:51'),
(9, 'Corporate Signature Buffet', 'corporate', 899.00, 30, 'A polished corporate catering package ideal for meetings, company celebrations, and conferences with a clean and professional setup.', NULL, 'bi-star-fill', '[\"Full buffet setup\",\"Free-flowing coffee\",\"Table linens (corporate colors)\",\"Uniformed service crew\",\"Water station\",\"Basic AV assistance\"]', 0, 1, 8, '2025-11-21 12:39:11', '2025-11-21 12:39:11');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `SessionID` varchar(255) NOT NULL,
  `UserID` int(11) NOT NULL,
  `UserType` enum('admin','client') NOT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `UserAgent` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `ExpiresAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`SessionID`, `UserID`, `UserType`, `IPAddress`, `UserAgent`, `CreatedAt`, `ExpiresAt`) VALUES
('083eeg7non7esstnjcsdkl87ni', 11, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 06:10:44', '2025-11-27 07:10:44'),
('08bjcbhc4pcsti3frj8mpipq5g', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 10:10:50', '2025-11-26 11:10:50'),
('092mpci4qf8u8eu8c34h6pn7cn', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 14:46:55', '2025-11-19 15:46:55'),
('0m8tq7rnaj87gh1j6j9qrruj3e', 1, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 15:32:21', '2025-11-25 16:32:21'),
('1dnubt5cfvfefqm4lgu7ufmts7', 4, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 14:33:46', '2025-11-19 15:33:46'),
('1n948besn8s256vdv8kgira2s2', 4, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 12:58:01', '2025-11-19 13:58:01'),
('1q35vjbt9isbvp0nkr0kl94ogh', 4, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 13:10:52', '2025-11-19 14:10:52'),
('2vm36ah5kd3udova20afb3aeki', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 15:54:53', '2025-11-25 16:54:53'),
('4npl2b7lci394mg1tlome49duc', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 15:42:05', '2025-11-26 16:42:05'),
('5fnqsgrsd2dcpc3e8kllo5m56a', 14, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 15:57:35', '2025-11-23 16:57:35'),
('6fnm3ciap7qnqvsb77l6poi3lk', 4, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 05:49:31', '2025-11-20 06:49:31'),
('6lsfdev610rqvgcqks1avld348', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 00:41:39', '2025-11-27 01:41:39'),
('6sjpq0416p229mj4h05oc74387', 4, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 07:58:21', '2025-11-20 08:58:21'),
('7l88jtg7rlo3k84egfj1fccdol', 1, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 12:58:15', '2025-11-23 13:58:15'),
('7rh78r4lnf2301rm4nio8ovs9k', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 05:24:58', '2025-11-20 06:24:58'),
('8mki5h00ef1dk8fj3abqsdpc49', 1, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 16:54:21', '2025-11-22 17:54:21'),
('anvf9ua67j0jg1d3spd1hlv69l', 1, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 10:33:14', '2025-11-26 11:33:14'),
('aoqj381ge250usts1f1619d6k5', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 04:31:40', '2025-11-20 05:31:40'),
('bc56lpla94hse744k1mlk0qfpi', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 15:27:29', '2025-11-25 16:27:29'),
('binutb2r2gvjeuo25iua7lojua', 11, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 10:03:05', '2025-11-27 11:03:05'),
('dv0n56fr1tksm95feu125a6g3s', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 16:56:24', '2025-11-23 17:56:24'),
('e3gob4lahscp7rjng9do8tkqen', 4, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 04:27:52', '2025-11-20 05:27:52'),
('eqk1rlikd6iuu0m9nvei9t0nhb', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 13:07:20', '2025-11-19 14:07:20'),
('f54dbi6tahup71hd27hg9k1orp', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 04:43:22', '2025-11-20 05:43:22'),
('g8sv32hdl223pjvcdh95f84lap', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 04:25:30', '2025-11-20 05:25:30'),
('h6d102o9on08lq7buf0nqihqa1', 11, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 00:38:31', '2025-11-27 01:38:31'),
('h6i02pnmvvd0ic1uh8pgupdptg', 1, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 17:02:29', '2025-11-23 18:02:29'),
('hkn6mv21po6gp2pnfhr1uj15vm', 4, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 09:49:25', '2025-11-20 10:49:25'),
('hnk9aeugsv5vsrt4qtmqo88nq3', 4, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 12:26:58', '2025-11-21 13:26:58'),
('i3bt8qegfvcgkdthaf0bbslrel', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-06 04:23:50', '2025-11-06 05:23:50'),
('ja48l4g9liu4ema33i93gdu73b', 1, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 16:55:32', '2025-11-23 17:55:32'),
('jeql1dijamesk191qe9lintbn9', 1, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 16:13:10', '2025-11-25 17:13:10'),
('k5htp4rs3u9u9af0rbdsiqi85t', 4, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 12:51:44', '2025-11-19 13:51:44'),
('l9as2ggvq117j9oial2frjeeen', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 04:26:11', '2025-11-20 05:26:11'),
('lmkinvphao904kdangvlk7dfm4', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 07:57:53', '2025-11-20 08:57:53'),
('m93bodnoi7n218e5gbqk6m8qmb', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 13:18:42', '2025-11-19 14:18:42'),
('mlm7q8kco77lm3a3m88nj4rgtc', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 03:53:25', '2025-11-22 04:53:25'),
('nlgnabt07ilhcq51pkv222psar', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 08:55:29', '2025-11-27 09:55:29'),
('o64t35igdn1a2t5dj5c4b7553n', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 12:25:46', '2025-11-21 13:25:46'),
('oeqjervisjvm21n9te81tmqqv8', 1, 'admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 19:10:36', '2025-11-26 20:10:36'),
('s4l54iops7ihcjs8i4eokko8tr', 4, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 03:53:40', '2025-11-22 04:53:40'),
('sgnchkke8hjlk0j84mfiiurefr', 1, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 13:22:34', '2025-11-25 14:22:34'),
('ti9j15tsl27it6dv3fjmlv3tpn', 11, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 09:20:02', '2025-11-27 10:20:02'),
('tt7dt474uf2e9remqdac552kn7', 1, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 19:05:29', '2025-11-26 20:05:29'),
('u4c196bdj8pcp7qt4n7hoj0khj', 4, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 05:00:47', '2025-11-20 06:00:47'),
('um3cgocd41gqh2pg1rdq298v8r', 11, 'client', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 13:22:27', '2025-11-27 14:22:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `idx_user` (`UserID`,`UserType`),
  ADD KEY `idx_created` (`CreatedAt`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`AdminID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `agreement`
--
ALTER TABLE `agreement`
  ADD PRIMARY KEY (`AgreementID`),
  ADD KEY `BookingID` (`BookingID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`BookingID`),
  ADD KEY `ClientID` (`ClientID`),
  ADD KEY `idx_payment_ref` (`GCashReference`,`BankReferenceNumber`);

--
-- Indexes for table `booking_menu`
--
ALTER TABLE `booking_menu`
  ADD PRIMARY KEY (`BookingID`,`MenuID`),
  ADD KEY `MenuID` (`MenuID`);

--
-- Indexes for table `booking_package`
--
ALTER TABLE `booking_package`
  ADD PRIMARY KEY (`BookingID`,`PackageID`),
  ADD KEY `PackageID` (`PackageID`);

--
-- Indexes for table `booking_service`
--
ALTER TABLE `booking_service`
  ADD PRIMARY KEY (`BookingID`,`ServiceID`),
  ADD KEY `ServiceID` (`ServiceID`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`CartID`),
  ADD KEY `ClientID` (`ClientID`),
  ADD KEY `idx_cart_client` (`ClientID`,`CreatedAt`);

--
-- Indexes for table `cart_orders`
--
ALTER TABLE `cart_orders`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `ClientID` (`ClientID`),
  ADD KEY `BookingID` (`BookingID`),
  ADD KEY `QuotationID` (`QuotationID`),
  ADD KEY `idx_cart_orders_status` (`Status`,`OrderDate`),
  ADD KEY `idx_cart_orders_client` (`ClientID`,`OrderDate`);

--
-- Indexes for table `cart_order_items`
--
ALTER TABLE `cart_order_items`
  ADD PRIMARY KEY (`OrderItemID`),
  ADD KEY `OrderID` (`OrderID`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`ClientID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`EmailID`),
  ADD KEY `idx_status` (`Status`);

--
-- Indexes for table `email_verification`
--
ALTER TABLE `email_verification`
  ADD PRIMARY KEY (`VerificationID`),
  ADD KEY `idx_email` (`Email`),
  ADD KEY `idx_code` (`Code`),
  ADD KEY `idx_expires` (`ExpiresAt`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`MenuID`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`NotificationID`,`ClientID`),
  ADD KEY `ClientID` (`ClientID`),
  ADD KEY `BookingID` (`BookingID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `package`
--
ALTER TABLE `package`
  ADD PRIMARY KEY (`PackageID`),
  ADD KEY `idx_package_type` (`PackageType`),
  ADD KEY `idx_active` (`IsActive`),
  ADD KEY `idx_popular` (`IsPopular`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `BookingID` (`BookingID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `quotation`
--
ALTER TABLE `quotation`
  ADD PRIMARY KEY (`QuotationID`),
  ADD KEY `BookingID` (`BookingID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `salesreport`
--
ALTER TABLE `salesreport`
  ADD PRIMARY KEY (`ReportID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`ServiceID`),
  ADD KEY `idx_service_type` (`ServiceType`),
  ADD KEY `idx_active` (`IsActive`),
  ADD KEY `idx_display_order` (`DisplayOrder`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`SessionID`),
  ADD KEY `idx_user` (`UserID`,`UserType`),
  ADD KEY `idx_expires` (`ExpiresAt`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=172;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `agreement`
--
ALTER TABLE `agreement`
  MODIFY `AgreementID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `BookingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `CartID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `cart_orders`
--
ALTER TABLE `cart_orders`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `cart_order_items`
--
ALTER TABLE `cart_order_items`
  MODIFY `OrderItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `ClientID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `EmailID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_verification`
--
ALTER TABLE `email_verification`
  MODIFY `VerificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `MenuID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package`
--
ALTER TABLE `package`
  MODIFY `PackageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation`
--
ALTER TABLE `quotation`
  MODIFY `QuotationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `salesreport`
--
ALTER TABLE `salesreport`
  MODIFY `ReportID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `ServiceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agreement`
--
ALTER TABLE `agreement`
  ADD CONSTRAINT `agreement_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`),
  ADD CONSTRAINT `agreement_ibfk_2` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`ClientID`) REFERENCES `client` (`ClientID`);

--
-- Constraints for table `booking_menu`
--
ALTER TABLE `booking_menu`
  ADD CONSTRAINT `booking_menu_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`),
  ADD CONSTRAINT `booking_menu_ibfk_2` FOREIGN KEY (`MenuID`) REFERENCES `menu` (`MenuID`);

--
-- Constraints for table `booking_package`
--
ALTER TABLE `booking_package`
  ADD CONSTRAINT `booking_package_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`),
  ADD CONSTRAINT `booking_package_ibfk_2` FOREIGN KEY (`PackageID`) REFERENCES `package` (`PackageID`);

--
-- Constraints for table `booking_service`
--
ALTER TABLE `booking_service`
  ADD CONSTRAINT `booking_service_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_service_ibfk_2` FOREIGN KEY (`ServiceID`) REFERENCES `services` (`ServiceID`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`ClientID`) REFERENCES `client` (`ClientID`) ON DELETE CASCADE;

--
-- Constraints for table `cart_orders`
--
ALTER TABLE `cart_orders`
  ADD CONSTRAINT `cart_orders_ibfk_1` FOREIGN KEY (`ClientID`) REFERENCES `client` (`ClientID`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_orders_ibfk_2` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`) ON DELETE SET NULL,
  ADD CONSTRAINT `cart_orders_ibfk_3` FOREIGN KEY (`QuotationID`) REFERENCES `quotation` (`QuotationID`) ON DELETE SET NULL;

--
-- Constraints for table `cart_order_items`
--
ALTER TABLE `cart_order_items`
  ADD CONSTRAINT `cart_order_items_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `cart_orders` (`OrderID`) ON DELETE CASCADE;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`ClientID`) REFERENCES `client` (`ClientID`),
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`),
  ADD CONSTRAINT `notification_ibfk_3` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`),
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- Constraints for table `quotation`
--
ALTER TABLE `quotation`
  ADD CONSTRAINT `quotation_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`),
  ADD CONSTRAINT `quotation_ibfk_2` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- Constraints for table `salesreport`
--
ALTER TABLE `salesreport`
  ADD CONSTRAINT `salesreport_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
