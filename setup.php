<?php
/**
 * One-time database setup — open in browser after starting MySQL in XAMPP.
 * http://localhost/Academics%20and%20textbooks/setup.php
 */
require_once __DIR__ . '/config/config.php';

$messages = [];
$errors = [];

function runSqlFile(PDO $pdo, string $path, string $label): void
{
    global $messages, $errors;
    if (!file_exists($path)) {
        $errors[] = "Missing file: $path";
        return;
    }
    $sql = file_get_contents($path);
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if ($stmt === '' || stripos($stmt, 'SET ') === 0 || stripos($stmt, 'START ') === 0 || stripos($stmt, 'COMMIT') === 0) {
            continue;
        }
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'already exists')) {
                continue;
            }
            $errors[] = "$label: " . $e->getMessage();
        }
    }
    $messages[] = "$label imported.";
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    $pdo->exec('USE `' . DB_NAME . '`');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        runSqlFile($pdo, __DIR__ . '/textbooks.sql', 'textbooks.sql');
        runSqlFile($pdo, __DIR__ . '/schema_extensions.sql', 'schema_extensions.sql');
        if (!empty($_POST['seed'])) {
            runSqlFile($pdo, __DIR__ . '/seed_data.sql', 'seed_data.sql');
        }
    }
} catch (PDOException $e) {
    $errors[] = 'Database connection failed: ' . $e->getMessage() . ' — Make sure MySQL is running in XAMPP.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup | <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 600px;">
    <h1 class="h3 mb-4">Database Setup</h1>
    <?php foreach ($messages as $m): ?>
        <div class="alert alert-success"><?= htmlspecialchars($m) ?></div>
    <?php endforeach; ?>
    <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
    <?php if (empty($errors) || str_contains($errors[0] ?? '', 'Duplicate')): ?>
        <div class="card p-4">
            <p>This will create the <code>textbooks</code> database and import your schema.</p>
            <form method="post">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="seed" value="1" id="seed" checked>
                    <label class="form-check-label" for="seed">Include sample data (mark@university.ac.ke / password)</label>
                </div>
                <button type="submit" class="btn btn-primary">Run Setup</button>
                <a href="index.php" class="btn btn-outline-secondary">Go to App</a>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
