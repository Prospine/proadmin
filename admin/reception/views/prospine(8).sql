-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 14, 2025 at 04:16 PM
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
-- Database: `prospine`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `branch_id` int(11) NOT NULL DEFAULT 1,
  `consultationType` enum('virtual','clinic','home') NOT NULL,
  `fullName` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `medical_condition` text DEFAULT NULL,
  `conditionType` enum('neck_pain','back_pain','low_back_pain','radiating_pain','other') DEFAULT 'other',
  `referralSource` enum('doctor_referral','web_search','social_media','returning_patient','local_event','advertisement','employee','family','self','other') DEFAULT 'self',
  `contactMethod` enum('Phone','Email','Text') DEFAULT 'Phone',
  `location` enum('bhagalpur_branch','siliguri_branch') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('cash','card','upi','online') DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `branch_id`, `consultationType`, `fullName`, `email`, `phone`, `gender`, `dob`, `age`, `occupation`, `address`, `medical_condition`, `conditionType`, `referralSource`, `contactMethod`, `location`, `created_at`, `status`, `payment_status`, `payment_amount`, `payment_method`, `payment_date`, `transaction_id`) VALUES
(1, NULL, 2, 'clinic', 'Sumit Srivastava', 'srisumit96@gmail.com', '7739028861', 'male', '2002-12-28', 23, 'student', '', 'Minor Pain in the back', 'back_pain', 'returning_patient', 'Phone', 'siliguri_branch', '2025-09-13 18:51:35', 'confirmed', 'pending', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointment_requests`
--

CREATE TABLE `appointment_requests` (
  `id` int(11) NOT NULL,
  `fullName` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `location` enum('bhagalpur_branch','siliguri_branch') NOT NULL,
  `branch_id` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('new','contacted','converted','discarded') DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `appointment_requests`
--

INSERT INTO `appointment_requests` (`id`, `fullName`, `phone`, `location`, `branch_id`, `created_at`, `status`) VALUES
(1, 'Sumit', '7739239823', 'siliguri_branch', 2, '2025-09-13 17:18:41', 'discarded'),
(2, 'sagar', '7334347394', 'siliguri_branch', 2, '2025-09-13 18:35:11', 'converted'),
(3, 'Raj', '7989438493', 'siliguri_branch', 2, '2025-09-13 18:35:20', 'contacted'),
(4, 'Chandan', '8938493948', 'siliguri_branch', 2, '2025-09-13 18:35:32', 'new'),
(5, 'Channndan', '7938920984', 'bhagalpur_branch', 1, '2025-09-13 18:35:40', 'new'),
(6, 'Raj', '7934934943', 'siliguri_branch', 2, '2025-09-20 01:15:51', 'new'),
(7, 'Pranav', '9038490348', 'bhagalpur_branch', 1, '2025-09-20 01:16:42', 'new'),
(8, 'Pranav', '8349839849', 'siliguri_branch', 2, '2025-09-20 01:17:04', 'new'),
(9, 'Aditya', '7834987394', 'siliguri_branch', 2, '2025-09-27 14:19:43', 'new');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `patient_id`, `attendance_date`, `remarks`, `payment_id`, `created_at`) VALUES
(17, 3, '2025-09-13', 'Daily attendance marked', 9, '2025-09-13 10:59:31'),
(18, 2, '2025-09-13', 'Auto: Used advance payment', NULL, '2025-09-13 11:07:27'),
(19, 1, '2025-09-13', 'Auto: Used advance payment', NULL, '2025-09-13 11:07:28'),
(21, 4, '2025-09-13', 'Auto: Used advance payment', NULL, '2025-09-13 11:26:21'),
(22, 2, '2025-09-14', 'Auto: Used advance payment', NULL, '2025-09-14 09:42:58'),
(23, 1, '2025-09-14', 'Auto: Used advance payment', NULL, '2025-09-14 09:43:05'),
(24, 2, '2025-09-19', 'Auto: Used advance payment', NULL, '2025-09-19 09:14:23'),
(25, 1, '2025-09-19', 'Auto: Used advance payment', NULL, '2025-09-19 09:14:28'),
(26, 4, '2025-09-19', 'Advance attendance marked', 12, '2025-09-19 13:56:31'),
(27, 3, '2025-09-19', 'Daily attendance marked', 13, '2025-09-19 13:56:50'),
(28, 5, '2025-09-20', 'Advance attendance marked', 15, '2025-09-19 19:30:50'),
(29, 2, '2025-09-20', 'Auto: Used advance payment', NULL, '2025-09-20 17:24:05'),
(30, 4, '2025-09-20', 'Advance attendance marked', 16, '2025-09-20 17:24:26'),
(31, 3, '2025-09-20', 'Daily attendance marked', 17, '2025-09-20 17:44:39'),
(32, 5, '2025-09-24', 'Advance attendance marked', 18, '2025-09-23 19:14:44'),
(33, 2, '2025-09-24', 'Auto: Used advance payment', NULL, '2025-09-23 19:16:55'),
(34, 2, '2025-09-25', 'Auto: Used advance payment', NULL, '2025-09-25 11:03:07'),
(35, 5, '2025-09-25', 'Advance attendance marked', 19, '2025-09-25 11:03:42'),
(36, 6, '2025-09-25', 'Auto: Used advance payment', NULL, '2025-09-25 12:01:45'),
(37, 6, '2025-09-26', 'Auto: Used advance payment', NULL, '2025-09-26 08:31:09'),
(38, 1, '2025-09-26', 'Auto: Used advance payment', NULL, '2025-09-26 09:06:55'),
(39, 5, '2025-09-26', 'Advance attendance marked', 21, '2025-09-26 09:35:06'),
(40, 3, '2025-09-26', 'Auto: Used advance payment', NULL, '2025-09-26 17:59:13'),
(41, 2, '2025-09-26', 'Auto: Used advance payment', NULL, '2025-09-26 17:59:18'),
(42, 6, '2025-09-27', 'Auto: Used advance payment', NULL, '2025-09-27 08:55:53'),
(43, 2, '2025-09-27', 'Auto: Used advance payment', NULL, '2025-09-27 08:56:05'),
(44, 1, '2025-09-27', 'Auto: Used advance payment', NULL, '2025-09-27 08:56:08'),
(45, 5, '2025-09-27', 'Advance attendance marked', 22, '2025-09-27 17:25:15'),
(46, 7, '2025-10-02', 'Auto: Used advance payment', NULL, '2025-10-01 19:51:25'),
(47, 6, '2025-10-02', 'Auto: Used advance payment', NULL, '2025-10-02 15:31:38'),
(48, 3, '2025-10-02', 'Daily attendance marked', 24, '2025-10-02 15:33:04'),
(49, 2, '2025-10-02', 'Auto: Used advance payment', NULL, '2025-10-02 16:45:33'),
(50, 8, '2025-10-02', 'Auto: Used advance payment', NULL, '2025-10-02 17:13:03'),
(51, 5, '2025-10-02', 'Advance attendance marked', 26, '2025-10-02 18:00:30'),
(52, 8, '2025-10-04', 'Auto: Used advance payment', NULL, '2025-10-04 10:14:43'),
(53, 7, '2025-10-04', 'Auto: Used advance payment', NULL, '2025-10-04 10:14:44'),
(54, 6, '2025-10-04', 'Auto: Used advance payment', NULL, '2025-10-04 10:14:45'),
(55, 9, '2025-10-06', 'Auto: Used advance payment', NULL, '2025-10-06 14:18:31'),
(56, 9, '2025-10-07', 'Auto: Used advance payment', NULL, '2025-10-06 19:35:27'),
(57, 9, '2025-10-11', 'Auto: Used advance payment', NULL, '2025-10-11 14:05:18'),
(58, 10, '2025-10-11', 'Auto: Used advance payment', NULL, '2025-10-11 18:11:06'),
(59, 10, '2025-10-12', 'Auto: Used advance payment', NULL, '2025-10-12 17:52:19'),
(60, 9, '2025-10-12', 'Auto: Used advance payment', NULL, '2025-10-12 17:52:23'),
(61, 7, '2025-10-12', 'Auto: Used advance payment', NULL, '2025-10-12 17:52:24'),
(62, 13, '2025-10-13', 'Auto: Used advance payment', NULL, '2025-10-12 21:00:56'),
(63, 10, '2025-10-13', 'Auto: Used advance payment', NULL, '2025-10-12 21:01:52'),
(64, 14, '2025-10-13', 'Daily attendance marked', 33, '2025-10-12 21:13:21'),
(65, 20, '2025-10-13', 'Auto: Used advance payment', NULL, '2025-10-13 17:33:41'),
(66, 8, '2025-10-13', 'Auto: Used advance payment', NULL, '2025-10-13 17:34:44'),
(67, 16, '2025-10-13', 'Auto: Used advance payment', NULL, '2025-10-13 17:37:12'),
(68, 1, '2025-10-13', 'Advance attendance marked', 37, '2025-10-13 17:37:25'),
(69, 21, '2025-10-14', 'Auto: Used advance payment', NULL, '2025-10-13 19:28:56');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` bigint(20) UNSIGNED NOT NULL,
  `log_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `action_type` enum('CREATE','UPDATE','DELETE','LOGIN_SUCCESS','LOGIN_FAIL','LOGOUT') NOT NULL,
  `target_table` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details_before` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details_before`)),
  `details_after` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details_after`)),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `log_timestamp`, `user_id`, `username`, `branch_id`, `action_type`, `target_table`, `target_id`, `details_before`, `details_after`, `ip_address`) VALUES
(1, '2025-09-27 09:38:44', 1, 'admin', 2, 'CREATE', 'quick_inquiry', 28, NULL, '{\"name\":\"Aditya\",\"age\":\"22\",\"phone_number\":\"8993849384\",\"referralSource\":\"web_search\"}', '::1'),
(2, '2025-09-27 09:41:00', 1, 'admin', 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1'),
(3, '2025-09-27 09:42:55', 1, 'admin', 2, 'CREATE', 'test_inquiry', 6, NULL, '{\"name\":\"Aditya\",\"testname\":\"bera\",\"mobile_number\":\"7889374893\",\"reffered_by\":\"Dr Sumit\"}', '::1'),
(4, '2025-09-27 09:58:47', 1, 'admin', 2, 'CREATE', 'registration', 17, NULL, '{\"patient_name\":\"Mahi\",\"phone_number\":\"8938938439\",\"age\":21,\"chief_complain\":\"low_back_pain\",\"consultation_amount\":600}', '::1'),
(5, '2025-09-27 10:04:51', 1, 'admin', 2, 'CREATE', 'tests', 9, NULL, '{\"patient_name\":\"Mahi\",\"test_name\":\"vep\",\"assigned_test_date\":\"2025-09-28\",\"total_amount\":2000,\"payment_status\":\"partial\"}', '::1'),
(6, '2025-09-27 10:08:57', 1, 'admin', 2, 'UPDATE', 'quick_inquiry', 24, '{\"status\":\"cancelled\"}', '{\"status\":\"Visited\"}', '::1'),
(7, '2025-09-27 10:37:42', 1, 'admin', 2, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1'),
(8, '2025-09-27 10:51:36', 1, 'admin', 2, 'CREATE', 'expenses', 1, NULL, '{\"voucher_no\":\"1\",\"paid_to\":\"Tea stall\",\"amount\":50,\"status\":\"pending\"}', '::1'),
(9, '2025-09-27 17:19:34', 1, 'sumit', 2, 'CREATE', 'expenses', 2, NULL, '{\"voucher_no\":\"2\",\"paid_to\":\"Tea stall\",\"amount\":50,\"status\":\"pending\"}', '127.0.0.1'),
(10, '2025-09-28 10:51:04', 2, 'admin', 2, 'CREATE', 'expenses', 3, NULL, '{\"voucher_no\":\"3\",\"paid_to\":\"Supplies\",\"amount\":200,\"payment_method\":\"upi\",\"status\":\"pending\"}', '127.0.0.1'),
(11, '2025-09-29 15:41:50', 2, 'admin', 2, 'CREATE', 'registration', 18, NULL, '{\"patient_name\":\"Ravi\",\"phone_number\":\"7899839489\",\"age\":20,\"chief_complain\":\"radiating_pain\",\"consultation_amount\":600}', '127.0.0.1'),
(12, '2025-09-30 19:35:38', 2, 'admin', 2, 'CREATE', 'expenses', 4, NULL, '{\"voucher_no\":\"101\",\"amount\":200,\"status\":\"approved\"}', '127.0.0.1'),
(13, '2025-09-30 19:39:27', 2, 'admin', 2, 'CREATE', 'expenses', 5, NULL, '{\"voucher_no\":\"102\",\"amount\":1500,\"status\":\"pending\"}', '127.0.0.1'),
(14, '2025-09-30 19:55:27', 2, 'admin', 2, 'CREATE', 'expenses', 6, NULL, '{\"voucher_no\":\"101\",\"amount\":500,\"status\":\"approved\"}', '127.0.0.1'),
(15, '2025-09-30 20:10:03', 2, 'admin', 2, 'CREATE', 'expenses', 7, NULL, '{\"voucher_no\":\"102\",\"amount\":1200,\"status\":\"pending\"}', '127.0.0.1'),
(16, '2025-10-01 06:35:10', 2, 'admin', 2, 'CREATE', 'registration', 19, NULL, '{\"patient_name\":\"Bhumi\",\"phone_number\":\"8938934893\",\"new_patient_uid\":\"2510011\",\"master_patient_id\":\"1\",\"consultation_amount\":600}', '127.0.0.1'),
(17, '2025-10-01 19:41:14', 2, 'admin', 2, 'CREATE', 'expenses', 8, NULL, '{\"voucher_no\":\"103\",\"amount\":500,\"status\":\"approved\"}', '127.0.0.1'),
(18, '2025-10-01 19:45:42', 2, 'admin', 2, 'CREATE', 'expenses', 9, NULL, '{\"voucher_no\":\"104\",\"amount\":1500,\"status\":\"approved\"}', '127.0.0.1'),
(19, '2025-10-01 19:47:00', 2, 'admin', 2, 'CREATE', 'expenses', 10, NULL, '{\"voucher_no\":\"104\",\"amount\":1500,\"status\":\"pending\"}', '127.0.0.1'),
(20, '2025-10-02 11:26:58', 2, 'admin', 2, 'CREATE', 'registration', 20, NULL, '{\"patient_name\":\"Aditya\",\"phone_number\":\"8993849384\",\"age\":22,\"chief_complain\":\"low_back_pain\",\"consultation_amount\":600,\"converted_from_inquiry_id\":28}', '127.0.0.1'),
(21, '2025-10-02 11:26:58', 2, 'admin', 2, 'UPDATE', 'quick_inquiry', 28, '{\"status\":\"pending\"}', '{\"status\":\"visited\"}', '127.0.0.1'),
(22, '2025-10-02 11:31:01', 2, 'admin', 2, 'CREATE', 'registration', 21, NULL, '{\"patient_name\":\"Raj kumar\",\"phone_number\":\"7384783783\",\"new_patient_uid\":\"2510021\",\"master_patient_id\":\"2\",\"consultation_amount\":600,\"converted_from_inquiry_id\":27}', '127.0.0.1'),
(23, '2025-10-02 11:33:07', 2, 'admin', 2, 'CREATE', 'tests', 10, NULL, '{\"patient_name\":\"Aditya\",\"test_name\":\"bera\",\"assigned_test_date\":\"2025-10-03\",\"total_amount\":2000,\"payment_status\":\"partial\"}', '127.0.0.1'),
(24, '2025-10-02 11:33:12', 2, 'admin', 2, 'UPDATE', 'test_inquiry', 6, '{\"status\":\"pending\"}', '{\"status\":\"Visited\"}', '127.0.0.1'),
(25, '2025-10-02 11:35:00', 2, 'admin', 2, 'UPDATE', 'quick_inquiry', 28, '{\"status\":\"visited\"}', '{\"status\":\"Cancelled\"}', '127.0.0.1'),
(26, '2025-10-02 11:35:02', 2, 'admin', 2, 'UPDATE', 'quick_inquiry', 28, '{\"status\":\"cancelled\"}', '{\"status\":\"Visited\"}', '127.0.0.1'),
(27, '2025-10-02 11:35:05', 2, 'admin', 2, 'UPDATE', 'test_inquiry', 6, '{\"status\":\"visited\"}', '{\"status\":\"Cancelled\"}', '127.0.0.1'),
(28, '2025-10-02 11:35:06', 2, 'admin', 2, 'UPDATE', 'test_inquiry', 6, '{\"status\":\"cancelled\"}', '{\"status\":\"Visited\"}', '127.0.0.1'),
(29, '2025-10-02 17:44:47', 2, 'admin', 2, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1'),
(30, '2025-10-03 22:12:46', 2, 'admin', 2, 'CREATE', 'registration', 22, NULL, '{\"patient_name\":\"Priyanshu SIngh\",\"phone_number\":\"8993849849\",\"new_patient_uid\":\"2510041\",\"master_patient_id\":\"3\",\"consultation_amount\":600}', '127.0.0.1'),
(31, '2025-10-04 15:28:08', 2, 'admin', 2, 'UPDATE', 'registration', 22, '{\"status\":\"Consulted\"}', '{\"status\":\"Pending\"}', '127.0.0.1'),
(32, '2025-10-05 17:05:12', 2, 'admin', 2, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1'),
(33, '2025-10-05 19:31:12', 2, 'admin', 2, 'UPDATE', 'quick_inquiry', 28, '{\"status\":\"visited\"}', '{\"status\":\"Cancelled\"}', '192.168.1.58'),
(34, '2025-10-05 19:31:34', 2, 'admin', 2, 'UPDATE', 'quick_inquiry', 28, '{\"status\":\"cancelled\"}', '{\"status\":\"Visited\"}', '127.0.0.1'),
(35, '2025-10-05 20:38:03', 2, 'admin', 2, 'UPDATE', 'quick_inquiry', 28, '{\"status\":\"visited\"}', '{\"status\":\"Cancelled\"}', '127.0.0.1'),
(36, '2025-10-05 20:38:05', 2, 'admin', 2, 'UPDATE', 'quick_inquiry', 28, '{\"status\":\"cancelled\"}', '{\"status\":\"Visited\"}', '127.0.0.1'),
(37, '2025-10-05 20:45:32', 2, 'admin', 2, 'UPDATE', 'quick_inquiry', 28, '{\"status\":\"visited\"}', '{\"status\":\"Cancelled\"}', '127.0.0.1'),
(38, '2025-10-05 20:45:34', 2, 'admin', 2, 'UPDATE', 'quick_inquiry', 28, '{\"status\":\"cancelled\"}', '{\"status\":\"Visited\"}', '127.0.0.1'),
(39, '2025-10-06 19:31:04', 2, 'admin', 2, 'LOGOUT', NULL, NULL, NULL, NULL, '192.168.1.36'),
(40, '2025-10-08 17:44:47', 2, 'admin', 2, 'UPDATE', 'patients', 9, '{\"patient_photo_path\":\"old\"}', '{\"patient_photo_path\":\"uploads\\/patient_photos\\/patient_9_1759945487.jpg\"}', '127.0.0.1'),
(41, '2025-10-08 17:46:44', 2, 'admin', 2, 'UPDATE', 'patients', 2, '{\"patient_photo_path\":\"old\"}', '{\"patient_photo_path\":\"uploads\\/patient_photos\\/patient_2_1759945604.jpg\"}', '127.0.0.1'),
(42, '2025-10-09 18:03:03', 2, 'admin', 2, 'UPDATE', 'patients', 6, '{\"patient_photo_path\":\"old\"}', '{\"patient_photo_path\":\"uploads\\/patient_photos\\/patient_6_1760032983.jpg\"}', '127.0.0.1'),
(43, '2025-10-10 08:23:45', 2, 'admin', 2, 'CREATE', 'registration', 23, NULL, '{\"patient_name\":\"Test\",\"phone_number\":\"8394394839\",\"new_patient_uid\":\"2510101\",\"master_patient_id\":\"4\",\"consultation_amount\":600}', '127.0.0.1'),
(44, '2025-10-10 08:25:38', 2, 'admin', 2, 'CREATE', 'test_inquiry', 7, NULL, '{\"name\":\"Test\",\"testname\":\"ncv\",\"mobile_number\":\"7394394838\",\"reffered_by\":\"DR KK Menon\"}', '127.0.0.1'),
(45, '2025-10-10 08:51:52', 2, 'admin', 2, 'UPDATE', 'patients', 9, '{\"patient_photo_path\":\"old\"}', '{\"patient_photo_path\":\"uploads\\/patient_photos\\/patient_9_1760086312.jpg\"}', '127.0.0.1'),
(46, '2025-10-10 08:52:20', 2, 'admin', 2, 'UPDATE', 'patients', 9, '{\"patient_photo_path\":\"old\"}', '{\"patient_photo_path\":\"uploads\\/patient_photos\\/patient_9_1760086340.jpg\"}', '127.0.0.1'),
(47, '2025-10-10 09:01:20', 2, 'admin', 2, 'UPDATE', 'patients', 9, '{\"patient_photo_path\":\"old\"}', '{\"patient_photo_path\":\"uploads\\/patient_photos\\/patient_9_1760086880.jpg\"}', '127.0.0.1'),
(48, '2025-10-10 09:08:53', 2, 'admin', 2, 'UPDATE', 'registration', 23, '{\"patient_photo_path\":\"old\"}', '{\"patient_photo_path\":\"uploads\\/patient_photos\\/reg_23_1760087333.jpeg\"}', '127.0.0.1'),
(49, '2025-10-10 09:10:33', 2, 'admin', 2, 'UPDATE', 'registration', 22, '{\"patient_photo_path\":\"old\"}', '{\"patient_photo_path\":\"uploads\\/patient_photos\\/reg_22_1760087433.jpeg\"}', '127.0.0.1'),
(50, '2025-10-10 09:12:18', 2, 'admin', 2, 'UPDATE', 'registration', 21, '{\"patient_photo_path\":\"old\"}', '{\"patient_photo_path\":\"uploads\\/patient_photos\\/reg_21_1760087538.jpeg\"}', '127.0.0.1'),
(51, '2025-10-10 09:34:03', 2, 'admin', 2, 'UPDATE', 'registration', 22, '{\"patient_photo_path\":\"old\"}', '{\"patient_photo_path\":\"uploads\\/patient_photos\\/reg_22_1760088843.jpeg\"}', '127.0.0.1'),
(52, '2025-10-10 11:51:12', 2, 'admin', 2, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1'),
(53, '2025-10-11 10:44:31', 2, 'admin', 2, 'CREATE', 'tests', 11, NULL, '{\"patient_name\":\"Sumit Sri\",\"test_uid\":\"25101101\",\"test_name\":\"rns\",\"assigned_test_date\":\"2025-10-11\",\"total_amount\":2000,\"payment_status\":\"partial\"}', '127.0.0.1'),
(54, '2025-10-11 17:46:48', 2, 'admin', 2, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1'),
(55, '2025-10-11 17:52:52', 2, 'admin', 2, 'CREATE', 'quick_inquiry', 29, NULL, '{\"name\":\"Ram\",\"age\":\"20\",\"phone_number\":\"8973743743\",\"referralSource\":\"social_media\"}', '127.0.0.1'),
(56, '2025-10-11 17:55:11', 2, 'admin', 2, 'CREATE', 'test_inquiry', 8, NULL, '{\"name\":\"Lakshman\",\"testname\":\"emg\",\"mobile_number\":\"8938394839\",\"reffered_by\":\"Dr Pranav\"}', '127.0.0.1'),
(57, '2025-10-11 18:01:51', 2, 'admin', 2, 'CREATE', 'registration', 24, NULL, '{\"patient_name\":\"Raj\",\"phone_number\":\"8394394399\",\"new_patient_uid\":\"2510111\",\"master_patient_id\":\"5\",\"consultation_amount\":600}', '127.0.0.1'),
(58, '2025-10-11 18:03:59', 2, 'admin', 2, 'UPDATE', 'registration', 24, '{\"patient_photo_path\":\"old\"}', '{\"patient_photo_path\":\"uploads\\/patient_photos\\/reg_24_1760205839.jpeg\"}', '127.0.0.1'),
(59, '2025-10-11 18:30:18', 2, 'admin', 2, 'CREATE', 'expenses', 11, NULL, '{\"voucher_no\":\"105\",\"amount\":800,\"status\":\"approved\"}', '127.0.0.1'),
(60, '2025-10-11 18:31:15', 2, 'admin', 2, 'CREATE', 'expenses', 12, NULL, '{\"voucher_no\":\"106\",\"amount\":1000,\"status\":\"approved\"}', '127.0.0.1'),
(61, '2025-10-11 18:32:37', 2, 'admin', 2, 'CREATE', 'expenses', 13, NULL, '{\"voucher_no\":\"107\",\"amount\":800,\"status\":\"pending\"}', '127.0.0.1'),
(62, '2025-10-12 12:19:55', 2, 'admin', 2, 'CREATE', 'quick_inquiry', 30, NULL, '{\"name\":\"test\",\"age\":\"22\",\"phone_number\":\"7868766767\",\"referralSource\":\"social_media\",\"inquiry_type\":\"physio\",\"communication_type\":\"phone\"}', '127.0.0.1'),
(65, '2025-10-12 18:00:12', 2, 'admin', 2, 'CREATE', 'tokens', 1, NULL, '{\"token_uid\":\"T251012-01\",\"patient_id\":10}', '127.0.0.1'),
(66, '2025-10-12 20:41:06', 2, 'admin', 2, 'CREATE', 'patients', 11, NULL, '{\"service_type\":\"speech_therapy\",\"total_amount\":11000}', '127.0.0.1'),
(67, '2025-10-12 20:44:24', 2, 'admin', 2, 'CREATE', 'patients', 12, NULL, '{\"service_type\":\"speech_therapy\",\"total_amount\":2500}', '127.0.0.1'),
(68, '2025-10-12 20:58:49', 2, 'admin', 2, 'CREATE', 'registration', 25, NULL, '{\"patient_name\":\"speech test\",\"phone_number\":\"8998948398\",\"new_patient_uid\":\"2510131\",\"master_patient_id\":\"6\",\"consultation_amount\":600}', '127.0.0.1'),
(69, '2025-10-12 20:59:41', 2, 'admin', 2, 'CREATE', 'patients', 13, NULL, '{\"service_type\":\"speech_therapy\",\"total_amount\":10450}', '127.0.0.1'),
(70, '2025-10-12 21:00:42', 2, 'admin', 2, 'CREATE', 'tokens', 2, NULL, '{\"token_uid\":\"T251013-01\",\"patient_id\":13}', '127.0.0.1'),
(71, '2025-10-12 21:02:02', 2, 'admin', 2, 'CREATE', 'tokens', 3, NULL, '{\"token_uid\":\"T251013-02\",\"patient_id\":10}', '127.0.0.1'),
(72, '2025-10-12 21:10:44', 2, 'admin', 2, 'CREATE', 'registration', 26, NULL, '{\"patient_name\":\"frnglnk\",\"phone_number\":\"4444444444\",\"new_patient_uid\":\"2510132\",\"master_patient_id\":\"7\",\"consultation_amount\":600}', '192.168.1.44'),
(73, '2025-10-12 21:12:24', 2, 'admin', 2, 'CREATE', 'patients', 14, NULL, '{\"service_type\":\"speech_therapy\",\"total_amount\":3220}', '192.168.1.44'),
(74, '2025-10-12 21:13:30', 2, 'admin', 2, 'CREATE', 'tokens', 4, NULL, '{\"token_uid\":\"T251013-03\",\"patient_id\":14}', '127.0.0.1'),
(75, '2025-10-13 13:49:29', 2, 'admin', 2, 'CREATE', 'patients', 20, NULL, '{\"service_type\":\"speech_therapy\",\"total_amount\":11000}', '192.168.1.44'),
(76, '2025-10-13 17:16:57', 2, 'admin', 2, 'CREATE', 'patient_appointments', 7, NULL, '{\"patient_id\":1,\"date\":\"2025-10-13\",\"time\":\"09:00\",\"service\":\"physio\"}', '127.0.0.1'),
(77, '2025-10-13 17:27:08', 2, 'admin', 2, 'CREATE', 'patient_appointments', 8, NULL, '{\"patient_id\":16,\"date\":\"2025-10-13\",\"time\":\"16:00\",\"service\":\"speech_therapy\"}', '127.0.0.1'),
(78, '2025-10-13 17:30:01', 2, 'admin', 2, 'CREATE', 'patient_appointments', 9, NULL, '{\"patient_id\":20,\"date\":\"2025-10-14\",\"time\":\"15:00\",\"service\":\"speech_therapy\"}', '127.0.0.1'),
(79, '2025-10-13 17:32:19', 2, 'admin', 2, 'CREATE', 'patient_appointments', 10, NULL, '{\"patient_id\":8,\"date\":\"2025-10-13\",\"time\":\"10:30\",\"service\":\"physio\"}', '127.0.0.1'),
(80, '2025-10-13 17:40:33', 2, 'admin', 2, 'CREATE', 'patient_appointments', 11, NULL, '{\"patient_id\":10,\"date\":\"2025-10-13\",\"time\":\"09:00\",\"service\":\"physio\"}', '127.0.0.1'),
(81, '2025-10-13 17:41:02', 2, 'admin', 2, 'CREATE', 'patient_appointments', 12, NULL, '{\"patient_id\":15,\"date\":\"2025-10-13\",\"time\":\"09:00\",\"service\":\"physio\"}', '127.0.0.1'),
(82, '2025-10-13 18:14:38', 2, 'admin', 2, 'CREATE', 'registration', 27, NULL, '{\"patient_name\":\"Aditya kumar singh\",\"phone_number\":\"8303057557\",\"new_patient_uid\":\"2510133\",\"master_patient_id\":\"8\",\"consultation_amount\":600}', '192.168.1.44'),
(83, '2025-10-13 20:41:12', 2, 'admin', 2, 'CREATE', 'tests', 12, NULL, '{\"patient_id\":21,\"test_name\":\"eeg\"}', '127.0.0.1'),
(84, '2025-10-13 21:29:58', 2, 'admin', 2, 'CREATE', 'test_items', 1, NULL, '{\"test_id\":12,\"test_name\":\"ncv\"}', '127.0.0.1'),
(85, '2025-10-13 21:47:48', 2, 'admin', 2, 'UPDATE', 'tests', 12, '{\"test_status\":\"previous\"}', '{\"test_status\":\"completed\"}', '127.0.0.1'),
(86, '2025-10-13 21:47:54', 2, 'admin', 2, 'UPDATE', 'tests', 12, '{\"test_status\":\"previous\"}', '{\"test_status\":\"cancelled\"}', '127.0.0.1'),
(87, '2025-10-13 21:47:58', 2, 'admin', 2, 'UPDATE', 'tests', 12, '{\"test_status\":\"previous\"}', '{\"test_status\":\"pending\"}', '127.0.0.1'),
(88, '2025-10-13 21:48:03', 2, 'admin', 2, 'UPDATE', 'tests', 12, '{\"payment_status\":\"previous\"}', '{\"payment_status\":\"pending\"}', '127.0.0.1'),
(89, '2025-10-13 21:48:58', 2, 'admin', 2, 'UPDATE', 'test_items', 1, '{\"test_status\":\"previous\"}', '{\"test_status\":\"completed\"}', '127.0.0.1'),
(90, '2025-10-13 21:51:27', 2, 'admin', 2, 'UPDATE', 'tests', 12, '{\"paid\":100}', '{\"new_due\":200,\"payment_status\":\"partial\"}', '127.0.0.1'),
(91, '2025-10-13 21:51:35', 2, 'admin', 2, 'UPDATE', 'test_items', 1, '{\"paid\":100}', '{\"new_due\":200,\"payment_status\":\"partial\"}', '127.0.0.1'),
(92, '2025-10-13 21:54:31', 2, 'admin', 2, 'UPDATE', 'test_items', 1, '{\"paid\":200}', '{\"new_due\":0,\"payment_status\":\"paid\"}', '127.0.0.1'),
(93, '2025-10-13 21:54:39', 2, 'admin', 2, 'UPDATE', 'tests', 12, '{\"paid\":200}', '{\"new_due\":0,\"payment_status\":\"paid\"}', '127.0.0.1'),
(94, '2025-10-13 21:54:45', 2, 'admin', 2, 'UPDATE', 'tests', 12, '{\"test_status\":\"previous\"}', '{\"test_status\":\"completed\"}', '127.0.0.1'),
(95, '2025-10-14 09:23:13', 2, 'admin', 2, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1'),
(96, '2025-10-14 11:16:14', 2, 'admin', 2, 'LOGOUT', NULL, NULL, NULL, NULL, '127.0.0.1'),
(97, '2025-10-14 12:34:37', 2, 'admin', 2, 'UPDATE', 'tests', 11, '{\"test_status\":\"previous\"}', '{\"test_status\":\"cancelled\"}', '127.0.0.1'),
(98, '2025-10-14 12:54:31', 2, 'admin', 2, 'UPDATE', 'tests', 12, '{\"test_status\":\"previous\"}', '{\"test_status\":\"cancelled\"}', '127.0.0.1'),
(99, '2025-10-14 12:56:05', 2, 'admin', 2, 'UPDATE', 'test_items', 1, '{\"test_status\":\"previous\"}', '{\"test_status\":\"cancelled\"}', '127.0.0.1'),
(100, '2025-10-14 12:56:15', 2, 'admin', 2, 'UPDATE', 'test_items', 1, '{\"test_status\":\"previous\"}', '{\"test_status\":\"completed\"}', '127.0.0.1'),
(101, '2025-10-14 13:01:40', 2, 'admin', 2, 'UPDATE', 'tests', 12, '{\"test_status\":\"previous\"}', '{\"test_status\":\"cancelled\"}', '127.0.0.1'),
(102, '2025-10-14 13:01:50', 2, 'admin', 2, 'UPDATE', 'test_items', 1, '{\"test_status\":\"previous\"}', '{\"test_status\":\"cancelled\"}', '127.0.0.1'),
(103, '2025-10-14 13:02:34', 2, 'admin', 2, 'UPDATE', 'tests', 12, '{\"test_status\":\"previous\"}', '{\"test_status\":\"completed\"}', '127.0.0.1'),
(104, '2025-10-14 13:11:49', 2, 'admin', 2, 'UPDATE', 'tests', 11, '{\"test_status\":\"previous\"}', '{\"test_status\":\"cancelled\"}', '127.0.0.1'),
(105, '2025-10-14 13:11:49', 2, 'admin', 2, 'UPDATE', 'tests', 11, '{\"test_status\":\"previous\"}', '{\"test_status\":\"cancelled\"}', '127.0.0.1'),
(106, '2025-10-14 13:54:28', 2, 'admin', 2, 'UPDATE', 'test_items', 1, '{\"test_status\":\"previous\"}', '{\"test_status\":\"pending\"}', '127.0.0.1'),
(107, '2025-10-14 13:54:50', 2, 'admin', 2, 'UPDATE', 'tests', 10, '{\"test_status\":\"previous\"}', '{\"test_status\":\"cancelled\"}', '127.0.0.1'),
(108, '2025-10-14 13:54:50', 2, 'admin', 2, 'UPDATE', 'tests', 10, '{\"test_status\":\"previous\"}', '{\"test_status\":\"cancelled\"}', '127.0.0.1'),
(109, '2025-10-14 13:55:02', 2, 'admin', 2, 'UPDATE', 'tests', 10, '{\"test_status\":\"previous\"}', '{\"test_status\":\"pending\"}', '127.0.0.1'),
(110, '2025-10-14 13:55:44', 2, 'admin', 2, 'UPDATE', 'tests', 11, '{\"refund_status\":\"no\"}', '{\"refund_amount\":1600,\"reason\":\"who knows\",\"new_status\":\"initiated\"}', '127.0.0.1'),
(111, '2025-10-14 14:00:59', 2, 'admin', 2, 'UPDATE', 'test_items', 1, '{\"test_status\":\"previous\"}', '{\"test_status\":\"cancelled\"}', '127.0.0.1'),
(112, '2025-10-14 14:01:00', 2, 'admin', 2, 'UPDATE', 'tests', 12, '{\"test_status\":\"previous\"}', '{\"test_status\":\"cancelled\"}', '127.0.0.1'),
(113, '2025-10-14 14:05:26', 2, 'admin', 2, 'UPDATE', 'test_items', 1, '{\"refund_status\":\"no\"}', '{\"refund_amount\":1800,\"reason\":\"\",\"new_status\":\"initiated\"}', '127.0.0.1'),
(114, '2025-10-14 14:05:42', 2, 'admin', 2, 'UPDATE', 'tests', 12, '{\"refund_status\":\"no\"}', '{\"refund_amount\":1800,\"reason\":\"dont like the service\",\"new_status\":\"initiated\"}', '127.0.0.1');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(100) NOT NULL COMMENT 'Short name for display in UI (e.g., Siliguri)',
  `clinic_name` varchar(255) NOT NULL COMMENT 'The full legal or display name of the clinic',
  `address_line_1` varchar(255) DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `phone_primary` varchar(20) DEFAULT NULL,
  `phone_secondary` varchar(100) DEFAULT NULL COMMENT 'Can store multiple numbers, comma-separated',
  `email` varchar(120) DEFAULT NULL,
  `logo_primary_path` varchar(255) DEFAULT NULL COMMENT 'e.g., /assets/logos/prospine_logo.png',
  `logo_secondary_path` varchar(255) DEFAULT NULL COMMENT 'e.g., /assets/logos/manipal_logo.png',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Allows deactivating a branch instead of deleting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`, `clinic_name`, `address_line_1`, `address_line_2`, `city`, `state`, `pincode`, `phone_primary`, `phone_secondary`, `email`, `logo_primary_path`, `logo_secondary_path`, `is_active`, `created_at`) VALUES
(1, 'Bhagalpur', 'ProSpine - Ortho & Neuro Rehab', 'Swami Vivika Nand Road', 'Adampur Chowk', 'Bhagalpur', 'Bihar', '812002', '+91-8002910021', '9304414144, 8002421212', NULL, 'NULL', 'NULL', 1, '2025-10-02 13:20:17'),
(2, 'Siliguri', 'ProSpine Siliguri', 'Swami Vivika Nand Road', NULL, 'Siliguri', 'West Bengal', NULL, '+91-8002910021', '9304414144, 8002421212', NULL, 'uploads/logos/branch_2_primary_1759412280.png', 'uploads/logos/branch_2_secondary_1759412293.png', 1, '2025-10-02 13:25:32');

-- --------------------------------------------------------

--
-- Table structure for table `branch_budgets`
--

CREATE TABLE `branch_budgets` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `daily_budget_amount` decimal(10,2) NOT NULL,
  `effective_from_date` date NOT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `branch_budgets`
--

INSERT INTO `branch_budgets` (`id`, `branch_id`, `daily_budget_amount`, `effective_from_date`, `created_by_user_id`, `created_at`) VALUES
(1, 1, 1000.00, '2025-01-01', 1, '2025-09-30 19:53:34'),
(2, 2, 1500.00, '2025-01-01', 1, '2025-09-30 19:53:50');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--


-- --------------------------------------------------------

--
-- Table structure for table `daily_patient_counter`
--

CREATE TABLE `daily_patient_counter` (
  `entry_date` date NOT NULL,
  `counter` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `daily_patient_counter`
--

INSERT INTO `daily_patient_counter` (`entry_date`, `counter`) VALUES
('2025-10-01', 1),
('2025-10-02', 1),
('2025-10-04', 1),
('2025-10-10', 1),
('2025-10-11', 1),
('2025-10-13', 3);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `voucher_no` varchar(50) NOT NULL,
  `expense_date` date NOT NULL,
  `paid_to` varchar(255) NOT NULL,
  `expense_done_by` varchar(100) DEFAULT NULL COMMENT 'Name of person who did the expense',
  `expense_for` varchar(100) DEFAULT NULL COMMENT 'Purpose of the expense, e.g., Office, Marketing',
  `description` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `amount_in_words` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  `approved_by_user_id` int(11) DEFAULT 1,
  `approved_at` timestamp NULL DEFAULT NULL,
  `authorized_by_user_id` int(11) DEFAULT NULL COMMENT '\r\n',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_method` enum('cash','upi','cheque','credit_card','debit_card','net_banking','other') NOT NULL,
  `bill_image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `branch_id`, `user_id`, `voucher_no`, `expense_date`, `paid_to`, `expense_done_by`, `expense_for`, `description`, `amount`, `amount_in_words`, `status`, `approved_by_user_id`, `approved_at`, `authorized_by_user_id`, `created_at`, `updated_at`, `payment_method`, `bill_image_path`) VALUES
(6, 2, 2, '101', '2025-09-30', 'Laundry', NULL, NULL, 'Blankets gone to laundry for cleaning', 500.00, 'Rupees five hundred Only', 'approved', NULL, NULL, NULL, '2025-09-30 19:55:27', '2025-09-30 19:59:00', 'cash', 'uploads/expenses/expense_6_1759262340.jpeg'),
(7, 2, 2, '102', '2025-09-30', 'gghfyf', NULL, NULL, 'tryrytryut', 200.00, 'Rupees one thousand two hundred Only', 'approved', 1, NULL, NULL, '2025-09-30 20:10:03', '2025-09-30 20:20:25', 'cash', 'uploads/expenses/expense_7_1759263046.jpeg'),
(8, 2, 2, '103', '2025-10-01', 'Laundary', NULL, NULL, 'Laudary for blanket cleaning', 500.00, 'Rupees five hundred Only', 'approved', 1, NULL, NULL, '2025-10-01 19:41:14', '2025-10-01 19:42:26', 'cash', 'uploads/expenses/expense_8_1759347746.png'),
(10, 2, 2, '104', '2025-10-01', 'stationary', NULL, NULL, 'dfkjdfjdskjf', 1500.00, 'Rupees one thousand five hundred Only', 'approved', 1, NULL, NULL, '2025-10-01 19:47:00', '2025-10-06 19:38:34', 'upi', 'uploads/expenses/expense_10_1759779514.jpeg'),
(11, 2, 2, '105', '2025-10-11', 'Printer', NULL, NULL, 'printer ink', 800.00, 'Rupees eight hundred Only', 'approved', 1, NULL, NULL, '2025-10-11 18:30:18', '2025-10-11 18:30:18', 'upi', NULL),
(12, 2, 2, '106', '2025-10-12', 'Electrician', NULL, NULL, 'wires', 1000.00, 'Rupees one thousand Only', 'approved', 1, NULL, NULL, '2025-10-11 18:31:15', '2025-10-11 18:31:15', 'upi', NULL),
(13, 2, 2, '107', '2025-10-12', 'misc', NULL, NULL, 'copy, pen', 800.00, 'Rupees eight hundred Only', 'approved', 1, NULL, NULL, '2025-10-11 18:32:37', '2025-10-11 18:33:20', 'cash', 'uploads/expenses/expense_13_1760207600.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','reviewed','accepted','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip` varbinary(16) NOT NULL,
  `attempt_count` int(11) NOT NULL DEFAULT 0,
  `last_attempt` timestamp NULL DEFAULT NULL,
  `locked_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `username`, `ip`, `attempt_count`, `last_attempt`, `locked_until`) VALUES
(1, 'DONkumar', 0x7f000001, 1, '2025-09-14 14:59:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `master_patient_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `registration_id` int(11) DEFAULT NULL,
  `assigned_doctor` varchar(255) NOT NULL DEFAULT 'Not Assigned',
  `service_type` varchar(50) NOT NULL DEFAULT 'physio' COMMENT 'e.g., physio, speech_therapy',
  `treatment_type` enum('daily','advance','package') NOT NULL,
  `treatment_cost_per_day` decimal(10,2) DEFAULT NULL,
  `package_cost` decimal(10,2) DEFAULT NULL,
  `treatment_days` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','upi','cheque','other') NOT NULL DEFAULT 'cash',
  `treatment_time_slot` time DEFAULT NULL COMMENT 'Initial time slot chosen at registration',
  `advance_payment` decimal(10,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_approved_by` int(11) DEFAULT NULL,
  `due_amount` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','completed','inactive') NOT NULL DEFAULT 'active',
  `patient_photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `master_patient_id`, `branch_id`, `registration_id`, `assigned_doctor`, `service_type`, `treatment_type`, `treatment_cost_per_day`, `package_cost`, `treatment_days`, `total_amount`, `payment_method`, `treatment_time_slot`, `advance_payment`, `discount_percentage`, `discount_approved_by`, `due_amount`, `start_date`, `end_date`, `status`, `patient_photo_path`, `created_at`, `updated_at`, `remarks`) VALUES
(1, NULL, 2, 5, 'Not Assigned', 'physio', 'advance', 1000.00, NULL, 5, 5000.00, 'cash', NULL, 6000.00, 0.00, NULL, -1000.00, '2025-08-27', '2025-08-31', 'active', NULL, '2025-08-27 16:34:05', '2025-10-13 17:37:25', NULL),
(2, NULL, 2, 9, 'Not Assigned', 'physio', 'package', NULL, 27000.00, 21, 27000.00, 'upi', NULL, 18000.00, 10.00, NULL, 9000.00, '2025-09-13', '2025-10-03', 'active', NULL, '2025-09-13 06:20:04', '2025-10-10 09:39:41', NULL),
(3, NULL, 2, 8, 'Not Assigned', 'physio', 'daily', 600.00, NULL, 5, 3000.00, 'cash', NULL, 3000.00, 0.00, NULL, 0.00, '2025-09-13', '2025-09-17', 'active', NULL, '2025-09-13 10:27:49', '2025-10-02 15:33:04', NULL),
(4, NULL, 2, 7, 'Not Assigned', 'physio', 'advance', 900.00, NULL, 10, 9000.00, 'upi', NULL, 2700.00, 10.00, NULL, 6300.00, '2025-09-13', '2025-09-22', 'active', NULL, '2025-09-13 11:00:58', '2025-09-20 17:24:26', NULL),
(5, NULL, 2, 13, 'Not Assigned', 'physio', 'advance', 900.00, NULL, 10, 9000.00, 'upi', NULL, 5400.00, 10.00, NULL, 3600.00, '2025-09-21', '2025-09-30', 'active', NULL, '2025-09-19 19:29:38', '2025-10-02 18:00:30', NULL),
(6, NULL, 2, 10, 'Not Assigned', 'physio', 'daily', 540.00, NULL, 10, 5400.00, 'upi', NULL, 5000.00, 10.00, NULL, 400.00, '2025-09-25', '2025-10-04', 'active', NULL, '2025-09-25 11:16:25', '2025-10-10 09:39:44', NULL),
(7, NULL, 2, 17, 'Not Assigned', 'physio', 'daily', 540.00, NULL, 10, 5400.00, 'upi', NULL, 4000.00, 10.00, NULL, 1400.00, '2025-09-29', '2025-10-08', 'active', NULL, '2025-09-27 18:04:32', '2025-09-27 18:04:32', NULL),
(8, NULL, 2, 21, 'Not Assigned', 'physio', 'package', NULL, 27000.00, 21, 27000.00, 'upi', NULL, 20000.00, 10.00, NULL, 7000.00, '2025-10-03', '2025-10-23', 'active', NULL, '2025-10-02 16:26:32', '2025-10-04 09:59:31', NULL),
(9, NULL, 2, 22, 'Not Assigned', 'physio', 'advance', 850.00, NULL, 10, 8500.00, 'upi', NULL, 4000.00, 15.00, 2, 4500.00, '2025-10-04', '2025-10-13', 'active', NULL, '2025-10-04 10:21:02', '2025-10-11 14:05:26', NULL),
(10, NULL, 2, 24, 'Not Assigned', 'physio', 'advance', 950.00, NULL, 5, 4750.00, 'upi', NULL, 3000.00, 5.00, 1, 1750.00, '2025-10-12', '2025-10-16', 'active', NULL, '2025-10-11 18:07:57', '2025-10-11 18:07:57', NULL),
(11, NULL, 2, 15, 'Not Assigned', 'speech_therapy', 'package', NULL, NULL, 26, 11000.00, 'upi', NULL, 5000.00, 0.00, NULL, 6000.00, '2025-10-13', '2025-11-02', 'active', NULL, '2025-10-12 20:41:06', '2025-10-12 20:53:24', NULL),
(12, NULL, 2, 19, 'Not Assigned', 'speech_therapy', 'daily', NULL, NULL, 5, 2500.00, 'cash', NULL, 2000.00, 0.00, NULL, 500.00, '2025-10-13', '2025-10-17', 'active', NULL, '2025-10-12 20:44:24', '2025-10-12 20:44:24', NULL),
(13, NULL, 2, 25, 'Not Assigned', 'speech_therapy', 'package', NULL, 10450.00, 26, 10450.00, 'upi', NULL, 8000.00, 5.00, 2, 2450.00, '2025-10-13', '2025-11-07', 'active', NULL, '2025-10-12 20:59:41', '2025-10-12 20:59:41', NULL),
(14, NULL, 2, 26, 'Not Assigned', 'speech_therapy', 'daily', 460.00, NULL, 7, 3220.00, 'upi', NULL, 460.00, 8.00, 2, 2760.00, '2025-10-13', '2025-10-19', 'active', NULL, '2025-10-12 21:12:24', '2025-10-12 21:13:21', NULL),
(15, NULL, 2, 23, 'Not Assigned', 'physio', 'daily', 600.00, NULL, 8, 4800.00, 'upi', '09:00:00', 1000.00, 0.00, NULL, 3800.00, '2025-10-14', '2025-10-21', 'active', NULL, '2025-10-13 13:32:23', '2025-10-13 13:32:23', NULL),
(16, NULL, 2, 6, 'Not Assigned', 'physio', 'package', NULL, 30000.00, 21, 30000.00, 'upi', '09:00:00', 10000.00, 0.00, NULL, 20000.00, '2025-10-14', '2025-11-03', 'active', NULL, '2025-10-13 13:47:32', '2025-10-13 13:47:32', NULL),
(20, NULL, 2, 3, 'Not Assigned', 'speech_therapy', 'package', NULL, 11000.00, 26, 11000.00, 'upi', '15:00:00', 11000.00, 0.00, NULL, 0.00, '2025-10-14', '2025-11-08', 'active', NULL, '2025-10-13 13:49:29', '2025-10-13 17:33:41', NULL),
(21, NULL, 2, 27, 'Not Assigned', 'physio', 'package', NULL, 30000.00, 21, 30000.00, 'upi', '09:00:00', 5000.00, 0.00, NULL, 25000.00, '2025-10-14', '2025-11-03', 'active', NULL, '2025-10-13 18:16:18', '2025-10-13 18:16:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `patients_treatment`
--

CREATE TABLE `patients_treatment` (
  `treatment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `treatment_type` enum('daily','advance','package') NOT NULL,
  `treatment_cost_per_day` decimal(10,2) DEFAULT 0.00,
  `package_cost` decimal(10,2) DEFAULT 0.00,
  `treatment_days` int(11) DEFAULT 0,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `advance_payment` decimal(10,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `due_amount` decimal(10,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','completed','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_appointments`
--

CREATE TABLE `patient_appointments` (
  `appointment_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `time_slot` time NOT NULL,
  `service_type` varchar(50) NOT NULL COMMENT 'e.g., physio, speech_therapy',
  `status` varchar(50) NOT NULL DEFAULT 'scheduled' COMMENT 'e.g., scheduled, completed, cancelled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_appointments`
--

INSERT INTO `patient_appointments` (`appointment_id`, `patient_id`, `branch_id`, `appointment_date`, `time_slot`, `service_type`, `status`, `created_at`) VALUES
(1, 15, 2, '2025-10-12', '09:00:00', 'physio', 'scheduled', '2025-10-13 13:32:23'),
(2, 16, 2, '2025-10-13', '09:00:00', 'physio', 'scheduled', '2025-10-13 13:47:32'),
(6, 20, 2, '2025-10-13', '15:00:00', 'speech_therapy', 'scheduled', '2025-10-13 13:49:29'),
(7, 1, 2, '2025-10-13', '09:00:00', 'physio', 'scheduled', '2025-10-13 17:16:57'),
(8, 16, 2, '2025-10-13', '16:00:00', 'speech_therapy', 'scheduled', '2025-10-13 17:27:08'),
(9, 20, 2, '2025-10-14', '15:00:00', 'speech_therapy', 'scheduled', '2025-10-13 17:30:01'),
(10, 8, 2, '2025-10-13', '10:30:00', 'physio', 'scheduled', '2025-10-13 17:32:19'),
(11, 10, 2, '2025-10-13', '09:00:00', 'physio', 'scheduled', '2025-10-13 17:40:33'),
(12, 15, 2, '2025-10-13', '09:00:00', 'physio', 'scheduled', '2025-10-13 17:41:02'),
(13, 21, 2, '2025-10-14', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(14, 21, 2, '2025-10-15', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(15, 21, 2, '2025-10-16', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(16, 21, 2, '2025-10-17', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(17, 21, 2, '2025-10-18', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(18, 21, 2, '2025-10-19', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(19, 21, 2, '2025-10-20', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(20, 21, 2, '2025-10-21', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(21, 21, 2, '2025-10-22', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(22, 21, 2, '2025-10-23', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(23, 21, 2, '2025-10-24', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(24, 21, 2, '2025-10-25', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(25, 21, 2, '2025-10-26', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(26, 21, 2, '2025-10-27', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(27, 21, 2, '2025-10-28', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(28, 21, 2, '2025-10-29', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(29, 21, 2, '2025-10-30', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(30, 21, 2, '2025-10-31', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(31, 21, 2, '2025-11-01', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(32, 21, 2, '2025-11-02', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18'),
(33, 21, 2, '2025-11-03', '09:00:00', 'physio', 'scheduled', '2025-10-13 18:16:18');

-- --------------------------------------------------------

--
-- Table structure for table `patient_master`
--

CREATE TABLE `patient_master` (
  `master_patient_id` bigint(20) UNSIGNED NOT NULL,
  `patient_uid` varchar(20) NOT NULL COMMENT 'The human-readable YYMMDD-S.No ID',
  `full_name` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `first_registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `first_registered_branch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_master`
--

INSERT INTO `patient_master` (`master_patient_id`, `patient_uid`, `full_name`, `phone_number`, `gender`, `age`, `first_registered_at`, `first_registered_branch_id`) VALUES
(1, '2510011', 'Bhumi', '8938934893', 'Female', 22, '2025-10-01 06:35:10', 2),
(2, '2510021', 'Raj kumar', '7384783783', 'Male', 20, '2025-10-02 11:31:01', 2),
(3, '2510041', 'Priyanshu SIngh', '8993849849', 'Male', 22, '2025-10-03 22:12:46', 2),
(4, '2510101', 'Test', '8394394839', 'Male', 22, '2025-10-10 08:23:45', 2),
(5, '2510111', 'Raj', '8394394399', 'Male', 22, '2025-10-11 18:01:51', 2),
(6, '2510131', 'speech test', '8998948398', 'Male', 22, '2025-10-12 20:58:49', 2),
(7, '2510132', 'frnglnk', '4444444444', 'Male', 54, '2025-10-12 21:10:44', 2),
(8, '2510133', 'Aditya kumar singh', '8303057557', 'Male', 22, '2025-10-13 18:14:38', 2);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `mode` enum('cash','card','upi','other') DEFAULT 'cash',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `patient_id`, `payment_date`, `amount`, `mode`, `remarks`, `created_at`) VALUES
(1, 1, '2025-08-27', 1000.00, 'cash', 'Advance attendance marked', '2025-08-27 16:37:42'),
(2, 1, '2025-09-02', 1000.00, 'upi', 'Advance attendance marked', '2025-09-02 18:08:53'),
(3, 1, '2025-09-03', 1000.00, 'upi', 'Advance attendance marked', '2025-09-03 05:11:30'),
(4, 1, '2025-09-04', 1000.00, 'upi', 'Advance attendance marked', '2025-09-04 04:39:29'),
(5, 1, '2025-09-05', 1000.00, 'cash', 'Advance attendance marked', '2025-09-04 19:09:55'),
(6, 2, '2025-09-13', 18000.00, 'upi', 'Initial advance payment', '2025-09-13 06:20:04'),
(7, 3, '2025-09-13', 500.00, 'cash', 'Initial advance payment', '2025-09-13 10:27:49'),
(9, 3, '2025-09-13', 100.00, 'cash', 'Daily attendance marked', '2025-09-13 10:59:31'),
(10, 4, '2025-09-13', 600.00, 'upi', 'Initial advance payment', '2025-09-13 11:00:58'),
(11, 4, '2025-09-13', 300.00, 'upi', 'Advance attendance marked', '2025-09-13 11:07:39'),
(12, 4, '2025-09-19', 900.00, 'cash', 'Advance attendance marked', '2025-09-19 13:56:31'),
(13, 3, '2025-09-19', 600.00, 'upi', 'Daily attendance marked', '2025-09-19 13:56:50'),
(14, 5, '2025-09-20', 500.00, 'upi', 'Initial advance payment', '2025-09-19 19:29:38'),
(15, 5, '2025-09-20', 400.00, 'upi', 'Advance attendance marked', '2025-09-19 19:30:50'),
(16, 4, '2025-09-20', 900.00, 'cash', 'Advance attendance marked', '2025-09-20 17:24:26'),
(17, 3, '2025-09-20', 1500.00, 'cash', 'Daily attendance marked', '2025-09-20 17:44:39'),
(18, 5, '2025-09-24', 900.00, 'upi', 'Advance attendance marked', '2025-09-23 19:14:44'),
(19, 5, '2025-09-25', 900.00, 'cash', 'Advance attendance marked', '2025-09-25 11:03:42'),
(20, 6, '2025-09-25', 5000.00, 'upi', 'Initial advance payment', '2025-09-25 11:16:25'),
(21, 5, '2025-09-26', 900.00, 'upi', 'Advance attendance marked', '2025-09-26 09:35:06'),
(22, 5, '2025-09-27', 900.00, 'upi', 'Advance attendance marked', '2025-09-27 17:25:15'),
(23, 7, '2025-09-27', 4000.00, 'upi', 'Initial advance payment', '2025-09-27 18:04:32'),
(24, 3, '2025-10-02', 300.00, 'upi', 'Daily attendance marked', '2025-10-02 15:33:04'),
(25, 8, '2025-10-02', 20000.00, 'upi', 'Initial advance payment', '2025-10-02 16:26:32'),
(26, 5, '2025-10-02', 900.00, 'upi', 'Advance attendance marked', '2025-10-02 18:00:30'),
(27, 9, '2025-10-04', 4000.00, 'upi', 'Initial advance payment', '2025-10-04 10:21:02'),
(28, 10, '2025-10-11', 3000.00, 'upi', 'Initial advance payment', '2025-10-11 18:07:57'),
(29, 11, '2025-10-13', 5000.00, 'upi', 'Initial advance payment', '2025-10-12 20:41:06'),
(30, 12, '2025-10-13', 2000.00, 'cash', 'Initial advance payment', '2025-10-12 20:44:24'),
(31, 13, '2025-10-13', 8000.00, 'upi', 'Initial advance payment', '2025-10-12 20:59:41'),
(32, 14, '2025-10-13', 444.00, 'upi', 'Initial advance payment', '2025-10-12 21:12:24'),
(33, 14, '2025-10-13', 16.00, 'cash', 'Daily attendance marked', '2025-10-12 21:13:21'),
(34, 15, '2025-10-13', 1000.00, 'upi', 'Initial advance payment', '2025-10-13 13:32:23'),
(35, 16, '2025-10-13', 10000.00, 'upi', 'Initial advance payment', '2025-10-13 13:47:32'),
(36, 20, '2025-10-14', 11000.00, 'upi', 'Initial advance payment', '2025-10-13 13:49:29'),
(37, 1, '2025-10-13', 1000.00, 'upi', 'Advance attendance marked', '2025-10-13 17:37:25'),
(38, 21, '2025-10-13', 5000.00, 'upi', 'Initial advance payment', '2025-10-13 18:16:18');

-- --------------------------------------------------------

--
-- Table structure for table `quick_inquiry`
--

CREATE TABLE `quick_inquiry` (
  `inquiry_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `inquiry_type` varchar(50) DEFAULT NULL COMMENT 'e.g., physio, speech_therapy',
  `communication_type` varchar(50) DEFAULT NULL COMMENT 'e.g., phone, web, email',
  `referralSource` enum('doctor_referral','web_search','social_media','returning_patient','local_event','advertisement','employee','family','self','other') DEFAULT 'self',
  `chief_complain` enum('neck_pain','back_pain','low_back_pain','radiating_pain','other') DEFAULT 'other',
  `phone_number` varchar(20) NOT NULL,
  `review` text DEFAULT NULL,
  `expected_visit_date` date DEFAULT NULL,
  `status` enum('visited','cancelled','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quick_inquiry`
--

INSERT INTO `quick_inquiry` (`inquiry_id`, `branch_id`, `name`, `age`, `gender`, `inquiry_type`, `communication_type`, `referralSource`, `chief_complain`, `phone_number`, `review`, `expected_visit_date`, `status`, `created_at`) VALUES
(1, 2, 'Sumit', 22, 'Male', NULL, NULL, 'doctor_referral', 'back_pain', '7739028861', 'Problem in back', '2025-09-04', 'visited', '2025-09-03 07:23:10'),
(2, 2, 'Tesss', 23, 'Female', NULL, NULL, 'web_search', 'back_pain', '7893937493', 'none', '2025-09-09', 'pending', '2025-09-09 04:59:58'),
(3, 2, 'simran', 33, 'Female', NULL, NULL, 'returning_patient', 'radiating_pain', '7374937493', 'none', '2025-09-09', 'pending', '2025-09-09 05:05:01'),
(4, 2, 'test', 11, 'Female', NULL, NULL, 'web_search', 'neck_pain', '7348374347', 'nnone', '2025-09-09', 'pending', '2025-09-09 05:14:27'),
(5, 2, 'heheheh', 31, 'Male', NULL, NULL, 'web_search', 'back_pain', '7834784384', 'nnn', '2025-09-09', 'pending', '2025-09-09 05:15:07'),
(6, 2, 'utuids', 19, 'Male', NULL, NULL, 'returning_patient', 'back_pain', '8394893439', 'nnnn', '2025-09-09', 'pending', '2025-09-09 05:17:06'),
(7, 2, 'sumit', 22, 'Male', NULL, NULL, 'social_media', 'back_pain', '7389493748', 'noen', '2025-09-09', 'pending', '2025-09-09 06:39:39'),
(8, 2, 'sumit', 22, 'Male', NULL, NULL, 'social_media', 'back_pain', '7389493748', 'none', '2025-09-09', 'pending', '2025-09-09 06:39:44'),
(9, 2, 'sumit', 22, 'Male', NULL, NULL, 'social_media', 'back_pain', '7389493748', 'none', '2025-09-09', 'pending', '2025-09-09 06:39:47'),
(10, 2, 'sumit', 22, 'Male', NULL, NULL, 'social_media', 'back_pain', '7389493748', 'none', '2025-09-09', 'cancelled', '2025-09-09 06:39:48'),
(11, 2, 'sumit', 22, 'Male', NULL, NULL, 'social_media', 'back_pain', '7389493748', 'none', '2025-09-09', 'pending', '2025-09-09 06:39:48'),
(12, 2, 'sumit', 22, 'Male', NULL, NULL, 'social_media', 'back_pain', '7389493748', 'none', '2025-09-09', 'pending', '2025-09-09 06:39:48'),
(13, 2, 'sumit', 22, 'Male', NULL, NULL, 'social_media', 'back_pain', '7389493748', 'none', '2025-09-09', 'pending', '2025-09-09 06:39:48'),
(14, 2, 'sumit', 12, 'Male', NULL, NULL, 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'pending', '2025-09-09 06:40:32'),
(15, 2, 'sumit', 12, 'Male', NULL, NULL, 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'pending', '2025-09-09 06:40:32'),
(16, 2, 'sumit', 12, 'Male', NULL, NULL, 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'pending', '2025-09-09 06:40:33'),
(17, 2, 'sumit', 12, 'Male', NULL, NULL, 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'cancelled', '2025-09-09 06:40:33'),
(18, 2, 'sumit', 12, 'Male', NULL, NULL, 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'pending', '2025-09-09 06:40:33'),
(19, 2, 'sumit', 12, 'Male', NULL, NULL, 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'visited', '2025-09-09 06:40:33'),
(20, 2, 'sumit', 12, 'Male', NULL, NULL, 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'visited', '2025-09-09 06:40:34'),
(21, 2, 'sumit', 12, 'Male', NULL, NULL, 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'visited', '2025-09-09 06:40:34'),
(22, 2, 'sumit', 22, 'Male', NULL, NULL, 'web_search', 'back_pain', '7838348374', 'none', '2025-09-09', 'pending', '2025-09-09 06:41:22'),
(23, 2, 'sumit', 22, 'Male', NULL, NULL, 'web_search', 'back_pain', '7838348374', 'none', '2025-09-09', 'visited', '2025-09-09 06:41:24'),
(24, 2, 'nnnn', 22, 'Male', NULL, NULL, 'social_media', 'back_pain', '7728273282', 'nown', '2025-09-09', 'visited', '2025-09-09 06:43:45'),
(25, 2, 'Chandan Shukla', 24, 'Male', NULL, NULL, 'social_media', 'low_back_pain', '8939394893', 'major pain', '2025-09-12', 'visited', '2025-09-12 10:48:48'),
(26, 2, 'Nikhil', 22, 'Male', NULL, NULL, 'advertisement', 'neck_pain', '7384737474', 'Severe pain', '2025-09-17', 'visited', '2025-09-16 16:00:44'),
(27, 2, 'Raj kumar', 20, 'Male', NULL, NULL, 'returning_patient', 'low_back_pain', '7384783783', 'kdsjfijfj', '2025-09-22', 'visited', '2025-09-19 19:37:11'),
(28, 2, 'Aditya', 22, 'Male', NULL, NULL, 'web_search', 'low_back_pain', '8993849384', 'inquiry check for logs', '2025-09-27', 'visited', '2025-09-27 09:38:44'),
(29, 2, 'Ram', 20, 'Male', NULL, NULL, 'social_media', 'low_back_pain', '8973743743', 'minor pain in back due to accident', '2025-10-12', 'pending', '2025-10-11 17:52:52'),
(30, 2, 'test', 22, 'Male', 'physio', 'phone', 'social_media', 'back_pain', '7868766767', '', '2025-10-12', 'pending', '2025-10-12 12:19:55');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `registration_id` int(11) NOT NULL,
  `master_patient_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `inquiry_id` int(11) DEFAULT NULL,
  `patient_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `age` int(11) NOT NULL,
  `chief_complain` enum('neck_pain','back_pain','low_back_pain','radiating_pain','other') DEFAULT 'other',
  `referralSource` enum('doctor_referral','web_search','social_media','returning_patient','local_event','advertisement','employee','family','self','other') DEFAULT 'self',
  `reffered_by` text DEFAULT NULL,
  `occupation` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `consultation_type` enum('in-clinic','home-visit','online','speech-therapy') DEFAULT 'in-clinic',
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `consultation_amount` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('cash','card','upi','upi-boi','upi-hdfc','cheque','other') DEFAULT 'cash',
  `remarks` text DEFAULT NULL,
  `doctor_notes` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `status` enum('Pending','Consulted','Closed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `patient_photo_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`registration_id`, `master_patient_id`, `branch_id`, `inquiry_id`, `patient_name`, `phone_number`, `email`, `gender`, `age`, `chief_complain`, `referralSource`, `reffered_by`, `occupation`, `address`, `consultation_type`, `appointment_date`, `appointment_time`, `consultation_amount`, `payment_method`, `remarks`, `doctor_notes`, `prescription`, `follow_up_date`, `status`, `created_at`, `updated_at`, `patient_photo_path`) VALUES
(2, NULL, 2, NULL, 'testing', '8397383937', '', 'Male', 22, 'back_pain', 'local_event', 'dr kk', 'dr', '', 'in-clinic', '2025-09-09', '12:30:00', 600.00, 'cash', '', NULL, NULL, NULL, 'Pending', '2025-09-09 06:58:52', '2025-09-13 06:30:22', NULL),
(3, NULL, 2, NULL, 'Testing', '8394938938', '', 'Male', 23, 'neck_pain', 'social_media', 'dr kp', 'student', '', 'in-clinic', '2025-09-10', '12:30:00', 600.00, 'cash', '', NULL, NULL, NULL, 'Consulted', '2025-09-10 06:37:52', '2025-10-13 13:49:29', NULL),
(4, NULL, 2, NULL, 'Saket', '7389634384', '', 'Male', 24, 'back_pain', 'social_media', 'dr kk', 'student', '', 'in-clinic', '2025-09-11', '17:00:00', 600.00, 'cash', 'hello', NULL, NULL, NULL, 'Closed', '2025-09-11 10:58:34', '2025-09-13 06:28:13', NULL),
(5, NULL, 2, 24, 'nnnn', '7728273282', '', 'Male', 22, 'back_pain', 'social_media', 'dr mm jha', 'student', '', 'in-clinic', '2025-09-12', '15:50:00', 600.00, 'cash', 'nown', NULL, NULL, NULL, 'Consulted', '2025-09-12 09:34:06', '2025-09-13 06:24:02', NULL),
(6, NULL, 2, NULL, 'sumit', '7838348374', '', 'Male', 22, 'back_pain', 'web_search', 'dd', 'ss', '', 'in-clinic', '2025-09-12', '15:30:00', 600.00, 'upi', 'none', NULL, NULL, NULL, 'Pending', '2025-09-12 09:36:41', '2025-09-12 09:36:41', NULL),
(7, NULL, 2, 23, 'sumit', '7838348374', '', 'Male', 22, 'back_pain', 'web_search', 'dr kk', 'stuedent', '', 'in-clinic', '2025-09-12', '15:40:00', 600.00, 'cash', 'none', NULL, NULL, NULL, 'Consulted', '2025-09-12 09:52:54', '2025-09-13 05:54:37', NULL),
(8, NULL, 2, NULL, 'Chandan', '8839783989', '', 'Male', 23, 'back_pain', 'returning_patient', 'Dr prnav', 'Student', '', 'in-clinic', '2025-09-12', '16:30:00', 600.00, 'upi', 'minor pain', NULL, NULL, NULL, 'Consulted', '2025-09-12 10:47:51', '2025-09-13 06:26:56', NULL),
(9, NULL, 2, 25, 'Chandan Shukla', '8939394893', '', 'Male', 24, 'low_back_pain', 'social_media', 'Dr Pranav', 'student', '', 'in-clinic', '2025-09-12', '17:10:00', 600.00, 'cash', 'hii', NULL, NULL, NULL, 'Consulted', '2025-09-12 10:51:53', '2025-09-13 07:44:29', NULL),
(10, NULL, 2, NULL, 'Testing', '8973843843', '', 'Male', 22, 'back_pain', 'social_media', 'DR TT', 'Student', '', 'in-clinic', '2025-09-14', '16:30:00', 600.00, 'card', 'checking time slot', NULL, NULL, NULL, 'Consulted', '2025-09-14 10:40:30', '2025-09-25 11:16:36', NULL),
(11, NULL, 2, NULL, 'Time slot test', '7873437883', '', 'Male', 12, 'radiating_pain', 'family', 'dd', 'sjdfjdk', '', 'in-clinic', '2025-09-14', '17:00:00', 600.00, 'card', '', NULL, NULL, NULL, 'Pending', '2025-09-14 10:58:15', '2025-09-14 10:58:15', NULL),
(12, NULL, 2, NULL, 'Nikhil', '7834939437', '', 'Male', 22, 'neck_pain', 'advertisement', 'DR KK Menon', 'student', '', 'in-clinic', '2025-09-17', '11:30:00', 600.00, 'upi', 'severe neck pain', NULL, NULL, NULL, 'Closed', '2025-09-16 16:00:10', '2025-09-19 13:04:18', NULL),
(13, NULL, 2, NULL, 'Sumit', '7739923493', '', 'Male', 24, 'back_pain', 'social_media', 'Dr KK', 'Student', '', 'in-clinic', '2025-09-20', '13:00:00', 600.00, 'upi', '', NULL, NULL, NULL, 'Consulted', '2025-09-19 19:19:31', '2025-09-19 19:41:22', NULL),
(14, NULL, 2, NULL, 'Raahul', '8943248923', 'rahul@gmail.com', 'Male', 22, 'neck_pain', 'local_event', 'dr mmb', 'student', 'noida', 'speech-therapy', '2025-09-25', '17:30:00', 600.00, 'upi', 'normal', NULL, NULL, NULL, 'Consulted', '2025-09-25 11:34:38', '2025-09-25 11:49:04', NULL),
(15, NULL, 2, NULL, 'Adi', '9773737439', 'adi@gmail.com', 'Male', 20, 'neck_pain', 'returning_patient', 'dr ss', 'student', 'noida', 'speech-therapy', '2025-09-25', '18:00:00', 600.00, 'upi', 'none', NULL, NULL, NULL, 'Consulted', '2025-09-25 11:44:26', '2025-09-26 11:04:18', NULL),
(16, NULL, 2, NULL, 'Aditya Singh', '8939493849', 'aditya@gmail.com', 'Male', 22, 'radiating_pain', 'returning_patient', 'Dr Sumit', 'student', 'noida', 'in-clinic', '2025-09-27', '15:30:00', 600.00, 'upi', 'pain in the back', NULL, NULL, NULL, 'Consulted', '2025-09-27 09:54:00', '2025-09-27 13:18:03', NULL),
(17, NULL, 2, NULL, 'Mahi', '8938938439', 'mahi@gmail.com', 'Female', 21, 'low_back_pain', 'doctor_referral', 'Dr Sumit', 'teacher', 'noida', 'in-clinic', '2025-09-27', '16:00:00', 600.00, 'upi', 'pain in the lower back', NULL, NULL, NULL, 'Consulted', '2025-09-27 09:58:47', '2025-09-27 11:43:59', NULL),
(18, NULL, 2, NULL, 'Ravi', '7899839489', '', 'Male', 20, 'radiating_pain', 'local_event', 'Dr Sumit', 'Student', '', 'in-clinic', '2025-09-29', '12:00:00', 600.00, 'cash', 'none', NULL, NULL, NULL, 'Consulted', '2025-09-29 15:41:50', '2025-10-01 16:42:41', NULL),
(19, 1, 2, NULL, 'Bhumi', '8938934893', 'bhumi@gmail.com', 'Female', 22, 'radiating_pain', 'employee', 'Dr Sumit', 'student', '', 'in-clinic', '2025-10-01', '12:30:00', 600.00, 'cash', 'pain due to sitting too long', NULL, NULL, NULL, 'Consulted', '2025-10-01 06:35:10', '2025-10-01 09:51:54', NULL),
(20, NULL, 2, 28, 'Aditya', '8993849384', '', 'Male', 22, 'low_back_pain', 'web_search', 'Dr Sumit', 'student', '', 'in-clinic', '2025-10-02', '17:00:00', 600.00, 'cash', 'inquiry check for logs', NULL, NULL, NULL, 'Consulted', '2025-10-02 11:26:58', '2025-10-02 17:52:46', NULL),
(21, 2, 2, 27, 'Raj kumar', '7384783783', NULL, 'Male', 20, 'low_back_pain', 'returning_patient', 'Dr Pranav', 'Cook', NULL, 'in-clinic', '2025-10-02', '17:30:00', 600.00, 'upi', 'kdsjfijfj', NULL, NULL, NULL, 'Consulted', '2025-10-02 11:31:01', '2025-10-10 09:39:18', NULL),
(22, 3, 2, NULL, 'Priyanshu SIngh', '8993849849', 'priyanshu@gmail.com', 'Male', 22, 'back_pain', 'social_media', 'Dr Pranav', 'Student', 'Bihar', 'in-clinic', '2025-10-04', '11:00:00', 600.00, 'cash', 'normal pain', NULL, NULL, NULL, 'Pending', '2025-10-03 22:12:46', '2025-10-10 09:39:21', NULL),
(23, 4, 2, NULL, 'Test', '8394394839', '', 'Male', 22, 'back_pain', 'social_media', 'Dr Sumit', 'student', 'bhagalpur', 'in-clinic', '2025-10-10', '14:00:00', 600.00, 'upi-boi', 'none', NULL, NULL, NULL, 'Consulted', '2025-10-10 08:23:45', '2025-10-10 10:48:02', NULL),
(24, 5, 2, NULL, 'Raj', '8394394399', 'raj@gmail.com', 'Male', 22, 'other', 'doctor_referral', 'Dr Pranav', 'Student', 'bhagalpur', 'in-clinic', '2025-10-12', '09:00:00', 600.00, 'upi-boi', '', NULL, NULL, NULL, 'Consulted', '2025-10-11 18:01:51', '2025-10-11 18:05:14', 'uploads/patient_photos/reg_24_1760205839.jpeg'),
(25, 6, 2, NULL, 'speech test', '8998948398', '', 'Male', 22, 'other', 'doctor_referral', 'dr kk', '', '', 'speech-therapy', '2025-10-13', '09:00:00', 600.00, 'upi-boi', '', NULL, NULL, NULL, 'Consulted', '2025-10-12 20:58:49', '2025-10-12 20:59:01', NULL),
(26, 7, 2, NULL, 'frnglnk', '4444444444', '', 'Male', 54, 'other', 'self', 'dr mmb', 'ffgmgbkmdd', '', 'speech-therapy', '2025-10-13', '12:00:00', 600.00, 'upi-hdfc', '', NULL, NULL, NULL, 'Consulted', '2025-10-12 21:10:44', '2025-10-12 21:11:17', NULL),
(27, 8, 2, NULL, 'Aditya kumar singh', '8303057557', '', 'Male', 22, 'low_back_pain', 'employee', 'DR KK Menon', '', 'Sanjay Nagar Colony', 'in-clinic', '2025-10-14', '12:30:00', 600.00, 'upi-hdfc', '', NULL, NULL, NULL, 'Consulted', '2025-10-13 18:14:38', '2025-10-13 18:14:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

CREATE TABLE `tests` (
  `test_id` int(11) NOT NULL,
  `test_uid` varchar(20) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `inquiry_id` int(11) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `assigned_test_date` date NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `parents` text DEFAULT NULL,
  `relation` text DEFAULT NULL,
  `alternate_phone_no` varchar(20) DEFAULT NULL,
  `limb` enum('upper_limb','lower_limb','both','none') DEFAULT 'none',
  `test_name` enum('eeg','ncv','emg','rns','bera','vep','other') NOT NULL,
  `referred_by` varchar(100) DEFAULT NULL,
  `test_done_by` varchar(100) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `advance_amount` decimal(10,2) DEFAULT 0.00,
  `due_amount` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('cash','upi','upi-boi','upi-hdfc','card','cheque','other') DEFAULT 'cash',
  `payment_status` enum('pending','partial','paid') DEFAULT 'pending',
  `refund_status` enum('no','initiated','completed') NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `test_status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tests`
--

INSERT INTO `tests` (`test_id`, `test_uid`, `branch_id`, `patient_id`, `inquiry_id`, `visit_date`, `assigned_test_date`, `patient_name`, `phone_number`, `gender`, `age`, `dob`, `parents`, `relation`, `alternate_phone_no`, `limb`, `test_name`, `referred_by`, `test_done_by`, `total_amount`, `advance_amount`, `due_amount`, `discount`, `payment_method`, `payment_status`, `refund_status`, `created_at`, `updated_at`, `test_status`) VALUES
(1, NULL, 2, NULL, NULL, '2025-09-05', '2025-09-06', 'raj', '2342434343', 'Male', 22, NULL, NULL, NULL, NULL, 'upper_limb', 'ncv', 'dr kk', 'achal', 2000.00, 1500.00, 500.00, NULL, 'cash', 'partial', 'no', '2025-09-05 08:52:39', '2025-09-05 08:52:39', 'pending'),
(2, NULL, 2, NULL, NULL, '2025-09-05', '2025-09-06', 'Sumit', '7738297389', 'Male', 22, NULL, NULL, NULL, NULL, NULL, 'eeg', 'DR KK MENON', 'pancham', 2000.00, 1500.00, 500.00, NULL, 'cash', 'partial', 'no', '2025-09-05 11:57:57', '2025-09-26 08:27:55', 'pending'),
(3, NULL, 2, NULL, 1, '2025-09-05', '2025-09-05', 'Sumit', '7738297389', 'Male', 22, NULL, NULL, NULL, NULL, NULL, 'eeg', 'DR KK MENON', 'sayan', 2000.00, 1500.00, 500.00, NULL, 'cash', 'partial', 'no', '2025-09-05 12:18:51', '2025-09-05 12:18:51', 'pending'),
(4, NULL, 2, NULL, NULL, '2025-09-09', '2025-09-09', 'rest', '7387348734', 'Male', 23, NULL, NULL, NULL, NULL, 'upper_limb', 'eeg', 'dr kk', 'ashish', 2000.00, 2000.00, 0.00, NULL, 'cash', 'paid', 'no', '2025-09-09 04:40:17', '2025-09-26 09:33:34', 'completed'),
(5, NULL, 2, NULL, NULL, '2025-09-09', '2025-09-09', 'tehsifh', '7383747239', 'Male', 24, NULL, NULL, NULL, NULL, NULL, 'emg', 'dd', 'achal', 2000.00, 2000.00, 0.00, NULL, 'cash', 'paid', 'no', '2025-09-09 04:44:22', '2025-09-26 07:50:48', 'completed'),
(6, NULL, 2, NULL, NULL, '2025-09-09', '2025-09-09', 'hdifhdi', '7934938338', 'Female', 11, NULL, NULL, NULL, NULL, NULL, 'eeg', 'dd', 'ashish', 2000.00, 2000.00, 0.00, NULL, 'cash', 'paid', 'no', '2025-09-09 05:17:53', '2025-09-26 07:48:50', 'completed'),
(7, NULL, 2, NULL, 4, '2025-09-12', '2025-09-12', 'Sumit', '7834734737', 'Male', 22, NULL, NULL, NULL, NULL, NULL, 'emg', 'Dr mm', 'ashish', 2000.00, 2000.00, 0.00, 0.00, 'cash', 'paid', 'no', '2025-09-12 10:21:34', '2025-09-26 07:48:22', 'completed'),
(8, NULL, 2, NULL, 5, '2025-09-12', '2025-09-12', 'Chandan', '7938938498', 'Male', 24, '2004-09-15', 'Sumit', 'Friend', '7783783748', 'both', 'vep', 'DR Pranav', 'pancham', 3000.00, 2500.00, 0.00, 500.00, 'upi', 'paid', 'no', '2025-09-12 10:51:03', '2025-09-26 07:59:08', 'completed'),
(9, NULL, 2, NULL, NULL, '2025-09-27', '2025-09-28', 'Mahi', '7889374893', 'Female', 22, NULL, NULL, NULL, NULL, 'lower_limb', 'vep', 'Dr Sumit', 'pancham', 2000.00, 2000.00, 0.00, NULL, 'upi', 'paid', 'no', '2025-09-27 10:04:51', '2025-09-27 11:45:44', 'pending'),
(10, NULL, 2, NULL, 6, '2025-10-02', '2025-10-03', 'Aditya', '7889374893', 'Male', 22, NULL, NULL, NULL, NULL, 'both', 'bera', 'Dr Sumit', 'sayan', 2000.00, 2000.00, 0.00, 0.00, 'upi', 'paid', 'no', '2025-10-02 11:33:07', '2025-10-14 13:55:02', 'pending'),
(11, '25101101', 2, NULL, NULL, '2025-10-11', '2025-10-11', 'Sumit Sri', '8934839439', 'Male', 24, NULL, NULL, NULL, NULL, 'upper_limb', 'rns', 'Dr Sumit', 'pancham', 2000.00, 1600.00, 0.00, NULL, 'upi-boi', 'paid', 'initiated', '2025-10-11 10:44:31', '2025-10-14 13:55:44', 'cancelled'),
(12, '25101401', 2, 21, NULL, '2025-10-14', '2025-10-14', 'Aditya kumar singh', '8303057557', 'Male', 22, NULL, '', '', '', NULL, 'eeg', 'Dr Sumit', 'achal', 2000.00, 1800.00, 0.00, 200.00, 'cash', 'paid', 'initiated', '2025-10-13 20:41:12', '2025-10-14 14:05:42', 'cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `tests_lists`
--

CREATE TABLE `tests_lists` (
  `test_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `test_name` varchar(100) NOT NULL,
  `default_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `test_inquiry`
--

CREATE TABLE `test_inquiry` (
  `inquiry_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `testname` varchar(100) NOT NULL,
  `reffered_by` varchar(100) DEFAULT NULL,
  `mobile_number` varchar(20) NOT NULL,
  `expected_visit_date` date DEFAULT NULL,
  `status` enum('visited','cancelled','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_inquiry`
--

INSERT INTO `test_inquiry` (`inquiry_id`, `branch_id`, `name`, `testname`, `reffered_by`, `mobile_number`, `expected_visit_date`, `status`, `created_at`) VALUES
(1, 2, 'Sumit', 'EEG', 'DR KK MENON', '7738297389', '2025-09-05', 'cancelled', '2025-09-04 08:05:18'),
(2, 2, 'test', 'ecg', 'difj', '8394839493', '2025-09-09', 'visited', '2025-09-09 06:45:18'),
(3, 2, 'testting', 'ncv', 'ddd', '7394398439', '2025-09-09', 'visited', '2025-09-09 06:46:21'),
(4, 2, 'Sumit', 'emg', 'Dr mm', '7834734737', '2025-09-10', 'pending', '2025-09-10 17:30:09'),
(5, 2, 'Chandan', 'vep', 'DR Pranav', '7938938498', '2025-09-12', 'visited', '2025-09-12 10:49:07'),
(6, 2, 'Aditya', 'bera', 'Dr Sumit', '7889374893', '2025-09-28', 'visited', '2025-09-27 09:42:55'),
(7, 2, 'Test', 'ncv', 'DR KK Menon', '7394394838', '2025-10-10', 'pending', '2025-10-10 08:25:38'),
(8, 2, 'Lakshman', 'emg', 'Dr Pranav', '8938394839', '2025-10-12', 'pending', '2025-10-11 17:55:11');

-- --------------------------------------------------------

--
-- Table structure for table `test_items`
--

CREATE TABLE `test_items` (
  `item_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL COMMENT '\r\n',
  `assigned_test_date` date NOT NULL,
  `test_name` enum('eeg','ncv','emg','rns','bera','vep','other') NOT NULL,
  `limb` enum('upper_limb','lower_limb','both','none') DEFAULT NULL,
  `referred_by` varchar(100) DEFAULT NULL,
  `test_done_by` varchar(100) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `advance_amount` decimal(10,2) DEFAULT 0.00,
  `due_amount` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('cash','upi','upi-boi','upi-hdfc','card','cheque','other') DEFAULT 'cash',
  `test_status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','partial','paid') NOT NULL DEFAULT 'pending',
  `refund_status` enum('no','initiated','completed') NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_items`
--

INSERT INTO `test_items` (`item_id`, `test_id`, `assigned_test_date`, `test_name`, `limb`, `referred_by`, `test_done_by`, `total_amount`, `advance_amount`, `due_amount`, `discount`, `payment_method`, `test_status`, `payment_status`, `refund_status`, `created_at`) VALUES
(1, 12, '2025-10-14', 'ncv', 'upper_limb', NULL, 'sayan', 1900.00, 1800.00, 0.00, 100.00, 'cash', 'cancelled', 'paid', 'initiated', '2025-10-13 21:29:58');

-- --------------------------------------------------------

--
-- Table structure for table `tokens`
--

CREATE TABLE `tokens` (
  `token_id` bigint(20) UNSIGNED NOT NULL,
  `token_uid` varchar(20) NOT NULL COMMENT 'Human-readable token ID, e.g., T251010-01',
  `branch_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `technician_id` int(11) DEFAULT NULL COMMENT 'FK to users table for the main technician',
  `assistant_id` int(11) DEFAULT NULL COMMENT 'FK to users table for the assistant',
  `service_type` varchar(50) NOT NULL COMMENT 'e.g., physio, speech_therapy, occupational_therapy',
  `token_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `role` enum('superadmin','admin','doctor','jrdoctor','reception') NOT NULL DEFAULT 'reception',
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `branch_id`, `role`, `username`, `password_hash`, `email`, `is_active`, `created_at`, `reset_token`, `reset_expiry`) VALUES
(1, 2, 'reception', 'sumit',  'srisumit96@gmail.com', 1, '2025-08-15 16:13:39', 'b8034119933b8d5ffeeaacf936adca75', '2025-08-15 19:40:26'),
(2, 2, 'reception', 'admin',  'srisumit4@gmail.com', 1, '2025-09-27 17:00:14', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_appointments_branch` (`branch_id`);

--
-- Indexes for table `appointment_requests`
--
ALTER TABLE `appointment_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_appt_requests_branch` (`branch_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_branch_id` (`branch_id`),
  ADD KEY `idx_target` (`target_table`,`target_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`);

--
-- Indexes for table `branch_budgets`
--
ALTER TABLE `branch_budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branch_id` (`branch_id`,`effective_from_date`),
  ADD KEY `created_by_user_id` (`created_by_user_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_sender_receiver` (`sender_id`,`receiver_id`),
  ADD KEY `idx_receiver_sender` (`receiver_id`,`sender_id`);

--
-- Indexes for table `daily_patient_counter`
--
ALTER TABLE `daily_patient_counter`
  ADD PRIMARY KEY (`entry_date`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD UNIQUE KEY `uk_branch_voucher` (`branch_id`,`voucher_no`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_authorized_by_user_id` (`authorized_by_user_id`),
  ADD KEY `approved_by_user_id` (`approved_by_user_id`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`),
  ADD KEY `ip` (`ip`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `fk_notification_branch` (`branch_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `registration_id` (`registration_id`),
  ADD KEY `idx_master_patient_id` (`master_patient_id`),
  ADD KEY `fk_discount_approver` (`discount_approved_by`);

--
-- Indexes for table `patients_treatment`
--
ALTER TABLE `patients_treatment`
  ADD PRIMARY KEY (`treatment_id`),
  ADD KEY `fk_patient_treatment` (`patient_id`);

--
-- Indexes for table `patient_appointments`
--
ALTER TABLE `patient_appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_branch_id_date_service` (`branch_id`,`appointment_date`,`service_type`);

--
-- Indexes for table `patient_master`
--
ALTER TABLE `patient_master`
  ADD PRIMARY KEY (`master_patient_id`),
  ADD UNIQUE KEY `patient_uid` (`patient_uid`),
  ADD KEY `idx_phone_number` (`phone_number`),
  ADD KEY `first_registered_branch_id` (`first_registered_branch_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `quick_inquiry`
--
ALTER TABLE `quick_inquiry`
  ADD PRIMARY KEY (`inquiry_id`),
  ADD KEY `fk_quick_inquiry_branch` (`branch_id`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`registration_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `fk_registration_inquiry` (`inquiry_id`),
  ADD KEY `idx_master_patient_id` (`master_patient_id`);

--
-- Indexes for table `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`test_id`),
  ADD UNIQUE KEY `test_uid_unique` (`test_uid`),
  ADD KEY `fk_tests_inquiry` (`inquiry_id`);

--
-- Indexes for table `tests_lists`
--
ALTER TABLE `tests_lists`
  ADD PRIMARY KEY (`test_id`),
  ADD KEY `fk_tests_lists_branch` (`branch_id`);

--
-- Indexes for table `test_inquiry`
--
ALTER TABLE `test_inquiry`
  ADD PRIMARY KEY (`inquiry_id`),
  ADD KEY `fk_test_inquiry_branch` (`branch_id`);

--
-- Indexes for table `test_items`
--
ALTER TABLE `test_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `fk_test_items_test_id` (`test_id`);

--
-- Indexes for table `tokens`
--
ALTER TABLE `tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `uq_token_uid` (`token_uid`),
  ADD KEY `idx_branch_id` (`branch_id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_technician_id` (`technician_id`),
  ADD KEY `idx_assistant_id` (`assistant_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `fk_users_branch` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `appointment_requests`
--
ALTER TABLE `appointment_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `branch_budgets`
--
ALTER TABLE `branch_budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `patients_treatment`
--
ALTER TABLE `patients_treatment`
  MODIFY `treatment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_appointments`
--
ALTER TABLE `patient_appointments`
  MODIFY `appointment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `patient_master`
--
ALTER TABLE `patient_master`
  MODIFY `master_patient_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `quick_inquiry`
--
ALTER TABLE `quick_inquiry`
  MODIFY `inquiry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `tests`
--
ALTER TABLE `tests`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tests_lists`
--
ALTER TABLE `tests_lists`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_inquiry`
--
ALTER TABLE `test_inquiry`
  MODIFY `inquiry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `test_items`
--
ALTER TABLE `test_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `token_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE;

--
-- Constraints for table `appointment_requests`
--
ALTER TABLE `appointment_requests`
  ADD CONSTRAINT `fk_appt_requests_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`);

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `fk_log_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `branch_budgets`
--
ALTER TABLE `branch_budgets`
  ADD CONSTRAINT `branch_budgets_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `branch_budgets_ibfk_2` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chat_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chat_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expense_authorized_by` FOREIGN KEY (`authorized_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expense_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`),
  ADD CONSTRAINT `fk_expense_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_discount_approver` FOREIGN KEY (`discount_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_patients_master_patient` FOREIGN KEY (`master_patient_id`) REFERENCES `patient_master` (`master_patient_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`),
  ADD CONSTRAINT `patients_ibfk_2` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`registration_id`);

--
-- Constraints for table `patients_treatment`
--
ALTER TABLE `patients_treatment`
  ADD CONSTRAINT `fk_patient_treatment` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patient_appointments`
--
ALTER TABLE `patient_appointments`
  ADD CONSTRAINT `fk_patient_appointments_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_patient_appointments_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_master`
--
ALTER TABLE `patient_master`
  ADD CONSTRAINT `patient_master_ibfk_1` FOREIGN KEY (`first_registered_branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);

--
-- Constraints for table `quick_inquiry`
--
ALTER TABLE `quick_inquiry`
  ADD CONSTRAINT `fk_quick_inquiry_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `registration`
--
ALTER TABLE `registration`
  ADD CONSTRAINT `fk_registration_inquiry` FOREIGN KEY (`inquiry_id`) REFERENCES `quick_inquiry` (`inquiry_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_registration_master_patient` FOREIGN KEY (`master_patient_id`) REFERENCES `patient_master` (`master_patient_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `registration_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);

--
-- Constraints for table `tests`
--
ALTER TABLE `tests`
  ADD CONSTRAINT `fk_tests_inquiry` FOREIGN KEY (`inquiry_id`) REFERENCES `test_inquiry` (`inquiry_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tests_lists`
--
ALTER TABLE `tests_lists`
  ADD CONSTRAINT `fk_tests_lists_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `test_inquiry`
--
ALTER TABLE `test_inquiry`
  ADD CONSTRAINT `fk_test_inquiry_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `test_items`
--
ALTER TABLE `test_items`
  ADD CONSTRAINT `test_items_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`test_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tokens`
--
ALTER TABLE `tokens`
  ADD CONSTRAINT `fk_token_assistant` FOREIGN KEY (`assistant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_token_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_token_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_token_technician` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
