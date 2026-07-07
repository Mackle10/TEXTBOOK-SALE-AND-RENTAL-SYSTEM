<?php
require_once __DIR__ . '/../includes/functions.php';

$bookId = (int) ($_GET['id'] ?? 0);
$book = getBookById($bookId);

if (!$book) {
    setFlash('error', 'Book not found.');
    redirect('books/index.php');
}

$pageTitle = $book['name'];
$isAvailable = $book['book_status'] === 'available';
$user = currentUser();

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card book-card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge bg-primary mb-2"><?= e($book['course_code']) ?></span>
                        <h1 class="h3"><?= e($book['name']) ?></h1>
                        <p class="text-muted mb-0">by <?= e($book['author']) ?></p>
                    </div>
                    <div data-book-status-id="<?= (int) $book['book_id'] ?>"><?= statusBadge($book['book_status']) ?></div>
                </div>

                <hr>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <strong>Sale Price</strong>
                        <p class="h4 text-primary mb-0">KES <?= number_format($book['price'], 2) ?></p>
                    </div>
                    <?php if ($book['listing_type'] !== 'sale' && $book['rental_price_per_day']): ?>
                        <div class="col-sm-6">
                            <strong>Rental Rate</strong>
                            <p class="h5 mb-0">KES <?= number_format($book['rental_price_per_day'], 2) ?> / day</p>
                        </div>
                    <?php endif; ?>
                    <div class="col-sm-6">
                        <strong>Listing Type</strong>
                        <p class="mb-0 text-capitalize"><?= e($book['listing_type']) ?></p>
                    </div>
                    <?php if ($book['seller_name']): ?>
                        <div class="col-sm-6">
                            <strong>Seller</strong>
                            <p class="mb-0"><?= e($book['seller_name']) ?></p>
                            <p class="small text-muted mb-0"><?= e($book['seller_phone'] ?? '') ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4 d-flex gap-2 flex-wrap">
                    <?php if ($isAvailable && isLoggedIn()): ?>
                        <?php if (in_array($book['listing_type'], ['sale', 'both'], true)): ?>
                            <a href="../purchase.php?book_id=<?= $bookId ?>&type=sale" class="btn btn-primary">
                                <i class="bi bi-cart"></i> Buy Now
                            </a>
                        <?php endif; ?>
                        <?php if (in_array($book['listing_type'], ['rent', 'both'], true)): ?>
                            <a href="../purchase.php?book_id=<?= $bookId ?>&type=rent" class="btn btn-outline-primary">
                                <i class="bi bi-clock"></i> Rent
                            </a>
                        <?php endif; ?>
                    <?php elseif (!$isAvailable && isLoggedIn()): ?>
                        <a href="../waitlist/join.php?book_id=<?= $bookId ?>" class="btn btn-warning">
                            <i class="bi bi-hourglass-split"></i> Join Waitlist
                        </a>
                    <?php elseif (!isLoggedIn()): ?>
                        <a href="../login.php" class="btn btn-primary">Login to Purchase</a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-outline-secondary">Back to Browse</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card feature-card p-4">
            <h2 class="h6"><i class="bi bi-info-circle"></i> Course Code Matching</h2>
            <p class="small text-muted mb-0">
                This book is tagged for <strong><?= e($book['course_code']) ?></strong>.
                Students enrolled in this course can find it directly from the syllabus search.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
