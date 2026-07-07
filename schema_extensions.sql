-- Extensions for Textbook Sale and Rental System
-- Run AFTER importing textbooks.sql

USE `textbooks`;

-- Allow multiple books per course code; link books to sellers
ALTER TABLE `books`
  DROP INDEX `idx_course_code`,
  ADD COLUMN `seller_id` int(11) DEFAULT NULL AFTER `book_id`,
  ADD COLUMN `listing_type` enum('sale','rent','both') NOT NULL DEFAULT 'sale' AFTER `price`,
  ADD COLUMN `rental_price_per_day` decimal(10,2) DEFAULT NULL AFTER `listing_type`,
  ADD COLUMN `book_status` enum('available','reserved','sold','rented') NOT NULL DEFAULT 'available' AFTER `status`,
  ADD KEY `idx_course_code` (`course_code`),
  ADD KEY `fk_books_seller` (`seller_id`);

-- Link students to user accounts
ALTER TABLE `students`
  ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `student_id`,
  ADD UNIQUE KEY `idx_students_user` (`user_id`);

-- Waitlist for out-of-stock books
CREATE TABLE IF NOT EXISTS `waitlist` (
  `waitlist_id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`waitlist_id`),
  UNIQUE KEY `idx_waitlist_book_user` (`book_id`,`user_id`),
  KEY `fk_waitlist_book` (`book_id`),
  KEY `fk_waitlist_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Rental tracking with return dates and reminders
CREATE TABLE IF NOT EXISTS `rentals` (
  `rental_id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `borrower_user_id` int(11) NOT NULL,
  `rental_days` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `return_date` date NOT NULL,
  `status` enum('active','returned','overdue') NOT NULL DEFAULT 'active',
  `reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`rental_id`),
  KEY `fk_rentals_book` (`book_id`),
  KEY `fk_rentals_sale` (`sale_id`),
  KEY `fk_rentals_borrower` (`borrower_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- QR verification tokens for transactions
CREATE TABLE IF NOT EXISTS `qr_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `idx_qr_token` (`token`),
  KEY `fk_qr_sale` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
