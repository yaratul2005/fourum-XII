-- Furom V4 Database Schema Updates
-- Run these queries to upgrade from V3 to V4

-- Create settings table for configuration storage
CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL UNIQUE,
    `setting_value` text,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extend users table with new profile fields
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `website` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `location` varchar(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `signature` text DEFAULT NULL,
ADD INDEX IF NOT EXISTS `idx_users_username` (`username`),
ADD INDEX IF NOT EXISTS `idx_users_email` (`email`);

-- Create uploads directory structure (needs to be done manually)
-- mkdir -p uploads/avatars
-- chmod 755 uploads
-- chmod 755 uploads/avatars

-- Insert default settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_maintenance', '0'),
('site_maintenance_message', 'We are currently performing maintenance. Please check back soon.'),
('registration_enabled', '1'),
('email_verification_required', '1'),
('max_posts_per_hour', '10'),
('max_comments_per_hour', '30'),
('min_post_length', '10'),
('min_comment_length', '5'),
('default_user_level', 'Newbie'),
('posts_per_page', '20'),
('comments_per_page', '50');

-- Create indexes for better performance
ALTER TABLE `posts` ADD INDEX IF NOT EXISTS `idx_posts_created_at` (`created_at`);
ALTER TABLE `posts` ADD INDEX IF NOT EXISTS `idx_posts_score` (`score`);
ALTER TABLE `comments` ADD INDEX IF NOT EXISTS `idx_comments_created_at` (`created_at`);
ALTER TABLE `comments` ADD INDEX IF NOT EXISTS `idx_comments_score` (`score`);
ALTER TABLE `votes` ADD INDEX IF NOT EXISTS `idx_votes_created_at` (`created_at`);
ALTER TABLE `reports` ADD INDEX IF NOT EXISTS `idx_reports_created_at` (`created_at`);
ALTER TABLE `reports` ADD INDEX IF NOT EXISTS `idx_reports_status` (`status`);

-- Optimize existing tables
OPTIMIZE TABLE users, posts, comments, categories, votes, reports;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS GetUserStats(IN user_id INT)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM posts WHERE user_id = user_id) as post_count,
        (SELECT COUNT(*) FROM comments WHERE user_id = user_id) as comment_count,
        (SELECT COUNT(*) FROM votes WHERE user_id = user_id AND vote_type = 'up') as upvotes_given,
        (SELECT COUNT(*) FROM votes WHERE target_type = 'post' AND target_id IN (SELECT id FROM posts WHERE user_id = user_id)) as post_upvotes_received,
        (SELECT COUNT(*) FROM votes WHERE target_type = 'comment' AND target_id IN (SELECT id FROM comments WHERE user_id = user_id)) as comment_upvotes_received;
END //

CREATE PROCEDURE IF NOT EXISTS GetSiteStats()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM posts) as total_posts,
        (SELECT COUNT(*) FROM comments) as total_comments,
        (SELECT COUNT(*) FROM reports WHERE status = 'pending') as pending_reports,
        (SELECT COUNT(*) FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 1 DAY)) as active_users_today;
END //

DELIMITER ;

-- Add foreign key constraints for data integrity
ALTER TABLE `posts` ADD CONSTRAINT IF NOT EXISTS `fk_posts_category` 
FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL;

ALTER TABLE `comments` ADD CONSTRAINT IF NOT EXISTS `fk_comments_parent` 
FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE;

-- Create audit log table for tracking changes
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `action` varchar(50) NOT NULL,
    `table_name` varchar(50) NOT NULL,
    `record_id` int(11) DEFAULT NULL,
    `old_values` json DEFAULT NULL,
    `new_values` json DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_table` (`table_name`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create cache table for performance optimization
CREATE TABLE IF NOT EXISTS `cache` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `cache_key` varchar(255) NOT NULL UNIQUE,
    `cache_value` longtext NOT NULL,
    `expires_at` datetime NOT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_cache_key` (`cache_key`),
    INDEX `idx_cache_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing (optional)
INSERT IGNORE INTO `categories` (`name`, `description`, `color`, `icon`) VALUES
('Technology', 'Discussions about technology, programming, and innovation', '#3498db', 'laptop'),
('Gaming', 'Video games, gaming culture, and entertainment', '#9b59b6', 'gamepad'),
('Science', 'Scientific discoveries, research, and education', '#2ecc71', 'flask'),
('Arts', 'Creative works, design, and artistic expression', '#e74c3c', 'palette'),
('Lifestyle', 'Daily life, hobbies, and personal interests', '#f39c12', 'heart');

-- Update existing users with default values for new columns
UPDATE `users` SET 
    `website` = COALESCE(`website`, ''),
    `location` = COALESCE(`location`, ''),
    `signature` = COALESCE(`signature`, '')
WHERE `website` IS NULL OR `location` IS NULL OR `signature` IS NULL;

-- Create views for common queries
CREATE OR REPLACE VIEW `user_activity_summary` AS
SELECT 
    u.id as user_id,
    u.username,
    u.exp,
    u.level,
    COUNT(DISTINCT p.id) as posts_count,
    COUNT(DISTINCT c.id) as comments_count,
    COUNT(DISTINCT CASE WHEN v.vote_type = 'up' THEN v.id END) as upvotes_given,
    (SELECT COUNT(*) FROM votes WHERE target_type = 'post' AND target_id IN (SELECT id FROM posts WHERE user_id = u.id)) as posts_upvoted,
    u.last_login,
    u.created_at
FROM users u
LEFT JOIN posts p ON u.id = p.user_id
LEFT JOIN comments c ON u.id = c.user_id
LEFT JOIN votes v ON u.id = v.user_id
GROUP BY u.id;

-- Grant necessary privileges (adjust as needed for your environment)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON furom_db.* TO 'your_db_user'@'localhost';
-- FLUSH PRIVILEGES;

-- V4 Schema Update Complete!
-- Remember to:
-- 1. Backup your database before running these updates
-- 2. Test in a development environment first
-- 3. Monitor performance after deployment
-- 4. Update file permissions for upload directories