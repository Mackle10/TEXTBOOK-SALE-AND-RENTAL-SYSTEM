<?php
require_once __DIR__ . '/functions.php';
requireAdmin();

$pageTitle = $pageTitle ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="<?= APP_URL ?>/admin/index.php"><i class="bi bi-shield-lock"></i> Admin Panel</a>
        <div class="navbar-nav flex-row gap-3">
            <a class="nav-link text-white-50" href="<?= APP_URL ?>/admin/index.php">Dashboard</a>
            <a class="nav-link text-white-50" href="<?= APP_URL ?>/admin/books.php">Books</a>
            <a class="nav-link text-white-50" href="<?= APP_URL ?>/admin/users.php">Users</a>
            <a class="nav-link text-white-50" href="<?= APP_URL ?>/admin/sales.php">Sales</a>
            <a class="nav-link text-white-50" href="<?= APP_URL ?>/admin/logout.php">Logout (<?= e($_SESSION['admin_name'] ?? 'Admin') ?>)</a>
        </div>
    </div>
</nav>
<main class="container py-4">
