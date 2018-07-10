-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 06, 2018 at 08:06 PM
-- Server version: 5.6.38
-- PHP Version: 7.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tokio`
--

-- --------------------------------------------------------

--
-- Table structure for table `masters`
--

CREATE TABLE `masters` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `salon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `range` int(11) NOT NULL DEFAULT '1',
  `plan` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `masters`
--

INSERT INTO `masters` (`id`, `name`, `salon`, `range`, `plan`) VALUES
(1, 'Мастер Дима', '2', 1, 3),
(2, 'Alex', '1', 2, 2),
(3, 'Petya Petechkin', '1', 2, 5),
(7, 'Alexey', '1', 11, 8),
(8, 'Yan', '1', 2, 4),
(9, 'Kolya', '1', 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(6, '2014_10_12_000000_create_master_table', 1),
(38, '2014_10_12_000000_create_masters_table', 2),
(39, '2014_10_12_000000_create_products_table', 2),
(40, '2014_10_12_000000_create_users_table', 2),
(41, '2014_10_12_100000_create_password_resets_table', 2),
(42, '2018_04_19_104250_create_sales_table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`) VALUES
(1, 'покраска'),
(2, 'стрижка');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(10) UNSIGNED NOT NULL,
  `users_user_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `cost` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` time DEFAULT NULL,
  `duration` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `users_user_id`, `date`, `cost`, `product`, `time`, `duration`) VALUES
(1, '2', '2018-06-07', '500', '1', '12:00:00', '00:50:00'),
(2, '2', '2018-06-05', '700', '1', '13:00:00', '02:00:00'),
(3, '3', '2018-06-29', '100', '2', '10:00:00', '01:15:00'),
(4, '3', '2018-05-17', '111', '1', '10:00:00', '01:30:00'),
(5, '2', '2018-06-22', '120', '1', '10:00:00', '00:45:00'),
(6, '2', '2018-06-30', '100', '2', '16:30:00', '00:20:00'),
(7, '2', '2018-06-29', '7', '2', '18:00:00', '00:45:00'),
(8, '2', '2018-06-29', '12', '1', '12:30:00', '01:45:00'),
(12, '2', '2018-06-27', '12', '2', '11:00:00', '01:25:00'),
(13, '2', '2018-06-27', '45', '2', '10:00:00', '00:45:00'),
(18, '2', '2018-06-27', '1', '1', '13:00:00', '00:10:00'),
(20, '2', '2018-06-27', '3', '1', '13:10:00', '00:05:00'),
(21, '2', '2018-06-27', '6', '1', '12:50:00', '00:10:00'),
(22, '2', '2018-06-27', '0.01', '1', '10:48:00', '00:02:00'),
(23, '2', '2018-07-23', '5.05', '2', '10:00:00', '00:35:00'),
(24, '2', '2018-07-04', '500', '1', '14:00:00', '00:20:00'),
(25, '2', '2018-07-04', '1.50', '1', '15:30:00', '00:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `master_id` int(11) DEFAULT NULL,
  `shift_type` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id`, `date`, `master_id`, `shift_type`) VALUES
(1, '2018-06-28', 2, 1),
(2, '2018-06-27', 2, 1),
(3, '2018-05-23', 2, 1),
(4, '2018-07-23', 2, 1),
(5, '2018-06-27', 3, 3),
(6, '2018-06-27', 7, 2),
(7, '2018-06-30', 7, 2),
(8, '2018-06-30', 3, 3),
(9, '2018-06-30', 2, 1),
(10, '2018-06-29', 2, 3),
(11, '2018-06-29', 3, 1),
(12, '2018-06-29', 7, 2),
(13, '2018-07-02', 2, 2),
(14, '2018-07-04', 2, 2),
(15, '2018-07-05', 2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `salon` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `salon`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Yan', 'yan@mail.ru', '$2y$10$wBety8iDfRQygCk0OgHvsuVJGz2UH2n7.9riRAxABHhv666ZI0LEq', '1', 'osPavfQ20CmOB8JbAi1oGXhNAUQgem5Ib4DJSteErziGsP8aYU4dqas3OcWM', '2018-06-26 14:31:47', '2018-06-26 14:31:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `masters`
--
ALTER TABLE `masters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`(191));

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `masters`
--
ALTER TABLE `masters`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
