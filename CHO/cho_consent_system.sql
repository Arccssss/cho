-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 10:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cho_consent_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reference_number` varchar(20) DEFAULT NULL,
  `qr_code_data` text DEFAULT NULL,
  `client_name` varchar(255) NOT NULL,
  `philhealth_no` varchar(50) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `maiden_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` enum('Male','Female','Other') DEFAULT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `time_period` enum('AM','PM') DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `service_type` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed','no_show') DEFAULT 'pending',
  `arrived_status` enum('not_arrived','arrived','cancelled_by_client') DEFAULT 'not_arrived',
  `notification_sent` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `reference_number`, `qr_code_data`, `client_name`, `philhealth_no`, `last_name`, `first_name`, `middle_name`, `maiden_name`, `suffix`, `date_of_birth`, `sex`, `civil_status`, `barangay`, `contact_number`, `email`, `appointment_date`, `appointment_time`, `time_period`, `purpose`, `service_type`, `status`, `arrived_status`, `notification_sent`, `notes`, `created_at`, `updated_at`, `processed_by`, `processed_at`) VALUES
(3, NULL, 'CHO-2026-000003', 'CHO Appointment Reference: CHO-2026-000003\nName: Mark Kent Gimarangan\nDate: March 31, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: Others\nContact: 0939-830-0291', 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', NULL, '', '2003-06-23', 'Male', 'Single', '36', '0939-830-0291', 'markkent21gimrangan@gmail.com', '2026-03-31', '09:00:00', 'AM', 'Others', NULL, 'completed', 'not_arrived', 0, '', '2026-03-09 10:14:00', '2026-03-16 05:19:29', NULL, NULL),
(4, NULL, 'CHO-2026-000004', 'CHO Appointment Reference: CHO-2026-000004\nName: Joemarie Bando Gimarangan\nDate: March 9, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: Others\nContact: 0939-830-0291', 'Joemarie Bando Gimarangan', '', 'Gimarangan', 'Joemarie', 'Bando', NULL, '', '1975-01-14', 'Male', 'Married', '36', '0939-830-0291', 'markkent21gimrangan@gmail.com', '2026-03-09', '09:00:00', 'AM', 'Others', NULL, 'no_show', 'not_arrived', 0, '', '2026-03-09 10:48:28', '2026-03-16 05:19:29', NULL, NULL),
(5, NULL, 'CHO-2026-000005', 'CHO Appointment Reference: CHO-2026-000005\nName: Rovic Dave Victory Rosal\nDate: March 10, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: Laboratory Tests\nContact: 0912-456-7945', 'Rovic Dave Victory Rosal', '', 'Rosal', 'Rovic Dave', 'Victory', NULL, '', '2003-07-23', 'Male', 'Single', 'villamonte', '0912-456-7945', 'rovicdaverosal@gmail.com', '2026-03-10', '09:00:00', 'AM', 'Laboratory Tests', NULL, 'completed', 'not_arrived', 0, '', '2026-03-10 08:01:36', '2026-03-16 05:19:29', NULL, NULL),
(6, NULL, 'CHO-2026-000006', 'CHO Appointment Reference: CHO-2026-000006\nName: Mark Kent  Chua Gimarangan\nDate: March 12, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: laboratory\nContact: 09398300291', 'Mark Kent  Chua Gimarangan', '22-242627-2', 'Gimarangan', 'Mark Kent ', 'Chua', NULL, '', '2003-06-23', 'Male', 'single', '36', '09398300291', 'markkent21gimrangan@gmail.com', '2026-03-12', '09:00:00', 'AM', 'laboratory', NULL, 'completed', 'not_arrived', 0, '', '2026-03-12 13:19:11', '2026-03-16 05:19:29', NULL, NULL),
(7, NULL, 'CHO-2026-000007', 'CHO Appointment Reference: CHO-2026-000007\nName: Mark Kent Gimarangan\nDate: March 12, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: consultation\nContact: 09398300291', 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', NULL, '', '2003-02-06', 'Male', 'single', '36', '09398300291', 'markkent21gimrangan@gmail.com', '2026-03-12', '09:00:00', 'AM', 'consultation', NULL, 'completed', 'not_arrived', 0, '', '2026-03-12 13:25:27', '2026-03-16 05:19:29', NULL, NULL),
(8, NULL, 'CHO-2026-000008', 'CHO Appointment Reference: CHO-2026-000008\nName: Joemarie Montaño Gimarangan Jr.\nDate: March 12, 2026\nTime: AFTERNOON (1:00 PM - 5:00 PM)\nPurpose: vaccination\nContact: 0997451248', 'Joemarie Montaño Gimarangan Jr.', '22-04578-12', 'Gimarangan', 'Joemarie', 'Montaño', NULL, 'Jr.', '1997-07-07', 'Male', 'single', '36', '0997451248', 'markkent21gimrangan@gmail.com', '2026-03-12', '09:00:00', 'AM', 'vaccination', NULL, 'completed', 'not_arrived', 0, '', '2026-03-12 14:40:38', '2026-03-16 05:19:29', NULL, NULL),
(19, NULL, NULL, NULL, 'Test User 1773633093', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1234567890', NULL, '2026-03-16', '09:00:00', 'AM', 'Test Booking', NULL, 'completed', 'not_arrived', 0, NULL, '2026-03-16 03:51:33', '2026-03-16 05:19:29', NULL, NULL),
(20, NULL, 'CHO-2026-000020', 'CHO Appointment Reference: CHO-2026-000020\nName: Ralph Shane Flores Divinagracia\nDate: March 16, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: pediatric\nContact: 09480302594', 'Ralph Shane Flores Divinagracia', '', 'Divinagracia', 'Ralph Shane', 'Flores', NULL, '', '2003-02-04', 'Male', 'single', 'cabug', '09480302594', 'divinagraciaralphshane@gmail.com', '2026-03-16', '09:00:00', 'AM', 'pediatric', NULL, 'completed', 'not_arrived', 0, '', '2026-03-16 04:58:09', '2026-03-16 05:19:29', NULL, NULL),
(25, NULL, 'CHO-2026-000025', 'CHO Appointment Reference: CHO-2026-000025\nName: rovic cruz rosal\nDate: March 18, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: vaccination\nContact: 0932143143', 'rovic cruz rosal', '', 'rosal', 'rovic', 'cruz', NULL, '', '2003-03-24', 'Male', 'single', 'cabug', '0932143143', 'rovic@gmail.com', '2026-03-18', '00:00:00', 'AM', 'vaccination', NULL, 'completed', 'not_arrived', 0, '', '2026-03-16 06:14:53', '2026-03-16 06:15:36', NULL, NULL),
(26, NULL, 'CHO-2026-000026', 'CHO Appointment Reference: CHO-2026-000026\nName: dave victor uy Jr.\nDate: March 19, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: vaccination\nContact: 09398300291', 'dave victor uy Jr.', '', 'uy', 'dave', 'victor', NULL, 'Jr.', '2004-12-22', 'Male', 'single', 'cabug', '09398300291', 'uydave123@gmail.com', '2026-03-19', '00:00:00', 'AM', 'vaccination', NULL, 'completed', 'not_arrived', 0, '', '2026-03-16 06:28:59', '2026-03-16 06:29:19', NULL, NULL),
(27, NULL, 'CHO-2026-000027', 'CHO Appointment Reference: CHO-2026-000027\nName: john uy manuel Jr.\nDate: March 25, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: consultation\nContact: 09123456789', 'john uy manuel Jr.', '', 'manuel', 'john', 'uy', NULL, 'Jr.', '2001-10-22', 'Male', 'single', '36', '09123456789', 'johnmanuel@gmail.com', '2026-03-25', '00:00:00', 'AM', 'consultation', NULL, 'completed', 'not_arrived', 0, '', '2026-03-16 06:32:07', '2026-03-16 06:32:22', NULL, NULL),
(28, NULL, 'CHO-2026-000028', 'CHO Appointment Reference: CHO-2026-000028\nName: Rigor Santa Demagiba\nDate: March 25, 2026\nTime: AFTERNOON (1:00 PM - 5:00 PM)\nPurpose: laboratory\nContact: 0909090909', 'Rigor Santa Demagiba', '', 'Demagiba', 'Rigor', 'Santa', NULL, '', '1975-04-23', 'Male', 'married', '35', '0909090909', 'rigor_demagiba@gmail.com', '2026-03-25', '00:00:00', 'PM', 'laboratory', NULL, 'confirmed', 'not_arrived', 0, '', '2026-03-19 02:19:09', '2026-03-19 02:19:09', NULL, NULL),
(29, NULL, 'CHO-2026-000029', 'CHO Appointment Reference: CHO-2026-000029\nName: Geo Mongi Santisima\nDate: March 26, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: consultation\nContact: 09094562798', 'Geo Mongi Santisima', '', 'Santisima', 'Geo', 'Mongi', NULL, '', '2000-09-02', 'Male', 'single', '36', '09094562798', 'geo_santisima@gmail.com', '2026-03-26', '00:00:00', 'AM', 'consultation', NULL, 'no_show', 'not_arrived', 0, '', '2026-03-19 02:23:33', '2026-03-30 09:00:10', NULL, NULL),
(30, NULL, 'CHO-2026-000030', 'CHO Appointment Reference: CHO-2026-000030\nName: Mark Magnifico Magsayo\nDate: March 19, 2026\nTime: AFTERNOON (1:00 PM - 5:00 PM)\nPurpose: others\nContact: 09398300291', 'Mark Magnifico Magsayo', '', 'Magsayo', 'Mark', 'Magnifico', NULL, '', '2000-12-02', 'Male', 'single', '36', '09398300291', 'markkent21gimrangan@gmail.com', '2026-03-19', '00:00:00', 'PM', 'others', NULL, 'completed', 'not_arrived', 0, '', '2026-03-19 02:26:33', '2026-03-19 02:26:49', NULL, NULL),
(31, NULL, 'CHO-2026-000031', 'CHO Appointment Reference: CHO-2026-000031\nName: Mark Kent Montaño Gimarangan\nDate: March 19, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: laboratory\nContact: 09398300291', 'Mark Kent Montaño Gimarangan', '', 'Gimarangan', 'Mark Kent', 'Montaño', NULL, '', '2003-06-23', 'Male', 'single', '36', '09398300291', 'markkent21gimrangan@gmail.com', '2026-03-19', '00:00:00', 'AM', 'laboratory', NULL, 'completed', 'not_arrived', 0, '', '2026-03-19 03:00:42', '2026-03-19 03:02:45', NULL, NULL),
(32, NULL, 'CHO-2026-000032', 'CHO Appointment Reference: CHO-2026-000032\nName: Rovic Dave v rosal\nDate: March 19, 2026\nTime: AFTERNOON (1:00 PM - 5:00 PM)\nPurpose: vaccination\nContact: 091234556789', 'Rovic Dave v rosal', '', 'rosal', 'Rovic Dave', 'v', NULL, '', '2003-02-10', 'Male', 'single', 'villamonte', '091234556789', 'rosalro@gmail.com', '2026-03-19', '00:00:00', 'PM', 'vaccination', NULL, 'completed', 'not_arrived', 0, '', '2026-03-19 03:25:47', '2026-03-19 03:26:49', NULL, NULL),
(33, NULL, 'CHO-2026-000033', 'CHO Appointment Reference: CHO-2026-000033\nName: woo jin lee\nDate: March 19, 2026\nTime: AFTERNOON (1:00 PM - 5:00 PM)\nPurpose: checkup\nContact: 09102234567', 'woo jin lee', '', 'lee', 'woo', 'jin', NULL, '', '2003-12-03', 'Male', 'married', '35', '09102234567', 'leewoojn2@gmail.com', '2026-03-19', '00:00:00', 'PM', 'checkup', NULL, 'completed', 'not_arrived', 0, '', '2026-03-19 03:39:59', '2026-03-24 06:13:36', NULL, NULL),
(34, NULL, 'CHO-2026-000034', 'CHO Appointment Reference: CHO-2026-000034\nName: Mark Kent Montaño Gimarangan\nDate: March 24, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: checkup\nContact: 09398300291', 'Mark Kent Montaño Gimarangan', '', 'Gimarangan', 'Mark Kent', 'Montaño', NULL, '', '2003-06-23', 'Male', 'single', '36', '09398300291', 'markkent21gimrangan@gmail.com', '2026-03-24', '00:00:00', 'AM', 'checkup', NULL, 'completed', 'not_arrived', 0, '', '2026-03-24 06:04:16', '2026-03-24 06:05:05', NULL, NULL),
(35, NULL, 'CHO-2026-000035', 'CHO Appointment Reference: CHO-2026-000035\nName: Rovic Dave Vicotry Rosal\nDate: March 24, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: consultation\nContact: 09398300291', 'Rovic Dave Vicotry Rosal', '', 'Rosal', 'Rovic Dave', 'Vicotry', NULL, '', '2003-02-03', 'Male', 'single', '36', '09398300291', 'markkent21gimrangan@gmail.com', '2026-03-24', '00:00:00', 'AM', 'consultation', NULL, 'confirmed', 'not_arrived', 0, '', '2026-03-24 08:10:37', '2026-03-30 06:32:36', NULL, NULL),
(36, NULL, 'CHO-2026-000036', 'CHO Appointment Reference: CHO-2026-000036\nName: Ace Dewill Portgas\nDate: March 24, 2026\nTime: AFTERNOON (1:00 PM - 5:00 PM)\nPurpose: vaccination\nContact: 09398300291', 'Ace Dewill Portgas', '', 'Portgas', 'Ace', 'Dewill', NULL, '', '1998-07-24', 'Male', 'single', '36', '09398300291', 'markkent21gimrangan@gmail.com', '2026-03-24', '00:00:00', 'PM', 'vaccination', NULL, 'confirmed', 'not_arrived', 0, '', '2026-03-24 13:51:27', '2026-03-30 08:38:03', NULL, NULL),
(37, NULL, 'CHO-2026-000037', 'CHO Appointment Reference: CHO-2026-000037\nName: Mark Kent Gimarangan\nDate: March 30, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: vaccination\nContact: 09398300291', 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', NULL, '', '2003-06-23', 'Male', 'single', '36', '09398300291', 'markkent21gimrangan@gmail.com', '2026-03-30', '00:00:00', 'AM', 'vaccination', NULL, 'completed', 'not_arrived', 0, '', '2026-03-30 06:21:38', '2026-03-30 06:23:13', NULL, NULL),
(38, NULL, 'CHO-2026-000038', 'CHO Appointment Reference: CHO-2026-000038\nName: John Mark Rosh Gimarangan\nDate: March 31, 2026\nTime: AFTERNOON (1:00 PM - 5:00 PM)\nPurpose: Medical Consultation, Dental\nContact: 09398300291', 'John Mark Rosh Gimarangan', '', 'Gimarangan', 'John Mark', 'Rosh', NULL, '', '2003-02-06', 'Male', 'single', '36', '09398300291', 'markkent21gimrangan@gmail.com', '2026-03-31', '00:00:00', 'PM', 'Medical Consultation, Dental', NULL, 'pending', 'not_arrived', 0, '', '2026-03-31 02:40:20', '2026-03-31 02:40:20', NULL, NULL),
(39, NULL, 'CHO-2026-000039', 'CHO Appointment Reference: CHO-2026-000039\nName: Joash Gobre Noble\nDate: March 31, 2026\nTime: AFTERNOON (1:00 PM - 5:00 PM)\nPurpose: Chest X-ray\nContact: 09094057281', 'Joash Gobre Noble', '', 'Noble', 'Joash', 'Gobre', NULL, '', '2000-07-02', 'Male', 'single', 'Handumanan', '09094057281', 'joashnoble_02@gmail.com', '2026-03-31', '00:00:00', 'PM', 'Chest X-ray', NULL, 'pending', 'not_arrived', 0, '', '2026-03-31 04:11:16', '2026-03-31 04:11:16', NULL, NULL),
(40, NULL, 'CHO-2026-000040', 'CHO Appointment Reference: CHO-2026-000040\nName: Kevin Tarantula Durant\nDate: March 31, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: TB\nContact: 09967128989', 'Kevin Tarantula Durant', '', 'Durant', 'Kevin', 'Tarantula', NULL, '', '1997-08-09', 'Male', 'single', '36', '09967128989', '', '2026-03-31', '00:00:00', 'AM', 'TB', NULL, 'confirmed', 'not_arrived', 0, '', '2026-03-31 06:22:39', '2026-04-08 07:41:27', NULL, NULL),
(41, NULL, 'CHO-2026-000041', 'CHO Appointment Reference: CHO-2026-000041\nName: Mark Kent Gimarangan\nDate: April 13, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: Animal Bite\nContact: 09398300291', 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', NULL, '', '2003-06-23', 'Male', 'single', '36', '09398300291', 'markkent21gimrangan@gmail.com', '2026-04-13', '00:00:00', 'AM', 'Animal Bite', NULL, 'pending', 'not_arrived', 0, '', '2026-04-13 06:51:58', '2026-04-13 06:51:58', NULL, NULL),
(42, NULL, 'CHO-2026-000042', 'CHO Appointment Reference: CHO-2026-000042\nName: Mark Kent Gimarangan\nDate: April 14, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: Medical Consultation\nContact: 09398300291', 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', NULL, '', '2003-06-23', 'Male', 'single', '36', '09398300291', 'markkent21gimrangan@gmail.com', '2026-04-14', '00:00:00', 'AM', 'Medical Consultation', NULL, 'confirmed', 'not_arrived', 0, '', '2026-04-14 03:40:48', '2026-04-14 03:41:01', NULL, NULL),
(43, NULL, 'CHO-2026-000043', 'CHO Appointment Reference: CHO-2026-000043\nName: dasdad adadad adsada\nDate: April 14, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: Medical Consultation\nContact: 0910101017', 'dasdad adadad adsada', '', 'adsada', 'dasdad', 'adadad', NULL, '', '2025-02-11', 'Male', 'married', '36', '0910101017', '', '2026-04-14', '00:00:00', 'AM', 'Medical Consultation', NULL, 'pending', 'not_arrived', 0, '', '2026-04-14 06:47:19', '2026-04-14 06:47:19', NULL, NULL),
(44, NULL, 'CHO-2026-000044', NULL, 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', '', '', '2003-06-23', 'Male', 'Single', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-04-27', '00:00:00', 'AM', 'Medical Consultation', NULL, 'pending', 'not_arrived', 0, '', '2026-04-27 02:54:00', '2026-04-27 02:54:00', NULL, NULL),
(45, NULL, 'CHO-2026-000045', NULL, 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', '', '', '2003-06-23', 'Male', 'Single', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-04-27', '00:00:00', 'AM', 'Medical Consultation', NULL, 'pending', 'not_arrived', 0, '', '2026-04-27 02:55:09', '2026-04-27 02:55:09', NULL, NULL),
(46, NULL, 'CHO-2026-000046', NULL, 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', '', '', '2003-06-23', 'Male', 'Single', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-04-27', '00:00:00', 'AM', 'Medical Consultation', NULL, 'pending', 'not_arrived', 0, '', '2026-04-27 02:55:16', '2026-04-27 02:55:16', NULL, NULL),
(47, NULL, 'CHO-2026-000047', NULL, 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', '', '', '2003-06-23', 'Male', 'Single', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-04-27', '00:00:00', 'AM', 'Medical Consultation', NULL, 'pending', 'not_arrived', 0, '', '2026-04-27 02:58:19', '2026-04-27 02:58:19', NULL, NULL),
(48, NULL, 'CHO-2026-000048', 'CHO Appointment Reference: CHO-2026-000048\nName: Mark Kent Gimarangan\nDate: April 27, 2026\nTime: AFTERNOON (1:00 PM - 5:00 PM)\nPurpose: Medical Consultation\nContact: 09398300291', 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', '', '', '2003-06-23', 'Male', 'Single', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-04-27', '00:00:00', 'PM', 'Medical Consultation', NULL, 'pending', 'not_arrived', 0, '', '2026-04-27 03:05:33', '2026-04-27 03:05:34', NULL, NULL),
(49, NULL, 'CHO-2026-000049', 'CHO Appointment Reference: CHO-2026-000049\nName: rovic Kent rosal\nDate: April 28, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: Medical Consultation, Adult/Pediatric, Postnatal Checkup\nContact: 09159735489', 'rovic Kent rosal', '1546135356465', 'rosal', 'rovic', 'Kent', '', '', '2003-10-22', 'Male', 'Single', 'barangay mansilingan bacolod city', '09159735489', 'markkent21gimrangan@gmail.com', '2026-04-28', '00:00:00', 'AM', 'Medical Consultation, Adult/Pediatric, Postnatal Checkup', NULL, 'pending', 'not_arrived', 0, '', '2026-04-28 06:06:18', '2026-04-28 06:06:18', NULL, NULL),
(50, NULL, 'CHO-2026-000050', 'CHO Appointment Reference: CHO-2026-000050\nName: Tanoy Mongi Antares\nDate: April 28, 2026\nTime: AFTERNOON (1:00 PM - 5:00 PM)\nPurpose: TB, Social Hygiene\nContact: 09097851234', 'Tanoy Mongi Antares', '1546135356465', 'Antares', 'Tanoy', 'Mongi', '', '', '1997-08-02', 'Male', 'Single', 'Handumanan', '09097851234', 'tanoyskie24@gmail.com', '2026-04-28', '00:00:00', 'PM', 'TB, Social Hygiene', NULL, 'pending', 'not_arrived', 0, '', '2026-04-28 07:06:02', '2026-04-28 07:06:02', NULL, NULL),
(51, NULL, 'CHO-2026-000051', 'CHO Appointment Reference: CHO-2026-000051\nName: Maribel Winner Lashly\nDate: April 28, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: Prenatal Checkup\nContact: 09398300291', 'Maribel Winner Lashly', '1546135356465', 'Lashly', 'Maribel', 'Winner', 'Maribel Mayag Winner', '', '1997-10-02', 'Female', 'Married', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-04-28', '00:00:00', 'AM', 'Prenatal Checkup', NULL, 'pending', 'not_arrived', 0, '', '2026-04-28 07:53:30', '2026-04-28 07:53:30', NULL, NULL),
(52, NULL, 'CHO-2026-000052', 'CHO Appointment Reference: CHO-2026-000052\nName: MARIE  WENGSHU GALASAO\nDate: April 27, 2026\nTime: MORNING (8:00 AM - 12:00 PM)\nPurpose: Prenatal Checkup\nContact: 09097457898', 'MARIE  WENGSHU GALASAO', '1546135356465', 'GALASAO', 'MARIE ', 'WENGSHU', 'MARIE KUPRA WENGSHU', '', '1992-02-17', 'Female', 'Married', 'BARANGAY 40, BACOLOD CITY', '09097457898', 'marie_wengshu17@gmail.com', '2026-04-27', '00:00:00', 'AM', 'Prenatal Checkup', NULL, 'pending', 'not_arrived', 0, '', '2026-05-01 03:03:43', '2026-05-01 03:03:43', NULL, NULL),
(53, NULL, 'CHO-2026-000053', 'CHO Appointment Reference: CHO-2026-000053\nName: Hervy Buset Gallardo\nDate: May 1, 2026\nTime: AFTERNOON (1:00 PM - 5:00 PM)\nPurpose: Adult/Pediatric\nContact: 0939006721', 'Hervy Buset Gallardo', '1546135356465', 'Gallardo', 'Hervy', 'Buset', '', '', '2002-04-03', 'Male', 'Single', 'Barangay Bata, Bacolod City', '0939006721', 'hervy_gallardo@yahoo.com', '2026-05-01', '00:00:00', 'PM', 'Adult/Pediatric', NULL, 'pending', 'not_arrived', 0, '', '2026-05-01 03:21:49', '2026-05-01 03:21:49', NULL, NULL),
(54, NULL, 'CHO-2026-000054', 'CHO Appointment Reference: CHO-2026-000054\nName: Mark Kent Gimarangan\nDate: May 1, 2026\nTime: AFTERNOON (1:00 PM - 5:00 PM)\nPurpose: Animal Bite\nContact: 09398300291', 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', '', '', '2003-06-23', 'Male', 'Single', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-05-01', '00:00:00', 'PM', 'Animal Bite', NULL, 'pending', 'not_arrived', 0, '', '2026-05-01 05:04:27', '2026-05-01 05:04:28', NULL, NULL),
(55, NULL, 'CHO-2026-000055', 'CHO Appointment Reference: CHO-2026-000055\nName: Mark Kent Gimarangan\nDate: May 7, 2026\nTime: WHOLE DAY (8:00 AM - 5:00 PM)\nPurpose: Medical Consultation\nContact: 09398300291', 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', '', '', '2003-06-23', 'Male', 'Single', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-05-07', '00:00:00', 'AM', 'Medical Consultation', NULL, 'pending', 'not_arrived', 0, '', '2026-05-07 15:48:37', '2026-05-07 15:48:38', NULL, NULL),
(56, NULL, 'CHO-2026-000056', 'CHO Appointment Reference: CHO-2026-000056\nName: Mark Kent Gimarangan\nDate: May 7, 2026\nTime: WHOLE DAY (8:00 AM - 5:00 PM)\nPurpose: Medical Consultation\nContact: 09398300291', 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', '', '', '2003-06-23', 'Male', 'Single', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-05-07', '00:00:00', 'AM', 'Medical Consultation', NULL, 'pending', 'not_arrived', 0, '', '2026-05-07 16:13:23', '2026-05-07 16:13:23', NULL, NULL),
(57, NULL, 'CHO-2026-000057', 'CHO Appointment Reference: CHO-2026-000057\nName: Mark Kent Gimarangan\nDate: May 8, 2026\nTime: WHOLE DAY (8:00 AM - 5:00 PM)\nPurpose: Medical Consultation\nContact: 09398300291', 'Mark Kent Gimarangan', '12312312', 'Gimarangan', 'Mark', 'Kent', '', '', '2003-06-23', 'Male', 'Single', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-05-08', '00:00:00', 'AM', 'Medical Consultation', NULL, 'cancelled', 'not_arrived', 0, '', '2026-05-07 16:42:05', '2026-05-08 06:18:46', NULL, NULL),
(58, NULL, 'CHO-2026-000058', 'CHO Appointment Reference: CHO-2026-000058\nName: mark Kent rosal\nDate: May 8, 2026\nTime: WHOLE DAY (8:00 AM - 5:00 PM)\nPurpose: Medical Consultation\nContact: 09398300291', 'mark Kent rosal', '23213124211', 'rosal', 'mark', 'Kent', '', '', '2009-01-03', 'Male', 'Single', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-05-08', '00:00:00', 'AM', 'Medical Consultation', NULL, 'completed', 'not_arrived', 0, '', '2026-05-08 06:08:54', '2026-05-08 06:19:10', NULL, NULL),
(59, NULL, 'CHO-2026-000059', 'CHO Appointment Reference: CHO-2026-000059\nName: Mark Kent Gimarangan\nDate: May 11, 2026\nTime: WHOLE DAY (8:00 AM - 5:00 PM)\nPurpose: Animal Bite\nContact: 09398300291', 'Mark Kent Gimarangan', '1546135356465', 'Gimarangan', 'Mark', 'Kent', '', '', '2012-08-18', 'Male', 'Married', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-05-11', '00:00:00', 'AM', 'Animal Bite', NULL, 'confirmed', 'not_arrived', 0, '', '2026-05-10 16:22:24', '2026-05-11 07:50:26', NULL, NULL),
(60, NULL, 'CHO-2026-000060', 'CHO Appointment Reference: CHO-2026-000060\nName: WENG PALUPOTAN ELADIO\nDate: May 11, 2026\nTime: WHOLE DAY (8:00 AM - 5:00 PM)\nPurpose: Dental\nContact: 09398300291', 'WENG PALUPOTAN ELADIO', '12312312', 'ELADIO', 'WENG', 'PALUPOTAN', '', '', '2010-01-15', 'Female', 'Single', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-05-11', '00:00:00', 'AM', 'Dental', NULL, 'pending', 'not_arrived', 0, '', '2026-05-10 16:44:57', '2026-05-10 16:44:57', NULL, NULL),
(61, NULL, 'CHO-2026-000061', 'CHO Appointment Reference: CHO-2026-000061\nName: Mark Kent Gimarangan\nDate: May 11, 2026\nTime: WHOLE DAY (8:00 AM - 5:00 PM)\nPurpose: Medical Consultation\nContact: 09398300291', 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', '', '', '2006-05-14', 'Male', 'Single', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-05-11', '00:00:00', 'AM', 'Medical Consultation', NULL, 'pending', 'not_arrived', 0, '', '2026-05-11 07:33:45', '2026-05-11 07:33:45', NULL, NULL),
(62, NULL, 'CHO-2026-000062', 'CHO Appointment Reference: CHO-2026-000062\nName: Mark Kent Gimarangan\nDate: May 11, 2026\nTime: WHOLE DAY (8:00 AM - 5:00 PM)\nPurpose: Medical Consultation, Dental\nContact: 09398300291', 'Mark Kent Gimarangan', '', 'Gimarangan', 'Mark', 'Kent', '', '', '2003-06-13', 'Male', 'Single', 'Barangay 36, Bacolod City', '09398300291', 'markkent21gimrangan@gmail.com', '2026-05-11', '00:00:00', 'AM', 'Medical Consultation, Dental', NULL, 'pending', 'not_arrived', 0, '', '2026-05-11 07:49:52', '2026-05-11 07:49:52', NULL, NULL);

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `tr_appointment_delete_update_slots` AFTER DELETE ON `appointments` FOR EACH ROW BEGIN
    IF OLD.status NOT IN ('cancelled', 'no_show') THEN
        UPDATE appointment_time_slots 
        SET current_booked = GREATEST(0, current_booked - 1),
            last_updated = NOW()
        WHERE day_of_week = LOWER(DAYNAME(OLD.appointment_date)) 
        AND time_period = OLD.time_period;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_appointment_insert_update_slots` AFTER INSERT ON `appointments` FOR EACH ROW BEGIN
    IF NEW.status NOT IN ('cancelled', 'no_show') THEN
        UPDATE appointment_time_slots 
        SET current_booked = current_booked + 1,
            last_updated = NOW()
        WHERE day_of_week = LOWER(DAYNAME(NEW.appointment_date)) 
        AND time_period = NEW.time_period;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_appointment_update_slots` AFTER UPDATE ON `appointments` FOR EACH ROW BEGIN
    -- Handle status changes that affect slot counts
    IF OLD.status IN ('cancelled', 'no_show') AND NEW.status NOT IN ('cancelled', 'no_show') THEN
        -- Appointment was cancelled/no-show and is now active
        UPDATE appointment_time_slots 
        SET current_booked = current_booked + 1,
            last_updated = NOW()
        WHERE day_of_week = LOWER(DAYNAME(NEW.appointment_date)) 
        AND time_period = NEW.time_period;
    ELSEIF OLD.status NOT IN ('cancelled', 'no_show') AND NEW.status IN ('cancelled', 'no_show') THEN
        -- Appointment was active and is now cancelled/no-show
        UPDATE appointment_time_slots 
        SET current_booked = GREATEST(0, current_booked - 1),
            last_updated = NOW()
        WHERE day_of_week = LOWER(DAYNAME(NEW.appointment_date)) 
        AND time_period = NEW.time_period;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_am_pm_slots`
--

CREATE TABLE `appointment_am_pm_slots` (
  `id` int(11) NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `time_period` enum('AM','PM') NOT NULL,
  `max_appointments` int(11) DEFAULT 50,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_am_pm_slots`
--

INSERT INTO `appointment_am_pm_slots` (`id`, `day_of_week`, `time_period`, `max_appointments`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'monday', 'AM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54'),
(2, 'monday', 'PM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54'),
(3, 'tuesday', 'AM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54'),
(4, 'tuesday', 'PM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54'),
(5, 'wednesday', 'AM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54'),
(6, 'wednesday', 'PM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54'),
(7, 'thursday', 'AM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54'),
(8, 'thursday', 'PM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54'),
(9, 'friday', 'AM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54'),
(10, 'friday', 'PM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54'),
(11, 'saturday', 'AM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54'),
(12, 'saturday', 'PM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54'),
(13, 'sunday', 'AM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54'),
(14, 'sunday', 'PM', 50, 1, '2026-03-16 02:37:35', '2026-03-31 04:50:54');

-- --------------------------------------------------------

--
-- Table structure for table `appointment_time_slots`
--

CREATE TABLE `appointment_time_slots` (
  `id` int(11) NOT NULL,
  `display_label` varchar(50) DEFAULT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `time_period` enum('AM','PM') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_appointments` int(11) DEFAULT 50,
  `is_active` tinyint(1) DEFAULT 1,
  `current_booked` int(11) DEFAULT 0 COMMENT 'Current number of booked appointments',
  `available_slots` int(11) GENERATED ALWAYS AS (`max_appointments` - `current_booked`) STORED COMMENT 'Available slots calculated dynamically',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last time slot count was updated',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_time_slots`
--

INSERT INTO `appointment_time_slots` (`id`, `display_label`, `day_of_week`, `time_period`, `start_time`, `end_time`, `max_appointments`, `is_active`, `current_booked`, `last_updated`, `updated_at`) VALUES
(11, 'MORNING', 'monday', 'AM', '08:00:00', '12:00:00', 50, 1, 13, '2026-05-11 07:49:52', '2026-05-11 07:49:52'),
(12, 'AFTERNOON', 'monday', 'PM', '01:00:00', '05:00:00', 50, 1, 1, '2026-04-27 03:05:33', '2026-04-27 03:05:33'),
(13, 'MORNING', 'tuesday', 'AM', '08:00:00', '12:00:00', 50, 1, 9, '2026-04-28 07:53:30', '2026-04-28 07:53:30'),
(14, 'AFTERNOON', 'tuesday', 'PM', '01:00:00', '05:00:00', 50, 1, 4, '2026-04-28 07:06:02', '2026-04-28 07:06:02'),
(15, 'MORNING', 'wednesday', 'AM', '08:00:00', '12:00:00', 50, 1, 2, '2026-03-31 04:24:25', '2026-03-31 04:24:25'),
(16, 'AFTERNOON', 'wednesday', 'PM', '01:00:00', '05:00:00', 50, 1, 1, '2026-03-31 04:24:25', '2026-03-31 04:24:25'),
(17, 'MORNING', 'thursday', 'AM', '08:00:00', '12:00:00', 50, 1, 7, '2026-05-07 16:13:23', '2026-05-07 16:13:23'),
(18, 'AFTERNOON', 'thursday', 'PM', '01:00:00', '05:00:00', 50, 1, 3, '2026-03-31 04:24:25', '2026-03-31 04:24:25'),
(19, 'MORNING', 'friday', 'AM', '08:00:00', '12:00:00', 50, 1, 1, '2026-05-08 06:18:46', '2026-05-08 06:18:46'),
(20, 'AFTERNOON', 'friday', 'PM', '01:00:00', '05:00:00', 50, 1, 2, '2026-05-01 05:04:27', '2026-05-01 05:04:27'),
(21, 'MORNING', 'monday', 'AM', '08:00:00', '12:00:00', 50, 1, 13, '2026-05-11 07:49:52', '2026-05-11 07:49:52'),
(22, 'AFTERNOON', 'monday', 'PM', '01:00:00', '05:00:00', 50, 1, 1, '2026-04-27 03:05:33', '2026-04-27 03:05:33'),
(23, 'MORNING', 'tuesday', 'AM', '08:00:00', '12:00:00', 50, 1, 9, '2026-04-28 07:53:30', '2026-04-28 07:53:30'),
(24, 'AFTERNOON', 'tuesday', 'PM', '01:00:00', '05:00:00', 50, 1, 4, '2026-04-28 07:06:02', '2026-04-28 07:06:02'),
(25, 'MORNING', 'wednesday', 'AM', '08:00:00', '12:00:00', 50, 1, 2, '2026-03-31 04:24:25', '2026-03-31 04:24:25'),
(26, 'AFTERNOON', 'wednesday', 'PM', '01:00:00', '05:00:00', 50, 1, 1, '2026-03-31 04:24:25', '2026-03-31 04:24:25'),
(27, 'MORNING', 'thursday', 'AM', '08:00:00', '12:00:00', 50, 1, 7, '2026-05-07 16:13:23', '2026-05-07 16:13:23'),
(28, 'AFTERNOON', 'thursday', 'PM', '01:00:00', '05:00:00', 50, 1, 3, '2026-03-31 04:24:25', '2026-03-31 04:24:25'),
(29, 'MORNING', 'friday', 'AM', '08:00:00', '12:00:00', 50, 1, 1, '2026-05-08 06:18:46', '2026-05-08 06:18:46'),
(30, 'AFTERNOON', 'friday', 'PM', '01:00:00', '05:00:00', 50, 1, 2, '2026-05-01 05:04:27', '2026-05-01 05:04:27'),
(31, 'MORNING', 'monday', 'AM', '08:00:00', '12:00:00', 50, 1, 13, '2026-05-11 07:49:52', '2026-05-11 07:49:52'),
(32, 'AFTERNOON', 'monday', 'PM', '01:00:00', '05:00:00', 50, 1, 1, '2026-04-27 03:05:33', '2026-04-27 03:05:33'),
(33, 'MORNING', 'tuesday', 'AM', '08:00:00', '12:00:00', 50, 1, 9, '2026-04-28 07:53:30', '2026-04-28 07:53:30'),
(34, 'AFTERNOON', 'tuesday', 'PM', '01:00:00', '05:00:00', 50, 1, 4, '2026-04-28 07:06:02', '2026-04-28 07:06:02'),
(35, 'MORNING', 'wednesday', 'AM', '08:00:00', '12:00:00', 50, 1, 2, '2026-03-31 04:24:25', '2026-03-31 04:24:25'),
(36, 'AFTERNOON', 'wednesday', 'PM', '01:00:00', '05:00:00', 50, 1, 1, '2026-03-31 04:24:25', '2026-03-31 04:24:25'),
(37, 'MORNING', 'thursday', 'AM', '08:00:00', '12:00:00', 50, 1, 7, '2026-05-07 16:13:23', '2026-05-07 16:13:23'),
(38, 'AFTERNOON', 'thursday', 'PM', '01:00:00', '05:00:00', 50, 1, 3, '2026-03-31 04:24:25', '2026-03-31 04:24:25'),
(39, 'MORNING', 'friday', 'AM', '08:00:00', '12:00:00', 50, 1, 1, '2026-05-08 06:18:46', '2026-05-08 06:18:46'),
(40, 'AFTERNOON', 'friday', 'PM', '01:00:00', '05:00:00', 50, 1, 2, '2026-05-01 05:04:27', '2026-05-01 05:04:27'),
(41, 'MORNING', 'monday', 'AM', '08:00:00', '12:00:00', 50, 1, 13, '2026-05-11 07:49:52', '2026-05-11 07:49:52'),
(42, 'AFTERNOON', 'monday', 'PM', '01:00:00', '05:00:00', 50, 1, 1, '2026-04-27 03:05:33', '2026-04-27 03:05:33'),
(43, 'MORNING', 'tuesday', 'AM', '08:00:00', '12:00:00', 50, 1, 9, '2026-04-28 07:53:30', '2026-04-28 07:53:30'),
(44, 'AFTERNOON', 'tuesday', 'PM', '01:00:00', '05:00:00', 50, 1, 4, '2026-04-28 07:06:02', '2026-04-28 07:06:02'),
(45, 'MORNING', 'wednesday', 'AM', '08:00:00', '12:00:00', 50, 1, 2, '2026-03-31 04:24:25', '2026-03-31 04:24:25'),
(46, 'AFTERNOON', 'wednesday', 'PM', '01:00:00', '05:00:00', 50, 1, 1, '2026-03-31 04:24:25', '2026-03-31 04:24:25'),
(47, 'MORNING', 'thursday', 'AM', '08:00:00', '12:00:00', 50, 1, 7, '2026-05-07 16:13:23', '2026-05-07 16:13:23'),
(48, 'AFTERNOON', 'thursday', 'PM', '01:00:00', '05:00:00', 50, 1, 3, '2026-03-31 04:24:25', '2026-03-31 04:24:25'),
(49, 'MORNING', 'friday', 'AM', '08:00:00', '12:00:00', 50, 1, 1, '2026-05-08 06:18:46', '2026-05-08 06:18:46'),
(50, 'AFTERNOON', 'friday', 'PM', '01:00:00', '05:00:00', 50, 1, 2, '2026-05-01 05:04:27', '2026-05-01 05:04:27');

-- --------------------------------------------------------

--
-- Table structure for table `consent_forms`
--

CREATE TABLE `consent_forms` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `patient_name` varchar(255) NOT NULL,
  `patient_photo` varchar(255) NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `signature_data` text DEFAULT NULL,
  `form_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_of_birth` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consent_forms`
--

INSERT INTO `consent_forms` (`id`, `user_id`, `patient_name`, `patient_photo`, `service_type`, `signature_data`, `form_date`, `created_at`, `date_of_birth`, `age`, `sex`) VALUES
(12, 4, 'Rovic Dave Rosal', 'uploads/photo_1771381794_4.png', 'ABTC, Laboratory', 'uploads/signature_1771381794_4.png', '2026-02-18', '2026-02-18 02:29:54', '2003-02-06', 23, 'Male'),
(13, 5, 'Joemarie B. Gimarangan', 'uploads/photo_1771503350_5.png', 'Adult Consultation, Dental, Laboratory', 'uploads/signature_1771503350_5.png', '2026-02-19', '2026-02-19 12:15:50', '1975-12-01', 50, 'Male'),
(17, 8, 'Mark Kent M. Gimarangan', 'uploads/photo_1771985654_8.png', 'Dental, Laboratory', 'Signed with ballpen', '2026-02-25', '2026-02-25 02:14:14', '2003-06-23', 22, 'Male'),
(18, 9, 'Tricia Marie Villones', 'uploads/photo_1772001848_9.png', 'Adult Consultation', 'Signed with ballpen', '2026-02-25', '2026-02-25 06:44:08', '2001-11-22', 24, 'Female'),
(19, 8, 'Mark Kent M. Gimarangan', 'uploads/photo_1772002720_8.png', 'Dental', 'Signed with ballpen', '2026-02-25', '2026-02-25 06:58:40', '2003-06-23', 22, 'Male'),
(20, 8, 'Mark Kent M. Gimarangan', 'uploads/photo_1772002769_8.png', 'Dental', 'uploads/signature_1772002769_8.png', '2026-02-25', '2026-02-25 06:59:29', '2003-06-23', 22, 'Male'),
(21, 8, 'Mark Kent M. Gimarangan', 'uploads/photo_1772003656_8.png', 'Dental', 'Signed with ballpen', '2026-02-25', '2026-02-25 07:14:16', '0003-06-23', 2022, 'Male'),
(22, 10, 'Ralph Divinagracia', 'uploads/photo_1772163198_10.png', 'Dental', 'uploads/signature_1772163198_10.png', '2026-02-27', '2026-02-27 03:33:18', '2003-04-24', 22, 'Male'),
(23, 11, 'shane divinagracia', 'uploads/photo_1772165008_11.png', 'Dental', 'uploads/signature_1772165008_11.png', '2026-02-27', '2026-02-27 04:03:28', '2001-04-27', 24, 'Male'),
(24, 4, 'juan delacruz', 'uploads/photo_1772170167_4.png', 'Social Hygiene, X-Ray/Ultrasound, TB Section, Prenatal', 'Signed with ballpen', '2026-02-27', '2026-02-27 05:29:27', '2000-07-13', 25, 'Male'),
(25, 3, 'Mark Kent M. Gimarangan', 'uploads/photo_1773239924_3.png', 'Dental', 'Signed with ballpen', '2026-03-11', '2026-03-11 14:38:44', '2003-06-23', 22, 'Male'),
(26, 3, 'Rovic Dave Rosal', 'uploads/photo_1773240443_3.png', 'ABTC', 'Signed with ballpen', '2026-03-11', '2026-03-11 14:47:23', '2003-12-23', 22, 'Male'),
(27, 3, 'Mark Kent M. Gimarangan', 'uploads/photo_1774340107_3.png', 'Dental', 'Signed with ballpen', '2026-03-24', '2026-03-24 08:15:07', '2003-06-23', 22, 'Male'),
(28, 3, 'Coco Martin', 'uploads/photo_1774853587_3.png', 'Dental, Laboratory', 'Signed with ballpen', '2026-03-30', '2026-03-30 06:53:08', '2003-06-23', 22, 'Male'),
(29, 3, 'Rovic Dave Rosal', 'uploads/photo_1775531229_3.png', 'Dental, Social Hygiene, Laboratory', 'Signed with ballpen', '2026-04-07', '2026-04-07 03:07:09', '2004-02-06', 22, 'Male');

-- --------------------------------------------------------

--
-- Table structure for table `date_slot_overrides`
--

CREATE TABLE `date_slot_overrides` (
  `id` int(11) NOT NULL,
  `override_date` date NOT NULL,
  `am_capacity` int(11) NOT NULL DEFAULT 50,
  `pm_capacity` int(11) NOT NULL DEFAULT 50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `date_slot_overrides`
--

INSERT INTO `date_slot_overrides` (`id`, `override_date`, `am_capacity`, `pm_capacity`, `created_at`, `updated_at`) VALUES
(1, '2026-04-05', 25, 25, '2026-03-31 06:50:49', '2026-03-31 06:58:46'),
(2, '2026-04-01', 70, 50, '2026-03-31 06:52:43', '2026-03-31 06:52:43'),
(5, '2026-04-06', 25, 25, '2026-03-31 07:25:34', '2026-03-31 07:26:11'),
(7, '2026-04-07', 75, 75, '2026-04-07 02:49:27', '2026-04-07 02:49:27'),
(8, '2026-04-22', 40, 40, '2026-04-08 07:27:44', '2026-04-08 07:28:53'),
(9, '2026-04-13', 15, 15, '2026-04-13 06:10:58', '2026-04-13 06:10:58'),
(10, '2026-04-20', 30, 30, '2026-04-13 06:23:02', '2026-04-13 06:23:02');

-- --------------------------------------------------------

--
-- Table structure for table `dental_date_overrides`
--

CREATE TABLE `dental_date_overrides` (
  `id` int(11) NOT NULL,
  `override_date` date NOT NULL,
  `max_appointments` int(11) NOT NULL DEFAULT 20,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dental_slots`
--

CREATE TABLE `dental_slots` (
  `id` int(11) NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `max_appointments` int(11) NOT NULL DEFAULT 20,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dental_slots`
--

INSERT INTO `dental_slots` (`id`, `day_of_week`, `max_appointments`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'monday', 40, 1, '2026-05-11 05:10:30', '2026-05-11 05:33:35'),
(2, 'tuesday', 40, 1, '2026-05-11 05:10:30', '2026-05-11 05:33:35'),
(3, 'wednesday', 40, 1, '2026-05-11 05:10:30', '2026-05-11 05:33:35'),
(4, 'thursday', 40, 1, '2026-05-11 05:10:30', '2026-05-11 05:33:35'),
(5, 'friday', 40, 1, '2026-05-11 05:10:30', '2026-05-11 05:33:35'),
(6, 'saturday', 0, 1, '2026-05-11 05:10:30', '2026-05-11 05:10:30'),
(7, 'sunday', 0, 1, '2026-05-11 05:10:30', '2026-05-11 05:10:30');

-- --------------------------------------------------------

--
-- Table structure for table `patient_enrollment`
--

CREATE TABLE `patient_enrollment` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `suffix` varchar(50) DEFAULT NULL,
  `maiden_name` varchar(255) DEFAULT NULL,
  `age` int(11) NOT NULL,
  `sex` varchar(10) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `residential_address` text DEFAULT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `spouse_name` varchar(255) DEFAULT NULL,
  `educational_attainment` varchar(100) DEFAULT NULL,
  `employment_status` varchar(100) DEFAULT NULL,
  `dswd_nhts` varchar(10) DEFAULT 'No',
  `four_ps_member` varchar(10) DEFAULT 'No',
  `facility_household_no` varchar(50) DEFAULT NULL,
  `co_habitation` varchar(100) DEFAULT NULL,
  `household_no` varchar(50) DEFAULT NULL,
  `philhealth_member` varchar(10) DEFAULT 'No',
  `philhealth_status_type` varchar(50) DEFAULT NULL,
  `philhealth_no` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `primary_care_benefit` varchar(10) DEFAULT 'No',
  `family_member` text DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `mode_of_transaction` varchar(50) DEFAULT NULL,
  `date_of_consultation` date DEFAULT NULL,
  `consultation_time` varchar(10) DEFAULT NULL,
  `purpose_of_visit` text DEFAULT NULL,
  `temperature` varchar(20) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `blood_pressure` varchar(50) DEFAULT NULL,
  `height` varchar(20) DEFAULT NULL,
  `chief_complaints` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `medication_treatment` text DEFAULT NULL,
  `performed_laboratory_test` text DEFAULT NULL,
  `laboratory_findings` text DEFAULT NULL,
  `healthcare_provider_name` varchar(255) DEFAULT NULL,
  `patient_signature_date` date DEFAULT NULL,
  `chu_rhu_representative` varchar(255) DEFAULT NULL,
  `or_number` varchar(50) DEFAULT NULL,
  `or_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_enrollment`
--

INSERT INTO `patient_enrollment` (`id`, `user_id`, `last_name`, `first_name`, `middle_name`, `suffix`, `maiden_name`, `age`, `sex`, `birth_date`, `contact_number`, `residential_address`, `civil_status`, `spouse_name`, `educational_attainment`, `employment_status`, `dswd_nhts`, `four_ps_member`, `facility_household_no`, `co_habitation`, `household_no`, `philhealth_member`, `philhealth_status_type`, `philhealth_no`, `category`, `primary_care_benefit`, `family_member`, `mother_name`, `mode_of_transaction`, `date_of_consultation`, `consultation_time`, `purpose_of_visit`, `temperature`, `weight`, `blood_pressure`, `height`, `chief_complaints`, `diagnosis`, `medication_treatment`, `performed_laboratory_test`, `laboratory_findings`, `healthcare_provider_name`, `patient_signature_date`, `chu_rhu_representative`, `or_number`, `or_date`, `created_at`) VALUES
(1, 3, 'Gimarangan', 'Mark', 'Kent', '', '', 22, 'Male', '2003-06-23', '09398300291', 'Barangay 36, Bacolod City', 'Single', '', 'College', 'Others', 'No', 'No', '', '', '', 'Yes', 'Member', '', 'FE- Private', 'Yes', 'Son', 'Cecile Gimarangan', 'Walk-in', '2026-04-27', 'PM', 'Medical Consultation [Ref: CHO-2026-000048]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-27 03:05:33'),
(2, 3, 'rosal', 'rovic', 'Kent', '', '', 22, 'Male', '2003-10-22', '09159735489', 'barangay mansilingan bacolod city', 'Single', '', 'College', 'Retired', 'No', 'No', '', '', '', 'Yes', 'Member', '1546135356465', 'FE- Private', 'Yes', 'Son', '', 'Walk-in', '2026-04-28', 'AM', 'Medical Consultation, Adult/Pediatric, Postnatal Checkup [Ref: CHO-2026-000049]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-28 06:06:18'),
(3, 3, 'Antares', 'Tanoy', 'Mongi', '', '', 28, 'Male', '1997-08-02', '09097851234', 'Handumanan', 'Single', '', 'No Formal Education', 'Retired', 'No', 'No', '', '', '', 'Yes', 'Member', '1546135356465', 'FE- Private', 'Yes', '', '', 'Referral', '2026-04-28', 'PM', 'TB, Social Hygiene [Ref: CHO-2026-000050]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-28 07:06:02'),
(4, 3, 'Lashly', 'Maribel', 'Winner', '', 'Maribel Mayag Winner', 28, 'Female', '1997-10-02', '09398300291', 'Barangay 36, Bacolod City', 'Married', '', 'Vocational/Technical', 'Employed', 'No', 'No', '', '', '', 'Yes', 'Member', '1546135356465', 'FE-Private', 'Yes', '', 'Cecile Gimarangan', 'Visited', '2026-04-28', 'AM', 'Prenatal Checkup [Ref: CHO-2026-000051]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-28 07:53:30'),
(5, 3, 'GALASAO', 'MARIE ', 'WENGSHU', '', 'MARIE KUPRA WENGSHU', 34, 'Female', '1992-02-17', '09097457898', 'BARANGAY 40, BACOLOD CITY', 'Married', '', 'College', 'Employed', 'No', 'Yes', '', '', '', 'Yes', 'Dependent', '1546135356465', 'FE-Government', 'Yes', 'Mother', 'FENGWENG LAHOT WENGSHU', 'Walk-in', '2026-04-27', 'AM', 'Prenatal Checkup [Ref: CHO-2026-000052]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-01 03:03:43'),
(6, 3, 'Gallardo', 'Hervy', 'Buset', '', '', 24, 'Male', '2002-04-03', '0939006721', 'Barangay Bata, Bacolod City', 'Single', '', 'Elementary', 'Non-Employed', 'No', 'No', '', '', '', 'Yes', 'Member', '1546135356465', 'FE-Private', 'Yes', '', 'Momshie Gallardo', 'Walk-in', '2026-05-01', 'PM', 'Adult/Pediatric [Ref: CHO-2026-000053]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-01 03:21:49'),
(7, 3, 'Gimarangan', 'Mark', 'Kent', '', '', 22, '0', '2003-06-23', '09398300291', 'Barangay 36, Bacolod City', 'Single', '', 'College', 'Non-Employed', 'No', 'No', '', '', '', 'Yes', 'Member', '', 'FE-Government', 'Yes', '', 'Cecile Gimarangan', 'Visited', '2026-05-01', 'PM', 'Animal Bite [Ref: CHO-2026-000054]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-01 05:04:27'),
(8, 3, 'Gimarangan', 'Mark', 'Kent', '', '', 22, '0', '2003-06-23', '09398300291', 'Barangay 36, Bacolod City', 'Single', '', 'College', 'Employed', 'No', 'No', '', '', '', 'No', '', '', '', 'No', '', 'Cecile Gimarangan', 'Visited', '2026-05-07', 'AM', 'Medical Consultation [Ref: CHO-2026-000055]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-07 15:48:38'),
(9, 3, 'Gimarangan', 'Mark', 'Kent', '', '', 22, '0', '2003-06-23', '09398300291', 'Barangay 36, Bacolod City', 'Single', '', 'College', 'Employed', 'No', 'No', '', '', '', 'Yes', 'Member', '', 'FE-Government', 'Yes', 'Son', 'Cecile Gimarangan', 'Visited', '2026-05-07', 'AM', 'Medical Consultation [Ref: CHO-2026-000056]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-07 16:13:23'),
(10, 3, 'Gimarangan', 'Mark', 'Kent', '', '', 22, 'Male', '2003-06-23', '09398300291', 'Barangay 36, Bacolod City', 'Single', '', 'Elementary', 'Employed', 'Yes', 'Yes', '', '', '', 'Yes', 'Member', '12312312', 'IE', 'Yes', 'Son', 'Cecile Gimarangan', 'Walk-in', '2026-05-08', 'AM', 'Medical Consultation [Ref: CHO-2026-000057]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-07 16:42:05'),
(11, 3, 'rosal', 'mark', 'Kent', '', '', 17, 'Male', '2009-01-03', '09398300291', 'Barangay 36, Bacolod City', 'Single', '', 'Elementary', 'Non-Employed', 'Yes', 'Yes', '1234', '', '1234', 'Yes', 'Member', '23213124211', 'FE-Government', 'Yes', 'Son', 'Cecile Gimarangan', 'Walk-in', '2026-05-08', 'AM', 'Medical Consultation [Ref: CHO-2026-000058]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-08 06:08:54'),
(12, 3, 'Gimarangan', 'Mark', 'Kent', '', '', 13, 'Male', '2012-08-18', '09398300291', 'Barangay 36, Bacolod City', 'Married', '', 'Elementary', 'Employed', 'No', 'No', '', '', '', 'Yes', 'Member', '1546135356465', 'FE-Private', 'Yes', 'Son', '', 'Walk-in', '2026-05-11', 'AM', 'Animal Bite [Ref: CHO-2026-000059]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-10 16:22:24'),
(13, 3, 'ELADIO', 'WENG', 'PALUPOTAN', '', '', 16, 'Female', '2010-01-15', '09398300291', 'Barangay 36, Bacolod City', 'Single', '', 'Elementary', 'Non-Employed', 'Yes', 'Yes', '', '', '', 'Yes', 'Dependent', '12312312', 'FE-Private', 'Yes', 'Daughter', 'FENGWENG LAHOT WENGSHU', 'Walk-in', '2026-05-11', 'AM', 'Dental [Ref: CHO-2026-000060]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-10 16:44:57'),
(14, 3, 'Gimarangan', 'Mark', 'Kent', '', '', 19, 'Male', '2006-05-14', '09398300291', 'Barangay 36, Bacolod City', 'Single', '', '', 'Employed', 'No', 'No', '', '', '', 'No', '', '', '', 'No', '', '', 'Walk-in', '2026-05-11', 'AM', 'Medical Consultation [Ref: CHO-2026-000061]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 07:33:45'),
(15, 3, 'Gimarangan', 'Mark', 'Kent', '', '', 22, 'Male', '2003-06-13', '09398300291', 'Barangay 36, Bacolod City', 'Single', '', 'College', 'OFW', 'No', 'No', '', '', '', 'No', '', '', 'FE-Government', 'Yes', 'Son', '', 'Walk-in', '2026-05-11', 'AM', 'Medical Consultation, Dental [Ref: CHO-2026-000062]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 07:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `date_of_birth` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `contact_number`, `password`, `role`, `created_at`, `updated_at`, `date_of_birth`, `age`, `sex`) VALUES
(3, 'System Administrator', 'admin_bcho@gov.ph', '09123456789', '$2y$10$xG7903GtEiHvmcb54AT4WeHK.G4jQQKgEJtNLRkHdkeWRzqOQ.U0m', 'admin', '2026-02-13 03:26:37', '2026-02-13 03:31:07', NULL, NULL, NULL),
(4, 'Rovic Dave Rosal', 'rovicdaverosal@gmail.com', '09554095270', '$2y$10$qU9WgGpdH7V6ebxgs2HD6ev9yqxBw/P.K84b/WoG017/UlrMajI6C', 'user', '2026-02-13 05:49:41', '2026-02-16 05:53:49', NULL, NULL, NULL),
(5, 'Joemarie B. Gimarangan', 'joemarietvpdentalb@gmail.com', '09947549023', '$2y$10$jfzQSrVigvtr4.d8qaNLtOHCF8adfhBG8PIN4/IPZ4eHKJ/FVX8D2', 'user', '2026-02-19 12:13:33', '2026-02-19 12:13:33', NULL, NULL, NULL),
(7, 'Ralph Shane Divinagracia', 'ralphshane@gmail.com', '09086457878', '$2y$10$g9SFEs8TBCbDx/B1WSPmkOq5lHQDlPvkkQC5zGRIvYgfBY1/0rCEu', 'user', '2026-02-25 01:51:10', '2026-02-25 01:51:10', '2003-07-12', 22, 'Male'),
(8, 'Mark Kent M. Gimarangan', 'markkent21gimrangan@gmail.com', '09398300291', '$2y$10$PQTYRRzevetSiQSK7.1ssesp3q784LgurgjkH8UWe3R7ATZrS68Ni', 'user', '2026-02-25 02:13:01', '2026-02-25 02:13:01', '2003-06-23', 22, 'Male'),
(9, 'Tricia Marie Villones', 'tmvillones2001@gmail.com', '09516081228', '$2y$10$mMUEaReR34F5Q3xl24tRz.5jgeOTd1npyIHmx/gnM0/acCkfOm5ye', 'user', '2026-02-25 06:42:00', '2026-02-25 06:42:00', '2001-11-22', 24, 'Female'),
(10, 'Ralph Divinagracia', 'divinagraciaralphshane@gmail.com', '0948030254', '$2y$10$BnYsXpG1sTm2S2YT1BKsnu2i5Hp8ONztQZ.6R6N.//UxrBpq.1P.y', 'user', '2026-02-27 03:30:29', '2026-02-27 03:30:29', '2003-04-24', 22, 'Male'),
(11, 'shane divinagracia', 'shane@gmail.com', '09432455456', '$2y$10$DUT0jmRBz4bG7CS21X5bZux4ndLtTnO5tZT76wGztWnhzIipRjs9q', 'user', '2026-02-27 04:01:24', '2026-02-27 04:01:24', '2001-04-25', 24, 'Male');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_number` (`reference_number`),
  ADD KEY `idx_appointment_date` (`appointment_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_appointment_datetime` (`appointment_date`,`appointment_time`),
  ADD KEY `idx_appointment_search` (`appointment_date`,`time_period`,`status`),
  ADD KEY `idx_processed_by` (`processed_by`),
  ADD KEY `idx_notification_sent` (`notification_sent`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_arrived_status` (`arrived_status`),
  ADD KEY `idx_appointments_date_period` (`appointment_date`,`time_period`,`status`);

--
-- Indexes for table `appointment_am_pm_slots`
--
ALTER TABLE `appointment_am_pm_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_day_period` (`day_of_week`,`time_period`),
  ADD KEY `idx_day_of_week` (`day_of_week`),
  ADD KEY `idx_time_period` (`time_period`);

--
-- Indexes for table `appointment_time_slots`
--
ALTER TABLE `appointment_time_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_day_period` (`day_of_week`,`time_period`),
  ADD KEY `idx_appointment_time_slots_lookup` (`day_of_week`,`time_period`,`is_active`);

--
-- Indexes for table `consent_forms`
--
ALTER TABLE `consent_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `date_slot_overrides`
--
ALTER TABLE `date_slot_overrides`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`override_date`),
  ADD KEY `idx_date` (`override_date`);

--
-- Indexes for table `dental_date_overrides`
--
ALTER TABLE `dental_date_overrides`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`override_date`);

--
-- Indexes for table `dental_slots`
--
ALTER TABLE `dental_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_day` (`day_of_week`);

--
-- Indexes for table `patient_enrollment`
--
ALTER TABLE `patient_enrollment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `appointment_am_pm_slots`
--
ALTER TABLE `appointment_am_pm_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `appointment_time_slots`
--
ALTER TABLE `appointment_time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `consent_forms`
--
ALTER TABLE `consent_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `date_slot_overrides`
--
ALTER TABLE `date_slot_overrides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `dental_date_overrides`
--
ALTER TABLE `dental_date_overrides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dental_slots`
--
ALTER TABLE `dental_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `patient_enrollment`
--
ALTER TABLE `patient_enrollment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_appointments_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `consent_forms`
--
ALTER TABLE `consent_forms`
  ADD CONSTRAINT `consent_forms_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_enrollment`
--
ALTER TABLE `patient_enrollment`
  ADD CONSTRAINT `patient_enrollment_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
