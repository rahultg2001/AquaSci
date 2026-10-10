<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/mailer.php';
$editor = require_editor();
$allowedStatuses = ['Received','Initial check','Under review','Revision requested','Revision submitted','Accepted','Rejected','Published'];
$message = $_SESSION['editor_notice'] ?? '';
unset($_SESSION['editor_notice']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT) ?: 0;
    $newStatus = is_string($_POST['status'] ?? null) ? trim($_POST['status']) : '';
    $note = is_string($_POST['editorial_note'] ?? null) ? trim($_POST['editorial_note']) : '';
    if ($submissionId < 1 || !in_array($newStatus, $allowedStatuses, true)) {
        $error = 'Choose a valid submission and status.';
    } elseif (strlen($note) > 10000) {
        $error = 'The editorial note must be 10,000 characters or fewer.';
    } else {
        $pdo = db();
        $find = $pdo->prepare('SELECT s.id,s.public_id,s.status,s.editorial_note,s.title,u.email,u.given_name FROM submissions s JOIN users u ON u.id=s.user_id WHERE s.id=? LIMIT 1');
        $find->execute([$submissionId]);
        $item = $find->fetch();
        if (!$item) {
            $error = 'Submission not found.';
        } else {
            $oldStatus = (string)$item['status'];
            $oldNote = (string)($item['editorial_note'] ?? '');
            $pdo->beginTransaction();
            try {
                $upd = $pdo->prepare('UPDATE submissions SET status=?, editorial_note=? WHERE id=?');
                $upd->execute([$newStatus, $note !== '' ? $note : null, $submissionId]);
                if ($oldStatus !== $newStatus || $oldNote !== $note) {
                    $hist = $pdo->prepare('INSERT INTO submission_history (submission_id,old_status,new_status,note,changed_by) VALUES (?,?,?,?,?)');
                    $hist->execute([$submissionId, $oldStatus, $newStatus, $note !== '' ? $note : null, (int)$editor['id']]);
                }
                $pdo->commit();
                if ($oldStatus !== $newStatus || $oldNote !== $note) {
                    try {
                        send_portal_email((string)$item['email'], (string)$item['given_name'], '[' . $item['public_id'] . '] Manuscript status updated', "Dear {$item['given_name']},\n\nThe status of your manuscript has been updated.\n\nTracking ID: {$item['public_id']}\nTitle: {$item['title']}\nNew status: {$newStatus}\n\nEditorial message:\n" . ($note !== '' ? $note : 'No additional note was supplied.') . "\n\nLog in to the Aquaculture Scientific author portal to view the full history.\n");
                        $_SESSION['editor_notice'] = 'Status and editorial note saved; an email notification was sent.';
                    } catch (Throwable $exception) {
                        error_log('Editor notification failed for ' . $item['public_id'] . ': ' . $exception->getMessage());
                        $_SESSION['editor_notice'] = 'Status and editorial note saved, but the email notification could not be confirmed.';
                    }
                } else {
                    $_SESSION['editor_notice'] = 'No changes were needed.';
                }
                redirect_to('/editor.php');
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $exception;
            }
        }
    }
}

$stmt = db()->query('SELECT s.id,s.public_id,s.title,s.article_type,s.status,s.editorial_note,s.created_at,s.updated_at,u.given_name,u.family_name,u.email FROM submissions s JOIN users u ON u.id=s.user_id ORDER BY s.created_at DESC LIMIT 200');
$submissions = $stmt->fetchAll();
page_start('Editorial submission dashboard', 'Review incoming manuscripts, set the editorial status and send revision instructions.');
?>
<div class="card" style="margin-bottom:18px"><p>Signed in as <strong><?= e($editor['given_name'] . ' ' . $editor['family_name']) ?></strong> (<?= e($editor['role']) ?>).</p><p><a class="cta ghost" href="/dashboard.php">Author dashboard</a></p><form method="post" action="/logout.php" style="display:inline-block;margin:0"><?= csrf_field() ?><button class="cta ghost" type="submit">Log out</button></form></div>
<?php if ($message !== ''): ?><div class="notice" role="status"><?= e($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="notice" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if (!$submissions): ?><div class="card"><p>No manuscripts have been submitted yet.</p></div><?php else: ?>
<div class="grid-2">
<?php foreach ($submissions as $item): ?>
  <article class="card">
    <p class="hint">Tracking ID: <strong><?= e($item['public_id']) ?></strong></p>
    <h3><?= e($item['title']) ?></h3>
    <p><strong>Author:</strong> <?= e($item['given_name'] . ' ' . $item['family_name']) ?> · <?= e($item['email']) ?></p>
    <p><strong>Submitted:</strong> <?= e(date('d M Y, H:i', strtotime($item['created_at']))) ?></p>
    <p><strong>Current status:</strong> <span class="status-pill"><?= e($item['status']) ?></span></p>
    <p><a href="/submission.php?id=<?= (int)$item['id'] ?>">View full submission and files</a></p>
    <form method="post" action="/editor.php">
      <?= csrf_field() ?>
      <input type="hidden" name="submission_id" value="<?= (int)$item['id'] ?>">
      <label for="status-<?= (int)$item['id'] ?>">Update status</label>
      <select id="status-<?= (int)$item['id'] ?>" name="status" required><?php foreach ($allowedStatuses as $status): ?><option value="<?= e($status) ?>" <?= $item['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select>
      <label for="note-<?= (int)$item['id'] ?>">Editorial note / revision request</label>
      <textarea id="note-<?= (int)$item['id'] ?>" name="editorial_note" rows="4" maxlength="10000" placeholder="Enter comments, revision instructions, or decision details."><?= e((string)$item['editorial_note']) ?></textarea>
      <p><button class="cta navy" type="submit">Save status and notify author</button></p>
    </form>
  </article>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php page_end(); ?>
