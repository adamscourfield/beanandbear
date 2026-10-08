<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM recipes WHERE id = ?');
$stmt->execute([$id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$recipe) {
    http_response_code(404);
    exit('Recipe not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_check($_POST['csrf'] ?? null)) {
        $pdo->prepare('DELETE FROM recipes WHERE id = ?')->execute([$id]);
    }
    header('Location: recipes.php');
    exit;
}

$title = 'Delete recipe — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel narrow">
  <p class="crumb"><a href="recipe.php?id=<?= $id ?>">&larr; <?= h($recipe['name']) ?></a></p>
  <h1>Delete "<?= h($recipe['name']) ?>"?</h1>
  <p class="muted">This removes the recipe and its ingredients and method permanently. It does not touch the photo in assets/img/flavours/.</p>
  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= $id ?>">
    <button class="btn-danger" type="submit">Yes, delete this recipe</button>
  </form>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
