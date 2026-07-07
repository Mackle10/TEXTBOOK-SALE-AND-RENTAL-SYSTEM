-- Feature migration for Textbook Sale and Rental System
-- Run AFTER schema_extensions_v2.sql (and after initial setup)
-- Adds: cover images, email verification, password resets, reviews, messaging

USE `textbooks`;

-- Book cover image
ALTER TABLE `books`
  ADD COLUMN IF NOT EXISTS `cover_image` varchar(255) DEFAULT NULL AFTER `condition_note`;

-- Email verification on users
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `verify_token` varchar(64) DEFAULT NULL;

-- Backfill: existing accounts are considered already verified (new registrations default to 0)
UPDATE `users` SET `email_verified` = 1 WHERE `email_verified` = 0;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pr_email` (`email`),
  KEY `idx_pr_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Book reviews / ratings
CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `idx_review_book_user` (`book_id`,`user_id`),
  KEY `fk_reviews_book` (`book_id`),
  KEY `fk_reviews_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- In-app messaging between buyers and sellers
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `fk_messages_book` (`book_id`),
  KEY `fk_messages_sender` (`sender_id`),
  KEY `fk_messages_receiver` (`receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
