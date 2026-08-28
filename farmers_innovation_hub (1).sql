-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 28, 2026 at 10:22 AM
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
-- Database: `farmers_innovation_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `coordinates`
--

CREATE TABLE `coordinates` (
  `id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `farm_id` char(36) DEFAULT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `accuracy_meters` decimal(10,2) DEFAULT NULL,
  `source` enum('device','farm_location') NOT NULL DEFAULT 'device',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coordinates`
--

INSERT INTO `coordinates` (`id`, `user_id`, `farm_id`, `latitude`, `longitude`, `accuracy_meters`, `source`, `created_at`, `updated_at`) VALUES
('48ab30f2-9102-427a-8bfe-0ca5ffe58eb2', 'cacd2a47-f26c-4ebb-8d02-56da40e05e00', '372c073f-1eb3-44d1-bcea-c1261fb1e346', -0.5759977, 37.3278096, 124.00, 'farm_location', '2026-08-27 15:42:23', '2026-08-27 16:39:11');

-- --------------------------------------------------------

--
-- Table structure for table `counties`
--

CREATE TABLE `counties` (
  `id` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `counties`
--

INSERT INTO `counties` (`id`, `name`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
('c1000000-0000-4000-8000-000000000001', 'Makueni', '047', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15');

-- --------------------------------------------------------

--
-- Table structure for table `crops`
--

CREATE TABLE `crops` (
  `id` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crops`
--

INSERT INTO `crops` (`id`, `name`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
('15272baa-d512-44dd-bcbf-850616a6e74a', 'Green grams', 'Pulses', 1, '2026-08-28 08:19:57', '2026-08-28 08:19:57'),
('232f15b4-fba2-47e2-9aee-4b24dc928d91', 'Bananas', 'Fruits', 1, '2026-08-28 08:19:57', '2026-08-28 08:19:57'),
('4e5e19c2-9aff-45b8-8c3d-7f862131f504', 'Mangoes', 'Fruits', 1, '2026-08-28 08:19:57', '2026-08-28 08:19:57'),
('52b49c7f-3052-4e59-9727-5a7b9b6542be', 'Beans', 'Pulses', 1, '2026-08-28 08:19:57', '2026-08-28 08:19:57'),
('69be392b-3f07-4d81-b974-7ff9b815ce2e', 'Maize', 'Cereals', 1, '2026-08-28 08:19:57', '2026-08-28 08:19:57'),
('828db60c-8f91-4ded-b6ed-9d399ee42ace', 'Cowpeas', 'Pulses', 1, '2026-08-28 08:19:57', '2026-08-28 08:19:57'),
('a0b98a57-fb16-4b29-a597-c3d42231a624', 'Wheat', 'Cereals', 1, '2026-08-28 08:19:57', '2026-08-28 08:19:57'),
('a497b32e-66b1-4bca-b9cb-23dc5aaa95dc', 'Rice', 'Cereals', 1, '2026-08-28 08:19:57', '2026-08-28 08:19:57'),
('b194dcd1-35da-4a86-82b6-7ab50ba2d7c6', 'Avocado', 'Fruits', 1, '2026-08-28 08:19:57', '2026-08-28 08:19:57'),
('b2582281-74e0-44d2-924e-dd46a392fca8', 'Cabbage', 'Vegetables', 1, '2026-08-28 08:19:57', '2026-08-28 08:19:57'),
('c1040315-72c5-44a8-af7b-703e0e63f38d', 'Kale', 'Vegetables', 1, '2026-08-28 08:19:57', '2026-08-28 08:19:57'),
('cf8106b5-3788-4440-8ec3-28bed8071420', 'Tomatoes', 'Vegetables', 1, '2026-08-28 08:19:57', '2026-08-28 08:19:57');

-- --------------------------------------------------------

--
-- Table structure for table `farmer_profiles`
--

CREATE TABLE `farmer_profiles` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `farm_name` varchar(100) DEFAULT NULL,
  `county_id` char(36) NOT NULL,
  `sub_county_id` char(36) NOT NULL,
  `ward_id` char(36) NOT NULL,
  `farm_size_acres` decimal(10,2) DEFAULT NULL,
  `water_source` varchar(100) DEFAULT NULL,
  `irrigation` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farmer_profiles`
--

INSERT INTO `farmer_profiles` (`id`, `user_id`, `farm_name`, `county_id`, `sub_county_id`, `ward_id`, `farm_size_acres`, `water_source`, `irrigation`, `created_at`, `updated_at`) VALUES
('22fb9737-52a2-4702-b1ee-388468d7cb06', 'cacd2a47-f26c-4ebb-8d02-56da40e05e00', 'kwakyai Farm', 'c1000000-0000-4000-8000-000000000001', 'c1000000-0000-4000-8000-000000000015', 'c1000000-0000-4000-8000-000000005002', 1.50, 'dam', 0, '2026-08-27 15:02:04', '2026-08-27 15:46:28'),
('372c073f-1eb3-44d1-bcea-c1261fb1e346', 'cacd2a47-f26c-4ebb-8d02-56da40e05e00', NULL, 'c1000000-0000-4000-8000-000000000001', 'c1000000-0000-4000-8000-000000000015', 'c1000000-0000-4000-8000-000000005004', 3.90, 'rainwater', 0, '2026-08-26 19:51:20', '2026-08-26 19:51:20');

-- --------------------------------------------------------

--
-- Table structure for table `farm_crops`
--

CREATE TABLE `farm_crops` (
  `id` char(36) NOT NULL,
  `farm_id` char(36) NOT NULL,
  `crop_id` char(36) NOT NULL,
  `area_planted_acres` decimal(10,2) DEFAULT NULL,
  `planting_date` date DEFAULT NULL,
  `expected_harvest_date` date DEFAULT NULL,
  `season` varchar(50) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Growing',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `government_profiles`
--

CREATE TABLE `government_profiles` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `institution_name` varchar(200) NOT NULL,
  `department` varchar(150) DEFAULT NULL,
  `position` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organization_profiles`
--

CREATE TABLE `organization_profiles` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `organization_name` varchar(200) NOT NULL,
  `organization_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sub_counties`
--

CREATE TABLE `sub_counties` (
  `id` char(36) NOT NULL,
  `county_id` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sub_counties`
--

INSERT INTO `sub_counties` (`id`, `county_id`, `name`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
('c1000000-0000-4000-8000-000000000011', 'c1000000-0000-4000-8000-000000000001', 'Makueni', '047-01', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000000012', 'c1000000-0000-4000-8000-000000000001', 'Mbooni', '047-02', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000000013', 'c1000000-0000-4000-8000-000000000001', 'Kaiti', '047-03', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000000014', 'c1000000-0000-4000-8000-000000000001', 'Kilome', '047-04', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000000015', 'c1000000-0000-4000-8000-000000000001', 'Kibwezi West', '047-05', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000000016', 'c1000000-0000-4000-8000-000000000001', 'Kibwezi East', '047-06', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` char(36) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('farmer','organization','government') NOT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `status`, `email_verified_at`, `phone_verified_at`, `last_login_at`, `created_at`, `updated_at`) VALUES
('cacd2a47-f26c-4ebb-8d02-56da40e05e00', 'Paul Musyimi', 'paulmatata66@gmail.com', '0726133235', '$2y$10$57byDRuuJBBhjOyl8cZIren185Ud2BStIGayFHA6LXft16QPG.ylu', 'farmer', 'active', NULL, NULL, '2026-08-28 07:00:06', '2026-08-26 19:51:20', '2026-08-28 07:00:06'),
('f8e9771b-c027-4e30-bde1-0f926dadad92', 'FIH Test Farmer', NULL, '0712345678', '$2y$10$Z4mSc0RCzkItncmDsrVjxe4.A9czBKQyD5T4GgWyhSk0Df.61LoVS', 'farmer', 'active', NULL, NULL, '2026-08-26 18:21:32', '2026-08-26 18:19:24', '2026-08-26 18:21:32');

-- --------------------------------------------------------

--
-- Table structure for table `wards`
--

CREATE TABLE `wards` (
  `id` char(36) NOT NULL,
  `sub_county_id` char(36) NOT NULL,
  `name` varchar(120) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wards`
--

INSERT INTO `wards` (`id`, `sub_county_id`, `name`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
('c1000000-0000-4000-8000-000000001001', 'c1000000-0000-4000-8000-000000000011', 'Wote', '0424', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000001002', 'c1000000-0000-4000-8000-000000000011', 'Muvau/Kikumini', '0425', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000001003', 'c1000000-0000-4000-8000-000000000011', 'Mavindini', '0426', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000001004', 'c1000000-0000-4000-8000-000000000011', 'Kitise/Kithuki', '0427', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000001005', 'c1000000-0000-4000-8000-000000000011', 'Kathonzweni', '0428', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000001006', 'c1000000-0000-4000-8000-000000000011', 'Nzaui/Kalamba', NULL, 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000001007', 'c1000000-0000-4000-8000-000000000011', 'Mbitini', NULL, 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000002001', 'c1000000-0000-4000-8000-000000000012', 'Tulimani', '0411', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000002002', 'c1000000-0000-4000-8000-000000000012', 'Mbooni', '0412', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000002003', 'c1000000-0000-4000-8000-000000000012', 'Kithungo/Kitundu', '0413', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000002004', 'c1000000-0000-4000-8000-000000000012', 'Kisau/Kiteta', '0414', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000002005', 'c1000000-0000-4000-8000-000000000012', 'Kako/Waia', '0415', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000002006', 'c1000000-0000-4000-8000-000000000012', 'Kalawa', '0416', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000003001', 'c1000000-0000-4000-8000-000000000013', 'Ukia', '0420', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000003002', 'c1000000-0000-4000-8000-000000000013', 'Kee', '0421', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000003003', 'c1000000-0000-4000-8000-000000000013', 'Kilungu', '0422', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000003004', 'c1000000-0000-4000-8000-000000000013', 'Ilima', '0423', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000004001', 'c1000000-0000-4000-8000-000000000014', 'Kiima Kiu/Kalanzoni', NULL, 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000004002', 'c1000000-0000-4000-8000-000000000014', 'Mukaa', NULL, 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000004003', 'c1000000-0000-4000-8000-000000000014', 'Kasikeu', NULL, 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000005001', 'c1000000-0000-4000-8000-000000000015', 'Emali/Mulala', '0430', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000005002', 'c1000000-0000-4000-8000-000000000015', 'Kikumbulyu North', NULL, 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000005003', 'c1000000-0000-4000-8000-000000000015', 'Kikumbulyu South', NULL, 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000005004', 'c1000000-0000-4000-8000-000000000015', 'Makindu', NULL, 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000005005', 'c1000000-0000-4000-8000-000000000015', 'Nguu/Masumba', '0429', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000005006', 'c1000000-0000-4000-8000-000000000015', 'Nguumo', NULL, 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000006001', 'c1000000-0000-4000-8000-000000000016', 'Masongaleni', '0437', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000006002', 'c1000000-0000-4000-8000-000000000016', 'Mtito Andei', '0438', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000006003', 'c1000000-0000-4000-8000-000000000016', 'Thange', '0439', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15'),
('c1000000-0000-4000-8000-000000006004', 'c1000000-0000-4000-8000-000000000016', 'Ivingoni/Nzambani', '0440', 1, '2026-08-10 10:21:15', '2026-08-10 10:21:15');

-- --------------------------------------------------------

--
-- Table structure for table `weather_observations`
--

CREATE TABLE `weather_observations` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `farm_id` char(36) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `observed_at` datetime NOT NULL,
  `predicted_condition` varchar(100) DEFAULT NULL,
  `observed_condition` varchar(100) NOT NULL,
  `farmer_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weather_observations`
--

INSERT INTO `weather_observations` (`id`, `user_id`, `farm_id`, `latitude`, `longitude`, `observed_at`, `predicted_condition`, `observed_condition`, `farmer_comment`, `created_at`) VALUES
('0d7ac06f-51a1-44a9-a0dc-d169db28b5f8', 'cacd2a47-f26c-4ebb-8d02-56da40e05e00', '372c073f-1eb3-44d1-bcea-c1261fb1e346', -0.5759977, 37.3278096, '2026-08-28 10:36:06', 'Clear sky', 'Cloudy', NULL, '2026-08-28 07:36:06'),
('59f0608f-6551-406d-9c8a-7d8686c2756e', 'cacd2a47-f26c-4ebb-8d02-56da40e05e00', '372c073f-1eb3-44d1-bcea-c1261fb1e346', -0.5759977, 37.3278096, '2026-08-28 10:20:56', NULL, 'Clear sky', NULL, '2026-08-28 07:20:56'),
('b75a956a-87f2-48c1-9308-818e8e60f166', 'cacd2a47-f26c-4ebb-8d02-56da40e05e00', '372c073f-1eb3-44d1-bcea-c1261fb1e346', -0.5759977, 37.3278096, '2026-08-28 10:21:10', NULL, 'Clear sky', 'hjjj', '2026-08-28 07:21:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `coordinates`
--
ALTER TABLE `coordinates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coordinates_user_id` (`user_id`),
  ADD KEY `idx_coordinates_farm_id` (`farm_id`);

--
-- Indexes for table `counties`
--
ALTER TABLE `counties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_counties_name` (`name`),
  ADD UNIQUE KEY `uq_counties_code` (`code`),
  ADD KEY `idx_counties_active` (`is_active`);

--
-- Indexes for table `crops`
--
ALTER TABLE `crops`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_crop_name` (`name`);

--
-- Indexes for table `farmer_profiles`
--
ALTER TABLE `farmer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_farmer_profile_county` (`county_id`),
  ADD KEY `fk_farmer_profile_sub_county` (`sub_county_id`),
  ADD KEY `fk_farmer_profile_ward` (`ward_id`),
  ADD KEY `farmer_profiles_user_id_index` (`user_id`);

--
-- Indexes for table `farm_crops`
--
ALTER TABLE `farm_crops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_farm_crops_farm_id` (`farm_id`),
  ADD KEY `idx_farm_crops_crop_id` (`crop_id`);

--
-- Indexes for table `government_profiles`
--
ALTER TABLE `government_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `organization_profiles`
--
ALTER TABLE `organization_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `sub_counties`
--
ALTER TABLE `sub_counties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sub_counties_county_name` (`county_id`,`name`),
  ADD UNIQUE KEY `uq_sub_counties_code` (`code`),
  ADD KEY `idx_sub_counties_county` (`county_id`),
  ADD KEY `idx_sub_counties_active` (`county_id`,`is_active`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `wards`
--
ALTER TABLE `wards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wards_sub_county_name` (`sub_county_id`,`name`),
  ADD UNIQUE KEY `uq_wards_code` (`code`),
  ADD KEY `idx_wards_sub_county` (`sub_county_id`),
  ADD KEY `idx_wards_active` (`sub_county_id`,`is_active`);

--
-- Indexes for table `weather_observations`
--
ALTER TABLE `weather_observations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_weather_observations_user` (`user_id`),
  ADD KEY `idx_weather_observations_farm` (`farm_id`),
  ADD KEY `idx_weather_observations_date` (`observed_at`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `coordinates`
--
ALTER TABLE `coordinates`
  ADD CONSTRAINT `fk_coordinates_farm` FOREIGN KEY (`farm_id`) REFERENCES `farmer_profiles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_coordinates_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `farmer_profiles`
--
ALTER TABLE `farmer_profiles`
  ADD CONSTRAINT `fk_farmer_profile_county` FOREIGN KEY (`county_id`) REFERENCES `counties` (`id`),
  ADD CONSTRAINT `fk_farmer_profile_sub_county` FOREIGN KEY (`sub_county_id`) REFERENCES `sub_counties` (`id`),
  ADD CONSTRAINT `fk_farmer_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_farmer_profile_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`);

--
-- Constraints for table `farm_crops`
--
ALTER TABLE `farm_crops`
  ADD CONSTRAINT `fk_farm_crops_crop` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`),
  ADD CONSTRAINT `fk_farm_crops_farm` FOREIGN KEY (`farm_id`) REFERENCES `farmer_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `government_profiles`
--
ALTER TABLE `government_profiles`
  ADD CONSTRAINT `fk_government_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `organization_profiles`
--
ALTER TABLE `organization_profiles`
  ADD CONSTRAINT `fk_organization_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sub_counties`
--
ALTER TABLE `sub_counties`
  ADD CONSTRAINT `fk_sub_counties_county` FOREIGN KEY (`county_id`) REFERENCES `counties` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `wards`
--
ALTER TABLE `wards`
  ADD CONSTRAINT `fk_wards_sub_county` FOREIGN KEY (`sub_county_id`) REFERENCES `sub_counties` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
