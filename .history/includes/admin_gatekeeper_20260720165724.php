<?php
require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    setFlash('error', 'Administrator access required.');
    redirect('login.php');
}
