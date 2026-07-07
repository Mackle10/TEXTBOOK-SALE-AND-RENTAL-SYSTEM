<?php
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!validateCsrf()) {
        setFlash('error', 'Invalid request.');
    } else {
        deleteUser((int) $_POST['user_id']);
        setFlash('success', 'User deleted.');
    }
    redirect('admin/users.php');
}

$total = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$meta = paginationMeta($total);
$users = $db->prepare(
    'SELECT u.*, s.first_name, s.last_name, s.course
     FROM users u
     LEFT JOIN students s ON s.user_id = u.user_id
     ORDER BY u.user_id DESC LIMIT ' . (int) $meta['perPage'] . ' OFFSET ' . (int) $meta['offset']
)->fetchAll();
$pageTitle = 'Manage Users';
?>

<h1 class="h3 mb-4">Manage Users</h1>

<div class="table-responsive">
    <table class="table table-hover bg-white rounded shadow-sm">
        <thead class="table-light">
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Course</th><th></th></tr>
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
                <td>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this user permanently?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                        <button type="submit" name="delete_user" value="1" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        </table>
    </div>
</div>

<?= renderPager('users.php', $meta['totalPages'], $meta['page']) ?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
