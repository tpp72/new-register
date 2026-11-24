<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/auth.php';

$username = $argv[1] ?? '';
$fullName = trim($argv[2] ?? $username);
if (!preg_match('/^[a-zA-Z0-9._-]{1,50}$/D', $username) || $fullName === '' || mb_strlen($fullName) > 100) {
    fwrite(STDERR, "Usage: php scripts/create_admin.php username [display-name]\n");
    exit(1);
}

$provided = getenv('TRAINING_ADMIN_PASSWORD');
$password = $provided === false ? bin2hex(random_bytes(16)) : $provided;
if ($error = admin_password_error($password)) {
    fwrite(STDERR, $error . PHP_EOL);
    exit(1);
}

try {
    query('INSERT INTO admins (username, full_name, password_hash) VALUES (?, ?, ?)',
        [$username, $fullName, password_hash($password, PASSWORD_DEFAULT)]);
} catch (PDOException $error) {
    $message = (int) ($error->errorInfo[1] ?? 0) === 1062 ? 'Username is already in use.' : 'Could not create admin. Check the database configuration.';
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
echo "Administrator created: $username" . PHP_EOL;
if ($provided === false) {
    echo "Temporary password: $password" . PHP_EOL;
}
