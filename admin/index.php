<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/includes/view.php';

require_admin();
$stats = query('SELECT (SELECT COUNT(*) FROM courses) AS courses, (SELECT COUNT(*) FROM registrations) AS applicants')->fetch();
$nearFull = query('SELECT * FROM (' . COURSE_LIST . ' WHERE c.is_open = 1) AS totals
                   WHERE expired = 0 AND enrolled < capacity AND enrolled * 5 >= capacity * 4 ORDER BY start_at')->fetchAll();
page_start('ภาพรวม', true);
?>
<div class="page-heading"><h1>ภาพรวมการอบรม</h1><a class="button" href="<?= e(url('admin/course_form.php')) ?>">เพิ่มหลักสูตร</a></div>
<div class="stats">
    <div class="panel"><span>หลักสูตรทั้งหมด</span><strong><?= (int) $stats['courses'] ?></strong></div>
    <div class="panel"><span>ผู้สมัครทั้งหมด</span><strong><?= (int) $stats['applicants'] ?></strong></div>
    <div class="panel"><span>หลักสูตรใกล้เต็ม</span><strong><?= count($nearFull) ?></strong></div>
</div>
<section class="panel">
    <div class="page-heading"><h2>หลักสูตรใกล้เต็ม</h2><a href="<?= e(url('admin/courses.php')) ?>">ดูหลักสูตรทั้งหมด →</a></div>
    <?php if (!$nearFull): ?><p class="muted">ยังไม่มีหลักสูตรที่ใกล้เต็ม</p>
    <?php else: ?>
    <ul class="dashboard-list">
        <?php foreach ($nearFull as $course): ?>
            <li><a href="<?= e(url('admin/registrations.php', ['course_id' => $course['id']])) ?>"><?= e($course['title']) ?></a>
                <span>เหลือ <?= seats_left($course) ?> ที่นั่ง</span></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</section>
<?php page_end();
