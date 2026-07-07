<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'QR Verification';
$result = null;
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$saleId = (int) ($_GET['sale'] ?? 0);

if ($token === '' && $saleId > 0) {
    // Resolve a sale ID to its most recent QR token (used by the Transactions "Verify" link)
    $stmt = getDB()->prepare('SELECT token FROM qr_tokens WHERE sale_id = ? ORDER BY token_id DESC LIMIT 1');
    $stmt->execute([$saleId]);
    $row = $stmt->fetch();
    if ($row) {
        $token = $row['token'];
    }
}

if ($token !== '') {
    $stmt = getDB()->prepare(
        'SELECT q.*, s.customer_name, s.product_name, s.price, s.sale_date, s.payment_method,
                p.payment_status, p.amount_paid, b.name AS book_name, b.course_code, b.book_status
         FROM qr_tokens q
         JOIN sales s ON q.sale_id = s.sale_id
         JOIN books b ON q.book_id = b.book_id
         LEFT JOIN payments p ON p.sale_id = s.sale_id
         WHERE q.token = ?
         LIMIT 1'
    );
    $stmt->execute([$token]);
    $result = $stmt->fetch();
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4"><i class="bi bi-qr-code-scan"></i> QR Code Verification</h1>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card p-4">
            <form method="get">
                <label class="form-label">Enter verification token or scan QR link</label>
                <input type="text" name="token" class="form-control mb-3" placeholder="Token from QR code"
                       value="<?= e($token) ?>">
                <button type="submit" class="btn btn-primary w-100">Verify Transaction</button>
            </form>
        </div>
    </div>
    <div class="col-md-7">
        <?php if ($token !== '' && !$result): ?>
            <div class="alert alert-danger">Invalid or expired verification token.</div>
        <?php elseif ($result): ?>
            <div class="card p-4 border-success">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-shield-check text-success fs-2 me-2"></i>
                    <h2 class="h5 mb-0">Verified Transaction</h2>
                </div>
                <table class="table table-sm mb-0">
                    <tr><th>Sale ID</th><td>#<?= (int) $result['sale_id'] ?></td></tr>
                    <tr><th>Book</th><td><?= e($result['book_name']) ?> (<?= e($result['course_code']) ?>)</td></tr>
                    <tr><th>Customer</th><td><?= e($result['customer_name']) ?></td></tr>
                    <tr><th>Amount</th><td>KES <?= number_format($result['amount_paid'] ?? $result['price'], 2) ?></td></tr>
                    <tr><th>Payment</th><td><?= e($result['payment_method']) ?> — <?= e($result['payment_status'] ?? 'N/A') ?></td></tr>
                    <tr><th>Date</th><td><?= e($result['sale_date']) ?></td></tr>
                    <tr><th>Book Status</th><td><?= statusBadge($result['book_status']) ?></td></tr>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-0">
                Scan a transaction QR code or paste the token from the receipt URL to verify a book sale or rental.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
