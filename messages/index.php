<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();
$pageTitle = 'Messages';

// Mark received messages as read on open
$db->prepare('UPDATE messages SET is_read = 1 WHERE receiver_id = ?')->execute([$user['user_id']]);

$stmt = $db->prepare(
    "SELECT m.*, b.name AS book_name, u.email AS other_email,
            CONCAT(s.first_name, ' ', s.last_name) AS other_name
     FROM messages m
     JOIN books b ON b.book_id = m.book_id
     JOIN users u ON u.user_id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
     LEFT JOIN students s ON s.user_id = u.user_id
     WHERE m.sender_id = ? OR m.receiver_id = ?
     ORDER BY m.created_at DESC"
);
$stmt->execute([$user['user_id'], $user['user_id'], $user['user_id']]);
$messages = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-4"><i class="bi bi-chat-dots"></i> Messages</h1>

<?php if (empty($messages)): ?>
    <div class="alert alert-info">No messages yet. Open a book listing and use "Message Seller" to start a conversation.</div>
<?php else: ?>
    <div class="list-group mb-4">
        <?php foreach ($messages as $m): ?>
            <div class="list-group-item">
                <div class="d-flex justify-content-between">
                    <strong><?= e($m['other_name'] ?: $m['other_email']) ?></strong>
                    <small class="text-muted"><?= e(date('M j, g:ia', strtotime($m['created_at']))) ?></small>
                </div>
                <div class="small text-muted mb-1">
                    Re: <a href="books/view.php?id=<?= (int) $m['book_id'] ?>"><?= e($m['book_name']) ?></a>
                    <?= (int) $m['sender_id'] === $user['user_id'] ? '(you)' : '' ?>
                </div>
                <p class="mb-2"><?= nl2br(e($m['body'])) ?></p>
                <a href="messages/send.php?book_id=<?= (int) $m['book_id'] ?>" class="btn btn-sm btn-outline-primary">Reply</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
