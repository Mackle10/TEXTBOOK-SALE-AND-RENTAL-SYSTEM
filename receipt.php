<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$tx = $_SESSION['last_transaction'] ?? null;
if (!$tx) {
    redirect('dashboard.php');
}

$book = getBookById($tx['book_id']);
$verifyUrl = APP_URL . '/verify.php?token=' . urlencode($tx['token']);
$pageTitle = 'Receipt';

require_once __DIR__ . '/includes/header.php';
unset($_SESSION['last_transaction']);
?>

<div class="card p-4 mx-auto" style="max-width: 640px;">
    <div class="text-center mb-4">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
        <h1 class="h4 mt-2">Transaction Complete</h1>
    </div>

    <table class="table">
        <tr><th>Sale ID</th><td>#<?= (int) $tx['sale_id'] ?></td></tr>
        <tr><th>Book</th><td><?= e($book['name'] ?? '') ?></td></tr>
        <tr><th>Type</th><td class="text-capitalize"><?= e($tx['type']) ?></td></tr>
        <tr><th>Amount</th><td>KES <?= number_format($tx['amount'], 2) ?></td></tr>
        <?php if ($tx['return_date']): ?>
            <tr><th>Return Date</th><td><?= e($tx['return_date']) ?></td></tr>
        <?php endif; ?>
    </table>

    <div class="qr-box mt-3">
        <h2 class="h6">QR Code Verification</h2>
        <p class="small text-muted">Scan to verify this transaction on campus</p>
        <img src="<?= e(qrCodeUrl($verifyUrl)) ?>" alt="QR Code" class="img-fluid mb-2" width="200">
        <p class="small mb-0"><a href="<?= e($verifyUrl) ?>"><?= e($verifyUrl) ?></a></p>
    </div>

    <div class="d-flex gap-2 mt-4 justify-content-center">
        <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
        <a href="books/index.php" class="btn btn-outline-secondary">Browse More</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
