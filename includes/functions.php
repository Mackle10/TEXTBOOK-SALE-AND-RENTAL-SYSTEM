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

/* ----------------------------------------------------------------
 * CSRF protection
 * ---------------------------------------------------------------- */
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function validateCsrf(): bool
{
    $token = $_POST['csrf'] ?? $_GET['csrf'] ?? '';
    $valid = isset($_SESSION['csrf']) && hash_equals((string) $_SESSION['csrf'], (string) $token);
    csrfToken(); // regenerate after check
    return $valid;
}

/* ----------------------------------------------------------------
 * QR token lookup (safe for verification links)
 * ---------------------------------------------------------------- */
function getOrCreateQrToken(int $saleId, int $bookId): string
{
    $stmt = getDB()->prepare('SELECT token FROM qr_tokens WHERE sale_id = ? ORDER BY token_id DESC LIMIT 1');
    $stmt->execute([$saleId]);
    $row = $stmt->fetch();
    return $row ? $row['token'] : generateQrToken($saleId, $bookId);
}

/* ----------------------------------------------------------------
 * Reviews & ratings
 * ---------------------------------------------------------------- */
function averageRating(int $bookId): ?float
{
    try {
        $stmt = getDB()->prepare('SELECT AVG(rating) AS avg, COUNT(*) AS cnt FROM reviews WHERE book_id = ?');
        $stmt->execute([$bookId]);
        $row = $stmt->fetch();
        return $row && (int) $row['cnt'] > 0 ? round((float) $row['avg'], 1) : null;
    } catch (PDOException) {
        return null;
    }
}

function getReviews(int $bookId): array
{
    try {
        $stmt = getDB()->prepare(
            'SELECT r.*, u.email, s.first_name, s.last_name
             FROM reviews r
             JOIN users u ON u.user_id = r.user_id
             LEFT JOIN students s ON s.user_id = u.user_id
             WHERE r.book_id = ? ORDER BY r.created_at DESC'
        );
        $stmt->execute([$bookId]);
        return $stmt->fetchAll();
    } catch (PDOException) {
        return [];
    }
}

function addReview(int $bookId, int $userId, int $rating, ?string $comment): bool
{
    $rating = max(1, min(5, (int) $rating));
    $stmt = getDB()->prepare(
        'INSERT INTO reviews (book_id, user_id, rating, comment) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = current_timestamp()'
    );
    return $stmt->execute([$bookId, $userId, $rating, $comment ?: null]);
}

/* ----------------------------------------------------------------
 * Messaging
 * ---------------------------------------------------------------- */
function sendMessage(int $bookId, int $senderId, int $receiverId, string $body): bool
{
    $body = trim($body);
    if ($body === '' || $receiverId === $senderId) {
        return false;
    }
    $stmt = getDB()->prepare(
        'INSERT INTO messages (book_id, sender_id, receiver_id, body) VALUES (?, ?, ?, ?)'
    );
    return $stmt->execute([$bookId, $senderId, $receiverId, $body]);
}

function getUnreadMessageCount(int $userId): int
{
    try {
        $stmt = getDB()->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}

function getInbox(int $userId): array
{
    // one row per conversation (latest message per book+sender)
    $stmt = getDB()->prepare(
        "SELECT m.*, b.name AS book_name,
                u.email AS other_email,
                CONCAT(s.first_name, ' ', s.last_name) AS other_name
         FROM messages m
         JOIN books b ON b.book_id = m.book_id
         JOIN users u ON u.user_id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
         LEFT JOIN students s ON s.user_id = u.user_id
         WHERE m.sender_id = ? OR m.receiver_id = ?
         ORDER BY m.created_at DESC"
    );
    $stmt->execute([$userId, $userId, $userId]);
    return $stmt->fetchAll();
}

/* ----------------------------------------------------------------
 * Delete helpers
 * ---------------------------------------------------------------- */
function deleteBook(int $bookId): void
{
    $book = getBookById($bookId);
    if ($book && !empty($book['cover_image'])) {
        $path = __DIR__ . '/../assets/uploads/' . basename($book['cover_image']);
        if (is_file($path)) {
            @unlink($path);
        }
    }
    getDB()->prepare('DELETE FROM books WHERE book_id = ?')->execute([$bookId]);
}

function deleteUser(int $userId): void
{
    $db = getDB();
    $db->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$userId]);
    $db->prepare('DELETE FROM waitlist WHERE user_id = ?')->execute([$userId]);
    $db->prepare('DELETE FROM students WHERE user_id = ?')->execute([$userId]);
    $db->prepare('DELETE FROM users WHERE user_id = ?')->execute([$userId]);
}

/* ----------------------------------------------------------------
 * Image upload (book covers)
 * ---------------------------------------------------------------- */
function handleImageUpload(array $file): ?string
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    $maxBytes = 2 * 1024 * 1024; // 2MB
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    if ($file['size'] > $maxBytes || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        return false;
    }
    $dir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        return false;
    }
    return $name;
}

/* ----------------------------------------------------------------
 * Pagination helper
 * ---------------------------------------------------------------- */
function paginationMeta(int $total, int $perPage = 12): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);
    return [
        'page'       => $page,
        'perPage'    => $perPage,
        'offset'     => ($page - 1) * $perPage,
        'totalPages' => $totalPages,
    ];
}

function renderPager(string $baseUrl, int $totalPages, int $currentPage): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $qs = $_GET;
    $html = '<nav><ul class="pagination justify-content-center">';
    for ($p = 1; $p <= $totalPages; $p++) {
        $qs['page'] = $p;
        $url = $baseUrl . '?' . http_build_query($qs);
        $active = $p === $currentPage ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($url) . '">' . $p . '</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

/* ----------------------------------------------------------------
 * Email verification
 * ---------------------------------------------------------------- */
function generateVerifyToken(int $userId): string
{
    $token = bin2hex(random_bytes(32));
    getDB()->prepare('UPDATE users SET verify_token = ? WHERE user_id = ?')->execute([$token, $userId]);
    return $token;
}

function verifyEmail(string $token): bool
{
    $db = getDB();
    $stmt = $db->prepare('SELECT user_id FROM users WHERE verify_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }
    $db->prepare("UPDATE users SET email_verified = 1, verify_token = NULL WHERE user_id = ?")->execute([$row['user_id']]);
    return true;
}

/* ----------------------------------------------------------------
 * Password reset
 * ---------------------------------------------------------------- */
function createPasswordReset(string $email): ?string
{
    $stmt = getDB()->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        return null; // do not reveal whether email exists
    }
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $ins = getDB()->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)');
    $ins->execute([$email, $token, $expires]);
    return $token;
}

function validResetToken(string $email, string $token): bool
{
    $stmt = getDB()->prepare(
        'SELECT id FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$email, $token]);
    return (bool) $stmt->fetch();
}

function consumePasswordReset(string $email, string $token, string $newHash): bool
{
    if (!validResetToken($email, $token)) {
        return false;
    }
    $db = getDB();
    $db->prepare('UPDATE users SET password = ? WHERE email = ?')->execute([$newHash, $email]);
    $db->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
    return true;
}
