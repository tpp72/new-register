<?php
declare(strict_types=1);

const APPLICANT_FIELDS = [
    'prefix' => ['คำนำหน้า', 20],
    'first_name' => ['ชื่อ', 100],
    'last_name' => ['นามสกุล', 100],
    'organization' => ['หน่วยงาน', 255],
    'position' => ['ตำแหน่ง', 255],
    'phone' => ['เบอร์โทรศัพท์', 30],
    'email' => ['อีเมล', 255],
    'food_type' => ['ข้อกำหนดอาหาร', 50],
    'note' => ['หมายเหตุ', 3000],
];

function applicant_form_data(array $source): array
{
    $data = [];
    foreach (APPLICANT_FIELDS as $key => $_) {
        $data[$key] = input($source, $key);
    }
    $data['pdpa_consent'] = input($source, 'pdpa_consent') === '1' ? 1 : 0;
    return $data;
}

function validate_applicant(array &$data): array
{
    $errors = [];
    foreach (APPLICANT_FIELDS as $key => [$label, $limit]) {
        if (!mb_check_encoding($data[$key], 'UTF-8') || mb_strlen($data[$key]) > $limit) {
            $errors[$key] = "$label ต้องไม่เกิน $limit ตัวอักษร";
        }
    }
    foreach (['first_name', 'last_name'] as $key) {
        if ($data[$key] === '') {
            $errors[$key] = 'กรุณากรอก' . APPLICANT_FIELDS[$key][0];
        }
    }
    $data['phone'] = preg_replace('/[\s-]/u', '', $data['phone']) ?? '';
    if (!preg_match('/^[0-9]{9,10}$/D', $data['phone'])) {
        $errors['phone'] = 'กรุณากรอกเบอร์โทรศัพท์ 9–10 หลัก';
    }
    $data['email'] = strtolower($data['email']);
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'กรุณากรอกอีเมลให้ถูกต้อง';
    }
    if (!$data['pdpa_consent']) {
        $errors['pdpa_consent'] = 'กรุณายืนยันความยินยอมก่อนส่งข้อมูล';
    }
    return $errors;
}

function save_applicant(int $courseId, array $data, ?int $registrationId = null, bool $public = true): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // All registration inserts and course edits serialize on the parent row.
        $course = query('SELECT capacity, is_open, COALESCE(reg_deadline, end_at) AS cutoff
                         FROM courses WHERE id = ? FOR UPDATE', [$courseId])->fetch();
        if (!$course) {
            throw new DomainException('ไม่พบหลักสูตร');
        }
        if ($public && (!$course['is_open'] || $course['cutoff'] <= query('SELECT NOW()')->fetchColumn())) {
            throw new DomainException('หลักสูตรนี้ปิดรับสมัครแล้ว');
        }
        $values = array_map(static fn ($key) => $data[$key] === '' ? null : $data[$key], array_keys(APPLICANT_FIELDS));
        $values[] = $data['pdpa_consent'];
        if ($registrationId === null) {
            $count = query('SELECT COUNT(*) FROM registrations WHERE course_id = ?', [$courseId])->fetchColumn();
            if ($count >= $course['capacity']) {
                throw new DomainException('หลักสูตรนี้เต็มแล้ว');
            }
            query('INSERT INTO registrations (prefix, first_name, last_name, organization, position, phone,
                    email, food_type, note, pdpa_consent, course_id, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [...$values, $courseId, $public ? (client_ip() ?: null) : null]);
            $registrationId = (int) $pdo->lastInsertId();
        } else {
            if (!query('SELECT id FROM registrations WHERE course_id = ? AND id = ? FOR UPDATE', [$courseId, $registrationId])->fetch()) {
                throw new DomainException('ไม่พบข้อมูลผู้สมัคร');
            }
            query('UPDATE registrations SET prefix = ?, first_name = ?, last_name = ?, organization = ?, position = ?,
                    phone = ?, email = ?, food_type = ?, note = ?, pdpa_consent = ? WHERE course_id = ? AND id = ?',
                [...$values, $courseId, $registrationId]);
        }
        $pdo->commit();
        return $registrationId;
    } catch (Throwable $error) {
        $pdo->rollBack();
        if ($error instanceof PDOException && (int) ($error->errorInfo[1] ?? 0) === 1062) {
            throw new DomainException('อีเมลหรือเบอร์โทรศัพท์นี้ลงทะเบียนในหลักสูตรแล้ว');
        }
        throw $error;
    }
}

function turnstile_enabled(): bool
{
    $settings = config('turnstile');
    return $settings['site_key'] !== '' && $settings['secret_key'] !== '';
}

function verify_turnstile(string $token): bool
{
    if (!turnstile_enabled()) {
        $settings = config('turnstile');
        return local_request() && $settings['site_key'] === '' && $settings['secret_key'] === '';
    }
    if ($token === '' || strlen($token) > 2048) {
        return false;
    }
    $curl = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => config('turnstile')['secret_key'], 'response' => $token, 'remoteip' => client_ip()]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 6,
    ]);
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    $result = is_string($response) && $status === 200 ? json_decode($response, true) : null;
    if (($result['success'] ?? false) !== true || ($result['action'] ?? '') !== 'training_register') {
        return false;
    }
    $host = parse_url(config('app')['base_url'], PHP_URL_HOST);
    return local_request() || (is_string($host) && strcasecmp($host, (string) ($result['hostname'] ?? '')) === 0);
}
