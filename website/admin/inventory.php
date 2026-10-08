<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$q = trim((string) ($_GET['q'] ?? ''));
$lowOnly = isset($_GET['low']);

$sql = 'SELECT * FROM ingredients WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND name LIKE ?';
    $params[] = '%' . $q . '%';
}
if ($lowOnly) {
    $sql .= ' AND reorder_level IS NOT NULL AND current_stock <= reorder_level';
}
$sql .= ' ORDER BY name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$lowCount = (int) $pdo->query('SELECT COUNT(*) FROM ingredients WHERE reorder_level IS NOT NULL AND current_stock <= reorder_level')->fetchColumn();

$title = 'Inventory — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel wide">
  <p class="crumb"><a href="dashboard.php">&larr; Dashboard</a></p>
  <div class="head-row">
    <div>
      <h1>Inventory</h1>
      <p class="muted"><?= count($ingredients) ?> ingredient<?= count($ingredients) === 1 ? '' : 's' ?><?= $lowCount ? ', ' . $lowCount . ' at or below reorder level' : '' ?></p>
    </div>
    <a class="btn" href="inventory_edit.php">+ Add ingredient</a>
  </div>

  <form method="get" class="search">
    <input type="search" name="q" placeholder="Search ingredients…" value="<?= h($q) ?>">
    <label class="low-toggle"><input type="checkbox" name="low" value="1" <?= $lowOnly ? 'checked' : '' ?> onchange="this.form.submit()"> Low stock only</label>
    <button class="btn-outline" type="submit">Search</button>
    <?php if ($q !== '' || $lowOnly): ?><a class="clear" href="inventory.php">Clear</a><?php endif; ?>
  </form>

  <?php if (empty($ingredients)): ?>
    <div class="empty">No ingredients found.</div>
  <?php else: ?>
  <table class="data-table">
    <thead>
      <tr><th>Ingredient</th><th>Stock</th><th>Reorder at</th><th>Supplier</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($ingredients as $ing): ?>
        <?php $low = $ing['reorder_level'] !== null && (float) $ing['current_stock'] <= (float) $ing['reorder_level']; ?>
        <tr class="<?= $low ? 'low-row' : '' ?>">
          <td><?= h($ing['name']) ?></td>
          <td><?= rtrim(rtrim(number_format((float) $ing['current_stock'], 2), '0'), '.') ?> <?= h($ing['unit']) ?><?= $low ? ' <span class="flag">low</span>' : '' ?></td>
          <td><?= $ing['reorder_level'] !== null ? rtrim(rtrim(number_format((float) $ing['reorder_level'], 2), '0'), '.') . ' ' . h($ing['unit']) : '—' ?></td>
          <td><?= h($ing['supplier']) ?: '—' ?></td>
          <td class="row-actions">
            <a href="inventory_adjust.php?id=<?= (int) $ing['id'] ?>">Adjust</a>
            <a href="inventory_edit.php?id=<?= (int) $ing['id'] ?>">Edit</a>
            <a href="inventory_delete.php?id=<?= (int) $ing['id'] ?>">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
