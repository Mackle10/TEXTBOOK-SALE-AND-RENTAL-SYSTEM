<?php
require_once __DIR__ . '/../includes/admin_gatekeeper.php';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        setFlash('error', 'Invalid request.');
        redirect('admin/approvals.php');
    }

    $bookId = (int) ($_POST['book_id'] ?? 0);
    if ($bookId <= 0) {
        setFlash('error', 'Invalid book selected.');
        redirect('admin/approvals.php');
    }

    if (isset($_POST['approve_book'])) {
        $stmt = $db->prepare('UPDATE books SET status = ? , book_status = ? WHERE book_id = ?');
        $stmt->execute(['Active', 'available', $bookId]);
        setFlash('success', 'Book approved and made available.');
        redirect('admin/approvals.php');
    }

    if (isset($_POST['delete_book'])) {
        deleteBook($bookId);
        setFlash('success', 'Book submission removed.');
        redirect('admin/approvals.php');
    }
}

$pendingBooks = $db->query(
    "SELECT b.book_id, b.name, b.author, b.course_code, b.price, b.listing_type, s.name AS seller_name, s.email AS seller_email
     FROM books b
     LEFT JOIN sellers s ON b.seller_id = s.seller_id
     WHERE b.status = 'Inactive'
     ORDER BY b.book_id DESC"
)->fetchAll();

$pageTitle = 'Pending Approvals';
?>

<h1 class="h3 mb-4"><?= e($pageTitle) ?></h1>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Course</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Listing</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pendingBooks): ?>
                        <?php foreach ($pendingBooks as $book): ?>
                            <tr>
                                <td><?= (int) $book['book_id'] ?></td>
                                <td><?= e($book['name']) ?></td>
                                <td><?= e($book['course_code']) ?></td>
                                <td><?= e($book['seller_email'] ?? $book['seller_name'] ?? 'Unknown') ?></td>
                                <td>KES <?= number_format((float) $book['price'], 2) ?></td>
                                <td><?= e(ucfirst($book['listing_type'])) ?></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="book_id" value="<?= (int) $book['book_id'] ?>">
                                        <button type="submit" name="approve_book" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this submission?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="book_id" value="<?= (int) $book['book_id'] ?>">
                                        <button type="submit" name="delete_book" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No pending book approvals found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
