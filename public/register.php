<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/view.php';
require dirname(__DIR__) . '/includes/mailer.php';

$courseId = input_id($_GET, 'course_id');
$course = course($courseId);
if (!$course) {
    stop_page(404, 'ไม่พบหลักสูตร', 'กรุณาเลือกหลักสูตรจากหน้ารายการ');
}
$data = [];
$errors = [];
$closed = course_closed_reason($course);
$challenge = turnstile_enabled();
$canRegister = $challenge || verify_turnstile('');
if (is_post()) {
    verify_csrf();
    $data = applicant_form_data($_POST);
    if (!$canRegister) {
        http_response_code(503);
        $errors[] = 'ระบบตรวจสอบความปลอดภัยไม่พร้อมใช้งาน กรุณาลองอีกครั้งภายหลัง';
    } elseif (!consume_rate_limit('registration', client_ip(), 10, 3600)) {
        http_response_code(429);
        $errors[] = 'ส่งข้อมูลเกินจำนวนที่กำหนด กรุณาลองใหม่ภายหลัง';
    } elseif (!verify_turnstile(input($_POST, 'cf-turnstile-response'))) {
        $errors[] = 'กรุณาผ่านการตรวจสอบความปลอดภัยแล้วลองใหม่';
    } else {
        $errors = validate_applicant($data);
        if (!$errors) {
            try {
                $id = save_applicant($courseId, $data);
            } catch (DomainException $error) {
                $errors[] = $error->getMessage();
                $updated = course($courseId);
                $closed = $updated ? course_closed_reason($updated) : 'ไม่พบหลักสูตร';
            }
            if (isset($id)) {
                // Limit access to a confirmation created by the same browser session.
                $confirmation = bin2hex(random_bytes(24));
                $confirmations = $_SESSION['registration_confirmations'] ?? [];
                $confirmations[$confirmation] = $id;
                $_SESSION['registration_confirmations'] = array_slice($confirmations, -10, null, true);
                session_write_close();
                send_registration_email($data, $course);
                redirect('public/success.php', ['id' => $confirmation]);
            }
        }
    }
}
$showForm = $closed === '' && $canRegister;
if (!$canRegister && !$errors) {
    http_response_code(503);
    $errors[] = 'ระบบตรวจสอบความปลอดภัยไม่พร้อมใช้งาน กรุณาลองอีกครั้งภายหลัง';
}
page_start('สมัครอบรม', false, $showForm && $challenge);
?>
<a class="back" href="<?= e(url('public/course.php', ['id' => $courseId])) ?>">← รายละเอียดหลักสูตร</a>
<section class="panel form-panel">
    <h1>สมัครอบรม</h1>
    <p class="subtitle"><?= e($course['title']) ?> · <?= e(thai_date($course['start_at'])) ?></p>
    <?php errors_block($errors); ?>
    <?php if ($closed): ?>
        <p class="notice"><?= e($closed) ?></p>
    <?php elseif ($showForm): ?>
        <p class="muted">กรอกข้อมูลในช่องที่มีเครื่องหมาย *</p>
        <form method="post">
            <?php csrf_field(); applicant_fields($data, $errors); ?>
            <?php if ($challenge): ?><div class="cf-turnstile" data-sitekey="<?= e(config('turnstile')['site_key']) ?>" data-action="training_register" data-size="flexible"></div><?php endif; ?>
            <div class="actions"><button class="button">ยืนยันการสมัคร</button></div>
        </form>
    <?php endif; ?>
</section>
<?php page_end();
