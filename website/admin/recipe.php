<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM recipes WHERE id = ?');
$stmt->execute([$id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$recipe) {
    http_response_code(404);
    $title = 'Recipe not found';
    require __DIR__ . '/includes/layout_top.php';
    echo '<main class="panel narrow"><h1>Recipe not found</h1><p><a class="btn" href="recipes.php">Back to recipes</a></p></main>';
    require __DIR__ . '/includes/layout_bottom.php';
    exit;
}

$ingStmt = $pdo->prepare('SELECT * FROM recipe_ingredients WHERE recipe_id = ? ORDER BY sort_order');
$ingStmt->execute([$id]);
$ingredients = $ingStmt->fetchAll(PDO::FETCH_ASSOC);

$stepStmt = $pdo->prepare('SELECT * FROM recipe_steps WHERE recipe_id = ? ORDER BY step_number');
$stepStmt->execute([$id]);
$steps = $stepStmt->fetchAll(PDO::FETCH_ASSOC);

$groups = [];
foreach ($ingredients as $ing) {
    $g = $ing['group_name'] ?? '';
    $groups[$g][] = $ing;
}

$title = h($recipe['name']) . ' — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel wide recipe-detail">
  <p class="crumb"><a href="recipes.php">&larr; Recipes</a></p>
  <div class="recipe-hero">
    <img src="../assets/img/flavours/<?= h($recipe['flavour_key']) ?>.jpg" alt=""
         onerror="this.remove()">
  </div>

  <div class="head-row">
    <div>
      <h1><?= h($recipe['name']) ?></h1>
      <p class="tagline-text"><?= h($recipe['tagline']) ?></p>
    </div>
    <div class="actions">
      <a class="btn-outline" href="recipe_edit.php?id=<?= (int) $recipe['id'] ?>">Edit</a>
      <a class="btn-danger" href="recipe_delete.php?id=<?= (int) $recipe['id'] ?>">Delete</a>
    </div>
  </div>

  <?php if ($recipe['story']): ?><p class="story"><?= nl2br(h($recipe['story'])) ?></p><?php endif; ?>

  <?php if ($recipe['brief']): ?>
  <blockquote class="brief">
    <span class="brief-label">The point of this flavour</span>
    <?= h($recipe['brief']) ?>
  </blockquote>
  <?php endif; ?>

  <?php if (!empty($ingredients)): ?>
  <h2>Ingredients</h2>
  <?php foreach ($groups as $g => $rows): ?>
    <?php if ($g !== ''): ?><h3 class="group-label"><?= h($g) ?></h3><?php endif; ?>
    <div class="ing-grid">
      <?php foreach ($rows as $ing): ?>
        <div class="ing-row">
          <span class="ing-qty"><?= h($ing['qty']) ?></span>
          <span class="ing-item"><?= h($ing['item']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php if (!empty($steps)): ?>
  <h2>Method</h2>
  <ol class="method-list">
    <?php foreach ($steps as $s): ?>
      <li><?= h($s['instruction']) ?></li>
    <?php endforeach; ?>
  </ol>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
