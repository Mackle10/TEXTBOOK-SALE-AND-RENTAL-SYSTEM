<?php
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

$stats = [
    'users'  => (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'books'  => (int) $db->query('SELECT COUNT(*) FROM books')->fetchColumn(),
    'sales'  => (int) $db->query('SELECT COUNT(*) FROM sales')->fetchColumn(),
    'active_rentals' => (int) $db->query("SELECT COUNT(*) FROM rentals WHERE status IN ('active','overdue')")->fetchColumn(),
    'waitlist' => (int) $db->query('SELECT COUNT(*) FROM waitlist')->fetchColumn(),
    'revenue' => (float) $db->query("SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE payment_status = 'Paid'")->fetchColumn(),
];

$recentSales = $db->query(
    'SELECT s.*, p.payment_status FROM sales s LEFT JOIN payments p ON p.sale_id = s.sale_id ORDER BY s.sale_id DESC LIMIT 8'
)->fetchAll();

$overdueRentals = $db->query(
    "SELECT r.*, b.name AS book_name, u.email AS borrower_email
     FROM rentals r
     JOIN books b ON r.book_id = b.book_id
     JOIN users u ON r.borrower_user_id = u.user_id
     WHERE r.status = 'overdue'
     ORDER BY r.return_date ASC"
)->fetchAll();
?>

<h1 class="h3 mb-4">Admin Dashboard</h1>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Users', $stats['users'], 'people', 'primary'],
        ['Books', $stats['books'], 'book', 'success'],
        ['Sales', $stats['sales'], 'cart', 'info'],
        ['Active Rentals', $stats['active_rentals'], 'clock', 'warning'],
        ['Waitlist', $stats['waitlist'], 'hourglass', 'secondary'],
        ['Revenue (KES)', number_format($stats['revenue'], 0), 'cash-stack', 'dark'],
    ];
    foreach ($cards as [$label, $value, $icon, $color]):
    ?>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card text-center p-3">
            <i class="bi bi-<?= $icon ?> text-<?= $color ?> fs-4"></i>
            <div class="fw-bold fs-5"><?= e((string) $value) ?></div>
            <div class="small text-muted"><?= e($label) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">Recent Sales</h2>
                <a href="sales.php" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>ID</th><th>Customer</th><th>Product</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentSales as $s): ?>
                        <tr>
                            <td>#<?= (int) $s['sale_id'] ?></td>
                            <td><?= e($s['customer_name']) ?></td>
                            <td><?= e($s['product_name']) ?></td>
                            <td>KES <?= number_format($s['price'], 2) ?></td>
                            <td><?= e($s['sale_date']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card p-3">
            <h2 class="h6 mb-3">Overdue Rentals</h2>
            <?php if (empty($overdueRentals)): ?>
                <p class="text-muted small mb-0">No overdue rentals.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($overdueRentals as $r): ?>
                    <li class="list-group-item px-0">
                        <strong><?= e($r['book_name']) ?></strong><br>
                        <span class="small text-muted"><?= e($r['borrower_email']) ?> · due <?= e($r['return_date']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="card p-3 mt-3">
            <h2 class="h6 mb-3">Quick Links</h2>
            <div class="d-grid gap-2">
                <a href="books.php" class="btn btn-outline-primary btn-sm">Manage Books</a>
                <a href="users.php" class="btn btn-outline-primary btn-sm">Manage Users</a>
                <a href="../index.php" class="btn btn-outline-secondary btn-sm">View Public Site</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
