<?php
require_once __DIR__ . '/../config/database.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    if (str_starts_with($path, 'http')) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    }
    exit;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = getDB()->prepare('SELECT * FROM users WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        redirect('login.php');
    }
}

function flash(string $key): ?string
{
    if (!empty($_SESSION['flash_' . $key])) {
        $msg = $_SESSION['flash_' . $key];
        unset($_SESSION['flash_' . $key]);
        return $msg;
    }
    return null;
}

function setFlash(string $key, string $message): void
{
    $_SESSION['flash_' . $key] = $message;
}

function isUniversityEmail(string $email): bool
{
    $domain = strtolower(UNIVERSITY_EMAIL_DOMAIN);
    $parts = explode('@', strtolower(trim($email)));
    return count($parts) === 2 && $parts[1] === $domain;
}

function getSellerIdForUser(int $userId): ?int
{
    $user = getDB()->prepare('SELECT email, phone FROM users WHERE user_id = ?');
    $user->execute([$userId]);
    $u = $user->fetch();
    if (!$u) {
        return null;
    }
    $stmt = getDB()->prepare('SELECT seller_id FROM sellers WHERE email = ? LIMIT 1');
    $stmt->execute([$u['email']]);
    $row = $stmt->fetch();
    return $row ? (int) $row['seller_id'] : null;
}

function statusBadge(string $status): string
{
    $map = [
        'available' => 'success',
        'reserved'  => 'warning',
        'sold'      => 'secondary',
        'rented'    => 'info',
        'Active'    => 'success',
        'Inactive'  => 'secondary',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $class . ' status-badge" data-status="' . e($status) . '">' . e(ucfirst($status)) . '</span>';
}

function generateQrToken(int $saleId, int $bookId): string
{
    $token = bin2hex(random_bytes(16));
    $stmt = getDB()->prepare('INSERT INTO qr_tokens (token, sale_id, book_id) VALUES (?, ?, ?)');
    $stmt->execute([$token, $saleId, $bookId]);
    return $token;
}

function qrCodeUrl(string $data): string
{
    return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($data);
}

function getBookById(int $bookId): ?array
{
    $stmt = getDB()->prepare(
        'SELECT b.*, s.name AS seller_name, s.email AS seller_email, s.phone AS seller_phone
         FROM books b
         LEFT JOIN sellers s ON b.seller_id = s.seller_id
         WHERE b.book_id = ?'
    );
    $stmt->execute([$bookId]);
    return $stmt->fetch() ?: null;
}

function updateBookStatus(int $bookId, string $status): void
{
    $stmt = getDB()->prepare('UPDATE books SET book_status = ? WHERE book_id = ?');
    $stmt->execute([$status, $bookId]);
    if ($status === 'available') {
        notifyWaitlist($bookId);
    }
}

function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin_id']);
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        redirect('admin/login.php');
    }
}

function createNotification(int $userId, string $message, ?string $link = null): void
{
    try {
        $stmt = getDB()->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $message, $link]);
    } catch (PDOException) {
        // notifications table may not exist yet
    }
}

function getUnreadNotificationCount(?int $userId = null): int
{
    if ($userId === null && !isLoggedIn()) {
        return 0;
    }
    try {
        $stmt = getDB()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}

function notifyWaitlist(int $bookId): void
{
    $db = getDB();
    $book = getBookById($bookId);
    if (!$book || $book['book_status'] !== 'available') {
        return;
    }

    $stmt = $db->prepare('SELECT user_id FROM waitlist WHERE book_id = ?');
    $stmt->execute([$bookId]);
    foreach ($stmt->fetchAll() as $row) {
        createNotification(
            (int) $row['user_id'],
            '"' . $book['name'] . '" (' . $book['course_code'] . ') is now available!',
            'books/view.php?id=' . $bookId
        );
    }
    $db->prepare('DELETE FROM waitlist WHERE book_id = ?')->execute([$bookId]);
}

function getUserIdBySellerId(int $sellerId): ?int
{
    $stmt = getDB()->prepare(
        'SELECT u.user_id FROM users u JOIN sellers s ON u.email = s.email WHERE s.seller_id = ? LIMIT 1'
    );
    $stmt->execute([$sellerId]);
    $row = $stmt->fetch();
    return $row ? (int) $row['user_id'] : null;
}

function returnRental(int $rentalId, int $requestedByUserId): bool
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT r.*, b.seller_id, b.name AS book_name, b.listing_type
         FROM rentals r
         JOIN books b ON r.book_id = b.book_id
         WHERE r.rental_id = ? AND r.status IN (\'active\', \'overdue\')'
    );
    $stmt->execute([$rentalId]);
    $rental = $stmt->fetch();
    if (!$rental) {
        return false;
    }

    $sellerUserId = $rental['seller_id'] ? getUserIdBySellerId((int) $rental['seller_id']) : null;
    $isBorrower = (int) $rental['borrower_user_id'] === $requestedByUserId;
    $isSeller = $sellerUserId === $requestedByUserId;

    if (!$isBorrower && !$isSeller && !isAdminLoggedIn()) {
        return false;
    }

    $db->beginTransaction();
    try {
        $db->prepare("UPDATE rentals SET status = 'returned' WHERE rental_id = ?")->execute([$rentalId]);
        updateBookStatus((int) $rental['book_id'], 'available');

        createNotification(
            (int) $rental['borrower_user_id'],
            'Rental returned: "' . $rental['book_name'] . '". Thank you!',
            'dashboard.php'
        );
        if ($sellerUserId) {
            createNotification(
                $sellerUserId,
                '"' . $rental['book_name'] . '" has been returned and is listed again.',
                'books/view.php?id=' . $rental['book_id']
            );
        }

        $db->commit();
        return true;
    } catch (Exception) {
        $db->rollBack();
        return false;
    }
}

function processRentalReminders(): void
{
    $db = getDB();
    $threshold = date('Y-m-d', strtotime('+' . RENTAL_REMINDER_DAYS . ' days'));
    $stmt = $db->prepare(
        "SELECT r.*, b.name AS book_name, u.email
         FROM rentals r
         JOIN books b ON r.book_id = b.book_id
         JOIN users u ON r.borrower_user_id = u.user_id
         WHERE r.status = 'active'
           AND r.return_date <= ?
           AND r.reminder_sent = 0"
    );
    $stmt->execute([$threshold]);
    foreach ($stmt->fetchAll() as $rental) {
        $msg = sprintf('Reminder: "%s" is due back on %s.', $rental['book_name'], $rental['return_date']);
        $_SESSION['rental_reminders'][] = $msg;
        createNotification((int) $rental['borrower_user_id'], $msg, 'dashboard.php');
        $upd = $db->prepare('UPDATE rentals SET reminder_sent = 1 WHERE rental_id = ?');
        $upd->execute([$rental['rental_id']]);
    }

    $db->exec("UPDATE rentals SET status = 'overdue' WHERE status = 'active' AND return_date < CURDATE()");
}
