-- Sample data for Textbook Sale and Rental System
-- Run AFTER textbooks.sql and schema_extensions.sql

USE `textbooks`;

INSERT INTO `users` (`role`, `phone`, `email`, `password`) VALUES
('Seller', '0712345678', 'mark@university.ac.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Buyer', '0723456789', 'jane@university.ac.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO `students` (`user_id`, `course`, `year_of_study`, `first_name`, `last_name`) VALUES
(1, 'Computer Science', 3, 'Mark', 'Muwando'),
(2, 'Information Technology', 2, 'Jane', 'Wanjiku');

INSERT INTO `sellers` (`name`, `phone`, `email`, `sell_price`, `date_added`) VALUES
('Mark Muwando', '0712345678', 'mark@university.ac.ke', 2500.00, CURDATE());

INSERT INTO `books` (`seller_id`, `name`, `author`, `course_code`, `price`, `listing_type`, `rental_price_per_day`, `status`, `book_status`) VALUES
(1, 'Introduction to Algorithms', 'Cormen et al.', 'CS301', 3500.00, 'both', 150.00, 'Active', 'available'),
(1, 'Database System Concepts', 'Silberschatz', 'CS201', 2800.00, 'sale', NULL, 'Active', 'available'),
(1, 'Operating System Concepts', 'Silberschatz', 'CS401', 3200.00, 'rent', 120.00, 'Active', 'available'),
(1, 'Computer Networking', 'Kurose & Ross', 'CS302', 2900.00, 'both', 100.00, 'Active', 'sold');

INSERT INTO `roles` (`role_name`, `password`) VALUES
('Admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Default password for sample accounts: password
