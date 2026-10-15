<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check($_POST['csrf'] ?? null)) {
    $_SESSION = [];
    session_destroy();
}

header('Location: login.php');
exit;
