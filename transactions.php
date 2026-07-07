<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();
$pageTitle = 'Transaction History';

$student = $db->prepare('SELECT first_name, last_name FROM students WHERE user_id = ?');
$student->execute([$user['user_id']]);
$profile = $student->fetch();
$fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));

$stmt = $db->prepare(
    'SELECT s.*, p.amount_paid, p.payment_status, p.reference_no
     FROM sales s
     LEFT JOIN payments p ON p.sale_id = s.sale_id
     WHERE s.customer_name = ?
     ORDER BY s.sale_date DESC, s.sale_id DESC'
);
$stmt->execute([$fullName]);
$transactions = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4"><i class="bi bi-receipt"></i> Transaction History</h1>

<?php if (empty($transactions)): ?>
    <div class="alert alert-info">No transactions yet. <a href="books/index.php">Browse books</a> to get started.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover bg-white rounded shadow-sm">
        <thead class="table-light">
            <tr>
                <th>ID</th><th>Product</th><th>Type</th><th>Amount</th><th>Payment</th><th>Status</th><th>Date</th><th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $t): ?>
            <?php $isRental = str_contains($t['product_name'], 'Rental'); ?>
            <tr>
                <td>#<?= (int) $t['sale_id'] ?></td>
                <td><?= e($t['product_name']) ?></td>
                <td><span class="badge bg-<?= $isRental ? 'info' : 'primary' ?>"><?= $isRental ? 'Rental' : 'Purchase' ?></span></td>
                <td>KES <?= number_format($t['amount_paid'] ?? $t['price'], 2) ?></td>
                <td><?= e($t['payment_method']) ?></td>
                <td><?= statusBadge($t['payment_status'] ?? 'Paid') ?></td>
                <td><?= e($t['sale_date']) ?></td>
                <td><a href="verify.php?sale=<?= (int) $t['sale_id'] ?>" class="btn btn-sm btn-outline-secondary">Verify</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
