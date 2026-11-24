<?php
declare(strict_types=1);

function page_start(string $title, bool $admin = false, bool $turnstile = false): void
{
    $home = $admin ? 'admin/index.php' : 'public/index.php';
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    ?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | ศูนย์อบรม</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(url('public/assets/app.css')) ?>">
    <?php if ($turnstile): ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
    <script src="<?= e(url('public/assets/app.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main">ข้ามไปเนื้อหา</a>
<header class="site-header">
    <div class="shell header-inner">
        <a class="brand" href="<?= e(url($home)) ?>"><span class="brand-icon" aria-hidden="true">▤</span><span>ศูนย์อบรม</span></a>
        <nav aria-label="เมนูหลัก">
            <?php if ($admin && isset($_SESSION['admin_id'])): ?>
                <a href="<?= e(url('admin/index.php')) ?>">ภาพรวม</a>
                <a href="<?= e(url('admin/courses.php')) ?>">หลักสูตร</a>
                <a href="<?= e(url('admin/password.php')) ?>">รหัสผ่าน</a>
                <form method="post" action="<?= e(url('admin/logout.php')) ?>">
                    <?php csrf_field(); ?>
                    <button class="nav-button">ออกจากระบบ</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('public/index.php')) ?>">หลักสูตร</a>
                <a href="<?= e(url('admin/login.php')) ?>">ผู้ดูแลระบบ</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main id="main" class="shell main">
    <?php if ($flash): ?><p class="notice success" role="status"><?= e($flash) ?></p><?php endif;
}

function page_end(): void
{
    ?>
</main>
<footer class="site-footer"><div class="shell footer-inner">
    <span>ศูนย์อบรม</span>
    <a href="<?= e(url('public/privacy.php')) ?>">นโยบายความเป็นส่วนตัว</a>
</div></footer>
</body>
</html>
    <?php
}

function errors_block(array $errors): void
{
    if ($errors): ?>
        <div class="notice danger" role="alert">
            <strong>กรุณาตรวจสอบข้อมูล</strong>
            <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif;
}

function applicant_fields(array $data, array $errors, bool $admin = false): void
{
    ?>
    <div class="form-grid">
    <?php foreach (APPLICANT_FIELDS as $key => [$label, $limit]):
        $required = in_array($key, ['first_name', 'last_name', 'phone', 'email'], true);
        $type = match ($key) { 'phone' => 'tel', 'email' => 'email', default => 'text' };
        $value = $data[$key] ?? '';
        $autocomplete = ['prefix' => 'honorific-prefix', 'first_name' => 'given-name', 'last_name' => 'family-name',
            'organization' => 'organization', 'position' => 'organization-title', 'phone' => 'tel', 'email' => 'email'];
        ?>
        <div class="field <?= $key === 'note' ? 'full' : '' ?>">
            <label for="<?= e($key) ?>"><?= e($label) ?><?= $required ? ' *' : '' ?></label>
            <?php if ($key === 'note'): ?>
                <textarea id="note" name="note" rows="4" maxlength="<?= $limit ?>"><?= e($value) ?></textarea>
            <?php elseif ($key === 'food_type'): ?>
                <input id="food_type" name="food_type" maxlength="<?= $limit ?>" list="food-options" value="<?= e($value) ?>">
                <datalist id="food-options">
                    <option value="ทั่วไป"><option value="มังสวิรัติ"><option value="อิสลาม">
                </datalist>
            <?php else: ?>
                <input id="<?= e($key) ?>" name="<?= e($key) ?>" type="<?= e($type) ?>" maxlength="<?= $limit ?>"
                    value="<?= e($value) ?>" autocomplete="<?= e($autocomplete[$key]) ?>" <?= $required ? 'required' : '' ?>
                    <?= $key === 'phone' ? 'pattern="[0-9\s\-]+" inputmode="tel" data-phone aria-describedby="phone-hint"' : '' ?>
                    <?= isset($errors[$key]) ? 'aria-invalid="true"' : '' ?>>
                <?php if ($key === 'phone'): ?><small id="phone-hint">กรอกตัวเลข 9–10 หลัก ใส่ขีดหรือเว้นวรรคได้</small><?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
    <div class="consent">
        <label class="check">
            <input type="checkbox" name="pdpa_consent" value="1" required <?= !empty($data['pdpa_consent']) ? 'checked' : '' ?>>
            <span><?= $admin ? 'ผู้สมัครได้ให้ความยินยอม' : 'ข้าพเจ้ายินยอม' ?>ให้ใช้ข้อมูลเพื่อจัดการการสมัครและประสานงานการอบรม
            โดยเก็บข้อมูลสูงสุด <?= e(config('app')['retention_months']) ?> เดือนหลังสิ้นสุดการอบรม *</span>
        </label>
        <a href="<?= e(url('public/privacy.php')) ?>" target="_blank" rel="noopener">อ่านนโยบายความเป็นส่วนตัว (เปิดแท็บใหม่)</a>
    </div>
    <?php
}

function course_meta(array $course): void
{
    ?>
    <dl class="course-meta">
        <div><dt>วันเริ่มอบรม</dt><dd><?= e(thai_date($course['start_at'])) ?></dd></div>
        <div><dt>วันสิ้นสุด</dt><dd><?= e(thai_date($course['end_at'])) ?></dd></div>
        <div><dt>สถานที่</dt><dd><?= e($course['location'] ?: 'ไม่ระบุ') ?></dd></div>
        <div><dt>วิทยากร</dt><dd><?= e($course['speaker'] ?: 'ไม่ระบุ') ?></dd></div>
        <div><dt>ปิดรับสมัคร</dt><dd><?= e(thai_date($course['reg_deadline'] ?? $course['end_at'])) ?></dd></div>
        <div><dt>ที่นั่งคงเหลือ</dt><dd><?= seats_left($course) ?> / <?= (int) $course['capacity'] ?> ที่นั่ง</dd></div>
    </dl>
    <?php
}
