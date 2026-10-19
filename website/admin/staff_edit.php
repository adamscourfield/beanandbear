<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$staff = ['name' => '', 'role' => '', 'active' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM staff WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$found) {
        http_response_code(404);
        exit('Staff member not found.');
    }
    $staff = $found;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'That form expired — try again.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? ''));
        $active = isset($_POST['active']) ? 1 : 0;
        $staff = compact('name', 'role', 'active');

        if ($name === '') {
            $error = 'Name is required.';
        } else {
            if ($id) {
                $pdo->prepare('UPDATE staff SET name=?, role=?, active=? WHERE id=?')->execute([$name, $role, $active, $id]);
            } else {
                $pdo->prepare('INSERT INTO staff (name, role, active) VALUES (?,?,?)')->execute([$name, $role, $active]);
            }
            header('Location: time-logs.php');
            exit;
        }
    }
}

$title = ($id ? 'Edit' : 'Add') . ' staff — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel narrow">
  <p class="crumb"><a href="time-logs.php">&larr; Time logs</a></p>
  <h1><?= $id ? 'Edit staff member' : 'Add a staff member' ?></h1>
  <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>

  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <label>Name
      <input type="text" name="name" required value="<?= h($staff['name']) ?>">
    </label>
    <label>Role <span class="hint">optional</span>
      <input type="text" name="role" value="<?= h($staff['role']) ?>" placeholder="e.g. scooper, supervisor">
    </label>
    <label class="checkbox-label">
      <input type="checkbox" name="active" <?= $staff['active'] ? 'checked' : '' ?>> Currently active
    </label>
    <button class="btn" type="submit">Save</button>
  </form>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
