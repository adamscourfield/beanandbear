<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
$user = require_login();

$title = 'Dashboard — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel wide">
  <h1>Welcome, <?= htmlspecialchars($user['username']) ?></h1>
  <p class="muted">Pick a section below. Each one is an empty placeholder for now — the real lists and forms get built out next.</p>
  <nav class="tiles">
    <a class="tile" href="recipes.php">
      <span class="tile-label">Recipes</span>
      <span class="tile-sub">Flavour recipes and batch notes</span>
    </a>
    <a class="tile" href="time-logs.php">
      <span class="tile-label">Time logs</span>
      <span class="tile-sub">Staff shifts and hours</span>
    </a>
    <a class="tile" href="inventory.php">
      <span class="tile-label">Inventory</span>
      <span class="tile-sub">Ingredients in stock</span>
    </a>
    <a class="tile" href="financials.php">
      <span class="tile-label">Financials</span>
      <span class="tile-sub">Weekly takings and costs</span>
    </a>
  </nav>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
