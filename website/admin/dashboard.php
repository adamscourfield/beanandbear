<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
$user = require_login();

$pdo = db();
$recipeCount = (int) $pdo->query('SELECT COUNT(*) FROM recipes')->fetchColumn();
$ingredientCount = (int) $pdo->query('SELECT COUNT(*) FROM ingredients')->fetchColumn();
$lowStockCount = (int) $pdo->query('SELECT COUNT(*) FROM ingredients WHERE reorder_level IS NOT NULL AND current_stock <= reorder_level')->fetchColumn();
$staffCount = (int) $pdo->query('SELECT COUNT(*) FROM staff WHERE active = 1')->fetchColumn();
$weekCount = (int) $pdo->query('SELECT COUNT(*) FROM financial_weeks')->fetchColumn();

$title = 'Dashboard — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel wide">
  <h1>Welcome, <?= h($user['username']) ?></h1>
  <p class="muted">Pick a section below.</p>
  <nav class="tiles">
    <a class="tile" href="recipes.php">
      <span class="tile-label">Recipes</span>
      <span class="tile-sub"><?= $recipeCount ?> flavour<?= $recipeCount === 1 ? '' : 's' ?>, with stories and method</span>
    </a>
    <a class="tile" href="time-logs.php">
      <span class="tile-label">Time logs</span>
      <span class="tile-sub"><?= $staffCount ?> active staff member<?= $staffCount === 1 ? '' : 's' ?></span>
    </a>
    <a class="tile" href="inventory.php">
      <span class="tile-label">Inventory</span>
      <span class="tile-sub"><?= $ingredientCount ?> ingredient<?= $ingredientCount === 1 ? '' : 's' ?><?= $lowStockCount ? ', ' . $lowStockCount . ' low' : '' ?></span>
    </a>
    <a class="tile" href="financials.php">
      <span class="tile-label">Financials</span>
      <span class="tile-sub"><?= $weekCount ?> week<?= $weekCount === 1 ? '' : 's' ?> recorded</span>
    </a>
  </nav>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
