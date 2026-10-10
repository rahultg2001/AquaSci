<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    page_start('Use the log out button');
    echo '<div class="card"><p>Please use the Log out button in your account dashboard.</p><p><a href="/dashboard.php">Return to dashboard</a></p></div>';
    page_end();
    exit;
}
verify_csrf();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
}
session_destroy();
redirect_to('/login.php');
