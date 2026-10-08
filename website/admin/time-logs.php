<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();

$staff = $pdo->query('SELECT * FROM staff ORDER BY active DESC, name')->fetchAll(PDO::FETCH_ASSOC);

$entries = $pdo->query('
    SELECT time_entries.*, staff.name AS staff_name
    FROM time_entries
    JOIN staff ON staff.id = time_entries.staff_id
    ORDER BY work_date DESC, clock_in DESC
    LIMIT 25
')->fetchAll(PDO::FETCH_ASSOC);

function entry_hours(array $e): float
{
    $in = strtotime($e['work_date'] . ' ' . $e['clock_in']);
    $out = strtotime($e['work_date'] . ' ' . $e['clock_out']);
    if ($out < $in) {
        $out += 86400; // shift past midnight
    }
    $minutes = ($out - $in) / 60 - (int) $e['break_minutes'];
    return max(0, $minutes) / 60;
}

$title = 'Time logs — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel wide">
  <p class="crumb"><a href="dashboard.php">&larr; Dashboard</a></p>
  <h1>Time logs</h1>
  <p class="muted">Staff shifts and hours worked in the shop.</p>

  <div class="head-row">
    <h2>Staff</h2>
    <a class="btn-outline" href="staff_edit.php">+ Add staff</a>
  </div>
  <?php if (empty($staff)): ?>
    <div class="empty">No staff added yet.</div>
  <?php else: ?>
  <table class="data-table">
    <thead><tr><th>Name</th><th>Role</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($staff as $s): ?>
        <tr>
          <td><?= h($s['name']) ?></td>
          <td><?= h($s['role']) ?: '—' ?></td>
          <td><?= $s['active'] ? 'Active' : 'Inactive' ?></td>
          <td class="row-actions">
            <a href="staff_edit.php?id=<?= (int) $s['id'] ?>">Edit</a>
            <a href="staff_delete.php?id=<?= (int) $s['id'] ?>">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <div class="head-row">
    <h2>Recent shifts</h2>
    <a class="btn-outline" href="time_entry_edit.php">+ Log time</a>
  </div>
  <?php if (empty($entries)): ?>
    <div class="empty">No shifts logged yet.</div>
  <?php else: ?>
  <table class="data-table">
    <thead><tr><th>Date</th><th>Staff</th><th>In</th><th>Out</th><th>Break</th><th>Hours</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($entries as $e): ?>
        <tr>
          <td><?= h($e['work_date']) ?></td>
          <td><?= h($e['staff_name']) ?></td>
          <td><?= h($e['clock_in']) ?></td>
          <td><?= h($e['clock_out']) ?></td>
          <td><?= (int) $e['break_minutes'] ?> min</td>
          <td><?= number_format(entry_hours($e), 2) ?></td>
          <td class="row-actions">
            <a href="time_entry_edit.php?id=<?= (int) $e['id'] ?>">Edit</a>
            <a href="time_entry_delete.php?id=<?= (int) $e['id'] ?>">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
