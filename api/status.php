<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
if (empty($ids)) {
    echo json_encode(['success' => false, 'books' => []]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = getDB()->prepare("SELECT book_id, book_status, name FROM books WHERE book_id IN ($placeholders)");
$stmt->execute($ids);
$books = $stmt->fetchAll();

echo json_encode(['success' => true, 'books' => $books]);
