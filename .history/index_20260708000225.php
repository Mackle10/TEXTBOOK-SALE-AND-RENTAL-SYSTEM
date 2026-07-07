<?php
require_once __DIR__ . '/includes/functions.php';
processRentalReminders();

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';

$stats = ['books' => 0, 'courses' => 0, 'sellers' => 0, 'rentals' => 0];
try {
    $db = getDB();
    $stats['books']   = (int) $db->query("SELECT COUNT(*) FROM books WHERE book_status = 'available'")->fetchColumn();
    $stats['courses'] = (int) $db->query('SELECT COUNT(DISTINCT course_code) FROM books')->fetchColumn();
    $stats['sellers'] = (int) $db->query('SELECT COUNT(*) FROM sellers')->fetchColumn();
    $stats['rentals'] = (int) $db->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'")->fetchColumn();
} catch (Exception $e) {
    $dbError = true;
}
?>

<?php if ($msg = flash('success')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if (!empty($dbError)): ?>
    <div class="alert alert-warning">
        Database not ready. Start MySQL in XAMPP, then run
        <a href="setup.php" class="alert-link">setup.php</a> to import <code>textbooks.sql</code>.
    </div>
<?php endif; ?>

<div class="hero mb-5">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h1 class="display-5 fw-bold mb-3"><?= e(APP_NAME) ?></h1>
            <p class="lead mb-4">
                A trusted peer-to-peer campus marketplace to buy, sell, and rent textbooks.
                Save money and find course materials from fellow students.
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <a href="books/index.php" class="btn btn-light btn-lg">Browse by Course Code</a>
                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-outline-light btn-lg">Join the student marketplace</a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn btn-outline-light btn-lg">Go to Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4 d-none d-lg-block text-center">
            <i class="bi bi-journal-bookmark-fill" style="font-size: 8rem; opacity: 0.3;"></i>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card stat-card text-center p-3">
            <div class="display-6 text-primary fw-bold"><?= $stats['books'] ?></div>
            <div class="text-muted">Available Books</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-center p-3">
            <div class="display-6 text-primary fw-bold"><?= $stats['courses'] ?></div>
            <div class="text-muted">Course Codes</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-center p-3">
            <div class="display-6 text-primary fw-bold"><?= $stats['sellers'] ?></div>
            <div class="text-muted">Student Sellers</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card text-center p-3">
            <div class="display-6 text-primary fw-bold"><?= $stats['rentals'] ?></div>
            <div class="text-muted">Active Rentals</div>
        </div>
    </div>
</div>

<h2 class="h4 mb-3">Platform Features</h2>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card feature-card h-100 p-4">
            <i class="bi bi-shield-check text-primary fs-2 mb-2"></i>
            <h3 class="h5">Trusted Student Marketplace</h3>
            <p class="text-muted mb-0">Register with a valid student email and join a safe, verified campus textbook community.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card feature-card h-100 p-4">
            <i class="bi bi-search text-primary fs-2 mb-2"></i>
            <h3 class="h5">Course Code Matching</h3>
            <p class="text-muted mb-0">Find textbooks tagged by course codes like CS101 and match them to your syllabus instantly.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card feature-card h-100 p-4">
            <i class="bi bi-clock-history text-primary fs-2 mb-2"></i>
            <h3 class="h5">Rental Timer &amp; Reminders</h3>
            <p class="text-muted mb-0">Set rental duration, get automatic return date calculations and reminders before due dates.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card feature-card h-100 p-4">
            <i class="bi bi-credit-card text-primary fs-2 mb-2"></i>
            <h3 class="h5">Built-in Payments</h3>
            <p class="text-muted mb-0">Pay via Cash, Mobile Money, or Debit Card with transaction tracking and references.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card feature-card h-100 p-4">
            <i class="bi bi-broadcast text-primary fs-2 mb-2"></i>
            <h3 class="h5">Realtime Status Tracker</h3>
            <p class="text-muted mb-0">Track books as available, reserved, sold, or rented with live status updates.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card feature-card h-100 p-4">
            <i class="bi bi-qr-code text-primary fs-2 mb-2"></i>
            <h3 class="h5">QR Verification &amp; Waitlist</h3>
            <p class="text-muted mb-0">Verify transactions via QR codes and join waitlists when books are out of stock.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
