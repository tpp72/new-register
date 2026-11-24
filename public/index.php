<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/view.php';

$courses = query(COURSE_LIST . ' WHERE c.is_open = 1 AND COALESCE(c.reg_deadline, c.end_at) > NOW()
                                ORDER BY c.start_at, c.id')->fetchAll();
page_start('หลักสูตรอบรม');
?>
<section class="hero">
    <div class="hero-copy">
        <h1>หลักสูตรอบรม</h1>
        <p>ดูวันอบรมและที่นั่งคงเหลือ แล้วสมัครได้โดยไม่ต้องสร้างบัญชี</p>
    </div>
    <div class="hero-art" aria-hidden="true"></div>
</section>
<section class="catalog" aria-labelledby="catalog-heading">
    <div class="section-heading"><h2 id="catalog-heading">หลักสูตรที่เปิดรับสมัคร</h2><span><?= count($courses) ?> หลักสูตร</span></div>
    <?php if (!$courses): ?>
        <div class="panel empty">ขณะนี้ไม่มีหลักสูตรที่เปิดรับสมัคร</div>
    <?php else: ?>
        <div class="course-grid">
            <?php foreach ($courses as $course): $left = seats_left($course); ?>
                <article class="course-card">
                    <div class="course-card-top">
                        <span class="course-icon" aria-hidden="true">▤</span>
                        <span class="status <?= $left ? 'available' : 'closed' ?>"><?= $left ? 'เหลือ ' . $left . ' ที่นั่ง' : 'เต็มแล้ว' ?></span>
                    </div>
                    <h3><?= e($course['title']) ?></h3>
                    <dl class="card-meta">
                        <div><dt>วันอบรม</dt><dd><?= e(thai_date($course['start_at'])) ?></dd></div>
                        <div><dt>สถานที่</dt><dd><?= e($course['location'] ?: 'ไม่ระบุ') ?></dd></div>
                        <div><dt>วิทยากร</dt><dd><?= e($course['speaker'] ?: 'ไม่ระบุ') ?></dd></div>
                    </dl>
                    <a class="detail-link" href="<?= e(url('public/course.php', ['id' => $course['id']])) ?>">ดูรายละเอียด <span aria-hidden="true">→</span></a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php page_end();
