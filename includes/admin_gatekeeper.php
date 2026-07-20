<?php
require_once __DIR__ . '/functions.php';

if (!isAdmin()) {
    setFlash('error', 'Administrator access required.');
    redirect('login.php');
}

