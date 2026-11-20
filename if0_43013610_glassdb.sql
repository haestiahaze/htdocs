-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql206.infinityfree.com
-- Generation Time: Nov 17, 2026 at 08:15 AM
-- Server version: 11.4.13-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_43013610_glassdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_escalations`
--

CREATE TABLE `ai_escalations` (
  `id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `question` text NOT NULL,
  `answer` text DEFAULT NULL,
  `status` enum('open','answered','closed') NOT NULL DEFAULT 'open',
  `add_to_knowledge` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `answered_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_knowledge`
--

CREATE TABLE `ai_knowledge` (
  `id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `content` text NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ai_knowledge`
--

INSERT INTO `ai_knowledge` (`id`, `title`, `category`, `content`, `enabled`, `updated_by`, `updated_at`) VALUES
(1, 'Services offered', 'Services', 'We provide glass and aluminum fabrication, measurements, delivery, and installation.', 1, 1, '2026-10-19 02:16:09'),
(2, 'Project process', 'Process', 'Typical process: submit an inquiry, review measurements, receive a quotation, confirm scope and schedule, fabrication, installation, and final inspection.', 1, 1, '2026-10-19 02:16:09'),
(3, 'Materials', 'Materials', 'Common materials include aluminum profiles, glass panels, seals, rollers, handles, and stainless hardware. Final material selection depends on project requirements.', 1, 1, '2026-10-19 02:16:09'),
(4, 'Pricing and timeline', 'FAQ', 'Pricing and timeline depend on measurements, material choice, site conditions, and project scope. A manager must confirm project-specific pricing and schedule.', 1, 1, '2026-10-19 02:16:09');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `personnel_id` int(11) NOT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `project_id`, `task_id`, `personnel_id`, `assigned_at`) VALUES
(1, 1, 1, 2, '2026-10-20 06:22:12'),
(2, 1, NULL, 2, '2026-10-20 06:23:37');

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` int(11) NOT NULL,
  `inquiry_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(120) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `attachments`
--

INSERT INTO `attachments` (`id`, `inquiry_id`, `project_id`, `uploaded_by`, `file_path`, `original_name`, `mime_type`, `created_at`) VALUES
(1, 5, NULL, 8, 'uploads/5e4d2d67e09762e1d38eb2c2b3c1e149.jpg', 'd94d5a9b-c3b8-47ac-92e3-6cbdbf24e9ea.jpg', 'image/jpeg', '2026-10-23 12:15:13');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(180) NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `created_at`) VALUES
(1, 4, 'Inquiry submitted', 'inquiry', 2, 'Windows and doors', '2026-10-19 02:21:49'),
(2, 1, 'Inquiry status updated', 'inquiry', 2, 'under_review', '2026-10-20 00:20:38'),
(3, 1, 'Personnel permissions updated', 'user', 2, 'can_record_materials=1; active=1', '2026-10-20 01:39:16'),
(4, 1, 'Quotation created', 'quotation', 1, '', '2026-10-20 01:44:40'),
(5, 1, 'Quotation sent', 'quotation', 1, '', '2026-10-20 01:44:56'),
(6, 1, 'Personnel permissions updated', 'user', 2, 'can_record_materials=1; active=1', '2026-10-20 01:45:14'),
(7, 1, 'Inquiry status updated', 'inquiry', 2, 'accepted', '2026-10-20 01:46:53'),
(8, 1, 'Quotation created', 'quotation', 2, '', '2026-10-20 01:49:20'),
(9, 1, 'Quotation sent', 'quotation', 2, '', '2026-10-20 01:49:26'),
(10, 1, 'Personnel permissions updated', 'user', 2, 'can_record_materials=0; active=1', '2026-10-20 05:29:16'),
(11, 5, 'Inquiry submitted', 'inquiry', 3, 'Windows and doors', '2026-10-20 06:13:49'),
(12, 5, 'Inquiry submitted', 'inquiry', 4, 'Windows and doors', '2026-10-20 06:14:09'),
(13, 1, 'Inquiry status updated', 'inquiry', 4, 'new', '2026-10-20 06:15:12'),
(14, 1, 'Quotation created', 'quotation', 3, '', '2026-10-20 06:18:40'),
(15, 1, 'Quotation sent', 'quotation', 3, '', '2026-10-20 06:18:50'),
(16, 5, 'Client quotation response', 'quotation', 3, 'accepted', '2026-10-20 06:19:04'),
(17, 1, 'Accepted quotation converted to project', 'project', 1, '', '2026-10-20 06:20:49'),
(18, 1, 'Task created', 'project', 1, 'Measurement', '2026-10-20 06:22:02'),
(19, 1, 'Personnel assigned', 'project', 1, 'personnel=2', '2026-10-20 06:22:12'),
(20, 1, 'Task created', 'project', 1, 'Fabrication', '2026-10-20 06:22:37'),
(21, 1, 'Task created', 'project', 1, 'Installation', '2026-10-20 06:23:21'),
(22, 1, 'Personnel assigned', 'project', 1, 'personnel=2', '2026-10-20 06:23:37'),
(23, 2, 'Task progress updated', 'task', 1, 'in_progress', '2026-10-20 06:24:55'),
(24, 1, 'Personnel permissions updated', 'user', 2, 'can_record_materials=1; active=1', '2026-10-20 06:25:23'),
(25, 2, 'Task progress updated', 'task', 1, 'in_progress', '2026-10-20 06:27:47'),
(26, 2, 'Task progress updated', 'task', 1, 'in_progress', '2026-10-20 06:28:02'),
(27, 2, 'Task progress updated', 'task', 1, 'in_progress', '2026-10-20 06:30:24'),
(28, 2, 'Task progress updated', 'task', 1, 'completed', '2026-10-20 06:30:50'),
(29, 4, 'Client quotation response', 'quotation', 2, 'accepted', '2026-10-21 12:46:33'),
(30, 1, 'Inquiry status updated', 'inquiry', 4, 'accepted', '2026-10-21 12:56:37'),
(31, 1, 'Inquiry status updated', 'inquiry', 3, 'accepted', '2026-10-21 12:57:08'),
(32, 1, 'Inquiry status updated', 'inquiry', 4, 'accepted', '2026-10-21 13:30:32'),
(33, 1, 'Inquiry status updated', 'inquiry', 2, 'quoted', '2026-10-21 13:30:39'),
(34, 1, 'Quotation created', 'quotation', 4, '', '2026-10-21 13:31:58'),
(35, 1, 'Project settings updated', 'project', 1, 'status=scheduled; client_visible=1', '2026-10-21 13:32:24'),
(36, 8, 'Inquiry submitted', 'inquiry', 5, 'Windows and doors', '2026-10-23 12:15:13'),
(37, 3, 'Inquiry submitted', 'inquiry', 6, 'Other', '2026-11-03 07:21:12'),
(38, 3, 'Inquiry submitted', 'inquiry', 7, 'Windows and doors', '2026-11-03 07:34:01'),
(39, 9, 'Inquiry submitted', 'inquiry', 8, 'Windows and doors', '2026-11-03 12:04:33'),
(40, 1, 'Inquiry status updated', 'inquiry', 8, 'accepted', '2026-11-03 12:09:27'),
(41, 11, 'Inquiry submitted', 'inquiry', 9, 'Windows and doors', '2026-11-11 11:39:48'),
(42, 1, 'Inquiry status updated', 'inquiry', 9, 'accepted', '2026-11-11 11:41:24'),
(43, 1, 'Project settings updated', 'project', 1, 'status=scheduled; client_visible=1', '2026-11-11 11:43:23'),
(44, 3, 'Inquiry submitted', 'inquiry', 10, 'Windows and doors', '2026-11-12 06:24:33'),
(45, 1, 'Quotation created', 'quotation', 5, '', '2026-11-12 06:33:46'),
(46, 1, 'Project settings updated', 'project', 1, 'status=scheduled; client_visible=1', '2026-11-12 06:34:23'),
(47, 1, 'Inquiry status updated', 'inquiry', 10, 'new', '2026-11-12 06:36:13'),
(48, 1, 'Quotation sent', 'quotation', 5, '', '2026-11-12 06:38:00'),
(49, 3, 'Client quotation response', 'quotation', 5, 'revision_requested', '2026-11-12 06:40:09');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `phone`, `address`, `created_at`) VALUES
(1, 3, '09170000000', 'Demo address, Philippines', '2026-10-19 02:16:09'),
(2, 4, NULL, NULL, '2026-10-19 02:19:22'),
(3, 5, NULL, NULL, '2026-10-20 06:12:00'),
(4, 6, NULL, NULL, '2026-10-20 11:59:30'),
(5, 7, NULL, NULL, '2026-10-21 00:33:04'),
(6, 8, NULL, NULL, '2026-10-21 12:27:18'),
(7, 9, NULL, NULL, '2026-11-03 12:03:50'),
(8, 10, NULL, NULL, '2026-11-09 12:18:06'),
(9, 11, NULL, NULL, '2026-11-11 11:38:57');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_type` varchar(120) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(255) NOT NULL,
  `preferred_timeline` varchar(150) DEFAULT NULL,
  `budget_range` varchar(120) DEFAULT NULL,
  `status` enum('new','under_review','quoted','accepted','declined','converted_to_project') NOT NULL DEFAULT 'new',
  `manager_response` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `client_id`, `project_type`, `description`, `location`, `preferred_timeline`, `budget_range`, `status`, `manager_response`, `created_at`) VALUES
(1, 3, 'Glass and aluminum', 'Sliding window and door fabrication', 'General Santos City', 'Within 4 weeks', '?30,000–?50,000', 'under_review', NULL, '2026-10-19 02:16:09'),
(2, 4, 'Windows and doors', 'Materials', 'Makilala, North Cotabato', '2weeks', '20,000', 'quoted', '', '2026-10-19 02:21:49'),
(3, 5, 'Windows and doors', 'Sliding window', 'New Caridad, Tulunan', '2weeks', '10,000', 'accepted', 'please wait for a moment', '2026-10-20 06:13:49'),
(4, 5, 'Windows and doors', 'Sliding window', 'New Caridad, Tulunan', '2weeks', '10,000', 'accepted', 'Please wait for a moment.', '2026-10-20 06:14:09'),
(5, 8, 'Windows and doors', 'size 4 inches', 'Makilala', '1 week', '1000', 'new', NULL, '2026-10-23 12:15:13'),
(6, 3, 'Other', 'Car Windshield \r\nToyota Hilux', 'Purok 2, Saguing, Makilala, Cotabato', '2 weeks', '10000', 'new', NULL, '2026-11-03 07:21:12'),
(7, 3, 'Windows and doors', 'Sliding window', 'Makilala, North Cotabato', '2weeks', '10,000', 'new', NULL, '2026-11-03 07:34:01'),
(8, 9, 'Windows and doors', 'Sliding Windows', 'Makilala, North Cotabato', '3weeks', '10,000', 'accepted', 'Are you sure your order?', '2026-11-03 12:04:33'),
(9, 11, 'Windows and doors', 'Sliding window', 'Makilala, North Cotabato', '3weeks', '20,000', 'accepted', 'Please wait for a moment.', '2026-11-11 11:39:48'),
(10, 3, 'Windows and doors', 'window,', 'conception, makilala, cotabato', 'november 12, 2026; 6pm', NULL, 'quoted', '', '2026-11-12 06:24:33');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `name` varchar(180) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(40) NOT NULL DEFAULT 'pcs',
  `quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reorder_point` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `supplier` varchar(180) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `name`, `category`, `unit`, `quantity`, `reorder_point`, `unit_cost`, `supplier`, `updated_at`) VALUES
(1, 'Aluminum profile', 'Aluminum', 'length', '45.00', '10.00', '420.00', 'Demo Supplier', '2026-10-19 02:16:09'),
(2, 'Clear tempered glass', 'Glass', 'sqm', '22.00', '8.00', '1850.00', 'Demo Supplier', '2026-10-19 02:16:09'),
(3, 'Stainless screws', 'Hardware', 'box', '12.00', '4.00', '250.00', 'Demo Supplier', '2026-10-19 02:16:09');

-- --------------------------------------------------------

--
-- Table structure for table `issue_reports`
--

CREATE TABLE `issue_reports` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `reported_by` int(11) NOT NULL,
  `report_type` enum('delay','damage','shortage','other') NOT NULL,
  `reason` text NOT NULL,
  `quantity` decimal(12,2) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `status` enum('open','reviewed','resolved') NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'New project inquiry', 'Grace Ledama submitted Windows and doors.', 0, '2026-10-19 02:21:49'),
(2, 4, 'Inquiry updated', 'Your inquiry #2 is now under_review. are you sure about this projects?', 0, '2026-10-20 00:20:38'),
(3, 4, 'Quotation ready', 'Quotation #1 is ready for your review.', 0, '2026-10-20 01:44:56'),
(4, 4, 'Inquiry updated', 'Your inquiry #2 is now accepted. ', 0, '2026-10-20 01:46:53'),
(5, 4, 'Quotation ready', 'Quotation #2 is ready for your review.', 0, '2026-10-20 01:49:26'),
(6, 1, 'New project inquiry', 'Angela Arsolon submitted Windows and doors.', 0, '2026-10-20 06:13:49'),
(7, 1, 'New project inquiry', 'Angela Arsolon submitted Windows and doors.', 0, '2026-10-20 06:14:09'),
(8, 5, 'Inquiry updated', 'Your inquiry #4 is now new. Please wait for a moment.', 0, '2026-10-20 06:15:12'),
(9, 5, 'Quotation ready', 'Quotation #3 is ready for your review.', 0, '2026-10-20 06:18:50'),
(10, 1, 'Quotation response received', 'A client responded to quotation #3.', 0, '2026-10-20 06:19:04'),
(11, 2, 'New project assignment', 'You have been assigned to project #1.', 0, '2026-10-20 06:22:12'),
(12, 2, 'New project assignment', 'You have been assigned to project #1.', 0, '2026-10-20 06:23:37'),
(13, 1, 'Quotation response received', 'A client responded to quotation #2.', 0, '2026-10-21 12:46:33'),
(14, 5, 'Inquiry updated', 'Your inquiry #4 is now accepted. Please wait for a moment.', 0, '2026-10-21 12:56:37'),
(15, 5, 'Inquiry updated', 'Your inquiry #3 is now accepted. please wait for a moment', 0, '2026-10-21 12:57:08'),
(16, 5, 'Inquiry updated', 'Your inquiry #4 is now accepted. Please wait for a moment.', 0, '2026-10-21 13:30:32'),
(17, 4, 'Inquiry updated', 'Your inquiry #2 is now quoted. ', 0, '2026-10-21 13:30:39'),
(18, 1, 'New project inquiry', 'irish delos reyes submitted Windows and doors.', 0, '2026-10-23 12:15:13'),
(19, 1, 'New project inquiry', 'Demo Client submitted Other.', 0, '2026-11-03 07:21:12'),
(20, 1, 'New project inquiry', 'Demo Client submitted Windows and doors.', 0, '2026-11-03 07:34:01'),
(21, 1, 'New project inquiry', 'Grace Ledama submitted Windows and doors.', 0, '2026-11-03 12:04:33'),
(22, 9, 'Inquiry updated', 'Your inquiry #8 is now accepted. Are you sure your order?', 0, '2026-11-03 12:09:27'),
(23, 1, 'New project inquiry', 'Aikee Arsolon submitted Windows and doors.', 0, '2026-11-11 11:39:48'),
(24, 11, 'Inquiry updated', 'Your inquiry #9 is now accepted. Please wait for a moment.', 0, '2026-11-11 11:41:24'),
(25, 1, 'New project inquiry', 'Demo Client submitted Windows and doors.', 0, '2026-11-12 06:24:33'),
(26, 3, 'Inquiry updated', 'Your inquiry #10 is now new. ', 0, '2026-11-12 06:36:13'),
(27, 3, 'Quotation ready', 'Quotation #5 is ready for your review.', 0, '2026-11-12 06:38:00'),
(28, 1, 'Quotation response received', 'A client responded to quotation #5.', 0, '2026-11-12 06:40:09');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `inquiry_id` int(11) DEFAULT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `status` enum('planning','scheduled','fabrication','installation','on_hold','completed','cancelled') NOT NULL DEFAULT 'planning',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `client_visible` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `inquiry_id`, `quotation_id`, `client_id`, `title`, `description`, `location`, `status`, `start_date`, `due_date`, `client_visible`, `created_at`) VALUES
(1, 4, 3, 5, 'Windows and doors', 'Sliding window', 'New Caridad, Tulunan', 'scheduled', '2026-10-21', '2026-10-21', 1, '2026-10-20 06:20:49');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `status` enum('requested','ordered','received','cancelled') NOT NULL DEFAULT 'requested',
  `expected_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `inquiry_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `status` enum('draft','sent','accepted','declined','revision_requested','approved') NOT NULL DEFAULT 'draft',
  `terms` text DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `inquiry_id`, `client_id`, `status`, `terms`, `valid_until`, `total`, `created_by`, `created_at`) VALUES
(1, 2, 4, 'sent', '', '2026-10-20', '2250.00', 1, '2026-10-20 01:44:40'),
(2, 2, 4, 'accepted', '', '2026-10-20', '33600.00', 1, '2026-10-20 01:49:20'),
(3, 4, 5, 'approved', '', '2026-10-31', '3250.00', 1, '2026-10-20 06:18:40'),
(4, 4, 5, 'draft', '', '2026-10-21', '0.00', 1, '2026-10-21 13:31:58'),
(5, 10, 3, 'revision_requested', '6 pm', '2026-11-12', '0.00', 1, '2026-11-12 06:33:46');

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `item_type` enum('material','labor','installation','other') NOT NULL DEFAULT 'other',
  `description` varchar(255) NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `quotation_items`
--

INSERT INTO `quotation_items` (`id`, `quotation_id`, `item_type`, `description`, `quantity`, `unit_price`, `line_total`) VALUES
(1, 1, 'material', 'clear glass', '1.00', '1500.00', '1500.00'),
(2, 1, 'material', 'aluminum 6mm', '2.00', '250.00', '500.00'),
(3, 1, 'labor', 'personnel', '1.00', '250.00', '250.00'),
(4, 2, 'material', 'clear glass', '5.00', '5500.00', '27500.00'),
(5, 2, 'material', 'Aluminum 6mm', '6.00', '600.00', '3600.00'),
(6, 2, 'material', 'aluminum 4mm', '5.00', '500.00', '2500.00'),
(7, 3, 'material', 'Glass 4x4', '2.00', '1500.00', '3000.00'),
(8, 3, 'labor', 'Personnel', '1.00', '250.00', '250.00');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

CREATE TABLE `stock_transactions` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_type` enum('received','issued','consumed','damaged','returned','adjustment') NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(180) NOT NULL,
  `contact_person` varchar(120) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  `due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `project_id`, `title`, `description`, `status`, `due_date`, `notes`, `updated_at`) VALUES
(1, 1, 'Measurement', 'Finish it by the same day so Fabrication can be started', 'completed', '2026-10-21', 'Measurements:\r\n12x6x25\r\nClear Glass\r\nAluminum\r\n 4pcs-4x4\r\n2-2x6', '2026-10-20 06:30:50'),
(2, 1, 'Fabrication', 'Assemble the product', 'not_started', '2026-10-22', NULL, '2026-10-20 06:22:37'),
(3, 1, 'Installation', 'Install the product on New Caridad, Tulunan, North Cotabato', 'not_started', '2026-10-23', NULL, '2026-10-20 06:23:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Client','Personnel','Manager') NOT NULL DEFAULT 'Client',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `can_record_materials` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `is_active`, `can_record_materials`, `created_at`) VALUES
(1, 'Demo', 'Manager', 'manager@example.com', '$2y$12$ur9XHWyFdGeI7hMKrIZs/.GyDuXf15Gqh2t7QIPFJNHnrOUVWf/S2', 'Manager', 1, 1, '2026-10-19 02:16:09'),
(2, 'Demo', 'Personnel', 'personnel@example.com', '$2y$12$ur9XHWyFdGeI7hMKrIZs/.GyDuXf15Gqh2t7QIPFJNHnrOUVWf/S2', 'Personnel', 1, 1, '2026-10-19 02:16:09'),
(3, 'Demo', 'Client', 'client@example.com', '$2y$12$ur9XHWyFdGeI7hMKrIZs/.GyDuXf15Gqh2t7QIPFJNHnrOUVWf/S2', 'Client', 1, 0, '2026-10-19 02:16:09'),
(4, 'Grace', 'Ledama', 'gela@gmail.com', '$2y$12$Rqv3fSTduUM.NdayghDeY.Up97VEbQC0t00TAAT53AXOWXLpfJfyi', 'Client', 1, 0, '2026-10-19 02:19:22'),
(5, 'Angela', 'Arsolon', 'ange@gmail.com', '$2y$12$/2fNR.LWlT83osH00BNuFeOU1bNxCsMFM0PuB3/xJqb8tc6eUXSOi', 'Client', 1, 0, '2026-10-20 06:12:00'),
(6, 'Kim', 'Oribia', 'kimstevenoribia14@gmail.com', '$2y$12$vtaYdd36FOU8F2gET33IZeQYWULGwFwLZ43/8BnOLZZfxj9BCISVa', 'Client', 1, 0, '2026-10-20 11:59:30'),
(7, 'jashmin', 'ledama', 'jashmin@gmail.com', '$2y$12$q8oU44M7zf3OunrkFSQlJeOdGasNCg3h0QuNilQdji8ZNeefnLHBu', 'Client', 1, 0, '2026-10-21 00:33:04'),
(8, 'irish', 'delos reyes', 'idelosreyes@gmail.com', '$2y$12$N.NATpii.1IpWMhbej18ReLzrlQxITw7nhwLME9u5svGIIJbOiBSi', 'Client', 1, 0, '2026-10-21 12:27:18'),
(9, 'Grace', 'Ledama', 'grace@gmail.com', '$2y$12$cCfZdZDxSYnxQsORZHN4nu5DmG884Zvp9LuxCpjfKwIvJGNjstLkq', 'Client', 1, 0, '2026-11-03 12:03:50'),
(10, 'Angela', 'Arsolon', 'angelagracearsolon1@gmail.com', '$2y$12$wbv6yQ8vjuYmBqVYWr4aDOk1s7OclZBAsFi8LU3PGUNlekU3NE1zm', 'Client', 1, 0, '2026-11-09 12:18:06'),
(11, 'Aikee', 'Arsolon', 'aikee@gmail.com', '$2y$12$30ux5ewYsZYNG95Oc7CLDu9wKrsf/aJFSddcEs164SZws6oE57Xky', 'Client', 1, 0, '2026-11-11 11:38:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_escalations`
--
ALTER TABLE `ai_escalations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `ai_knowledge`
--
ALTER TABLE `ai_knowledge`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inquiry_id` (`inquiry_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `issue_reports`
--
ALTER TABLE `issue_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `reported_by` (`reported_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inquiry_id` (`inquiry_id`),
  ADD KEY `quotation_id` (`quotation_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inquiry_id` (`inquiry_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`);

--
-- Indexes for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

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
-- AUTO_INCREMENT for table `ai_escalations`
--
ALTER TABLE `ai_escalations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_knowledge`
--
ALTER TABLE `ai_knowledge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `issue_reports`
--
ALTER TABLE `issue_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
