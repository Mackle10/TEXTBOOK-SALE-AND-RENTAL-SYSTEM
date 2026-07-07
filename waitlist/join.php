<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$bookId = (int) ($_GET['book_id'] ?? 0);
$book = getBookById($bookId);
$user = currentUser();

if (!validateCsrf()) {
    setFlash('error', 'Invalid request.');
    redirect('books/view.php?id=' . $bookId);
}

if (!$book) {
    setFlash('error', 'Book not found.');
    redirect('books/index.php');
}

if ($book['book_status'] === 'available') {
    setFlash('error', 'This book is already available — no need to join the waitlist.');
    redirect('books/view.php?id=' . $bookId);
}

$db = getDB();
try {
    $stmt = $db->prepare('INSERT INTO waitlist (book_id, user_id) VALUES (?, ?)');
    $stmt->execute([$bookId, $user['user_id']]);
    setFlash('success', 'You have joined the waitlist. We will notify you when this book becomes available.');
} catch (PDOException $e) {
    setFlash('success', 'You are already on the waitlist for this book.');
}

redirect('books/view.php?id=' . $bookId);
