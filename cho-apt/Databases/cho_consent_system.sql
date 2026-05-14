-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 04, 2026 at 03:19 PM
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
(24, 4, 'juan delacruz', 'uploads/photo_1772170167_4.png', 'Social Hygiene, X-Ray/Ultrasound, TB Section, Prenatal', 'Signed with ballpen', '2026-02-27', '2026-02-27 05:29:27', '2000-07-13', 25, 'Male');

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
-- Indexes for table `consent_forms`
--
ALTER TABLE `consent_forms`
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
-- AUTO_INCREMENT for table `consent_forms`
--
ALTER TABLE `consent_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `consent_forms`
--
ALTER TABLE `consent_forms`
  ADD CONSTRAINT `consent_forms_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
