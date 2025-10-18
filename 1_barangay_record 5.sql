-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 18, 2025 at 09:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `1_barangay_record`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `posted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `posted_by`, `posted_at`) VALUES
(1, 'Barangay Meeting', 'There will be a general assembly on Oct 25, 2025.', 1, '2025-10-17 19:17:02'),
(2, 'Cleanup Drive', 'Join the barangay cleanup activity on Oct 28, 2025.', 1, '2025-10-17 19:17:02');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_desc` text NOT NULL,
  `target_table` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `user_id`, `action_type`, `action_desc`, `target_table`, `target_id`, `created_at`) VALUES
(1, 1, 'Create', 'Added new resident ID 8', 'residents', 8, '2025-10-17 22:29:53'),
(2, 1, 'Create', 'Created user ID 7 for resident ID 8', 'users', 7, '2025-10-17 22:29:53'),
(3, 1, 'Create', 'Added new resident ID 9', 'residents', 9, '2025-10-17 22:30:57'),
(4, 1, 'Create', 'Created user ID 8 for resident ID 9', 'users', 8, '2025-10-17 22:30:57'),
(5, 1, 'Create', 'Added new resident ID 10', 'residents', 10, '2025-10-17 22:32:34'),
(6, 1, 'Update', 'Updated resident ID 10', 'residents', 10, '2025-10-17 22:35:47'),
(7, 1, 'Update', 'Updated resident ID 10', 'residents', 10, '2025-10-17 22:36:22'),
(8, 1, 'Create', 'Added cedula ID 4 for resident ID 1', 'cedula_records', 4, '2025-10-17 22:39:34'),
(9, 1, 'Update', 'Updated user for resident ID 8', 'users', 8, '2025-10-17 22:40:55'),
(10, 1, 'Update', 'Updated user for resident ID 8', 'users', 8, '2025-10-17 22:41:07'),
(11, 1, 'Create', 'Created user for resident ID 3', 'users', 9, '2025-10-17 22:41:27'),
(12, 1, 'Update', 'Updated user for resident ID 5', 'users', 4, '2025-10-17 22:43:26'),
(13, 1, 'Update', 'Updated user for resident ID 9', 'users', 8, '2025-10-17 22:43:56'),
(14, 1, 'Create', 'Created user for resident ID 10', 'users', 10, '2025-10-17 22:44:27'),
(15, 1, 'Create', 'Added household ID 5', 'households', 5, '2025-10-17 22:51:12'),
(16, 1, 'Create', 'Added new resident ID 11', 'residents', 11, '2025-10-17 23:18:32'),
(17, 1, 'Update', 'Updated resident ID 11', 'residents', 11, '2025-10-17 23:25:01'),
(18, 1, 'Create', 'Added cedula ID 5 for resident ID 4', 'cedula_records', 5, '2025-10-17 23:37:44'),
(19, 1, 'Create', 'Created user for resident ID 2', 'users', 11, '2025-10-17 23:44:21'),
(20, 1, 'Create', 'Added cedula ID 6 for resident ID 8', 'cedula_records', 6, '2025-10-17 23:47:51'),
(21, 1, 'Create', 'Added new resident ID 12', 'residents', 12, '2025-10-18 00:03:42'),
(22, 1, 'Update', 'Updated resident ID 12', 'residents', 12, '2025-10-18 00:03:56'),
(23, 1, 'Create', 'Added cedula ID 7 for resident ID 11', 'cedula_records', 7, '2025-10-18 00:05:01'),
(24, 1, 'Create', 'Created user for resident ID 12', 'users', 12, '2025-10-18 01:20:47'),
(25, 1, 'Update', 'Updated user for resident ID 5', 'users', 4, '2025-10-18 01:20:56'),
(26, 1, 'Update', 'Updated user for resident ID 5', 'users', 4, '2025-10-18 01:21:09'),
(27, 1, 'Create', 'Added new resident ID 13', 'residents', 13, '2025-10-18 05:47:24'),
(28, 1, 'Create', 'Added household ID 6', 'households', 6, '2025-10-18 06:02:21'),
(29, 1, 'Create', 'Created user for resident ID 13', 'users', 13, '2025-10-18 06:11:17');

-- --------------------------------------------------------

--
-- Table structure for table `cedula_records`
--

CREATE TABLE `cedula_records` (
  `cedula_id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `cedula_no` varchar(50) DEFAULT NULL,
  `issue_date` date DEFAULT curdate(),
  `amount` decimal(10,2) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cedula_records`
--

INSERT INTO `cedula_records` (`cedula_id`, `resident_id`, `issued_by`, `cedula_no`, `issue_date`, `amount`, `request_id`) VALUES
(1, 1, 1, 'CED-001', '2025-01-10', 50.00, NULL),
(2, 2, 1, 'CED-002', '2025-01-11', 50.00, NULL),
(3, 4, 1, 'CED-4190', '2025-10-17', 250.00, NULL),
(4, 1, 1, 'CED-3986', '2025-10-18', 25.00, NULL),
(5, 4, 1, 'CED-9884', '2025-10-18', 125.00, NULL),
(6, 8, 1, 'CED-4753', '2025-10-18', 75.00, NULL),
(7, 11, 1, 'CED-8561', '2025-10-18', 25.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `households`
--

CREATE TABLE `households` (
  `household_id` int(11) NOT NULL,
  `household_no` varchar(50) NOT NULL,
  `head_id` int(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `total_members` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `households`
--

INSERT INTO `households` (`household_id`, `household_no`, `head_id`, `address`, `total_members`, `created_at`) VALUES
(3, '09616142091', 4, 'Zone 8 , Zayas Carmen', 1, '2025-10-17 20:56:11'),
(4, '09979925115', 3, 'Zone 6 , Balulang', 3, '2025-10-17 20:57:10'),
(5, '09098752194', 5, 'Zone 9 , Purok 4 Upper Balulang', 3, '2025-10-17 22:51:12'),
(6, '09616242124', 11, 'Zone 4 , Upper Carmen', 3, '2025-10-18 06:02:21');

-- --------------------------------------------------------

--
-- Table structure for table `household_members`
--

CREATE TABLE `household_members` (
  `member_id` int(11) NOT NULL,
  `household_id` int(11) DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `relationship` enum('Head','Spouse','Child','Sibling','Parent','Other') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `household_members`
--

INSERT INTO `household_members` (`member_id`, `household_id`, `resident_id`, `relationship`) VALUES
(5, 3, 4, 'Head'),
(6, 4, 3, 'Head'),
(7, 4, 1, 'Other'),
(8, 4, 2, 'Other'),
(9, 5, 5, 'Head'),
(10, 5, 8, 'Other'),
(11, 5, 9, 'Other'),
(12, 6, 11, 'Head'),
(13, 6, 12, 'Other'),
(14, 6, 10, 'Other');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `request_id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `request_type` enum('Cedula','Barangay ID','Certificate of Residency','Other') DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `purpose` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `resident_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`resident_id`, `first_name`, `middle_name`, `last_name`, `birthdate`, `gender`, `contact_no`, `address`, `civil_status`, `created_at`) VALUES
(1, 'Juan', 'Santos', 'Dela Cruz', '1990-01-05', 'Male', '09123456789', 'Zone 10, Carmen', 'Single', '2025-10-17 19:17:02'),
(2, 'Maria', 'Lopez', 'Reyes', '1992-03-14', 'Female', '09198765432', 'Zone 10, Carmen', 'Married', '2025-10-17 19:17:02'),
(3, 'Carlos', 'Gomez', 'Torres', '1988-05-20', 'Male', '09155667788', 'Zone 10, Carmen', 'Married', '2025-10-17 19:17:02'),
(4, 'Anna', 'Ramos', 'Cruz', '2000-09-10', 'Female', '09223344556', 'Zone 10, Carmen', 'Single', '2025-10-17 19:17:02'),
(5, 'dsfadsfa', 'sdafsad', 'sadfdsafdsa', '2001-05-22', 'Male', '09616142091', 'Zone 10 , Upper Carmen', 'Single', '2025-10-17 22:23:55'),
(8, 'dfsa', 'sfda', 'dfas', '2234-06-26', 'Female', '09616142091', 'Zone 8 , Zayas', 'Single', '2025-10-17 22:29:53'),
(9, 'gsagsad', 'sdafads', 'sdafdsafsda', '2311-02-03', 'Male', '142143214321', 'Zone 9 , Bulua', 'Single', '2025-10-17 22:30:57'),
(10, 'Joseph', 'dsafdsafdsa', 'Tongco', '2003-05-25', 'Male', '09616142091', 'Zone 8 , Lower Dagong', 'Married', '2025-10-17 22:32:34'),
(11, 'Erl Perseus II', 'Gomez', 'Latras', '2003-06-29', 'Male', '09616142091', 'Zone 10 , Upper Carmen Basketball Court', 'Single', '2025-10-17 23:18:32'),
(12, 'Eric', 'Perales', 'Latras', '1952-05-25', 'Male', '09616142091', 'Zone 7 , Upper Lower', 'Single', '2025-10-18 00:03:42'),
(13, 'kenneth', '', 'otero', '2512-06-25', 'Other', '06961421412', 'Zone 4 , balulang', 'Single', '2025-10-18 05:47:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Resident') NOT NULL DEFAULT 'Resident',
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `resident_id`, `username`, `email`, `password`, `role`, `profile_image`, `created_at`) VALUES
(1, 11, 'admin', 'erllatras@gmail.com', 'admin', 'Admin', 'uploads/profile_images/1760765368_kennethotero.png', '2025-10-17 19:17:02'),
(2, 1, 'juan_dcruz', 'juandcrus@gmail.com', '2a2b8801afe2d9e5f47c5b786d8d349ce3d0b46f94c84bd5608abe1c2c75bb84', 'Resident', NULL, '2025-10-17 19:17:02'),
(3, 4, 'anna_cruz', 'annacruz@gmail.com', '$2y$10$hl7RsoRJeV507cFvRrKWr.g.UDTDYs8NjiybLVvpy.i/P6FDrMeDC', 'Resident', NULL, '2025-10-17 22:00:14'),
(4, 5, 'dsfadsfa_sadfdsafdsa', 'dsafdsafadsfdsa@hotmail.com', '$2y$10$A2ymGT9hkkDgjM1b6cLUf.U4O9XDZ5A4StMZQhSk1M5X3w08fJxj2', 'Resident', NULL, '2025-10-17 22:23:55'),
(7, 8, 'dfsa_dfas', 'erlbazz123@yaofdsaho.com', '$2y$10$ZEGiqH8rSnW.E4RCM5mwHOiiQmiQW6WU64t8LSesu.6Fw6tJPfk2W', 'Resident', NULL, '2025-10-17 22:29:53'),
(8, 9, 'gsagsad_sdafdsafsda', 'dsafdsa143214123@hotmail.com', '$2y$10$QU0Yg9wgw6iEMNoWOBEl8.MlmlCjtlmIFECIeiPu3fryhtJz5d5Ae', 'Resident', NULL, '2025-10-17 22:30:57'),
(9, 3, 'random12313', 'erlbazz123@yahoo.com', '$2y$10$9bz.m0BJfMriLDY/XDQrmeyfIHCpg/rYnjxuNaiMF2yvJIWago4au', 'Resident', NULL, '2025-10-17 22:41:27'),
(10, 10, 'tongco', 'tongco@gmail.com', '$2y$10$8ro2Im5bgyb1/MY7Z.jnfOuVMgCyl04EKcQOv67U92hz.luY9H2Ly', 'Resident', NULL, '2025-10-17 22:44:27'),
(11, 2, 'sadsadfasdfdsa', '231512sdafsa@gmail.com', '$2y$10$cg.s5BYPgTBfZkB1yiANFOLz17.pO.sgMsvELyBUKt3w8lfHh3svq', 'Resident', NULL, '2025-10-17 23:44:21'),
(12, 12, 'Otero', 'otero123@gmail.com', '$2y$10$V//CU31XsNZFfYBgesm93.CPH8JeaCIk8xEHA1qfQ3vCIugNx8R.2', 'Resident', NULL, '2025-10-18 01:20:47'),
(13, 13, 'kenneth', 'kenneth@gmail.com', '$2y$10$1Xq9hc0XWc3jSaSVr4S/1u77InPDUGpfPZB4q/tJRGBkhtotLhfci', 'Resident', 'uploads/profile_images/profile_13_1760769364.png', '2025-10-18 06:11:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cedula_records`
--
ALTER TABLE `cedula_records`
  ADD PRIMARY KEY (`cedula_id`),
  ADD UNIQUE KEY `cedula_no` (`cedula_no`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `issued_by` (`issued_by`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `households`
--
ALTER TABLE `households`
  ADD PRIMARY KEY (`household_id`),
  ADD UNIQUE KEY `household_no` (`household_no`),
  ADD KEY `head_id` (`head_id`);

--
-- Indexes for table `household_members`
--
ALTER TABLE `household_members`
  ADD PRIMARY KEY (`member_id`),
  ADD KEY `household_id` (`household_id`),
  ADD KEY `resident_id` (`resident_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `resident_id` (`resident_id`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`resident_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `resident_id` (`resident_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `cedula_records`
--
ALTER TABLE `cedula_records`
  MODIFY `cedula_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `households`
--
ALTER TABLE `households`
  MODIFY `household_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `household_members`
--
ALTER TABLE `household_members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `resident_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `cedula_records`
--
ALTER TABLE `cedula_records`
  ADD CONSTRAINT `cedula_records_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cedula_records_ibfk_2` FOREIGN KEY (`issued_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `cedula_records_ibfk_3` FOREIGN KEY (`request_id`) REFERENCES `requests` (`request_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `households`
--
ALTER TABLE `households`
  ADD CONSTRAINT `households_ibfk_1` FOREIGN KEY (`head_id`) REFERENCES `residents` (`resident_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `household_members`
--
ALTER TABLE `household_members`
  ADD CONSTRAINT `household_members_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `household_members_ibfk_2` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
