-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 10, 2025 at 09:11 AM
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
(56, 9, '2025-10-07', 'Auto: Used advance payment', NULL, '2025-10-06 19:35:27');

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
(42, '2025-10-09 18:03:03', 2, 'admin', 2, 'UPDATE', 'patients', 6, '{\"patient_photo_path\":\"old\"}', '{\"patient_photo_path\":\"uploads\\/patient_photos\\/patient_6_1760032983.jpg\"}', '127.0.0.1');

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
('2025-10-04', 1);

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

INSERT INTO `expenses` (`expense_id`, `branch_id`, `user_id`, `voucher_no`, `expense_date`, `paid_to`, `description`, `amount`, `amount_in_words`, `status`, `approved_by_user_id`, `approved_at`, `authorized_by_user_id`, `created_at`, `updated_at`, `payment_method`, `bill_image_path`) VALUES
(6, 2, 2, '101', '2025-09-30', 'Laundry', 'Blankets gone to laundry for cleaning', 500.00, 'Rupees five hundred Only', 'approved', NULL, NULL, NULL, '2025-09-30 19:55:27', '2025-09-30 19:59:00', 'cash', 'uploads/expenses/expense_6_1759262340.jpeg'),
(7, 2, 2, '102', '2025-09-30', 'gghfyf', 'tryrytryut', 200.00, 'Rupees one thousand two hundred Only', 'approved', 1, NULL, NULL, '2025-09-30 20:10:03', '2025-09-30 20:20:25', 'cash', 'uploads/expenses/expense_7_1759263046.jpeg'),
(8, 2, 2, '103', '2025-10-01', 'Laundary', 'Laudary for blanket cleaning', 500.00, 'Rupees five hundred Only', 'approved', 1, NULL, NULL, '2025-10-01 19:41:14', '2025-10-01 19:42:26', 'cash', 'uploads/expenses/expense_8_1759347746.png'),
(10, 2, 2, '104', '2025-10-01', 'stationary', 'dfkjdfjdskjf', 1500.00, 'Rupees one thousand five hundred Only', 'approved', 1, NULL, NULL, '2025-10-01 19:47:00', '2025-10-06 19:38:34', 'upi', 'uploads/expenses/expense_10_1759779514.jpeg');

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
  `treatment_type` enum('daily','advance','package') NOT NULL,
  `treatment_cost_per_day` decimal(10,2) DEFAULT NULL,
  `package_cost` decimal(10,2) DEFAULT NULL,
  `treatment_days` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','upi','cheque','other') NOT NULL DEFAULT 'cash',
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

INSERT INTO `patients` (`patient_id`, `master_patient_id`, `branch_id`, `registration_id`, `assigned_doctor`, `treatment_type`, `treatment_cost_per_day`, `package_cost`, `treatment_days`, `total_amount`, `payment_method`, `advance_payment`, `discount_percentage`, `discount_approved_by`, `due_amount`, `start_date`, `end_date`, `status`, `patient_photo_path`, `created_at`, `updated_at`, `remarks`) VALUES
(1, NULL, 2, 5, 'Not Assigned', 'advance', 1000.00, NULL, 5, 5000.00, 'cash', 5000.00, 0.00, NULL, 0.00, '2025-08-27', '2025-08-31', 'active', NULL, '2025-08-27 16:34:05', '2025-09-13 08:18:49', NULL),
(2, NULL, 2, 9, 'Not Assigned', 'package', NULL, 27000.00, 21, 27000.00, 'upi', 18000.00, 10.00, NULL, 9000.00, '2025-09-13', '2025-10-03', 'active', 'uploads/patient_photos/patient_2_1759945604.jpg', '2025-09-13 06:20:04', '2025-10-08 17:46:44', NULL),
(3, NULL, 2, 8, 'Not Assigned', 'daily', 600.00, NULL, 5, 3000.00, 'cash', 3000.00, 0.00, NULL, 0.00, '2025-09-13', '2025-09-17', 'active', NULL, '2025-09-13 10:27:49', '2025-10-02 15:33:04', NULL),
(4, NULL, 2, 7, 'Not Assigned', 'advance', 900.00, NULL, 10, 9000.00, 'upi', 2700.00, 10.00, NULL, 6300.00, '2025-09-13', '2025-09-22', 'active', NULL, '2025-09-13 11:00:58', '2025-09-20 17:24:26', NULL),
(5, NULL, 2, 13, 'Not Assigned', 'advance', 900.00, NULL, 10, 9000.00, 'upi', 5400.00, 10.00, NULL, 3600.00, '2025-09-21', '2025-09-30', 'active', NULL, '2025-09-19 19:29:38', '2025-10-02 18:00:30', NULL),
(6, NULL, 2, 10, 'Not Assigned', 'daily', 540.00, NULL, 10, 5400.00, 'upi', 5000.00, 10.00, NULL, 400.00, '2025-09-25', '2025-10-04', 'active', 'uploads/patient_photos/patient_6_1760032983.jpg', '2025-09-25 11:16:25', '2025-10-09 18:03:03', NULL),
(7, NULL, 2, 17, 'Not Assigned', 'daily', 540.00, NULL, 10, 5400.00, 'upi', 4000.00, 10.00, NULL, 1400.00, '2025-09-29', '2025-10-08', 'active', NULL, '2025-09-27 18:04:32', '2025-09-27 18:04:32', NULL),
(8, NULL, 2, 21, 'Not Assigned', 'package', NULL, 27000.00, 21, 27000.00, 'upi', 20000.00, 10.00, NULL, 7000.00, '2025-10-03', '2025-10-23', 'active', NULL, '2025-10-02 16:26:32', '2025-10-04 09:59:31', NULL),
(9, NULL, 2, 22, 'Not Assigned', 'advance', 850.00, NULL, 10, 8500.00, 'upi', 4000.00, 15.00, 2, 4500.00, '2025-10-04', '2025-10-13', 'active', 'uploads/patient_photos/patient_9_1759945487.jpg', '2025-10-04 10:21:02', '2025-10-08 17:44:47', NULL);

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
(3, '2510041', 'Priyanshu SIngh', '8993849849', 'Male', 22, '2025-10-03 22:12:46', 2);

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
(27, 9, '2025-10-04', 4000.00, 'upi', 'Initial advance payment', '2025-10-04 10:21:02');

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

INSERT INTO `quick_inquiry` (`inquiry_id`, `branch_id`, `name`, `age`, `gender`, `referralSource`, `chief_complain`, `phone_number`, `review`, `expected_visit_date`, `status`, `created_at`) VALUES
(1, 2, 'Sumit', 22, 'Male', 'doctor_referral', 'back_pain', '7739028861', 'Problem in back', '2025-09-04', 'visited', '2025-09-03 07:23:10'),
(2, 2, 'Tesss', 23, 'Female', 'web_search', 'back_pain', '7893937493', 'none', '2025-09-09', 'pending', '2025-09-09 04:59:58'),
(3, 2, 'simran', 33, 'Female', 'returning_patient', 'radiating_pain', '7374937493', 'none', '2025-09-09', 'pending', '2025-09-09 05:05:01'),
(4, 2, 'test', 11, 'Female', 'web_search', 'neck_pain', '7348374347', 'nnone', '2025-09-09', 'pending', '2025-09-09 05:14:27'),
(5, 2, 'heheheh', 31, 'Male', 'web_search', 'back_pain', '7834784384', 'nnn', '2025-09-09', 'pending', '2025-09-09 05:15:07'),
(6, 2, 'utuids', 19, 'Male', 'returning_patient', 'back_pain', '8394893439', 'nnnn', '2025-09-09', 'pending', '2025-09-09 05:17:06'),
(7, 2, 'sumit', 22, 'Male', 'social_media', 'back_pain', '7389493748', 'noen', '2025-09-09', 'pending', '2025-09-09 06:39:39'),
(8, 2, 'sumit', 22, 'Male', 'social_media', 'back_pain', '7389493748', 'none', '2025-09-09', 'pending', '2025-09-09 06:39:44'),
(9, 2, 'sumit', 22, 'Male', 'social_media', 'back_pain', '7389493748', 'none', '2025-09-09', 'pending', '2025-09-09 06:39:47'),
(10, 2, 'sumit', 22, 'Male', 'social_media', 'back_pain', '7389493748', 'none', '2025-09-09', 'cancelled', '2025-09-09 06:39:48'),
(11, 2, 'sumit', 22, 'Male', 'social_media', 'back_pain', '7389493748', 'none', '2025-09-09', 'pending', '2025-09-09 06:39:48'),
(12, 2, 'sumit', 22, 'Male', 'social_media', 'back_pain', '7389493748', 'none', '2025-09-09', 'pending', '2025-09-09 06:39:48'),
(13, 2, 'sumit', 22, 'Male', 'social_media', 'back_pain', '7389493748', 'none', '2025-09-09', 'pending', '2025-09-09 06:39:48'),
(14, 2, 'sumit', 12, 'Male', 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'pending', '2025-09-09 06:40:32'),
(15, 2, 'sumit', 12, 'Male', 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'pending', '2025-09-09 06:40:32'),
(16, 2, 'sumit', 12, 'Male', 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'pending', '2025-09-09 06:40:33'),
(17, 2, 'sumit', 12, 'Male', 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'cancelled', '2025-09-09 06:40:33'),
(18, 2, 'sumit', 12, 'Male', 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'pending', '2025-09-09 06:40:33'),
(19, 2, 'sumit', 12, 'Male', 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'visited', '2025-09-09 06:40:33'),
(20, 2, 'sumit', 12, 'Male', 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'visited', '2025-09-09 06:40:34'),
(21, 2, 'sumit', 12, 'Male', 'social_media', 'back_pain', '7394973949', 'none', '2025-09-09', 'visited', '2025-09-09 06:40:34'),
(22, 2, 'sumit', 22, 'Male', 'web_search', 'back_pain', '7838348374', 'none', '2025-09-09', 'pending', '2025-09-09 06:41:22'),
(23, 2, 'sumit', 22, 'Male', 'web_search', 'back_pain', '7838348374', 'none', '2025-09-09', 'visited', '2025-09-09 06:41:24'),
(24, 2, 'nnnn', 22, 'Male', 'social_media', 'back_pain', '7728273282', 'nown', '2025-09-09', 'visited', '2025-09-09 06:43:45'),
(25, 2, 'Chandan Shukla', 24, 'Male', 'social_media', 'low_back_pain', '8939394893', 'major pain', '2025-09-12', 'visited', '2025-09-12 10:48:48'),
(26, 2, 'Nikhil', 22, 'Male', 'advertisement', 'neck_pain', '7384737474', 'Severe pain', '2025-09-17', 'visited', '2025-09-16 16:00:44'),
(27, 2, 'Raj kumar', 20, 'Male', 'returning_patient', 'low_back_pain', '7384783783', 'kdsjfijfj', '2025-09-22', 'visited', '2025-09-19 19:37:11'),
(28, 2, 'Aditya', 22, 'Male', 'web_search', 'low_back_pain', '8993849384', 'inquiry check for logs', '2025-09-27', 'visited', '2025-09-27 09:38:44');

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
  `payment_method` enum('cash','card','upi','cheque','other') DEFAULT 'cash',
  `remarks` text DEFAULT NULL,
  `doctor_notes` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `status` enum('Pending','Consulted','Closed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`registration_id`, `master_patient_id`, `branch_id`, `inquiry_id`, `patient_name`, `phone_number`, `email`, `gender`, `age`, `chief_complain`, `referralSource`, `reffered_by`, `occupation`, `address`, `consultation_type`, `appointment_date`, `appointment_time`, `consultation_amount`, `payment_method`, `remarks`, `doctor_notes`, `prescription`, `follow_up_date`, `status`, `created_at`, `updated_at`) VALUES
(2, NULL, 2, NULL, 'testing', '8397383937', '', 'Male', 22, 'back_pain', 'local_event', 'dr kk', 'dr', '', 'in-clinic', '2025-09-09', '12:30:00', 600.00, 'cash', '', NULL, NULL, NULL, 'Pending', '2025-09-09 06:58:52', '2025-09-13 06:30:22'),
(3, NULL, 2, NULL, 'Testing', '8394938938', '', 'Male', 23, 'neck_pain', 'social_media', 'dr kp', 'student', '', 'in-clinic', '2025-09-10', '12:30:00', 600.00, 'cash', '', NULL, NULL, NULL, 'Pending', '2025-09-10 06:37:52', '2025-09-13 06:30:15'),
(4, NULL, 2, NULL, 'Saket', '7389634384', '', 'Male', 24, 'back_pain', 'social_media', 'dr kk', 'student', '', 'in-clinic', '2025-09-11', '17:00:00', 600.00, 'cash', 'hello', NULL, NULL, NULL, 'Closed', '2025-09-11 10:58:34', '2025-09-13 06:28:13'),
(5, NULL, 2, 24, 'nnnn', '7728273282', '', 'Male', 22, 'back_pain', 'social_media', 'dr mm jha', 'student', '', 'in-clinic', '2025-09-12', '15:50:00', 600.00, 'cash', 'nown', NULL, NULL, NULL, 'Consulted', '2025-09-12 09:34:06', '2025-09-13 06:24:02'),
(6, NULL, 2, NULL, 'sumit', '7838348374', '', 'Male', 22, 'back_pain', 'web_search', 'dd', 'ss', '', 'in-clinic', '2025-09-12', '15:30:00', 600.00, 'upi', 'none', NULL, NULL, NULL, 'Pending', '2025-09-12 09:36:41', '2025-09-12 09:36:41'),
(7, NULL, 2, 23, 'sumit', '7838348374', '', 'Male', 22, 'back_pain', 'web_search', 'dr kk', 'stuedent', '', 'in-clinic', '2025-09-12', '15:40:00', 600.00, 'cash', 'none', NULL, NULL, NULL, 'Consulted', '2025-09-12 09:52:54', '2025-09-13 05:54:37'),
(8, NULL, 2, NULL, 'Chandan', '8839783989', '', 'Male', 23, 'back_pain', 'returning_patient', 'Dr prnav', 'Student', '', 'in-clinic', '2025-09-12', '16:30:00', 600.00, 'upi', 'minor pain', NULL, NULL, NULL, 'Consulted', '2025-09-12 10:47:51', '2025-09-13 06:26:56'),
(9, NULL, 2, 25, 'Chandan Shukla', '8939394893', '', 'Male', 24, 'low_back_pain', 'social_media', 'Dr Pranav', 'student', '', 'in-clinic', '2025-09-12', '17:10:00', 600.00, 'cash', 'hii', NULL, NULL, NULL, 'Consulted', '2025-09-12 10:51:53', '2025-09-13 07:44:29'),
(10, NULL, 2, NULL, 'Testing', '8973843843', '', 'Male', 22, 'back_pain', 'social_media', 'DR TT', 'Student', '', 'in-clinic', '2025-09-14', '16:30:00', 600.00, 'card', 'checking time slot', NULL, NULL, NULL, 'Consulted', '2025-09-14 10:40:30', '2025-09-25 11:16:36'),
(11, NULL, 2, NULL, 'Time slot test', '7873437883', '', 'Male', 12, 'radiating_pain', 'family', 'dd', 'sjdfjdk', '', 'in-clinic', '2025-09-14', '17:00:00', 600.00, 'card', '', NULL, NULL, NULL, 'Pending', '2025-09-14 10:58:15', '2025-09-14 10:58:15'),
(12, NULL, 2, NULL, 'Nikhil', '7834939437', '', 'Male', 22, 'neck_pain', 'advertisement', 'DR KK Menon', 'student', '', 'in-clinic', '2025-09-17', '11:30:00', 600.00, 'upi', 'severe neck pain', NULL, NULL, NULL, 'Closed', '2025-09-16 16:00:10', '2025-09-19 13:04:18'),
(13, NULL, 2, NULL, 'Sumit', '7739923493', '', 'Male', 24, 'back_pain', 'social_media', 'Dr KK', 'Student', '', 'in-clinic', '2025-09-20', '13:00:00', 600.00, 'upi', '', NULL, NULL, NULL, 'Consulted', '2025-09-19 19:19:31', '2025-09-19 19:41:22'),
(14, NULL, 2, NULL, 'Raahul', '8943248923', 'rahul@gmail.com', 'Male', 22, 'neck_pain', 'local_event', 'dr mmb', 'student', 'noida', 'speech-therapy', '2025-09-25', '17:30:00', 600.00, 'upi', 'normal', NULL, NULL, NULL, 'Consulted', '2025-09-25 11:34:38', '2025-09-25 11:49:04'),
(15, NULL, 2, NULL, 'Adi', '9773737439', 'adi@gmail.com', 'Male', 20, 'neck_pain', 'returning_patient', 'dr ss', 'student', 'noida', 'speech-therapy', '2025-09-25', '18:00:00', 600.00, 'upi', 'none', NULL, NULL, NULL, 'Consulted', '2025-09-25 11:44:26', '2025-09-26 11:04:18'),
(16, NULL, 2, NULL, 'Aditya Singh', '8939493849', 'aditya@gmail.com', 'Male', 22, 'radiating_pain', 'returning_patient', 'Dr Sumit', 'student', 'noida', 'in-clinic', '2025-09-27', '15:30:00', 600.00, 'upi', 'pain in the back', NULL, NULL, NULL, 'Consulted', '2025-09-27 09:54:00', '2025-09-27 13:18:03'),
(17, NULL, 2, NULL, 'Mahi', '8938938439', 'mahi@gmail.com', 'Female', 21, 'low_back_pain', 'doctor_referral', 'Dr Sumit', 'teacher', 'noida', 'in-clinic', '2025-09-27', '16:00:00', 600.00, 'upi', 'pain in the lower back', NULL, NULL, NULL, 'Consulted', '2025-09-27 09:58:47', '2025-09-27 11:43:59'),
(18, NULL, 2, NULL, 'Ravi', '7899839489', '', 'Male', 20, 'radiating_pain', 'local_event', 'Dr Sumit', 'Student', '', 'in-clinic', '2025-09-29', '12:00:00', 600.00, 'cash', 'none', NULL, NULL, NULL, 'Consulted', '2025-09-29 15:41:50', '2025-10-01 16:42:41'),
(19, 1, 2, NULL, 'Bhumi', '8938934893', 'bhumi@gmail.com', 'Female', 22, 'radiating_pain', 'employee', 'Dr Sumit', 'student', '', 'in-clinic', '2025-10-01', '12:30:00', 600.00, 'cash', 'pain due to sitting too long', NULL, NULL, NULL, 'Consulted', '2025-10-01 06:35:10', '2025-10-01 09:51:54'),
(20, NULL, 2, 28, 'Aditya', '8993849384', '', 'Male', 22, 'low_back_pain', 'web_search', 'Dr Sumit', 'student', '', 'in-clinic', '2025-10-02', '17:00:00', 600.00, 'cash', 'inquiry check for logs', NULL, NULL, NULL, 'Consulted', '2025-10-02 11:26:58', '2025-10-02 17:52:46'),
(21, 2, 2, 27, 'Raj kumar', '7384783783', NULL, 'Male', 20, 'low_back_pain', 'returning_patient', 'Dr Pranav', 'Cook', NULL, 'in-clinic', '2025-10-02', '17:30:00', 600.00, 'upi', 'kdsjfijfj', NULL, NULL, NULL, 'Consulted', '2025-10-02 11:31:01', '2025-10-03 22:11:13'),
(22, 3, 2, NULL, 'Priyanshu SIngh', '8993849849', 'priyanshu@gmail.com', 'Male', 22, 'back_pain', 'social_media', 'Dr Pranav', 'Student', 'Bihar', 'in-clinic', '2025-10-04', '11:00:00', 600.00, 'cash', 'normal pain', NULL, NULL, NULL, 'Pending', '2025-10-03 22:12:46', '2025-10-06 14:06:22');

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

CREATE TABLE `tests` (
  `test_id` int(11) NOT NULL,
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
  `payment_method` enum('cash','upi','card','cheque','other') DEFAULT 'cash',
  `payment_status` enum('pending','partial','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `test_status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tests`
--

INSERT INTO `tests` (`test_id`, `branch_id`, `patient_id`, `inquiry_id`, `visit_date`, `assigned_test_date`, `patient_name`, `phone_number`, `gender`, `age`, `dob`, `parents`, `relation`, `alternate_phone_no`, `limb`, `test_name`, `referred_by`, `test_done_by`, `total_amount`, `advance_amount`, `due_amount`, `discount`, `payment_method`, `payment_status`, `created_at`, `updated_at`, `test_status`) VALUES
(1, 2, NULL, NULL, '2025-09-05', '2025-09-06', 'raj', '2342434343', 'Male', 22, NULL, NULL, NULL, NULL, 'upper_limb', 'ncv', 'dr kk', 'achal', 2000.00, 1500.00, 500.00, NULL, 'cash', 'partial', '2025-09-05 08:52:39', '2025-09-05 08:52:39', 'pending'),
(2, 2, NULL, NULL, '2025-09-05', '2025-09-06', 'Sumit', '7738297389', 'Male', 22, NULL, NULL, NULL, NULL, NULL, 'eeg', 'DR KK MENON', 'pancham', 2000.00, 1500.00, 500.00, NULL, 'cash', 'partial', '2025-09-05 11:57:57', '2025-09-26 08:27:55', 'pending'),
(3, 2, NULL, 1, '2025-09-05', '2025-09-05', 'Sumit', '7738297389', 'Male', 22, NULL, NULL, NULL, NULL, NULL, 'eeg', 'DR KK MENON', 'sayan', 2000.00, 1500.00, 500.00, NULL, 'cash', 'partial', '2025-09-05 12:18:51', '2025-09-05 12:18:51', 'pending'),
(4, 2, NULL, NULL, '2025-09-09', '2025-09-09', 'rest', '7387348734', 'Male', 23, NULL, NULL, NULL, NULL, 'upper_limb', 'eeg', 'dr kk', 'ashish', 2000.00, 2000.00, 0.00, NULL, 'cash', 'paid', '2025-09-09 04:40:17', '2025-09-26 09:33:34', 'completed'),
(5, 2, NULL, NULL, '2025-09-09', '2025-09-09', 'tehsifh', '7383747239', 'Male', 24, NULL, NULL, NULL, NULL, NULL, 'emg', 'dd', 'achal', 2000.00, 2000.00, 0.00, NULL, 'cash', 'paid', '2025-09-09 04:44:22', '2025-09-26 07:50:48', 'completed'),
(6, 2, NULL, NULL, '2025-09-09', '2025-09-09', 'hdifhdi', '7934938338', 'Female', 11, NULL, NULL, NULL, NULL, NULL, 'eeg', 'dd', 'ashish', 2000.00, 2000.00, 0.00, NULL, 'cash', 'paid', '2025-09-09 05:17:53', '2025-09-26 07:48:50', 'completed'),
(7, 2, NULL, 4, '2025-09-12', '2025-09-12', 'Sumit', '7834734737', 'Male', 22, NULL, NULL, NULL, NULL, NULL, 'emg', 'Dr mm', 'ashish', 2000.00, 2000.00, 0.00, 0.00, 'cash', 'paid', '2025-09-12 10:21:34', '2025-09-26 07:48:22', 'completed'),
(8, 2, NULL, 5, '2025-09-12', '2025-09-12', 'Chandan', '7938938498', 'Male', 24, '2004-09-15', 'Sumit', 'Friend', '7783783748', 'both', 'vep', 'DR Pranav', 'pancham', 3000.00, 2500.00, 0.00, 500.00, 'upi', 'paid', '2025-09-12 10:51:03', '2025-09-26 07:59:08', 'completed'),
(9, 2, NULL, NULL, '2025-09-27', '2025-09-28', 'Mahi', '7889374893', 'Female', 22, NULL, NULL, NULL, NULL, 'lower_limb', 'vep', 'Dr Sumit', 'pancham', 2000.00, 2000.00, 0.00, NULL, 'upi', 'paid', '2025-09-27 10:04:51', '2025-09-27 11:45:44', 'pending'),
(10, 2, NULL, 6, '2025-10-02', '2025-10-03', 'Aditya', '7889374893', 'Male', 22, NULL, NULL, NULL, NULL, 'both', 'bera', 'Dr Sumit', 'sayan', 2000.00, 2000.00, 0.00, 0.00, 'upi', 'paid', '2025-10-02 11:33:07', '2025-10-06 17:40:28', 'completed');

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
(6, 2, 'Aditya', 'bera', 'Dr Sumit', '7889374893', '2025-09-28', 'visited', '2025-09-27 09:42:55');

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
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

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
  MODIFY `message_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `patients_treatment`
--
ALTER TABLE `patients_treatment`
  MODIFY `treatment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_master`
--
ALTER TABLE `patient_master`
  MODIFY `master_patient_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `quick_inquiry`
--
ALTER TABLE `quick_inquiry`
  MODIFY `inquiry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `tests`
--
ALTER TABLE `tests`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tests_lists`
--
ALTER TABLE `tests_lists`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_inquiry`
--
ALTER TABLE `test_inquiry`
  MODIFY `inquiry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
