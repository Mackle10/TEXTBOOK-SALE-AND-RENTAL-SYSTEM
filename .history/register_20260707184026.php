<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$pageTitle = 'Register';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $errors[] = 'Invalid or expired form submission. Please try again.';
    }
    $role = $_POST['role'] ?? 'Buyer';
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year = (int) ($_POST['year_of_study'] ?? 1);

    if (!in_array($role, ['Buyer', 'Seller'], true)) {
        $errors[] = 'Invalid role selected.';
    }
    if (!isUniversityEmail($email)) {
        $errors[] = 'Registration requires a valid @' . UNIVERSITY_EMAIL_DOMAIN . ' email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if ($firstName === '' || $lastName === '') {
        $errors[] = 'First and last name are required.';
    }

    if (empty($errors)) {
        $db = getDB();
        $check = $db->prepare('SELECT user_id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            try {
                $db->beginTransaction();

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $db->prepare('INSERT INTO users (role, phone, email, password) VALUES (?, ?, ?, ?)');
                $ins->execute([$role, $phone, $email, $hash]);
                $userId = (int) $db->lastInsertId();

                $stu = $db->prepare(
                    'INSERT INTO students (user_id, course, year_of_study, first_name, last_name) VALUES (?, ?, ?, ?, ?)'
                );
                $stu->execute([$userId, $course ?: 'Undeclared', max(1, min(5, $year)), $firstName, $lastName]);

                if ($role === 'Seller') {
                    $name = $firstName . ' ' . $lastName;
                    $sel = $db->prepare(
                        'INSERT INTO sellers (name, phone, email, sell_price, date_added) VALUES (?, ?, ?, 0, CURDATE())'
                    );
                    $sel->execute([$name, $phone, $email]);
                }

                $db->commit();

                // Generate email verification token (dev: link shown on login screen)
                $token = generateVerifyToken($userId);
                $_SESSION['verify_notice'] = APP_URL . '/verify_email.php?token=' . urlencode($token);

                setFlash('success', 'Account created! Please verify your email before logging in.');
                redirect('login.php');
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card auth-card">
    <div class="card-body p-4">
        <h1 class="h3 mb-1 text-center">Register</h1>
        <p class="text-muted text-center mb-4">Join the campus textbook marketplace</p>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger py-2"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="post" novalidate>
            <?= csrfField() ?>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" required value="<?= e($_POST['first_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" required value="<?= e($_POST['last_name'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-3 mt-2">
                <label class="form-label">University Email</label>
                <input type="email" name="email" class="form-control" required
                       placeholder="you@<?= e(UNIVERSITY_EMAIL_DOMAIN) ?>"
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control" required value="<?= e($_POST['phone'] ?? '') ?>">
            </div>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">Course / Program</label>
                    <input type="text" name="course" class="form-control" placeholder="e.g. Computer Science" value="<?= e($_POST['course'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Year of Study</label>
                    <select name="year_of_study" class="form-select">
                        <?php for ($y = 1; $y <= 5; $y++): ?>
                            <option value="<?= $y ?>" <?= (($_POST['year_of_study'] ?? 1) == $y) ? 'selected' : '' ?>>Year <?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3 mt-2">
                <label class="form-label">I want to</label>
                <select name="role" class="form-select">
                    <option value="Buyer" <?= (($_POST['role'] ?? '') === 'Buyer') ? 'selected' : '' ?>>Buy / Rent textbooks</option>
                    <option value="Seller" <?= (($_POST['role'] ?? '') === 'Seller') ? 'selected' : '' ?>>Sell / Rent out my books</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <input id="reg-password" type="password" name="password" class="form-control" required minlength="6">
                    <button class="btn btn-outline-secondary btn-toggle-password" data-target="reg-password" type="button" aria-label="Show password"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                    <input id="reg-confirm-password" type="password" name="confirm_password" class="form-control" required>
                    <button class="btn btn-outline-secondary btn-toggle-password" data-target="reg-confirm-password" type="button" aria-label="Show password"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Create Account</button>
        </form>
        <p class="text-center mt-3 mb-0 small">
            Already registered? <a href="login.php">Login</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
