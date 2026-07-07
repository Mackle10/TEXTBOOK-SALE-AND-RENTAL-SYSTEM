<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$pageTitle = 'Register';
$errors = [];
$studentCategory = $_POST['student_category'] ?? 'University';
$program = trim($_POST['program'] ?? '');
$highSchoolLevel = $_POST['high_school_level'] ?? '';
$highSchoolClass = $_POST['high_school_class'] ?? '';
$year = (int) ($_POST['year_of_study'] ?? 1);

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
    $studentCategory = $_POST['student_category'] ?? 'University';
    $program = trim($_POST['program'] ?? '');
    $highSchoolLevel = $_POST['high_school_level'] ?? '';
    $highSchoolClass = $_POST['high_school_class'] ?? '';
    $year = (int) ($_POST['year_of_study'] ?? 1);

    $validStudentCategories = ['University', 'High School'];
    $validHighSchoolLevels = ['Ordinary Level / O-Level', 'Advanced Level / A-Level'];
    $validHighSchoolClasses = ['Senior 1', 'Senior 2', 'Senior 3', 'Senior 4', 'Senior 5', 'Senior 6'];

    if (!in_array($role, ['Buyer', 'Seller'], true)) {
        $errors[] = 'Invalid role selected.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
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
    if (!in_array($studentCategory, $validStudentCategories, true)) {
        $errors[] = 'Please select a valid education level.';
    }

    if ($studentCategory === 'High School') {
        if (!in_array($highSchoolLevel, $validHighSchoolLevels, true)) {
            $errors[] = 'Please select a valid high school level.';
        }
        if (!in_array($highSchoolClass, $validHighSchoolClasses, true)) {
            $errors[] = 'Please select a valid class/form.';
        }
    }

    if (empty($errors)) {
        $db = getDB();
        $check = $db->prepare('SELECT user_id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            if ($studentCategory === 'University') {
                $educationLevel = $program ?: 'Undeclared';
                $yearClass = 'Year ' . max(1, min(5, $year));
                $courseValue = $program ?: 'Undeclared';
                $yearOfStudyValue = max(1, min(5, $year));
            } else {
                $educationLevel = $highSchoolLevel ?: 'Ordinary Level / O-Level';
                $yearClass = $highSchoolClass ?: 'Senior 1';
                $courseValue = 'High School';
                $yearOfStudyValue = null;
            }

            try {
                $db->beginTransaction();

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $db->prepare('INSERT INTO users (role, phone, email, password) VALUES (?, ?, ?, ?)');
                $ins->execute([$role, $phone, $email, $hash]);
                $userId = (int) $db->lastInsertId();

                $stu = $db->prepare(
                    'INSERT INTO students (user_id, course, year_of_study, first_name, last_name, student_category, education_level, year_class)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stu->execute([
                    $userId,
                    $courseValue,
                    $yearOfStudyValue,
                    $firstName,
                    $lastName,
                    $studentCategory,
                    $educationLevel,
                    $yearClass,
                ]);

                if ($role === 'Seller') {
                    $name = $firstName . ' ' . $lastName;
                    $sel = $db->prepare(
                        'INSERT INTO sellers (name, phone, email, sell_price, date_added) VALUES (?, ?, ?, 0, CURDATE())'
                    );
                    $sel->execute([$name, $phone, $email]);
                }

                $db->commit();

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
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required
                       placeholder="you@example.com"
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control" required value="<?= e($_POST['phone'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label d-block">Education Level</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="student-category-university" name="student_category" value="University" <?= (($_POST['student_category'] ?? 'University') === 'University') ? 'checked' : '' ?> >
                    <label class="form-check-label" for="student-category-university">University Student</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="student-category-highschool" name="student_category" value="High School" <?= (($_POST['student_category'] ?? '') === 'High School') ? 'checked' : '' ?> >
                    <label class="form-check-label" for="student-category-highschool">High School Student</label>
                </div>
            </div>
            <div id="university-fields" class="<?= (($_POST['student_category'] ?? 'University') !== 'University') ? 'd-none' : '' ?>">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Course / Program</label>
                        <input type="text" name="program" class="form-control" placeholder="e.g. Computer Science" value="<?= e($_POST['program'] ?? '') ?>">
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
            </div>
            <div id="highschool-fields" class="<?= (($_POST['student_category'] ?? 'University') !== 'High School') ? 'd-none' : '' ?>">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">High School Level</label>
                        <select name="high_school_level" class="form-select">
                            <option value="">Select level</option>
                            <option value="Ordinary Level / O-Level" <?= (($_POST['high_school_level'] ?? '') === 'Ordinary Level / O-Level') ? 'selected' : '' ?>>Ordinary Level / O-Level</option>
                            <option value="Advanced Level / A-Level" <?= (($_POST['high_school_level'] ?? '') === 'Advanced Level / A-Level') ? 'selected' : '' ?>>Advanced Level / A-Level</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Class / Form</label>
                        <select name="high_school_class" class="form-select">
                            <option value="">Select class</option>
                            <?php for ($s = 1; $s <= 6; $s++): ?>
                                <option value="Senior <?= $s ?>" <?= (($_POST['high_school_class'] ?? '') === "Senior $s") ? 'selected' : '' ?>>Senior <?= $s ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
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
        <p class="text-center mt-3 mb-0 small">
            Already registered? <a href="login.php">Login</a>
        </p>
    </div>
</div>

            <button type="submit" class="btn btn-primary w-100">Create Account</button>
        </form>
        <p class="text-center mt-3 mb-0 small">
            Already registered? <a href="login.php">Login</a>
        </p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const universityFields = document.getElementById('university-fields');
    const highschoolFields = document.getElementById('highschool-fields');
    const categoryInputs = document.querySelectorAll('input[name="student_category"]');

    function toggleFields() {
        const selected = document.querySelector('input[name="student_category"]:checked');
        const isUniversity = selected && selected.value === 'University';
        universityFields.classList.toggle('d-none', !isUniversity);
        highschoolFields.classList.toggle('d-none', isUniversity);
    }

    categoryInputs.forEach((input) => input.addEventListener('change', toggleFields));
    toggleFields();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
