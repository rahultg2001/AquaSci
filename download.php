<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$fileId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($fileId < 1) { http_response_code(404); exit('File not found.'); }
$stmt = db()->prepare('SELECT f.*,s.user_id,s.id AS submission_id FROM submission_files f JOIN submissions s ON s.id=f.submission_id WHERE f.id=? LIMIT 1');
$stmt->execute([$fileId]);
$file = $stmt->fetch();
if (!$file || (!in_array($user['role'], ['editor','admin'], true) && (int)$file['user_id'] !== (int)$user['id'])) {
    http_response_code(404);
    exit('File not found.');
}
$cfg = app_config();
$storage = rtrim((string)($cfg['storage_path'] ?? (dirname(__DIR__) . '/private/uploads')), DIRECTORY_SEPARATOR);
$rootReal = realpath($storage);
$path = realpath($storage . DIRECTORY_SEPARATOR . $file['relative_path']);
if (!$rootReal || !$path || !is_file($path) || !str_starts_with($path, $rootReal . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    exit('File not found.');
}
$name = str_replace(["\r", "\n", '"'], '', basename((string)$file['original_name']));
header('Content-Type: application/octet-stream');
header('Content-Length: ' . (string)filesize($path));
header('Content-Disposition: attachment; filename="' . addcslashes($name, '\\"') . '"; filename*=UTF-8\'\'' . rawurlencode($name));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
