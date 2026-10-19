<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM staff WHERE id = ?');
$stmt->execute([$id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$staff) {
    http_response_code(404);
    exit('Staff member not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_check($_POST['csrf'] ?? null)) {
        $pdo->prepare('DELETE FROM staff WHERE id = ?')->execute([$id]);
    }
    header('Location: time-logs.php');
    exit;
}

$title = 'Delete staff — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel narrow">
  <p class="crumb"><a href="time-logs.php">&larr; Time logs</a></p>
  <h1>Delete "<?= h($staff['name']) ?>"?</h1>
  <p class="muted">This also deletes every shift logged for them.</p>
  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= $id ?>">
    <button class="btn-danger" type="submit">Yes, delete this staff member</button>
  </form>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
