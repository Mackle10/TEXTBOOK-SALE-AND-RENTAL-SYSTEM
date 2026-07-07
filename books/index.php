<?php
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Browse Books';
$courseCode = strtoupper(trim($_GET['course'] ?? ''));
$search = trim($_GET['q'] ?? '');

$db = getDB();
$where = ' WHERE b.status = \'Active\'';
$params = [];

if ($courseCode !== '') {
    $where .= ' AND b.course_code LIKE ?';
    $params[] = $courseCode . '%';
}
if ($search !== '') {
    $where .= ' AND (b.name LIKE ? OR b.author LIKE ? OR b.course_code LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$countStmt = $db->prepare('SELECT COUNT(*) FROM books b LEFT JOIN sellers s ON b.seller_id = s.seller_id ' . $where);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$meta = paginationMeta($total);
$sql = 'SELECT b.*, s.name AS seller_name FROM books b LEFT JOIN sellers s ON b.seller_id = s.seller_id '
     . $where
     . ' ORDER BY b.book_status = \'available\' DESC, b.course_code, b.name'
     . ' LIMIT ' . (int) $meta['perPage'] . ' OFFSET ' . (int) $meta['offset'];

$stmt = $db->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$courses = $db->query('SELECT DISTINCT course_code FROM books ORDER BY course_code')->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">Browse Textbooks</h1>
    <span class="text-muted"><?= $total ?> result(s)</span>
</div>

<form method="get" class="row g-2 mb-4">
    <div class="col-md-5">
        <input type="text" name="q" class="form-control search-bar" placeholder="Search by title, author..."
               value="<?= e($search) ?>">
    </div>
    <div class="col-md-3">
        <select name="course" class="form-select">
            <option value="">All course codes</option>
            <?php foreach ($courses as $code): ?>
                <option value="<?= e($code) ?>" <?= $courseCode === $code ? 'selected' : '' ?>><?= e($code) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Search</button>
    </div>
    <div class="col-md-2">
        <a href="index.php" class="btn btn-outline-secondary w-100">Clear</a>
    </div>
</form>

<?php if (empty($books)): ?>
    <div class="alert alert-info">No books found. Try a different course code or search term.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($books as $book): ?>
            <div class="col-md-4 col-lg-3">
                <div class="card book-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="badge bg-primary"><?= e($book['course_code']) ?></span>
                        <span data-book-status-id="<?= (int) $book['book_id'] ?>"><?= statusBadge($book['book_status']) ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($book['cover_image'])): ?>
                            <img src="<?= e(APP_URL . '/assets/uploads/' . $book['cover_image']) ?>" alt="cover" class="img-fluid rounded mb-2" style="max-height:160px; width:100%; object-fit:cover;">
                        <?php endif; ?>
                        <h2 class="h6 card-title"><?= e($book['name']) ?></h2>
                        <p class="small text-muted mb-2"><?= e($book['author']) ?></p>
                        <?php if ($book['seller_name']): ?>
                            <p class="small mb-2"><i class="bi bi-person"></i> <?= e($book['seller_name']) ?></p>
                        <?php endif; ?>
                        <p class="fw-bold mb-1">KES <?= number_format($book['price'], 2) ?></p>
                        <?php if ($book['listing_type'] !== 'sale' && $book['rental_price_per_day']): ?>
                            <p class="small text-muted">Rent: KES <?= number_format($book['rental_price_per_day'], 2) ?>/day</p>
                        <?php endif; ?>
                        <a href="view.php?id=<?= (int) $book['book_id'] ?>" class="btn btn-sm btn-primary w-100">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= renderPager('index.php', $meta['totalPages'], $meta['page']) ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
