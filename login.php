<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect_to('/dashboard.php');
}

$error = '';
$identity = '';
$next = $_GET['next'] ?? ($_POST['next'] ?? 'dashboard.php');
$allowedNext = ['dashboard.php', 'submit.php', 'editor.php'];
if (!is_string($next) || !in_array($next, $allowedNext, true)) {
    $next = 'dashboard.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identity = trim(is_string($_POST['identity'] ?? null) ? $_POST['identity'] : '');
    $password = $_POST['password'] ?? '';
    if ($identity === '' || !is_string($password)) {
        $error = 'Enter your username or email address and password.';
    } else {
        $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$identity, strtolower($identity)]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($password, $row['password_hash'])) {
            // Same error for unknown username and bad password.
            $error = 'The login details were not recognised. Check them and try again.';
        } else {
            if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
                $update = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $update->execute([password_hash($password, PASSWORD_DEFAULT), (int)$row['id']]);
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$row['id'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            redirect_to('/' . $next);
        }
    }
}

page_start('Author login', 'Log in to submit manuscripts and track your existing submissions.');
?>
<div style="max-width:600px;margin:0 auto">
  <div class="card">
    <?php if ($error !== ''): ?><div class="notice" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/login.php" autocomplete="on">
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <label for="identity">Username or email *</label>
      <input id="identity" name="identity" required autocomplete="username" value="<?= e($identity) ?>">
      <label for="password">Password *</label>
      <input id="password" name="password" type="password" required autocomplete="current-password">
      <p><button class="cta navy" type="submit">Log in</button></p>
    </form>
    <p>New author? <a href="/register.php">Create an account</a>.</p>
  </div>
</div>
<?php page_end(); ?>
