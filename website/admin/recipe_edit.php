<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$recipe = ['flavour_key' => '', 'name' => '', 'tagline' => '', 'story' => '', 'brief' => ''];
$ingredientsText = '';
$stepsText = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM recipes WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$found) {
        http_response_code(404);
        exit('Recipe not found.');
    }
    $recipe = $found;

    $ingStmt = $pdo->prepare('SELECT group_name AS "group", qty, item FROM recipe_ingredients WHERE recipe_id = ? ORDER BY sort_order');
    $ingStmt->execute([$id]);
    $ingredientsText = ingredients_to_text($ingStmt->fetchAll(PDO::FETCH_ASSOC));

    $stepStmt = $pdo->prepare('SELECT instruction FROM recipe_steps WHERE recipe_id = ? ORDER BY step_number');
    $stepStmt->execute([$id]);
    $stepsText = steps_to_text($stepStmt->fetchAll(PDO::FETCH_COLUMN));
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'That form expired — try again.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $tagline = trim((string) ($_POST['tagline'] ?? ''));
        $story = trim((string) ($_POST['story'] ?? ''));
        $brief = trim((string) ($_POST['brief'] ?? ''));
        $flavourKey = trim((string) ($_POST['flavour_key'] ?? ''));
        $ingredientsText = (string) ($_POST['ingredients'] ?? '');
        $stepsText = (string) ($_POST['steps'] ?? '');

        if ($flavourKey === '' && $name !== '') {
            $flavourKey = slugify($name);
        }

        $recipe = compact('flavourKey', 'name', 'tagline', 'story', 'brief');
        $recipe['flavour_key'] = $flavourKey;

        if ($name === '' || $flavourKey === '') {
            $error = 'Name is required.';
        } else {
            $ingredientRows = parse_ingredients_text($ingredientsText);
            $stepRows = parse_steps_text($stepsText);

            $pdo->beginTransaction();
            try {
                if ($id) {
                    $upd = $pdo->prepare('UPDATE recipes SET flavour_key=?, name=?, tagline=?, story=?, brief=?, updated_at=datetime("now") WHERE id=?');
                    $upd->execute([$flavourKey, $name, $tagline, $story, $brief, $id]);
                    $pdo->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = ?')->execute([$id]);
                    $pdo->prepare('DELETE FROM recipe_steps WHERE recipe_id = ?')->execute([$id]);
                    $rid = $id;
                } else {
                    $maxOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM recipes')->fetchColumn();
                    $ins = $pdo->prepare('INSERT INTO recipes (flavour_key, sort_order, name, tagline, story, brief) VALUES (?,?,?,?,?,?)');
                    $ins->execute([$flavourKey, $maxOrder + 1, $name, $tagline, $story, $brief]);
                    $rid = (int) $pdo->lastInsertId();
                }

                $insI = $pdo->prepare('INSERT INTO recipe_ingredients (recipe_id, group_name, qty, item, sort_order) VALUES (?,?,?,?,?)');
                foreach ($ingredientRows as $idx => $row) {
                    $insI->execute([$rid, $row['group'], $row['qty'], $row['item'], $idx]);
                }
                $insS = $pdo->prepare('INSERT INTO recipe_steps (recipe_id, step_number, instruction) VALUES (?,?,?)');
                foreach ($stepRows as $idx => $step) {
                    $insS->execute([$rid, $idx + 1, $step]);
                }

                $pdo->commit();
                header('Location: recipe.php?id=' . $rid);
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = str_contains($e->getMessage(), 'UNIQUE')
                    ? 'That flavour key is already used by another recipe.'
                    : 'Could not save the recipe.';
            }
        }
    }
}

$title = ($id ? 'Edit' : 'Add') . ' recipe — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel wide">
  <p class="crumb"><a href="<?= $id ? 'recipe.php?id=' . $id : 'recipes.php' ?>">&larr; <?= $id ? h($recipe['name']) : 'Recipes' ?></a></p>
  <h1><?= $id ? 'Edit recipe' : 'Add a recipe' ?></h1>
  <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>

  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

    <label>Name
      <input type="text" name="name" required value="<?= h($recipe['name']) ?>">
    </label>

    <label>Flavour key <span class="hint">matches the image filename in assets/img/flavours/ — leave blank to generate from the name</span>
      <input type="text" name="flavour_key" value="<?= h($recipe['flavour_key']) ?>" placeholder="e.g. dark-chocolate">
    </label>

    <label>Tagline
      <input type="text" name="tagline" value="<?= h($recipe['tagline']) ?>">
    </label>

    <label>Story
      <textarea name="story" rows="4"><?= h($recipe['story']) ?></textarea>
    </label>

    <label>The point of this flavour
      <textarea name="brief" rows="2"><?= h($recipe['brief']) ?></textarea>
    </label>

    <label>Ingredients
      <span class="hint">One per line: <code>qty | ingredient</code>. Start a line with <code>## </code> to begin a named group (e.g. for a recipe with a base and a swirl).</span>
      <textarea name="ingredients" rows="12" class="mono"><?= h($ingredientsText) ?></textarea>
    </label>

    <label>Method
      <span class="hint">One step per line, in order.</span>
      <textarea name="steps" rows="8" class="mono"><?= h($stepsText) ?></textarea>
    </label>

    <button class="btn" type="submit">Save recipe</button>
  </form>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
