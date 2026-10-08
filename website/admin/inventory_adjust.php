<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM ingredients WHERE id = ?');
$stmt->execute([$id]);
$ing = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ing) {
    http_response_code(404);
    exit('Ingredient not found.');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'That form expired — try again.';
    } else {
        $direction = $_POST['direction'] ?? 'in';
        $amountRaw = trim((string) ($_POST['amount'] ?? ''));
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $amount = (float) $amountRaw;

        if ($amountRaw === '' || $amount <= 0) {
            $error = 'Enter an amount greater than zero.';
        } else {
            $change = $direction === 'out' ? -$amount : $amount;
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO stock_movements (ingredient_id, change_amount, reason) VALUES (?,?,?)')
                ->execute([$id, $change, $reason]);
            $pdo->prepare('UPDATE ingredients SET current_stock = current_stock + ?, updated_at = datetime("now") WHERE id = ?')
                ->execute([$change, $id]);
            $pdo->commit();
            header('Location: inventory.php');
            exit;
        }
    }
}

$moveStmt = $pdo->prepare('SELECT * FROM stock_movements WHERE ingredient_id = ? ORDER BY created_at DESC, id DESC LIMIT 10');
$moveStmt->execute([$id]);
$movements = $moveStmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Adjust stock — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel narrow">
  <p class="crumb"><a href="inventory.php">&larr; Inventory</a></p>
  <h1><?= h($ing['name']) ?></h1>
  <p class="muted">Currently <?= rtrim(rtrim(number_format((float) $ing['current_stock'], 2), '0'), '.') ?> <?= h($ing['unit']) ?> in stock.</p>
  <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>

  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= $id ?>">

    <label>Direction
      <select name="direction">
        <option value="in">Stock in (delivery, correction)</option>
        <option value="out">Stock out (used, wasted, correction)</option>
      </select>
    </label>

    <label>Amount (<?= h($ing['unit']) ?: 'units' ?>)
      <input type="number" step="any" min="0" name="amount" required autofocus>
    </label>

    <label>Reason <span class="hint">optional</span>
      <input type="text" name="reason" placeholder="e.g. delivery from supplier, used in production">
    </label>

    <button class="btn" type="submit">Record adjustment</button>
  </form>

  <?php if (!empty($movements)): ?>
  <h2>Recent movements</h2>
  <table class="data-table">
    <thead><tr><th>When</th><th>Change</th><th>Reason</th></tr></thead>
    <tbody>
      <?php foreach ($movements as $m): ?>
        <tr>
          <td><?= h($m['created_at']) ?></td>
          <td class="<?= $m['change_amount'] < 0 ? 'neg' : 'pos' ?>"><?= ($m['change_amount'] > 0 ? '+' : '') . rtrim(rtrim(number_format((float) $m['change_amount'], 2), '0'), '.') ?> <?= h($ing['unit']) ?></td>
          <td><?= h($m['reason']) ?: '—' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
