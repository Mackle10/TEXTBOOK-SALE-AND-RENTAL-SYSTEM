<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Verify Email';
$token = trim($_GET['token'] ?? '');
$done = false;
$error = '';

if ($token === '') {
    $error = 'Missing verification token.';
} elseif (verifyEmail($token)) {
    $done = true;
} else {
    $error = 'This verification link is invalid or has already been used.';
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card auth-card mx-auto" style="max-width: 520px;">
    <div class="card-body p-4 text-center">
        <?php if ($done): ?>
            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
            <h1 class="h4 mt-2">Email Verified</h1>
            <p class="text-muted">Your account is now active. You can sign in.</p>
            <a href="login.php" class="btn btn-primary">Go to Login</a>
        <?php else: ?>
            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
            <h1 class="h4 mt-2">Verification Failed</h1>
            <p class="text-muted"><?= e($error) ?></p>
            <a href="login.php" class="btn btn-outline-secondary">Back to Login</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
