<?php
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? APP_NAME;
$bodyClass = $bodyClass ?? '';
$notifCount = isLoggedIn() ? getUnreadNotificationCount() : 0;
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
<body class="<?= e($bodyClass) ?>">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= APP_URL ?>/index.php">
            <i class="bi bi-book-half me-1"></i><?= e(APP_NAME) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/books/index.php">Browse Books</a></li>
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/transactions.php">Transactions</a></li>
                    <?php if (currentUser()['role'] === 'Seller'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/books/upload.php">Upload Book</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/verify.php">QR Verify</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav align-items-lg-center">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= APP_URL ?>/notifications.php" title="Notifications">
                            <i class="bi bi-bell fs-5"></i>
                            <?php if ($notifCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notif-badge" id="notifCount">
                                    <?= $notifCount > 9 ? '9+' : $notifCount ?>
                                </span>
                            <?php else: ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notif-badge d-none" id="notifCount">0</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= e(explode('@', currentUser()['email'])[0]) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text small text-muted"><?= e(currentUser()['email']) ?></span></li>
                            <li><span class="dropdown-item-text"><span class="badge bg-primary"><?= e(currentUser()['role']) ?></span></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/notifications.php">Notifications</a></li>
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/register.php">Register</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50" href="<?= APP_URL ?>/admin/login.php">Admin</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
