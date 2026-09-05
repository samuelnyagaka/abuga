-- ==========================================================
-- Tailor Business Management & Marketing System
-- Complete Database Schema & Seed Data
-- Compatible with MySQL 5.7+ / MySQL 8.0+ / MariaDB (phpMyAdmin)
-- Timezone: Africa/Nairobi (UTC+3)
-- ==========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. USERS & STAFF ACCOUNTS
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  `phone` VARCHAR(20) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. LEADS & MARKETING INQUIRIES
CREATE TABLE IF NOT EXISTS `leads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_type` ENUM('individual', 'institution') NOT NULL DEFAULT 'individual',
  `institution_name` VARCHAR(150) DEFAULT NULL,
  `contact_person` VARCHAR(100) NOT NULL,
  `role_title` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `whatsapp_number` VARCHAR(30) DEFAULT NULL,
  `garment_type` VARCHAR(100) NOT NULL,
  `estimated_quantity` INT NOT NULL DEFAULT 1,
  `budget_range` VARCHAR(50) DEFAULT NULL,
  `target_deadline` DATE DEFAULT NULL,
  `delivery_location` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `sample_file` VARCHAR(255) DEFAULT NULL,
  `source` ENUM('organic_search', 'social_facebook', 'social_instagram', 'social_tiktok', 'whatsapp', 'referral', 'walk_in', 'other') NOT NULL DEFAULT 'organic_search',
  `status` ENUM('new', 'contacted', 'quoted', 'converted', 'lost') NOT NULL DEFAULT 'new',
  `follow_up_date` DATE DEFAULT NULL,
  `assigned_to` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_lead_status` (`status`),
  INDEX `idx_lead_source` (`source`),
  INDEX `idx_follow_up` (`follow_up_date`),
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. LEAD FOLLOW-UP NOTES
CREATE TABLE IF NOT EXISTS `lead_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `lead_id` INT NOT NULL,
  `user_id` INT DEFAULT NULL,
  `note_text` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. CUSTOMERS DIRECTORY
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` ENUM('individual', 'institution') NOT NULL DEFAULT 'individual',
  `name` VARCHAR(100) NOT NULL,
  `institution_name` VARCHAR(150) DEFAULT NULL,
  `contact_person` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `whatsapp_number` VARCHAR(30) DEFAULT NULL,
  `physical_address` TEXT DEFAULT NULL,
  `lead_id` INT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_customer_type` (`type`),
  INDEX `idx_customer_phone` (`phone`),
  INDEX `idx_customer_email` (`email`),
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. MEASUREMENT PROFILES
CREATE TABLE IF NOT EXISTS `measurement_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `profile_name` VARCHAR(100) NOT NULL,
  `gender` ENUM('male', 'female', 'unisex', 'boy', 'girl') NOT NULL DEFAULT 'unisex',
  `unit` ENUM('inches', 'cm') NOT NULL DEFAULT 'inches',
  `neck` DECIMAL(5,2) DEFAULT NULL,
  `chest_bust` DECIMAL(5,2) DEFAULT NULL,
  `waist` DECIMAL(5,2) DEFAULT NULL,
  `hips` DECIMAL(5,2) DEFAULT NULL,
  `shoulder` DECIMAL(5,2) DEFAULT NULL,
  `sleeve_length` DECIMAL(5,2) DEFAULT NULL,
  `shirt_length` DECIMAL(5,2) DEFAULT NULL,
  `trouser_waist` DECIMAL(5,2) DEFAULT NULL,
  `trouser_length` DECIMAL(5,2) DEFAULT NULL,
  `inseam` DECIMAL(5,2) DEFAULT NULL,
  `thigh` DECIMAL(5,2) DEFAULT NULL,
  `custom_specs` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ORDERS & BULK UNIFORM PIPELINE
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(30) NOT NULL UNIQUE,
  `customer_id` INT NOT NULL,
  `lead_id` INT DEFAULT NULL,
  `order_title` VARCHAR(150) NOT NULL,
  `order_type` ENUM('school_uniform', 'corporate_uniform', 'bespoke_suit', 'general_clothing', 'alteration', 'security_workwear') NOT NULL DEFAULT 'school_uniform',
  `status` ENUM('lead', 'quoted', 'confirmed', 'in_production', 'ready_for_pickup', 'completed', 'cancelled') NOT NULL DEFAULT 'quoted',
  `total_quantity` INT NOT NULL DEFAULT 1,
  `delivered_quantity` INT NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `deposit_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid',
  `payment_method` ENUM('mpesa', 'cash', 'bank_transfer', 'cheque') NOT NULL DEFAULT 'mpesa',
  `deadline` DATE DEFAULT NULL,
  `production_start_date` DATE DEFAULT NULL,
  `delivery_date` DATE DEFAULT NULL,
  `delivery_address` TEXT DEFAULT NULL,
  `special_instructions` TEXT DEFAULT NULL,
  `completion_photo` VARCHAR(255) DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_order_status` (`status`),
  INDEX `idx_order_deadline` (`deadline`),
  INDEX `idx_order_payment` (`payment_status`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. ORDER LINE ITEMS & SIZE BREAKDOWN
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `item_title` VARCHAR(150) NOT NULL,
  `size_label` VARCHAR(50) NOT NULL DEFAULT 'Standard',
  `quantity` INT NOT NULL DEFAULT 1,
  `delivered_quantity` INT NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `subtotal_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `fabric_details` VARCHAR(200) DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. PARTIAL DELIVERIES LOG
CREATE TABLE IF NOT EXISTS `order_deliveries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `delivery_date` DATE NOT NULL,
  `quantity_delivered` INT NOT NULL,
  `recipient_name` VARCHAR(100) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. INVOICES & QUOTES
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(30) NOT NULL UNIQUE,
  `order_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `type` ENUM('quote', 'invoice', 'receipt') NOT NULL DEFAULT 'invoice',
  `issue_date` DATE NOT NULL,
  `due_date` DATE DEFAULT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('draft', 'sent', 'paid', 'partial', 'cancelled') NOT NULL DEFAULT 'sent',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. PAYMENTS RECORDING
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `invoice_id` INT DEFAULT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_method` ENUM('mpesa', 'cash', 'bank_transfer', 'cheque') NOT NULL DEFAULT 'mpesa',
  `reference_number` VARCHAR(100) DEFAULT NULL,
  `recorded_by` INT DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. INVENTORY (FABRICS & TRIMS)
CREATE TABLE IF NOT EXISTS `inventory` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sku_code` VARCHAR(50) NOT NULL UNIQUE,
  `item_name` VARCHAR(150) NOT NULL,
  `category` ENUM('fabric', 'lining', 'thread', 'buttons', 'zipper', 'elastic', 'accessory') NOT NULL DEFAULT 'fabric',
  `color_pattern` VARCHAR(100) DEFAULT NULL,
  `quantity_on_hand` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `unit` ENUM('meters', 'rolls', 'pieces', 'gross', 'spools', 'boxes') NOT NULL DEFAULT 'meters',
  `reorder_threshold` DECIMAL(10,2) NOT NULL DEFAULT 10.00,
  `cost_per_unit` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `supplier_name` VARCHAR(150) DEFAULT NULL,
  `supplier_phone` VARCHAR(50) DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. GALLERY & PORTFOLIO
CREATE TABLE IF NOT EXISTS `gallery` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `category` ENUM('school_uniforms', 'corporate_uniforms', 'security_medical', 'suits_blazers', 'dresses_traditional', 'casual_everyday') NOT NULL DEFAULT 'school_uniforms',
  `image_path` VARCHAR(255) NOT NULL,
  `thumbnail_path` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. CLIENT TESTIMONIALS
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `author_name` VARCHAR(100) NOT NULL,
  `organization_title` VARCHAR(150) DEFAULT NULL,
  `quote_text` TEXT NOT NULL,
  `rating` TINYINT NOT NULL DEFAULT 5,
  `avatar_url` VARCHAR(255) DEFAULT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. SEO ARTICLES & BLOG
CREATE TABLE IF NOT EXISTS `articles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL UNIQUE,
  `excerpt` TEXT NOT NULL,
  `body_content` LONGTEXT NOT NULL,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `meta_title` VARCHAR(150) DEFAULT NULL,
  `meta_description` VARCHAR(255) DEFAULT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `author_id` INT DEFAULT NULL,
  `views_count` INT NOT NULL DEFAULT 0,
  `published_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. NEWSLETTER EMAIL CAPTURE
CREATE TABLE IF NOT EXISTS `newsletters` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `source_page` VARCHAR(100) DEFAULT 'homepage',
  `is_synced_brevo` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. SYSTEM SETTINGS
CREATE TABLE IF NOT EXISTS `settings` (
  `key_name` VARCHAR(100) PRIMARY KEY,
  `key_value` TEXT DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- SEED DATA
-- ==========================================================

-- Default Users:
-- Admin: admin / admin123 (Hash generated with password_hash or md5 fallback)
-- Staff: tailor_john / staff123
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `phone`, `is_active`) VALUES
(1, 'admin', 'admin@stitchcraftmasters.co.ke', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', 'Chief Master Tailor', 'admin', '+254712345678', 1),
(2, 'tailor_john', 'john@stitchcraftmasters.co.ke', '$2y$10$iK4g8vR5kXf6Vp3P/1FqXeMv4tXfS8v6q1yHqYcR4jA3k7wVf9L5K', 'John Mwangi (Senior Cutter)', 'staff', '+254722998877', 1)
ON DUPLICATE KEY UPDATE `username`=`username`;

-- System Settings
INSERT INTO `settings` (`key_name`, `key_value`, `description`) VALUES
('business_name', 'StitchCraft & Uniform Masters', 'Company trading name'),
('business_phone', '+254 712 345 678', 'Primary customer care phone number'),
('business_whatsapp', '254712345678', 'WhatsApp international phone number for direct chat'),
('business_email', 'info@stitchcraftmasters.co.ke', 'Public customer inquiries email'),
('business_address', 'Biashara Plaza, 2nd Floor, Suite 14, Moi Avenue, Nairobi, Kenya', 'Workshop & fitting showroom address'),
('business_hours', 'Mon - Sat: 8:00 AM - 6:00 PM | Sun: Closed', 'Operational showroom hours'),
('currency_symbol', 'KSh ', 'Display currency symbol'),
('currency_code', 'KES', 'Display ISO currency code'),
('brevo_api_key', '', 'Brevo v3 HTTPS API Key for transactional emails'),
('brevo_sender_email', 'notifications@stitchcraftmasters.co.ke', 'Verified sender email registered on Brevo'),
('cron_token', 'tailor_cron_secure_token_2026', 'Security token required for external cron pings')
ON DUPLICATE KEY UPDATE `key_name`=`key_name`;

-- Seed Leads
INSERT INTO `leads` (`id`, `customer_type`, `institution_name`, `contact_person`, `role_title`, `email`, `phone`, `whatsapp_number`, `garment_type`, `estimated_quantity`, `budget_range`, `target_deadline`, `delivery_location`, `notes`, `source`, `status`, `follow_up_date`) VALUES
(1, 'institution', 'St. Augustine Academy', 'Mrs. Margaret Ochieng', 'Procurement Head', 'procurement@staugustine.ac.ke', '+254720112233', '254720112233', 'Full School Uniforms (Blazers, Shirts, Pleated Skirts, Trousers)', 350, 'KSh 800,000 - 1,200,000', DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'Westlands, Nairobi', 'Term 1 intake bulk order. Need durable anti-fade poly-viscose blazers with embroidered school crest.', 'organic_search', 'quoted', DATE_ADD(CURDATE(), INTERVAL 2 DAY)),
(2, 'institution', 'Apex Logistics Kenya Ltd', 'David Kiptoo', 'HR & Operations Manager', 'hr@apexlogistics.co.ke', '+254733445566', '254733445566', 'Corporate Staff Uniforms & Reflective Workwear', 120, 'KSh 300,000 - 500,000', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'Embakasi Industrial Area', 'Branded polo shirts, heavy duty cargo trousers, and safety jackets for 60 drivers and warehouse staff.', 'referral', 'contacted', DATE_ADD(CURDATE(), INTERVAL 1 DAY)),
(3, 'individual', NULL, 'Dr. Evans Mutua', NULL, 'evans.mutua@knh.or.ke', '+254711889900', '254711889900', 'Three-Piece Bespoke Italian Wool Wedding Suit', 1, 'KSh 35,000 - 45,000', DATE_ADD(CURDATE(), INTERVAL 18 DAY), 'Kilimani, Nairobi', 'Groom wedding suit. Midnight navy with satin lapels and personalized embroidered initials on inside pocket.', 'social_instagram', 'new', CURDATE())
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Lead Notes
INSERT INTO `lead_notes` (`lead_id`, `user_id`, `note_text`) VALUES
(1, 1, 'Sent official quotation via Brevo with fabric swatch catalogue attached. Scheduled fitting sample delivery for next Tuesday.'),
(2, 2, 'Spoke with David on WhatsApp. Sent fabric sample photos for heavy drill cargo trousers. Awaiting final size list from logistics depot.');

-- Seed Customers
INSERT INTO `customers` (`id`, `type`, `name`, `institution_name`, `contact_person`, `email`, `password_hash`, `phone`, `whatsapp_number`, `physical_address`, `lead_id`, `notes`, `is_active`) VALUES
(1, 'institution', 'St. Augustine Academy', 'St. Augustine Academy', 'Mrs. Margaret Ochieng', 'procurement@staugustine.ac.ke', '$2y$10$wE99Y5Kk5i9iIqIuY/qG..t2sVf6a.UjYgK9uX6dM5oK6pL2e.Q8y', '+254720112233', '254720112233', 'School Campus, Ring Road, Westlands, Nairobi', 1, 'Key institutional account. Preferred delivery schedule before opening day.', 1),
(2, 'individual', 'Dr. Evans Mutua', NULL, 'Dr. Evans Mutua', 'evans.mutua@knh.or.ke', '$2y$10$wE99Y5Kk5i9iIqIuY/qG..t2sVf6a.UjYgK9uX6dM5oK6pL2e.Q8y', '+254711889900', '254711889900', 'Apartment 4B, Lenana Road, Kilimani, Nairobi', 3, 'VIP Bespoke customer. Requires two fitting sessions before final press.', 1)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Measurement Profiles
INSERT INTO `measurement_profiles` (`id`, `customer_id`, `profile_name`, `gender`, `unit`, `neck`, `chest_bust`, `waist`, `hips`, `shoulder`, `sleeve_length`, `shirt_length`, `trouser_waist`, `trouser_length`, `inseam`, `thigh`, `custom_specs`, `notes`) VALUES
(1, 1, 'Junior Secondary Boys Standard Uniform', 'boy', 'inches', 14.50, 34.00, 28.00, 32.00, 15.50, 22.00, 26.00, 28.00, 36.00, 27.00, 19.00, 'Elasticated waist expansion on trousers, reinforced knee patches', 'Standard pattern for Grade 7 & 8 boys'),
(2, 2, 'Executive Three-Piece Suit Fitted', 'male', 'inches', 16.50, 41.50, 35.00, 42.00, 18.50, 25.50, 30.50, 35.00, 41.00, 32.00, 23.50, 'Peak satin lapel 3.5 inches, double vent back, tapered trouser leg with 1.5 inch cuff', 'Customer prefers snug fit around chest and shoulders')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Orders
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `lead_id`, `order_title`, `order_type`, `status`, `total_quantity`, `delivered_quantity`, `total_amount`, `deposit_amount`, `paid_amount`, `payment_status`, `payment_method`, `deadline`, `production_start_date`, `delivery_date`, `special_instructions`, `created_by`) VALUES
(1, 'ORD-2026-1001', 1, 1, 'St. Augustine Term 1 Uniforms (350 Sets)', 'school_uniform', 'in_production', 350, 120, 980000.00, 500000.00, 500000.00, 'partial', 'bank_transfer', DATE_ADD(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), NULL, 'Gold crest embroidery on left chest pocket. Deliveries split into 2 batches.', 1),
(2, 'ORD-2026-1002', 2, 3, 'Bespoke Midnight Navy 3-Piece Italian Wool Suit', 'bespoke_suit', 'confirmed', 1, 0, 38500.00, 20000.00, 20000.00, 'partial', 'mpesa', DATE_ADD(CURDATE(), INTERVAL 10 DAY), CURDATE(), NULL, 'Wedding date is in 3 weeks. First basted fitting scheduled for Saturday.', 1)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Order Items
INSERT INTO `order_items` (`order_id`, `item_title`, `size_label`, `quantity`, `delivered_quantity`, `unit_price`, `subtotal_price`, `fabric_details`) VALUES
(1, 'Embroidered Navy Blazer (Senior School)', 'Size 34', 80, 50, 3200.00, 256000.00, 'High-grade anti-pill navy poly-viscose'),
(1, 'Embroidered Navy Blazer (Senior School)', 'Size 36', 100, 70, 3200.00, 320000.00, 'High-grade anti-pill navy poly-viscose'),
(1, 'White Oxford Long-Sleeve Shirt (Logo on pocket)', 'Size 14.5', 90, 0, 1200.00, 108000.00, '60/40 Cotton-Poly breathable blend'),
(1, 'Charcoal Grey Pleated Skirt', 'Size 28-30', 80, 0, 1800.00, 144000.00, 'Durable wool-blend twill'),
(2, 'Custom 3-Piece Bespoke Italian Wool Suit (Jacket, Vest, Trousers)', 'Tailored Fit', 1, 0, 38500.00, 38500.00, 'Super 130s Italian Worsted Wool with Bemberg Cupro lining');

-- Seed Partial Deliveries Log
INSERT INTO `order_deliveries` (`order_id`, `delivery_date`, `quantity_delivered`, `recipient_name`, `notes`, `created_by`) VALUES
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 120, 'Mr. Bernard Kamau (School Storekeeper)', 'First batch of 120 blazers delivered and signed off. Remainder in progress.', 1);

-- Seed Invoices
INSERT INTO `invoices` (`id`, `invoice_number`, `order_id`, `customer_id`, `type`, `issue_date`, `due_date`, `subtotal`, `tax_amount`, `discount_amount`, `total_amount`, `status`, `notes`) VALUES
(1, 'INV-2026-1001', 1, 1, 'invoice', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 828000.00, 152000.00, 0.00, 980000.00, 'partial', '50% deposit received. Remaining balance payable upon 100% uniform delivery.'),
(2, 'INV-2026-1002', 2, 2, 'invoice', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 38500.00, 0.00, 0.00, 38500.00, 'partial', 'KSh 20,000 M-Pesa deposit received (Ref: QJK8991LA). Balance due at final fitting.')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Payments
INSERT INTO `payments` (`order_id`, `invoice_id`, `amount`, `payment_date`, `payment_method`, `reference_number`, `recorded_by`, `notes`) VALUES
(1, 1, 500000.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'bank_transfer', 'EFT-STANBIC-98214', 1, 'Initial 50% mobilization deposit from school board account.'),
(2, 2, 20000.00, CURDATE(), 'mpesa', 'QJK8991LA', 1, 'Commitment deposit paid via M-Pesa Paybill.');

-- Seed Inventory (Fabrics & Trims)
INSERT INTO `inventory` (`id`, `sku_code`, `item_name`, `category`, `color_pattern`, `quantity_on_hand`, `unit`, `reorder_threshold`, `cost_per_unit`, `supplier_name`, `supplier_phone`) VALUES
(1, 'FAB-NVY-01', 'Navy Blue Poly-Viscose Uniform Twill', 'fabric', 'Navy Blue Solid', 450.00, 'meters', 100.00, 480.00, 'Rivatex East Africa', '+254700112233'),
(2, 'FAB-WHT-02', 'White Oxford Uniform Cotton Blend', 'fabric', 'Crisp White', 320.00, 'meters', 80.00, 350.00, 'Thika Cloth Mills', '+254711223344'),
(3, 'FAB-GRY-03', 'Charcoal Grey Wool-Blend Skirt/Trouser Fabric', 'fabric', 'Charcoal Grey Twill', 18.00, 'meters', 50.00, 620.00, 'Apex Textile Importers', '+254722334455'),
(4, 'FAB-WOL-04', 'Italian Worsted Wool Super 130s', 'fabric', 'Midnight Navy Chalkstripe', 45.00, 'meters', 15.00, 3200.00, 'Milan Fabrics Direct', '+254733445566'),
(5, 'TRM-BTN-01', 'Brass Crest Blazer Buttons (Large 20mm)', 'buttons', 'Embossed Antique Gold', 600.00, 'pieces', 200.00, 15.00, 'Nairobi Haberdashery Hub', '+254744556677'),
(6, 'TRM-ZIP-01', 'YKK Brass Concealed Trouser Zippers (7 inch)', 'zipper', 'Navy & Charcoal', 8.00, 'pieces', 50.00, 45.00, 'YKK Fasteners Kenya', '+254755667788')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Testimonials
INSERT INTO `testimonials` (`id`, `author_name`, `organization_title`, `quote_text`, `rating`, `is_approved`, `sort_order`) VALUES
(1, 'Sister Rose Wanjiru', 'Principal, St. Jude Girls High School', 'StitchCraft delivered over 600 sets of school uniforms 5 days ahead of opening day. The fabric quality has endured two full terms of rigorous washing without fading.', 5, 1, 1),
(2, 'Eng. Patrick Mwangi', 'Operations Director, KenPower Logistics', 'Our drivers and field staff look sharp, professional, and consistent across all branches in Kenya. Their size breakdown and replacement process is completely frictionless.', 5, 1, 2),
(3, 'Brian & Cynthia Kariuki', 'Newlyweds', 'My wedding suit was tailored to perfection down to the quarter-inch. Everyone at the reception commented on the fit and satin lapel finish. Highly recommended!', 5, 1, 3)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Gallery
INSERT INTO `gallery` (`id`, `title`, `category`, `image_path`, `description`, `is_featured`, `sort_order`) VALUES
(1, 'Premium School Blazers with Embroidered Crest', 'school_uniforms', 'assets/images/gallery/school_blazers.svg', 'Tailored school blazers made with reinforced poly-viscose and high-definition metallic thread crest embroidery.', 1, 1),
(2, 'Corporate Executive Office Attire', 'corporate_uniforms', 'assets/images/gallery/corporate_suits.svg', 'Modern slim-fit blazers and coordinated corporate attire for banking and corporate front offices.', 1, 2),
(3, 'Three-Piece Bespoke Italian Wool Suit', 'suits_blazers', 'assets/images/gallery/bespoke_suit.svg', 'Handcrafted bespoke wedding suit with peak satin lapels and full canvas interior construction.', 1, 3),
(4, 'Heavy-Duty Security & Guard Uniforms', 'security_medical', 'assets/images/gallery/security_uniforms.svg', 'Reinforced ripstop security guard uniforms with shoulder epaulettes and utility cargo pockets.', 1, 4),
(5, 'Contemporary African Elegance & Evening Wear', 'dresses_traditional', 'assets/images/gallery/african_wear.svg', 'Bespoke modern formal wear fusing traditional African print fabrics with modern tailored silhouettes.', 1, 5)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed SEO Articles
INSERT INTO `articles` (`id`, `title`, `slug`, `excerpt`, `body_content`, `meta_title`, `meta_description`, `is_published`, `author_id`, `views_count`) VALUES
(1, 'How to Procure High-Durability School Uniforms in Bulk in Kenya', 'how-to-procure-school-uniforms-bulk-kenya', 'A comprehensive procurement guide for school principals, bursars, and boards on selecting non-fading fabrics, managing student size variations, and preventing opening-day delays.', '<p>Procuring school uniforms for hundreds or thousands of students requires precision, dependable fabric sourcing, and clear timeline management. In this guide, we break down the critical factors every school administration must evaluate before issuing a uniform tender.</p><h3>1. Fabric Composition: Why Pure Cotton Fails in School Environments</h3><p>While 100% cotton is breathable, it wrinkles easily and degrades rapidly under weekly commercial washing. For school blazers and trousers, a <strong>65/35 Poly-Viscose blend</strong> or a <strong>high-count twill</strong> provides the ideal balance between breathability, color retention, and tear resistance.</p><h3>2. Standardized Sizing with Growth Allowances</h3><p>Children grow rapidly throughout the school year. Professional school uniform tailors incorporate hidden expansion allowances, such as elasticized inner waistbands and generous hem inlays that parents can let down as students grow.</p><h3>3. Reliable Production Timelines</h3><p>Avoid last-minute rushes in January or September by placing bulk orders at least 6 to 8 weeks before the new term commences. This provides ample time for sample approval, embroidery digitization, and staggered size distribution.</p>', 'School Uniform Procurement Guide Kenya | Bulk Tailoring', 'Expert guide for Kenyan school administrations on bulk uniform procurement, durable fabrics, and avoiding term opening delays.', 1, 1, 142),
(2, 'The Ultimate Custom Suit Fitting Guide: Measurements That Matter', 'custom-suit-fitting-guide-nairobi', 'From shoulder pitch to trouser break, learn how master tailors take precision measurements for bespoke suits that turn heads in the boardroom or at your wedding.', '<p>A bespoke suit is an investment in personal branding and confidence. Unlike off-the-rack suits that compromise on armhole depth or waist taper, a handcrafted suit is molded to your unique anatomical posture.</p><h3>1. The Shoulder Line: The Foundation of Every Jacket</h3><p>A master cutter will always inspect the shoulder slope first. If the shoulder pad overhangs your natural bone line, the entire garment will collapse into wrinkles. The jacket shoulder must sit flush with zero puckering.</p><h3>2. The Jacket Sleeve and Shirt Cuff Harmony</h3><p>Your suit jacket sleeve should end precisely at the hinge of your wrist, revealing exactly <strong>one-quarter to one-half inch</strong> of crisp shirt cuff. This subtle detail defines seasoned elegance.</p><h3>3. Trouser Break: Full, Half, or No Break?</h3><p>In modern menswear, the <strong>half break</strong> or <strong>slight break</strong> is the gold standard—resting gently on the top of your dress shoe without pooling into folds around your ankles.</p>', 'Custom Suit Fitting Guide Nairobi | Bespoke Tailoring', 'Learn how precision bespoke measurements create the perfect custom suit in Nairobi. Shoulder pitch, sleeve lengths, and trouser break explained.', 1, 1, 98)
ON DUPLICATE KEY UPDATE `id`=`id`;

SET FOREIGN_KEY_CHECKS = 1;
