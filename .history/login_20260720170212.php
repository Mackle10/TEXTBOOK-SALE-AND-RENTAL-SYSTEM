<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$pageTitle = 'Login';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $errors[] = 'Invalid or expired form submission. Please try again.';
    }
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (empty($user['email_verified'])) {
                $token = $user['verify_token'] ?: generateVerifyToken((int) $user['user_id']);
                $errors[] = 'Please verify your email first. <a href="' . e(APP_URL . '/verify_email.php?token=' . urlencode($token)) . '">Resend verification link</a>.';
            } else {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_role'] = $user['role'];
                setFlash('success', 'Welcome back!');

                if ($user['role'] === 'admin') {
                    redirect('admin/admin_dashboard.php');
                }

                redirect('dashboard.php');
            }
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card auth-card">
    <div class="card-body p-4">
        <h1 class="h3 mb-1 text-center">Login</h1>
        <p class="text-muted text-center mb-4">Access your <?= e(APP_NAME) ?> account</p>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger py-2"><?= e($err) ?></div>
        <?php endforeach; ?>
        <?php if ($msg = flash('error')): ?>
            <div class="alert alert-danger py-2"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['verify_notice'])): ?>
            <div class="alert alert-info py-2">
                Dev mode — email not sent. Verify here:
                <a href="<?= e($_SESSION['verify_notice']) ?>"><?= e($_SESSION['verify_notice']) ?></a>
            </div>
            <?php unset($_SESSION['verify_notice']); ?>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required
                       placeholder="you@example.com"
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <p class="text-center mt-3 mb-0 small">
            No account? <a href="register.php">Register</a><br>
            <a href="forgot_password.php">Forgot password?</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
