<?php
declare(strict_types=1);

$title = $title ?? 'Bean and Bear — Back of House';
?>
<!doctype html>
<html lang="en-GB">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($title) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<header class="bar">
  <a class="brand" href="<?= current_user() ? 'dashboard.php' : 'login.php' ?>">Bean &amp; Bear <span>Back of House</span></a>
  <?php if (current_user()): ?>
    <form method="post" action="logout.php" class="logout">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <span class="who"><?= htmlspecialchars(current_user()['username']) ?></span>
      <button type="submit">Sign out</button>
    </form>
  <?php endif; ?>
</header>
