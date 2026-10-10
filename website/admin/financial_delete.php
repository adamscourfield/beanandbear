<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM financial_weeks WHERE id = ?');
$stmt->execute([$id]);
$week = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$week) {
    http_response_code(404);
    exit('Week not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_check($_POST['csrf'] ?? null)) {
        $pdo->prepare('DELETE FROM financial_weeks WHERE id = ?')->execute([$id]);
    }
    header('Location: financials.php');
    exit;
}

$title = 'Delete week — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel narrow">
  <p class="crumb"><a href="financials.php">&larr; Financials</a></p>
  <h1>Delete the week of <?= h($week['week_start']) ?>?</h1>
  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= $id ?>">
    <button class="btn-danger" type="submit">Yes, delete this week</button>
  </form>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
