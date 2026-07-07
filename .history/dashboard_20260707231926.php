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

require_once __DIR__ . '/includes/header.php';
?>

<div class="row">
    <aside class="col-md-3">
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="h6 text-uppercase text-muted mb-3">Academic Books</h3>
                <h4 class="h6 mb-2">Categories</h4>
                <div class="list-group list-group-flush">
                    <a href="books/index.php" class="list-group-item list-group-item-action">All course codes</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="books/index.php?course=<?= urlencode($cat) ?>" class="list-group-item list-group-item-action"><?= e($cat) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </aside>

    <div class="col-md-9">

        <h1 class="h3 mb-4">Dashboard</h1>

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

        <div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card stat-card p-4">
            <h2 class="h6 text-muted">Profile</h2>
            <p class="mb-1"><strong><?= e(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')) ?></strong></p>
            <p class="mb-1 small"><?= e($user['email']) ?> · <?= e($user['role']) ?></p>
            <?php
                $profileNotes = [];
                if (!empty($profile['student_category'])) {
                    $profileNotes[] = e($profile['student_category']);
                }
                if (!empty($profile['education_level'])) {
                    $profileNotes[] = e($profile['education_level']);
                }
                if (!empty($profile['year_class'])) {
                    $profileNotes[] = e($profile['year_class']);
                }
            ?>
            <p class="mb-2 small text-muted"><?= $profileNotes ? implode(' · ', $profileNotes) : 'Student' ?></p>
            <a href="profile.php" class="btn btn-sm btn-outline-primary">Edit Profile</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card p-4">
            <h2 class="h6 text-muted">Quick Actions</h2>
            <div class="d-flex gap-2 flex-wrap">
                <a href="books/index.php" class="btn btn-outline-primary btn-sm">Browse Books</a>
                <a href="transactions.php" class="btn btn-outline-primary btn-sm">Transactions</a>
                <?php if ($user['role'] === 'Seller'): ?>
                    <a href="books/upload.php" class="btn btn-primary btn-sm">Upload Book</a>
                <?php endif; ?>
                <a href="verify.php" class="btn btn-outline-secondary btn-sm">QR Verify</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card p-4">
            <h2 class="h6 text-muted">Recent Notifications</h2>
            <?php if ($notifications): ?>
                <ul class="list-unstyled small mb-2">
                    <?php foreach ($notifications as $n): ?>
                        <li class="mb-1 <?= $n['is_read'] ? 'text-muted' : 'fw-semibold' ?>"><?= e($n['message']) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="notifications.php" class="btn btn-sm btn-outline-secondary">View all</a>
            <?php else: ?>
                <p class="small text-muted mb-0">No notifications yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$sellerSummary = null;
if ($user['role'] === 'Seller') {
    $sid = getSellerIdForUser($user['user_id']);
    if ($sid) {
        $sum = $db->prepare(
            "SELECT COALESCE(SUM(p.amount_paid), 0) AS earned, COUNT(DISTINCT s.sale_id) AS deals
             FROM sales s
             JOIN payments p ON p.sale_id = s.sale_id
             JOIN qr_tokens qt ON qt.sale_id = s.sale_id
             JOIN books b ON b.book_id = qt.book_id
             WHERE b.seller_id = ? AND p.payment_status = 'Paid'"
        );
        $sum->execute([$sid]);
        $sellerSummary = $sum->fetch();
    }
}
?>

<?php if ($sellerSummary): ?>
<h2 class="h5 mb-3"><i class="bi bi-graph-up"></i> Seller Summary</h2>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card stat-card p-4">
            <h2 class="h6 text-muted">Total Earnings</h2>
            <p class="h3 text-success mb-0">KES <?= number_format((float) $sellerSummary['earned'], 2) ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card p-4">
            <h2 class="h6 text-muted">Completed Deals</h2>
            <p class="h3 mb-0"><?= (int) $sellerSummary['deals'] ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card p-4">
            <h2 class="h6 text-muted">Active Listings</h2>
            <p class="h3 mb-0"><?= count($myBooks) ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($rentals): ?>
<h2 class="h5 mb-3"><i class="bi bi-clock"></i> My Rentals</h2>
<div class="table-responsive mb-4">
    <table class="table table-hover bg-white rounded shadow-sm">
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
                                onclick="return confirm('Mark this book as returned?')">Return Book</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($sellerRentals): ?>
<h2 class="h5 mb-3"><i class="bi bi-arrow-left-right"></i> Rentals on My Books</h2>
<div class="table-responsive mb-4">
    <table class="table table-hover bg-white rounded shadow-sm">
        <thead class="table-light">
            <tr><th>Book</th><th>Borrower</th><th>Return Date</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($sellerRentals as $r): ?>
            <tr>
                <td><?= e($r['book_name']) ?></td>
                <td><?= e($r['borrower_email']) ?></td>
                <td><?= e($r['return_date']) ?></td>
                <td><?= statusBadge($r['status']) ?></td>
                <td>
                    <a href="rentals/return.php?id=<?= (int) $r['rental_id'] ?>&csrf=<?= urlencode(csrfToken()) ?>" class="btn btn-sm btn-outline-success"
                        onclick="return confirm('Confirm book was returned?')">Confirm Return</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($waitlist): ?>
<h2 class="h5 mb-3"><i class="bi bi-hourglass-split"></i> My Waitlist</h2>
<div class="table-responsive mb-4">
    <table class="table table-hover bg-white rounded shadow-sm">
        <thead class="table-light">
            <tr><th>Book</th><th>Course</th><th>Book Status</th><th>Joined</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($waitlist as $w): ?>
                <tr>
                    <td><?= e($w['book_name']) ?></td>
                    <td><?= e($w['course_code']) ?></td>
                    <td><?= statusBadge($w['book_status']) ?></td>
                    <td><?= e($w['joined_at']) ?></td>
                    <td><a href="books/view.php?id=<?= (int) $w['book_id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($user['role'] === 'Seller' && $myBooks): ?>
<h2 class="h5 mb-3"><i class="bi bi-upload"></i> My Listings</h2>
<div class="row g-3">
    <?php foreach ($myBooks as $book): ?>
        <div class="col-md-4">
            <div class="card book-card">
                <div class="card-body">
                    <h3 class="h6"><?= e($book['name']) ?></h3>
                    <p class="small text-muted mb-2"><?= e($book['author']) ?> · <?= e($book['course_code']) ?></p>
                    <div data-book-status-id="<?= (int) $book['book_id'] ?>"><?= statusBadge($book['book_status']) ?></div>
                    <p class="mt-2 mb-0 fw-bold">KES <?= number_format($book['price'], 2) ?></p>
                    <div class="mt-2 d-flex gap-1">
                        <a href="books/view.php?id=<?= (int) $book['book_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                        <a href="books/edit.php?id=<?= (int) $book['book_id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

    </div> <!-- .col-md-9 -->
</div> <!-- .row -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
