<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/includes/view.php';

if (isset($_SESSION['admin_id'])) {
    redirect('admin/index.php');
}
$errors = [];
$username = '';
if (is_post()) {
    verify_csrf();
    $username = input($_POST, 'username');
    $password = password_input('password');
    if (!consume_rate_limit('login', client_ip(), 5, 900)) {
        http_response_code(429);
        $errors[] = 'ลองเข้าสู่ระบบเกินจำนวนที่กำหนด กรุณารอ 15 นาที';
    } elseif ($username === '' || mb_strlen($username) > 50 || strlen($password) > 72 || !login_admin($username, $password)) {
        $errors[] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    } else {
        query('DELETE FROM rate_limits WHERE action = ? AND identity_hash = ?', ['login', hash('sha256', client_ip())]);
        redirect('admin/index.php');
    }
}
page_start('เข้าสู่ระบบผู้ดูแล', true);
?>
<section class="panel auth-panel">
    <span class="course-icon" aria-hidden="true">▤</span>
    <h1>เข้าสู่ระบบผู้ดูแล</h1>
    <?php errors_block($errors); ?>
    <form method="post">
        <?php csrf_field(); ?>
        <div class="field"><label for="username">ชื่อผู้ใช้</label><input id="username" name="username" value="<?= e($username) ?>" autocomplete="username" maxlength="50" required></div>
        <div class="field"><label for="password">รหัสผ่าน</label><input type="password" id="password" name="password" autocomplete="current-password" required></div>
        <div class="actions"><button class="button">เข้าสู่ระบบ</button></div>
    </form>
</section>
<?php page_end();
