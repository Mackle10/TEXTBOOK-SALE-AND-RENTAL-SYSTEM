<?php
/**
 * Textbook Sale and Rental System — configuration
 */

define('APP_NAME', 'Textbook Sale and Rental System');
define('APP_URL', 'http://localhost/Academics%20and%20textbooks');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'textbooks');
define('DB_USER', 'root');
define('DB_PASS', '');

// Legacy email domain constant. Registration now accepts any valid email address.
define('UNIVERSITY_EMAIL_DOMAIN', 'students.mak.ac.ug');

define('RENTAL_REMINDER_DAYS', 3);

date_default_timezone_set('Africa/Nairobi');

session_start();
