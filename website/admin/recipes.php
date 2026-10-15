<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$q = trim((string) ($_GET['q'] ?? ''));

if ($q !== '') {
    $stmt = $pdo->prepare('SELECT * FROM recipes WHERE name LIKE ? ORDER BY sort_order');
    $stmt->execute(['%' . $q . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM recipes ORDER BY sort_order');
}
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Recipes — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel wide">
  <p class="crumb"><a href="dashboard.php">&larr; Dashboard</a></p>
  <div class="head-row">
    <div>
      <h1>Recipes</h1>
      <p class="muted"><?= count($recipes) ?> flavour<?= count($recipes) === 1 ? '' : 's' ?><?= $q !== '' ? ' matching "' . h($q) . '"' : '' ?></p>
    </div>
    <a class="btn" href="recipe_edit.php">+ Add recipe</a>
  </div>

  <form method="get" class="search">
    <input type="search" name="q" placeholder="Search recipes…" value="<?= h($q) ?>">
    <button class="btn-outline" type="submit">Search</button>
    <?php if ($q !== ''): ?><a class="clear" href="recipes.php">Clear</a><?php endif; ?>
  </form>

  <?php if (empty($recipes)): ?>
    <div class="empty">No recipes found.</div>
  <?php else: ?>
  <div class="recipe-grid">
    <?php foreach ($recipes as $r): ?>
      <a class="recipe-card" href="recipe.php?id=<?= (int) $r['id'] ?>">
        <div class="recipe-photo">
          <img src="../assets/img/flavours/<?= h($r['flavour_key']) ?>.jpg" alt=""
               onerror="this.closest('.recipe-photo').classList.add('noimg');this.remove()">
        </div>
        <div class="recipe-card-body">
          <div class="recipe-name"><?= h($r['name']) ?></div>
          <div class="recipe-tagline"><?= h($r['tagline']) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
