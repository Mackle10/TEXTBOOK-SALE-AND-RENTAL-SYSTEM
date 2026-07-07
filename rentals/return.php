<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$rentalId = (int) ($_GET['id'] ?? 0);
$user = currentUser();

if (!validateCsrf()) {
    setFlash('error', 'Invalid request.');
    redirect('dashboard.php');
}

if (returnRental($rentalId, (int) $user['user_id'])) {
    setFlash('success', 'Book marked as returned. Waitlisted students have been notified.');
} else {
    setFlash('error', 'Could not process return. Check permissions or rental status.');
}

redirect('dashboard.php');
