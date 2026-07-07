<?php
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();
$users = $db->query(
    'SELECT u.*, s.first_name, s.last_name, s.course
     FROM users u
     LEFT JOIN students s ON s.user_id = u.user_id
     ORDER BY u.user_id DESC'
)->fetchAll();
$pageTitle = 'Manage Users';
?>

<h1 class="h3 mb-4">Manage Users</h1>

<div class="table-responsive">
    <table class="table table-hover bg-white rounded shadow-sm">
        <thead class="table-light">
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Course</th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int) $u['user_id'] ?></td>
                <td><?= e(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['phone']) ?></td>
                <td><span class="badge bg-<?= $u['role'] === 'Seller' ? 'primary' : 'secondary' ?>"><?= e($u['role']) ?></span></td>
                <td><?= e($u['course'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
