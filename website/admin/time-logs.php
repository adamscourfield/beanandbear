<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require_login();

$title = 'Time logs — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel wide">
  <p class="crumb"><a href="dashboard.php">&larr; Dashboard</a></p>
  <h1>Time logs</h1>
  <p class="muted">Staff shifts and hours worked in the shop will be tracked here.</p>
  <div class="empty">Nothing logged yet.</div>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
