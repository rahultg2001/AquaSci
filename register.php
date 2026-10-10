<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect_to('/dashboard.php');
}

$errors = [];
$values = ['username' => '', 'email' => '', 'given_name' => '', 'family_name' => '', 'institution' => '', 'orcid' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($values as $key => $_) {
        $value = $_POST[$key] ?? '';
        $values[$key] = is_string($value) ? trim($value) : '';
    }
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $values['username'])) {
        $errors[] = 'Username must be 3–32 characters and may contain letters, numbers, dots, underscores or hyphens.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($values['given_name'] === '' || $values['family_name'] === '') {
        $errors[] = 'Enter your given name and family name.';
    }
    if (!is_string($password) || strlen($password) < 12) {
        $errors[] = 'Use a password of at least 12 characters.';
    }
    if (!is_string($confirm) || !is_string($password) || !hash_equals($password, $confirm)) {
        $errors[] = 'The password confirmation does not match.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO users (username,email,password_hash,given_name,family_name,institution,orcid,role) VALUES (?,?,?,?,?,?,?,\'author\')');
            $stmt->execute([
                $values['username'],
                strtolower($values['email']),
                password_hash($password, PASSWORD_DEFAULT),
                $values['given_name'],
                $values['family_name'],
                $values['institution'] ?: null,
                $values['orcid'] ?: null,
            ]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)db()->lastInsertId();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            redirect_to('/dashboard.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'That username or email address is already registered. Try logging in instead.';
            } else {
                throw $exception;
            }
        }
    }
}

page_start('Create an author account', 'Register once, then use your username or email and password to log in again.');
?>
<div style="max-width:760px;margin:0 auto">
  <div class="card">
    <?php if ($errors): ?>
      <div class="notice" role="alert"><strong>Please correct the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" action="/register.php" autocomplete="on">
      <?= csrf_field() ?>
      <div class="form-row">
        <div><label for="username">Username *</label><input id="username" name="username" required minlength="3" maxlength="32" pattern="[A-Za-z0-9_.-]+" value="<?= e($values['username']) ?>" autocomplete="username"></div>
        <div><label for="email">Email address *</label><input id="email" name="email" type="email" required maxlength="190" value="<?= e($values['email']) ?>" autocomplete="email"></div>
      </div>
      <div class="form-row">
        <div><label for="given_name">Given name *</label><input id="given_name" name="given_name" required maxlength="100" value="<?= e($values['given_name']) ?>" autocomplete="given-name"></div>
        <div><label for="family_name">Family name *</label><input id="family_name" name="family_name" required maxlength="100" value="<?= e($values['family_name']) ?>" autocomplete="family-name"></div>
      </div>
      <div class="form-row">
        <div><label for="password">Password *</label><input id="password" name="password" type="password" required minlength="12" autocomplete="new-password"><p class="hint">At least 12 characters. Choose a unique password.</p></div>
        <div><label for="confirm_password">Confirm password *</label><input id="confirm_password" name="confirm_password" type="password" required minlength="12" autocomplete="new-password"></div>
      </div>
      <label for="institution">Institution</label><input id="institution" name="institution" maxlength="255" value="<?= e($values['institution']) ?>" placeholder="University or research institute">
      <label for="orcid">ORCID iD</label><input id="orcid" name="orcid" maxlength="80" value="<?= e($values['orcid']) ?>" placeholder="https://orcid.org/0000-0000-0000-0000">
      <p class="hint">Accounts registered here receive the Author role. Editorial permissions must be assigned separately by the website administrator.</p>
      <p><button class="cta navy" type="submit">Create account</button></p>
    </form>
    <p>Already registered? <a href="/login.php">Log in here</a>.</p>
  </div>
</div>
<?php page_end(); ?>
