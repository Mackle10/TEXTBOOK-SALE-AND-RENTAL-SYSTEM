<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: ' . APP_URL . '/admin/index.php');
    exit;
}

$pageTitle = 'Admin Login';
$errors = [];

function isAdminLoggedInEarly(): bool
{
    return isset($_SESSION['admin_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../config/database.php';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = getDB()->prepare('SELECT * FROM roles WHERE role_name = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['role_id'];
        $_SESSION['admin_name'] = $admin['role_name'];
        header('Location: ' . APP_URL . '/admin/index.php');
        exit;
    }
    $errors[] = 'Invalid admin credentials.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark">
<div class="container py-5">
    <div class="card mx-auto shadow" style="max-width: 420px;">
        <div class="card-body p-4">
            <h1 class="h4 text-center mb-4">Admin Login</h1>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required value="Admin">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-dark w-100">Login</button>
            </form>
            <p class="text-center mt-3 mb-0 small"><a href="../index.php">Back to site</a></p>
        </div>
    </div>
</div>
</body>
</html>
