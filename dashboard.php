<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$stmt = db()->prepare('SELECT id, public_id, title, article_type, status, editorial_note, created_at, updated_at FROM submissions WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([(int)$user['id']]);
$submissions = $stmt->fetchAll();

page_start('My submissions', 'Welcome, ' . $user['given_name'] . '. View manuscript status and editorial messages here.');
?>
<div class="card" style="margin-bottom:20px">
  <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
    <div><h3>Author account</h3><p><strong><?= e($user['given_name'] . ' ' . $user['family_name']) ?></strong><br><?= e($user['email']) ?> · Username: <?= e($user['username']) ?></p></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap"><a class="cta navy" href="/submit.php">Submit a manuscript</a><?php if (in_array($user['role'], ['editor','admin'], true)): ?><a class="cta ghost" href="/editor.php">Editor dashboard</a><?php endif; ?><form method="post" action="/logout.php" style="margin:0"><?= csrf_field() ?><button class="cta ghost" type="submit">Log out</button></form></div>
  </div>
</div>
<h3>Your manuscript history</h3>
<?php if (!$submissions): ?>
  <div class="card"><p>You have not submitted a manuscript yet.</p><p><a class="cta navy" href="/submit.php">Start your first submission</a></p></div>
<?php else: ?>
  <div class="grid-2">
  <?php foreach ($submissions as $submission): ?>
    <article class="card">
      <p class="hint">Tracking ID: <strong><?= e($submission['public_id']) ?></strong></p>
      <h3><?= e($submission['title']) ?></h3>
      <p><strong>Article type:</strong> <?= e($submission['article_type']) ?></p>
      <p><strong>Status:</strong> <span class="status-pill"><?= e($submission['status']) ?></span></p>
      <p><strong>Submitted:</strong> <?= e(date('d M Y, H:i', strtotime($submission['created_at']))) ?></p>
      <?php if (trim((string)$submission['editorial_note']) !== ''): ?><div class="notice"><strong>Latest editorial message</strong><p><?= nl2br(e($submission['editorial_note'])) ?></p></div><?php endif; ?>
      <p><a class="cta navy" href="/submission.php?id=<?= (int)$submission['id'] ?>">View submission and updates</a></p>
    </article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php page_end(); ?>
