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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    if (!validateCsrf()) {
        setFlash('error', 'Invalid request.');
    } else {
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 5) {
            setFlash('error', 'Please choose a rating between 1 and 5.');
        } else {
            addReview($bookId, (int) $user['user_id'], $rating, $comment);
            setFlash('success', 'Thanks for your review!');
        }
    }
    redirect('books/view.php?id=' . $bookId);
}

$avgRating = averageRating($bookId);
$reviews = getReviews($bookId);
$sellerUserId = !empty($book['seller_id']) ? getUserIdBySellerId((int) $book['seller_id']) : null;
$canReview = isLoggedIn() && $user && $sellerUserId && $sellerUserId !== (int) $user['user_id'];

require_once __DIR__ . '/../includes/header.php'; ?>
?>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card book-card">
        <div class="card-body p-4">
            <?php if (!empty($book['cover_image'])): ?>
                <img src="<?= e(APP_URL . '/assets/uploads/' . $book['cover_image']) ?>" alt="Cover of <?= e($book['name']) ?>" class="img-fluid rounded mb-3" style="max-height:340px;">
            <?php endif; ?>
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
                        <a href="../waitlist/join.php?book_id=<?= $bookId ?>&csrf=<?= urlencode(csrfToken()) ?>" class="btn btn-warning">
                            <i class="bi bi-hourglass-split"></i> Join Waitlist
                        </a>
                    <?php elseif (!isLoggedIn()): ?>
                        <a href="../login.php" class="btn btn-primary">Login to Purchase</a>
                    <?php endif; ?>
                    <?php
                    $sellerUserId = !empty($book['seller_id']) ? getUserIdBySellerId((int) $book['seller_id']) : null;
                    $canMessage = isLoggedIn() && $user && $sellerUserId && $sellerUserId !== (int) $user['user_id'];
                    ?>
                    <?php if ($canMessage): ?>
                        <a href="messages/send.php?book_id=<?= $bookId ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-chat-dots"></i> Message Seller
                        </a>
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

<div class="row g-4 mt-1">
    <div class="col-lg-8">
        <div class="card book-card">
            <div class="card-body p-4">
                <h2 class="h5 mb-3"><i class="bi bi-star-fill text-warning"></i> Reviews
                    <?php if ($avgRating !== null): ?>
                        <span class="badge bg-warning text-dark"><?= $avgRating ?> / 5</span>
                        <small class="text-muted">(<?= count($reviews) ?>)</small>
                    <?php else: ?>
                        <small class="text-muted">No reviews yet</small>
                    <?php endif; ?>
                </h2>

                <?php if ($canReview): ?>
                    <form method="post" class="mb-4 p-3 bg-light rounded">
                        <?= csrfField() ?>
                        <div class="mb-2">
                            <label class="form-label">Your rating</label>
                            <select name="rating" class="form-select" style="max-width:160px;" required>
                                <option value="">Choose…</option>
                                <?php foreach ([5,4,3,2,1] as $r): ?>
                                    <option value="<?= $r ?>"><?= $r ?> ★</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <textarea name="comment" class="form-control" rows="2" placeholder="Share your experience (optional)"></textarea>
                        </div>
                        <button type="submit" name="submit_review" value="1" class="btn btn-primary btn-sm">Submit Review</button>
                    </form>
                <?php elseif (isLoggedIn()): ?>
                    <p class="small text-muted">You cannot review your own listing.</p>
                <?php else: ?>
                    <p class="small text-muted"><a href="../login.php">Log in</a> to write a review.</p>
                <?php endif; ?>

                <?php if ($reviews): ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($reviews as $rv): ?>
                            <li class="border-top pt-2 mt-2">
                                <strong><?= e(trim(($rv['other_name'] ?? '') ?: $rv['email'])) ?></strong>
                                <span class="text-warning"><?= str_repeat('★', (int) $rv['rating']) ?><?= str_repeat('☆', 5 - (int) $rv['rating']) ?></span>
                                <small class="text-muted"><?= e(date('M j', strtotime($rv['created_at']))) ?></small>
                                <?php if ($rv['comment']): ?><p class="mb-1 small"><?= nl2br(e($rv['comment'])) ?></p><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
