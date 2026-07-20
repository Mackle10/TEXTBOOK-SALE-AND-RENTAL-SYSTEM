<?php
require_once __DIR__ . '/../includes/admin_gatekeeper.php';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        setFlash('error', 'Invalid request.');
        redirect('admin/users.php');
    }

    $userId = (int) ($_POST['user_id'] ?? 0);
    if ($userId <= 0) {
        setFlash('error', 'Invalid user selected.');
        redirect('admin/users.php');
    }

    if (isset($_POST['promote_admin'])) {
        $stmt = $db->prepare('UPDATE users SET role = ? WHERE user_id = ?');
        $stmt->execute(['admin', $userId]);
        setFlash('success', 'User promoted to admin.');
        redirect('admin/users.php');
    }

    if (isset($_POST['revoke_admin'])) {
        $stmt = $db->prepare('UPDATE users SET role = ? WHERE user_id = ?');
        $stmt->execute(['user', $userId]);
        setFlash('success', 'Admin privileges revoked.');
        redirect('admin/users.php');
    }
}

$users = $db->query('SELECT user_id, email, phone, role FROM users ORDER BY user_id DESC')->fetchAll();
$pageTitle = 'User Management';
?>

<h1 class="h3 mb-4"><?= e($pageTitle) ?></h1>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= (int) $user['user_id'] ?></td>
                                <td><?= e($user['email']) ?></td>
                                <td><?= e($user['phone']) ?></td>
                                <td><span class="badge bg-<?= $user['role'] === 'admin' ? 'warning text-dark' : 'secondary' ?>"><?= e(ucfirst($user['role'])) ?></span></td>
                                <td>
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <form method="post" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                                            <button type="submit" name="promote_admin" class="btn btn-sm btn-success">Promote</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Revoke admin privileges?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                                            <button type="submit" name="revoke_admin" class="btn btn-sm btn-outline-warning">Revoke</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
