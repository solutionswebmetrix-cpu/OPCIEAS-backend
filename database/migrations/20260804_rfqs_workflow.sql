-- Migration: add RFQ workflow columns and quote storage
ALTER TABLE `rfqs`
  ADD COLUMN IF NOT EXISTS `updated_by` BIGINT UNSIGNED DEFAULT NULL AFTER `awarded_quote_id`,
  ADD COLUMN IF NOT EXISTS `rejection_reason` VARCHAR(500) DEFAULT NULL AFTER `updated_by`,
  ADD COLUMN IF NOT EXISTS `closed_by` BIGINT UNSIGNED DEFAULT NULL AFTER `rejection_reason`,
  ADD COLUMN IF NOT EXISTS `closed_at` TIMESTAMP NULL DEFAULT NULL AFTER `closed_by`,
  ADD COLUMN IF NOT EXISTS `admin_notes` TEXT DEFAULT NULL AFTER `closed_at`;

CREATE TABLE IF NOT EXISTS `rfq_quotes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rfq_id` BIGINT UNSIGNED NOT NULL,
  `quote_number` VARCHAR(100) NOT NULL,
  `price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `gst_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `delivery_time` VARCHAR(100) DEFAULT NULL,
  `payment_terms` VARCHAR(255) DEFAULT NULL,
  `validity` VARCHAR(100) DEFAULT NULL,
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

ALTER TABLE `rfqs`
  MODIFY COLUMN `status` ENUM('draft','submitted','New','In Review','Quoted','Closed','Rejected','under_review','open','quotations_received','negotiating','shortlisted','awarded','Cancelled','expired') NOT NULL DEFAULT 'draft';
