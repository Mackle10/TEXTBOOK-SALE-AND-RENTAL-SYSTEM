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

// Restrict registration to university email domain (change to your institution)
define('UNIVERSITY_EMAIL_DOMAIN', 'university.ac.ke');

define('RENTAL_REMINDER_DAYS', 3);

date_default_timezone_set('Africa/Nairobi');

session_start();
