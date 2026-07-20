<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();
processRentalReminders();

$user = currentUser();
$db = getDB();
$pageTitle = 'Dashboard';

// fetch distinct course codes to use as categories in the dashboard sidebar
$categories = $db->query('SELECT DISTINCT course_code FROM books ORDER BY course_code')->fetchAll(PDO::FETCH_COLUMN);

$student = $db->prepare('SELECT * FROM students WHERE user_id = ?');
$student->execute([$user['user_id']]);
$profile = $student->fetch();

$myRentals = $db->prepare(
    "SELECT r.*, b.name AS book_name, b.course_code, b.seller_id
     FROM rentals r
     JOIN books b ON r.book_id = b.book_id
     WHERE r.borrower_user_id = ?
     ORDER BY r.return_date ASC"
);
$myRentals->execute([$user['user_id']]);
$rentals = $myRentals->fetchAll();

$sellerRentals = [];
if ($user['role'] === 'Seller') {
    $sellerId = getSellerIdForUser($user['user_id']);
    if ($sellerId) {
        $sr = $db->prepare(
            "SELECT r.*, b.name AS book_name, b.course_code, u.email AS borrower_email
             FROM rentals r
             JOIN books b ON r.book_id = b.book_id
             JOIN users u ON r.borrower_user_id = u.user_id
             WHERE b.seller_id = ? AND r.status IN ('active', 'overdue')
             ORDER BY r.return_date ASC"
        );
        $sr->execute([$sellerId]);
        $sellerRentals = $sr->fetchAll();
    }
}

$myWaitlist = $db->prepare(
    "SELECT w.*, b.name AS book_name, b.course_code, b.book_status
     FROM waitlist w
     JOIN books b ON w.book_id = b.book_id
     WHERE w.user_id = ?
     ORDER BY w.joined_at DESC"
);
$myWaitlist->execute([$user['user_id']]);
$waitlist = $myWaitlist->fetchAll();

$myBooks = [];
if ($user['role'] === 'Seller') {
    $sellerId = getSellerIdForUser($user['user_id']);
    if ($sellerId) {
        $stmt = $db->prepare('SELECT * FROM books WHERE seller_id = ? ORDER BY book_id DESC');
        $stmt->execute([$sellerId]);
        $myBooks = $stmt->fetchAll();
    }
}

$recentNotifs = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
try {
    $recentNotifs->execute([$user['user_id']]);
    $notifications = $recentNotifs->fetchAll();
} catch (PDOException) {
    $notifications = [];
}

$unreadNotifications = getUnreadNotificationCount($user['user_id']);
$myRentalCount = count($rentals);
$activeRentalCount = 0;
$overdueRentalCount = 0;
foreach ($rentals as $r) {
    if (in_array($r['status'], ['active', 'overdue'], true)) {
        $activeRentalCount++;
    }
    if ($r['status'] === 'overdue') {
        $overdueRentalCount++;
    }
}
$waitlistCount = count($waitlist);
$myBookCount = count($myBooks);
$sellerSummary = null;
$sellerPendingReturns = 0;
$sellerPerformanceLabels = [];
$sellerPerformanceRevenue = [];

if ($user['role'] === 'Seller') {
    $sellerId = getSellerIdForUser($user['user_id']);
    if ($sellerId) {
        $sellerSummaryStmt = $db->prepare(
            "SELECT COALESCE(SUM(p.amount_paid), 0) AS earned, COUNT(DISTINCT s.sale_id) AS deals
             FROM sales s
             JOIN payments p ON p.sale_id = s.sale_id
             JOIN qr_tokens qt ON qt.sale_id = s.sale_id
             JOIN books b ON b.book_id = qt.book_id
             WHERE b.seller_id = ? AND p.payment_status = 'Paid'"
        );
        $sellerSummaryStmt->execute([$sellerId]);
        $sellerSummary = $sellerSummaryStmt->fetch();
        $sellerPendingReturns = count($sellerRentals);

        $sellerPerformanceStmt = $db->prepare(
            "SELECT DATE_FORMAT(s.sale_date, '%b %Y') AS label, COALESCE(SUM(p.amount_paid), 0) AS revenue
             FROM sales s
             JOIN payments p ON p.sale_id = s.sale_id
             JOIN qr_tokens qt ON qt.sale_id = s.sale_id
             JOIN books b ON b.book_id = qt.book_id
             WHERE b.seller_id = ? AND p.payment_status = 'Paid' AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY label
             ORDER BY MIN(s.sale_date) ASC"
        );
        $sellerPerformanceStmt->execute([$sellerId]);
        $performanceRows = $sellerPerformanceStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($performanceRows as $row) {
            $sellerPerformanceLabels[] = $row['label'];
            $sellerPerformanceRevenue[] = (float) $row['revenue'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row">
    <aside class="col-lg-3 mb-4 mb-lg-0">
        <div class="dashboard-aside">
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="h6 text-uppercase text-muted mb-3">Categories</h3>
                    <div class="list-group list-group-flush dashboard-category-list">
                        <a href="books/index.php" class="list-group-item list-group-item-action">All course codes</a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="books/index.php?course=<?= urlencode($cat) ?>" class="list-group-item list-group-item-action"><?= e($cat) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="h6 text-uppercase text-muted mb-3">Quick Actions</h3>
                    <div class="d-grid gap-2">
                        <a href="books/index.php" class="btn btn-light btn-sm">Browse Books</a>
                        <a href="transactions.php" class="btn btn-light btn-sm">Transactions</a>
                        <?php if ($user['role'] === 'Seller'): ?>
                            <a href="books/upload.php" class="btn btn-primary btn-sm">Upload Book</a>
                        <?php endif; ?>
                        <a href="verify.php" class="btn btn-light btn-sm">QR Verify</a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3 class="h6 text-uppercase text-muted mb-3">Notifications</h3>
                    <p class="display-6 mb-1"><?= $unreadNotifications ?></p>
                    <p class="small text-muted mb-0">Unread alerts</p>
                </div>
            </div>
        </div>
    </aside>

    <section class="col-lg-9">
        <div class="dashboard-hero card mb-4">
            <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-4 align-items-start align-items-lg-center">
                <div>
                    <h1 class="h3 text-white mb-2">Welcome back, <?= e($profile['first_name'] ?? explode('@', $user['email'])[0]) ?>.</h1>
                    <p class="text-white-75 mb-3">Your textbook marketplace dashboard is ready. Track rentals, listings, and course categories from one place.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="books/index.php" class="btn btn-white btn-sm">Browse books</a>
                        <a href="profile.php" class="btn btn-outline-white btn-sm">Edit profile</a>
                    </div>
                </div>
                <div class="dashboard-hero-meta text-white text-end">
                    <p class="small text-uppercase mb-2 opacity-75">Current role</p>
                    <div class="badge bg-white bg-opacity-10 text-white py-2 px-3 rounded-pill"><?= e($user['role']) ?></div>
                </div>
            </div>
        </div>

        <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = flash('error')): ?>
            <div class="alert alert-danger"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['rental_reminders'])): ?>
            <?php foreach ($_SESSION['rental_reminders'] as $reminder): ?>
                <div class="alert alert-warning"><i class="bi bi-bell"></i> <?= e($reminder) ?></div>
            <?php endforeach; unset($_SESSION['rental_reminders']); ?>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card dashboard-metric p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted small text-uppercase">Active rentals</span>
                        <i class="bi bi-book-half fs-4 text-primary"></i>
                    </div>
                    <h2 class="mb-1"><?= $activeRentalCount ?></h2>
                    <p class="small text-muted mb-0"><?= $overdueRentalCount ?> overdue</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card dashboard-metric p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted small text-uppercase">My rentals</span>
                        <i class="bi bi-calendar-check fs-4 text-info"></i>
                    </div>
                    <h2 class="mb-1"><?= $myRentalCount ?></h2>
                    <p class="small text-muted mb-0">Total rentals</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card dashboard-metric p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted small text-uppercase">My listings</span>
                        <i class="bi bi-collection fs-4 text-success"></i>
                    </div>
                    <h2 class="mb-1"><?= $myBookCount ?></h2>
                    <p class="small text-muted mb-0">Seller listings</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card dashboard-metric p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted small text-uppercase">Categories</span>
                        <i class="bi bi-tags fs-4 text-success"></i>
                    </div>
                    <h2 class="mb-1"><?= count($categories) ?></h2>
                    <p class="small text-muted mb-0">Course codes available</p>
                </div>
            </div>
        </div>

        <?php if ($sellerSummary): ?>
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-4">
                    <div class="card dashboard-metric p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted small text-uppercase">Total earnings</span>
                            <i class="bi bi-currency-dollar fs-4 text-success"></i>
                        </div>
                        <h2 class="mb-1">KES <?= number_format((float) $sellerSummary['earned'], 2) ?></h2>
                        <p class="small text-muted mb-0">Earnings from paid sales</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="card dashboard-metric p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted small text-uppercase">Completed deals</span>
                            <i class="bi bi-check2-all fs-4 text-primary"></i>
                        </div>
                        <h2 class="mb-1"><?= (int) $sellerSummary['deals'] ?></h2>
                        <p class="small text-muted mb-0">Paid sales completed</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="card dashboard-metric p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted small text-uppercase">Pending returns</span>
                            <i class="bi bi-clock-history fs-4 text-warning"></i>
                        </div>
                        <h2 class="mb-1"><?= $sellerPendingReturns ?></h2>
                        <p class="small text-muted mb-0">Rentals awaiting return</p>
                    </div>
                </div>
            </div>
            <?php if (!empty($sellerPerformanceLabels)): ?>
                <div class="card dashboard-section mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Seller performance</h2>
                            <span class="small text-muted">Last 6 months</span>
                        </div>
                        <canvas id="sellerPerformanceChart" height="220"></canvas>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card mb-4 dashboard-section">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                    <div>
                        <h2 class="h5 mb-1">My Rentals</h2>
                        <p class="small text-muted mb-0">Your current rental activity and return status.</p>
                    </div>
                    <a href="transactions.php" class="btn btn-sm btn-outline-primary">View transactions</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Book</th><th>Course</th><th>Return Date</th><th>Status</th><th>Days Left</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rentals as $r): ?>
                                <?php
                                $daysLeft = (int) ((strtotime($r['return_date']) - time()) / 86400);
                                $rowClass = $daysLeft < 0 ? 'table-danger' : ($daysLeft <= RENTAL_REMINDER_DAYS ? 'table-warning' : '');
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td><?= e($r['book_name']) ?></td>
                                    <td><span class="badge bg-primary"><?= e($r['course_code']) ?></span></td>
                                    <td><?= e($r['return_date']) ?></td>
                                    <td><?= statusBadge($r['status']) ?></td>
                                    <td><?= $daysLeft >= 0 ? $daysLeft . ' days' : abs($daysLeft) . ' days overdue' ?></td>
                                    <td>
                                        <?php if (in_array($r['status'], ['active', 'overdue'], true)): ?>
                                            <a href="rentals/return.php?id=<?= (int) $r['rental_id'] ?>&csrf=<?= urlencode(csrfToken()) ?>" class="btn btn-sm btn-success"
                                                onclick="return confirm('Mark this book as returned?')">Return</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card dashboard-section mb-4 h-100">
                    <div class="card-body">
                        <h2 class="h5 mb-3">My Waitlist</h2>
                        <?php if ($waitlist): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($waitlist as $w): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div>
                                            <strong><?= e($w['book_name']) ?></strong>
                                            <div class="small text-muted"><?= e($w['course_code']) ?> · <?= statusBadge($w['book_status']) ?></div>
                                        </div>
                                        <a href="books/view.php?id=<?= (int) $w['book_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small mb-0">No waitlist items yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($user['role'] === 'Seller'): ?>
                <div class="col-lg-6">
                    <div class="card dashboard-section mb-4 h-100">
                        <div class="card-body">
                            <h2 class="h5 mb-3">My Listings</h2>
                            <?php if ($myBooks): ?>
                                <div class="row g-3">
                                    <?php foreach ($myBooks as $book): ?>
                                        <div class="col-sm-6">
                                            <div class="card book-card h-100">
                                                <div class="card-body">
                                                    <h3 class="h6 mb-1"><?= e($book['name']) ?></h3>
                                                    <p class="small text-muted mb-2"><?= e($book['author']) ?> · <?= e($book['course_code']) ?></p>
                                                    <div class="mb-2"><?= statusBadge($book['book_status']) ?></div>
                                                    <p class="fw-bold mb-3">KES <?= number_format($book['price'], 2) ?></p>
                                                    <div class="d-flex gap-1 flex-wrap">
                                                        <a href="books/view.php?id=<?= (int) $book['book_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                        <a href="books/edit.php?id=<?= (int) $book['book_id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small mb-0">You haven’t uploaded any listings yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if (!empty($sellerPerformanceLabels)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.sellerPerformanceLabels = <?= json_encode($sellerPerformanceLabels) ?>;
        window.sellerPerformanceRevenue = <?= json_encode($sellerPerformanceRevenue) ?>;
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
