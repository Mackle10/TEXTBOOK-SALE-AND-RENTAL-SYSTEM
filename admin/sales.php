<?php
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();
$sales = $db->query(
    'SELECT s.*, p.amount_paid, p.payment_status, p.payment_method AS pay_method, p.reference_no
     FROM sales s
     LEFT JOIN payments p ON p.sale_id = s.sale_id
     ORDER BY s.sale_id DESC'
)->fetchAll();
$pageTitle = 'Sales & Payments';
?>

<h1 class="h3 mb-4">Sales &amp; Payments</h1>

<div class="table-responsive">
    <table class="table table-hover bg-white rounded shadow-sm">
        <thead class="table-light">
            <tr>
                <th>ID</th><th>Customer</th><th>Product</th><th>Qty</th><th>Amount</th>
                <th>Payment</th><th>Status</th><th>Location</th><th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales as $s): ?>
            <tr>
                <td>#<?= (int) $s['sale_id'] ?></td>
                <td><?= e($s['customer_name']) ?></td>
                <td><?= e($s['product_name']) ?></td>
                <td><?= (int) $s['quantity'] ?></td>
                <td>KES <?= number_format($s['amount_paid'] ?? $s['price'], 2) ?></td>
                <td><?= e($s['pay_method'] ?? $s['payment_method']) ?></td>
                <td><?= statusBadge($s['payment_status'] ?? 'Paid') ?></td>
                <td><?= e($s['location']) ?></td>
                <td><?= e($s['sale_date']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
