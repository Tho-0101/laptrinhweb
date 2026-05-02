-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost:3307
-- Thời gian đã tạo: Th4 28, 2026 lúc 01:34 AM
-- Phiên bản máy phục vụ: 8.4.3
-- Phiên bản PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `bike_marketplace1`
--

DELIMITER $$
--
-- Thủ tục
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_user_rating` (IN `p_user_id` INT)   BEGIN
    UPDATE users
    SET
        rating_score = (
            SELECT AVG(rating)
            FROM reviews
            WHERE seller_id = p_user_id AND is_visible = TRUE
        ),
        total_reviews = (
            SELECT COUNT(*)
            FROM reviews
            WHERE seller_id = p_user_id AND is_visible = TRUE
        )
    WHERE id = p_user_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `old_values` text,
  `new_values` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bikes`
--

CREATE TABLE `bikes` (
  `id` int NOT NULL,
  `seller_id` int NOT NULL,
  `category_id` int NOT NULL,
  `brand_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `original_price` decimal(12,2) DEFAULT NULL,
  `frame_size` varchar(50) DEFAULT NULL,
  `frame_material` varchar(100) DEFAULT NULL,
  `wheel_size` varchar(50) DEFAULT NULL,
  `gear_system` varchar(100) DEFAULT NULL,
  `brake_type` varchar(100) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `year_of_manufacture` int DEFAULT NULL,
  `condition_status` enum('like_new','good','fair','needs_repair') NOT NULL,
  `usage_time` int DEFAULT NULL,
  `has_warranty` tinyint(1) DEFAULT '0',
  `warranty_months` int DEFAULT '0',
  `city` varchar(100) NOT NULL,
  `district` varchar(100) NOT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected','sold','hidden') DEFAULT 'pending',
  `rejection_reason` text,
  `is_featured` tinyint(1) DEFAULT '0',
  `is_inspected` tinyint(1) DEFAULT '0',
  `inspector_id` int DEFAULT NULL,
  `inspection_date` timestamp NULL DEFAULT NULL,
  `view_count` int DEFAULT '0',
  `favorite_count` int DEFAULT '0',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approved_at` timestamp NULL DEFAULT NULL,
  `sold_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bike_images`
--

CREATE TABLE `bike_images` (
  `id` int NOT NULL,
  `bike_id` int NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` enum('image','video') DEFAULT 'image',
  `is_primary` tinyint(1) DEFAULT '0',
  `display_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `brands`
--

CREATE TABLE `brands` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `country`, `description`, `is_active`, `created_at`) VALUES
(1, 'Giant', 'giant', NULL, 'Taiwan', NULL, 1, '2026-04-21 17:22:12'),
(2, 'Trek', 'trek', NULL, 'USA', NULL, 1, '2026-04-21 17:22:12'),
(3, 'Specialized', 'specialized', NULL, 'USA', NULL, 1, '2026-04-21 17:22:12'),
(4, 'Cannondale', 'cannondale', NULL, 'USA', NULL, 1, '2026-04-21 17:22:12'),
(5, 'Merida', 'merida', NULL, 'Taiwan', NULL, 1, '2026-04-21 17:22:12'),
(6, 'Scott', 'scott', NULL, 'Switzerland', NULL, 1, '2026-04-21 17:22:12'),
(7, 'Bianchi', 'bianchi', NULL, 'Italy', NULL, 1, '2026-04-21 17:22:12'),
(8, 'Pinarello', 'pinarello', NULL, 'Italy', NULL, 1, '2026-04-21 17:22:12'),
(9, 'Cervélo', 'cervelo', NULL, 'Canada', NULL, 1, '2026-04-21 17:22:12'),
(10, 'Santa Cruz', 'santa-cruz', NULL, 'USA', NULL, 1, '2026-04-21 17:22:12'),
(11, 'Brompton', 'brompton', NULL, 'UK', NULL, 1, '2026-04-21 17:22:12'),
(12, 'Dahon', 'dahon', NULL, 'USA', NULL, 1, '2026-04-21 17:22:12'),
(13, 'Trinx', 'trinx', NULL, 'China', NULL, 1, '2026-04-21 17:22:12'),
(14, 'Twitter', 'twitter', NULL, 'China', NULL, 1, '2026-04-21 17:22:12'),
(15, 'Fornix', 'fornix', NULL, 'Vietnam', NULL, 1, '2026-04-21 17:22:12'),
(16, 'Asama', 'asama', NULL, 'Vietnam', NULL, 1, '2026-04-21 17:22:12');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `icon` varchar(50) DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `display_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `parent_id`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'Xe đạp đường trường (Road Bike)', 'road-bike', 'Xe đạp dành cho đường trường, tốc độ cao', 'fa-road', NULL, 0, 1, '2026-04-21 17:22:12'),
(2, 'Xe đạp địa hình (Mountain Bike)', 'mountain-bike', 'Xe đạp cho địa hình gồ ghề, off-road', 'fa-mountain', NULL, 0, 1, '2026-04-21 17:22:12'),
(3, 'Xe đạp touring', 'touring', 'Xe đạp cho đi phượt, đường dài', 'fa-route', NULL, 0, 1, '2026-04-21 17:22:12'),
(4, 'Xe đạp đua (Racing)', 'racing', 'Xe đạp thi đấu chuyên nghiệp', 'fa-flag-checkered', NULL, 0, 1, '2026-04-21 17:22:12'),
(5, 'Xe đạp hybrid', 'hybrid', 'Xe đạp đa năng, kết hợp nhiều loại', 'fa-bicycle', NULL, 0, 1, '2026-04-21 17:22:12'),
(6, 'Xe đạp gấp (Folding)', 'folding', 'Xe đạp có thể gấp gọn', 'fa-compress', NULL, 0, 1, '2026-04-21 17:22:12'),
(7, 'Xe đạp điện (E-bike)', 'e-bike', 'Xe đạp có trợ lực điện', 'fa-bolt', NULL, 0, 1, '2026-04-21 17:22:12');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `favorites`
--

CREATE TABLE `favorites` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `bike_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Bẫy `favorites`
--
DELIMITER $$
CREATE TRIGGER `after_favorite_delete` AFTER DELETE ON `favorites` FOR EACH ROW BEGIN
    UPDATE bikes SET favorite_count = favorite_count - 1 WHERE id = OLD.bike_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_favorite_insert` AFTER INSERT ON `favorites` FOR EACH ROW BEGIN
    UPDATE bikes SET favorite_count = favorite_count + 1 WHERE id = NEW.bike_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inspections`
--

CREATE TABLE `inspections` (
  `id` int NOT NULL,
  `bike_id` int NOT NULL,
  `inspector_id` int NOT NULL,
  `overall_status` enum('excellent','good','fair','poor') NOT NULL,
  `frame_condition` enum('excellent','good','fair','poor') NOT NULL,
  `frame_notes` text,
  `brake_condition` enum('excellent','good','fair','poor') NOT NULL,
  `brake_notes` text,
  `gear_condition` enum('excellent','good','fair','poor') NOT NULL,
  `gear_notes` text,
  `wheel_condition` enum('excellent','good','fair','poor') NOT NULL,
  `wheel_notes` text,
  `tire_condition` enum('excellent','good','fair','poor') NOT NULL,
  `tire_notes` text,
  `overall_notes` text,
  `estimated_value` decimal(12,2) DEFAULT NULL,
  `recommendations` text,
  `report_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `bike_id` int DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order','message','review','system','inspection') NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `order_code` varchar(50) NOT NULL,
  `buyer_id` int NOT NULL,
  `seller_id` int NOT NULL,
  `bike_id` int NOT NULL,
  `order_type` enum('deposit','full_payment') NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `deposit_amount` decimal(12,2) DEFAULT '0.00',
  `remaining_amount` decimal(12,2) DEFAULT '0.00',
  `delivery_method` enum('pickup','shipping') NOT NULL,
  `shipping_address` text,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_district` varchar(100) DEFAULT NULL,
  `shipping_ward` varchar(100) DEFAULT NULL,
  `shipping_phone` varchar(20) DEFAULT NULL,
  `shipping_note` text,
  `status` enum('pending','confirmed','shipping','completed','cancelled','refunded') DEFAULT 'pending',
  `payment_method` enum('cash','bank_transfer','vnpay','momo') DEFAULT 'cash',
  `payment_status` enum('unpaid','partially_paid','paid') DEFAULT 'unpaid',
  `buyer_note` text,
  `seller_note` text,
  `cancellation_reason` text,
  `cancelled_by` enum('buyer','seller','admin') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reports`
--

CREATE TABLE `reports` (
  `id` int NOT NULL,
  `reporter_id` int NOT NULL,
  `reported_user_id` int DEFAULT NULL,
  `bike_id` int DEFAULT NULL,
  `report_type` enum('fraud','spam','inappropriate','fake_product','other') NOT NULL,
  `description` text NOT NULL,
  `evidence_files` text,
  `status` enum('pending','investigating','resolved','rejected') DEFAULT 'pending',
  `admin_note` text,
  `resolved_by` int DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `buyer_id` int NOT NULL,
  `seller_id` int NOT NULL,
  `bike_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text,
  `accuracy_rating` int DEFAULT NULL,
  `communication_rating` int DEFAULT NULL,
  `shipping_rating` int DEFAULT NULL,
  `seller_response` text,
  `responded_at` timestamp NULL DEFAULT NULL,
  `is_visible` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Bẫy `reviews`
--
DELIMITER $$
CREATE TRIGGER `after_review_insert` AFTER INSERT ON `reviews` FOR EACH ROW BEGIN
    CALL update_user_rating(NEW.seller_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_review_update` AFTER UPDATE ON `reviews` FOR EACH ROW BEGIN
    CALL update_user_rating(NEW.seller_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'site_name', 'Bike Marketplace', 'string', 'Tên website', '2026-04-21 17:22:12'),
(2, 'site_email', 'support@bikemarketplace.com', 'string', 'Email liên hệ', '2026-04-21 17:22:12'),
(3, 'commission_rate', '5', 'number', 'Phí hoa hồng (%)', '2026-04-21 17:22:12'),
(4, 'featured_price', '50000', 'number', 'Phí tin nổi bật (VNĐ)', '2026-04-21 17:22:12'),
(5, 'inspection_fee', '200000', 'number', 'Phí kiểm định (VNĐ)', '2026-04-21 17:22:12'),
(6, 'min_deposit_percent', '20', 'number', 'Tỉ lệ đặt cọc tối thiểu (%)', '2026-04-21 17:22:12'),
(7, 'auto_approve_listings', 'false', 'boolean', 'Tự động duyệt tin đăng', '2026-04-21 17:22:12'),
(8, 'enable_chatbot', 'true', 'boolean', 'Bật chatbot hỗ trợ', '2026-04-21 17:22:12');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default-avatar.png',
  `role` enum('buyer','seller','inspector','admin') NOT NULL DEFAULT 'buyer',
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','banned') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT '0',
  `verification_token` varchar(255) DEFAULT NULL,
  `rating_score` decimal(3,2) DEFAULT '0.00',
  `total_reviews` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `phone`, `avatar`, `role`, `address`, `city`, `district`, `ward`, `status`, `email_verified`, `verification_token`, `rating_score`, `total_reviews`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'admin@bikemarketplace.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin System', '0901234567', 'default-avatar.png', 'admin', NULL, NULL, NULL, NULL, 'active', 1, NULL, 0.00, 0, '2026-04-21 17:22:12', '2026-04-21 17:22:12', NULL),
(2, 'inspector@bikemarketplace.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn Kiểm', '0902345678', 'default-avatar.png', 'inspector', NULL, NULL, NULL, NULL, 'active', 1, NULL, 0.00, 0, '2026-04-21 17:22:12', '2026-04-21 17:22:12', NULL),
(3, 'seller@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Minh Tuấn', '0903456789', 'default-avatar.png', 'seller', NULL, 'Hồ Chí Minh', NULL, NULL, 'active', 1, NULL, 4.50, 25, '2026-04-21 17:22:12', '2026-04-21 17:22:12', NULL),
(4, 'buyer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lê Thị Hương', '0904567890', 'default-avatar.png', 'buyer', NULL, 'Hà Nội', NULL, NULL, 'active', 1, NULL, 0.00, 0, '2026-04-21 17:22:12', '2026-04-21 17:22:12', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_active_bikes`
-- (See below for the actual view)
--
CREATE TABLE `v_active_bikes` (
`approved_at` timestamp
,`brake_type` varchar(100)
,`brand_id` int
,`brand_name` varchar(100)
,`category_id` int
,`category_name` varchar(100)
,`city` varchar(100)
,`color` varchar(50)
,`condition_status` enum('like_new','good','fair','needs_repair')
,`created_at` timestamp
,`description` text
,`district` varchar(100)
,`favorite_count` int
,`frame_material` varchar(100)
,`frame_size` varchar(50)
,`gear_system` varchar(100)
,`has_warranty` tinyint(1)
,`id` int
,`inspection_date` timestamp
,`inspector_id` int
,`is_featured` tinyint(1)
,`is_inspected` tinyint(1)
,`meta_description` text
,`meta_title` varchar(255)
,`original_price` decimal(12,2)
,`price` decimal(12,2)
,`primary_image` varchar(255)
,`rejection_reason` text
,`seller_id` int
,`seller_name` varchar(255)
,`seller_phone` varchar(20)
,`seller_rating` decimal(3,2)
,`slug` varchar(255)
,`sold_at` timestamp
,`status` enum('pending','approved','rejected','sold','hidden')
,`title` varchar(255)
,`updated_at` timestamp
,`usage_time` int
,`view_count` int
,`ward` varchar(100)
,`warranty_months` int
,`weight` decimal(5,2)
,`wheel_size` varchar(50)
,`year_of_manufacture` int
);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Chỉ mục cho bảng `bikes`
--
ALTER TABLE `bikes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `inspector_id` (`inspector_id`),
  ADD KEY `idx_seller` (`seller_id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_brand` (`brand_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_location` (`city`,`district`),
  ADD KEY `idx_created` (`created_at`);
ALTER TABLE `bikes` ADD FULLTEXT KEY `idx_search` (`title`,`description`);

--
-- Chỉ mục cho bảng `bike_images`
--
ALTER TABLE `bike_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bike` (`bike_id`);

--
-- Chỉ mục cho bảng `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- Chỉ mục cho bảng `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`bike_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_bike` (`bike_id`);

--
-- Chỉ mục cho bảng `inspections`
--
ALTER TABLE `inspections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bike` (`bike_id`),
  ADD KEY `idx_inspector` (`inspector_id`),
  ADD KEY `idx_status` (`status`);

--
-- Chỉ mục cho bảng `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_receiver` (`receiver_id`),
  ADD KEY `idx_bike` (`bike_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_read` (`is_read`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `idx_buyer` (`buyer_id`),
  ADD KEY `idx_seller` (`seller_id`),
  ADD KEY `idx_bike` (`bike_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Chỉ mục cho bảng `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resolved_by` (`resolved_by`),
  ADD KEY `idx_reporter` (`reporter_id`),
  ADD KEY `idx_reported_user` (`reported_user_id`),
  ADD KEY `idx_bike` (`bike_id`),
  ADD KEY `idx_status` (`status`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`order_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `bike_id` (`bike_id`),
  ADD KEY `idx_seller` (`seller_id`),
  ADD KEY `idx_rating` (`rating`);

--
-- Chỉ mục cho bảng `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `bikes`
--
ALTER TABLE `bikes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `bike_images`
--
ALTER TABLE `bike_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `inspections`
--
ALTER TABLE `inspections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_active_bikes`
--
DROP TABLE IF EXISTS `v_active_bikes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_active_bikes`  AS SELECT `b`.`id` AS `id`, `b`.`seller_id` AS `seller_id`, `b`.`category_id` AS `category_id`, `b`.`brand_id` AS `brand_id`, `b`.`title` AS `title`, `b`.`slug` AS `slug`, `b`.`description` AS `description`, `b`.`price` AS `price`, `b`.`original_price` AS `original_price`, `b`.`frame_size` AS `frame_size`, `b`.`frame_material` AS `frame_material`, `b`.`wheel_size` AS `wheel_size`, `b`.`gear_system` AS `gear_system`, `b`.`brake_type` AS `brake_type`, `b`.`weight` AS `weight`, `b`.`color` AS `color`, `b`.`year_of_manufacture` AS `year_of_manufacture`, `b`.`condition_status` AS `condition_status`, `b`.`usage_time` AS `usage_time`, `b`.`has_warranty` AS `has_warranty`, `b`.`warranty_months` AS `warranty_months`, `b`.`city` AS `city`, `b`.`district` AS `district`, `b`.`ward` AS `ward`, `b`.`status` AS `status`, `b`.`rejection_reason` AS `rejection_reason`, `b`.`is_featured` AS `is_featured`, `b`.`is_inspected` AS `is_inspected`, `b`.`inspector_id` AS `inspector_id`, `b`.`inspection_date` AS `inspection_date`, `b`.`view_count` AS `view_count`, `b`.`favorite_count` AS `favorite_count`, `b`.`meta_title` AS `meta_title`, `b`.`meta_description` AS `meta_description`, `b`.`created_at` AS `created_at`, `b`.`updated_at` AS `updated_at`, `b`.`approved_at` AS `approved_at`, `b`.`sold_at` AS `sold_at`, `u`.`full_name` AS `seller_name`, `u`.`phone` AS `seller_phone`, `u`.`rating_score` AS `seller_rating`, `c`.`name` AS `category_name`, `br`.`name` AS `brand_name`, (select `bike_images`.`file_path` from `bike_images` where ((`bike_images`.`bike_id` = `b`.`id`) and (`bike_images`.`is_primary` = true)) limit 1) AS `primary_image` FROM (((`bikes` `b` join `users` `u` on((`b`.`seller_id` = `u`.`id`))) join `categories` `c` on((`b`.`category_id` = `c`.`id`))) join `brands` `br` on((`b`.`brand_id` = `br`.`id`))) WHERE (`b`.`status` = 'approved') ;

--
-- Ràng buộc đối với các bảng kết xuất
--

--
-- Ràng buộc cho bảng `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ràng buộc cho bảng `bikes`
--
ALTER TABLE `bikes`
  ADD CONSTRAINT `bikes_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bikes_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `bikes_ibfk_3` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  ADD CONSTRAINT `bikes_ibfk_4` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ràng buộc cho bảng `bike_images`
--
ALTER TABLE `bike_images`
  ADD CONSTRAINT `bike_images_ibfk_1` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Ràng buộc cho bảng `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `inspections`
--
ALTER TABLE `inspections`
  ADD CONSTRAINT `inspections_ibfk_1` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inspections_ibfk_2` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`);

--
-- Ràng buộc cho bảng `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE SET NULL;

--
-- Ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`);

--
-- Ràng buộc cho bảng `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reports_ibfk_4` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_4` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
