<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();
$pageTitle = 'My Profile';
$errors = [];

$stmt = $db->prepare('SELECT * FROM students WHERE user_id = ?');
$stmt->execute([$user['user_id']]);
$profile = $stmt->fetch();

$studentCategory = $_POST['student_category'] ?? ($profile['student_category'] ?? 'University');
$program = trim($_POST['program'] ?? (((string)($profile['student_category'] ?? 'University') === 'University') ? ($profile['education_level'] ?? $profile['course'] ?? '') : ''));
$highSchoolLevel = $_POST['high_school_level'] ?? (((string)($profile['student_category'] ?? '') === 'High School') ? ($profile['education_level'] ?? '') : '');
$highSchoolClass = $_POST['high_school_class'] ?? (((string)($profile['student_category'] ?? '') === 'High School') ? ($profile['year_class'] ?? '') : '');
$year = (int) ($_POST['year_of_study'] ?? ($profile['year_of_study'] ?? 1));
$newPassword = $_POST['new_password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $errors[] = 'Invalid or expired form submission. Please try again.';
    }
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $studentCategory = $_POST['student_category'] ?? 'University';
    $program = trim($_POST['program'] ?? '');
    $highSchoolLevel = $_POST['high_school_level'] ?? '';
    $highSchoolClass = $_POST['high_school_class'] ?? '';
    $year = (int) ($_POST['year_of_study'] ?? 1);
    $newPassword = $_POST['new_password'] ?? '';

    if ($firstName === '' || $lastName === '') {
        $errors[] = 'Name is required.';
    }

    if (empty($errors)) {
        $db->prepare('UPDATE users SET phone = ? WHERE user_id = ?')->execute([$phone, $user['user_id']]);

        if ($studentCategory === 'University') {
            $educationLevel = $program ?: 'Undeclared';
            $yearClass = 'Year ' . max(1, min(5, $year));
            $yearOfStudyValue = max(1, min(5, $year));
        } else {
            $educationLevel = $highSchoolLevel ?: 'Ordinary Level / O-Level';
            $yearClass = $highSchoolClass ?: 'Senior 1';
            $yearOfStudyValue = null;
        }

        $db->prepare(
            'UPDATE students SET first_name = ?, last_name = ?, course = ?, year_of_study = ?, student_category = ?, education_level = ?, year_class = ? WHERE user_id = ?'
        )->execute([$firstName, $lastName, $studentCategory === 'University' ? ($program ?: 'Undeclared') : 'High School', $yearOfStudyValue, $studentCategory, $educationLevel, $yearClass, $user['user_id']]);

        if ($user['role'] === 'Seller') {
            $db->prepare('UPDATE sellers SET phone = ?, name = ? WHERE email = ?')
               ->execute([$phone, "$firstName $lastName", $user['email']]);
        }

        if ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                $errors[] = 'Password must be at least 6 characters.';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $db->prepare('UPDATE users SET password = ? WHERE user_id = ?')->execute([$hash, $user['user_id']]);
            }
        }

        if (empty($errors)) {
            setFlash('success', 'Profile updated successfully.');
            redirect('profile.php');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4">My Profile</h1>

<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if (empty($user['email_verified'])): ?>
    <?php $vtoken = generateVerifyToken((int) $user['user_id']); ?>
    <div class="alert alert-warning">
        Your email is not verified. <a href="<?= e(APP_URL . '/verify_email.php?token=' . urlencode($vtoken)) ?>">Verify now</a>.
    </div>
<?php endif; ?>

<div class="card auth-card mx-0" style="max-width: 560px;">
    <div class="card-body p-4">
        <form method="post">
            <?= csrfField() ?>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" required value="<?= e($_POST['first_name'] ?? $profile['first_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" required value="<?= e($_POST['last_name'] ?? $profile['last_name'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-3 mt-2">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control" required value="<?= e($_POST['phone'] ?? $user['phone']) ?>">
            </div>
            <div class="mb-3 mt-2">
                <label class="form-label d-block">Education Level</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="profile-category-university" name="student_category" value="University" <?= ($studentCategory === 'University') ? 'checked' : '' ?> >
                    <label class="form-check-label" for="profile-category-university">University Student</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="profile-category-highschool" name="student_category" value="High School" <?= ($studentCategory === 'High School') ? 'checked' : '' ?> >
                    <label class="form-check-label" for="profile-category-highschool">High School Student</label>
                </div>
            </div>
            <div id="profile-university-fields" class="<?= ($studentCategory !== 'University') ? 'd-none' : '' ?>">
                <div class="row g-2">
                    <div class="col-md-8">
                        <label class="form-label">Course / Program</label>
                        <input type="text" name="program" class="form-control" value="<?= e($program) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Year</label>
                        <select name="year_of_study" class="form-select">
                            <?php for ($y = 1; $y <= 5; $y++): ?>
                                <option value="<?= $y ?>" <?= ($year === $y) ? 'selected' : '' ?>>Year <?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div id="profile-highschool-fields" class="<?= ($studentCategory !== 'High School') ? 'd-none' : '' ?>">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">High School Level</label>
                        <select name="high_school_level" class="form-select">
                            <option value="">Select level</option>
                            <option value="Ordinary Level / O-Level" <?= ($highSchoolLevel === 'Ordinary Level / O-Level') ? 'selected' : '' ?>>Ordinary Level / O-Level</option>
                            <option value="Advanced Level / A-Level" <?= ($highSchoolLevel === 'Advanced Level / A-Level') ? 'selected' : '' ?>>Advanced Level / A-Level</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Class / Form</label>
                        <select name="high_school_class" class="form-select">
                            <option value="">Select class</option>
                            <?php for ($s = 1; $s <= 6; $s++): ?>
                                <option value="Senior <?= $s ?>" <?= ($highSchoolClass === "Senior $s") ? 'selected' : '' ?>>Senior <?= $s ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="mb-3 mt-2">
                <label class="form-label">New Password <span class="text-muted">(leave blank to keep current)</span></label>
                <input type="password" name="new_password" class="form-control" minlength="6">
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const universityFields = document.getElementById('profile-university-fields');
    const highschoolFields = document.getElementById('profile-highschool-fields');
    const categoryInputs = document.querySelectorAll('input[name="student_category"]');

    function toggleProfileFields() {
        const selected = document.querySelector('input[name="student_category"]:checked');
        const isUniversity = selected && selected.value === 'University';
        universityFields.classList.toggle('d-none', !isUniversity);
        highschoolFields.classList.toggle('d-none', isUniversity);
    }

    categoryInputs.forEach(function (input) {
        input.addEventListener('change', toggleProfileFields);
    });
    toggleProfileFields();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
