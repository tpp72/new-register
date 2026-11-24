<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/auth.php';

require_admin();
verify_csrf();
$_SESSION = [];
session_regenerate_id(true);
flash('ออกจากระบบแล้ว');
redirect('admin/login.php');
