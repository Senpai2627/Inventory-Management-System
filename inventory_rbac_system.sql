-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 11, 2025 at 02:26 PM
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
-- Database: `inventory_rbac_system`
--
CREATE DATABASE inventory_rbac_system;
USE inventory_rbac_system;
-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `location` varchar(100) DEFAULT NULL COMMENT 'Geolocation info: Country, Region, City',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--


-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Electronics', 'Electronic devices and components', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(2, 'Office Supplies', 'Items for office use', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(3, 'Furniture', 'Office furniture and equipment', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(4, 'Computers', 'Computer hardware and accessories', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(5, 'Stationery', 'Writing and printing materials', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(6, 'Cleaning Supplies', 'Cleaning products and tools.', '2025-07-06 15:30:37', '2025-07-11 00:37:35'),
(7, 'Tools', 'Hand and power tools', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(8, 'Safety Equipment', 'Workplace safety gear', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(9, 'Appliances', 'Office appliances', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(10, 'Miscellaneous', 'Other uncategorized items', '2025-07-06 15:30:37', '2025-07-06 15:30:37');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `reorder_level` int(11) DEFAULT 10,
  `location` varchar(100) DEFAULT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `name`, `description`, `category_id`, `supplier_id`, `quantity`, `price`, `reorder_level`, `location`, `barcode`, `created_at`, `updated_at`) VALUES
(1, 'Laptop Dell XPS 15', '15-inch business laptop', 4, NULL, 10, 1300.00, 10, 'Warehouse A1', 'DLXPS15001', '2025-07-06 15:30:37', '2025-07-09 05:44:58'),
(2, 'Wireless Mouse', 'Bluetooth wireless mouse', 1, NULL, 518, 29.99, 19, 'Warehouse B2', 'WMSE001', '2025-07-06 15:30:37', '2025-07-11 00:38:21'),
(3, 'Office Chair', 'Ergonomic office chair', 3, NULL, 16, 199.99, 5, 'Warehouse C3', 'OCHAIR001', '2025-07-06 15:30:37', '2025-07-11 00:20:48'),
(4, 'Notebook Set', 'Pack of 5 premium notebooks', 5, 5, 70, 19.99, 10, 'Warehouse D4', 'NOTE001', '2025-07-06 15:30:37', '2025-07-11 00:08:27'),
(5, 'All-in-One Printer', 'Color printer/scanner/copier', 1, NULL, 9, 249.99, 3, 'Warehouse A2', 'PRINT001', '2025-07-06 15:30:37', '2025-07-07 14:47:12'),
(6, 'Desk Lamp', 'LED adjustable desk lamp', 9, 9, 30, 39.99, 10, 'Warehouse B3', 'LAMP001', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(7, 'Power Drill', '18V cordless power drill', 7, 7, 12, 89.99, 5, 'Warehouse C4', 'DRILL001', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(8, 'Safety Glasses', 'Anti-fog safety glasses', 8, 8, 75, 9.99, 25, 'Warehouse D5', 'SAFE001', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(9, 'Whiteboard', '4x6 feet magnetic whiteboard', 2, NULL, 30, 149.99, 2, 'Warehouse A3', 'BOARD001', '2025-07-06 15:30:37', '2025-07-09 05:44:06'),
(10, 'Paper Shredder', '6-sheet cross-cut shredder', 9, 6, 10, 59.99, 3, 'Warehouse B4', 'SHRED001', '2025-07-06 15:30:37', '2025-07-06 15:30:37');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_type` enum('purchase','sale','adjustment','transfer') NOT NULL,
  `quantity` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`id`, `item_id`, `user_id`, `transaction_type`, `quantity`, `notes`, `reference_number`, `created_at`) VALUES
(1, 1, 1, 'purchase', 10, 'Initial stock', 'PO-20250701-001', '2025-07-06 15:30:37'),
(2, 2, 1, 'purchase', 50, 'Bulk order', 'PO-20250701-002', '2025-07-06 15:30:37'),
(3, 3, 1, 'purchase', 5, 'Showroom furniture', 'PO-20250702-001', '2025-07-06 15:30:37'),
(4, 4, 1, 'purchase', 25, 'Back to school stock', 'PO-20250702-002', '2025-07-06 15:30:37'),
(5, 5, 1, 'purchase', 3, 'Office upgrade', 'PO-20250703-001', '2025-07-06 15:30:37'),
(6, 1, 1, 'sale', 2, 'Sold to employee', 'SO-20250703-001', '2025-07-06 15:30:37'),
(7, 2, 1, 'sale', 10, 'Department request', 'SO-20250703-002', '2025-07-06 15:30:37'),
(8, 6, 1, 'purchase', 15, 'New office setup', 'PO-20250704-001', '2025-07-06 15:30:37'),
(9, 7, 1, 'purchase', 6, 'Maintenance department', 'PO-20250704-002', '2025-07-06 15:30:37'),
(10, 8, 1, 'purchase', 30, 'Safety compliance', 'PO-20250705-001', '2025-07-06 15:30:37'),
(11, 9, 1, 'sale', 1, 'bahala kana', '134134134', '2025-07-07 13:32:37'),
(12, 5, 6, 'purchase', 1, 'print na kita', '98172489712', '2025-07-07 14:47:12'),
(13, 2, 6, 'adjustment', 1, 'adjust la', '24993843', '2025-07-07 14:47:39'),
(14, 4, 4, 'purchase', 10, 'pa stock la', '128739821123', '2025-07-08 04:47:51'),
(15, 9, 7, 'purchase', 3, 'purchased', '', '2025-07-08 05:06:56'),
(16, 4, 1, 'purchase', 10, 'stock in', '1231425425', '2025-07-11 00:08:27'),
(17, 2, 1, 'adjustment', 5, 'hahahah', '311133243', '2025-07-11 00:38:21');

-- --------------------------------------------------------

--
-- Table structure for table `ip_geolocation_cache`
--
-- --------------------------------------------------------

--
-- Table structure for table `page_views`
--

CREATE TABLE `page_views` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'User who viewed the page (nullable for guest views)',
  `page` varchar(100) NOT NULL COMMENT 'Page path or identifier',
  `ip_address` varchar(45) NOT NULL COMMENT 'IPv4 or IPv6 address of viewer',
  `user_agent` varchar(255) DEFAULT NULL COMMENT 'Browser/device information',
  `location` varchar(100) DEFAULT NULL,
  `referrer` varchar(255) DEFAULT NULL COMMENT 'Referring URL if available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks user page views across the system';

--
-- Dumping data for table `page_views`
--


-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('user_management','inventory_management','reporting','system') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`, `category`) VALUES
(1, 'view_users', 'View user list', 'user_management'),
(2, 'edit_users', 'Edit users', 'user_management'),
(3, 'delete_users', 'Delete users', 'user_management'),
(4, 'view_roles', 'View role permissions', 'user_management'),
(5, 'edit_roles', 'Edit role permissions', 'user_management'),
(6, 'view_dashboard', 'View dashboard', 'system'),
(7, 'view_inventory', 'View inventory items', 'inventory_management'),
(8, 'add_inventory', 'Add new inventory items', 'inventory_management'),
(9, 'edit_inventory', 'Edit inventory items', 'inventory_management'),
(10, 'delete_inventory', 'Delete inventory items', 'inventory_management'),
(11, 'process_transactions', 'Process inventory transactions', 'inventory_management'),
(12, 'view_transactions', 'View inventory transactions', 'inventory_management'),
(13, 'manage_categories', 'Manage item categories', 'inventory_management'),
(14, 'manage_suppliers', 'Manage suppliers', 'inventory_management'),
(15, 'generate_reports', 'Generate inventory reports', 'reporting'),
(16, 'export_data', 'Export inventory data', 'reporting'),
(17, 'view_audit_logs', 'View system audit logs', 'system'),
(18, 'low_stock_alerts', 'Receive low stock alerts', 'inventory_management');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role` varchar(50) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role`, `permission_id`) VALUES
('admin', 1),
('admin', 2),
('admin', 3),
('admin', 4),
('admin', 5),
('admin', 6),
('admin', 7),
('admin', 8),
('admin', 9),
('admin', 10),
('admin', 11),
('admin', 12),
('admin', 13),
('admin', 14),
('admin', 15),
('admin', 16),
('admin', 17),
('admin', 18),
('inventory_manager', 6),
('inventory_manager', 7),
('inventory_manager', 8),
('inventory_manager', 9),
('inventory_manager', 11),
('inventory_manager', 12),
('inventory_manager', 13),
('inventory_manager', 14),
('inventory_manager', 15),
('inventory_manager', 18),
('staff', 6),
('staff', 7),
('staff', 11),
('staff', 12),
('staff', 18);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'Tech Solutions Inc.', 'John Smith', 'tech@example.com', '555-1234', '123 Tech St, Silicon Valley', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(2, 'Office World', 'Sarah Johnson', 'office@example.com', '555-2345', '456 Business Ave, New York', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(3, 'Furniture Plus', 'Mike Brown', 'furniture@example.com', '555-3456', '789 Oak Rd, Chicago', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(4, 'Computer Wholesale', 'Lisa Wong', 'computers@example.com', '555-4567', '321 Circuit Blvd, Austin', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(5, 'General Supplies Co.', 'David Miller', 'general@example.com', '555-5678', '654 Main St, Boston', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(6, 'CleanPro', 'Amy Chen', 'clean@example.com', '555-6789', '987 Hygiene Lane, Seattle', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(7, 'Tool Masters', 'Robert Wilson', 'tools@example.com', '555-7890', '147 Wrench Dr, Detroit', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(8, 'Safety First Ltd.', 'Jennifer Adams', 'safety@example.com', '555-8901', '258 Shield Way, Denver', '2025-07-06 15:30:37', '2025-07-06 15:30:37'),
(9, 'Appliance Depot', 'Kevin Taylor', 'appliance@example.com', '555-9012', 'Tacloban City', '2025-07-06 15:30:37', '2025-07-11 00:36:12'),
(10, 'Global Distributors', 'Emily Davis', 'global@example.com', '555-0123', '741 World Ave, Miami', '2025-07-06 15:30:37', '2025-07-06 15:30:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','inventory_manager','staff') NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@inventory.com', 'admin', 1, '2025-06-23 03:32:26', '2025-06-28 14:27:38'),
(3, 'rommel', '$2y$10$SxHkrYzo.mX74dY7Xs5k0eNwc1GaAjFb/I8QGyf2s9vWvu//nq7Nm', 'rommel@gmail.com', 'staff', 1, '2025-07-04 06:15:06', '2025-07-04 06:15:06'),
(4, 'manager', '$2y$10$mPMA1OblXS1ws94N2/OpD.pbvcFKyd5juxojI6iMSzTC4stUkFU9C', 'baka@gmail.com', 'inventory_manager', 1, '2025-07-06 15:09:02', '2025-07-08 05:02:27'),
(6, 'paul', '$2y$10$NUc/0kiQDn/csHr0QoxWpOU3GxpaCDCWx./3tfaIGOTMwJZ/t9s7K', 'paul@gmail.com', 'staff', 1, '2025-07-07 01:18:33', '2025-07-07 01:18:33'),
(7, 'staff', '$2y$10$8bx/E6Pg/gMsOVczR5m19e7OrGLTGcMVIw8xb0ESM4w5MxbQtspJe', 'staff2@gmail.com', 'staff', 1, '2025-07-08 05:05:34', '2025-07-08 05:05:34'),
(8, 'russel', '$2y$10$PwEZaKYCHeS492SI2hrLfODoRiYiGA9yng0ROlKlEo8LGmuRzc9Rm', 'ruskie@gmail.com', 'staff', 1, '2025-07-10 07:10:16', '2025-07-11 00:35:41'),
(9, 'mhon', '$2y$10$cmhX0VZKhsmclyo.cmUJ2OL2Yjh5lqX/AUKKOUyIgONGGpvikhBPO', 'mhon@gmail.com', 'staff', 1, '2025-07-10 07:47:40', '2025-07-10 07:47:40'),
(10, 'theyang', '$2y$10$zqSNijt7FMzn4x4nVIubO.LCiNByWyVXD2GD3P7O01Hv9jbPEzB3W', 'there@gmail.com', 'staff', 1, '2025-07-11 03:04:11', '2025-07-11 03:04:11');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`user_id`, `permission_id`) VALUES
(4, 6),
(4, 7),
(4, 8),
(4, 9),
(4, 10),
(4, 11),
(4, 12),
(4, 13),
(4, 14),
(4, 15),
(4, 16),
(4, 17),
(4, 18),
(6, 6),
(6, 7),
(6, 12),
(6, 18),
(7, 6),
(7, 7),
(7, 11),
(7, 18),
(9, 6),
(9, 7),
(9, 11),
(9, 12),
(9, 18);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 1 day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
(1, 1, 'c0bc0ed571b82f54d68fa7125e7bd676a1eea4ffea8d6fa53103ec39dc7057ae', '192.168.1.7', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', '2025-07-11 02:44:15', '2025-08-09 20:44:15'),
(2, 7, 'cd6f955b7c3cea09eadcf47a843851a1951c830dc679b331b8a8c14ab4c6b627', '192.168.1.7', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', '2025-07-11 03:05:33', '2025-08-09 21:05:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `barcode` (`barcode`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `user_id` (`user_id`);


--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`user_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--


--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`),
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`);

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
