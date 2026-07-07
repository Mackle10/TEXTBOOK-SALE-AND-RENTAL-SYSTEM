-- Additional features: notifications, book descriptions
-- Run AFTER schema_extensions.sql

USE `textbooks`;

CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` varchar(500) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_notifications_user` (`user_id`),
  KEY `idx_notifications_unread` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `books`
  ADD COLUMN IF NOT EXISTS `description` text DEFAULT NULL AFTER `author`,
  ADD COLUMN IF NOT EXISTS `condition_note` varchar(100) DEFAULT 'Good' AFTER `description`;
