<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

header('Content-Type: application/json');
echo json_encode(['count' => getUnreadNotificationCount()]);
