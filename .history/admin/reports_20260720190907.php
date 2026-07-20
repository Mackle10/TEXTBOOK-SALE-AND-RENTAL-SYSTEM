<?php
require_once __DIR__ . '/../includes/admin_gatekeeper.php';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

// Export CSV of sales when requested
if (isset($_GET['export']) && $_GET['export'] === 'sales') {
    $stmt = $db->query("SELECT s.sale_id, s.product_name, s.customer_name, p.amount_paid, p.payment_method, p.payment_date
                        FROM sales s JOIN payments p ON p.sale_id = s.sale_id ORDER BY p.payment_date DESC");
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sales_export.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['sale_id','product_name','customer_name','amount_paid','payment_method','payment_date']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['sale_id'], $r['product_name'], $r['customer_name'], $r['amount_paid'], $r['payment_method'], $r['payment_date']]);
    }
    fclose($out);
    exit;
}

// Metrics
$totalUsers = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalAdmins = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$totalBooks = (int) $db->query("SELECT COUNT(*) FROM books")->fetchColumn();
$availableBooks = (int) $db->query("SELECT COUNT(*) FROM books WHERE book_status = 'available'")->fetchColumn();
$totalRentals = (int) $db->query("SELECT COUNT(*) FROM rentals")->fetchColumn();
$activeRentals = (int) $db->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'")->fetchColumn();
$overdueRentals = (int) $db->query("SELECT COUNT(*) FROM rentals WHERE status = 'overdue'")->fetchColumn();
$totalRevenue = (float) $db->query("SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE payment_status = 'Paid'")->fetchColumn();

$pageTitle = 'Reports & Analytics';
?>

// Build last 6 months labels (YYYY-MM) and ensure zero-filled series
function lastMonthsLabels(int $months = 6): array
{
    $labels = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $labels[] = date('Y-m', strtotime("-{$i} months"));
    }
    return $labels;
}

$months = 6;
$ymLabels = lastMonthsLabels($months);

// Revenue per month (last $months months)
$revStmt = $db->prepare(
    "SELECT DATE_FORMAT(p.payment_date, '%Y-%m') AS ym, COALESCE(SUM(p.amount_paid),0) AS revenue
     FROM payments p
     WHERE p.payment_status = 'Paid' AND p.payment_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
     GROUP BY ym ORDER BY ym ASC"
);
$revStmt->execute([$months]);
$revRows = $revStmt->fetchAll();
$revMap = array_fill_keys($ymLabels, 0.0);
foreach ($revRows as $r) {
    if (isset($revMap[$r['ym']])) {
        $revMap[$r['ym']] = (float) $r['revenue'];
    }
}

// Rentals per month (by start_date)
$rentStmt = $db->prepare(
    "SELECT DATE_FORMAT(start_date, '%Y-%m') AS ym, COUNT(*) AS cnt
     FROM rentals
     WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
     GROUP BY ym ORDER BY ym ASC"
);
$rentStmt->execute([$months]);
$rentRows = $rentStmt->fetchAll();
$rentMap = array_fill_keys($ymLabels, 0);
foreach ($rentRows as $r) {
    if (isset($rentMap[$r['ym']])) {
        $rentMap[$r['ym']] = (int) $r['cnt'];
    }
}

// Prepare labels for display (e.g. "Jul 2026")
$displayLabels = array_map(fn($ym) => date('M Y', strtotime($ym . '-01')), $ymLabels);

?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h3 mb-1"><?= e($pageTitle) ?></h1>
        <p class="text-muted mb-0">High-level metrics and exportable sales data.</p>
    </div>
    <div>
        <a href="?export=sales" class="btn btn-outline-primary btn-sm">Export Sales CSV</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Total users</small>
            <h4 class="mb-0"><?= $totalUsers ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Admins</small>
            <h4 class="mb-0"><?= $totalAdmins ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Books</small>
            <h4 class="mb-0"><?= $totalBooks ?></h4>
            <small class="text-muted">Available: <?= $availableBooks ?></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Revenue (KES)</small>
            <h4 class="mb-0"><?= number_format($totalRevenue, 2) ?></h4>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-3">Rental Summary</h5>
            <p class="mb-1"><strong><?= $totalRentals ?></strong> total rentals</p>
            <p class="mb-1"><strong><?= $activeRentals ?></strong> active rentals</p>
            <p class="mb-1"><strong><?= $overdueRentals ?></strong> overdue rentals</p>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-3">Quick Actions</h5>
            <a href="approvals.php" class="btn btn-sm btn-primary mb-2">Review Approvals</a>
            <a href="users.php" class="btn btn-sm btn-outline-secondary mb-2">Manage Users</a>
            <a href="?export=sales" class="btn btn-sm btn-outline-success">Export Sales CSV</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
