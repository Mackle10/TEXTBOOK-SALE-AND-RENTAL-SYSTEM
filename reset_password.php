<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Reset Password';
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];

if ($email === '' || $token === '' || !validResetToken($email, $token)) {
    $invalid = true;
} else {
    $invalid = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$invalid) {
    if (!validateCsrf()) {
        $errors[] = 'Invalid or expired form submission. Please try again.';
    }
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (empty($errors)) {
        consumePasswordReset($email, $token, password_hash($password, PASSWORD_DEFAULT));
        setFlash('success', 'Password updated. Please log in.');
        redirect('login.php');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card auth-card mx-auto" style="max-width: 480px;">
    <div class="card-body p-4">
        <h1 class="h3 mb-1 text-center">Reset Password</h1>

        <?php if ($invalid): ?>
            <div class="alert alert-danger">This reset link is invalid or has expired. <a href="forgot_password.php">Request a new one</a>.</div>
        <?php else: ?>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger py-2"><?= e($err) ?></div>
            <?php endforeach; ?>
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="email" value="<?= e($email) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Update Password</button>
            </form>
        <?php endif; ?>
        <p class="text-center mt-3 mb-0 small"><a href="login.php">Back to Login</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
