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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCsrf()) {
    $errors[] = 'Invalid or expired form submission. Please try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    deleteBook($bookId);
    setFlash('success', 'Listing deleted.');
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_book'])) {
    $name = trim($_POST['name'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $courseCode = strtoupper(trim($_POST['course_code'] ?? ''));
    $price = (float) ($_POST['price'] ?? 0);
    $listingType = $_POST['listing_type'] ?? 'sale';
    $rentalPrice = $_POST['rental_price_per_day'] !== '' ? (float) $_POST['rental_price_per_day'] : null;
    $description = trim($_POST['description'] ?? '');
    $condition = trim($_POST['condition_note'] ?? 'Good');
    $coverImage = $book['cover_image'] ?? null;

    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploaded = handleImageUpload($_FILES['cover_image']);
        if ($uploaded === false) {
            $errors[] = 'Cover image must be a JPG, PNG, GIF or WebP file under 2MB.';
        } elseif ($uploaded !== null) {
            $coverImage = $uploaded;
        }
    }

    if ($name === '' || $author === '' || $courseCode === '' || $price <= 0) {
        $errors[] = 'Please fill in all required fields with valid values.';
    }

    if (empty($errors)) {
        $stmt = getDB()->prepare(
            'UPDATE books SET name=?, author=?, course_code=?, price=?, listing_type=?, rental_price_per_day=?,
             description=?, condition_note=?, cover_image=? WHERE book_id=? AND seller_id=?'
        );
        $stmt->execute([$name, $author, $courseCode, $price, $listingType, $rentalPrice, $description, $condition, $coverImage, $bookId, $sellerId]);
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
            <?= csrfField() ?>
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
            <?php if (!empty($book['cover_image'])): ?>
                <div class="mb-3">
                    <label class="form-label">Current Cover</label><br>
                    <img src="<?= e(APP_URL . '/assets/uploads/' . $book['cover_image']) ?>" alt="cover" style="max-height:120px;" class="img-thumbnail">
                </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">Cover Image (optional)</label>
                <input type="file" name="cover_image" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="view.php?id=<?= $bookId ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" name="delete_book" value="1" class="btn btn-outline-danger float-end"
                    onclick="return confirm('Delete this listing permanently?')">Delete Listing</button>
        </form>
    </div>
</div>

<script>
document.getElementById('listingType').addEventListener('change', function () {
    document.getElementById('rentalField').style.display = (this.value === 'rent' || this.value === 'both') ? 'block' : 'none';
}).dispatchEvent(new Event('change'));
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
