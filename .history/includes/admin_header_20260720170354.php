<?php
require_once __DIR__ . '/functions.php';

if (!isAdmin()) {
    setFlash('error', 'Administrator access required.');
    redirect('login.php');
}

$pageTitle = $pageTitle ?? APP_NAME . ' Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="<?= APP_URL ?>/admin/admin_dashboard.php">
            <i class="bi bi-shield-lock-fill me-2"></i>Admin Panel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/admin_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/approvals.php">Approvals</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/books.php">Manage Books</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/books/index.php">Public Site</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50 small"><?= e(currentUser()['email']) ?></span>
                <a class="btn btn-outline-warning btn-sm" href="<?= APP_URL ?>/logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>
<main class="container py-5">
<?php if ($msg = flash('success')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= e($msg) ?></div>
<?php endif; ?>
