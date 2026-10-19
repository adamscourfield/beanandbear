<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

$pdo = db();
$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

if ($userCount > 0) {
    http_response_code(403);
    $title = 'Setup already complete';
    require __DIR__ . '/includes/layout_top.php';
    ?>
    <main class="panel narrow">
      <h1>Setup already complete</h1>
      <p class="muted">An admin account already exists, so this page has locked itself. To add another account for now, insert a row into the <code>users</code> table directly (password hashed with <code>password_hash()</code>); a proper "add staff" screen can replace this later.</p>
      <p><a class="btn" href="login.php">Go to sign in</a></p>
    </main>
    <?php
    require __DIR__ . '/includes/layout_bottom.php';
    exit;
}

// A setup key gates this page: only someone with server filesystem access can
// read it, so a stranger who finds this URL before the real owner visits it
// cannot create the admin account themselves. data/ is denied over HTTP by
// its own .htaccess, so this is never fetchable through the web server.
$tokenPath = __DIR__ . '/data/setup_token.txt';
if (!is_dir(dirname($tokenPath))) {
    mkdir(dirname($tokenPath), 0700, true);
}
if (!file_exists($tokenPath)) {
    file_put_contents($tokenPath, bin2hex(random_bytes(16)));
    chmod($tokenPath, 0600);
}
$setupToken = trim((string) file_get_contents($tokenPath));

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'That form expired — try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');
        $setupKey = trim((string) ($_POST['setup_key'] ?? ''));

        if (!hash_equals($setupToken, $setupKey)) {
            $error = 'Incorrect setup key.';
        } elseif ($username === '' || $password === '') {
            $error = 'Enter a username and password.';
        } elseif (strlen($password) < 10) {
            $error = 'Password must be at least 10 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            header('Location: login.php?created=1');
            exit;
        }
    }
}

$title = 'Set up the first account';
require __DIR__ . '/includes/layout_top.php';
?>
<main class="panel narrow">
  <h1>Create the first admin account</h1>
  <p class="muted">This page only works once — it locks itself as soon as one account exists.</p>
  <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <p class="muted">Needs the setup key from <code>data/setup_token.txt</code> on the server (SSH or your host's file manager) — this page is otherwise open to whoever gets to it first.</p>
  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <label>Setup key<input type="text" name="setup_key" required autofocus autocomplete="off"></label>
    <label>Username<input type="text" name="username" required autocomplete="username"></label>
    <label>Password<input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
    <label>Confirm password<input type="password" name="confirm" required minlength="10" autocomplete="new-password"></label>
    <button class="btn" type="submit">Create account</button>
  </form>
</main>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
