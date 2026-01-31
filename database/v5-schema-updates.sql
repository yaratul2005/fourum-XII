-- Furom Version 5.0 Database Schema Updates
-- Run these queries to implement all V5 features

-- ======================================================
-- KYC SYSTEM TABLES
-- ======================================================

-- Create KYC documents table
CREATE TABLE IF NOT EXISTS `kyc_documents` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `document_type` enum('passport', 'driver_license', 'national_id', 'other') NOT NULL,
    `document_number` varchar(100) NOT NULL,
    `first_name` varchar(100) NOT NULL,
    `last_name` varchar(100) NOT NULL,
    `date_of_birth` date NOT NULL,
    `issue_date` date DEFAULT NULL,
    `expiry_date` date DEFAULT NULL,
    `front_image` varchar(255) NOT NULL,
    `back_image` varchar(255) DEFAULT NULL,
    `selfie_image` varchar(255) DEFAULT NULL,
    `status` enum('pending', 'approved', 'rejected', 'review_required') DEFAULT 'pending',
    `admin_notes` text DEFAULT NULL,
    `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` datetime DEFAULT NULL,
    `reviewed_by` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_document` (`user_id`, `document_type`, `document_number`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_submitted_at` (`submitted_at`),
    CONSTRAINT `fk_kyc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kyc_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create KYC verification logs table
CREATE TABLE IF NOT EXISTS `kyc_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `kyc_id` int(11) NOT NULL,
    `action` enum('submitted', 'approved', 'rejected', 'updated', 'review_started') NOT NULL,
    `performed_by` int(11) DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kyc_id` (`kyc_id`),
    KEY `idx_action` (`action`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_kyc_logs_kyc` FOREIGN KEY (`kyc_id`) REFERENCES `kyc_documents` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kyc_logs_performer` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- NOTIFICATION SYSTEM TABLES
-- ======================================================

-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `type` enum('kyc_update', 'category_approved', 'category_rejected', 'post_featured', 'mention', 'reply', 'general') NOT NULL,
    `title` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `related_id` int(11) DEFAULT NULL,
    `related_type` varchar(50) DEFAULT NULL,
    `is_read` tinyint(1) DEFAULT 0,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_is_read` (`is_read`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- USER CATEGORIES TABLE
-- ======================================================

-- Create user categories table
CREATE TABLE IF NOT EXISTS `user_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `slug` varchar(100) NOT NULL,
    `created_by` int(11) NOT NULL,
    `kyc_verified_only` tinyint(1) DEFAULT 0,
    `status` enum('active', 'pending', 'rejected') DEFAULT 'pending',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `approved_at` datetime DEFAULT NULL,
    `approved_by` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_category_slug` (`slug`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_category_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_category_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- USERS TABLE MODIFICATIONS
-- ======================================================

-- Add KYC status columns to users table
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `kyc_status` enum('not_submitted', 'pending', 'verified', 'rejected') DEFAULT 'not_submitted',
ADD COLUMN IF NOT EXISTS `kyc_verified_at` datetime DEFAULT NULL,
ADD INDEX `idx_kyc_status` (`kyc_status`);

-- ======================================================
-- SETTINGS TABLE UPDATES
-- ======================================================

-- Insert KYC configuration settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('kyc_enabled', '1', 'Enable KYC verification system'),
('kyc_min_level', '500', 'Minimum EXP required to submit KYC'),
('kyc_required_for_categories', '1', 'Require KYC verification to create categories'),
('kyc_auto_approval_threshold', '1000', 'EXP threshold for automatic category approval'),
('notifications_enabled', '1', 'Enable notification system'),
('guest_post_redirect', 'register', 'Where guests should be redirected when trying to post');

-- ======================================================
-- POSTS TABLE MODIFICATIONS
-- ======================================================

-- Add category relationship to posts table
ALTER TABLE `posts` 
ADD COLUMN IF NOT EXISTS `category_id` int(11) DEFAULT NULL,
ADD INDEX `idx_category_id` (`category_id`),
ADD CONSTRAINT `fk_post_category` FOREIGN KEY (`category_id`) REFERENCES `user_categories` (`id`) ON DELETE SET NULL;

-- ======================================================
-- CREATE UPLOAD DIRECTORIES (Manual Step)
-- ======================================================
/*
Run these commands on your server to create upload directories:

mkdir -p uploads/kyc
mkdir -p uploads/categories
chmod 755 uploads
chmod 755 uploads/kyc
chmod 755 uploads/categories

These directories are needed for:
- KYC document uploads
- Category images (future feature)
*/

-- ======================================================
-- INDEX OPTIMIZATIONS
-- ======================================================

-- Add additional indexes for better performance
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_username_exp` (`username`, `exp`);
ALTER TABLE `posts` ADD INDEX IF NOT EXISTS `idx_created_at_status` (`created_at`, `status`);
ALTER TABLE `comments` ADD INDEX IF NOT EXISTS `idx_post_id_created_at` (`post_id`, `created_at`);

-- ======================================================
-- CLEANUP QUERIES (Optional)
-- ======================================================

-- Remove any duplicate or obsolete settings
DELETE s1 FROM `settings` s1
INNER JOIN `settings` s2 
WHERE s1.setting_key = s2.setting_key AND s1.id > s2.id;

-- Clean up orphaned records (optional - backup first!)
-- DELETE FROM votes WHERE user_id NOT IN (SELECT id FROM users);
-- DELETE FROM comments WHERE user_id NOT IN (SELECT id FROM users);
-- DELETE FROM posts WHERE user_id NOT IN (SELECT id FROM users);

-- ======================================================
-- VERIFICATION QUERIES
-- ======================================================

-- Verify all tables were created successfully
SELECT 
    TABLE_NAME as 'Table',
    TABLE_ROWS as 'Rows',
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('kyc_documents', 'kyc_logs', 'notifications', 'user_categories')
ORDER BY TABLE_NAME;

-- Check constraint relationships
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND REFERENCED_TABLE_NAME IS NOT NULL
AND TABLE_NAME IN ('kyc_documents', 'kyc_logs', 'notifications', 'user_categories')
ORDER BY TABLE_NAME;

-- ======================================================
-- SAMPLE DATA (Optional for testing)
-- ======================================================

-- Insert sample admin user if not exists
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `exp`, `role`, `created_at`) 
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 999999, 'admin', NOW());

-- Insert sample categories
INSERT IGNORE INTO `user_categories` (`name`, `description`, `slug`, `created_by`, `status`, `approved_at`, `approved_by`) 
VALUES 
('Technology', 'Discuss the latest in tech news and innovations', 'technology', 1, 'active', NOW(), 1),
('Gaming', 'Video games, gaming culture, and entertainment', 'gaming', 1, 'active', NOW(), 1),
('Science', 'Scientific discoveries and research discussions', 'science', 1, 'active', NOW(), 1);

-- V5 Schema Updates - Enhanced Admin Controls
-- Run this after the initial v5-schema-updates.sql

-- Add header/footer customization fields to settings table
ALTER TABLE settings ADD COLUMN IF NOT EXISTS header_title VARCHAR(100) DEFAULT 'FUROM';
ALTER TABLE settings ADD COLUMN IF NOT EXISTS header_subtitle VARCHAR(200) DEFAULT 'Futuristic Community Platform';
ALTER TABLE settings ADD COLUMN IF NOT EXISTS footer_text TEXT DEFAULT '© 2024 Furom. All rights reserved.';
ALTER TABLE settings ADD COLUMN IF NOT EXISTS show_header_logo BOOLEAN DEFAULT TRUE;
ALTER TABLE settings ADD COLUMN IF NOT EXISTS header_custom_css TEXT DEFAULT '';
ALTER TABLE settings ADD COLUMN IF NOT EXISTS footer_custom_html TEXT DEFAULT '';

-- Insert default values if they don't exist
INSERT IGNORE INTO settings (setting_key, setting_value, setting_type) VALUES 
('header_title', 'FUROM', 'string'),
('header_subtitle', 'Futuristic Community Platform', 'string'),
('footer_text', '© 2024 Furom. All rights reserved.', 'text'),
('show_header_logo', '1', 'boolean'),
('header_custom_css', '', 'text'),
('footer_custom_html', '', 'text');

-- Create site_appearance table for advanced customization
CREATE TABLE IF NOT EXISTS site_appearance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('color', 'text', 'boolean', 'html') DEFAULT 'text',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default appearance settings
INSERT IGNORE INTO site_appearance (setting_name, setting_value, setting_type, description) VALUES
('primary_color', '#00f5ff', 'color', 'Primary brand color'),
('secondary_color', '#ff00ff', 'color', 'Secondary accent color'),
('background_color', '#0a0a1a', 'color', 'Main background color'),
('text_color', '#ffffff', 'color', 'Primary text color'),
('enable_particles', '1', 'boolean', 'Enable particle background effect'),
('custom_header_html', '', 'html', 'Custom HTML for header'),
('custom_footer_html', '', 'html', 'Custom HTML for footer');

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_settings_key ON settings(setting_key);
CREATE INDEX IF NOT EXISTS idx_appearance_name ON site_appearance(setting_name);

COMMIT;