-- Furom KYC System Database Schema
-- Run these queries to implement KYC verification system

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

-- Add KYC status column to users table
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `kyc_status` enum('not_submitted', 'pending', 'verified', 'rejected') DEFAULT 'not_submitted',
ADD COLUMN IF NOT EXISTS `kyc_verified_at` datetime DEFAULT NULL,
ADD INDEX `idx_kyc_status` (`kyc_status`);

-- Insert sample KYC verification levels
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('kyc_enabled', '1'),
('kyc_min_level', '500'), -- Minimum EXP level required for KYC
('kyc_required_for_categories', '1'), -- Whether KYC is required to create categories
('kyc_auto_approval_threshold', '1000'); -- EXP threshold for auto-approval

-- Create KYC categories table (user-created categories)
CREATE TABLE IF NOT EXISTS `user_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `slug` varchar(100) NOT NULL,
    `created_by` int(11) NOT NULL,
    `kyc_verified_only` tinyint(1) DEFAULT 0, -- Only visible to KYC verified users
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

-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `type` enum('kyc_update', 'category_approved', 'category_rejected', 'post_featured', 'mention', 'reply') NOT NULL,
    `title` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `related_id` int(11) DEFAULT NULL, -- ID of related item (post, category, etc.)
    `related_type` varchar(50) DEFAULT NULL, -- Type of related item
    `is_read` tinyint(1) DEFAULT 0,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_is_read` (`is_read`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;