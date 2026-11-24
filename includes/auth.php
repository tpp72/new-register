<?php
declare(strict_types=1);

function require_admin(): int
{
    if (!isset($_SESSION['admin_id'])) {
        flash('กรุณาเข้าสู่ระบบผู้ดูแล');
        redirect('admin/login.php');
    }
    return $_SESSION['admin_id'];
}

function login_admin(string $username, string $password): bool
{
    $admin = query('SELECT id, password_hash FROM admins WHERE username = ?', [$username])->fetch();
    $fallback = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    $valid = password_verify($password, $admin['password_hash'] ?? $fallback);
    if (!$admin || !$valid) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION = ['admin_id' => (int) $admin['id']];
    return true;
}

function admin_password_error(string $password): string
{
    return mb_strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0")
        ? 'ตั้งรหัสผ่านอย่างน้อย 12 ตัวอักษร และไม่เกิน 72 ไบต์' : '';
}

function password_input(string $key): string
{
    return is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
}
