<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (currentUser()['role'] !== 'Seller') {
    setFlash('error', 'Only sellers can edit books.');
    redirect('dashboard.php');
}

$bookId = (int) ($_GET['id'] ?? 0);
$sellerId = getSellerIdForUser(currentUser()['user_id']);
$book = getBookById($bookId);

if (!$book || (int) $book['seller_id'] !== $sellerId) {
    setFlash('error', 'Book not found or access denied.');
    redirect('dashboard.php');
}

$pageTitle = 'Edit Book';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $courseCode = strtoupper(trim($_POST['course_code'] ?? ''));
    $price = (float) ($_POST['price'] ?? 0);
    $listingType = $_POST['listing_type'] ?? 'sale';
    $rentalPrice = $_POST['rental_price_per_day'] !== '' ? (float) $_POST['rental_price_per_day'] : null;
    $description = trim($_POST['description'] ?? '');
    $condition = trim($_POST['condition_note'] ?? 'Good');

    if ($name === '' || $author === '' || $courseCode === '' || $price <= 0) {
        $errors[] = 'Please fill in all required fields with valid values.';
    }

    if (empty($errors)) {
        $stmt = getDB()->prepare(
            'UPDATE books SET name=?, author=?, course_code=?, price=?, listing_type=?, rental_price_per_day=?,
             description=?, condition_note=? WHERE book_id=? AND seller_id=?'
        );
        $stmt->execute([$name, $author, $courseCode, $price, $listingType, $rentalPrice, $description, $condition, $bookId, $sellerId]);
        setFlash('success', 'Book updated successfully.');
        redirect('view.php?id=' . $bookId);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-4">Edit Textbook</h1>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>

<div class="card auth-card mx-0" style="max-width: 600px;">
    <div class="card-body p-4">
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Book Title</label>
                <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? $book['name']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Author</label>
                <input type="text" name="author" class="form-control" required value="<?= e($_POST['author'] ?? $book['author']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Course Code</label>
                <input type="text" name="course_code" class="form-control" required value="<?= e($_POST['course_code'] ?? $book['course_code']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= e($_POST['description'] ?? $book['description'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Condition</label>
                <select name="condition_note" class="form-select">
                    <?php foreach (['Like New', 'Good', 'Fair', 'Worn'] as $c): ?>
                        <option value="<?= $c ?>" <?= (($book['condition_note'] ?? 'Good') === $c) ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Listing Type</label>
                <select name="listing_type" class="form-select" id="listingType">
                    <?php foreach (['sale', 'rent', 'both'] as $lt): ?>
                        <option value="<?= $lt ?>" <?= ($book['listing_type'] === $lt) ? 'selected' : '' ?>><?= ucfirst($lt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Sale Price (KES)</label>
                <input type="number" name="price" class="form-control" step="0.01" min="0.01" required value="<?= e($book['price']) ?>">
            </div>
            <div class="mb-3" id="rentalField">
                <label class="form-label">Rental Price per Day (KES)</label>
                <input type="number" name="rental_price_per_day" class="form-control" step="0.01" min="0.01"
                       value="<?= e($book['rental_price_per_day'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="view.php?id=<?= $bookId ?>" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<script>
document.getElementById('listingType').addEventListener('change', function () {
    document.getElementById('rentalField').style.display = (this.value === 'rent' || this.value === 'both') ? 'block' : 'none';
}).dispatchEvent(new Event('change'));
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
