-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 13, 2018 at 09:15 PM
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
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `tel` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `tel`, `address`) VALUES
(1, 'Vasiliy Pypkin Ivanovich', '+375296506780', 'ул. Анимешников д.44 кв.1234'),
(2, 'Yan Malanenko', '+375296506444', 'axda');

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
(1, 'Инокентий Макороненов', '2', 2, 3),
(2, 'ALex', '1', 3, 5),
(3, 'Petya Petechkin', '1', 2, 5),
(7, 'Alexey', '1', 11, 8),
(9, 'Kolya', '1', 1, 3),
(10, 'abcd', '2', 3, 2);

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
(2, 'стрижка'),
(3, 'расчёска'),
(4, 'Накрутка');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(10) UNSIGNED NOT NULL,
  `users_user_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `cost` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` time DEFAULT NULL,
  `duration` time NOT NULL,
  `client_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount` int(11) DEFAULT NULL,
  `text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `users_user_id`, `date`, `cost`, `product`, `time`, `duration`, `client_id`, `discount`, `text`) VALUES
(1, '2', '2018-06-07', '500', '1', '12:00:00', '00:50:00', NULL, NULL, NULL),
(2, '2', '2018-06-05', '700', '1', '13:00:00', '02:00:00', NULL, NULL, NULL),
(3, '3', '2018-06-29', '100', '2', '10:00:00', '01:15:00', NULL, NULL, NULL),
(4, '3', '2018-05-17', '111', '1', '10:00:00', '01:30:00', NULL, NULL, NULL),
(5, '2', '2018-06-22', '120', '1', '10:00:00', '00:45:00', NULL, NULL, NULL),
(6, '2', '2018-06-30', '100', '2', '16:30:00', '00:20:00', '1', NULL, NULL),
(7, '2', '2018-06-29', '7', '2', '18:00:00', '00:45:00', NULL, NULL, NULL),
(8, '2', '2018-06-29', '12', '1', '12:30:00', '01:45:00', NULL, NULL, NULL),
(12, '2', '2018-06-27', '12', '2', '11:00:00', '01:25:00', NULL, NULL, NULL),
(13, '2', '2018-06-27', '45', '2', '10:00:00', '00:45:00', NULL, NULL, NULL),
(18, '2', '2018-06-27', '1', '1', '13:00:00', '00:10:00', NULL, NULL, NULL),
(20, '2', '2018-06-27', '3', '1', '13:10:00', '00:05:00', NULL, NULL, NULL),
(21, '2', '2018-06-27', '6', '1', '12:50:00', '00:10:00', NULL, NULL, NULL),
(22, '2', '2018-06-27', '0.01', '1', '10:48:00', '00:02:00', NULL, NULL, NULL),
(31, '2', '2018-07-08', '7', '2', '17:30:00', '00:30:00', NULL, NULL, NULL),
(32, '2', '2018-07-08', '5', '1', '09:00:00', '00:10:00', NULL, NULL, 'asdadadaddddddddddddddddddddddddddddddddddddddddddddddddddddadada1223'),
(35, '2', '2018-07-08', '500', '1', '14:00:00', '02:35:00', NULL, NULL, NULL),
(36, '2', '2018-07-08', '2', '1', '10:00:00', '00:31:00', NULL, 0, NULL),
(39, '3', '2018-07-08', '3', '3', '10:00:00', '00:25:00', '1', NULL, NULL),
(41, '2', '2018-07-08', '700', '3', '12:30:00', '01:00:00', NULL, 0, NULL),
(42, '2', '2018-07-24', '100', '3', '11:30:00', '01:00:00', NULL, NULL, NULL),
(43, '1', '2018-07-10', '10', '1', '09:30:00', '00:30:00', NULL, NULL, NULL),
(57, '2', '2018-07-23', '700', '4', '09:00:00', '03:00:00', NULL, NULL, NULL),
(58, '2', '2018-07-11', '500', '4', '11:00:00', '01:30:00', NULL, NULL, NULL),
(59, '2', '2018-07-11', '700', '4', '10:30:00', '00:30:00', NULL, NULL, NULL),
(60, '1', '2018-07-12', '500', '1', '10:30:00', '00:30:00', NULL, NULL, NULL),
(62, '1', '2018-07-12', '100', '4', '15:00:00', '01:00:00', NULL, NULL, NULL),
(65, '2', '2018-07-24', '40', '1', '14:00:00', '02:00:00', '1', 60, 'qwerty12');

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `master_id` int(11) DEFAULT NULL,
  `shift_type` int(11) DEFAULT '0',
  `start_shift` time NOT NULL,
  `end_shift` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id`, `date`, `master_id`, `shift_type`, `start_shift`, `end_shift`) VALUES
(1, '2018-06-28', 2, 1, '00:00:00', '00:00:00'),
(2, '2018-06-27', 2, 1, '00:00:00', '00:00:00'),
(3, '2018-05-23', 2, 1, '00:00:00', '00:00:00'),
(4, '2018-07-23', 2, 3, '00:00:00', '00:00:00'),
(5, '2018-06-27', 3, 3, '00:00:00', '00:00:00'),
(6, '2018-06-27', 7, 2, '00:00:00', '00:00:00'),
(7, '2018-06-30', 7, 2, '00:00:00', '00:00:00'),
(8, '2018-06-30', 3, 3, '00:00:00', '00:00:00'),
(9, '2018-06-30', 2, 1, '00:00:00', '00:00:00'),
(10, '2018-06-29', 2, 3, '00:00:00', '00:00:00'),
(11, '2018-06-29', 3, 1, '00:00:00', '00:00:00'),
(12, '2018-06-29', 7, 2, '00:00:00', '00:00:00'),
(13, '2018-07-02', 2, 3, '09:00:00', '20:00:00'),
(14, '2018-07-04', 2, 3, '09:00:00', '20:00:00'),
(15, '2018-07-05', 2, 3, '00:00:00', '00:00:00'),
(16, '2018-07-08', 2, 3, '00:00:00', '00:00:00'),
(17, '2018-07-24', 2, 3, '09:00:00', '20:00:00'),
(18, '2018-07-08', 3, 3, '00:00:00', '00:00:00'),
(19, '2018-07-18', 2, 1, '13:00:00', '18:00:00'),
(21, '2018-07-25', 9, 3, '00:00:00', '00:00:00'),
(22, '2018-07-08', 7, 1, '09:00:00', '18:00:00'),
(28, '2018-07-10', 2, 3, '00:00:00', '00:00:00'),
(29, '2018-07-10', 3, 3, '00:00:00', '00:00:00'),
(32, '2018-07-10', 7, 3, '00:00:00', '00:00:00'),
(33, '2018-07-08', 9, 1, '12:00:00', '12:30:00'),
(34, '2018-07-10', 1, 3, '09:00:00', '20:00:00'),
(37, '2018-07-11', 2, 1, '10:30:00', '13:00:00'),
(38, '2018-07-27', 2, 1, '10:00:00', '12:00:00'),
(39, '2018-07-31', 3, 1, '10:30:00', '20:00:00'),
(40, '2018-07-12', 1, 1, '10:30:00', '20:00:00');

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
  `admin` int(11) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `salon`, `admin`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Yan', 'yan@mail.ru', '$2y$10$wBety8iDfRQygCk0OgHvsuVJGz2UH2n7.9riRAxABHhv666ZI0LEq', '1', 0, '99m9htmDf2FcfVrazMJOjFtnppp9iPJsWYhMhlkRlXRwHfReQi9rnp6ixUqi', '2018-06-26 14:31:47', '2018-06-26 14:31:47'),
(2, 'Vasya Pypkin', 'yan2@mail.ru', '$2y$10$PJMc77j8TO6wLoUUJH5q1Obg5gu2GRrqV1ZEF7EhWZMZQd8bLaeey', '1', 1, '66Z86gBw1krzU9PUhreR6omtgNPElXverfsAScolwIh3QDt55Ufnthx0wHXx', '2018-07-10 15:07:41', '2018-07-13 14:28:13'),
(3, 'wefwef', 'efw@mail.ru', '$2y$10$cd1hY56ZQznD.COfb0fMsOlCAMghOIz3kalFGvB3d2qNQAPTw7kIG', '2', 1, 'mieZTsoKUdRQpPJ1rrRnx9e2Gr18cNQJu6C1LVYHcXS8p0QNB0zgoksjq8GK', '2018-07-10 15:56:21', '2018-07-10 17:17:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD UNIQUE KEY `id` (`id`);

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
-- Indexes for table `services`
--
ALTER TABLE `services`
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
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `masters`
--
ALTER TABLE `masters`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
