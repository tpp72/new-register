<?php
declare(strict_types=1);

function e(string|int|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function input(array $source, string $key): string
{
    return is_string($source[$key] ?? null) ? trim($source[$key]) : '';
}

function input_id(array $source, string $key): int
{
    return filter_var(input($source, $key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
}

function app_base(): string
{
    $configured = config('app')['base_url'];
    if ($configured !== '') {
        return rtrim(parse_url($configured, PHP_URL_PATH) ?: '', '/');
    }
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    return preg_match('~^(.*)/(?:public|admin)/[^/]+$~', $script, $match) ? $match[1] : '';
}

function url(string $path, array $params = []): string
{
    return app_base() . '/' . ltrim($path, '/') . ($params ? '?' . http_build_query($params) : '');
}

function redirect(string $path, array $params = []): never
{
    header('Location: ' . url($path, $params), true, 303);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function csrf_field(): void
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    echo '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function verify_csrf(): void
{
    if (!is_post() || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], input($_POST, 'csrf'))) {
        stop_page(403, 'คำขอไม่ถูกต้อง', 'กรุณาเปิดหน้าเดิมใหม่และลองอีกครั้ง');
    }
}

function flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function client_ip(): string
{
    $address = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($address, FILTER_VALIDATE_IP) ?: '';
}

function local_request(): bool
{
    return config('app')['environment'] === 'local' && in_array(client_ip(), ['127.0.0.1', '::1'], true);
}

function consume_rate_limit(string $action, string $identity, int $limit, int $seconds): bool
{
    $key = hash('sha256', $identity);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        query('INSERT INTO rate_limits (action, identity_hash, attempts, expires_at)
               VALUES (?, ?, 0, TIMESTAMPADD(SECOND, ?, NOW()))
               ON DUPLICATE KEY UPDATE identity_hash = identity_hash', [$action, $key, $seconds]);
        $row = query('SELECT attempts, expires_at <= NOW() AS expired FROM rate_limits
                      WHERE action = ? AND identity_hash = ? FOR UPDATE', [$action, $key])->fetch();
        $attempts = $row['expired'] ? 0 : (int) $row['attempts'];
        if ($row['expired']) {
            query('UPDATE rate_limits SET attempts = 1, expires_at = TIMESTAMPADD(SECOND, ?, NOW())
                   WHERE action = ? AND identity_hash = ?', [$seconds, $action, $key]);
        } elseif ($attempts < $limit) {
            query('UPDATE rate_limits SET attempts = attempts + 1 WHERE action = ? AND identity_hash = ?', [$action, $key]);
        }
        $pdo->commit();
        return $attempts < $limit;
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function thai_date(?string $value, bool $withTime = true): string
{
    if (!$value) {
        return 'ไม่ระบุ';
    }
    $date = new DateTimeImmutable($value);
    $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return $date->format('j') . ' ' . $months[(int) $date->format('n') - 1] . ' '
        . ((int) $date->format('Y') + 543) . ($withTime ? ' · ' . $date->format('H:i') . ' น.' : '');
}

function datetime_input(?string $value): string
{
    return $value ? str_replace(' ', 'T', substr($value, 0, 16)) : '';
}

function stop_page(int $status, string $title, string $message): never
{
    require_once __DIR__ . '/view.php';
    http_response_code($status);
    page_start($title, isset($_SESSION['admin_id']));
    echo '<section class="panel"><h1>' . e($title) . '</h1><p>' . e($message) . '</p></section>';
    page_end();
    exit;
}
