<?php
declare(strict_types=1);

const COURSE_LIST = 'SELECT c.*, (SELECT COUNT(*) FROM registrations r WHERE r.course_id = c.id) AS enrolled,
                            (COALESCE(c.reg_deadline, c.end_at) <= NOW()) AS expired FROM courses c';

function course(int $id): array|false
{
    return query(COURSE_LIST . ' WHERE c.id = ?', [$id])->fetch();
}

function seats_left(array $course): int
{
    return max(0, (int) $course['capacity'] - (int) $course['enrolled']);
}

function course_closed_reason(array $course): string
{
    return match (true) {
        seats_left($course) === 0 => 'เต็มแล้ว',
        !(bool) $course['is_open'] => 'ปิดรับสมัคร',
        (bool) $course['expired'] => 'หมดเขตรับสมัคร',
        default => '',
    };
}

function course_date(string $value): ?string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
    if (!$date || $date->format('Y-m-d\TH:i') !== $value || (int) $date->format('Y') < 1000) {
        return null;
    }
    return $date->format('Y-m-d H:i:s');
}
