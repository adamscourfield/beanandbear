<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

const VAT_RATE = 0.20;
const CARD_FEE_RATE = 0.013; // plan assumption, central case
const WEEKLY_THRESHOLD = 92000 / 52; // £22k debt service + £70k owner-income objective, per the business plan

$pdo = db();
$weeks = $pdo->query('SELECT * FROM financial_weeks ORDER BY week_start DESC')->fetchAll(PDO::FETCH_ASSOC);

function week_figures(array $w): array
{
    $sales = (float) $w['consumer_sales'];
    $vat = $sales / 6; // VAT-inclusive price at 20%: VAT = price / 6
    $net = $sales - $vat;
    $cogs = (float) $w['direct_cogs'];
    $cardFees = $sales * CARD_FEE_RATE;
    $other = (float) $w['other_costs'];
    $surplus = $net - $cogs - $cardFees - $other;
    return compact('sales', 'vat', 'net', 'cogs', 'cardFees', 'other', 'surplus');
}

$totalSurplus = 0;
foreach ($weeks as $w) {
    $totalSurplus += week_figures($w)['surplus'];
}
$avgSurplus = count($weeks) ? $totalSurplus / count($weeks) : 0;

$title = 'Financials — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel wide">
  <p class="crumb"><a href="dashboard.php">&larr; Dashboard</a></p>
  <div class="head-row">
    <div>
      <h1>Financials</h1>
      <p class="muted">Weekly takings and costs, measured against the business plan's own £92,000/year threshold (the £22k illustrative debt service plus the £70k owner-income objective — about <?= money(WEEKLY_THRESHOLD) ?>/week).</p>
    </div>
    <a class="btn" href="financial_edit.php">+ Add week</a>
  </div>

  <?php if (!empty($weeks)): ?>
  <div class="stat-row">
    <div class="stat"><span class="stat-n"><?= count($weeks) ?></span><span class="stat-l">weeks logged</span></div>
    <div class="stat"><span class="stat-n"><?= money($avgSurplus) ?></span><span class="stat-l">average weekly surplus</span></div>
    <div class="stat"><span class="stat-n <?= $totalSurplus - WEEKLY_THRESHOLD * count($weeks) >= 0 ? 'pos' : 'neg' ?>"><?= money($totalSurplus - WEEKLY_THRESHOLD * count($weeks)) ?></span><span class="stat-l">cumulative vs threshold</span></div>
  </div>
  <?php endif; ?>

  <?php if (empty($weeks)): ?>
    <div class="empty">No weeks recorded yet.</div>
  <?php else: ?>
  <table class="data-table">
    <thead>
      <tr><th>Week of</th><th>Consumer sales</th><th>Net sales</th><th>Direct COGS</th><th>COGS %</th><th>Card fees (est.)</th><th>Other costs</th><th>Operating surplus</th><th>vs threshold</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($weeks as $w): $f = week_figures($w); ?>
        <tr>
          <td><?= h($w['week_start']) ?></td>
          <td><?= money($f['sales']) ?></td>
          <td><?= money($f['net']) ?></td>
          <td><?= money($f['cogs']) ?></td>
          <td><?= $f['net'] > 0 ? number_format($f['cogs'] / $f['net'] * 100, 1) . '%' : '—' ?></td>
          <td><?= money($f['cardFees']) ?></td>
          <td><?= money($f['other']) ?></td>
          <td class="<?= $f['surplus'] >= 0 ? 'pos' : 'neg' ?>"><?= money($f['surplus']) ?></td>
          <td class="<?= $f['surplus'] - WEEKLY_THRESHOLD >= 0 ? 'pos' : 'neg' ?>"><?= money($f['surplus'] - WEEKLY_THRESHOLD) ?></td>
          <td class="row-actions">
            <a href="financial_edit.php?id=<?= (int) $w['id'] ?>">Edit</a>
            <a href="financial_delete.php?id=<?= (int) $w['id'] ?>">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
