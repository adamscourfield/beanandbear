<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$entry = [
    'staff_id' => '',
    'work_date' => date('Y-m-d'),
    'clock_in' => '09:00',
    'clock_out' => '17:00',
    'break_minutes' => '0',
    'notes' => '',
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM time_entries WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$found) {
        http_response_code(404);
        exit('Time entry not found.');
    }
    $entry = $found;
}

$staffList = $pdo->query('SELECT id, name FROM staff ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$error = null;

if (empty($staffList)) {
    $error = 'Add a staff member before logging time.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'That form expired — try again.';
    } else {
        $staffId = (int) ($_POST['staff_id'] ?? 0);
        $workDate = trim((string) ($_POST['work_date'] ?? ''));
        $clockIn = trim((string) ($_POST['clock_in'] ?? ''));
        $clockOut = trim((string) ($_POST['clock_out'] ?? ''));
        $breakMinutes = (int) ($_POST['break_minutes'] ?? 0);
        $notes = trim((string) ($_POST['notes'] ?? ''));

        $entry = compact('staffId', 'workDate', 'clockIn', 'clockOut', 'breakMinutes', 'notes');
        $entry['staff_id'] = $staffId;
        $entry['work_date'] = $workDate;
        $entry['clock_in'] = $clockIn;
        $entry['clock_out'] = $clockOut;
        $entry['break_minutes'] = $breakMinutes;

        if (!$staffId || $workDate === '' || $clockIn === '' || $clockOut === '') {
            $error = 'Staff, date, clock in and clock out are all required.';
        } else {
            if ($id) {
                $pdo->prepare('UPDATE time_entries SET staff_id=?, work_date=?, clock_in=?, clock_out=?, break_minutes=?, notes=? WHERE id=?')
                    ->execute([$staffId, $workDate, $clockIn, $clockOut, $breakMinutes, $notes, $id]);
            } else {
                $pdo->prepare('INSERT INTO time_entries (staff_id, work_date, clock_in, clock_out, break_minutes, notes) VALUES (?,?,?,?,?,?)')
                    ->execute([$staffId, $workDate, $clockIn, $clockOut, $breakMinutes, $notes]);
            }
            header('Location: time-logs.php');
            exit;
        }
    }
}

$title = ($id ? 'Edit' : 'Log') . ' time — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel narrow">
  <p class="crumb"><a href="time-logs.php">&larr; Time logs</a></p>
  <h1><?= $id ? 'Edit shift' : 'Log a shift' ?></h1>
  <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>

  <?php if (!empty($staffList)): ?>
  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

    <label>Staff
      <select name="staff_id" required>
        <option value="">Select…</option>
        <?php foreach ($staffList as $s): ?>
          <option value="<?= (int) $s['id'] ?>" <?= (int) $entry['staff_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= h($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Date
      <input type="date" name="work_date" required value="<?= h($entry['work_date']) ?>">
    </label>

    <label>Clock in
      <input type="time" name="clock_in" required value="<?= h($entry['clock_in']) ?>">
    </label>

    <label>Clock out
      <input type="time" name="clock_out" required value="<?= h($entry['clock_out']) ?>">
    </label>

    <label>Break (minutes)
      <input type="number" min="0" name="break_minutes" value="<?= h((string) $entry['break_minutes']) ?>">
    </label>

    <label>Notes <span class="hint">optional</span>
      <input type="text" name="notes" value="<?= h($entry['notes']) ?>">
    </label>

    <button class="btn" type="submit">Save shift</button>
  </form>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
