<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/includes/view.php';

require_admin();
$courseId = input_id($_GET, 'course_id');
$course = course($courseId);
if (!$course) {
    stop_page(404, 'ไม่พบหลักสูตร', 'กรุณาเลือกหลักสูตรจากหน้าจัดการหลักสูตร');
}
if (is_post()) {
    verify_csrf();
    if (input($_POST, 'confirm_delete') !== '1') {
        stop_page(400, 'คำขอไม่ถูกต้อง', 'กรุณายืนยันการลบผู้สมัคร');
    }
    query('DELETE FROM registrations WHERE course_id = ? AND id = ?', [$courseId, input_id($_POST, 'id')]);
    flash('ลบข้อมูลผู้สมัครแล้ว');
    redirect('admin/registrations.php', ['course_id' => $courseId]);
}

$term = mb_substr(input($_GET, 'q'), 0, 120);
$params = [$courseId];
$where = ' WHERE course_id = ?';
if ($term !== '') {
    $where .= " AND LOCATE(?, CONCAT_WS(' ', first_name, last_name, email, phone, organization)) > 0";
    $params[] = $term;
}
$count = (int) query('SELECT COUNT(*) FROM registrations' . $where, $params)->fetchColumn();
$size = 20;
$last = max(1, (int) ceil($count / $size));
$current = min($last, max(1, input_id($_GET, 'page')));
$offset = ($current - 1) * $size;
$rows = query('SELECT * FROM registrations' . $where . ' ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?',
    [...$params, $size, $offset])->fetchAll();
$pageParams = ['course_id' => $courseId, 'q' => $term];

page_start('รายชื่อผู้สมัคร', true);
?>
<a class="back" href="<?= e(url('admin/courses.php')) ?>">← จัดการหลักสูตร</a>
<div class="page-heading">
    <div><h1>รายชื่อผู้สมัคร</h1><p class="subtitle"><?= e($course['title']) ?> · <?= (int) $course['enrolled'] ?> / <?= (int) $course['capacity'] ?> ที่นั่ง</p></div>
    <div class="actions">
        <a class="button secondary" href="<?= e(url('admin/export.php', ['course_id' => $courseId])) ?>">ส่งออก CSV</a>
        <a class="button" href="<?= e(url('admin/registration_form.php', ['course_id' => $courseId])) ?>">เพิ่มผู้สมัคร</a>
    </div>
</div>
<section class="panel table-panel">
    <form method="get" class="search">
        <input type="hidden" name="course_id" value="<?= $courseId ?>">
        <div class="field"><label for="q">ค้นหาผู้สมัคร</label><input id="q" name="q" maxlength="120" value="<?= e($term) ?>" placeholder="ชื่อ อีเมล เบอร์โทร หรือหน่วยงาน"></div>
        <button class="button secondary">ค้นหา</button>
        <?php if ($term !== ''): ?><a href="<?= e(url('admin/registrations.php', ['course_id' => $courseId])) ?>">ล้าง</a><?php endif; ?>
    </form>
    <?php if (!$rows): ?><p class="muted">ไม่พบผู้สมัคร</p>
    <?php else: ?>
        <div class="table-scroll" tabindex="0" role="region" aria-label="ตารางผู้สมัคร">
        <table>
            <thead><tr><th scope="col">ชื่อผู้สมัคร</th><th scope="col">ข้อมูลติดต่อ</th><th scope="col">หน่วยงาน</th><th scope="col">วันที่สมัคร</th><th scope="col">จัดการ</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $applicant): ?>
                <tr>
                    <th scope="row"><?= e(trim(implode(' ', [$applicant['prefix'], $applicant['first_name'], $applicant['last_name']]))) ?></th>
                    <td><?= e($applicant['phone']) ?><br><?= e($applicant['email']) ?></td>
                    <td><?= e($applicant['organization'] ?: '—') ?></td>
                    <td><?= e(thai_date($applicant['created_at'])) ?></td>
                    <td><div class="table-actions">
                        <a href="<?= e(url('admin/registration_form.php', ['course_id' => $courseId, 'id' => $applicant['id']])) ?>">แก้ไข</a>
                        <details class="delete-control">
                            <summary>ลบ</summary>
                            <form method="post">
                                <?php csrf_field(); ?><input type="hidden" name="id" value="<?= e($applicant['id']) ?>">
                                <label class="check"><input type="checkbox" name="confirm_delete" value="1" required> ยืนยันลบผู้สมัคร</label>
                                <button class="text-button danger-text">ลบผู้สมัคร</button>
                            </form>
                        </details>
                    </div></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <nav class="pagination-bar" aria-label="หน้ารายชื่อ">
            <?php if ($current > 1): ?><a href="<?= e(url('admin/registrations.php', $pageParams + ['page' => $current - 1])) ?>">← ก่อนหน้า</a><?php endif; ?>
            <span>หน้า <?= $current ?> จาก <?= $last ?> · <?= $count ?> รายชื่อ</span>
            <?php if ($current < $last): ?><a href="<?= e(url('admin/registrations.php', $pageParams + ['page' => $current + 1])) ?>">ถัดไป →</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
<?php page_end();
