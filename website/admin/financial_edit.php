<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$week = [
    'week_start' => date('Y-m-d', strtotime('monday this week')),
    'consumer_sales' => '',
    'direct_cogs' => '',
    'other_costs' => '',
    'notes' => '',
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM financial_weeks WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$found) {
        http_response_code(404);
        exit('Week not found.');
    }
    $week = $found;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'That form expired — try again.';
    } else {
        $weekStart = trim((string) ($_POST['week_start'] ?? ''));
        $consumerSales = (float) ($_POST['consumer_sales'] ?? 0);
        $directCogs = (float) ($_POST['direct_cogs'] ?? 0);
        $otherCosts = (float) ($_POST['other_costs'] ?? 0);
        $notes = trim((string) ($_POST['notes'] ?? ''));

        $week = compact('weekStart', 'consumerSales', 'directCogs', 'otherCosts', 'notes');
        $week['week_start'] = $weekStart;
        $week['consumer_sales'] = $consumerSales;
        $week['direct_cogs'] = $directCogs;
        $week['other_costs'] = $otherCosts;

        if ($weekStart === '') {
            $error = 'Week start date is required.';
        } else {
            try {
                if ($id) {
                    $pdo->prepare('UPDATE financial_weeks SET week_start=?, consumer_sales=?, direct_cogs=?, other_costs=?, notes=? WHERE id=?')
                        ->execute([$weekStart, $consumerSales, $directCogs, $otherCosts, $notes, $id]);
                } else {
                    $pdo->prepare('INSERT INTO financial_weeks (week_start, consumer_sales, direct_cogs, other_costs, notes) VALUES (?,?,?,?,?)')
                        ->execute([$weekStart, $consumerSales, $directCogs, $otherCosts, $notes]);
                }
                header('Location: financials.php');
                exit;
            } catch (PDOException $e) {
                $error = str_contains($e->getMessage(), 'UNIQUE')
                    ? 'A week starting on that date is already recorded.'
                    : 'Could not save this week.';
            }
        }
    }
}

$title = ($id ? 'Edit' : 'Add') . ' week — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel narrow">
  <p class="crumb"><a href="financials.php">&larr; Financials</a></p>
  <h1><?= $id ? 'Edit week' : 'Add a week' ?></h1>
  <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>

  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

    <label>Week starting
      <input type="date" name="week_start" required value="<?= h($week['week_start']) ?>">
    </label>

    <label>Consumer sales <span class="hint">total takings, VAT-inclusive</span>
      <input type="number" step="any" min="0" name="consumer_sales" required value="<?= h((string) $week['consumer_sales']) ?>">
    </label>

    <label>Direct COGS <span class="hint">ingredients and packaging for the week — the plan assumes 25% of net sales</span>
      <input type="number" step="any" min="0" name="direct_cogs" required value="<?= h((string) $week['direct_cogs']) ?>">
    </label>

    <label>Other costs <span class="hint">anything one-off this week, beyond the usual fixed costs</span>
      <input type="number" step="any" min="0" name="other_costs" value="<?= h((string) $week['other_costs']) ?>">
    </label>

    <label>Notes <span class="hint">optional</span>
      <textarea name="notes" rows="3"><?= h($week['notes']) ?></textarea>
    </label>

    <button class="btn" type="submit">Save week</button>
  </form>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
