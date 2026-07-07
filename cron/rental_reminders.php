<?php
/**
 * CLI / scheduled job: send rental reminders and flag overdue rentals.
 *
 * Run manually:   php cron/rental_reminders.php
 * Schedule (cron): 0 9 * * * php /path/to/cron/rental_reminders.php
 *
 * In the web app this also runs on page load (dashboard / notifications),
 * but a scheduled job is required for reliable, off-visit reminders.
 */
require_once __DIR__ . '/../includes/functions.php';

processRentalReminders();

echo 'Rental reminders processed at ' . date('Y-m-d H:i:s') . PHP_EOL;
