<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/includes/view.php';

require_admin();
$courseId = input_id($_GET, 'course_id');
$id = input_id($_GET, 'id');
$course = course($courseId);
if (!$course) {
    stop_page(404, 'ไม่พบหลักสูตร', 'กรุณาเลือกหลักสูตรจากหน้าจัดการหลักสูตร');
}
$data = $id ? query('SELECT * FROM registrations WHERE id = ? AND course_id = ?', [$id, $courseId])->fetch() : [];
if (array_key_exists('id', $_GET) && !$data) {
    stop_page(404, 'ไม่พบผู้สมัคร', 'กรุณาเลือกผู้สมัครจากหน้ารายชื่อ');
}
$errors = [];
if (is_post()) {
    verify_csrf();
    $data = applicant_form_data($_POST);
    $errors = validate_applicant($data);
    if (!$errors) {
        try {
            save_applicant($courseId, $data, $id ?: null, false);
            flash('บันทึกข้อมูลผู้สมัครแล้ว');
            redirect('admin/registrations.php', ['course_id' => $courseId]);
        } catch (DomainException $error) {
            $errors[] = $error->getMessage();
        }
    }
}
$title = $id ? 'แก้ไขผู้สมัคร' : 'เพิ่มผู้สมัคร';
$back = url('admin/registrations.php', ['course_id' => $courseId]);
page_start($title, true);
?>
<a class="back" href="<?= e($back) ?>">← รายชื่อผู้สมัคร</a>
<section class="panel form-panel">
    <h1><?= e($title) ?></h1><p class="subtitle"><?= e($course['title']) ?></p>
    <?php errors_block($errors); ?>
    <form method="post">
        <?php csrf_field(); applicant_fields($data, $errors, true); ?>
        <div class="actions"><button class="button">บันทึกผู้สมัคร</button><a class="button secondary" href="<?= e($back) ?>">ยกเลิก</a></div>
    </form>
</section>
<?php page_end();
