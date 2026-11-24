<?php
declare(strict_types=1);

function config(string $section): array
{
    static $settings;
    $settings ??= require dirname(__DIR__) . '/config/config.php';
    return $settings[$section];
}

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/courses.php';
require_once __DIR__ . '/registrations.php';

date_default_timezone_set(config('app')['timezone']);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (PHP_SAPI === 'cli') {
    return;
}

set_exception_handler(static function (Throwable $error): void {
    error_log('Training registration: ' . get_class($error) . ' at ' . $error->getFile() . ':' . $error->getLine());
    if (!headers_sent()) {
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html><html lang="th"><meta charset="utf-8"><title>ระบบไม่พร้อมใช้งาน</title>'
        . '<p>ระบบไม่พร้อมใช้งานชั่วคราว กรุณาลองใหม่ภายหลัง</p></html>';
});

$app = config('app');
$secure = isset($_SERVER['HTTPS']) && !in_array(strtolower($_SERVER['HTTPS']), ['', 'off', '0'], true);
if ($app['environment'] === 'local' && !local_request()) {
    http_response_code(403);
    echo 'Local access only.';
    exit;
}
if ($app['environment'] !== 'local' && !$secure) {
    if (str_starts_with($app['base_url'], 'https://')) {
        $configured = parse_url($app['base_url']);
        $origin = 'https://' . $configured['host'] . (isset($configured['port']) ? ':' . $configured['port'] : '');
        $uri = '/' . ltrim($_SERVER['REQUEST_URI'] ?? '/', '/');
        header('Location: ' . $origin . $uri, true, 307);
    } else {
        http_response_code(403);
        echo 'HTTPS required.';
    }
    exit;
}
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdn.jsdelivr.net; script-src 'self' https://challenges.cloudflare.com; frame-src https://challenges.cloudflare.com; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
if ($secure && $app['environment'] !== 'local') {
    header('Strict-Transport-Security: max-age=31536000');
}

if (defined('TRAINING_NO_SESSION')) {
    return;
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('training_reg_session');
session_set_cookie_params([
    'path' => app_base() . '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
