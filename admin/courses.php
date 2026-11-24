<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/includes/view.php';

require_admin();
if (is_post()) {
    verify_csrf();
    $id = input_id($_POST, 'id');
    $action = input($_POST, 'action');
    if ($action === 'toggle') {
        query('UPDATE courses SET is_open = 1 - is_open WHERE id = ?', [$id]);
        flash('อัปเดตสถานะการรับสมัครแล้ว');
    } elseif ($action === 'delete' && input($_POST, 'confirm_delete') === '1') {
        query('DELETE FROM courses WHERE id = ?', [$id]);
        flash('ลบหลักสูตรและรายชื่อผู้สมัครแล้ว');
    } else {
        stop_page(400, 'คำขอไม่ถูกต้อง', 'กรุณาเลือกการดำเนินการและยืนยัน');
    }
    redirect('admin/courses.php');
}
$courses = query(COURSE_LIST . ' ORDER BY c.start_at DESC, c.id DESC')->fetchAll();
page_start('จัดการหลักสูตร', true);
?>
<div class="page-heading"><h1>จัดการหลักสูตร</h1><a class="button" href="<?= e(url('admin/course_form.php')) ?>">เพิ่มหลักสูตร</a></div>
<?php if (!$courses): ?>
    <p class="panel empty">ยังไม่มีหลักสูตร</p>
<?php else: ?>
<div class="panel table-panel">
<div class="table-scroll" tabindex="0" role="region" aria-label="ตารางหลักสูตร">
<table>
    <thead><tr><th scope="col">หลักสูตร</th><th scope="col">วันเริ่มอบรม</th><th scope="col">ผู้สมัคร</th><th scope="col">สถานะ</th><th scope="col">จัดการ</th></tr></thead>
    <tbody>
        <?php foreach ($courses as $course): $closed = course_closed_reason($course); ?>
        <tr>
            <th scope="row"><?= e($course['title']) ?></th>
            <td><?= e(thai_date($course['start_at'])) ?></td>
            <td><?= (int) $course['enrolled'] ?> / <?= (int) $course['capacity'] ?></td>
            <td><span class="status <?= $closed ? 'closed' : 'available' ?>"><?= e($closed ?: 'เปิดรับสมัคร') ?></span></td>
            <td><div class="table-actions">
                <a href="<?= e(url('admin/registrations.php', ['course_id' => $course['id']])) ?>">รายชื่อ</a>
                <a href="<?= e(url('admin/course_form.php', ['id' => $course['id']])) ?>">แก้ไข</a>
                <form method="post">
                    <?php csrf_field(); ?><input type="hidden" name="id" value="<?= e($course['id']) ?>">
                    <button class="text-button" name="action" value="toggle"><?= $course['is_open'] ? 'ปิดรับ' : 'เปิดรับ' ?></button>
                </form>
                <details class="delete-control">
                    <summary>ลบ</summary>
                    <form method="post">
                        <?php csrf_field(); ?><input type="hidden" name="id" value="<?= e($course['id']) ?>">
                        <label class="check"><input type="checkbox" name="confirm_delete" value="1" required> ยืนยันลบหลักสูตรและผู้สมัครทั้งหมด</label>
                        <button class="text-button danger-text" name="action" value="delete">ลบหลักสูตร</button>
                    </form>
                </details>
            </div></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>
<?php endif; page_end();
