<?php
require_once __DIR__ . '/../includes/admin_gatekeeper.php';

$pageTitle = 'Admin Dashboard';
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
        <a class="navbar-brand" href="admin_dashboard.php"><i class="bi bi-shield-lock-fill me-2"></i>Admin Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="admin/books.php">Manage Books</a></li>
                <li class="nav-item"><a class="nav-link" href="#pending">Approvals</a></li>
                <li class="nav-item"><a class="nav-link" href="#rentals">Rentals</a></li>
                <li class="nav-item"><a class="nav-link" href="#transactions">Transactions</a></li>
                <li class="nav-item"><a class="nav-link text-warning" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><?= e($pageTitle) ?></h1>
            <p class="text-muted mb-0">Use this panel to review new textbook submissions, track rentals, and audit sales activity.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-secondary">Signed in as: <?= e($_SESSION['user_role']) ?></span>
        </div>
    </div>

    <div class="row gy-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Pending Book Approvals</h5>
                    <p class="card-text text-muted">Review student submissions before they go live to ensure quality and correct pricing.</p>
                    <p class="display-6 mb-0">12</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Active Rentals</h5>
                    <p class="card-text text-muted">Monitor books currently out on rent, upcoming returns, and overdue items.</p>
                    <p class="display-6 mb-0">34</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Transaction Logs</h5>
                    <p class="card-text text-muted">Keep an eye on recent sales and rental payments made through the platform.</p>
                    <p class="display-6 mb-0">27</p>
                </div>
            </div>
        </div>
    </div>

    <section id="pending" class="mb-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Pending Book Approvals</h2>
                <p class="small text-muted mb-0">Approve or reject textbook listings submitted by students.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Submission ID</th>
                            <th>Title</th>
                            <th>Course</th>
                            <th>Submitted By</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#1024</td>
                            <td>Intro to Economics</td>
                            <td>ECON 101</td>
                            <td>student1@example.com</td>
                            <td>2026-07-18</td>
                            <td><span class="badge bg-warning">Pending</span></td>
                        </tr>
                        <tr>
                            <td>#1025</td>
                            <td>Organic Chemistry</td>
                            <td>CHEM 102</td>
                            <td>student2@example.com</td>
                            <td>2026-07-19</td>
                            <td><span class="badge bg-warning">Pending</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="rentals" class="mb-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Rental Tracking</h2>
                <p class="small text-muted mb-0">See active rentals, overdue returns, and current return statuses.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Rental ID</th>
                            <th>Book</th>
                            <th>Borrower</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#881</td>
                            <td>Calculus Made Easy</td>
                            <td>student3@example.com</td>
                            <td>2026-07-22</td>
                            <td><span class="badge bg-info">Active</span></td>
                        </tr>
                        <tr>
                            <td>#877</td>
                            <td>History of Art</td>
                            <td>student4@example.com</td>
                            <td>2026-07-12</td>
                            <td><span class="badge bg-danger">Overdue</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="transactions">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Transaction Logs</h2>
                <p class="small text-muted mb-0">Audit recent sales and rental payment activity across the site.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tx ID</th>
                            <th>Type</th>
                            <th>Book</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#5021</td>
                            <td>Sale</td>
                            <td>Programming in PHP</td>
                            <td>student5@example.com</td>
                            <td>KES 1,200.00</td>
                            <td>2026-07-20</td>
                        </tr>
                        <tr>
                            <td>#5020</td>
                            <td>Rental</td>
                            <td>Advanced Algebra</td>
                            <td>student6@example.com</td>
                            <td>KES 450.00</td>
                            <td>2026-07-19</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
<footer class="bg-white border-top py-3">
    <div class="container text-center text-muted small">
        &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> — Admin panel
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
