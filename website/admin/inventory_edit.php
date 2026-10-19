<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$ing = ['name' => '', 'unit' => '', 'reorder_level' => '', 'supplier' => '', 'cost_per_unit' => '', 'current_stock' => '0'];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM ingredients WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$found) {
        http_response_code(404);
        exit('Ingredient not found.');
    }
    $ing = $found;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'That form expired — try again.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $unit = trim((string) ($_POST['unit'] ?? ''));
        $supplier = trim((string) ($_POST['supplier'] ?? ''));
        $reorderRaw = trim((string) ($_POST['reorder_level'] ?? ''));
        $costRaw = trim((string) ($_POST['cost_per_unit'] ?? ''));
        $startingStockRaw = trim((string) ($_POST['current_stock'] ?? '0'));

        $ing = compact('name', 'unit', 'supplier') + [
            'reorder_level' => $reorderRaw,
            'cost_per_unit' => $costRaw,
            'current_stock' => $startingStockRaw,
        ];

        if ($name === '') {
            $error = 'Name is required.';
        } else {
            $reorder = $reorderRaw === '' ? null : (float) $reorderRaw;
            $cost = $costRaw === '' ? null : (float) $costRaw;

            try {
                if ($id) {
                    $upd = $pdo->prepare('UPDATE ingredients SET name=?, unit=?, reorder_level=?, supplier=?, cost_per_unit=?, updated_at=datetime("now") WHERE id=?');
                    $upd->execute([$name, $unit, $reorder, $supplier, $cost, $id]);
                    $rid = $id;
                } else {
                    $startingStock = (float) $startingStockRaw;
                    $ins = $pdo->prepare('INSERT INTO ingredients (name, unit, current_stock, reorder_level, supplier, cost_per_unit) VALUES (?,?,?,?,?,?)');
                    $ins->execute([$name, $unit, $startingStock, $reorder, $supplier, $cost]);
                    $rid = (int) $pdo->lastInsertId();
                    if ($startingStock != 0) {
                        $pdo->prepare('INSERT INTO stock_movements (ingredient_id, change_amount, reason) VALUES (?,?,?)')
                            ->execute([$rid, $startingStock, 'Starting stock']);
                    }
                }
                header('Location: inventory.php');
                exit;
            } catch (PDOException $e) {
                $error = str_contains($e->getMessage(), 'UNIQUE')
                    ? 'An ingredient with that name already exists.'
                    : 'Could not save the ingredient.';
            }
        }
    }
}

$title = ($id ? 'Edit' : 'Add') . ' ingredient — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel narrow">
  <p class="crumb"><a href="inventory.php">&larr; Inventory</a></p>
  <h1><?= $id ? 'Edit ingredient' : 'Add an ingredient' ?></h1>
  <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>

  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

    <label>Name
      <input type="text" name="name" required value="<?= h($ing['name']) ?>">
    </label>

    <label>Unit <span class="hint">e.g. g, ml, each</span>
      <input type="text" name="unit" value="<?= h($ing['unit']) ?>">
    </label>

    <?php if (!$id): ?>
    <label>Starting stock
      <input type="number" step="any" name="current_stock" value="<?= h((string) $ing['current_stock']) ?>">
    </label>
    <?php endif; ?>

    <label>Reorder level <span class="hint">flagged as low once stock falls to or below this</span>
      <input type="number" step="any" name="reorder_level" value="<?= h((string) ($ing['reorder_level'] ?? '')) ?>">
    </label>

    <label>Supplier
      <input type="text" name="supplier" value="<?= h($ing['supplier']) ?>">
    </label>

    <label>Cost per unit
      <input type="number" step="any" name="cost_per_unit" value="<?= h((string) ($ing['cost_per_unit'] ?? '')) ?>">
    </label>

    <button class="btn" type="submit">Save ingredient</button>
  </form>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
