<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$bookId = (int) ($_GET['book_id'] ?? 0);
$type = $_GET['type'] ?? 'sale';
$book = getBookById($bookId);

if (!$book || $book['book_status'] !== 'available') {
    setFlash('error', 'This book is not available.');
    redirect('books/view.php?id=' . $bookId);
}

if ($type === 'rent' && !in_array($book['listing_type'], ['rent', 'both'], true)) {
    setFlash('error', 'This book is not available for rent.');
    redirect('books/view.php?id=' . $bookId);
}
if ($type === 'sale' && !in_array($book['listing_type'], ['sale', 'both'], true)) {
    setFlash('error', 'This book is not available for sale.');
    redirect('books/view.php?id=' . $bookId);
}

$user = currentUser();
$student = getDB()->prepare('SELECT first_name, last_name FROM students WHERE user_id = ?');
$student->execute([$user['user_id']]);
$profile = $student->fetch();
$customerName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));

$pageTitle = $type === 'rent' ? 'Rent Book' : 'Purchase Book';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $errors[] = 'Invalid or expired form submission. Please try again.';
    }
    $paymentMethod = $_POST['payment_method'] ?? '';
    $location = trim($_POST['location'] ?? 'Campus');
    $referenceNo = trim($_POST['reference_no'] ?? '');
    $rentalDays = (int) ($_POST['rental_days'] ?? 0);

    $validMethods = ['Cash', 'Mobile Money', 'Debit Card'];
    if (!in_array($paymentMethod, $validMethods, true)) {
        $errors[] = 'Please select a valid payment method.';
    }

    if ($type === 'rent') {
        if ($rentalDays < 1 || $rentalDays > 90) {
            $errors[] = 'Rental duration must be between 1 and 90 days.';
        }
        $amount = $rentalDays * (float) $book['rental_price_per_day'];
    } else {
        $amount = (float) $book['price'];
        $rentalDays = 0;
    }

    if (empty($errors)) {
        $db = getDB();
        try {
            $db->beginTransaction();

            updateBookStatus($bookId, 'reserved');

            $sale = $db->prepare(
                'INSERT INTO sales (customer_name, product_name, quantity, price, payment_method, location, sale_date)
                 VALUES (?, ?, ?, ?, ?, ?, CURDATE())'
            );
            $sale->execute([
                $customerName,
                $book['name'] . ($type === 'rent' ? " (Rental {$rentalDays} days)" : ''),
                max(1, $rentalDays ?: 1),
                $amount,
                $paymentMethod,
                $location,
            ]);
            $saleId = (int) $db->lastInsertId();

            $pay = $db->prepare(
                'INSERT INTO payments (sale_id, amount_paid, payment_method, reference_no, payment_status)
                 VALUES (?, ?, ?, ?, \'Paid\')'
            );
            $pay->execute([$saleId, $amount, $paymentMethod, $referenceNo ?: null]);

            $finalStatus = $type === 'rent' ? 'rented' : 'sold';
            updateBookStatus($bookId, $finalStatus);

            if ($type === 'rent') {
                $startDate = date('Y-m-d');
                $returnDate = date('Y-m-d', strtotime("+{$rentalDays} days"));
                $rent = $db->prepare(
                    'INSERT INTO rentals (book_id, sale_id, borrower_user_id, rental_days, start_date, return_date, status)
                     VALUES (?, ?, ?, ?, ?, ?, \'active\')'
                );
                $rent->execute([$bookId, $saleId, $user['user_id'], $rentalDays, $startDate, $returnDate]);
            }

            $token = generateQrToken($saleId, $bookId);

            if (!empty($book['seller_id'])) {
                $sellerUserId = getUserIdBySellerId((int) $book['seller_id']);
                if ($sellerUserId) {
                    createNotification(
                        $sellerUserId,
                        ($type === 'rent' ? 'Rental' : 'Sale') . ': "' . $book['name'] . '" — KES ' . number_format($amount, 2),
                        'books/view.php?id=' . $bookId
                    );
                }
            }

            $db->commit();

            $_SESSION['last_transaction'] = [
                'sale_id'    => $saleId,
                'book_id'    => $bookId,
                'token'      => $token,
                'type'       => $type,
                'amount'     => $amount,
                'return_date'=> $type === 'rent' ? date('Y-m-d', strtotime("+{$rentalDays} days")) : null,
            ];

            setFlash('success', ucfirst($type) . ' completed successfully!');
            redirect('receipt.php');
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Transaction failed. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4"><?= $type === 'rent' ? 'Rent' : 'Purchase' ?>: <?= e($book['name']) ?></h1>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card p-4">
            <form method="post">
                <?= csrfField() ?>
                <?php if ($type === 'rent'): ?>
                    <div class="mb-3">
                        <label class="form-label">Rental Duration (days)</label>
                        <input type="number" name="rental_days" class="form-control" min="1" max="90" required
                               value="<?= e($_POST['rental_days'] ?? '7') ?>" id="rentalDays">
                        <div class="form-text">Rate: KES <?= number_format($book['rental_price_per_day'], 2) ?>/day</div>
                    </div>
                    <div class="rental-timer mb-3" id="rentalPreview">
                        <strong>Return date:</strong> <span id="returnDate">—</span>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="">Select method</option>
                        <option value="Cash">Cash</option>
                        <option value="Mobile Money">Mobile Money</option>
                        <option value="Debit Card">Debit Card</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reference No. (optional)</label>
                    <input type="text" name="reference_no" class="form-control" placeholder="M-Pesa code, receipt #">
                </div>
                <div class="mb-3">
                    <label class="form-label">Pickup / Meet Location</label>
                    <input type="text" name="location" class="form-control" value="Main Campus Library" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg">
                    Confirm <?= $type === 'rent' ? 'Rental' : 'Purchase' ?>
                </button>
                <a href="books/view.php?id=<?= $bookId ?>" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card p-4">
            <h2 class="h6">Order Summary</h2>
            <p class="mb-1"><?= e($book['name']) ?></p>
            <p class="small text-muted"><?= e($book['course_code']) ?> · <?= e($book['author']) ?></p>
            <hr>
            <?php if ($type === 'rent'): ?>
                <p class="mb-0">Estimated total updates based on rental days selected.</p>
            <?php else: ?>
                <p class="h4 text-primary mb-0">KES <?= number_format($book['price'], 2) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($type === 'rent'): ?>
<script>
(function () {
    const daysInput = document.getElementById('rentalDays');
    const returnEl = document.getElementById('returnDate');
    function updateReturn() {
        const days = parseInt(daysInput.value, 10) || 0;
        if (days > 0) {
            const d = new Date();
            d.setDate(d.getDate() + days);
            returnEl.textContent = d.toISOString().split('T')[0];
        } else {
            returnEl.textContent = '—';
        }
    }
    daysInput.addEventListener('input', updateReturn);
    updateReturn();
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
