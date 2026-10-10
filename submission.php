<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/mailer.php';
$user = require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($id < 1) {
    http_response_code(400);
    page_start('Invalid submission');
    echo '<div class="card"><p>The submission ID is invalid.</p><p><a href="/dashboard.php">Return to dashboard</a></p></div>';
    page_end();
    exit;
}
$isEditor = in_array($user['role'], ['editor','admin'], true);

$stmt = db()->prepare('SELECT s.*, u.id AS author_user_id, u.username, u.email AS author_email, u.given_name, u.family_name, u.institution FROM submissions s JOIN users u ON u.id = s.user_id WHERE s.id = ? LIMIT 1');
$stmt->execute([$id]);
$submission = $stmt->fetch();
if (!$submission || (!$isEditor && (int)$submission['user_id'] !== (int)$user['id'])) {
    http_response_code(404);
    page_start('Submission not found');
    echo '<div class="card"><p>This submission does not exist or you do not have access to it.</p><p><a href="/dashboard.php">Return to dashboard</a></p></div>';
    page_end();
    exit;
}

$notice = $_SESSION['submission_notice'] ?? '';
unset($_SESSION['submission_notice']);
$revisionError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($isEditor || (int)$submission['user_id'] !== (int)$user['id']) {
        http_response_code(403);
        page_start('Access restricted');
        echo '<div class="card"><p>You cannot upload a revision for this submission.</p></div>';
        page_end();
        exit;
    }
    if ($submission['status'] !== 'Revision requested') {
        $revisionError = 'A revision can be uploaded only after the editor requests one.';
    } else {
        $file = $_FILES['revision_file'] ?? null;
        if (!is_array($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)$file['tmp_name'])) {
            $revisionError = 'Choose a revised Word manuscript to upload.';
        } elseif (!in_array(strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION)), ['doc','docx'], true)) {
            $revisionError = 'A revised manuscript must be a .doc or .docx file.';
        } elseif ((int)$file['size'] < 1 || (int)$file['size'] > 15 * 1024 * 1024) {
            $revisionError = 'The revised manuscript must be no larger than 15 MB.';
        } else {
            $cfg = app_config();
            $storage = rtrim((string)($cfg['storage_path'] ?? (dirname(__DIR__) . '/private/uploads')), DIRECTORY_SEPARATOR);
            $subDir = $storage . DIRECTORY_SEPARATOR . (int)$submission['id'];
            if (!is_dir($subDir) && !mkdir($subDir, 0750, true) && !is_dir($subDir)) throw new RuntimeException('Could not create private revision storage.');
            $original = str_replace(["\r", "\n", "\0"], '', basename((string)$file['name']));
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $stored = bin2hex(random_bytes(16)) . '.' . $ext;
            $dest = $subDir . DIRECTORY_SEPARATOR . $stored;
            if (!move_uploaded_file($file['tmp_name'], $dest)) throw new RuntimeException('Could not save the revised manuscript.');
            @chmod($dest, 0640);
            $relative = (int)$submission['id'] . '/' . $stored;
            $mime = 'application/octet-stream';
            if (function_exists('finfo_open')) {
                $fi = finfo_open(FILEINFO_MIME_TYPE);
                if ($fi) { $found = finfo_file($fi, $dest); if (is_string($found)) $mime = $found; finfo_close($fi); }
            }
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $ins = $pdo->prepare('INSERT INTO submission_files (submission_id,file_kind,original_name,relative_path,mime_type,size_bytes) VALUES (?,?,?,?,?,?)');
                $ins->execute([(int)$submission['id'], 'revision', $original, $relative, $mime, (int)$file['size']]);
                $up = $pdo->prepare("UPDATE submissions SET status = 'Revision submitted', mail_status = 'pending', mail_error = NULL WHERE id = ?");
                $up->execute([(int)$submission['id']]);
                $hist = $pdo->prepare('INSERT INTO submission_history (submission_id,old_status,new_status,note,changed_by) VALUES (?,?,?,?,?)');
                $hist->execute([(int)$submission['id'], 'Revision requested', 'Revision submitted', 'Author uploaded revised manuscript: ' . $original, (int)$user['id']]);
                $pdo->commit();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                @unlink($dest);
                throw $exception;
            }
            try {
                send_portal_email((string)(app_config()['mail']['to_email'] ?? ''), 'Editorial Office', '[' . $submission['public_id'] . '] Revised manuscript received', "A revised manuscript was uploaded.\n\nTracking ID: {$submission['public_id']}\nTitle: {$submission['title']}\nAuthor: {$user['given_name']} {$user['family_name']}\nEmail: {$user['email']}\n", [['path'=>$dest,'name'=>$original]]);
                $pdo->prepare("UPDATE submissions SET mail_status='sent', mail_error=NULL WHERE id=?")->execute([(int)$submission['id']]);
                $_SESSION['submission_notice'] = 'Your revised manuscript was saved and the editorial office was emailed.';
            } catch (Throwable $exception) {
                error_log('Revision email failed for ' . $submission['public_id'] . ': ' . $exception->getMessage());
                $pdo->prepare("UPDATE submissions SET mail_status='failed', mail_error=? WHERE id=?")->execute([substr($exception->getMessage(),0,450),(int)$submission['id']]);
                $_SESSION['submission_notice'] = 'Your revised manuscript was saved in the portal, but email delivery could not be confirmed. Please contact the editorial office.';
            }
            redirect_to('/submission.php?id=' . (int)$submission['id']);
        }
    }
}

$fileStmt = db()->prepare('SELECT id,file_kind,original_name,size_bytes,created_at FROM submission_files WHERE submission_id=? ORDER BY created_at ASC, id ASC');
$fileStmt->execute([(int)$submission['id']]);
$files = $fileStmt->fetchAll();
$historyStmt = db()->prepare('SELECT h.old_status,h.new_status,h.note,h.changed_at, u.given_name,u.family_name FROM submission_history h LEFT JOIN users u ON u.id=h.changed_by WHERE h.submission_id=? ORDER BY h.changed_at DESC,h.id DESC');
$historyStmt->execute([(int)$submission['id']]);
$history = $historyStmt->fetchAll();

page_start('Submission details', 'Track status, read editorial messages and view files for this manuscript.');
?>
<?php if ($notice !== ''): ?><div class="notice" role="status"><?= e($notice) ?></div><?php endif; ?>
<?php if ($revisionError !== ''): ?><div class="notice" role="alert"><?= e($revisionError) ?></div><?php endif; ?>
<div class="card" style="margin-bottom:18px">
  <p class="hint">Tracking ID: <strong><?= e($submission['public_id']) ?></strong></p>
  <h3><?= e($submission['title']) ?></h3>
  <p><strong>Status:</strong> <span class="status-pill"><?= e($submission['status']) ?></span></p>
  <p><strong>Submitted:</strong> <?= e(date('d M Y, H:i', strtotime($submission['created_at']))) ?></p>
  <p><strong>Last updated:</strong> <?= e(date('d M Y, H:i', strtotime($submission['updated_at']))) ?></p>
  <?php if ($isEditor): ?><p><strong>Author:</strong> <?= e($submission['given_name'] . ' ' . $submission['family_name']) ?> (<?= e($submission['author_email']) ?>)</p><?php endif; ?>
  <p><strong>Article type:</strong> <?= e($submission['article_type']) ?><br><strong>Subject:</strong> <?= e($submission['subject_area']) ?></p>
  <h4>Abstract</h4><p><?= nl2br(e($submission['abstract_text'])) ?></p>
  <?php if (trim((string)$submission['cover_letter']) !== ''): ?><h4>Cover letter</h4><p><?= nl2br(e($submission['cover_letter'])) ?></p><?php endif; ?>
  <?php if (trim((string)$submission['editorial_note']) !== ''): ?><div class="notice"><strong>Latest editorial message / revision request</strong><p><?= nl2br(e($submission['editorial_note'])) ?></p></div><?php endif; ?>
</div>
<div class="card" style="margin-bottom:18px">
  <h3>Uploaded files</h3>
  <?php if (!$files): ?><p>No files are recorded.</p><?php else: ?><ul><?php foreach ($files as $file): ?><li><a href="/download.php?id=<?= (int)$file['id'] ?>"><?= e($file['original_name']) ?></a> — <?= e(ucfirst($file['file_kind'])) ?>, <?= e(number_format(((int)$file['size_bytes'])/1024, 1)) ?> KB</li><?php endforeach; ?></ul><?php endif; ?>
  <?php if (!$isEditor && $submission['status'] === 'Revision requested'): ?>
    <h3>Upload revised manuscript</h3>
    <p class="hint">Upload the revised Word document. This adds a revision to the existing tracking record.</p>
    <form method="post" action="/submission.php?id=<?= (int)$submission['id'] ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <label for="revision-file">Revised manuscript (.doc or .docx)</label>
      <input id="revision-file" name="revision_file" type="file" accept=".doc,.docx" required>
      <p><button class="cta navy" type="submit">Submit revision</button></p>
    </form>
  <?php endif; ?>
</div>
<div class="card" style="margin-bottom:18px">
  <h3>Status and revision history</h3>
  <?php if (!$history): ?><p>No updates have been recorded yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Date</th><th>Status</th><th>Message</th><th>Updated by</th></tr></thead><tbody><?php foreach ($history as $event): ?><tr><td><?= e(date('d M Y, H:i', strtotime($event['changed_at']))) ?></td><td><?= e($event['new_status']) ?></td><td><?= nl2br(e((string)$event['note'])) ?></td><td><?= e(trim(($event['given_name'] ?? '') . ' ' . ($event['family_name'] ?? '')) ?: 'Editorial system') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>
<p><a class="cta ghost" href="/dashboard.php">Back to my submissions</a><?php if ($isEditor): ?> <a class="cta navy" href="/editor.php">Back to editor dashboard</a><?php endif; ?></p>
<?php page_end(); ?>
