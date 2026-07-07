<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (currentUser()['role'] !== 'Seller') {
    setFlash('error', 'Only sellers can upload books.');
    redirect('dashboard.php');
}

$pageTitle = 'Upload Book';
$errors = [];
$sellerId = getSellerIdForUser(currentUser()['user_id']);

if (!$sellerId) {
    setFlash('error', 'Seller profile not found.');
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $errors[] = 'Invalid or expired form submission. Please try again.';
    }
    $name = trim($_POST['name'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $courseCode = strtoupper(trim($_POST['course_code'] ?? ''));
    $price = (float) ($_POST['price'] ?? 0);
    $listingType = $_POST['listing_type'] ?? 'sale';
    $rentalPrice = $_POST['rental_price_per_day'] !== '' ? (float) $_POST['rental_price_per_day'] : null;

    $description = trim($_POST['description'] ?? '');
    $condition = trim($_POST['condition_note'] ?? 'Good');
    $coverImage = null;

    if (isset($_FILES['cover_image'])) {
        $coverImage = handleImageUpload($_FILES['cover_image']);
        if ($coverImage === false) {
            $errors[] = 'Cover image must be a JPG, PNG, GIF or WebP file under 2MB.';
        }
    }

    if ($name === '' || $author === '' || $courseCode === '') {
        $errors[] = 'Name, author, and course code are required.';
    }
    if ($price <= 0) {
        $errors[] = 'Sale price must be greater than zero.';
    }
    if (!in_array($listingType, ['sale', 'rent', 'both'], true)) {
        $errors[] = 'Invalid listing type.';
    }
    if (in_array($listingType, ['rent', 'both'], true) && (!$rentalPrice || $rentalPrice <= 0)) {
        $errors[] = 'Rental price per day is required for rent listings.';
    }

    if (empty($errors)) {
        $stmt = getDB()->prepare(
            'INSERT INTO books (seller_id, name, author, description, condition_note, course_code, price, listing_type, rental_price_per_day, cover_image, status, book_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'Active\', \'available\')'
        );
        $stmt->execute([$sellerId, $name, $author, $description, $condition, $courseCode, $price, $listingType, $rentalPrice, $coverImage]);

        $upd = getDB()->prepare('UPDATE sellers SET sell_price = ? WHERE seller_id = ?');
        $upd->execute([$price, $sellerId]);

        setFlash('success', 'Book uploaded successfully!');
        redirect('view.php?id=' . getDB()->lastInsertId());
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-4">Upload Textbook</h1>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card auth-card mx-0" style="max-width: 600px;">
    <div class="card-body p-4">
        <form method="post">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Book Title</label>
                <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Author</label>
                <input type="text" name="author" class="form-control" required value="<?= e($_POST['author'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Course Code</label>
                <input type="text" name="course_code" class="form-control" required placeholder="e.g. CS101"
                       value="<?= e($_POST['course_code'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Listing Type</label>
                <select name="listing_type" class="form-select" id="listingType">
                    <option value="sale">Sale only</option>
                    <option value="rent">Rent only</option>
                    <option value="both">Sale &amp; Rent</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Sale Price (KES)</label>
                <input type="number" name="price" class="form-control" step="0.01" min="0.01" required
                       value="<?= e($_POST['price'] ?? '') ?>">
            </div>
            <div class="mb-3" id="rentalField" style="display:none;">
                <label class="form-label">Rental Price per Day (KES)</label>
                <input type="number" name="rental_price_per_day" class="form-control" step="0.01" min="0.01"
                        value="<?= e($_POST['rental_price_per_day'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Cover Image (optional)</label>
                <input type="file" name="cover_image" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Upload Book</button>
            <a href="../dashboard.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<script>
document.getElementById('listingType').addEventListener('change', function () {
    const show = this.value === 'rent' || this.value === 'both';
    document.getElementById('rentalField').style.display = show ? 'block' : 'none';
});
document.getElementById('listingType').dispatchEvent(new Event('change'));
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
