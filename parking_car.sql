-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 29, 2025 at 09:59 AM
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
-- Database: `parking_car`
--

-- --------------------------------------------------------

--
-- Table structure for table `deletion_log`
--

CREATE TABLE `deletion_log` (
  `id` int(11) NOT NULL,
  `vehicle_id` varchar(36) DEFAULT NULL,
  `ref_code` varchar(20) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deletion_log`
--

INSERT INTO `deletion_log` (`id`, `vehicle_id`, `ref_code`, `deleted_at`) VALUES
(2, '23f76c6a-c605-4b2b-bc67-bb76da41a045', 'REF-3E62E27B', '2025-10-19 07:34:46'),
(3, '9afed84c-cd61-4440-8486-cd5972d5bd8f', 'REF-D1F79A7C', '2025-10-19 07:34:53'),
(4, 'db50096c-d9a4-4c38-a453-a8be19c7183f', 'REF-3D480F6B', '2025-10-19 07:35:30'),
(5, '94f07176-00f2-4c00-82e9-d3f035c03552', 'REF-02ACED91', '2025-10-19 07:41:45'),
(6, '56b33416-b4fe-4eb5-a53e-a2b2e06acd7d', 'REF-198A48C4', '2025-10-19 07:47:04'),
(7, '46a5202b-912e-4687-96da-b19086efaf15', 'REF-BEF986BB', '2025-10-19 07:48:38'),
(8, 'cf02b228-43c9-4ba0-934f-50d259d54088', 'REF-64421CAB', '2025-10-19 07:56:48'),
(9, '5d302025-ca7c-49eb-b3ae-9f3e23be1be5', 'REF-A3FD2EE9', '2025-10-19 07:59:13'),
(10, 'e145e540-50a6-4a8b-aea7-4101032f3675', 'REF-B3413F7B', '2025-10-19 09:01:06'),
(11, '4528f442-6ff1-45ee-802f-127b6dc85865', 'REF-90C3FF67', '2025-10-19 09:02:23'),
(12, '510385e4-d417-4146-8f9a-58e33315d84b', 'REF-96B55F73', '2025-10-19 09:07:49'),
(13, '621ff9f3-bcdc-4949-9fe4-89ed0628cf52', 'REF-A8095504', '2025-10-19 09:08:30'),
(14, '88bc1325-4afc-46c4-b680-3b294edfa785', 'REF-CAC1844F', '2025-10-19 09:19:34'),
(15, 'edb29489-5b16-4060-a60a-7b43fbb1baf8', 'REF-C8B0FEBC', '2025-10-19 09:25:49'),
(16, 'e39e2968-36cd-4858-b407-6a843cf009ad', 'REF-C6E8BAB9', '2025-10-19 09:26:18'),
(17, 'dc08e7cd-31ef-4816-abd3-81999cc23406', 'REF-FBB8971D', '2025-10-19 09:31:22'),
(18, 'f2f67abe-3810-4cb4-b0c4-5ba8492567b2', 'REF-06A47127', '2025-10-19 09:34:16'),
(19, '4faf8d9c-6594-4a61-aa1b-502d83b128f8', 'REF-D02916CB', '2025-10-20 10:25:02'),
(20, '14737603-c3e1-4040-a7d6-ff22ec378752', 'REF-DCA2750F', '2025-10-23 14:20:27'),
(21, '1843a81a-ccf6-43ea-80b2-ec63880afe4e', 'REF-685F3317', '2025-10-24 08:19:50'),
(22, 'ff9399c4-24f0-452c-b039-bf264b6d6a50', 'REF-66444357', '2025-10-24 08:20:05'),
(23, 'c69eb396-f267-45be-9894-1efcfb48c2d4', 'REF-86228D7C', '2025-10-24 08:29:51'),
(24, '0a44be8d-c939-4e0d-9c6b-47ed095b4d4a', 'REF-73178D06', '2025-10-24 08:29:55'),
(27, '0a2f72ae-b46d-4bb8-883e-e160e24c9e41', 'REF-059B791E', '2025-10-24 09:01:42'),
(29, 'cce9aafc-c9f0-4342-8a0d-9df28bab5789', 'REF-214A4B81', '2025-10-24 09:08:48'),
(30, '9b9233a8-e0c6-4552-aa68-04e80ed26905', 'REF-246EDAAA', '2025-10-24 09:09:33'),
(31, '26d0c339-dcd9-4f6a-b607-f1ac6cd98b9a', 'REF-28936A2D', '2025-10-24 09:11:23'),
(32, '172cc564-63f1-4894-b121-60b934321cef', 'REF-3C94D72E', '2025-10-24 09:16:07'),
(33, 'cdac9e23-f222-44b6-bb35-34c246289e3b', 'REF-C1CBBB4C', '2025-10-24 09:51:47'),
(34, '2673bfe7-81c1-4d4b-b4e9-ee7e859c17cd', 'REF-C7470573', '2025-10-24 09:53:05'),
(35, 'd1f9d695-ad83-432c-b2a3-44a3c229f653', 'REF-CF8079FA', '2025-10-24 09:56:18'),
(36, 'cf21dab4-7517-4954-9876-50e19413b15b', 'REF-E2CDFA78', '2025-10-24 10:00:21'),
(37, '1d0d2a81-130c-48e9-b045-a741538ff64b', 'REF-E8767B0D', '2025-10-24 10:01:53'),
(38, 'b44d473d-a177-4aba-a231-83cd93bdc9bc', 'REF-4BBCBB35', '2025-10-24 10:08:16'),
(39, '5c2c5782-f506-46de-8be4-06a6312f6517', 'REF-23743661', '2025-10-28 12:08:31'),
(40, '346d038d-7ef2-4ed9-9655-a5cf708b2e4e', 'REF-3FE6B0E4', '2025-10-28 12:16:12');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` timestamp NULL DEFAULT NULL,
  `reset_token_used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id`, `username`, `password`, `created_at`, `reset_token`, `reset_token_expires`, `reset_token_used`) VALUES
(1, 'admin', '$2y$10$LN.hxVqyoUiFkcckiXO9XeX83ChANVk5WMG66YOtv.A60Ut29FXRy', '2025-10-08 17:24:43', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_log`
--

CREATE TABLE `password_reset_log` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('pending','completed','expired','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_log`
--

INSERT INTO `password_reset_log` (`id`, `username`, `reset_token`, `requested_at`, `completed_at`, `ip_address`, `user_agent`, `status`) VALUES
(1, 'admin', 'e5f8d0285035ec7ac53d9ca8f638580e2357e514fb3a3754b51fd7a5eba85961', '2025-10-19 05:26:53', '2025-10-19 05:27:01', '127.0.0.1', 'Test Script', 'completed'),
(4, 'admin', '166dc1b426e5276ff9f965bbf8d3eca7fd550f407d7c2ee43d6e27029e0a10f2', '2025-10-19 05:43:55', '2025-10-19 05:45:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'completed'),
(6, 'admin', '4ebfc6e74fe341a68f2693436265d1b8910b6e7efdca8d8d59f47f5d79f0e915', '2025-10-20 10:27:29', '2025-10-20 10:27:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'completed'),
(7, 'admin', 'f5405edae102caea1e8701c1f80947f80ddd296c9969fbf8949581f7b53f628f', '2025-10-23 14:28:35', '2025-10-23 14:29:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL,
  `qr_code_image` varchar(255) DEFAULT NULL,
  `time_limit` int(11) DEFAULT 30,
  `payment_info` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` char(36) NOT NULL,
  `ref_code` varchar(20) NOT NULL,
  `plate` varchar(50) NOT NULL,
  `type_id` int(11) NOT NULL,
  `owner_name` varchar(200) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `plate_normalized` varchar(100) GENERATED ALWAYS AS (lcase(replace(`plate`,' ',''))) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `ref_code`, `plate`, `type_id`, `owner_name`, `phone`, `price`, `note`, `created_at`) VALUES
('15830d61-ce38-49d6-843b-59b8542847f0', 'REF-D9A79880', 'ອກ 4466', 2, 'ຮ', '', 25000.00, '', '2025-10-25 05:18:18'),
('41263eb6-6f19-4b46-bbf6-e884e1f00091', 'REF-05E13563', 'ອດ 4567', 3, 'ດອນ1', '355 4355 878', 50000.00, '', '2025-10-23 07:59:26'),
('65c5ac6e-f82e-47da-926e-c8dbe13b9e23', 'REF-42CE1D18', 'ພພ 5554', 2, 'ຮ', '', 25000.00, '', '2025-10-28 12:16:44'),
('6bba7b04-0e50-43e6-8961-6810a77a6686', 'REF-F68C2E70', '7799', 2, 'ແສງ', '', 25000.00, '', '2025-10-24 10:05:28'),
('8e07f0eb-5a06-4b34-b0cd-361271993f50', 'REF-5B7BF327', '5556', 2, 'ຮ', '', 25000.00, 'asdef123', '2025-10-28 12:23:19'),
('92d6ca18-7509-466a-9afb-4a669a71ee2b', 'REF-2308ED6E', 'dd 8899', 2, 'kk', '355 4354 6465', 50000.00, '', '2025-10-24 08:00:48'),
('9355f6c9-2590-47d6-a0cf-33c936aa5428', 'REF-45B3F511', 'ຫກ 7174', 2, 'ກ', '112 2334 4', 25000.00, '', '2025-10-24 08:10:03'),
('b949e160-d148-412d-b5ad-a458de827b87', 'REF-1A622E4E', 'ພພ 5555', 2, 'ຮ', '', 25000.00, '', '2025-10-28 12:05:58');

--
-- Triggers `vehicles`
--
DELIMITER $$
CREATE TRIGGER `protect_created_at_update` BEFORE UPDATE ON `vehicles` FOR EACH ROW BEGIN
            SET NEW.created_at = OLD.created_at;
        END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `protect_ref_code_delete` BEFORE DELETE ON `vehicles` FOR EACH ROW BEGIN
            INSERT INTO deletion_log (vehicle_id, ref_code, deleted_at) 
            VALUES (OLD.id, OLD.ref_code, NOW());
        END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `protect_ref_code_update` BEFORE UPDATE ON `vehicles` FOR EACH ROW BEGIN
            IF NEW.ref_code != OLD.ref_code THEN
                SIGNAL SQLSTATE '45000' 
                SET MESSAGE_TEXT = 'ລະຫັດອ້າງອີງ (ref_code) ບໍ່ສາມາດປ່ຽນແປງໄດ້. ກະລຸນາຕິດຕໍ່ຜູ້ບໍລິຫານຖານຂໍ້ມູນຖ້າຕ້ອງການປ່ຽນແປງ.';
            END IF;
        END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_types`
--

CREATE TABLE `vehicle_types` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_types`
--

INSERT INTO `vehicle_types` (`id`, `code`, `name`) VALUES
(1, 'BIC', 'ລົດຖີບ'),
(2, 'MOT', 'ລົດຈັກ'),
(3, 'CAR', 'ລົດໃຫຍ່');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `deletion_log`
--
ALTER TABLE `deletion_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vehicle_id` (`vehicle_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_reset_token` (`reset_token`);

--
-- Indexes for table `password_reset_log`
--
ALTER TABLE `password_reset_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_reset_token` (`reset_token`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_requested_at` (`requested_at`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_code` (`ref_code`),
  ADD UNIQUE KEY `idx_plate_normalized` (`plate_normalized`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `vehicle_types`
--
ALTER TABLE `vehicle_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `deletion_log`
--
ALTER TABLE `deletion_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_reset_log`
--
ALTER TABLE `password_reset_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `vehicle_types`
--
ALTER TABLE `vehicle_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `vehicle_types` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
