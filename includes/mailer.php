<?php
declare(strict_types=1);

function send_registration_email(array $applicant, array $course): void
{
    $settings = config('mail');
    if (!$settings['enabled']) {
        return;
    }
    try {
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('Run composer install to enable SMTP.');
        }
        require_once $autoload;
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $settings['host'];
        $mail->Port = (int) $settings['port'];
        $mail->SMTPAuth = $settings['user'] !== '';
        $mail->Username = $settings['user'];
        $mail->Password = $settings['pass'];
        $mail->SMTPSecure = $mail->Port === 465
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout = 5;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($settings['from'], $settings['from_name']);
        $mail->addAddress($applicant['email']);
        $mail->Subject = 'ได้รับใบสมัครอบรมแล้ว';
        $mail->Body = "ได้รับใบสมัครของคุณเรียบร้อยแล้ว\n"
            . 'หลักสูตร: ' . $course['title'] . "\n"
            . 'วันเวลา: ' . thai_date($course['start_at']) . "\n"
            . 'สถานที่: ' . ($course['location'] ?: 'ไม่ระบุ');
        $mail->send();
    } catch (Throwable $error) {
        error_log('Training registration: SMTP delivery failed (' . get_class($error) . ').');
    }
}
