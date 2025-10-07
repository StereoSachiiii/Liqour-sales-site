-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 22, 2025 at 08:44 PM
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
-- Database: `webdev-liqour`
--

-- --------------------------------------------------------

--
-- Table structure for table `liqours`
--

CREATE TABLE `liqours` (
  `liqour_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `liqours`
--

INSERT INTO `liqours` (`liqour_id`, `name`, `description`, `price`, `image_url`, `category_id`, `supplier_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Jack Daniels', 'Classic Tennessee Whiskey', 4509.00, 'src/product-images/jack.jpg', 1, NULL, 1, '2025-09-11 14:45:08', '2025-09-11 18:27:53'),
(2, 'Absolut', 'Premium Swedish Vodka', 30.00, 'src/product-images/absolut.jpg', 2, NULL, 1, '2025-09-11 14:45:08', '2025-09-11 14:45:08'),
(3, 'Bacardi', 'Smooth Caribbean Rum', 35.00, 'src/product-images/bacardi.jpg', 3, NULL, 1, '2025-09-11 14:45:08', '2025-09-11 14:45:08'),
(4, 'Tanqueray', 'London Dry Gin', 40.00, 'uploads/liqour_68c9cb9a5bd819.63000126.jpg', 4, NULL, 1, '2025-09-11 14:45:08', '2025-09-17 02:12:02'),
(16, 'Lion', 'Sri lankan beer', 100.00, 'src/product-images/68cb5b9d6a116.jpg', 14, NULL, 1, '2025-09-18 06:38:45', '2025-09-18 06:38:45');

-- --------------------------------------------------------

--
-- Table structure for table `liqour_categories`
--

CREATE TABLE `liqour_categories` (
  `liqour_category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image_url` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `liqour_categories`
--

INSERT INTO `liqour_categories` (`liqour_category_id`, `name`, `image_url`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Whiskey', 'public/src/category-images/barman-filling-glass-alcohol.jpg', 1, '2025-09-11 14:44:55', '2025-09-17 02:26:49'),
(2, 'Vodka', 'src/product-images/vodka.jpg', 1, '2025-09-11 14:44:55', '2025-09-11 14:44:55'),
(3, 'Rum', 'src/product-images/rum.jpg', 1, '2025-09-11 14:44:55', '2025-09-11 14:44:55'),
(4, 'Gin', 'src/product-images/gin.jpg', 1, '2025-09-11 14:44:55', '2025-09-11 14:44:55'),
(10, 'new category', 'public/src/category images/cat_68c93c088296a5.34720109.jpg', 0, '2025-09-16 15:59:28', '2025-09-16 16:04:30'),
(11, 'hi', 'public/src/category-images/ash-edmonds-fsI-_MRsic0-unsplash.jpg', 0, '2025-09-16 17:22:47', '2025-09-17 02:42:56'),
(14, 'Beer', 'public/src/category images/cat_68cb5b4c6f0ae6.59187610.jpg', 1, '2025-09-18 06:37:24', '2025-09-18 06:37:24');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `total` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `status`, `total`, `is_active`, `created_at`, `updated_at`) VALUES
(38, 7, 'completed', 70.00, 1, '2025-09-18 02:45:15', '2025-09-18 04:42:50'),
(39, 7, 'pending', 185.00, 0, '2025-09-18 06:07:09', '2025-09-18 07:10:02'),
(40, 7, 'completed', 40.00, 1, '2025-09-18 06:21:33', '2025-09-18 15:33:07'),
(41, 7, 'pending', 100.00, 1, '2025-09-18 06:42:06', '2025-09-18 06:42:06'),
(43, 7, 'processing', 500.00, 1, '2025-09-18 06:47:34', '2025-09-18 06:47:34'),
(44, 7, 'processing', 320.00, 1, '2025-09-18 07:13:56', '2025-09-18 07:13:56'),
(45, 7, 'processing', 4325.00, 1, '2025-09-18 07:26:34', '2025-09-18 07:26:34'),
(54, 7, 'processing', 270.00, 1, '2025-09-18 15:43:39', '2025-09-18 15:43:39'),
(55, 7, 'processing', 200.00, 1, '2025-09-18 15:53:25', '2025-09-18 15:53:25');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_id` int(11) NOT NULL,
  `liqour_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_id`, `liqour_id`, `quantity`, `price`, `is_active`) VALUES
(38, 3, 2, 35.00, 1),
(39, 2, 1, 30.00, 1),
(39, 3, 1, 35.00, 1),
(39, 4, 3, 40.00, 1),
(40, 4, 1, 40.00, 1),
(41, 16, 1, 100.00, 1),
(43, 16, 5, 100.00, 1),
(44, 4, 8, 40.00, 1),
(45, 3, 95, 35.00, 1),
(45, 16, 10, 100.00, 1),
(54, 3, 2, 35.00, 1),
(54, 4, 5, 40.00, 1),
(55, 4, 5, 40.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `liqour_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `liqour_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0 CHECK (`quantity` >= 0),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`liqour_id`, `warehouse_id`, `quantity`, `updated_at`, `is_active`) VALUES
(3, 2, 900, '2025-09-18 15:43:39', 1),
(3, 4, 100, '2025-09-12 01:28:07', 1),
(4, 3, 81, '2025-09-18 15:53:25', 1),
(4, 4, 97, '2025-09-18 15:33:07', 1),
(16, 4, 0, '2025-09-18 07:26:34', 1);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `name`, `email`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Premium Spirits Co', 'sales@premiumspirits.com', '555-0101', 1, '2025-09-22 23:47:26', '2025-09-22 23:47:26'),
(2, 'Local Brewery Supply', 'info@localbrewery.com', '555-0102', 1, '2025-09-22 23:47:26', '2025-09-22 23:47:26'),
(3, 'International Wine Co', 'orders@intwine.com', '555-0103', 1, '2025-09-22 23:47:26', '2025-09-22 23:47:26');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `phone`, `address`, `profile_pic`, `is_admin`, `is_active`, `created_at`, `updated_at`, `last_login`) VALUES
(5, 'Test User 3', 'user3@example.com', '$2y$10$examplehash3', '1234567892', 'Address 3', NULL, 0, 1, '2025-09-11 14:47:48', '2025-09-16 17:05:00', NULL),
(7, 'Akila', 'Akila@gmail.com', '$2y$10$NjSQAdrriieJrp.3O.CnBufczh23LEGyk8uofg/R72u8HAYSXgvKi', '0702222221', 'No 50 Kandy', 'uploads/profile_7.1758150301.jpg', 1, 1, '2025-09-11 18:06:09', '2025-09-18 04:35:01', NULL),
(9, 'janani', 'janali@gmail.com', '$2y$10$T2CvgEg2shZO7qKlQw3P.uNfC.0Nrcs9zPBu08psOZHW3Q.hcFaxy', '070222222', 'no 60', NULL, 0, 1, '2025-09-16 18:17:46', '2025-09-16 18:17:46', NULL),
(10, 'admin', 'kalsun@gmail.com', '$2y$10$fb.dVyjPiu28qA986E9PAOMpq6wkq2yIDFwZMsqsb.Ln1NI58gfvK', '122', 'no 50', 'uploads/profile_10.1758045705.jpg', 1, 1, '2025-09-16 19:01:14', '2025-09-17 02:56:13', NULL),
(11, 'newperson', 'new@gmail.com', '$2y$10$J0RyBkIhnzk64bCQbimsLOQtvlS8edeY9zvVpfU8Pgrwa2M401Q2G', '0702214096', '1234567890', NULL, 0, 1, '2025-09-16 19:13:32', '2025-09-16 19:13:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `warehouse`
--

CREATE TABLE `warehouse` (
  `warehouse_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouse`
--

INSERT INTO `warehouse` (`warehouse_id`, `name`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Colombo Central', '50 Main Street, Colombo', 1, '2025-09-11 14:45:20', '2025-09-11 14:45:20'),
(2, 'Kandy Depot', '25 Temple Rd, Kandy', 1, '2025-09-11 14:45:20', '2025-09-11 14:45:20'),
(3, 'Galle Storage', '10 Beach Rd, Galle', 1, '2025-09-11 14:45:20', '2025-09-11 14:45:20'),
(4, 'Jaffna Hub', '5 Market St, Jaffna', 1, '2025-09-11 14:45:20', '2025-09-16 16:51:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `liqours`
--
ALTER TABLE `liqours`
  ADD PRIMARY KEY (`liqour_id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_supplier` (`supplier_id`);

--
-- Indexes for table `liqour_categories`
--
ALTER TABLE `liqour_categories`
  ADD PRIMARY KEY (`liqour_category_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_id`,`liqour_id`),
  ADD KEY `idx_liqour` (`liqour_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_liqour` (`liqour_id`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`liqour_id`,`warehouse_id`),
  ADD KEY `idx_warehouse` (`warehouse_id`),
  ADD KEY `idx_liqour` (`liqour_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `warehouse`
--
ALTER TABLE `warehouse`
  ADD PRIMARY KEY (`warehouse_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `liqours`
--
ALTER TABLE `liqours`
  MODIFY `liqour_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `liqour_categories`
--
ALTER TABLE `liqour_categories`
  MODIFY `liqour_category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `warehouse`
--
ALTER TABLE `warehouse`
  MODIFY `warehouse_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `liqours`
--
ALTER TABLE `liqours`
  ADD CONSTRAINT `liqours_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `liqour_categories` (`liqour_category_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `liqours_supplier_fk` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`liqour_id`) REFERENCES `liqours` (`liqour_id`) ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`liqour_id`) REFERENCES `liqours` (`liqour_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`liqour_id`) REFERENCES `liqours` (`liqour_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`warehouse_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
