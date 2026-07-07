<?php
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    if (!validateCsrf()) {
        setFlash('error', 'Invalid request.');
        redirect('admin/books.php');
    }
    $bookId = (int) $_POST['book_id'];
    if ($_POST['new_status'] === 'available') {
        updateBookStatus($bookId, 'available');
        $db->prepare("UPDATE books SET status = 'Active' WHERE book_id = ?")->execute([$bookId]);
    } else {
        // Deactivate the listing without corrupting the sold/rented status semantics
        $db->prepare("UPDATE books SET status = 'Inactive' WHERE book_id = ?")->execute([$bookId]);
    }
    setFlash('success', 'Book updated.');
    redirect('admin/books.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    if (!validateCsrf()) {
        setFlash('error', 'Invalid request.');
    } else {
        deleteBook((int) $_POST['book_id']);
        setFlash('success', 'Book deleted.');
    }
    redirect('admin/books.php');
}

$total = (int) $db->query('SELECT COUNT(*) FROM books')->fetchColumn();
$meta = paginationMeta($total);
$books = $db->prepare(
    'SELECT b.*, s.name AS seller_name FROM books b LEFT JOIN sellers s ON b.seller_id = s.seller_id
     ORDER BY b.book_id DESC LIMIT ' . (int) $meta['perPage'] . ' OFFSET ' . (int) $meta['offset']
)->fetchAll();
$pageTitle = 'Manage Books';
?>

<h1 class="h3 mb-4">Manage Books</h1>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<div class="table-responsive">
    <table class="table table-hover bg-white rounded shadow-sm">
        <thead class="table-light">
            <tr>
                <th>ID</th><th>Title</th><th>Course</th><th>Seller</th><th>Price</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
            <tr>
                <td><?= (int) $book['book_id'] ?></td>
                <td><?= e($book['name']) ?></td>
                <td><span class="badge bg-primary"><?= e($book['course_code']) ?></span></td>
                <td><?= e($book['seller_name'] ?? '—') ?></td>
                <td>KES <?= number_format($book['price'], 2) ?></td>
                <td><?= statusBadge($book['book_status']) ?></td>
                <td>
                    <form method="post" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="book_id" value="<?= (int) $book['book_id'] ?>">
                        <?php if ($book['book_status'] !== 'available'): ?>
                            <input type="hidden" name="new_status" value="available">
                            <button type="submit" name="toggle_status" class="btn btn-sm btn-success">Mark Available</button>
                        <?php else: ?>
                            <input type="hidden" name="new_status" value="inactive">
                            <button type="submit" name="toggle_status" class="btn btn-sm btn-outline-danger">Deactivate</button>
                        <?php endif; ?>
                    </form>
                    <a href="../books/view.php?id=<?= (int) $book['book_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this book permanently?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="book_id" value="<?= (int) $book['book_id'] ?>">
                        <button type="submit" name="delete_book" value="1" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        </table>
    </div>
</div>

<?= renderPager('books.php', $meta['totalPages'], $meta['page']) ?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
