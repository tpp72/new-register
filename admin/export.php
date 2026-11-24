<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/auth.php';

require_admin();
$courseId = input_id($_GET, 'course_id');
if (!course($courseId)) {
    stop_page(404, 'ไม่พบหลักสูตร', 'กรุณาเลือกหลักสูตรจากหน้าจัดการหลักสูตร');
}
$rows = query('SELECT * FROM registrations WHERE course_id = ? ORDER BY created_at, id', [$courseId]);
session_write_close();
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="registrations-' . $courseId . '.csv"');
$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");
$labels = array_column(APPLICANT_FIELDS, 0);
fputcsv($output, [...$labels, 'ให้ความยินยอม', 'วันที่สมัคร'], ',', '"', '', "\r\n");
while ($row = $rows->fetch()) {
    $fields = array_map(static function (string $key) use ($row): string {
        $value = (string) $row[$key];
        return preg_match('/^(?:[\t\r\n]|\s*[=+@-])/u', $value) ? "'" . $value : $value;
    }, array_keys(APPLICANT_FIELDS));
    fputcsv($output, [...$fields, $row['pdpa_consent'] ? 'ยินยอม' : '', thai_date($row['created_at'])], ',', '"', '', "\r\n");
}
fclose($output);
