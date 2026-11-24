<?php
declare(strict_types=1);
define('TRAINING_NO_SESSION', true);
require __DIR__ . '/includes/bootstrap.php';
header('Location: public/index.php', true, 303);
exit;
