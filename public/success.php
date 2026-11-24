<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/view.php';

$registrationId = $_SESSION['registration_confirmations'][input($_GET, 'id')] ?? null;
if ($registrationId === null) {
    stop_page(404, 'ไม่พบใบสมัคร', 'เปิดหน้านี้จากเบราว์เซอร์ที่ใช้สมัครอบรม');
}
$applicant = query('SELECT * FROM registrations WHERE id = ?', [$registrationId])->fetch();
$course = $applicant ? course((int) $applicant['course_id']) : false;
if (!$course) {
    stop_page(404, 'ไม่พบใบสมัคร', 'ใบสมัครนี้อาจถูกลบแล้ว');
}
page_start('สมัครสำเร็จ');
?>
<section class="panel form-panel">
    <span class="status available">✓ ได้รับใบสมัครแล้ว</span>
    <h1>สมัครสำเร็จ</h1>
    <h2><?= e($course['title']) ?></h2>
    <?php course_meta($course); ?>
    <h2>สรุปข้อมูลผู้สมัคร</h2>
    <dl class="course-meta">
        <?php foreach (APPLICANT_FIELDS as $key => [$label]): ?>
            <div><dt><?= e($label) ?></dt><dd><?= e($applicant[$key] ?? '—') ?></dd></div>
        <?php endforeach; ?>
        <div><dt>ความยินยอม</dt><dd>ให้ความยินยอมแล้ว</dd></div>
    </dl>
    <div class="actions"><a class="button" href="<?= e(url('public/index.php')) ?>">กลับหน้าหลักสูตร</a></div>
</section>
<?php page_end();
