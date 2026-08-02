-- =====================================================================
-- Kupiana — Enterprise E-Commerce Schema
-- =====================================================================
-- Engine  : InnoDB
-- Charset : utf8mb4 / utf8mb4_unicode_ci
-- Target  : MySQL 5.7+ / MariaDB 10.3+
--
-- CONVENTIONS
-- -----------
-- Every table carries the standard block, because MY_Model depends on it:
--
--     status      VARCHAR(20)      record lifecycle ('active', 'inactive', ...)
--     created_at  DATETIME
--     updated_at  DATETIME
--     deleted_at  DATETIME         NULL = live row; non-NULL = soft deleted
--     created_by  BIGINT UNSIGNED  acting user id
--     updated_by  BIGINT UNSIGNED  acting user id
--
-- DELIBERATE DEVIATIONS
-- ---------------------
-- 1. created_by / updated_by are INDEXED but carry NO foreign key.
--    Constraining them across ~70 tables would make `users` effectively
--    undeletable and force a strict seed order, for no real integrity gain.
--
-- 2. inventory.variant_id, batches.variant_id and stock_movements.variant_id
--    are NOT NULL DEFAULT 0 with no FK (0 means "product has no variant").
--    MySQL treats NULLs as DISTINCT inside a UNIQUE key, so a nullable column
--    would permit duplicate stock rows for the same product+warehouse.
--    Guaranteeing one stock row per (product, variant, warehouse) matters more
--    than the constraint here.
--
-- 3. A few tables need a domain status AND the MY_Model lifecycle status.
--    Where they collide the domain column is named explicitly and `status`
--    stays the lifecycle column:
--        payments.status         = gateway state,  payments.status_flag = lifecycle
--        refunds.refund_status, return_requests.return_status,
--        shipments.shipment_status, backups.backup_status
--    (payments is the one inversion — see its table comment.)
--
-- 4. Money is DECIMAL(12,2) everywhere; never FLOAT. Percentages DECIMAL(5,2).
--
-- This file is dependency-ordered. Run it top to bottom, then seed.sql.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `kupiana`
	CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kupiana`;

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS = 0;

-- Legacy tables from the original scaffold that no longer exist in this schema.
-- They must be dropped explicitly: with FOREIGN_KEY_CHECKS = 0 a leftover table
-- keeps its FK definition pointing at `users`, and because the old users.id was
-- INT UNSIGNED while the new one is BIGINT UNSIGNED, recreating `users` then
-- fails with errno 150.
DROP TABLE IF EXISTS `user_addresses`;

-- Drop in reverse dependency order so re-running is safe.
DROP TABLE IF EXISTS
	`backups`, `audit_logs`, `notifications`, `sms_templates`, `email_templates`, `settings`,
	`seo_meta`, `newsletter_subscribers`, `contact_messages`, `faqs`, `testimonials`,
	`blog_posts`, `blog_categories`, `pages`, `banners`,
	`coupon_usages`, `invoices`, `shipment_tracking`, `shipments`, `return_items`,
	`return_requests`, `refunds`, `payment_logs`, `payments`, `order_status_history`,
	`order_items`, `orders`, `offers`, `coupon_restrictions`, `coupons`,
	`wallet_transactions`, `wallets`, `wishlists`, `cart_items`, `carts`, `addresses`,
	`stock_movements`, `stock_adjustments`, `purchase_order_items`, `purchase_orders`,
	`batches`, `inventory`, `suppliers`, `warehouses`,
	`review_images`, `product_reviews`, `product_tags`, `tags`, `product_images`,
	`variant_attribute_values`, `product_variants`, `attribute_values`, `attributes`,
	`product_categories`, `products`, `brands`, `categories`,
	`tax_rates`, `hsn_codes`, `currencies`, `states`, `countries`,
	`user_sessions`, `login_attempts`, `otp_codes`, `email_verifications`,
	`password_resets`, `user_roles`, `role_permissions`, `permissions`, `roles`, `users`;

-- =====================================================================
-- 1. AUTHENTICATION, ROLES & PERMISSIONS
-- =====================================================================

CREATE TABLE `users` (
	`id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`uuid`                  CHAR(36)        NOT NULL,
	`first_name`            VARCHAR(100)    NOT NULL,
	`last_name`             VARCHAR(100)    DEFAULT NULL,
	`email`                 VARCHAR(191)    NOT NULL,
	`phone`                 VARCHAR(20)     DEFAULT NULL,
	`password`              VARCHAR(255)    NOT NULL COMMENT 'bcrypt via password_hash()',
	`avatar`                VARCHAR(255)    DEFAULT NULL,
	`user_type`             ENUM('customer','staff') NOT NULL DEFAULT 'customer',
	`gender`                ENUM('male','female','other') DEFAULT NULL,
	`date_of_birth`         DATE            DEFAULT NULL,
	`email_verified_at`     DATETIME        DEFAULT NULL,
	`phone_verified_at`     DATETIME        DEFAULT NULL,
	`last_login_at`         DATETIME        DEFAULT NULL,
	`last_login_ip`         VARCHAR(45)     DEFAULT NULL,
	`failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
	`locked_until`          DATETIME        DEFAULT NULL,
	`remember_token`        VARCHAR(255)    DEFAULT NULL,
	`status`                VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`            DATETIME        DEFAULT NULL,
	`updated_at`            DATETIME        DEFAULT NULL,
	`deleted_at`            DATETIME        DEFAULT NULL,
	`created_by`            BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`            BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_users_email` (`email`),
	UNIQUE KEY `uq_users_uuid` (`uuid`),
	KEY `idx_users_phone` (`phone`),
	KEY `idx_users_type` (`user_type`),
	KEY `idx_users_status` (`status`),
	KEY `idx_users_deleted` (`deleted_at`),
	KEY `idx_users_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
	`id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name`        VARCHAR(100)    NOT NULL,
	`slug`        VARCHAR(100)    NOT NULL,
	`description` VARCHAR(255)    DEFAULT NULL,
	`is_system`   TINYINT(1)      NOT NULL DEFAULT 0 COMMENT 'system roles cannot be deleted',
	`status`      VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`  DATETIME        DEFAULT NULL,
	`updated_at`  DATETIME        DEFAULT NULL,
	`deleted_at`  DATETIME        DEFAULT NULL,
	`created_by`  BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`  BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_roles_slug` (`slug`),
	KEY `idx_roles_status` (`status`),
	KEY `idx_roles_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
	`id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name`        VARCHAR(150)    NOT NULL,
	`slug`        VARCHAR(150)    NOT NULL COMMENT 'module.action, e.g. products.edit',
	`module`      VARCHAR(80)     NOT NULL,
	`action`      VARCHAR(80)     NOT NULL,
	`description` VARCHAR(255)    DEFAULT NULL,
	`status`      VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`  DATETIME        DEFAULT NULL,
	`updated_at`  DATETIME        DEFAULT NULL,
	`deleted_at`  DATETIME        DEFAULT NULL,
	`created_by`  BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`  BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_permissions_slug` (`slug`),
	KEY `idx_permissions_module` (`module`),
	KEY `idx_permissions_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
	`id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`role_id`       BIGINT UNSIGNED NOT NULL,
	`permission_id` BIGINT UNSIGNED NOT NULL,
	`status`        VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`    DATETIME        DEFAULT NULL,
	`updated_at`    DATETIME        DEFAULT NULL,
	`deleted_at`    DATETIME        DEFAULT NULL,
	`created_by`    BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`    BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_role_permission` (`role_id`, `permission_id`),
	KEY `idx_rp_permission` (`permission_id`),
	CONSTRAINT `fk_rp_role`       FOREIGN KEY (`role_id`)       REFERENCES `roles` (`id`)       ON DELETE CASCADE,
	CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_roles` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id`    BIGINT UNSIGNED NOT NULL,
	`role_id`    BIGINT UNSIGNED NOT NULL,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_user_role` (`user_id`, `role_id`),
	KEY `idx_ur_role` (`role_id`),
	CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id`    BIGINT UNSIGNED DEFAULT NULL,
	`email`      VARCHAR(191)    NOT NULL,
	`token`      VARCHAR(255)    NOT NULL COMMENT 'SHA-256 of the emailed token',
	`ip_address` VARCHAR(45)     DEFAULT NULL,
	`expires_at` DATETIME        NOT NULL,
	`used_at`    DATETIME        DEFAULT NULL,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_pr_email` (`email`),
	KEY `idx_pr_token` (`token`),
	KEY `idx_pr_user` (`user_id`),
	CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `email_verifications` (
	`id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id`     BIGINT UNSIGNED NOT NULL,
	`email`       VARCHAR(191)    NOT NULL,
	`token`       VARCHAR(255)    NOT NULL,
	`expires_at`  DATETIME        NOT NULL,
	`verified_at` DATETIME        DEFAULT NULL,
	`status`      VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`  DATETIME        DEFAULT NULL,
	`updated_at`  DATETIME        DEFAULT NULL,
	`deleted_at`  DATETIME        DEFAULT NULL,
	`created_by`  BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`  BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_ev_token` (`token`),
	KEY `idx_ev_user` (`user_id`),
	CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `otp_codes` (
	`id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id`     BIGINT UNSIGNED DEFAULT NULL,
	`identifier`  VARCHAR(191)    NOT NULL COMMENT 'email or phone the OTP was sent to',
	`channel`     ENUM('email','sms') NOT NULL DEFAULT 'email',
	`purpose`     VARCHAR(50)     NOT NULL DEFAULT 'login' COMMENT 'login|verify|reset|checkout',
	`otp_hash`    VARCHAR(255)    NOT NULL COMMENT 'never store the plain OTP',
	`attempts`    TINYINT UNSIGNED NOT NULL DEFAULT 0,
	`expires_at`  DATETIME        NOT NULL,
	`verified_at` DATETIME        DEFAULT NULL,
	`status`      VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`  DATETIME        DEFAULT NULL,
	`updated_at`  DATETIME        DEFAULT NULL,
	`deleted_at`  DATETIME        DEFAULT NULL,
	`created_by`  BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`  BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_otp_identifier` (`identifier`, `purpose`),
	KEY `idx_otp_user` (`user_id`),
	CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`email`      VARCHAR(191)    NOT NULL,
	`ip_address` VARCHAR(45)     NOT NULL,
	`user_agent` VARCHAR(255)    DEFAULT NULL,
	`successful` TINYINT(1)      NOT NULL DEFAULT 0,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_la_email_time` (`email`, `created_at`),
	KEY `idx_la_ip_time` (`ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_sessions` (
	`id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id`      BIGINT UNSIGNED NOT NULL,
	`token_hash`   VARCHAR(255)    NOT NULL COMMENT 'hashed remember-me token',
	`ip_address`   VARCHAR(45)     DEFAULT NULL,
	`user_agent`   VARCHAR(255)    DEFAULT NULL,
	`last_used_at` DATETIME        DEFAULT NULL,
	`expires_at`   DATETIME        NOT NULL,
	`revoked_at`   DATETIME        DEFAULT NULL,
	`status`       VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`   DATETIME        DEFAULT NULL,
	`updated_at`   DATETIME        DEFAULT NULL,
	`deleted_at`   DATETIME        DEFAULT NULL,
	`created_by`   BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`   BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_us_token` (`token_hash`),
	KEY `idx_us_user` (`user_id`),
	CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. GEOGRAPHY, CURRENCY & TAX REFERENCE
-- =====================================================================

CREATE TABLE `countries` (
	`id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name`          VARCHAR(100)    NOT NULL,
	`iso2`          CHAR(2)         NOT NULL,
	`iso3`          CHAR(3)         DEFAULT NULL,
	`phone_code`    VARCHAR(10)     DEFAULT NULL,
	`currency_code` CHAR(3)         DEFAULT NULL,
	`status`        VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`    DATETIME        DEFAULT NULL,
	`updated_at`    DATETIME        DEFAULT NULL,
	`deleted_at`    DATETIME        DEFAULT NULL,
	`created_by`    BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`    BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_countries_iso2` (`iso2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `states` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`country_id` BIGINT UNSIGNED NOT NULL,
	`name`       VARCHAR(100)    NOT NULL,
	`code`       VARCHAR(10)     DEFAULT NULL COMMENT 'GST state code for India',
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_states_country` (`country_id`),
	KEY `idx_states_code` (`code`),
	CONSTRAINT `fk_states_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `currencies` (
	`id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`code`          CHAR(3)         NOT NULL,
	`name`          VARCHAR(60)     NOT NULL,
	`symbol`        VARCHAR(10)     NOT NULL,
	`exchange_rate` DECIMAL(12,6)   NOT NULL DEFAULT 1.000000,
	`is_default`    TINYINT(1)      NOT NULL DEFAULT 0,
	`status`        VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`    DATETIME        DEFAULT NULL,
	`updated_at`    DATETIME        DEFAULT NULL,
	`deleted_at`    DATETIME        DEFAULT NULL,
	`created_by`    BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`    BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_currencies_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hsn_codes` (
	`id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`code`        VARCHAR(20)     NOT NULL,
	`description` VARCHAR(255)    DEFAULT NULL,
	`gst_rate`    DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
	`status`      VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`  DATETIME        DEFAULT NULL,
	`updated_at`  DATETIME        DEFAULT NULL,
	`deleted_at`  DATETIME        DEFAULT NULL,
	`created_by`  BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`  BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_hsn_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tax_rates` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name`       VARCHAR(100)    NOT NULL,
	`rate`       DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
	`type`       ENUM('gst','igst','vat','none') NOT NULL DEFAULT 'gst',
	`hsn_code`   VARCHAR(20)     DEFAULT NULL,
	`is_default` TINYINT(1)      NOT NULL DEFAULT 0,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_tax_rates_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3. CATALOG
-- =====================================================================

CREATE TABLE `categories` (
	`id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`parent_id`        BIGINT UNSIGNED DEFAULT NULL,
	`name`             VARCHAR(150)    NOT NULL,
	`slug`             VARCHAR(191)    NOT NULL,
	`description`      TEXT,
	`image`            VARCHAR(255)    DEFAULT NULL,
	`icon`             VARCHAR(80)     DEFAULT NULL,
	`banner`           VARCHAR(255)    DEFAULT NULL,
	`level`            TINYINT UNSIGNED NOT NULL DEFAULT 0,
	`sort_order`       INT             NOT NULL DEFAULT 0,
	`is_featured`      TINYINT(1)      NOT NULL DEFAULT 0,
	`show_in_menu`     TINYINT(1)      NOT NULL DEFAULT 1,
	`product_count`    INT UNSIGNED    NOT NULL DEFAULT 0,
	`meta_title`       VARCHAR(191)    DEFAULT NULL,
	`meta_description` VARCHAR(255)    DEFAULT NULL,
	`meta_keywords`    VARCHAR(255)    DEFAULT NULL,
	`status`           VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`       DATETIME        DEFAULT NULL,
	`updated_at`       DATETIME        DEFAULT NULL,
	`deleted_at`       DATETIME        DEFAULT NULL,
	`created_by`       BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`       BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_categories_slug` (`slug`),
	KEY `idx_categories_parent` (`parent_id`),
	KEY `idx_categories_status` (`status`),
	KEY `idx_categories_deleted` (`deleted_at`),
	KEY `idx_categories_featured` (`is_featured`),
	CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `brands` (
	`id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name`             VARCHAR(150)    NOT NULL,
	`slug`             VARCHAR(191)    NOT NULL,
	`logo`             VARCHAR(255)    DEFAULT NULL,
	`banner`           VARCHAR(255)    DEFAULT NULL,
	`description`      TEXT,
	`website`          VARCHAR(255)    DEFAULT NULL,
	`sort_order`       INT             NOT NULL DEFAULT 0,
	`is_featured`      TINYINT(1)      NOT NULL DEFAULT 0,
	`meta_title`       VARCHAR(191)    DEFAULT NULL,
	`meta_description` VARCHAR(255)    DEFAULT NULL,
	`status`           VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`       DATETIME        DEFAULT NULL,
	`updated_at`       DATETIME        DEFAULT NULL,
	`deleted_at`       DATETIME        DEFAULT NULL,
	`created_by`       BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`       BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_brands_slug` (`slug`),
	KEY `idx_brands_status` (`status`),
	KEY `idx_brands_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
	`id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`uuid`                CHAR(36)        NOT NULL,
	`name`                VARCHAR(255)    NOT NULL,
	`slug`                VARCHAR(191)    NOT NULL,
	`sku`                 VARCHAR(80)     NOT NULL,
	`barcode`             VARCHAR(80)     DEFAULT NULL,
	`brand_id`            BIGINT UNSIGNED DEFAULT NULL,
	`category_id`         BIGINT UNSIGNED DEFAULT NULL COMMENT 'primary category',
	`tax_rate_id`         BIGINT UNSIGNED DEFAULT NULL,
	`type`                ENUM('simple','variable','digital') NOT NULL DEFAULT 'simple',
	`short_description`   VARCHAR(500)    DEFAULT NULL,
	`description`         LONGTEXT,
	`specifications`      JSON            DEFAULT NULL,
	`price`               DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`mrp`                 DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`cost_price`          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`hsn_code`            VARCHAR(20)     DEFAULT NULL,
	`manage_stock`        TINYINT(1)      NOT NULL DEFAULT 1,
	`stock_quantity`      INT             NOT NULL DEFAULT 0,
	`low_stock_threshold` INT             NOT NULL DEFAULT 10,
	`allow_backorder`     TINYINT(1)      NOT NULL DEFAULT 0,
	`weight`              DECIMAL(10,3)   NOT NULL DEFAULT 0.000 COMMENT 'kg',
	`length`              DECIMAL(10,2)   NOT NULL DEFAULT 0.00 COMMENT 'cm',
	`width`               DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
	`height`              DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
	`is_featured`         TINYINT(1)      NOT NULL DEFAULT 0,
	`is_trending`         TINYINT(1)      NOT NULL DEFAULT 0,
	`is_bestseller`       TINYINT(1)      NOT NULL DEFAULT 0,
	`is_new_arrival`      TINYINT(1)      NOT NULL DEFAULT 0,
	`video_url`           VARCHAR(255)    DEFAULT NULL,
	`rating_average`      DECIMAL(3,2)    NOT NULL DEFAULT 0.00,
	`rating_count`        INT UNSIGNED    NOT NULL DEFAULT 0,
	`view_count`          INT UNSIGNED    NOT NULL DEFAULT 0,
	`sold_count`          INT UNSIGNED    NOT NULL DEFAULT 0,
	`published_at`        DATETIME        DEFAULT NULL,
	`meta_title`          VARCHAR(191)    DEFAULT NULL,
	`meta_description`    VARCHAR(255)    DEFAULT NULL,
	`meta_keywords`       VARCHAR(255)    DEFAULT NULL,
	`status`              VARCHAR(20)     NOT NULL DEFAULT 'draft',
	`created_at`          DATETIME        DEFAULT NULL,
	`updated_at`          DATETIME        DEFAULT NULL,
	`deleted_at`          DATETIME        DEFAULT NULL,
	`created_by`          BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`          BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_products_slug` (`slug`),
	UNIQUE KEY `uq_products_sku` (`sku`),
	UNIQUE KEY `uq_products_uuid` (`uuid`),
	KEY `idx_products_brand` (`brand_id`),
	KEY `idx_products_category` (`category_id`),
	KEY `idx_products_status_deleted` (`status`, `deleted_at`),
	KEY `idx_products_price` (`price`),
	KEY `idx_products_featured` (`is_featured`),
	KEY `idx_products_bestseller` (`is_bestseller`),
	KEY `idx_products_published` (`published_at`),
	KEY `idx_products_barcode` (`barcode`),
	FULLTEXT KEY `ft_products_search` (`name`, `short_description`),
	CONSTRAINT `fk_products_brand`    FOREIGN KEY (`brand_id`)    REFERENCES `brands` (`id`)     ON DELETE SET NULL,
	CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
	CONSTRAINT `fk_products_tax`      FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_categories` (
	`id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`product_id`  BIGINT UNSIGNED NOT NULL,
	`category_id` BIGINT UNSIGNED NOT NULL,
	`status`      VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`  DATETIME        DEFAULT NULL,
	`updated_at`  DATETIME        DEFAULT NULL,
	`deleted_at`  DATETIME        DEFAULT NULL,
	`created_by`  BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`  BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_product_category` (`product_id`, `category_id`),
	KEY `idx_pc_category` (`category_id`),
	CONSTRAINT `fk_pc_product`  FOREIGN KEY (`product_id`)  REFERENCES `products` (`id`)   ON DELETE CASCADE,
	CONSTRAINT `fk_pc_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attributes` (
	`id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name`          VARCHAR(100)    NOT NULL,
	`slug`          VARCHAR(100)    NOT NULL,
	`type`          ENUM('select','color','button','text') NOT NULL DEFAULT 'select',
	`is_variation`  TINYINT(1)      NOT NULL DEFAULT 1 COMMENT 'usable to build variants',
	`is_filterable` TINYINT(1)      NOT NULL DEFAULT 1,
	`sort_order`    INT             NOT NULL DEFAULT 0,
	`status`        VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`    DATETIME        DEFAULT NULL,
	`updated_at`    DATETIME        DEFAULT NULL,
	`deleted_at`    DATETIME        DEFAULT NULL,
	`created_by`    BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`    BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_attributes_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attribute_values` (
	`id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`attribute_id` BIGINT UNSIGNED NOT NULL,
	`value`        VARCHAR(150)    NOT NULL,
	`slug`         VARCHAR(150)    NOT NULL,
	`color_code`   VARCHAR(10)     DEFAULT NULL,
	`image`        VARCHAR(255)    DEFAULT NULL,
	`sort_order`   INT             NOT NULL DEFAULT 0,
	`status`       VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`   DATETIME        DEFAULT NULL,
	`updated_at`   DATETIME        DEFAULT NULL,
	`deleted_at`   DATETIME        DEFAULT NULL,
	`created_by`   BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`   BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_attribute_value` (`attribute_id`, `slug`),
	CONSTRAINT `fk_av_attribute` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_variants` (
	`id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`product_id`     BIGINT UNSIGNED NOT NULL,
	`sku`            VARCHAR(80)     NOT NULL,
	`barcode`        VARCHAR(80)     DEFAULT NULL,
	`name`           VARCHAR(255)    DEFAULT NULL COMMENT 'e.g. "Red / Large"',
	`price`          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`mrp`            DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`cost_price`     DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`stock_quantity` INT             NOT NULL DEFAULT 0,
	`weight`         DECIMAL(10,3)   NOT NULL DEFAULT 0.000,
	`image`          VARCHAR(255)    DEFAULT NULL,
	`is_default`     TINYINT(1)      NOT NULL DEFAULT 0,
	`status`         VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`     DATETIME        DEFAULT NULL,
	`updated_at`     DATETIME        DEFAULT NULL,
	`deleted_at`     DATETIME        DEFAULT NULL,
	`created_by`     BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`     BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_variants_sku` (`sku`),
	KEY `idx_variants_product` (`product_id`),
	KEY `idx_variants_barcode` (`barcode`),
	CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `variant_attribute_values` (
	`id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`variant_id`         BIGINT UNSIGNED NOT NULL,
	`attribute_id`       BIGINT UNSIGNED NOT NULL,
	`attribute_value_id` BIGINT UNSIGNED NOT NULL,
	`status`             VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`         DATETIME        DEFAULT NULL,
	`updated_at`         DATETIME        DEFAULT NULL,
	`deleted_at`         DATETIME        DEFAULT NULL,
	`created_by`         BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`         BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_variant_attribute` (`variant_id`, `attribute_id`),
	KEY `idx_vav_value` (`attribute_value_id`),
	CONSTRAINT `fk_vav_variant`   FOREIGN KEY (`variant_id`)         REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_vav_attribute` FOREIGN KEY (`attribute_id`)       REFERENCES `attributes` (`id`)       ON DELETE CASCADE,
	CONSTRAINT `fk_vav_value`     FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_images` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`product_id` BIGINT UNSIGNED NOT NULL,
	`variant_id` BIGINT UNSIGNED DEFAULT NULL,
	`image_path` VARCHAR(255)    NOT NULL,
	`alt_text`   VARCHAR(191)    DEFAULT NULL,
	`sort_order` INT             NOT NULL DEFAULT 0,
	`is_primary` TINYINT(1)      NOT NULL DEFAULT 0,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_pi_product` (`product_id`, `sort_order`),
	KEY `idx_pi_variant` (`variant_id`),
	CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)         ON DELETE CASCADE,
	CONSTRAINT `fk_pi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tags` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name`       VARCHAR(100)    NOT NULL,
	`slug`       VARCHAR(100)    NOT NULL,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_tags_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_tags` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`product_id` BIGINT UNSIGNED NOT NULL,
	`tag_id`     BIGINT UNSIGNED NOT NULL,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_product_tag` (`product_id`, `tag_id`),
	KEY `idx_pt_tag` (`tag_id`),
	CONSTRAINT `fk_pt_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_pt_tag`     FOREIGN KEY (`tag_id`)     REFERENCES `tags` (`id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_reviews` (
	`id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`product_id`           BIGINT UNSIGNED NOT NULL,
	`user_id`              BIGINT UNSIGNED DEFAULT NULL,
	`order_id`             BIGINT UNSIGNED DEFAULT NULL,
	`reviewer_name`        VARCHAR(150)    DEFAULT NULL,
	`reviewer_email`       VARCHAR(191)    DEFAULT NULL,
	`rating`               TINYINT UNSIGNED NOT NULL DEFAULT 5,
	`title`                VARCHAR(191)    DEFAULT NULL,
	`comment`              TEXT,
	`is_verified_purchase` TINYINT(1)      NOT NULL DEFAULT 0,
	`is_approved`          TINYINT(1)      NOT NULL DEFAULT 0,
	`helpful_count`        INT UNSIGNED    NOT NULL DEFAULT 0,
	`admin_reply`          TEXT,
	`replied_at`           DATETIME        DEFAULT NULL,
	`status`               VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`           DATETIME        DEFAULT NULL,
	`updated_at`           DATETIME        DEFAULT NULL,
	`deleted_at`           DATETIME        DEFAULT NULL,
	`created_by`           BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`           BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_prv_product_approved` (`product_id`, `is_approved`),
	KEY `idx_prv_user` (`user_id`),
	KEY `idx_prv_rating` (`rating`),
	CONSTRAINT `fk_prv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_prv_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `review_images` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`review_id`  BIGINT UNSIGNED NOT NULL,
	`image_path` VARCHAR(255)    NOT NULL,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_ri_review` (`review_id`),
	CONSTRAINT `fk_ri_review` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. INVENTORY & PURCHASING
-- =====================================================================

CREATE TABLE `warehouses` (
	`id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name`           VARCHAR(150)    NOT NULL,
	`code`           VARCHAR(50)     NOT NULL,
	`contact_person` VARCHAR(150)    DEFAULT NULL,
	`phone`          VARCHAR(20)     DEFAULT NULL,
	`email`          VARCHAR(191)    DEFAULT NULL,
	`address_line1`  VARCHAR(255)    DEFAULT NULL,
	`address_line2`  VARCHAR(255)    DEFAULT NULL,
	`city`           VARCHAR(100)    DEFAULT NULL,
	`state`          VARCHAR(100)    DEFAULT NULL,
	`state_code`     VARCHAR(10)     DEFAULT NULL,
	`postal_code`    VARCHAR(20)     DEFAULT NULL,
	`country`        VARCHAR(100)    NOT NULL DEFAULT 'India',
	`is_default`     TINYINT(1)      NOT NULL DEFAULT 0,
	`status`         VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`     DATETIME        DEFAULT NULL,
	`updated_at`     DATETIME        DEFAULT NULL,
	`deleted_at`     DATETIME        DEFAULT NULL,
	`created_by`     BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`     BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_warehouses_code` (`code`),
	KEY `idx_warehouses_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `suppliers` (
	`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name`            VARCHAR(191)    NOT NULL,
	`code`            VARCHAR(50)     NOT NULL,
	`contact_person`  VARCHAR(150)    DEFAULT NULL,
	`email`           VARCHAR(191)    DEFAULT NULL,
	`phone`           VARCHAR(20)     DEFAULT NULL,
	`gstin`           VARCHAR(20)     DEFAULT NULL,
	`pan`             VARCHAR(20)     DEFAULT NULL,
	`address_line1`   VARCHAR(255)    DEFAULT NULL,
	`address_line2`   VARCHAR(255)    DEFAULT NULL,
	`city`            VARCHAR(100)    DEFAULT NULL,
	`state`           VARCHAR(100)    DEFAULT NULL,
	`state_code`      VARCHAR(10)     DEFAULT NULL,
	`postal_code`     VARCHAR(20)     DEFAULT NULL,
	`country`         VARCHAR(100)    NOT NULL DEFAULT 'India',
	`payment_terms`   VARCHAR(100)    DEFAULT NULL,
	`opening_balance` DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`notes`           TEXT,
	`status`          VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`      DATETIME        DEFAULT NULL,
	`updated_at`      DATETIME        DEFAULT NULL,
	`deleted_at`      DATETIME        DEFAULT NULL,
	`created_by`      BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`      BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_suppliers_code` (`code`),
	KEY `idx_suppliers_status` (`status`),
	KEY `idx_suppliers_gstin` (`gstin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory` (
	`id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`product_id`        BIGINT UNSIGNED NOT NULL,
	`variant_id`        BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = no variant; see header note 2',
	`warehouse_id`      BIGINT UNSIGNED NOT NULL,
	`quantity`          INT             NOT NULL DEFAULT 0,
	`reserved_quantity` INT             NOT NULL DEFAULT 0 COMMENT 'held by unshipped orders',
	`reorder_level`     INT             NOT NULL DEFAULT 0,
	`shelf_location`    VARCHAR(100)    DEFAULT NULL,
	`status`            VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`        DATETIME        DEFAULT NULL,
	`updated_at`        DATETIME        DEFAULT NULL,
	`deleted_at`        DATETIME        DEFAULT NULL,
	`created_by`        BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`        BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_inventory_stock` (`product_id`, `variant_id`, `warehouse_id`),
	KEY `idx_inventory_warehouse` (`warehouse_id`),
	KEY `idx_inventory_quantity` (`quantity`),
	CONSTRAINT `fk_inv_product`   FOREIGN KEY (`product_id`)   REFERENCES `products` (`id`)   ON DELETE CASCADE,
	CONSTRAINT `fk_inv_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `batches` (
	`id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`product_id`        BIGINT UNSIGNED NOT NULL,
	`variant_id`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
	`warehouse_id`      BIGINT UNSIGNED NOT NULL,
	`batch_number`      VARCHAR(80)     NOT NULL,
	`manufactured_date` DATE            DEFAULT NULL,
	`expiry_date`       DATE            DEFAULT NULL,
	`quantity`          INT             NOT NULL DEFAULT 0,
	`cost_price`        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`status`            VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`        DATETIME        DEFAULT NULL,
	`updated_at`        DATETIME        DEFAULT NULL,
	`deleted_at`        DATETIME        DEFAULT NULL,
	`created_by`        BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`        BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_batches_product` (`product_id`),
	KEY `idx_batches_number` (`batch_number`),
	KEY `idx_batches_expiry` (`expiry_date`),
	CONSTRAINT `fk_batches_product`   FOREIGN KEY (`product_id`)   REFERENCES `products` (`id`)   ON DELETE CASCADE,
	CONSTRAINT `fk_batches_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `purchase_orders` (
	`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`po_number`       VARCHAR(50)     NOT NULL,
	`supplier_id`     BIGINT UNSIGNED NOT NULL,
	`warehouse_id`    BIGINT UNSIGNED NOT NULL,
	`order_date`      DATE            NOT NULL,
	`expected_date`   DATE            DEFAULT NULL,
	`received_date`   DATE            DEFAULT NULL,
	`subtotal`        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`tax_amount`      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`discount_amount` DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`shipping_amount` DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`total_amount`    DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`paid_amount`     DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`payment_status`  ENUM('pending','partial','paid') NOT NULL DEFAULT 'pending',
	`receive_status`  ENUM('pending','partial','received','cancelled') NOT NULL DEFAULT 'pending',
	`notes`           TEXT,
	`status`          VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`      DATETIME        DEFAULT NULL,
	`updated_at`      DATETIME        DEFAULT NULL,
	`deleted_at`      DATETIME        DEFAULT NULL,
	`created_by`      BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`      BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_po_number` (`po_number`),
	KEY `idx_po_supplier` (`supplier_id`),
	KEY `idx_po_warehouse` (`warehouse_id`),
	KEY `idx_po_date` (`order_date`),
	CONSTRAINT `fk_po_supplier`  FOREIGN KEY (`supplier_id`)  REFERENCES `suppliers` (`id`)  ON DELETE RESTRICT,
	CONSTRAINT `fk_po_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `purchase_order_items` (
	`id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`purchase_order_id` BIGINT UNSIGNED NOT NULL,
	`product_id`        BIGINT UNSIGNED NOT NULL,
	`variant_id`        BIGINT UNSIGNED DEFAULT NULL,
	`quantity`          INT             NOT NULL DEFAULT 0,
	`received_quantity` INT             NOT NULL DEFAULT 0,
	`unit_cost`         DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`tax_rate`          DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
	`tax_amount`        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`discount_amount`   DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`total`             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`status`            VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`        DATETIME        DEFAULT NULL,
	`updated_at`        DATETIME        DEFAULT NULL,
	`deleted_at`        DATETIME        DEFAULT NULL,
	`created_by`        BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`        BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_poi_po` (`purchase_order_id`),
	KEY `idx_poi_product` (`product_id`),
	CONSTRAINT `fk_poi_po`      FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_poi_product` FOREIGN KEY (`product_id`)        REFERENCES `products` (`id`)        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_adjustments` (
	`id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`reference_no` VARCHAR(50)     NOT NULL,
	`warehouse_id` BIGINT UNSIGNED NOT NULL,
	`adjust_date`  DATE            NOT NULL,
	`reason`       VARCHAR(150)    NOT NULL,
	`total_items`  INT             NOT NULL DEFAULT 0,
	`notes`        TEXT,
	`status`       VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`   DATETIME        DEFAULT NULL,
	`updated_at`   DATETIME        DEFAULT NULL,
	`deleted_at`   DATETIME        DEFAULT NULL,
	`created_by`   BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`   BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_sa_reference` (`reference_no`),
	KEY `idx_sa_warehouse` (`warehouse_id`),
	CONSTRAINT `fk_sa_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_movements` (
	`id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`product_id`     BIGINT UNSIGNED NOT NULL,
	`variant_id`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
	`warehouse_id`   BIGINT UNSIGNED NOT NULL,
	`batch_id`       BIGINT UNSIGNED DEFAULT NULL,
	`type`           ENUM('purchase','sale','return','adjustment','transfer_in','transfer_out','damage','initial') NOT NULL,
	`quantity`       INT             NOT NULL COMMENT 'signed: positive = in, negative = out',
	`balance_after`  INT             NOT NULL DEFAULT 0,
	`unit_cost`      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`reference_type` VARCHAR(60)     DEFAULT NULL COMMENT 'orders | purchase_orders | stock_adjustments',
	`reference_id`   BIGINT UNSIGNED DEFAULT NULL,
	`notes`          VARCHAR(255)    DEFAULT NULL,
	`status`         VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`     DATETIME        DEFAULT NULL,
	`updated_at`     DATETIME        DEFAULT NULL,
	`deleted_at`     DATETIME        DEFAULT NULL,
	`created_by`     BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`     BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_sm_product` (`product_id`, `variant_id`),
	KEY `idx_sm_warehouse` (`warehouse_id`),
	KEY `idx_sm_reference` (`reference_type`, `reference_id`),
	KEY `idx_sm_created` (`created_at`),
	CONSTRAINT `fk_sm_product`   FOREIGN KEY (`product_id`)   REFERENCES `products` (`id`)   ON DELETE CASCADE,
	CONSTRAINT `fk_sm_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 5. CUSTOMER DATA: ADDRESSES, CART, WISHLIST, WALLET
-- =====================================================================

CREATE TABLE `addresses` (
	`id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id`       BIGINT UNSIGNED NOT NULL,
	`type`          ENUM('shipping','billing','both') NOT NULL DEFAULT 'both',
	`label`         VARCHAR(50)     DEFAULT NULL COMMENT 'Home, Office, ...',
	`first_name`    VARCHAR(100)    NOT NULL,
	`last_name`     VARCHAR(100)    DEFAULT NULL,
	`phone`         VARCHAR(20)     NOT NULL,
	`alt_phone`     VARCHAR(20)     DEFAULT NULL,
	`address_line1` VARCHAR(255)    NOT NULL,
	`address_line2` VARCHAR(255)    DEFAULT NULL,
	`landmark`      VARCHAR(255)    DEFAULT NULL,
	`city`          VARCHAR(100)    NOT NULL,
	`state`         VARCHAR(100)    NOT NULL,
	`state_code`    VARCHAR(10)     DEFAULT NULL,
	`postal_code`   VARCHAR(20)     NOT NULL,
	`country`       VARCHAR(100)    NOT NULL DEFAULT 'India',
	`is_default`    TINYINT(1)      NOT NULL DEFAULT 0,
	`status`        VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`    DATETIME        DEFAULT NULL,
	`updated_at`    DATETIME        DEFAULT NULL,
	`deleted_at`    DATETIME        DEFAULT NULL,
	`created_by`    BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`    BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_addresses_user` (`user_id`),
	KEY `idx_addresses_default` (`user_id`, `is_default`),
	CONSTRAINT `fk_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `carts` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id`    BIGINT UNSIGNED DEFAULT NULL COMMENT 'NULL for guest carts',
	`session_id` VARCHAR(191)    DEFAULT NULL,
	`coupon_id`  BIGINT UNSIGNED DEFAULT NULL,
	`expires_at` DATETIME        DEFAULT NULL,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_carts_user` (`user_id`),
	KEY `idx_carts_session` (`session_id`),
	CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cart_items` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`cart_id`    BIGINT UNSIGNED NOT NULL,
	`product_id` BIGINT UNSIGNED NOT NULL,
	`variant_id` BIGINT UNSIGNED DEFAULT NULL,
	`quantity`   INT             NOT NULL DEFAULT 1,
	`unit_price` DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_ci_cart` (`cart_id`),
	KEY `idx_ci_product` (`product_id`),
	KEY `idx_ci_variant` (`variant_id`),
	CONSTRAINT `fk_ci_cart`    FOREIGN KEY (`cart_id`)    REFERENCES `carts` (`id`)            ON DELETE CASCADE,
	CONSTRAINT `fk_ci_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)         ON DELETE CASCADE,
	CONSTRAINT `fk_ci_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wishlists` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id`    BIGINT UNSIGNED NOT NULL,
	`product_id` BIGINT UNSIGNED NOT NULL,
	`variant_id` BIGINT UNSIGNED DEFAULT NULL,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_wishlist_item` (`user_id`, `product_id`, `variant_id`),
	KEY `idx_wishlists_product` (`product_id`),
	CONSTRAINT `fk_wl_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)    ON DELETE CASCADE,
	CONSTRAINT `fk_wl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wallets` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id`    BIGINT UNSIGNED NOT NULL,
	`balance`    DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_wallets_user` (`user_id`),
	CONSTRAINT `fk_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wallet_transactions` (
	`id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`wallet_id`      BIGINT UNSIGNED NOT NULL,
	`user_id`        BIGINT UNSIGNED NOT NULL,
	`type`           ENUM('credit','debit') NOT NULL,
	`amount`         DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`balance_after`  DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`reference_type` VARCHAR(60)     DEFAULT NULL,
	`reference_id`   BIGINT UNSIGNED DEFAULT NULL,
	`description`    VARCHAR(255)    DEFAULT NULL,
	`status`         VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`     DATETIME        DEFAULT NULL,
	`updated_at`     DATETIME        DEFAULT NULL,
	`deleted_at`     DATETIME        DEFAULT NULL,
	`created_by`     BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`     BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_wt_wallet` (`wallet_id`),
	KEY `idx_wt_user` (`user_id`),
	CONSTRAINT `fk_wt_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_wt_user`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 6. PROMOTIONS
-- =====================================================================

CREATE TABLE `coupons` (
	`id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`code`                 VARCHAR(50)     NOT NULL,
	`name`                 VARCHAR(150)    NOT NULL,
	`description`          VARCHAR(255)    DEFAULT NULL,
	`type`                 ENUM('percentage','fixed','free_shipping') NOT NULL DEFAULT 'percentage',
	`value`                DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`min_order_amount`     DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`max_discount_amount`  DECIMAL(12,2)   DEFAULT NULL COMMENT 'caps percentage coupons',
	`usage_limit`          INT UNSIGNED    DEFAULT NULL COMMENT 'NULL = unlimited',
	`usage_limit_per_user` INT UNSIGNED    DEFAULT NULL,
	`used_count`           INT UNSIGNED    NOT NULL DEFAULT 0,
	`applies_to`           ENUM('all','products','categories') NOT NULL DEFAULT 'all',
	`first_order_only`     TINYINT(1)      NOT NULL DEFAULT 0,
	`starts_at`            DATETIME        DEFAULT NULL,
	`expires_at`           DATETIME        DEFAULT NULL,
	`status`               VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`           DATETIME        DEFAULT NULL,
	`updated_at`           DATETIME        DEFAULT NULL,
	`deleted_at`           DATETIME        DEFAULT NULL,
	`created_by`           BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`           BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_coupons_code` (`code`),
	KEY `idx_coupons_status` (`status`, `deleted_at`),
	KEY `idx_coupons_window` (`starts_at`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `coupon_restrictions` (
	`id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`coupon_id`     BIGINT UNSIGNED NOT NULL,
	`restrict_type` ENUM('product','category','user') NOT NULL,
	`reference_id`  BIGINT UNSIGNED NOT NULL,
	`status`        VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`    DATETIME        DEFAULT NULL,
	`updated_at`    DATETIME        DEFAULT NULL,
	`deleted_at`    DATETIME        DEFAULT NULL,
	`created_by`    BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`    BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_coupon_restriction` (`coupon_id`, `restrict_type`, `reference_id`),
	CONSTRAINT `fk_cr_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `offers` (
	`id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`title`          VARCHAR(191)    NOT NULL,
	`slug`           VARCHAR(191)    NOT NULL,
	`description`    TEXT,
	`banner`         VARCHAR(255)    DEFAULT NULL,
	`discount_type`  ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
	`discount_value` DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`applies_to`     ENUM('all','products','categories','brands') NOT NULL DEFAULT 'all',
	`priority`       INT             NOT NULL DEFAULT 0,
	`starts_at`      DATETIME        DEFAULT NULL,
	`ends_at`        DATETIME        DEFAULT NULL,
	`status`         VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`     DATETIME        DEFAULT NULL,
	`updated_at`     DATETIME        DEFAULT NULL,
	`deleted_at`     DATETIME        DEFAULT NULL,
	`created_by`     BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`     BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_offers_slug` (`slug`),
	KEY `idx_offers_window` (`starts_at`, `ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 7. ORDERS, PAYMENTS, FULFILMENT
-- =====================================================================

CREATE TABLE `orders` (
	`id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`order_number`     VARCHAR(50)     NOT NULL,
	`user_id`          BIGINT UNSIGNED DEFAULT NULL COMMENT 'NULL for guest checkout',
	`customer_name`    VARCHAR(191)    NOT NULL,
	`customer_email`   VARCHAR(191)    NOT NULL,
	`customer_phone`   VARCHAR(20)     NOT NULL,
	`billing_address`  JSON            DEFAULT NULL COMMENT 'snapshot at time of order',
	`shipping_address` JSON            DEFAULT NULL,
	`subtotal`         DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`discount_amount`  DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`coupon_id`        BIGINT UNSIGNED DEFAULT NULL,
	`coupon_code`      VARCHAR(50)     DEFAULT NULL,
	`tax_amount`       DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`cgst_amount`      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`sgst_amount`      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`igst_amount`      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`shipping_amount`  DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`total_amount`     DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`paid_amount`      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`refunded_amount`  DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`currency`         CHAR(3)         NOT NULL DEFAULT 'INR',
	`payment_method`   VARCHAR(40)     NOT NULL DEFAULT 'cod',
	`payment_status`   ENUM('pending','paid','failed','refunded','partially_refunded') NOT NULL DEFAULT 'pending',
	`order_status`     ENUM('pending','confirmed','processing','packed','shipped','out_for_delivery',
	                        'delivered','cancelled','returned','refunded') NOT NULL DEFAULT 'pending',
	`place_of_supply`  VARCHAR(10)     DEFAULT NULL COMMENT 'GST state code',
	`customer_note`    TEXT,
	`admin_note`       TEXT,
	`cancel_reason`    VARCHAR(255)    DEFAULT NULL,
	`source`           VARCHAR(40)     NOT NULL DEFAULT 'web',
	`ip_address`       VARCHAR(45)     DEFAULT NULL,
	`placed_at`        DATETIME        DEFAULT NULL,
	`confirmed_at`     DATETIME        DEFAULT NULL,
	`shipped_at`       DATETIME        DEFAULT NULL,
	`delivered_at`     DATETIME        DEFAULT NULL,
	`cancelled_at`     DATETIME        DEFAULT NULL,
	`status`           VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`       DATETIME        DEFAULT NULL,
	`updated_at`       DATETIME        DEFAULT NULL,
	`deleted_at`       DATETIME        DEFAULT NULL,
	`created_by`       BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`       BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_orders_number` (`order_number`),
	KEY `idx_orders_user` (`user_id`),
	KEY `idx_orders_status` (`order_status`),
	KEY `idx_orders_payment_status` (`payment_status`),
	KEY `idx_orders_placed` (`placed_at`),
	KEY `idx_orders_email` (`customer_email`),
	KEY `idx_orders_phone` (`customer_phone`),
	KEY `idx_orders_coupon` (`coupon_id`),
	CONSTRAINT `fk_orders_user`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`)   ON DELETE SET NULL,
	CONSTRAINT `fk_orders_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
	`id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`order_id`           BIGINT UNSIGNED NOT NULL,
	`product_id`         BIGINT UNSIGNED DEFAULT NULL,
	`variant_id`         BIGINT UNSIGNED DEFAULT NULL,
	`product_name`       VARCHAR(255)    NOT NULL COMMENT 'snapshot',
	`variant_name`       VARCHAR(255)    DEFAULT NULL,
	`sku`                VARCHAR(80)     DEFAULT NULL,
	`image`              VARCHAR(255)    DEFAULT NULL,
	`hsn_code`           VARCHAR(20)     DEFAULT NULL,
	`quantity`           INT             NOT NULL DEFAULT 1,
	`unit_price`         DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`mrp`                DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`discount_amount`    DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`tax_rate`           DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
	`tax_amount`         DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`total`              DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`fulfilled_quantity` INT             NOT NULL DEFAULT 0,
	`returned_quantity`  INT             NOT NULL DEFAULT 0,
	`status`             VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`         DATETIME        DEFAULT NULL,
	`updated_at`         DATETIME        DEFAULT NULL,
	`deleted_at`         DATETIME        DEFAULT NULL,
	`created_by`         BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`         BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_oi_order` (`order_id`),
	KEY `idx_oi_product` (`product_id`),
	KEY `idx_oi_variant` (`variant_id`),
	CONSTRAINT `fk_oi_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders` (`id`)           ON DELETE CASCADE,
	CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)         ON DELETE SET NULL,
	CONSTRAINT `fk_oi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_status_history` (
	`id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`order_id`    BIGINT UNSIGNED NOT NULL,
	`from_status` VARCHAR(40)     DEFAULT NULL,
	`to_status`   VARCHAR(40)     NOT NULL,
	`comment`     VARCHAR(500)    DEFAULT NULL,
	`notified`    TINYINT(1)      NOT NULL DEFAULT 0,
	`status`      VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`  DATETIME        DEFAULT NULL,
	`updated_at`  DATETIME        DEFAULT NULL,
	`deleted_at`  DATETIME        DEFAULT NULL,
	`created_by`  BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`  BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_osh_order` (`order_id`, `created_at`),
	CONSTRAINT `fk_osh_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
	`id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`order_id`           BIGINT UNSIGNED NOT NULL,
	`payment_number`     VARCHAR(50)     NOT NULL,
	`gateway`            VARCHAR(40)     NOT NULL DEFAULT 'razorpay',
	`method`             VARCHAR(40)     DEFAULT NULL COMMENT 'card, upi, netbanking, cod, wallet',
	`amount`             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`currency`           CHAR(3)         NOT NULL DEFAULT 'INR',
	`status`             ENUM('pending','authorized','captured','failed','refunded','partially_refunded')
	                     NOT NULL DEFAULT 'pending' COMMENT 'GATEWAY state, not the MY_Model lifecycle',
	`gateway_order_id`   VARCHAR(100)    DEFAULT NULL COMMENT 'razorpay_order_id',
	`gateway_payment_id` VARCHAR(100)    DEFAULT NULL COMMENT 'razorpay_payment_id',
	`gateway_signature`  VARCHAR(255)    DEFAULT NULL,
	`gateway_response`   JSON            DEFAULT NULL,
	`failure_reason`     VARCHAR(255)    DEFAULT NULL,
	`paid_at`            DATETIME        DEFAULT NULL,
	`status_flag`        VARCHAR(20)     NOT NULL DEFAULT 'active' COMMENT 'MY_Model lifecycle column',
	`created_at`         DATETIME        DEFAULT NULL,
	`updated_at`         DATETIME        DEFAULT NULL,
	`deleted_at`         DATETIME        DEFAULT NULL,
	`created_by`         BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`         BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_payments_number` (`payment_number`),
	KEY `idx_payments_order` (`order_id`),
	KEY `idx_payments_gateway_payment` (`gateway_payment_id`),
	KEY `idx_payments_gateway_order` (`gateway_order_id`),
	KEY `idx_payments_status` (`status`),
	CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Payment_model must set $status_column = ''status_flag''';

CREATE TABLE `payment_logs` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`payment_id` BIGINT UNSIGNED DEFAULT NULL,
	`order_id`   BIGINT UNSIGNED DEFAULT NULL,
	`gateway`    VARCHAR(40)     NOT NULL DEFAULT 'razorpay',
	`event`      VARCHAR(80)     NOT NULL COMMENT 'order.created, payment.captured, webhook.received',
	`request`    JSON            DEFAULT NULL,
	`response`   JSON            DEFAULT NULL,
	`ip_address` VARCHAR(45)     DEFAULT NULL,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_pl_payment` (`payment_id`),
	KEY `idx_pl_order` (`order_id`),
	KEY `idx_pl_event` (`event`, `created_at`),
	CONSTRAINT `fk_pl_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
	CONSTRAINT `fk_pl_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders` (`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `refunds` (
	`id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`order_id`          BIGINT UNSIGNED NOT NULL,
	`payment_id`        BIGINT UNSIGNED DEFAULT NULL,
	`refund_number`     VARCHAR(50)     NOT NULL,
	`amount`            DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`reason`            VARCHAR(255)    DEFAULT NULL,
	`refund_status`     ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
	`gateway_refund_id` VARCHAR(100)    DEFAULT NULL,
	`gateway_response`  JSON            DEFAULT NULL,
	`processed_at`      DATETIME        DEFAULT NULL,
	`processed_by`      BIGINT UNSIGNED DEFAULT NULL,
	`status`            VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`        DATETIME        DEFAULT NULL,
	`updated_at`        DATETIME        DEFAULT NULL,
	`deleted_at`        DATETIME        DEFAULT NULL,
	`created_by`        BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`        BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_refunds_number` (`refund_number`),
	KEY `idx_refunds_order` (`order_id`),
	KEY `idx_refunds_payment` (`payment_id`),
	CONSTRAINT `fk_refunds_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders` (`id`)   ON DELETE CASCADE,
	CONSTRAINT `fk_refunds_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `return_requests` (
	`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`return_number`   VARCHAR(50)     NOT NULL,
	`order_id`        BIGINT UNSIGNED NOT NULL,
	`user_id`         BIGINT UNSIGNED DEFAULT NULL,
	`type`            ENUM('return','exchange') NOT NULL DEFAULT 'return',
	`reason`          VARCHAR(150)    NOT NULL,
	`description`     TEXT,
	`return_status`   ENUM('requested','approved','rejected','picked_up','received','completed','cancelled')
	                  NOT NULL DEFAULT 'requested',
	`pickup_address`  JSON            DEFAULT NULL,
	`refund_amount`   DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`approved_at`     DATETIME        DEFAULT NULL,
	`rejected_reason` VARCHAR(255)    DEFAULT NULL,
	`completed_at`    DATETIME        DEFAULT NULL,
	`status`          VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`      DATETIME        DEFAULT NULL,
	`updated_at`      DATETIME        DEFAULT NULL,
	`deleted_at`      DATETIME        DEFAULT NULL,
	`created_by`      BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`      BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_returns_number` (`return_number`),
	KEY `idx_returns_order` (`order_id`),
	KEY `idx_returns_user` (`user_id`),
	KEY `idx_returns_status` (`return_status`),
	CONSTRAINT `fk_rr_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_rr_user`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `return_items` (
	`id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`return_request_id` BIGINT UNSIGNED NOT NULL,
	`order_item_id`     BIGINT UNSIGNED NOT NULL,
	`quantity`          INT             NOT NULL DEFAULT 1,
	`reason`            VARCHAR(150)    DEFAULT NULL,
	`condition_note`    VARCHAR(255)    DEFAULT NULL,
	`refund_amount`     DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`restocked`         TINYINT(1)      NOT NULL DEFAULT 0,
	`status`            VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`        DATETIME        DEFAULT NULL,
	`updated_at`        DATETIME        DEFAULT NULL,
	`deleted_at`        DATETIME        DEFAULT NULL,
	`created_by`        BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`        BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_rit_request` (`return_request_id`),
	KEY `idx_rit_order_item` (`order_item_id`),
	CONSTRAINT `fk_rit_request`    FOREIGN KEY (`return_request_id`) REFERENCES `return_requests` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_rit_order_item` FOREIGN KEY (`order_item_id`)     REFERENCES `order_items` (`id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shipments` (
	`id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`shipment_number`    VARCHAR(50)     NOT NULL,
	`order_id`           BIGINT UNSIGNED NOT NULL,
	`warehouse_id`       BIGINT UNSIGNED DEFAULT NULL,
	`courier_name`       VARCHAR(100)    DEFAULT NULL,
	`courier_code`       VARCHAR(50)     DEFAULT NULL,
	`tracking_number`    VARCHAR(100)    DEFAULT NULL,
	`tracking_url`       VARCHAR(500)    DEFAULT NULL,
	`shipment_status`    ENUM('pending','packed','picked_up','in_transit','out_for_delivery','delivered','failed','returned')
	                     NOT NULL DEFAULT 'pending',
	`weight`             DECIMAL(10,3)   NOT NULL DEFAULT 0.000,
	`shipping_cost`      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`shipped_at`         DATETIME        DEFAULT NULL,
	`estimated_delivery` DATE            DEFAULT NULL,
	`delivered_at`       DATETIME        DEFAULT NULL,
	`status`             VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`         DATETIME        DEFAULT NULL,
	`updated_at`         DATETIME        DEFAULT NULL,
	`deleted_at`         DATETIME        DEFAULT NULL,
	`created_by`         BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`         BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_shipments_number` (`shipment_number`),
	KEY `idx_shipments_order` (`order_id`),
	KEY `idx_shipments_tracking` (`tracking_number`),
	KEY `idx_shipments_status` (`shipment_status`),
	CONSTRAINT `fk_ship_order`     FOREIGN KEY (`order_id`)     REFERENCES `orders` (`id`)     ON DELETE CASCADE,
	CONSTRAINT `fk_ship_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shipment_tracking` (
	`id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`shipment_id` BIGINT UNSIGNED NOT NULL,
	`status_text` VARCHAR(100)    NOT NULL,
	`location`    VARCHAR(191)    DEFAULT NULL,
	`description` VARCHAR(500)    DEFAULT NULL,
	`occurred_at` DATETIME        NOT NULL,
	`status`      VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`  DATETIME        DEFAULT NULL,
	`updated_at`  DATETIME        DEFAULT NULL,
	`deleted_at`  DATETIME        DEFAULT NULL,
	`created_by`  BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`  BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_st_shipment` (`shipment_id`, `occurred_at`),
	CONSTRAINT `fk_st_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invoices` (
	`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`invoice_number`  VARCHAR(50)     NOT NULL,
	`order_id`        BIGINT UNSIGNED NOT NULL,
	`invoice_date`    DATE            NOT NULL,
	`due_date`        DATE            DEFAULT NULL,
	`place_of_supply` VARCHAR(10)     DEFAULT NULL,
	`subtotal`        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`tax_amount`      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`total_amount`    DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`pdf_path`        VARCHAR(255)    DEFAULT NULL,
	`status`          VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`      DATETIME        DEFAULT NULL,
	`updated_at`      DATETIME        DEFAULT NULL,
	`deleted_at`      DATETIME        DEFAULT NULL,
	`created_by`      BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`      BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_invoices_number` (`invoice_number`),
	KEY `idx_invoices_order` (`order_id`),
	KEY `idx_invoices_date` (`invoice_date`),
	CONSTRAINT `fk_invoices_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `coupon_usages` (
	`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`coupon_id`       BIGINT UNSIGNED NOT NULL,
	`user_id`         BIGINT UNSIGNED DEFAULT NULL,
	`order_id`        BIGINT UNSIGNED DEFAULT NULL,
	`discount_amount` DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
	`status`          VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`      DATETIME        DEFAULT NULL,
	`updated_at`      DATETIME        DEFAULT NULL,
	`deleted_at`      DATETIME        DEFAULT NULL,
	`created_by`      BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`      BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_cu_coupon` (`coupon_id`),
	KEY `idx_cu_user` (`user_id`),
	KEY `idx_cu_order` (`order_id`),
	CONSTRAINT `fk_cu_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_cu_user`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`)   ON DELETE SET NULL,
	CONSTRAINT `fk_cu_order`  FOREIGN KEY (`order_id`)  REFERENCES `orders` (`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 8. CMS & MARKETING CONTENT
-- =====================================================================

CREATE TABLE `banners` (
	`id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`title`        VARCHAR(191)    NOT NULL,
	`subtitle`     VARCHAR(255)    DEFAULT NULL,
	`image`        VARCHAR(255)    NOT NULL,
	`mobile_image` VARCHAR(255)    DEFAULT NULL,
	`link_url`     VARCHAR(500)    DEFAULT NULL,
	`button_text`  VARCHAR(50)     DEFAULT NULL,
	`position`     ENUM('home_slider','home_banner','category','sidebar','popup') NOT NULL DEFAULT 'home_slider',
	`sort_order`   INT             NOT NULL DEFAULT 0,
	`starts_at`    DATETIME        DEFAULT NULL,
	`ends_at`      DATETIME        DEFAULT NULL,
	`status`       VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`   DATETIME        DEFAULT NULL,
	`updated_at`   DATETIME        DEFAULT NULL,
	`deleted_at`   DATETIME        DEFAULT NULL,
	`created_by`   BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`   BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_banners_position` (`position`, `sort_order`),
	KEY `idx_banners_status` (`status`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pages` (
	`id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`title`            VARCHAR(191)    NOT NULL,
	`slug`             VARCHAR(191)    NOT NULL,
	`content`          LONGTEXT,
	`template`         VARCHAR(60)     NOT NULL DEFAULT 'default',
	`is_system`        TINYINT(1)      NOT NULL DEFAULT 0,
	`meta_title`       VARCHAR(191)    DEFAULT NULL,
	`meta_description` VARCHAR(255)    DEFAULT NULL,
	`meta_keywords`    VARCHAR(255)    DEFAULT NULL,
	`status`           VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`       DATETIME        DEFAULT NULL,
	`updated_at`       DATETIME        DEFAULT NULL,
	`deleted_at`       DATETIME        DEFAULT NULL,
	`created_by`       BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`       BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_pages_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_categories` (
	`id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name`        VARCHAR(150)    NOT NULL,
	`slug`        VARCHAR(191)    NOT NULL,
	`description` VARCHAR(500)    DEFAULT NULL,
	`status`      VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`  DATETIME        DEFAULT NULL,
	`updated_at`  DATETIME        DEFAULT NULL,
	`deleted_at`  DATETIME        DEFAULT NULL,
	`created_by`  BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`  BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_blog_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_posts` (
	`id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`blog_category_id` BIGINT UNSIGNED DEFAULT NULL,
	`title`            VARCHAR(191)    NOT NULL,
	`slug`             VARCHAR(191)    NOT NULL,
	`excerpt`          VARCHAR(500)    DEFAULT NULL,
	`content`          LONGTEXT,
	`featured_image`   VARCHAR(255)    DEFAULT NULL,
	`author_id`        BIGINT UNSIGNED DEFAULT NULL,
	`published_at`     DATETIME        DEFAULT NULL,
	`view_count`       INT UNSIGNED    NOT NULL DEFAULT 0,
	`meta_title`       VARCHAR(191)    DEFAULT NULL,
	`meta_description` VARCHAR(255)    DEFAULT NULL,
	`status`           VARCHAR(20)     NOT NULL DEFAULT 'draft',
	`created_at`       DATETIME        DEFAULT NULL,
	`updated_at`       DATETIME        DEFAULT NULL,
	`deleted_at`       DATETIME        DEFAULT NULL,
	`created_by`       BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`       BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_blog_posts_slug` (`slug`),
	KEY `idx_bp_category` (`blog_category_id`),
	KEY `idx_bp_published` (`published_at`),
	KEY `idx_bp_author` (`author_id`),
	CONSTRAINT `fk_bp_category` FOREIGN KEY (`blog_category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL,
	CONSTRAINT `fk_bp_author`   FOREIGN KEY (`author_id`)        REFERENCES `users` (`id`)           ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `testimonials` (
	`id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name`        VARCHAR(150)    NOT NULL,
	`designation` VARCHAR(150)    DEFAULT NULL,
	`company`     VARCHAR(150)    DEFAULT NULL,
	`avatar`      VARCHAR(255)    DEFAULT NULL,
	`rating`      TINYINT UNSIGNED NOT NULL DEFAULT 5,
	`content`     TEXT            NOT NULL,
	`sort_order`  INT             NOT NULL DEFAULT 0,
	`is_featured` TINYINT(1)      NOT NULL DEFAULT 0,
	`status`      VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`  DATETIME        DEFAULT NULL,
	`updated_at`  DATETIME        DEFAULT NULL,
	`deleted_at`  DATETIME        DEFAULT NULL,
	`created_by`  BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`  BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_testimonials_status` (`status`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `faqs` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`category`   VARCHAR(100)    NOT NULL DEFAULT 'General',
	`question`   VARCHAR(500)    NOT NULL,
	`answer`     TEXT            NOT NULL,
	`sort_order` INT             NOT NULL DEFAULT 0,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_faqs_category` (`category`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_messages` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name`       VARCHAR(150)    NOT NULL,
	`email`      VARCHAR(191)    NOT NULL,
	`phone`      VARCHAR(20)     DEFAULT NULL,
	`subject`    VARCHAR(255)    DEFAULT NULL,
	`message`    TEXT            NOT NULL,
	`is_read`    TINYINT(1)      NOT NULL DEFAULT 0,
	`reply`      TEXT,
	`replied_at` DATETIME        DEFAULT NULL,
	`ip_address` VARCHAR(45)     DEFAULT NULL,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_cm_read` (`is_read`, `created_at`),
	KEY `idx_cm_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `newsletter_subscribers` (
	`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`email`           VARCHAR(191)    NOT NULL,
	`name`            VARCHAR(150)    DEFAULT NULL,
	`token`           VARCHAR(100)    DEFAULT NULL COMMENT 'unsubscribe token',
	`subscribed_at`   DATETIME        DEFAULT NULL,
	`unsubscribed_at` DATETIME        DEFAULT NULL,
	`ip_address`      VARCHAR(45)     DEFAULT NULL,
	`status`          VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`      DATETIME        DEFAULT NULL,
	`updated_at`      DATETIME        DEFAULT NULL,
	`deleted_at`      DATETIME        DEFAULT NULL,
	`created_by`      BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`      BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_newsletter_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `seo_meta` (
	`id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`entity_type`      VARCHAR(60)     NOT NULL COMMENT 'product | category | brand | page | blog_post',
	`entity_id`        BIGINT UNSIGNED NOT NULL,
	`meta_title`       VARCHAR(191)    DEFAULT NULL,
	`meta_description` VARCHAR(500)    DEFAULT NULL,
	`meta_keywords`    VARCHAR(255)    DEFAULT NULL,
	`og_title`         VARCHAR(191)    DEFAULT NULL,
	`og_description`   VARCHAR(500)    DEFAULT NULL,
	`og_image`         VARCHAR(255)    DEFAULT NULL,
	`canonical_url`    VARCHAR(500)    DEFAULT NULL,
	`robots`           VARCHAR(50)     NOT NULL DEFAULT 'index,follow',
	`schema_json`      JSON            DEFAULT NULL,
	`status`           VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`       DATETIME        DEFAULT NULL,
	`updated_at`       DATETIME        DEFAULT NULL,
	`deleted_at`       DATETIME        DEFAULT NULL,
	`created_by`       BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`       BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_seo_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 9. SYSTEM
-- =====================================================================

CREATE TABLE `settings` (
	`id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`setting_key`   VARCHAR(120)    NOT NULL,
	`setting_value` TEXT,
	`setting_group` VARCHAR(60)     NOT NULL DEFAULT 'general',
	`setting_type`  VARCHAR(30)     NOT NULL DEFAULT 'text' COMMENT 'text|textarea|number|bool|select|file|json',
	`label`         VARCHAR(150)    DEFAULT NULL,
	`description`   VARCHAR(255)    DEFAULT NULL,
	`is_public`     TINYINT(1)      NOT NULL DEFAULT 0 COMMENT 'safe to expose to the storefront',
	`status`        VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`    DATETIME        DEFAULT NULL,
	`updated_at`    DATETIME        DEFAULT NULL,
	`deleted_at`    DATETIME        DEFAULT NULL,
	`created_by`    BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`    BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_settings_key` (`setting_key`),
	KEY `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `email_templates` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`code`       VARCHAR(80)     NOT NULL COMMENT 'order_placed, otp_login, ...',
	`name`       VARCHAR(150)    NOT NULL,
	`subject`    VARCHAR(255)    NOT NULL,
	`body`       LONGTEXT        NOT NULL,
	`variables`  VARCHAR(500)    DEFAULT NULL COMMENT 'comma separated placeholders',
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_email_templates_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sms_templates` (
	`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`code`            VARCHAR(80)     NOT NULL,
	`name`            VARCHAR(150)    NOT NULL,
	`body`            VARCHAR(1000)   NOT NULL,
	`variables`       VARCHAR(500)    DEFAULT NULL,
	`dlt_template_id` VARCHAR(80)     DEFAULT NULL COMMENT 'Indian DLT registration id',
	`status`          VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`      DATETIME        DEFAULT NULL,
	`updated_at`      DATETIME        DEFAULT NULL,
	`deleted_at`      DATETIME        DEFAULT NULL,
	`created_by`      BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`      BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_sms_templates_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
	`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id`    BIGINT UNSIGNED DEFAULT NULL COMMENT 'NULL = broadcast',
	`audience`   ENUM('user','admin','all') NOT NULL DEFAULT 'user',
	`type`       VARCHAR(60)     NOT NULL DEFAULT 'system',
	`title`      VARCHAR(191)    NOT NULL,
	`message`    VARCHAR(1000)   DEFAULT NULL,
	`icon`       VARCHAR(60)     DEFAULT NULL,
	`link`       VARCHAR(500)    DEFAULT NULL,
	`data`       JSON            DEFAULT NULL,
	`is_read`    TINYINT(1)      NOT NULL DEFAULT 0,
	`read_at`    DATETIME        DEFAULT NULL,
	`status`     VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at` DATETIME        DEFAULT NULL,
	`updated_at` DATETIME        DEFAULT NULL,
	`deleted_at` DATETIME        DEFAULT NULL,
	`created_by` BIGINT UNSIGNED DEFAULT NULL,
	`updated_by` BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_notifications_user` (`user_id`, `is_read`),
	KEY `idx_notifications_audience` (`audience`, `created_at`),
	CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
	`id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id`     BIGINT UNSIGNED DEFAULT NULL,
	`user_name`   VARCHAR(191)    DEFAULT NULL,
	`action`      VARCHAR(60)     NOT NULL COMMENT 'create|update|delete|login|export',
	`entity`      VARCHAR(80)     NOT NULL,
	`entity_id`   BIGINT UNSIGNED DEFAULT NULL,
	`description` VARCHAR(500)    DEFAULT NULL,
	`old_values`  JSON            DEFAULT NULL,
	`new_values`  JSON            DEFAULT NULL,
	`ip_address`  VARCHAR(45)     DEFAULT NULL,
	`user_agent`  VARCHAR(255)    DEFAULT NULL,
	`url`         VARCHAR(255)    DEFAULT NULL,
	`method`      VARCHAR(10)     DEFAULT NULL,
	`status`      VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`  DATETIME        DEFAULT NULL,
	`updated_at`  DATETIME        DEFAULT NULL,
	`deleted_at`  DATETIME        DEFAULT NULL,
	`created_by`  BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`  BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_audit_entity` (`entity`, `entity_id`),
	KEY `idx_audit_user` (`user_id`, `created_at`),
	KEY `idx_audit_action` (`action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `backups` (
	`id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`filename`      VARCHAR(255)    NOT NULL,
	`path`          VARCHAR(500)    NOT NULL,
	`size_bytes`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
	`type`          ENUM('database','files','full') NOT NULL DEFAULT 'database',
	`backup_status` ENUM('running','completed','failed') NOT NULL DEFAULT 'running',
	`error_message` VARCHAR(500)    DEFAULT NULL,
	`started_at`    DATETIME        DEFAULT NULL,
	`completed_at`  DATETIME        DEFAULT NULL,
	`status`        VARCHAR(20)     NOT NULL DEFAULT 'active',
	`created_at`    DATETIME        DEFAULT NULL,
	`updated_at`    DATETIME        DEFAULT NULL,
	`deleted_at`    DATETIME        DEFAULT NULL,
	`created_by`    BIGINT UNSIGNED DEFAULT NULL,
	`updated_by`    BIGINT UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_backups_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- End of schema.
