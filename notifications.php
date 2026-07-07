<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();
processRentalReminders();

$user = currentUser();
$db = getDB();
$pageTitle = 'Notifications';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$user['user_id']]);
    setFlash('success', 'All notifications marked as read.');
    redirect('notifications.php');
}

if (isset($_GET['read'])) {
    $nid = (int) $_GET['read'];
    $db->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?')->execute([$nid, $user['user_id']]);
}

$stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$stmt->execute([$user['user_id']]);
$notifications = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4"><i class="bi bi-bell"></i> Notifications</h1>

<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<?php if ($notifications): ?>
<form method="post" class="mb-3">
    <button type="submit" name="mark_read" class="btn btn-sm btn-outline-secondary">Mark all as read</button>
</form>
<div class="list-group">
    <?php foreach ($notifications as $n): ?>
        <a href="<?= $n['link'] ? APP_URL . '/' . e($n['link']) . (str_contains($n['link'], '?') ? '&' : '?') . 'read=' . $n['notification_id'] : '#' ?>"
           class="list-group-item list-group-item-action <?= $n['is_read'] ? '' : 'list-group-item-primary' ?>">
            <div class="d-flex justify-content-between">
                <span><?= e($n['message']) ?></span>
                <small class="text-muted"><?= e(date('M j, g:ia', strtotime($n['created_at']))) ?></small>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="alert alert-info">No notifications yet.</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
