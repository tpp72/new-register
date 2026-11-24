<?php
declare(strict_types=1);

$settings = [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3307,
        'name' => 'training_reg',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'environment' => 'local',
        'base_url' => '',
        'timezone' => 'Asia/Bangkok',
        'retention_months' => 12,
        'privacy_contact' => '',
    ],
    'mail' => [
        'enabled' => false,
        'host' => '',
        'port' => 587,
        'user' => '',
        'pass' => '',
        'from' => '',
        'from_name' => 'ระบบลงทะเบียนอบรม',
    ],
    'turnstile' => [
        'site_key' => '',
        'secret_key' => '',
    ],
];

$override = __DIR__ . '/local.php';
return is_file($override) ? array_replace_recursive($settings, require $override) : $settings;
