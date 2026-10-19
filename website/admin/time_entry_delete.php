<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('
    SELECT time_entries.*, staff.name AS staff_name FROM time_entries
    JOIN staff ON staff.id = time_entries.staff_id
    WHERE time_entries.id = ?
');
$stmt->execute([$id]);
$entry = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$entry) {
    http_response_code(404);
    exit('Time entry not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_check($_POST['csrf'] ?? null)) {
        $pdo->prepare('DELETE FROM time_entries WHERE id = ?')->execute([$id]);
    }
    header('Location: time-logs.php');
    exit;
}

$title = 'Delete shift — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel narrow">
  <p class="crumb"><a href="time-logs.php">&larr; Time logs</a></p>
  <h1>Delete this shift?</h1>
  <p class="muted"><?= h($entry['staff_name']) ?> · <?= h($entry['work_date']) ?> · <?= h($entry['clock_in']) ?>–<?= h($entry['clock_out']) ?></p>
  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= $id ?>">
    <button class="btn-danger" type="submit">Yes, delete this shift</button>
  </form>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
