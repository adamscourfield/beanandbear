<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require_login();

$title = 'Recipes — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel wide">
  <p class="crumb"><a href="dashboard.php">&larr; Dashboard</a></p>
  <h1>Recipes</h1>
  <p class="muted">Flavour recipes and batch notes will live here — base mixes, ratios, allergens, the lot.</p>
  <div class="empty">Nothing added yet.</div>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
