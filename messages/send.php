<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$bookId = (int) ($_GET['book_id'] ?? 0);
$book = getBookById($bookId);
$user = currentUser();
$pageTitle = 'Message Seller';
$errors = [];

if (!$book || empty($book['seller_id'])) {
    setFlash('error', 'This listing has no seller to message.');
    redirect('books/view.php?id=' . $bookId);
}

$receiverId = getUserIdBySellerId((int) $book['seller_id']);
if (!$receiverId || $receiverId === (int) $user['user_id']) {
    setFlash('error', 'You cannot message this listing.');
    redirect('books/view.php?id=' . $bookId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $errors[] = 'Invalid or expired form submission. Please try again.';
    }
    $body = trim($_POST['body'] ?? '');
    if ($body === '') {
        $errors[] = 'Message cannot be empty.';
    }
    if (empty($errors)) {
        sendMessage($bookId, (int) $user['user_id'], $receiverId, $body);
        setFlash('success', 'Message sent.');
        redirect('messages/index.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-4">Message Seller about "<?= e($book['name']) ?>"</h1>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card auth-card mx-0" style="max-width: 600px;">
    <div class="card-body p-4">
        <form method="post">
            <?= csrfField() ?>
            <div class="mb-2 small text-muted">
                To: <?= e($book['seller_name'] ?? $book['seller_email'] ?? 'Seller') ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Your message</label>
                <textarea name="body" class="form-control" rows="5" required placeholder="Ask about availability, condition, meetup..."><?= e($_POST['body'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Message</button>
            <a href="books/view.php?id=<?= $bookId ?>" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
