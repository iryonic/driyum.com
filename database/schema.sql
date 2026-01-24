-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 24, 2026 at 06:39 AM
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
-- Database: `driyum_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `abandoned_carts`
--

CREATE TABLE `abandoned_carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cart_data` text NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_reminded` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `abandoned_carts`
--

INSERT INTO `abandoned_carts` (`id`, `user_id`, `cart_data`, `last_updated`, `is_reminded`, `created_at`) VALUES
(3, 1, '{\"12\":2}', '2026-01-23 15:53:15', 0, '2026-01-23 11:48:30'),
(4, 2, '{\"11\":6,\"10\":6,\"12\":1}', '2026-01-23 18:08:53', 0, '2026-01-23 15:56:37');

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `link` varchar(255) DEFAULT '',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES
(1, 'New Order #ORD-69735E6875CBB received from Guest - ₹2,783.00', 'order', 'orders.php?id=14', 1, '2026-01-23 11:41:28');

-- --------------------------------------------------------

--
-- Table structure for table `affiliates`
--

CREATE TABLE `affiliates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `commission_rate` decimal(5,2) DEFAULT 10.00,
  `discount_percentage` decimal(5,2) DEFAULT 10.00,
  `bank_details` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `status` enum('active','suspended') DEFAULT 'active',
  `total_earnings` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `affiliates`
--

INSERT INTO `affiliates` (`id`, `user_id`, `code`, `commission_rate`, `discount_percentage`, `bank_details`, `is_approved`, `status`, `total_earnings`, `created_at`, `updated_at`) VALUES
(1, 2, 'iry10', 10.00, 10.00, NULL, 1, 'active', 460.00, '2026-01-20 13:50:55', '2026-01-22 15:46:43');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `message`, `is_active`, `end_date`, `created_at`, `updated_at`) VALUES
(2, 'Free  shipping on orders abpve 500$ . Limited time offer', 1, NULL, '2026-01-17 08:53:47', '2026-01-17 08:53:47');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `parent_id`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Fruits', 'fruits', 'Apple, kiwi, banana', 'assets/images/categories/cat_696bc21d5e319.webp', NULL, 1, 1, '2026-01-16 18:57:45', '2026-01-17 17:08:45'),
(2, 'Vegetable', 'vegetable', 'Al hach and vangan hach', 'assets/images/categories/cat_696bc44544ce2.webp', NULL, 1, 2, '2026-01-16 18:57:45', '2026-01-17 17:17:57'),
(3, 'Combos', 'combos', 'Value packs and gift hampers', 'assets/images/categories/cat_696bc434cb3f2.webp', NULL, 1, 3, '2026-01-16 18:57:45', '2026-01-17 17:17:40'),
(4, 'Gift Hampers', 'gift-hampers', 'Premium gift hampers', 'assets/images/categories/cat_696b364f8fce6.png', NULL, 0, 4, '2026-01-16 18:57:45', '2026-01-17 14:47:53'),
(5, 'hampers', 'hampers', 'gifting hamper by iry', 'assets/images/categories/cat_696b367511da3.png', NULL, 0, 3, '2026-01-17 07:12:53', '2026-01-17 14:47:53');

-- --------------------------------------------------------

--
-- Table structure for table `contact_info`
--

CREATE TABLE `contact_info` (
  `id` int(11) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `whatsapp` varchar(50) DEFAULT NULL,
  `instagram` varchar(100) DEFAULT NULL,
  `facebook` varchar(100) DEFAULT NULL,
  `map_iframe` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_info`
--

INSERT INTO `contact_info` (`id`, `address`, `phone`, `email`, `whatsapp`, `instagram`, `facebook`, `map_iframe`, `updated_at`) VALUES
(1, '123 Digital Valley, Srinagar, J&K, India', '+91 98765 43210', 'contact@driyum.com', '919876543210', 'driyum_official', 'driyum', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3305.823907722744!2d74.7973711!3d34.0836558!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x38e18f26f743f0ed%3A0x6b4474327680f48b!2sSrinagar!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin\" width=\"100%\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\"></iframe>', '2026-01-22 15:02:19');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(2, 'irfan manzoor', 'drop.mail.iry@gmail.com', 'try', 'trying contact mesagehttp://localhost/newdry/contact.phpadd the below  script is in  the head tag of every  frontnend like index and all files of  public folder ', '2026-01-22 13:02:45');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed') DEFAULT 'percentage',
  `value` decimal(10,2) NOT NULL,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `min_order_value` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `expiry_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `type`, `value`, `max_discount`, `min_order_value`, `usage_limit`, `usage_count`, `expiry_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'percentage', 10.00, NULL, 299.00, 100, 0, '2026-02-16 00:27:45', 1, '2026-01-16 18:57:45', '2026-01-16 18:57:45'),
(2, 'FIRSTORDER', 'percentage', 15.00, NULL, 499.00, 50, 0, '2026-02-16 00:27:45', 1, '2026-01-16 18:57:45', '2026-01-16 18:57:45'),
(3, 'SAVE100', 'fixed', 100.00, NULL, 999.00, 200, 0, '2026-02-16 00:27:45', 1, '2026-01-16 18:57:45', '2026-01-16 18:57:45'),
(4, 'RAMADAN2026', 'fixed', 200.00, 0.00, 1000.00, 1000, 0, NULL, 1, '2026-01-17 17:07:55', '2026-01-23 10:27:07');

-- --------------------------------------------------------

--
-- Table structure for table `hero_slides`
--

CREATE TABLE `hero_slides` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `image` varchar(500) NOT NULL,
  `cta_text` varchar(100) DEFAULT NULL,
  `cta_link` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hero_slides`
--

INSERT INTO `hero_slides` (`id`, `title`, `subtitle`, `image`, `cta_text`, `cta_link`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, '100% Real Fruit Snacks', 'No Oil. No Preservatives. Just Pure Goodness.', '/assets/images/hero/slide1.jpg', 'Shop Now', '/shop.php', 1, 1, '2026-01-16 18:57:45', '2026-01-16 18:57:45'),
(2, 'Traditional Hokh Suin', 'Experience the authentic taste of Kashmir', '/assets/images/hero/slide2.jpg', 'Explore', '/shop.php?category=hokh-suin', 1, 2, '2026-01-16 18:57:45', '2026-01-16 18:57:45'),
(3, 'Premium Gift Hampers', 'Perfect gifts for your loved ones', '/assets/images/hero/slide3.jpg', 'View Hampers', '/shop.php?category=gift-hampers', 1, 3, '2026-01-16 18:57:45', '2026-01-16 18:57:45');

-- --------------------------------------------------------

--
-- Table structure for table `homepage_sections`
--

CREATE TABLE `homepage_sections` (
  `id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `heading` varchar(255) DEFAULT NULL,
  `subheading` text DEFAULT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `cta_text` varchar(100) DEFAULT NULL,
  `cta_link` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `homepage_sections`
--

INSERT INTO `homepage_sections` (`id`, `section_name`, `heading`, `subheading`, `media_url`, `video_url`, `cta_text`, `cta_link`, `updated_at`) VALUES
(1, 'video_brand_story', 'AMARSINGH REVIWS ', 'Featuring IRY', 'assets/images/uploads/vid_thumb_696e35fd83b1a_thumb.jpeg', 'assets/videos/brand_v_696e35fd8ca2a.mp4', NULL, NULL, '2026-01-19 13:47:41'),
(2, 'hero', 'YOUR NEW \nHEALTHY HABIT.', 'Absolutely No Sugar. 100% Guilt-Free.', 'assets/images/hero.jpg', NULL, 'Start Crunching', 'shop.php', '2026-01-18 16:40:19');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`id`, `email`, `is_active`, `subscribed_at`) VALUES
(1, 'drop.mail.iry@gmail.com', 1, '2026-01-17 09:28:55'),
(3, 'faiqbhat123@gmail.com', 1, '2026-01-23 11:18:53'),
(4, 'masrat5qadir@gmail.com', 1, '2026-01-23 11:18:53'),
(5, 'aymenraina@gmail.com', 1, '2026-01-23 11:18:53'),
(6, 'saqibillahi@gmail.com', 1, '2026-01-23 11:18:53'),
(7, 'shifashaber@gmail.com', 1, '2026-01-23 11:18:53'),
(8, 'shahaisha.004@gmail.com', 1, '2026-01-23 11:18:53'),
(9, 'basharathooria@gmail.com', 1, '2026-01-23 11:18:53'),
(10, 'gsajad609@gmail.com', 1, '2026-01-23 11:18:53'),
(11, 'sunsetchalet08@gmail.com', 1, '2026-01-23 11:18:53'),
(12, 'mirsehraan9@gmail.com', 1, '2026-01-23 11:18:53'),
(13, 'ghouleyepatch31@gmail.com', 1, '2026-01-23 11:18:53'),
(14, 'riyanjavaid9@gmail.com', 1, '2026-01-23 11:18:53'),
(15, 'friedmomo.com@gmail.com', 1, '2026-01-23 11:18:53'),
(16, 'shakeebfarhat40@gmail.com', 1, '2026-01-23 11:18:53'),
(17, 'stayhumble7865@gmail.com', 1, '2026-01-23 11:18:53'),
(18, 'nuzeefakhan612@gmail.com', 1, '2026-01-23 11:18:53'),
(19, 'owaisrashad075@gmail.com', 1, '2026-01-23 11:18:53'),
(20, 'rahilmanzoorsangeen@gmail.com', 1, '2026-01-23 11:18:53'),
(21, 'xainabbhat321@gmail.com', 1, '2026-01-23 11:18:53'),
(22, 'bhatbhatfaiq@gmail.com', 1, '2026-01-23 11:18:53'),
(23, 'ahmadsaadtramboo@gmail.com', 1, '2026-01-23 11:18:53'),
(24, 'bhatmehnaz37@gmail.com', 1, '2026-01-23 11:18:53'),
(25, 'aaminaaltaf868@gmail.com', 1, '2026-01-23 11:18:53'),
(26, 'sbhtshbr@gmail.com', 1, '2026-01-23 11:18:53'),
(27, 'hayamalik07886@gmail.com', 1, '2026-01-23 11:18:53'),
(28, 'jannataslam505@gmail.com', 1, '2026-01-23 11:18:53'),
(29, 'ajmeenferoz@gmail.com', 1, '2026-01-23 11:18:53'),
(30, 'ajmeenagway@gmail.com', 1, '2026-01-23 11:18:53'),
(31, 'kashfiyah52@gmail.com', 1, '2026-01-23 11:18:53'),
(32, 'saimawani260@gmail.com', 1, '2026-01-23 11:18:53'),
(33, 'mxargar9@gmail.com', 1, '2026-01-23 11:18:53'),
(34, 'dalpafrheen@gmail.com', 1, '2026-01-23 11:18:53'),
(35, 'badiahussainbhat@gmail.com', 1, '2026-01-23 11:18:53'),
(36, 'ikhlaasmanzoor@gmail.com', 1, '2026-01-23 11:18:53'),
(37, 'insha8105@gmail.com', 1, '2026-01-23 11:18:53'),
(38, 'imaadmanzoor8@gmail.com', 1, '2026-01-23 11:18:53'),
(39, 'shawlyasmeena@gmail.com', 1, '2026-01-23 11:18:53'),
(40, 'my.artgallery52@gmail.com', 1, '2026-01-23 11:18:53'),
(41, 'mirafsha100@gmail.com', 1, '2026-01-23 11:18:53'),
(42, 'hafsabashirhafsa7@gmail.com', 1, '2026-01-23 11:18:53'),
(43, 'hafsahafsa579900@gmail.com', 1, '2026-01-23 11:18:53'),
(44, 'umertariqrather@gmail.com', 1, '2026-01-23 11:18:53'),
(45, 'moinmj7@gmail.com', 1, '2026-01-23 11:18:53'),
(46, 'mariyamushtaqsagar@gmail.com', 1, '2026-01-23 11:18:53'),
(47, 'sheezanfayaz12@gmail.com', 1, '2026-01-23 11:18:53'),
(48, 'sheezanfayaz504@gmail.com', 1, '2026-01-23 11:18:53'),
(49, 'yahyamir352@gmail.com', 1, '2026-01-23 11:18:53'),
(50, 'kmuizz499@gmail.com', 1, '2026-01-23 11:18:53'),
(51, 'basiqrather4@gmail.com', 1, '2026-01-23 11:18:53'),
(52, 'im.inaam.01@gmail.com', 1, '2026-01-23 11:18:53'),
(53, 'toibabhat1234@gmail.com', 1, '2026-01-23 11:18:53'),
(54, 'zahidkhuroo625@gmail.com', 1, '2026-01-23 11:18:53'),
(55, 'rafidjan0@gmail.com', 1, '2026-01-23 11:18:53'),
(56, 'rafidjan@gmail.com', 1, '2026-01-23 11:18:53'),
(57, 'abcd@gmail.com', 1, '2026-01-23 11:18:53'),
(58, 'rehanmajeed318@gmail.com', 1, '2026-01-23 11:18:53'),
(59, 'abrar8173@gmail.com', 1, '2026-01-23 11:18:53'),
(60, 'kamranwani077@gmail.com', 1, '2026-01-23 11:18:53'),
(61, 'taufeeq322ahmad@gmail.com', 1, '2026-01-23 11:18:53'),
(62, 'muhammedumer0007@gmail.com', 1, '2026-01-23 11:18:53'),
(63, 'maribmuzafar1@gmail.com', 1, '2026-01-23 11:18:53'),
(64, 'usmaan826@gmail.com', 1, '2026-01-23 11:18:53'),
(65, 'bilalshah4732@gmail.com', 1, '2026-01-23 11:18:53'),
(66, 'umer.gm.bhat0@gmail.com', 1, '2026-01-23 11:18:53'),
(67, 'abc@gmail.com', 1, '2026-01-23 11:18:53'),
(68, 'faaniqhussain@gmail.com', 1, '2026-01-23 11:18:53'),
(69, 'waniarham007@gmail.com', 1, '2026-01-23 11:18:53'),
(70, 'babaronaq66@gmail.com', 1, '2026-01-23 11:18:53'),
(71, 'maheenbhat557@gmail.com', 1, '2026-01-23 11:18:53'),
(72, 'sumaya@gmail.com', 1, '2026-01-23 11:18:53'),
(73, 'friedmomos.com@gmail.com', 1, '2026-01-23 11:18:53'),
(74, 'waniyamin07@gmail.com', 1, '2026-01-23 11:18:53'),
(75, 'mirmoumin.ahmad@gmail.com', 1, '2026-01-23 11:18:53'),
(76, 'whiteverses7@gmail.com', 1, '2026-01-23 11:18:53'),
(77, 'justttfrankie@gmail.com', 1, '2026-01-23 11:18:53'),
(78, 'xhafsa3@gmail.com', 1, '2026-01-23 11:18:53'),
(79, 'arhamchowdary14@gmail.com', 1, '2026-01-23 11:18:53'),
(80, 'lonetalal@gmail.com', 1, '2026-01-23 11:18:53'),
(81, 'faizanbeigh06@gmail.com', 1, '2026-01-23 11:18:53'),
(82, 'fahadtariqbhatt123@gmail.com', 1, '2026-01-23 11:18:53'),
(83, 'qaimqayoom1@gmail.com', 1, '2026-01-23 11:18:53'),
(84, 'wani19aazim@gmail.com', 1, '2026-01-23 11:18:53'),
(85, 'bfuzail606@gmail.com', 1, '2026-01-23 11:18:53'),
(86, 'bhat.faiq10@gmail.com', 1, '2026-01-23 11:18:53'),
(87, 'hire.iry@gmail.com', 1, '2026-01-23 11:18:53');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `affiliate_id` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `affiliate_commission` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` varchar(50) DEFAULT 'pending',
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `order_status` varchar(50) DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `tracking_note` text DEFAULT NULL,
  `dispatch_date` datetime DEFAULT NULL,
  `shipping_method_id` int(11) DEFAULT NULL,
  `shipping_zone_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `affiliate_id`, `subtotal`, `discount`, `shipping_cost`, `total`, `affiliate_commission`, `payment_method`, `payment_status`, `razorpay_payment_id`, `order_status`, `shipping_address`, `tracking_number`, `tracking_note`, `dispatch_date`, `shipping_method_id`, `shipping_zone_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'ORD-696B2AB8D0085', 1, NULL, 2598.00, 0.00, 0.00, 2728.00, 0.00, 'cod', 'pending', NULL, 'delivered', '{\"name\":\"irfan manzoor\",\"email\":\"drop.mail.iry@gmail.com\",\"phone\":\"06006801960\",\"address\":\"sector 7 gulberg colony hyderpora\",\"city\":\"Srinagar (M Corp. + OG) (Part)\",\"zip\":\"190014\"}', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-17 06:22:48', '2026-01-23 10:25:16'),
(2, 'ORD-696B2B8364365', 2, NULL, 698.00, 0.00, 0.00, 733.00, 0.00, 'cod', 'pending', NULL, 'cancelled', '{\"name\":\"irfan manzoor\",\"email\":\"drop.mail.iry@gmail.com\",\"phone\":\"06006801960\",\"address\":\"sector 7 gulberg colony hyderpora\",\"city\":\"Srinagar (M Corp. + OG) (Part)\",\"zip\":\"190014\"}', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-17 06:26:11', '2026-01-23 10:25:21'),
(3, 'ORD-696BA2A2345E2', 1, NULL, 2396.00, 0.00, 0.00, 2516.00, 0.00, 'cod', 'pending', NULL, 'confirmed', '{\"name\":\"irfan manzoor\",\"email\":\"drop.mail.iry@gmail.com\",\"phone\":\"+916006801960\",\"address\":\"sector 7 gulberg colony hyderpora\",\"city\":\"Srinagar (M Corp. + OG) (Part)\",\"zip\":\"190014\"}', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-17 14:54:26', '2026-01-23 09:50:37'),
(4, 'ORD-696BA431DFDFA', 1, NULL, 349.00, 0.00, 50.00, 417.00, 0.00, 'cod', 'pending', NULL, 'confirmed', '{\"name\":\"irfan manzoor\",\"email\":\"drop.mail.iry@gmail.com\",\"phone\":\"+916006801960\",\"address\":\"sector 7 gulberg colony hyderpora\",\"city\":\"Srinagar (M Corp. + OG) (Part)\",\"zip\":\"190014\"}', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-17 15:01:05', '2026-01-17 15:02:46'),
(5, 'ORD-696DF621C44E6', 1, NULL, 299.00, 0.00, 0.00, 314.00, 0.00, 'cod', 'pending', NULL, 'shipped', '{\"name\":\"irfan manzoor\",\"email\":\"drop.mail.iry@gmail.com\",\"phone\":\"+916006801960\",\"address\":\"sector 7 gulberg colony hyderpora\",\"city\":\"Srinagar (M Corp. + OG) (Part)\",\"zip\":\"190014\"}', 'ORD-696DF621C44E6', 'you parcel has been confirmed', '2026-01-19 00:00:00', 0, NULL, NULL, '2026-01-19 09:15:13', '2026-01-19 10:05:00'),
(6, 'ORD-696E06D28F1C5', 1, NULL, 648.00, 0.00, 0.00, 681.00, 0.00, 'upi', 'pending', '', 'pending', '{\"name\":\"irfan manzoor\",\"email\":\"drop.mail.iry@gmail.com\",\"phone\":\"+916006801960\",\"address\":\"sector 7 gulberg colony hyderpora\",\"city\":\"Srinagar (M Corp. + OG) (Part)\",\"zip\":\"190014\"}', NULL, NULL, NULL, 0, NULL, NULL, '2026-01-19 10:26:26', '2026-01-19 10:26:26'),
(7, 'ORD-69711BF09CC2F', 2, NULL, 349.00, 0.00, 30.00, 417.00, 0.00, 'cod', 'pending', '', 'confirmed', '{\"name\":\"irfan manzoor\",\"email\":\"drop.mail.iry@gmail.com\",\"phone\":\"+916006801960\",\"address\":\"sector 7 gulberg colony hyderpora\",\"city\":\"Srinagar (M Corp. + OG) (Part)\",\"zip\":\"190014\"}', NULL, NULL, NULL, 3, NULL, NULL, '2026-01-21 18:33:20', '2026-01-23 09:48:53'),
(9, 'TEST-GUEST-2', NULL, NULL, 100.00, 0.00, 0.00, 100.00, 0.00, 'cod', 'pending', NULL, 'confirmed', 'test address', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 18:54:52', '2026-01-23 09:48:44'),
(10, 'ORD-697121A9A4E4A', NULL, NULL, 299.00, 0.00, 30.00, 362.00, 0.00, 'cod', 'pending', '', 'shipped', '{\"name\":\"irfan manzoor\",\"email\":\"drop.mail.iry@gmail.com\",\"phone\":\"+916006801960\",\"address\":\"sector 7 gulberg colony hyderpora\",\"city\":\"Srinagar (M Corp. + OG) (Part)\",\"zip\":\"190014\"}', NULL, NULL, NULL, 3, NULL, NULL, '2026-01-21 18:57:45', '2026-01-23 09:49:10'),
(11, 'ORD-6971FEB00D7F6', NULL, NULL, 349.00, 0.00, 30.00, 417.00, 0.00, 'cod', 'pending', '', 'pending', '{\"name\":\"irfan manzoor\",\"email\":\"drop.mail.iry@gmail.com\",\"phone\":\"+916006801960\",\"address\":\"sector 7 gulberg colony hyderpora\",\"city\":\"Srinagar (M Corp. + OG) (Part)\",\"zip\":\"190014\"}', NULL, NULL, NULL, 3, NULL, NULL, '2026-01-22 10:40:48', '2026-01-23 10:23:15'),
(12, 'ORD-6972214827268', 1, NULL, 6020.00, 0.00, 30.00, 6655.00, 0.00, 'cod', 'pending', '', 'shipped', '{\"name\":\"irfan manzoor\",\"email\":\"drop.mail.iry@gmail.com\",\"phone\":\"+916006801960\",\"address\":\"sector 7 gulberg colony hyderpora\",\"city\":\"Srinagar (M Corp. + OG) (Part)\",\"zip\":\"190014\"}', NULL, NULL, NULL, 0, NULL, NULL, '2026-01-22 13:08:24', '2026-01-23 09:49:06'),
(13, 'ORD-6972466368CEF', NULL, 1, 5121.00, 512.00, 0.00, 5070.00, 460.00, 'cod', 'pending', '', 'delivered', '{\"name\":\"irfan manzoor\",\"email\":\"drop.mail.iry@gmail.com\",\"phone\":\"+916006801960\",\"address\":\"sector 7 gulberg colony hyderpora\",\"city\":\"Srinagar (M Corp. + OG) (Part)\",\"zip\":\"190014\"}', NULL, NULL, NULL, 0, NULL, NULL, '2026-01-22 15:46:43', '2026-01-23 10:25:05'),
(14, 'ORD-69735E6875CBB', NULL, NULL, 2495.00, 0.00, 35.00, 2783.00, 0.00, 'cod', 'pending', '', 'pending', '{\"name\":\"irfan manzoor\",\"email\":\"drop.mail.iry@gmail.com\",\"phone\":\"+916006801960\",\"address\":\"sector 7 gulberg colony hyderpora\",\"city\":\"Srinagar (M Corp. + OG) (Part)\",\"state\":\"Jammu and Kashmir\",\"zip\":\"190014\"}', NULL, NULL, NULL, 5, NULL, NULL, '2026-01-23 11:41:28', '2026-01-23 11:41:28');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `subtotal`, `created_at`) VALUES
(6, 4, 11, 1, 349.00, 349.00, '2026-01-17 15:01:06'),
(7, 5, 10, 1, 299.00, 299.00, '2026-01-19 09:15:13'),
(8, 6, 11, 1, 349.00, 349.00, '2026-01-19 10:26:26'),
(9, 6, 10, 1, 299.00, 299.00, '2026-01-19 10:26:26'),
(10, 7, 11, 1, 349.00, 349.00, '2026-01-21 18:33:20'),
(11, 10, 10, 1, 299.00, 299.00, '2026-01-21 18:57:45'),
(12, 11, 11, 1, 349.00, 349.00, '2026-01-22 10:40:48'),
(13, 12, 16, 1, 799.00, 799.00, '2026-01-22 13:08:24'),
(14, 12, 19, 1, 459.00, 459.00, '2026-01-22 13:08:24'),
(15, 12, 18, 1, 749.00, 749.00, '2026-01-22 13:08:24'),
(16, 12, 14, 1, 269.00, 269.00, '2026-01-22 13:08:24'),
(17, 12, 15, 1, 899.00, 899.00, '2026-01-22 13:08:24'),
(18, 12, 20, 1, 1499.00, 1499.00, '2026-01-22 13:08:24'),
(19, 12, 17, 1, 499.00, 499.00, '2026-01-22 13:08:24'),
(20, 12, 10, 1, 299.00, 299.00, '2026-01-22 13:08:24'),
(21, 12, 12, 1, 199.00, 199.00, '2026-01-22 13:08:24'),
(22, 12, 11, 1, 349.00, 349.00, '2026-01-22 13:08:24'),
(23, 13, 12, 1, 199.00, 199.00, '2026-01-22 15:46:43'),
(24, 13, 14, 1, 269.00, 269.00, '2026-01-22 15:46:43'),
(25, 13, 18, 1, 749.00, 749.00, '2026-01-22 15:46:43'),
(26, 13, 19, 1, 459.00, 459.00, '2026-01-22 15:46:43'),
(27, 13, 17, 1, 499.00, 499.00, '2026-01-22 15:46:43'),
(28, 13, 16, 1, 799.00, 799.00, '2026-01-22 15:46:43'),
(29, 13, 10, 1, 299.00, 299.00, '2026-01-22 15:46:43'),
(30, 13, 11, 1, 349.00, 349.00, '2026-01-22 15:46:43'),
(31, 13, 20, 1, 1499.00, 1499.00, '2026-01-22 15:46:43'),
(32, 14, 11, 1, 349.00, 349.00, '2026-01-23 11:41:28'),
(33, 14, 12, 1, 199.00, 199.00, '2026-01-23 11:41:28'),
(34, 14, 15, 1, 899.00, 899.00, '2026-01-23 11:41:28'),
(35, 14, 18, 1, 749.00, 749.00, '2026-01-23 11:41:28'),
(36, 14, 10, 1, 299.00, 299.00, '2026-01-23 11:41:28');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `notes`, `created_at`) VALUES
(1, 4, 'pending', 'Order placed successfully', '2026-01-17 15:01:06'),
(2, 4, 'confirmed', 'Order status updated to confirmed by admin.', '2026-01-17 15:02:46'),
(3, 5, 'pending', 'Order placed successfully', '2026-01-19 09:15:13'),
(4, 5, 'confirmed', 'Order status updated to confirmed by admin.', '2026-01-19 10:02:19'),
(5, 5, 'shipped', 'Order dispatched via India Post. Tracking Number: ORD-696DF621C44E6. Note: you parcel has been confirmed', '2026-01-19 10:05:00'),
(6, 6, 'pending', 'Order placed successfully', '2026-01-19 10:26:26'),
(7, 7, 'pending', 'Order placed successfully', '2026-01-21 18:33:20'),
(8, 10, 'pending', 'Order placed successfully', '2026-01-21 18:57:45'),
(9, 11, 'pending', 'Order placed successfully', '2026-01-22 10:40:48'),
(10, 12, 'pending', 'Order placed successfully', '2026-01-22 13:08:24'),
(11, 13, 'pending', 'Order placed successfully', '2026-01-22 15:46:43'),
(12, 9, 'confirmed', 'Order status updated to confirmed by admin.', '2026-01-23 09:48:44'),
(13, 13, 'delivered', 'Order status updated to delivered by admin.', '2026-01-23 09:48:48'),
(14, 7, 'confirmed', 'Order status updated to confirmed by admin.', '2026-01-23 09:48:53'),
(15, 11, 'delivered', 'Order status updated to delivered by admin.', '2026-01-23 09:49:03'),
(16, 12, 'shipped', 'Order status updated to shipped by admin.', '2026-01-23 09:49:06'),
(17, 10, 'shipped', 'Order status updated to shipped by admin.', '2026-01-23 09:49:10'),
(18, 2, 'confirmed', 'Order status updated to confirmed by admin.', '2026-01-23 09:49:23'),
(19, 1, 'cancelled', 'Order status updated to cancelled by admin.', '2026-01-23 09:50:29'),
(20, 3, 'confirmed', 'Order status updated to confirmed by admin.', '2026-01-23 09:50:37'),
(21, 13, 'shipped', 'Order status updated to shipped by admin via AJAX.', '2026-01-23 09:59:35'),
(22, 13, 'pending', 'Order status updated to pending by admin via AJAX.', '2026-01-23 10:13:11'),
(23, 13, 'confirmed', 'Order status updated to confirmed by admin via AJAX.', '2026-01-23 10:13:16'),
(24, 13, 'pending', 'Order status updated to pending by admin via AJAX.', '2026-01-23 10:13:21'),
(25, 13, 'confirmed', 'Order status updated to confirmed by admin via AJAX.', '2026-01-23 10:23:12'),
(26, 11, 'pending', 'Order status updated to pending by admin via AJAX.', '2026-01-23 10:23:15'),
(27, 13, 'delivered', 'Order status updated to delivered by admin via AJAX', '2026-01-23 10:25:05'),
(28, 2, 'pending', 'Order status updated to pending by admin via AJAX', '2026-01-23 10:25:12'),
(29, 1, 'delivered', 'Order status updated to delivered by admin via AJAX', '2026-01-23 10:25:16'),
(30, 2, 'cancelled', 'Order status updated to cancelled by admin via AJAX', '2026-01-23 10:25:21'),
(31, 14, 'pending', 'Order placed successfully', '2026-01-23 11:41:28');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `created_at`) VALUES
(5, 'drop.mail.iry@gmail.com', '32898d851fe92ba5c6d5693ac0af67cb209929327bb5e3927089e154f339fe50', '2026-01-20 17:09:01');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ingredients` text DEFAULT NULL,
  `nutritional_info` text DEFAULT NULL,
  `weight` varchar(50) DEFAULT NULL,
  `shelf_life` varchar(100) DEFAULT NULL,
  `storage_instructions` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `bg_color` varchar(20) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `discount_percentage` int(11) DEFAULT 0,
  `stock` int(11) DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_new` tinyint(1) DEFAULT 0,
  `is_vegetarian` tinyint(1) DEFAULT 1,
  `country_of_origin` varchar(100) DEFAULT 'India',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `ingredients`, `nutritional_info`, `weight`, `shelf_life`, `storage_instructions`, `image`, `bg_color`, `price`, `original_price`, `discount_percentage`, `stock`, `sku`, `is_active`, `is_featured`, `is_new`, `is_vegetarian`, `country_of_origin`, `created_at`, `updated_at`) VALUES
(10, 1, 'Kashmiri Apple Rings', 'apple', '', '[\"dired apples\"]', '{\"protein\":\"50g\",\"fat\":\"20g\"}', '0.500', NULL, NULL, 'assets/images/products/prod_696bbf988ab2c.webp', '', 299.00, 350.00, 0, 6, NULL, 1, 1, 0, 1, 'India', '2026-01-17 14:47:53', '2026-01-23 11:41:28'),
(11, 1, 'Premium Kiwi Slices', 'kiwi', '', '', 'calories 45g\\r\\nenergy 200kcl', '0.500', NULL, NULL, 'assets/images/products/prod_696bc03e18959.webp', NULL, 349.00, 460.00, 0, 25, NULL, 1, 1, 0, 1, 'India', '2026-01-17 14:47:53', '2026-01-23 11:41:28'),
(12, 1, 'Dried Banana Coins', 'banana', '', '', 'calories 45g\\r\\nenergy 200kcl', '0.500', NULL, NULL, 'assets/images/products/prod_696bbfd448da6.webp', NULL, 199.00, 249.00, 0, 7, NULL, 1, 1, 0, 1, 'India', '2026-01-17 14:47:53', '2026-01-23 11:41:28'),
(13, 2, 'Al Hach (Bottle Gourd)', 'al-hach', '', '', '', '0.500', NULL, NULL, 'assets/images/products/al-hach.jpg', '', 249.00, 0.00, 0, 10, NULL, 1, 1, 0, 1, 'India', '2026-01-17 14:47:53', '2026-01-23 10:46:25'),
(14, 2, 'Vangan Hach (Brinjal)', 'vangan-hach', '', '', '', '0.500', NULL, NULL, 'assets/images/products/prod_696dfd6db00d7.webp', '', 269.00, 300.00, 0, 28, NULL, 1, 1, 0, 1, 'India', '2026-01-17 14:47:53', '2026-01-23 10:46:25'),
(15, 3, '3 Pack Kiwi', '3-pack-kiwi', '', '', 'calories 45g\\r\\nenergy 200kcl', '0.500', NULL, NULL, 'assets/images/products/prod_696bc17061792.webp', NULL, 899.00, 1200.00, 0, 28, NULL, 1, 0, 0, 1, 'India', '2026-01-17 14:47:53', '2026-01-23 11:41:28'),
(16, 3, '3 Pack Apple', '3-pack-apple', '', '', 'calories 45g\\r\\nenergy 200kcl', '0.500', NULL, NULL, 'assets/images/products/prod_696bc01148a86.webp', NULL, 799.00, 950.00, 0, 18, NULL, 1, 0, 0, 1, 'India', '2026-01-17 14:47:53', '2026-01-23 10:46:25'),
(17, 3, '3 Pack Banana', '3-pack-banana', '', '', 'calories 45g\\\\r\\\\nenergy 200kcl\\\\r\\\\nprotein 40g', '0.500', NULL, NULL, 'assets/images/products/prod_696bbf7459ad8.webp', NULL, 499.00, 550.00, 0, 12, NULL, 1, 0, 0, 1, 'India', '2026-01-17 14:47:53', '2026-01-23 10:46:25'),
(18, 3, '3 Pack Mix (1 each)', '3-pack-mix', '', '', 'calories 45g\\r\\nenergy 200kcl', '0.500', NULL, NULL, 'assets/images/products/prod_696bbf2aa1462.webp', NULL, 749.00, 900.00, 0, 12, NULL, 1, 0, 0, 1, 'India', '2026-01-17 14:47:53', '2026-01-23 11:41:28'),
(19, 3, '2 Mix Al Hach + Vangan Hach', 'veggie-combo', '', '100 % natural', 'calories 45g\\\\r\\\\nenergy 200kcl', '0.500', NULL, NULL, 'assets/images/products/prod_696bbecfabf45.webp', NULL, 459.00, 550.00, 0, 12, NULL, 1, 0, 0, 1, 'India', '2026-01-17 14:47:53', '2026-01-23 10:46:25'),
(20, 3, 'The Grand Driyum Hamper', 'grand-hamper', '', '[\"protein   20g\"]', '', '0.500', NULL, NULL, 'assets/images/products/prod_696bbe13eb874.webp', '#F0FDFA', 1499.00, 1799.00, 0, 10, NULL, 1, 0, 0, 1, 'India', '2026-01-17 14:47:53', '2026-01-23 10:46:25');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `sort_order`, `created_at`) VALUES
(1, 20, 'assets/images/products/g_696bbe1400eaf.webp', 0, '2026-01-17 16:51:32'),
(2, 20, 'assets/images/products/g_696bbe140326d.webp', 0, '2026-01-17 16:51:32'),
(3, 20, 'assets/images/products/g_696bbe1403e67.webp', 0, '2026-01-17 16:51:32'),
(4, 20, 'assets/images/products/g_696bbe1404ebd.webp', 0, '2026-01-17 16:51:32'),
(5, 20, 'assets/images/products/g_696bbe1408866.webp', 0, '2026-01-17 16:51:32'),
(6, 19, 'assets/images/products/g_696bbec42286a.webp', 0, '2026-01-17 16:54:28'),
(7, 19, 'assets/images/products/g_696bbec42354a.webp', 0, '2026-01-17 16:54:28'),
(9, 19, 'assets/images/products/g_696bbec426143.webp', 0, '2026-01-17 16:54:28'),
(11, 19, 'assets/images/products/g_696bbecfad5e3.webp', 0, '2026-01-17 16:54:39'),
(13, 19, 'assets/images/products/g_696bbecfaf81d.webp', 0, '2026-01-17 16:54:39'),
(14, 19, 'assets/images/products/g_696bbecfb074d.webp', 0, '2026-01-17 16:54:39'),
(15, 19, 'assets/images/products/g_696bbecfb1764.webp', 0, '2026-01-17 16:54:39'),
(16, 18, 'assets/images/products/g_696bbf2aa2c29.webp', 0, '2026-01-17 16:56:10'),
(17, 18, 'assets/images/products/g_696bbf2aa3b44.webp', 0, '2026-01-17 16:56:10'),
(18, 18, 'assets/images/products/g_696bbf2aa52a2.webp', 0, '2026-01-17 16:56:10'),
(19, 18, 'assets/images/products/g_696bbf2aa5f0f.webp', 0, '2026-01-17 16:56:10'),
(20, 18, 'assets/images/products/g_696bbf2aa6d92.webp', 0, '2026-01-17 16:56:10'),
(21, 17, 'assets/images/products/g_696bbf745b3cc.webp', 0, '2026-01-17 16:57:24'),
(22, 17, 'assets/images/products/g_696bbf745c042.webp', 0, '2026-01-17 16:57:24'),
(23, 17, 'assets/images/products/g_696bbf745cb4e.webp', 0, '2026-01-17 16:57:24'),
(24, 17, 'assets/images/products/g_696bbf745d92e.webp', 0, '2026-01-17 16:57:24'),
(25, 17, 'assets/images/products/g_696bbf745e5e2.webp', 0, '2026-01-17 16:57:24'),
(26, 10, 'assets/images/products/g_696bbf988cb77.webp', 0, '2026-01-17 16:58:00'),
(27, 10, 'assets/images/products/g_696bbf988d8c4.webp', 0, '2026-01-17 16:58:00'),
(28, 10, 'assets/images/products/g_696bbf988e505.webp', 0, '2026-01-17 16:58:00'),
(29, 10, 'assets/images/products/g_696bbf988f172.webp', 0, '2026-01-17 16:58:00'),
(30, 10, 'assets/images/products/g_696bbf989023d.webp', 0, '2026-01-17 16:58:00'),
(31, 12, 'assets/images/products/g_696bbfd44a127.webp', 0, '2026-01-17 16:59:00'),
(32, 12, 'assets/images/products/g_696bbfd44ae52.webp', 0, '2026-01-17 16:59:00'),
(33, 12, 'assets/images/products/g_696bbfd44be70.webp', 0, '2026-01-17 16:59:00'),
(34, 12, 'assets/images/products/g_696bbfd44d4e2.webp', 0, '2026-01-17 16:59:00'),
(35, 12, 'assets/images/products/g_696bbfd44e09f.webp', 0, '2026-01-17 16:59:00'),
(36, 16, 'assets/images/products/g_696bc0114a23a.webp', 0, '2026-01-17 17:00:01'),
(37, 16, 'assets/images/products/g_696bc0114af13.webp', 0, '2026-01-17 17:00:01'),
(38, 16, 'assets/images/products/g_696bc0114bdce.webp', 0, '2026-01-17 17:00:01'),
(39, 16, 'assets/images/products/g_696bc0114d1fb.webp', 0, '2026-01-17 17:00:01'),
(40, 16, 'assets/images/products/g_696bc0114e099.webp', 0, '2026-01-17 17:00:01'),
(41, 11, 'assets/images/products/g_696bc03e1a0b2.webp', 0, '2026-01-17 17:00:46'),
(42, 11, 'assets/images/products/g_696bc03e1b5b0.webp', 0, '2026-01-17 17:00:46'),
(43, 11, 'assets/images/products/g_696bc03e1c348.webp', 0, '2026-01-17 17:00:46'),
(44, 15, 'assets/images/products/g_696bc15d7008f.webp', 0, '2026-01-17 17:05:33'),
(45, 15, 'assets/images/products/g_696bc15d713cc.webp', 0, '2026-01-17 17:05:33'),
(46, 15, 'assets/images/products/g_696bc15d72011.webp', 0, '2026-01-17 17:05:33'),
(47, 15, 'assets/images/products/g_696bc170630d2.webp', 0, '2026-01-17 17:05:52'),
(48, 15, 'assets/images/products/g_696bc17064098.webp', 0, '2026-01-17 17:05:52'),
(49, 15, 'assets/images/products/g_696bc1706507f.webp', 0, '2026-01-17 17:05:52'),
(50, 14, 'assets/images/products/g_696dfd6db96db.webp', 0, '2026-01-19 09:46:21'),
(51, 14, 'assets/images/products/g_696dfd6dbbeb8.webp', 0, '2026-01-19 09:46:21'),
(52, 14, 'assets/images/products/g_696dfd6dbcffe.webp', 0, '2026-01-19 09:46:21'),
(53, 14, 'assets/images/products/g_696dfd6dbe3c8.webp', 0, '2026-01-19 09:46:21'),
(54, 14, 'assets/images/products/g_696dfd6dbf5e8.webp', 0, '2026-01-19 09:46:21');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `title` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT 5,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(2, 10, 2, 1, 'worst product', '2026-01-23 11:15:24'),
(3, 10, 2, 4, 'nice product', '2026-01-23 11:15:33'),
(4, 10, 2, 5, 'best product ', '2026-01-23 11:15:43'),
(7, 10, 2, 5, 'i loved the product', '2026-01-23 11:16:43');

-- --------------------------------------------------------

--
-- Table structure for table `sale_countdowns`
--

CREATE TABLE `sale_countdowns` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `end_date` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_countdowns`
--

INSERT INTO `sale_countdowns` (`id`, `title`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '­ MEGA SALE - Up to 50% OFF', '2026-01-24 00:27:00', 1, '2026-01-16 18:57:45', '2026-01-17 09:11:48');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`key`, `value`, `updated_at`) VALUES
('announcement_text', '🍎FREE SHIPPING ON ORDERS ABOVE 500  🍌 SUBSCRIBE TO DRIYUM 🥝', '2026-01-23 09:35:54'),
('facebook_url', 'https://www.facebook.com/people/Driyum-Foods/61585416636650/?mibextid=wwXIfr&amp;rdid=BDRmnaJ3qy4ccL3W&amp;share_url=https%3A%2F%2Fwww.facebook.com%2Fshare%2F1DPT8KEgt7%2F%3Fmibextid%3DwwXIfr', '2026-01-23 09:46:25'),
('footer_description', 'Redefining the art of snacking with premium, sun-dried indulgence. Naturally sweet, unapologetically bold.', '2026-01-19 10:55:09'),
('free_shipping_threshold', '500', '2026-01-19 10:41:25'),
('instagram_url', 'https://www.instagram.com/driyumfoods?utm_source=ig_web_button_share_sheet&amp;igsh=ZDNlZDc0MzIxNw%3D%3D', '2026-01-23 09:46:25'),
('last_cron_run', '1769191233', '2026-01-23 18:00:33'),
('maintenance_mode', 'off', '2026-01-20 14:01:12'),
('order_prefix', '', '2026-01-19 10:41:37'),
('pinterest_url', '#', '2026-01-19 10:53:08'),
('seo_description', '', '2026-01-19 10:41:37'),
('store_name', 'DRIYUM', '2026-01-19 10:41:25'),
('support_email', 'contact@driyum.com', '2026-01-19 10:41:25'),
('support_phone', '+91 91030 00000', '2026-01-19 10:41:25'),
('tax_percentage', '10', '2026-01-19 10:45:57'),
('twitter_url', '#', '2026-01-19 10:53:08'),
('whatsapp_number', '6006801960', '2026-01-22 15:01:17'),
('youtube_url', '#', '2026-01-19 10:53:08');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_methods`
--

CREATE TABLE `shipping_methods` (
  `id` int(11) NOT NULL,
  `carrier_name` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `min_days` int(11) NOT NULL,
  `max_days` int(11) NOT NULL,
  `charge_type` enum('flat','weight_based','zone_based') NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipping_methods`
--

INSERT INTO `shipping_methods` (`id`, `carrier_name`, `display_name`, `min_days`, `max_days`, `charge_type`, `status`, `created_at`, `updated_at`) VALUES
(4, 'India Post', 'India Post', 0, 0, 'weight_based', 1, '2026-01-22 12:54:22', '2026-01-22 13:05:31'),
(5, 'India Post', 'India Post (Speed Post)', 0, 0, 'weight_based', 1, '2026-01-22 12:54:57', '2026-01-22 12:54:57'),
(6, 'India Post', 'India Post (Normal Delivery)', 0, 0, 'weight_based', 1, '2026-01-22 12:54:57', '2026-01-22 12:54:57');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_rates`
--

CREATE TABLE `shipping_rates` (
  `id` int(11) NOT NULL,
  `method_id` int(11) NOT NULL,
  `zone_id` int(11) NOT NULL,
  `min_weight` decimal(10,3) NOT NULL,
  `max_weight` decimal(10,3) NOT NULL,
  `charge` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipping_rates`
--

INSERT INTO `shipping_rates` (`id`, `method_id`, `zone_id`, `min_weight`, `max_weight`, `charge`) VALUES
(17, 5, 7, 0.000, 0.250, 30.00),
(18, 5, 7, 0.251, 0.500, 35.00),
(19, 5, 7, 0.501, 1.000, 50.00),
(20, 6, 8, 0.000, 0.500, 50.00),
(21, 6, 8, 0.501, 1.000, 65.00),
(22, 6, 9, 0.000, 0.500, 60.00),
(23, 6, 9, 0.501, 1.000, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `shipping_zones`
--

CREATE TABLE `shipping_zones` (
  `id` int(11) NOT NULL,
  `zone_name` varchar(100) NOT NULL,
  `pincode_ranges` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `min_days` int(11) DEFAULT 0,
  `max_days` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipping_zones`
--

INSERT INTO `shipping_zones` (`id`, `zone_name`, `pincode_ranges`, `is_active`, `created_at`, `updated_at`, `min_days`, `max_days`) VALUES
(1, 'Local', '190001-190020', 1, '2026-01-19 08:33:00', '2026-01-19 09:21:42', 0, 1),
(2, 'State', '180000-190001', 1, '2026-01-19 08:33:00', '2026-01-22 13:06:06', 2, 4),
(3, 'National', '100001-999999', 1, '2026-01-19 08:33:00', '2026-01-19 09:38:01', 5, 7),
(4, 'Srinagar (Speed Post)', '190001-190035', 1, '2026-01-22 12:52:03', '2026-01-22 12:52:03', 0, 2),
(5, 'Outside Srinagar (Kashmir/Jammu)', '180001-189999,190036-194999', 1, '2026-01-22 12:52:58', '2026-01-22 12:52:58', 3, 4),
(6, 'Outside State (National)', '110001-179999,200000-999999', 1, '2026-01-22 12:52:58', '2026-01-22 12:52:58', 5, 7),
(7, 'Srinagar Local', '190001-190035', 1, '2026-01-22 12:54:57', '2026-01-22 12:54:57', 0, 2),
(8, 'Outside Srinagar', '180001-189999,190036-194999', 1, '2026-01-22 12:54:57', '2026-01-22 12:54:57', 3, 4),
(9, 'Outside State', '110001-179999,200000-999999', 1, '2026-01-22 12:54:57', '2026-01-22 12:54:57', 5, 7);

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `rating` int(11) DEFAULT 5,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `name`, `location`, `message`, `rating`, `is_active`, `created_at`) VALUES
(1, 'Towfeeq Fayaz', 'Hyderpora', 'Absolutely love the quality! The dehydrated fruits taste amazing and are so fresh.', 5, 1, '2026-01-16 18:57:45'),
(2, 'Fuzail Bhat', 'Sanatnagar', 'Best Hokh Suin I have ever tasted. Reminds me of Kashmir!', 5, 1, '2026-01-16 18:57:45'),
(3, 'Amreen Imtiyaz', 'Lasjan', 'Great products and super fast delivery. Highly recommended!', 5, 1, '2026-01-16 18:57:45'),
(4, 'Vikram Mehta', 'Pune', 'Premium quality snacks. My kids love them!', 4, 1, '2026-01-16 18:57:45'),
(5, 'irfan manzoor', 'srinagar', 'Nice startup for health concious people ', 5, 1, '2026-01-17 09:21:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `is_admin`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@driyum.com', '9876543210', '$2y$10$46xQjsFXLcecONgkC79rv.xxYAbJxCWf7abdYMHTkUn1zEWj01wj2', 1, 1, '2026-01-16 18:57:45', '2026-01-17 06:43:51'),
(2, 'irfan manzoor', 'drop.mail.iry@gmail.com', NULL, '$2y$10$h9jYz4hehgUyGjGQp.Dkq.l8NaXxgyV7FdsdFl5NnstxQOm09iITO', 0, 1, '2026-01-17 06:25:09', '2026-01-23 08:44:13'),
(4, 'irfan manzoor', 'hire.iry@gmail.com', '6006801960', '$2y$10$F7s4umo3Xydz13/DLFexyewMXq7GMpoeD8hM191cOVQBtWrQILiQ2', 1, 1, '2026-01-23 09:24:47', '2026-01-23 09:42:09');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line1` varchar(500) NOT NULL,
  `address_line2` varchar(500) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(2, 2, 10, '2026-01-21 18:31:31'),
(3, 2, 11, '2026-01-21 18:31:39'),
(4, 2, 12, '2026-01-21 18:31:41'),
(5, 2, 13, '2026-01-21 18:31:42'),
(6, 1, 11, '2026-01-22 12:23:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `abandoned_carts`
--
ALTER TABLE `abandoned_carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_reminded` (`is_reminded`);

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `affiliates`
--
ALTER TABLE `affiliates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- Indexes for table `contact_info`
--
ALTER TABLE `contact_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `hero_slides`
--
ALTER TABLE `hero_slides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `homepage_sections`
--
ALTER TABLE `homepage_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_name` (`section_name`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_status` (`order_status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `affiliate_id` (`affiliate_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_price` (`price`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sale_countdowns`
--
ALTER TABLE `sale_countdowns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `shipping_methods`
--
ALTER TABLE `shipping_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipping_rates`
--
ALTER TABLE `shipping_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `method_id` (`method_id`),
  ADD KEY `zone_id` (`zone_id`);

--
-- Indexes for table `shipping_zones`
--
ALTER TABLE `shipping_zones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `abandoned_carts`
--
ALTER TABLE `abandoned_carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `affiliates`
--
ALTER TABLE `affiliates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_info`
--
ALTER TABLE `contact_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hero_slides`
--
ALTER TABLE `hero_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `homepage_sections`
--
ALTER TABLE `homepage_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sale_countdowns`
--
ALTER TABLE `sale_countdowns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shipping_methods`
--
ALTER TABLE `shipping_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `shipping_rates`
--
ALTER TABLE `shipping_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `shipping_zones`
--
ALTER TABLE `shipping_zones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `affiliates`
--
ALTER TABLE `affiliates`
  ADD CONSTRAINT `affiliates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipping_rates`
--
ALTER TABLE `shipping_rates`
  ADD CONSTRAINT `shipping_rates_ibfk_1` FOREIGN KEY (`method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipping_rates_ibfk_2` FOREIGN KEY (`zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
