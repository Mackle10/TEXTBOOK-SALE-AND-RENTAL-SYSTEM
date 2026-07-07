<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Forgot Password';
$errors = [];
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $errors[] = 'Invalid or expired form submission. Please try again.';
    }
    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (empty($errors)) {
        $token = createPasswordReset($email);
        // No mail server in dev: surface the link directly.
        if ($token !== null) {
            $resetLink = APP_URL . '/reset_password.php?email=' . urlencode($email) . '&token=' . urlencode($token);
        }
        setFlash('success', 'If that email exists, a reset link has been generated.');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card auth-card mx-auto" style="max-width: 480px;">
    <div class="card-body p-4">
        <h1 class="h3 mb-1 text-center">Forgot Password</h1>
        <p class="text-muted text-center mb-4">Enter your registered student email to reset your password.</p>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger py-2"><?= e($err) ?></div>
        <?php endforeach; ?>
        <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success py-2"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($resetLink !== ''): ?>
            <div class="alert alert-info py-2">
                Dev mode — email not sent. Use this link:<br>
                <a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a>
            </div>
        <?php endif; ?>

        <form method="post">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Student Email</label>
                <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
        </form>
        <p class="text-center mt-3 mb-0 small"><a href="login.php">Back to Login</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
