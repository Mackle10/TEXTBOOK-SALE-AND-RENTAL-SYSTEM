<?php
require_once __DIR__ . '/../includes/admin_gatekeeper.php';
require_once __DIR__ . '/../includes/admin_header.php';

$pageTitle = 'Admin Dashboard';
$db = getDB();

$pendingApprovals = $db->query(
    "SELECT b.book_id, b.name, b.course_code, b.status, s.name AS seller_name, s.email AS seller_email
     FROM books b
     LEFT JOIN sellers s ON b.seller_id = s.seller_id
     WHERE b.status = 'Inactive'
     ORDER BY b.book_id DESC
     LIMIT 6"
)->fetchAll();

$pendingCount = (int) $db->query("SELECT COUNT(*) FROM books WHERE status = 'Inactive'")->fetchColumn();
$activeRentalCount = (int) $db->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'")->fetchColumn();
$overdueRentalCount = (int) $db->query("SELECT COUNT(*) FROM rentals WHERE status = 'overdue'")->fetchColumn();

$activeRentals = $db->query(
    "SELECT r.rental_id, r.book_id, r.borrower_user_id, r.return_date, r.status,
            b.name AS book_name, u.email AS borrower_email
     FROM rentals r
     JOIN books b ON r.book_id = b.book_id
     LEFT JOIN users u ON r.borrower_user_id = u.user_id
     WHERE r.status = 'active'
     ORDER BY r.return_date ASC
     LIMIT 6"
)->fetchAll();

$overdueRentals = $db->query(
    "SELECT r.rental_id, r.book_id, r.borrower_user_id, r.return_date, r.status,
            b.name AS book_name, u.email AS borrower_email
     FROM rentals r
     JOIN books b ON r.book_id = b.book_id
     LEFT JOIN users u ON r.borrower_user_id = u.user_id
     WHERE r.status = 'overdue'
     ORDER BY r.return_date ASC
     LIMIT 6"
)->fetchAll();

$transactions = $db->query(
    "SELECT s.sale_id, s.product_name, s.customer_name, p.amount_paid, p.payment_method, p.payment_date
     FROM sales s
     JOIN payments p ON p.sale_id = s.sale_id
     ORDER BY p.payment_date DESC
     LIMIT 6"
)->fetchAll();
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e($pageTitle) ?></h1>
        <p class="text-muted mb-0">Use this panel to review new textbook submissions, track rentals, and audit sales activity.</p>
    </div>
    <div class="text-end">
        <span class="badge bg-secondary">Signed in as: <?= e($_SESSION['user_role']) ?></span>
    </div>
</div>

<div class="row gy-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title">Pending Book Approvals</h5>
                <p class="card-text text-muted">Review student submissions before they go live.</p>
                <p class="display-6 mb-0"><?= $pendingCount ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title">Active Rentals</h5>
                <p class="card-text text-muted">Monitor books currently out on rent.</p>
                <p class="display-6 mb-0"><?= $activeRentalCount ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title">Overdue Rentals</h5>
                <p class="card-text text-muted">Track late returns and follow up with borrowers.</p>
                <p class="display-6 mb-0"><?= $overdueRentalCount ?></p>
            </div>
        </div>
    </div>
</div>

<section id="pending" class="mb-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Pending Book Approvals</h2>
            <p class="small text-muted mb-0">These submissions still need admin approval before they go live.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Course</th>
                        <th>Seller</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pendingApprovals): ?>
                        <?php foreach ($pendingApprovals as $book): ?>
                            <tr>
                                <td><?= (int) $book['book_id'] ?></td>
                                <td><?= e($book['name']) ?></td>
                                <td><?= e($book['course_code']) ?></td>
                                <td><?= e($book['seller_email'] ?? $book['seller_name'] ?? 'Unknown') ?></td>
                                <td><span class="badge bg-warning">Pending</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No pending approvals at the moment.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section id="rentals" class="mb-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Rental Tracking</h2>
            <p class="small text-muted mb-0">Current rental activity and overdue items.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Rental ID</th>
                        <th>Book</th>
                        <th>Borrower</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($activeRentals || $overdueRentals): ?>
                        <?php foreach (array_merge($activeRentals, $overdueRentals) as $rental): ?>
                            <tr>
                                <td><?= (int) $rental['rental_id'] ?></td>
                                <td><?= e($rental['book_name']) ?></td>
                                <td><?= e($rental['borrower_email'] ?? 'Unknown') ?></td>
                                <td><?= e($rental['return_date']) ?></td>
                                <td>
                                    <?php if ($rental['status'] === 'overdue'): ?>
                                        <span class="badge bg-danger">Overdue</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Active</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No active or overdue rentals right now.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section id="transactions">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Transaction Logs</h2>
            <p class="small text-muted mb-0">Recent sales and payment activity across the platform.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tx ID</th>
                        <th>Type</th>
                        <th>Item</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions): ?>
                        <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td><?= (int) $tx['sale_id'] ?></td>
                                <td><?= e(str_contains(strtolower($tx['product_name']), 'rental') ? 'Rental' : 'Sale') ?></td>
                                <td><?= e($tx['product_name']) ?></td>
                                <td><?= e($tx['customer_name']) ?></td>
                                <td>KES <?= number_format((float) $tx['amount_paid'], 2) ?></td>
                                <td><?= e($tx['payment_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No recent transactions recorded.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
