<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$pdo = db();
$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($userCount === 0) {
    header('Location: setup.php');
    exit;
}

$error = null;
$created = isset($_GET['created']);
$LOCKOUT_THRESHOLD = 5;
$LOCKOUT_SECONDS = 300;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'That form expired — try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $locked = $user && $user['locked_until'] && strtotime($user['locked_until']) > time();

        if ($locked) {
            $error = 'Too many failed attempts. Try again in a few minutes.';
        } elseif ($user && password_verify($password, $user['password_hash'])) {
            $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?')
                ->execute([$user['id']]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username']];
            header('Location: dashboard.php');
            exit;
        } else {
            if ($user) {
                $attempts = $user['failed_attempts'] + 1;
                $lockUntil = $attempts >= $LOCKOUT_THRESHOLD ? date('Y-m-d H:i:s', time() + $LOCKOUT_SECONDS) : null;
                $pdo->prepare('UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?')
                    ->execute([$attempts, $lockUntil, $user['id']]);
            }
            $error = 'Incorrect username or password.';
        }
    }
}

$title = 'Sign in — Bean and Bear Back of House';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel narrow">
  <h1>Staff sign in</h1>
  <?php if ($created): ?><p class="notice">Account created — sign in below.</p><?php endif; ?>
  <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <label>Username<input type="text" name="username" required autofocus autocomplete="username"></label>
    <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn" type="submit">Sign in</button>
  </form>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
