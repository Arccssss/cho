-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 12:49 PM
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
  `reasons_of_visit` text DEFAULT NULL,
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
-- Indexes for table `patient_enrollment`
--
ALTER TABLE `patient_enrollment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for table `patient_enrollment`
--
ALTER TABLE `patient_enrollment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for table `patient_enrollment`
--
ALTER TABLE `patient_enrollment`
  ADD CONSTRAINT `patient_enrollment_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;
