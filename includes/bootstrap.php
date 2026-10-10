<?php
declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
$https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
session_name('AS_AUTHOR_PORTAL');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, private');

function app_config(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }
    $path = dirname(__DIR__, 2) . '/private/app-config.php';
    if (!is_file($path)) {
        throw new RuntimeException('Portal configuration is missing. Create private/app-config.php from private/app-config.example.php.');
    }
    $loaded = require $path;
    if (!is_array($loaded)) {
        throw new RuntimeException('Portal configuration must return a PHP array.');
    }
    $config = $loaded;
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $cfg = app_config();
    if (empty($cfg['db']['name']) || empty($cfg['db']['user'])) {
        throw new RuntimeException('Database settings are incomplete in private/app-config.php.');
    }
    $host = $cfg['db']['host'] ?? 'localhost';
    $name = $cfg['db']['name'];
    $charset = $cfg['db']['charset'] ?? 'utf8mb4';
    $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
    $pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect_to(string $path): never
{
    // Only internal absolute paths are used by this application.
    if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
        $path = '/dashboard.php';
    }
    header('Location: ' . $path, true, 303);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        http_response_code(403);
        page_start('Security check failed', 'Please return to the form and try again.');
        echo '<div class="card"><p>Your session security token was missing or expired. No changes were saved.</p><p><a class="cta navy" href="/login.php">Continue</a></p></div>';
        page_end();
        exit;
    }
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, username, email, given_name, family_name, institution, orcid, role, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect_to('/login.php?next=submit.php');
    }
    return $user;
}

function require_editor(): array
{
    $user = require_login();
    if (!in_array($user['role'], ['editor', 'admin'], true)) {
        http_response_code(403);
        page_start('Access restricted', 'The editor area is only available to authorised editorial users.');
        echo '<div class="card"><p>You do not have permission to access this page.</p><p><a href="/dashboard.php">Return to your dashboard</a></p></div>';
        page_end();
        exit;
    }
    return $user;
}

function page_start(string $title, string $subtitle = ''): void
{
    echo '<!doctype html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . ' | Aquaculture Scientific</title>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700;900&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="/css/style.css"></head><body>';
    echo '<div id="site-header"></div><div class="page-hero"><div class="wrap"><h2>' . e($title) . '</h2>';
    if ($subtitle !== '') {
        echo '<p>' . e($subtitle) . '</p>';
    }
    echo '</div></div><section class="section"><div class="wrap">';
}

function page_end(): void
{
    echo '</div></section><div id="site-footer"></div><script src="/js/site.js"></script></body></html>';
}

function show_portal_error(Throwable $error): never
{
    error_log('Aquaculture Scientific portal error: ' . $error->getMessage());
    http_response_code(500);
    $msg = 'The author portal is not configured or is temporarily unavailable. Please try again later or contact the editorial office.';
    if (str_contains($error->getMessage(), 'configuration is missing')) {
        $msg = 'The author portal has not been configured yet. The website administrator must finish the database and mail setup before registration and submissions can work.';
    }
    page_start('Author portal unavailable');
    echo '<div class="card"><p>' . e($msg) . '</p><p><a href="/">Return to Aquaculture Scientific</a></p></div>';
    page_end();
    exit;
}

set_exception_handler('show_portal_error');
