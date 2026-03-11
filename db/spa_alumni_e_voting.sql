-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 11, 2026 at 11:45 AM
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
-- Database: `spa_alumni_e_voting`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`) VALUES
(140, 13, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-08 18:11:15'),
(141, 13, 'MFA Verification Successful', '::1', '2026-03-08 18:11:52'),
(142, 13, 'User logged out on $current_date', '::1', '2026-03-08 18:24:59'),
(143, 3, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-08 18:25:06'),
(144, 3, 'MFA Verification Successful', '::1', '2026-03-08 18:25:23'),
(145, 3, 'User logged out on $current_date', '::1', '2026-03-08 18:30:53'),
(146, 13, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-08 18:31:01'),
(147, 3, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-08 18:31:46'),
(148, 3, 'MFA Verification Successful', '::1', '2026-03-08 18:32:55'),
(149, 3, 'User logged out on $current_date', '::1', '2026-03-08 18:54:45'),
(150, 13, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-08 18:54:52'),
(151, 13, 'MFA Verification Successful', '::1', '2026-03-08 18:55:57'),
(152, 13, 'System auto-logout due to inactivity', '::1', '2026-03-08 19:25:26'),
(153, 13, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-08 19:25:32'),
(154, 13, 'MFA Verification Successful', '::1', '2026-03-08 19:26:34'),
(155, 13, 'Password updated', '::1', '2026-03-08 19:30:47'),
(156, 13, 'System auto-logout due to inactivity', '::1', '2026-03-09 08:26:16'),
(157, 13, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-09 08:26:32'),
(158, 13, 'MFA Verification Successful', '::1', '2026-03-09 08:30:38'),
(159, 13, 'System auto-logout due to inactivity', '::1', '2026-03-09 10:28:11'),
(160, 13, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-09 10:28:21'),
(161, 13, 'MFA Verification Successful', '::1', '2026-03-09 10:28:39'),
(162, 13, 'System auto-logout due to inactivity', '::1', '2026-03-09 12:38:51'),
(163, 13, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-09 12:38:56'),
(164, 13, 'MFA Verification Successful', '::1', '2026-03-09 12:40:06'),
(165, 13, 'voted successfully', '::1', '2026-03-09 12:41:07'),
(166, 13, 'voted successfully', '::1', '2026-03-09 12:46:41'),
(167, 13, 'voted successfully', '::1', '2026-03-09 12:52:02'),
(168, 13, 'voted successfully in election #78880159', '::1', '2026-03-09 12:57:57'),
(169, 13, 'voted successfully in election #78880159', '::1', '2026-03-09 13:16:02'),
(170, 13, 'System auto-logout due to inactivity', '::1', '2026-03-10 08:25:03'),
(171, 13, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-10 08:25:07'),
(172, 13, 'MFA Verification Successful', '::1', '2026-03-10 08:25:34'),
(173, 13, 'User logged out on $current_date', '::1', '2026-03-10 08:40:21'),
(174, 3, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-10 08:40:26'),
(175, 3, 'MFA Verification Successful', '::1', '2026-03-10 08:40:37'),
(176, 3, 'Status Update: Toggled status for User ID: 13', '::1', '2026-03-10 08:41:32'),
(177, 3, 'Status Update: Toggled status for User ID: 13', '::1', '2026-03-10 08:41:36'),
(178, 3, 'User logged out on $current_date', '::1', '2026-03-10 09:41:06'),
(179, 13, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-10 09:45:14'),
(180, 13, 'MFA Verification Successful', '::1', '2026-03-10 09:45:36'),
(181, 13, 'voted successfully in election #78880159', '::1', '2026-03-10 09:51:40'),
(182, 13, 'voted successfully in election #78880159', '::1', '2026-03-10 10:00:12'),
(183, 13, 'User logged out on $current_date', '::1', '2026-03-10 10:11:57'),
(184, 3, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-10 10:12:04'),
(185, 3, 'MFA Verification Successful', '::1', '2026-03-10 10:12:21'),
(186, 3, 'System auto-logout due to inactivity', '::1', '2026-03-10 12:59:12'),
(187, 3, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-10 13:50:46'),
(188, 13, 'Successful Login Attempt (Awaiting OTP)', '::1', '2026-03-10 13:51:10'),
(189, 13, 'MFA Verification Successful', '::1', '2026-03-10 13:53:34');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `election_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `manifesto` text DEFAULT NULL,
  `photo` text DEFAULT 'uploadImage/Profile/default.png',
  `status` enum('approved','pending','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`id`, `election_id`, `position_id`, `user_id`, `manifesto`, `photo`, `status`, `created_at`) VALUES
(11, 78880159, 5, 5, 'ccc', 'uploadImage/Profile/CAND_UPD_1772594909.jpeg', 'approved', '2026-03-04 03:27:17'),
(17, 78880159, 5, 10, NULL, 'uploadImage/Profile/default.png', 'approved', '2026-03-09 08:32:41'),
(18, 78880159, 6, 11, '', 'uploadImage/Profile/CAND_UPD_1773132187.jpg', 'approved', '2026-03-09 08:32:41'),
(19, 78880159, 6, 9, 'fsddsdsd', 'uploadImage/Profile/IMG_6_1772904039.jpg', 'approved', '2026-03-09 08:34:55');

-- --------------------------------------------------------

--
-- Table structure for table `elections`
--

CREATE TABLE `elections` (
  `id` int(8) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `status` enum('scheduled','active','closed') DEFAULT 'scheduled',
  `allow_result_view` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elections`
--

INSERT INTO `elections` (`id`, `title`, `description`, `start_datetime`, `end_datetime`, `status`, `allow_result_view`, `created_by`, `created_at`) VALUES
(78880159, 'SPA Alumni 2001 set Election 2019', '', '2026-03-03 00:22:00', '2026-03-18 00:22:00', 'active', 1, 3, '2026-03-03 23:22:43');

--
-- Triggers `elections`
--
DELIMITER $$
CREATE TRIGGER `before_insert_elections` BEFORE INSERT ON `elections` FOR EACH ROW BEGIN
    -- Only generate ID if it is not provided in the insert statement
    IF NEW.id IS NULL OR NEW.id = '' THEN
        SET @new_id = '';
        SET @chars = '0123456789';
        
        -- Loop to generate 8 random characters
        WHILE CHAR_LENGTH(@new_id) < 8 DO
            SET @new_id = CONCAT(@new_id, SUBSTRING(@chars, FLOOR(1 + RAND() * 62), 1));
        END WHILE;
        
        SET NEW.id = @new_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `failed_login`
--

CREATE TABLE `failed_login` (
  `id` int(4) NOT NULL,
  `user_id` int(4) NOT NULL,
  `ip_address` varchar(245) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

CREATE TABLE `otps` (
  `id` int(4) NOT NULL,
  `user_id` int(4) NOT NULL,
  `code` varchar(5) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otps`
--

INSERT INTO `otps` (`id`, `user_id`, `code`, `created_at`) VALUES
(43, 3, '94208', '2026-03-10 13:50:46');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `election_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `max_vote` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `election_id`, `title`, `max_vote`, `created_at`) VALUES
(5, 78880159, 'President', 45, '2026-03-03 23:23:33'),
(6, 78880159, 'PRO', 45, '2026-03-03 23:23:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `nickname` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` text NOT NULL,
  `role` enum('eleco','voter','candidate') DEFAULT 'voter',
  `is_verified` tinyint(1) DEFAULT 0,
  `financial_status` enum('active','non-active') NOT NULL DEFAULT 'non-active',
  `has_voted` tinyint(1) DEFAULT 0,
  `status` enum('active','suspended') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `user_image` varchar(555) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `nickname`, `email`, `phone`, `password`, `role`, `is_verified`, `financial_status`, `has_voted`, `status`, `last_login`, `user_image`, `created_at`) VALUES
(3, 'Ndueso Okorie', 'Escobar', 'newleastpaysolution@gmail.com', '08067361023', '$2y$10$n0BXfplbyD/dfPRY2JFHq.cPTVkLz2bW10Jq0wyJWfzH1yuR7f7NC', 'eleco', 1, 'non-active', 0, 'active', '2026-03-10 14:50:46', '', '2026-03-03 09:35:45'),
(5, 'Oto-Obong Idiong', 'Adiaha response', 'nduesowalter@gmail.coms', '08067361026', '$2y$10$ZjBkwWd5gtdbCJ5XNinh4.s2vXBBUVxi.9ln0KtkLz.ttrh8FypHi', 'candidate', 1, 'active', 0, 'active', NULL, '', '2026-03-03 22:35:29'),
(9, 'Moses Ibanga', 'Musa', 'moses_ibanga@gmail.com', '091', '$2y$10$IXlgK9LRxsBx7k049TpHBel/B5zQOwRcB3aKV.NH0Lue/qapb4MhO', 'candidate', 1, 'active', 0, 'active', NULL, '', '2026-03-04 04:44:51'),
(10, 'Akwa-Ima Idiong', 'Jerry-boy', 'akwa@gmail.com', '08067361026', '$2y$10$D3yN3dz4/UdBgUcLwDmCq.VCQnS/DSoY4hDMjfEu5rh6CPgz8B3DW', 'candidate', 1, 'non-active', 0, 'active', NULL, '', '2026-03-04 08:09:03'),
(11, 'Mmakamba Okorie', 'Mma', 'nduesowalter@gmail.com', '0806736107722', '$2y$10$Feca4KRWgz91umJGwawm3u7agpblG.PwPk3mzrlBRwcIWTBrHU2aC', 'candidate', 1, 'non-active', 0, 'active', NULL, '', '2026-03-04 20:05:00'),
(13, 'Stella Ndu', 'steco', 'newleastpaysolution@yahoo.com', '08067361077', '$2y$10$ZNmCDgHRlGaXV9D82uyZueBCMNMndVdv0LUvqD/n45lWSo49BaUyK', 'candidate', 1, 'active', 1, 'active', '2026-03-10 14:51:10', 'uploadImage/Profile/voter_1772992743_69adb8e7ea45e.png', '2026-03-08 17:59:04');

-- --------------------------------------------------------

--
-- Table structure for table `voter_sessions`
--

CREATE TABLE `voter_sessions` (
  `id` int(4) NOT NULL,
  `user_id` int(4) NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `device_name` text NOT NULL,
  `session_token` text NOT NULL,
  `login_time` datetime NOT NULL,
  `logout_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voter_sessions`
--

INSERT INTO `voter_sessions` (`id`, `user_id`, `ip_address`, `device_name`, `session_token`, `login_time`, `logout_time`, `created_at`) VALUES
(53, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'b5557c7ad724691beb3ac06515807bc458684575152932a12a29a15d18293c48', '2026-03-08 19:11:15', '2026-03-08 19:24:59', '2026-03-08 18:24:59'),
(54, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '84bc23e2d551ccb442607ea2392992a8f4d4de83bf5a3199688c45453fc4b8a1', '2026-03-08 19:25:06', '2026-03-08 19:30:53', '2026-03-08 18:30:53'),
(55, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '80c4d1f87528b732f2cd2ba4579fabe81906f76389ed80ec9097f5aaabf6ed13', '2026-03-08 19:31:01', '0000-00-00 00:00:00', '2026-03-08 18:31:01'),
(56, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '1aade602a02fd849a646b539bd1a28467fdc2b4f91cc173788cc71dde35fbde0', '2026-03-08 19:31:46', '2026-03-08 19:54:45', '2026-03-08 18:54:45'),
(57, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '6417e7f13a4425f1e6b858561b5a7994f7ae2dd52ac1ecfb55abc1133dc72be1', '2026-03-08 19:54:52', '2026-03-08 20:25:26', '2026-03-08 19:25:26'),
(58, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '5a3c8e13bb44bd16635626165cd40baf1dd6fb7cb9c06ad1ef0f86ecf9639263', '2026-03-08 20:25:32', '2026-03-09 09:26:16', '2026-03-09 08:26:16'),
(59, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'e4aee68454a16b601b680840372c782a7184d0f1b05937517b6d4be06c01d4b3', '2026-03-09 09:26:32', '2026-03-09 11:28:11', '2026-03-09 10:28:11'),
(60, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2e2c4f681c17f3daf8d21a3d71bb363b58c76923c35840733ecd2bf751404330', '2026-03-09 11:28:21', '2026-03-09 13:38:51', '2026-03-09 12:38:51'),
(61, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '796df63e094307e3d8ea691765c1082766e5b75b5f5e4debd38eca993d7eee1f', '2026-03-09 13:38:56', '2026-03-10 09:25:03', '2026-03-10 08:25:03'),
(62, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '39f48bc18893c9c2bf79d24425daaef45b847d10bfe46dbc6b9a86e5a4c6b50c', '2026-03-10 09:25:07', '2026-03-10 09:40:21', '2026-03-10 08:40:21'),
(63, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'c9eda86278329b37e95715c77f1e2f7bbc25cd55729e5b7bdb8afa81a00b33dc', '2026-03-10 09:40:26', '2026-03-10 10:41:06', '2026-03-10 09:41:06'),
(64, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'be775945867f0f378ba5794118967d291d38f01dc06d3900b1f8f28088353048', '2026-03-10 10:45:14', '2026-03-10 11:11:57', '2026-03-10 10:11:57'),
(65, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'bd304565634a4de235df0d12ee893185796eafcf6f404d3da9b399ffb5d2d56e', '2026-03-10 11:12:04', '2026-03-10 13:59:12', '2026-03-10 12:59:12'),
(66, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'e7c3a8bd6ccd9005c6e083b869a70cc153bcd6fc3632535797c5b347c237533a', '2026-03-10 14:50:46', '0000-00-00 00:00:00', '2026-03-10 13:50:46'),
(67, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '4a63481f73c5ba734c4c99d0b634a727ee8164bb3c72cd7aea9d1b7471a925d0', '2026-03-10 14:51:10', '0000-00-00 00:00:00', '2026-03-10 13:51:10');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `id` int(4) NOT NULL,
  `election_id` int(4) NOT NULL,
  `position_id` int(4) NOT NULL,
  `candidate_id` text NOT NULL,
  `voted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `voter_ip` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `votes`
--

INSERT INTO `votes` (`id`, `election_id`, `position_id`, `candidate_id`, `voted_at`, `voter_ip`) VALUES
(21, 78880159, 5, 'YQJgZoO8Geg2zbGO3Fswhg==', '2026-03-10 10:00:09', '::1'),
(22, 78880159, 6, 'VsiqPjyhvyJTQMpVp5/E2A==', '2026-03-10 10:00:09', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `website_settings`
--

CREATE TABLE `website_settings` (
  `id` int(11) NOT NULL,
  `site_name` varchar(150) NOT NULL,
  `site_email` varchar(150) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `allow_registration` tinyint(1) DEFAULT 0,
  `maintenance_mode` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_settings`
--

INSERT INTO `website_settings` (`id`, `site_name`, `site_email`, `logo`, `allow_registration`, `maintenance_mode`, `created_at`, `updated_at`) VALUES
(1, 'St Paul Academy Alumni 2001 set', 'newleastpaysolution@gmail.com', 'uploadImage/Logo/logo_1772615037.png', 1, 1, '2026-03-02 10:41:07', '2026-03-04 19:35:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `election_id` (`election_id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `elections`
--
ALTER TABLE `elections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_login`
--
ALTER TABLE `failed_login`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `otps`
--
ALTER TABLE `otps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `election_id` (`election_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `voter_sessions`
--
ALTER TABLE `voter_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `website_settings`
--
ALTER TABLE `website_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `failed_login`
--
ALTER TABLE `failed_login`
  MODIFY `id` int(4) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otps`
--
ALTER TABLE `otps`
  MODIFY `id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `voter_sessions`
--
ALTER TABLE `voter_sessions`
  MODIFY `id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `website_settings`
--
ALTER TABLE `website_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `candidates_ibfk_2` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `candidates_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
