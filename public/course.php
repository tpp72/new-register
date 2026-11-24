<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/view.php';

$course = course(input_id($_GET, 'id'));
if (!$course) {
    stop_page(404, 'ไม่พบหลักสูตร', 'ตรวจสอบลิงก์หรือเลือกหลักสูตรจากหน้ารายการ');
}
$closed = course_closed_reason($course);
page_start($course['title']);
?>
<a class="back" href="<?= e(url('public/index.php')) ?>">← หลักสูตรทั้งหมด</a>
<section class="panel">
    <div class="page-heading"><h1><?= e($course['title']) ?></h1><span class="status <?= $closed ? 'closed' : 'available' ?>"><?= e($closed ?: 'เปิดรับสมัคร') ?></span></div>
    <?php if ($course['description']): ?><p class="description"><?= e($course['description']) ?></p><?php endif; ?>
    <?php course_meta($course); ?>
    <div class="actions">
        <?php if ($closed): ?><button class="button" disabled><?= e($closed) ?></button>
        <?php else: ?><a class="button" href="<?= e(url('public/register.php', ['course_id' => $course['id']])) ?>">สมัครอบรม <span aria-hidden="true">→</span></a><?php endif; ?>
    </div>
</section>
<?php page_end();
