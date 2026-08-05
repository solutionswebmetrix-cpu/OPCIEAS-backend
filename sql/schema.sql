-- MySQL Schema for OPCIEAS Furniture Platform
-- Engine: InnoDB | Charset: utf8mb4
-- Aligned per STEP 8 / STEP 14 Status Enums

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS opcieas DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE opcieas;

-- --------------------------------------------------------

-- Table structure for users (STEP 14: Pending / Approved / Rejected / Suspended)
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `company` VARCHAR(255) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('buyer', 'seller', 'admin') NOT NULL DEFAULT 'buyer',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Pending','Approved','Rejected','Suspended') NOT NULL DEFAULT 'Pending',
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`),
  KEY `users_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for admin_users
CREATE TABLE `admin_users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('super_admin','admin','manager','editor') NOT NULL DEFAULT 'admin',
  `permissions` JSON DEFAULT NULL,
  `status` ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `remember_expires` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_users_username_unique` (`username`),
  UNIQUE KEY `admin_users_email_unique` (`email`),
  KEY `admin_users_user_id_foreign` (`user_id`),
  KEY `admin_users_role_index` (`role`),
  KEY `admin_users_status_index` (`status`),
  CONSTRAINT `admin_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for categories
CREATE TABLE `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` BIGINT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `icon` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `meta_title` VARCHAR(255) DEFAULT NULL,
  `meta_description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  KEY `categories_status_index` (`status`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for seller_profiles
CREATE TABLE `seller_profiles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `company_name` VARCHAR(255) NOT NULL,
  `company_logo` VARCHAR(255) DEFAULT NULL,
  `gst_number` VARCHAR(50) DEFAULT NULL,
  `pan_number` VARCHAR(50) DEFAULT NULL,
  `registration_number` VARCHAR(100) DEFAULT NULL,
  `business_type` ENUM('manufacturer','wholesaler','retailer','distributor','exporter','importer') NOT NULL DEFAULT 'manufacturer',
  `description` TEXT DEFAULT NULL,
  `address_line1` VARCHAR(255) DEFAULT NULL,
  `address_line2` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'India',
  `pincode` VARCHAR(20) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `alternate_phone` VARCHAR(20) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `established_year` YEAR DEFAULT NULL,
  `total_employees` ENUM('1-10','11-50','51-100','101-250','251-500','500+') DEFAULT NULL,
  `annual_turnover` VARCHAR(100) DEFAULT NULL,
  `certifications` JSON DEFAULT NULL,
  `bank_details` JSON DEFAULT NULL,
  `verification_status` ENUM('unverified','Pending','Approved','Rejected') NOT NULL DEFAULT 'unverified',
  `verification_remarks` TEXT DEFAULT NULL,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `rating` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `total_reviews` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_products` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('Pending','Approved','Rejected','Suspended') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seller_profiles_user_id_unique` (`user_id`),
  UNIQUE KEY `seller_profiles_company_name_unique` (`company_name`),
  UNIQUE KEY `seller_profiles_gst_number_unique` (`gst_number`),
  UNIQUE KEY `seller_profiles_pan_number_unique` (`pan_number`),
  KEY `seller_profiles_business_type_index` (`business_type`),
  KEY `seller_profiles_verification_status_index` (`verification_status`),
  KEY `seller_profiles_city_index` (`city`),
  KEY `seller_profiles_status_index` (`status`),
  CONSTRAINT `seller_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for buyer_profiles
CREATE TABLE `buyer_profiles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `company_name` VARCHAR(255) DEFAULT NULL,
  `company_logo` VARCHAR(255) DEFAULT NULL,
  `gst_number` VARCHAR(50) DEFAULT NULL,
  `business_type` ENUM('individual','corporate','government','educational','hospital','hotel','other') NOT NULL DEFAULT 'individual',
  `address_line1` VARCHAR(255) DEFAULT NULL,
  `address_line2` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'India',
  `pincode` VARCHAR(20) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `alternate_phone` VARCHAR(20) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `total_orders` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_spent` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Pending','Approved','Rejected','Suspended') NOT NULL DEFAULT 'Approved',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `buyer_profiles_user_id_unique` (`user_id`),
  KEY `buyer_profiles_business_type_index` (`business_type`),
  KEY `buyer_profiles_city_index` (`city`),
  KEY `buyer_profiles_status_index` (`status`),
  CONSTRAINT `buyer_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for products (STEP 14: Pending / Published / Hidden / Rejected)
CREATE TABLE `products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `seller_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(100) DEFAULT NULL,
  `short_description` VARCHAR(500) DEFAULT NULL,
  `description` LONGTEXT DEFAULT NULL,
  `features` JSON DEFAULT NULL,
  `specifications` JSON DEFAULT NULL,
  `dimensions` JSON DEFAULT NULL,
  `material` VARCHAR(255) DEFAULT NULL,
  `color` VARCHAR(255) DEFAULT NULL,
  `warranty_months` INT DEFAULT NULL,
  `min_order_quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `max_order_quantity` INT UNSIGNED DEFAULT NULL,
  `unit` ENUM('piece','set','pair','box','carton','meter','square_meter') NOT NULL DEFAULT 'piece',
  `price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `sale_price` DECIMAL(15,2) DEFAULT NULL,
  `discount_price` DECIMAL(15,2) DEFAULT NULL,
  `discount_percentage` DECIMAL(5,2) DEFAULT NULL,
  `tax_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `availability_status` ENUM('in_stock','out_of_stock','pre_order','made_to_order') NOT NULL DEFAULT 'in_stock',
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `approved_by` BIGINT UNSIGNED DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_new_arrival` TINYINT(1) NOT NULL DEFAULT 0,
  `is_best_seller` TINYINT(1) NOT NULL DEFAULT 0,
  `rating` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `total_reviews` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_views` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_orders` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('Pending','Published','Hidden','Rejected') NOT NULL DEFAULT 'Pending',
  `meta_title` VARCHAR(255) DEFAULT NULL,
  `meta_description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  KEY `products_seller_id_foreign` (`seller_id`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_approved_by_foreign` (`approved_by`),
  KEY `products_status_index` (`status`),
  KEY `products_availability_status_index` (`availability_status`),
  KEY `products_price_index` (`price`),
  KEY `products_is_featured_index` (`is_featured`),
  KEY `products_rating_index` (`rating`),
  CONSTRAINT `products_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `seller_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for product_images (both image_path and image_url columns for compat)
CREATE TABLE `product_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `image_path` VARCHAR(500) NOT NULL,
  `image_url` VARCHAR(500) NOT NULL DEFAULT '',
  `alt_text` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_images_product_id_foreign` (`product_id`),
  KEY `product_images_is_primary_index` (`is_primary`),
  CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for product_specs
CREATE TABLE `product_specs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `spec_key` VARCHAR(100) NOT NULL,
  `spec_value` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_specs_product_id_foreign` (`product_id`),
  CONSTRAINT `product_specs_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for purchase_requirements (STEP 8 full fields + STEP 14 statuses)
CREATE TABLE `purchase_requirements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `buyer_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `product_name` VARCHAR(255) DEFAULT NULL,
  `required_quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit` ENUM('piece','set','pair','box','carton','meter','square_meter') NOT NULL DEFAULT 'piece',
  `budget_min` DECIMAL(15,2) DEFAULT NULL,
  `budget_max` DECIMAL(15,2) DEFAULT NULL,
  `preferred_location` VARCHAR(255) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `delivery_address` TEXT DEFAULT NULL,
  `expected_delivery` DATE DEFAULT NULL,
  `required_by_date` DATE DEFAULT NULL,
  `explanation_note` TEXT DEFAULT NULL,
  `specifications` JSON DEFAULT NULL,
  `attachments` JSON DEFAULT NULL,
  `total_quotes_received` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('Pending','Approved','Rejected','Deleted','Closed','Fake') NOT NULL DEFAULT 'Pending',
  `visibility` ENUM('public','verified_sellers','invited_only') NOT NULL DEFAULT 'public',
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `awarded_to` BIGINT UNSIGNED DEFAULT NULL,
  `awarded_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_requirements_slug_unique` (`slug`),
  KEY `purchase_requirements_buyer_id_foreign` (`buyer_id`),
  KEY `purchase_requirements_category_id_foreign` (`category_id`),
  KEY `purchase_requirements_awarded_to_foreign` (`awarded_to`),
  KEY `purchase_requirements_status_index` (`status`),
  KEY `purchase_requirements_visibility_index` (`visibility`),
  CONSTRAINT `purchase_requirements_buyer_id_foreign` FOREIGN KEY (`buyer_id`) REFERENCES `buyer_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_requirements_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_requirements_awarded_to_foreign` FOREIGN KEY (`awarded_to`) REFERENCES `seller_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for orders (STEP 14: Pending / Processing / Completed / Cancelled)
CREATE TABLE `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(50) NOT NULL,
  `buyer_id` BIGINT UNSIGNED NOT NULL,
  `seller_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(15,2) NOT NULL,
  `subtotal` DECIMAL(15,2) NOT NULL,
  `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `shipping_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `handling_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(15,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `payment_method` ENUM('credit_card','debit_card','net_banking','upi','wallet','cod','bank_transfer','cheque') DEFAULT NULL,
  `payment_status` ENUM('pending','paid','partially_paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  `payment_transaction_id` VARCHAR(255) DEFAULT NULL,
  `payment_at` TIMESTAMP NULL DEFAULT NULL,
  `status` ENUM('Pending','Processing','Completed','Cancelled','confirmed','packed','shipped','out_for_delivery','delivered','returned','refunded') NOT NULL DEFAULT 'Pending',
  `delivery_method` VARCHAR(100) DEFAULT NULL,
  `tracking_number` VARCHAR(100) DEFAULT NULL,
  `tracking_url` VARCHAR(500) DEFAULT NULL,
  `estimated_delivery_date` DATE DEFAULT NULL,
  `actual_delivery_date` DATE DEFAULT NULL,
  `shipping_address` JSON DEFAULT NULL,
  `billing_address` JSON DEFAULT NULL,
  `buyer_notes` TEXT DEFAULT NULL,
  `seller_notes` TEXT DEFAULT NULL,
  `admin_notes` TEXT DEFAULT NULL,
  `invoice_number` VARCHAR(50) DEFAULT NULL,
  `invoice_generated_at` TIMESTAMP NULL DEFAULT NULL,
  `cancelled_by` BIGINT UNSIGNED DEFAULT NULL,
  `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
  `cancellation_reason` VARCHAR(500) DEFAULT NULL,
  `return_requested_at` TIMESTAMP NULL DEFAULT NULL,
  `return_approved_at` TIMESTAMP NULL DEFAULT NULL,
  `return_reason` VARCHAR(500) DEFAULT NULL,
  `refund_amount` DECIMAL(15,2) DEFAULT NULL,
  `refunded_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  UNIQUE KEY `orders_invoice_number_unique` (`invoice_number`),
  KEY `orders_buyer_id_foreign` (`buyer_id`),
  KEY `orders_seller_id_foreign` (`seller_id`),
  KEY `orders_product_id_foreign` (`product_id`),
  KEY `orders_status_index` (`status`),
  KEY `orders_payment_status_index` (`payment_status`),
  KEY `orders_created_at_index` (`created_at`),
  CONSTRAINT `orders_buyer_id_foreign` FOREIGN KEY (`buyer_id`) REFERENCES `buyer_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `seller_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for notifications
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `type` ENUM('order','payment','rfq','product','system','admin','message','review') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `data` JSON DEFAULT NULL,
  `related_id` BIGINT UNSIGNED DEFAULT NULL,
  `related_type` VARCHAR(100) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `priority` ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_foreign` (`user_id`),
  KEY `notifications_type_index` (`type`),
  KEY `notifications_is_read_index` (`is_read`),
  KEY `notifications_priority_index` (`priority`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for emails
CREATE TABLE `emails` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_name` VARCHAR(255) DEFAULT NULL,
  `from_email` VARCHAR(255) NOT NULL,
  `to_email` VARCHAR(255) NOT NULL,
  `cc` JSON DEFAULT NULL,
  `bcc` JSON DEFAULT NULL,
  `reply_to` VARCHAR(255) DEFAULT NULL,
  `subject` VARCHAR(500) NOT NULL,
  `body` LONGTEXT NOT NULL,
  `attachments` JSON DEFAULT NULL,
  `headers` JSON DEFAULT NULL,
  `type` ENUM('transactional','marketing','notification','system') NOT NULL DEFAULT 'transactional',
  `status` ENUM('queued','sending','sent','failed','bounced','spam') NOT NULL DEFAULT 'queued',
  `error_message` TEXT DEFAULT NULL,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  `failed_at` TIMESTAMP NULL DEFAULT NULL,
  `opens_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `clicks_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_opened_at` TIMESTAMP NULL DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `emails_to_email_index` (`to_email`),
  KEY `emails_status_index` (`status`),
  KEY `emails_type_index` (`type`),
  KEY `emails_created_by_foreign` (`created_by`),
  CONSTRAINT `emails_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for activity_logs
CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `admin_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(100) NOT NULL,
  `subject_type` VARCHAR(255) DEFAULT NULL,
  `subject_id` BIGINT UNSIGNED DEFAULT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `url` VARCHAR(500) DEFAULT NULL,
  `method` VARCHAR(10) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_admin_user_id_foreign` (`admin_user_id`),
  KEY `activity_logs_action_index` (`action`),
  KEY `activity_logs_module_index` (`module`),
  KEY `activity_logs_created_at_index` (`created_at`),
  KEY `activity_logs_subject_index` (`subject_type`,`subject_id`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activity_logs_admin_user_id_foreign` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for settings
CREATE TABLE `settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(255) NOT NULL,
  `value` LONGTEXT DEFAULT NULL,
  `type` ENUM('string','integer','boolean','json','text','email','url','file') NOT NULL DEFAULT 'string',
  `group` VARCHAR(100) NOT NULL DEFAULT 'general',
  `label` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 0,
  `autoload` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_group_index` (`group`),
  KEY `settings_autoload_index` (`autoload`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for rfqs
CREATE TABLE `rfqs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rfq_number` VARCHAR(50) NOT NULL,
  `buyer_id` BIGINT UNSIGNED NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `category_id` BIGINT UNSIGNED DEFAULT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `unit` ENUM('piece','set','pair','box','carton','meter','square_meter') NOT NULL DEFAULT 'piece',
  `description` LONGTEXT NOT NULL,
  `specifications` JSON DEFAULT NULL,
  `material` VARCHAR(255) DEFAULT NULL,
  `color` VARCHAR(255) DEFAULT NULL,
  `dimensions` JSON DEFAULT NULL,
  `budget_range_min` DECIMAL(15,2) DEFAULT NULL,
  `budget_range_max` DECIMAL(15,2) DEFAULT NULL,
  `required_date` DATE DEFAULT NULL,
  `delivery_location` VARCHAR(500) DEFAULT NULL,
  `shipping_address` JSON DEFAULT NULL,
  `contact_person` VARCHAR(255) DEFAULT NULL,
  `contact_email` VARCHAR(255) DEFAULT NULL,
  `contact_phone` VARCHAR(20) DEFAULT NULL,
  `attachments` JSON DEFAULT NULL,
  `preferred_supplier_location` VARCHAR(255) DEFAULT NULL,
  `certification_required` JSON DEFAULT NULL,
  `payment_terms` VARCHAR(255) DEFAULT NULL,
  `delivery_terms` VARCHAR(255) DEFAULT NULL,
  `total_quotes` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('draft','submitted','New','In Review','Quoted','Closed','Rejected','under_review','open','quotations_received','negotiating','shortlisted','awarded','Cancelled','expired') NOT NULL DEFAULT 'draft',
  `visibility` ENUM('public','verified_sellers','invited_only') NOT NULL DEFAULT 'public',
  `published_at` TIMESTAMP NULL DEFAULT NULL,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `awarded_to` BIGINT UNSIGNED DEFAULT NULL,
  `awarded_at` TIMESTAMP NULL DEFAULT NULL,
  `awarded_quote_id` BIGINT UNSIGNED DEFAULT NULL,
  `updated_by` BIGINT UNSIGNED DEFAULT NULL,
  `rejection_reason` VARCHAR(500) DEFAULT NULL,
  `admin_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rfqs_rfq_number_unique` (`rfq_number`),
  KEY `rfqs_buyer_id_foreign` (`buyer_id`),
  KEY `rfqs_category_id_foreign` (`category_id`),
  KEY `rfqs_awarded_to_foreign` (`awarded_to`),
  KEY `rfqs_status_index` (`status`),
  KEY `rfqs_visibility_index` (`visibility`),
  KEY `rfqs_created_at_index` (`created_at`),
  CONSTRAINT `rfqs_buyer_id_foreign` FOREIGN KEY (`buyer_id`) REFERENCES `buyer_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rfqs_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rfqs_awarded_to_foreign` FOREIGN KEY (`awarded_to`) REFERENCES `seller_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for rfq_quotes
CREATE TABLE `rfq_quotes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rfq_id` BIGINT UNSIGNED NOT NULL,
  `quote_number` VARCHAR(100) NOT NULL,
  `price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `delivery_time` VARCHAR(100) DEFAULT NULL,
  `payment_terms` VARCHAR(255) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `attachment_path` VARCHAR(500) DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `rfq_quotes_rfq_id_foreign` (`rfq_id`),
  KEY `rfq_quotes_created_by_foreign` (`created_by`),
  CONSTRAINT `rfq_quotes_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `rfqs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rfq_quotes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for contacts
CREATE TABLE `contacts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `company` VARCHAR(255) DEFAULT NULL,
  `subject` VARCHAR(500) NOT NULL,
  `message` LONGTEXT NOT NULL,
  `type` ENUM('general','sales','support','partnership','career','tender') NOT NULL DEFAULT 'general',
  `source` ENUM('website','catalog','exhibition','referral','social_media','other') NOT NULL DEFAULT 'website',
  `attachments` JSON DEFAULT NULL,
  `preferred_contact_method` ENUM('email','phone','whatsapp','any') NOT NULL DEFAULT 'any',
  `preferred_time` VARCHAR(100) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `assigned_to` BIGINT UNSIGNED DEFAULT NULL,
  `status` ENUM('new','read','replied','resolved','spam','Closed') NOT NULL DEFAULT 'new',
  `priority` ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `admin_notes` TEXT DEFAULT NULL,
  `first_reply_at` TIMESTAMP NULL DEFAULT NULL,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `contacts_email_index` (`email`),
  KEY `contacts_type_index` (`type`),
  KEY `contacts_status_index` (`status`),
  KEY `contacts_priority_index` (`priority`),
  KEY `contacts_assigned_to_foreign` (`assigned_to`),
  KEY `contacts_created_at_index` (`created_at`),
  CONSTRAINT `contacts_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for job_applications
CREATE TABLE `job_applications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_title` VARCHAR(255) NOT NULL,
  `job_id` VARCHAR(50) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `alternate_phone` VARCHAR(20) DEFAULT NULL,
  `gender` ENUM('male','female','other') DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `marital_status` ENUM('single','married','divorced','widowed') DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'India',
  `pincode` VARCHAR(20) DEFAULT NULL,
  `nationality` VARCHAR(100) NOT NULL DEFAULT 'Indian',
  `highest_qualification` VARCHAR(255) DEFAULT NULL,
  `field_of_study` VARCHAR(255) DEFAULT NULL,
  `institution_name` VARCHAR(255) DEFAULT NULL,
  `year_of_passing` YEAR DEFAULT NULL,
  `percentage` DECIMAL(5,2) DEFAULT NULL,
  `total_experience_years` DECIMAL(4,1) NOT NULL DEFAULT 0.0,
  `relevant_experience_years` DECIMAL(4,1) NOT NULL DEFAULT 0.0,
  `current_company` VARCHAR(255) DEFAULT NULL,
  `current_designation` VARCHAR(255) DEFAULT NULL,
  `current_ctc` DECIMAL(12,2) DEFAULT NULL,
  `expected_ctc` DECIMAL(12,2) DEFAULT NULL,
  `notice_period` ENUM('immediate','15_days','30_days','45_days','60_days','90_days','more_than_90_days') DEFAULT NULL,
  `skills` JSON DEFAULT NULL,
  `languages_known` JSON DEFAULT NULL,
  `expected_joining_date` DATE DEFAULT NULL,
  `cover_letter` LONGTEXT DEFAULT NULL,
  `resume_path` VARCHAR(500) NOT NULL,
  `photo_path` VARCHAR(500) DEFAULT NULL,
  `portfolio_url` VARCHAR(500) DEFAULT NULL,
  `linkedin_url` VARCHAR(255) DEFAULT NULL,
  `references` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `assigned_to` BIGINT UNSIGNED DEFAULT NULL,
  `status` ENUM('received','under_review','shortlisted','interview_scheduled','interviewed','offered','hired','rejected','on_hold','withdrawn') NOT NULL DEFAULT 'received',
  `stage` ENUM('screening','technical_round_1','technical_round_2','hr_round','final_round') DEFAULT NULL,
  `rating` DECIMAL(3,2) DEFAULT NULL,
  `admin_notes` TEXT DEFAULT NULL,
  `interview_notes` TEXT DEFAULT NULL,
  `interview_date` DATETIME DEFAULT NULL,
  `interview_mode` ENUM('in_person','phone','video_call') DEFAULT NULL,
  `interviewer_name` VARCHAR(255) DEFAULT NULL,
  `offer_sent_at` TIMESTAMP NULL DEFAULT NULL,
  `offer_accepted_at` TIMESTAMP NULL DEFAULT NULL,
  `rejected_at` TIMESTAMP NULL DEFAULT NULL,
  `rejection_reason` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `job_applications_email_index` (`email`),
  KEY `job_applications_phone_index` (`phone`),
  KEY `job_applications_status_index` (`status`),
  KEY `job_applications_job_id_index` (`job_id`),
  KEY `job_applications_assigned_to_foreign` (`assigned_to`),
  KEY `job_applications_created_at_index` (`created_at`),
  CONSTRAINT `job_applications_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for newsletters
CREATE TABLE `newsletters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `company` VARCHAR(255) DEFAULT NULL,
  `interests` JSON DEFAULT NULL,
  `source` ENUM('website_footer','popup','checkout','registration','manual','other') NOT NULL DEFAULT 'website_footer',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `subscription_status` ENUM('subscribed','unsubscribed','bounced','complained','Pending') NOT NULL DEFAULT 'subscribed',
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `verification_token` VARCHAR(255) DEFAULT NULL,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `subscribed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unsubscribed_at` TIMESTAMP NULL DEFAULT NULL,
  `last_sent_at` TIMESTAMP NULL DEFAULT NULL,
  `total_emails_sent` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_opens` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_clicks` INT UNSIGNED NOT NULL DEFAULT 0,
  `tags` JSON DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletters_email_unique` (`email`),
  KEY `newsletters_subscription_status_index` (`subscription_status`),
  KEY `newsletters_source_index` (`source`),
  KEY `newsletters_subscribed_at_index` (`subscribed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- DEFAULT DATA INSERTS
-- --------------------------------------------------------

-- Insert Default Admin User (password: password)
INSERT INTO `admin_users` (`username`, `email`, `password_hash`, `full_name`, `role`, `status`) VALUES
('admin', 'admin@opcieas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', 'active');

-- Insert Default Categories (match CANONICAL_CATEGORIES in src/lib/images.ts)
INSERT INTO `categories` (`name`, `slug`, `description`, `sort_order`, `is_featured`, `status`) VALUES
('Office Furniture', 'office-furniture', 'Premium office furniture including workstations, desks, chairs, meeting tables, storage cabinets, and modular office solutions.', 1, 1, 'active'),
('Educational Furniture', 'educational-furniture', 'Complete range of educational furniture for schools, colleges, universities, coaching centers, and training institutes.', 2, 1, 'active'),
('School Furniture', 'school-furniture', 'Durable and ergonomic school furniture including student desks, benches, classroom chairs, library furniture, and lab tables.', 3, 1, 'active'),
('Hospital Furniture', 'hospital-furniture', 'Hospital-grade furniture including patient beds, examination tables, ward furniture, doctor chairs, and medical storage solutions.', 4, 1, 'active'),
('Hostel Furniture', 'hostel-furniture', 'Comfortable and space-saving hostel furniture including bunk beds, study tables, wardrobes, lockers, and common area seating.', 5, 0, 'active'),
('Industrial Storage', 'industrial-storage', 'Heavy-duty industrial storage solutions including pallet racks, shelving systems, lockers, tool cabinets, and warehouse equipment.', 6, 0, 'active'),
('Bathroom Collection', 'bathroom-collection', 'Modern bathroom collection including vanities, cabinets, mirror cabinets, storage units, and accessories for commercial and residential use.', 7, 0, 'active'),
('Letter Box', 'letter-boxes', 'Secure and stylish letter boxes for apartments, offices, societies, and individual homes with multiple size and material options.', 8, 0, 'active');

COMMIT;
