<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/includes/view.php';

$id = require_admin();
$errors = [];
if (is_post()) {
    verify_csrf();
    $old = password_input('current_password');
    $new = password_input('new_password');
    $confirm = password_input('confirm_password');
    $hash = query('SELECT password_hash FROM admins WHERE id = ?', [$id])->fetchColumn();
    if (!$hash || strlen($old) > 72 || !password_verify($old, $hash)) {
        $errors[] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
    }
    if ($policyError = admin_password_error($new)) {
        $errors[] = $policyError;
    }
    if ($new !== $confirm) {
        $errors[] = 'รหัสผ่านใหม่กับช่องยืนยันไม่ตรงกัน';
    }
    if (!$errors) {
        $changed = query('UPDATE admins SET password_hash = ? WHERE id = ? AND password_hash = ?',
            [password_hash($new, PASSWORD_DEFAULT), $id, $hash])->rowCount();
        if ($changed) {
            session_regenerate_id(true);
            unset($_SESSION['csrf']);
            flash('เปลี่ยนรหัสผ่านแล้ว');
            redirect('admin/password.php');
        }
        $errors[] = 'รหัสผ่านมีการเปลี่ยนแปลง กรุณาตรวจสอบแล้วลองอีกครั้ง';
    }
}
page_start('เปลี่ยนรหัสผ่าน', true);
?>
<section class="panel auth-panel">
    <h1>เปลี่ยนรหัสผ่าน</h1>
    <?php errors_block($errors); ?>
    <form method="post">
        <?php csrf_field(); ?>
        <div class="field"><label for="current_password">รหัสผ่านปัจจุบัน</label><input type="password" id="current_password" name="current_password" autocomplete="current-password" required></div>
        <div class="field"><label for="new_password">รหัสผ่านใหม่</label><input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="12" maxlength="72" required><small>อย่างน้อย 12 ตัวอักษร และไม่เกิน 72 ไบต์</small></div>
        <div class="field"><label for="confirm_password">ยืนยันรหัสผ่านใหม่</label><input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required></div>
        <div class="actions"><button class="button">บันทึกรหัสผ่าน</button></div>
    </form>
</section>
<?php page_end();
