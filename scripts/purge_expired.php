<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/includes/bootstrap.php';

$months = filter_var(config('app')['retention_months'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($months === false) {
    fwrite(STDERR, "Invalid retention period.\n");
    exit(1);
}
$removed = query('DELETE r FROM registrations r JOIN courses c ON c.id = r.course_id
                  WHERE TIMESTAMPADD(MONTH, ?, c.end_at) <= NOW()', [$months])->rowCount();
query('DELETE FROM rate_limits WHERE expires_at <= NOW()');
printf("Removed %d expired registrations and cleared expired rate limits.\n", $removed);
