<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/includes/view.php';

require_admin();
$id = input_id($_GET, 'id');
$existing = $id ? course($id) : false;
if (array_key_exists('id', $_GET) && !$existing) {
    stop_page(404, 'ไม่พบหลักสูตร', 'กรุณาเลือกหลักสูตรจากหน้าจัดการหลักสูตร');
}
$fields = [
    'title' => ['ชื่อหลักสูตร', 255],
    'description' => ['รายละเอียด', 12000],
    'speaker' => ['วิทยากร', 255],
    'location' => ['สถานที่', 255],
];
$dates = ['start_at' => 'วันและเวลาเริ่ม', 'end_at' => 'วันและเวลาสิ้นสุด', 'reg_deadline' => 'วันและเวลาปิดรับสมัคร'];
$data = $existing ?: ['capacity' => '30', 'is_open' => 1];
foreach ($dates as $key => $_) {
    $data[$key] = datetime_input($data[$key] ?? null);
}
$errors = [];
if (is_post()) {
    verify_csrf();
    foreach ($fields as $key => [$label, $limit]) {
        $data[$key] = input($_POST, $key);
        if (!mb_check_encoding($data[$key], 'UTF-8') || mb_strlen($data[$key]) > $limit) {
            $errors[] = "$label ต้องไม่เกิน $limit ตัวอักษร";
        }
    }
    if ($data['title'] === '') {
        $errors[] = 'กรุณากรอกชื่อหลักสูตร';
    }
    foreach ($dates as $key => $label) {
        $data[$key] = input($_POST, $key);
        $sqlDates[$key] = course_date($data[$key]);
        if ($sqlDates[$key] === null && ($key !== 'reg_deadline' || $data[$key] !== '')) {
            $errors[] = 'กรุณาตรวจสอบ' . $label;
        }
    }
    if ($sqlDates['start_at'] && $sqlDates['end_at'] && $sqlDates['end_at'] <= $sqlDates['start_at']) {
        $errors[] = 'เวลาสิ้นสุดต้องอยู่หลังเวลาเริ่ม';
    }
    if ($sqlDates['reg_deadline'] && $sqlDates['end_at'] && $sqlDates['reg_deadline'] > $sqlDates['end_at']) {
        $errors[] = 'เวลาปิดรับสมัครต้องไม่เกินเวลาสิ้นสุดการอบรม';
    }
    $data['capacity'] = input($_POST, 'capacity');
    $capacity = filter_var($data['capacity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);
    if ($capacity === false) {
        $errors[] = 'กรุณากรอกจำนวนที่นั่งเป็นจำนวนเต็มบวก';
    }
    $data['is_open'] = input($_POST, 'is_open') === '1' ? 1 : 0;
    if (!$errors) {
        $values = [$data['title'], $data['description'], $data['speaker'], $data['location'],
            $sqlDates['start_at'], $sqlDates['end_at'], $sqlDates['reg_deadline'], $capacity, $data['is_open']];
        if ($id) {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                if (!query('SELECT id FROM courses WHERE id = ? FOR UPDATE', [$id])->fetch()) {
                    throw new DomainException('ไม่พบหลักสูตร');
                }
                if ($capacity < query('SELECT COUNT(*) FROM registrations WHERE course_id = ?', [$id])->fetchColumn()) {
                    throw new DomainException('จำนวนที่นั่งต้องไม่น้อยกว่าจำนวนผู้สมัครปัจจุบัน');
                }
                query('UPDATE courses SET title = ?, description = ?, speaker = ?, location = ?, start_at = ?,
                       end_at = ?, reg_deadline = ?, capacity = ?, is_open = ? WHERE id = ?', [...$values, $id]);
                $pdo->commit();
            } catch (Throwable $error) {
                $pdo->rollBack();
                if (!$error instanceof DomainException) {
                    throw $error;
                }
                $errors[] = $error->getMessage();
            }
        } else {
            query('INSERT INTO courses (title, description, speaker, location, start_at, end_at, reg_deadline, capacity, is_open)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $values);
        }
        if (!$errors) {
            flash('บันทึกหลักสูตรแล้ว');
            redirect('admin/courses.php');
        }
    }
}
$title = $id ? 'แก้ไขหลักสูตร' : 'เพิ่มหลักสูตร';
page_start($title, true);
?>
<a class="back" href="<?= e(url('admin/courses.php')) ?>">← จัดการหลักสูตร</a>
<section class="panel form-panel">
    <h1><?= e($title) ?></h1>
    <?php errors_block($errors); ?>
    <form method="post">
        <?php csrf_field(); ?>
        <div class="form-grid">
            <?php foreach ($fields as $key => [$label, $limit]): ?>
                <div class="field <?= in_array($key, ['title', 'description'], true) ? 'full' : '' ?>">
                    <label for="<?= e($key) ?>"><?= e($label) ?><?= $key === 'title' ? ' *' : '' ?></label>
                    <?php if ($key === 'description'): ?>
                        <textarea id="description" name="description" rows="5" maxlength="<?= $limit ?>"><?= e($data[$key] ?? '') ?></textarea>
                    <?php else: ?>
                        <input id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($data[$key] ?? '') ?>" maxlength="<?= $limit ?>" <?= $key === 'title' ? 'required' : '' ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php foreach ($dates as $key => $label): ?>
                <div class="field">
                    <label for="<?= e($key) ?>"><?= e($label) ?><?= $key === 'reg_deadline' ? '' : ' *' ?></label>
                    <input id="<?= e($key) ?>" name="<?= e($key) ?>" type="datetime-local" value="<?= e($data[$key]) ?>" <?= $key === 'reg_deadline' ? '' : 'required' ?>>
                </div>
            <?php endforeach; ?>
            <div class="field"><label for="capacity">จำนวนที่นั่ง *</label><input id="capacity" name="capacity" type="number" min="1" max="4294967295" value="<?= e($data['capacity']) ?>" required></div>
        </div>
        <label class="check"><input type="checkbox" name="is_open" value="1" <?= $data['is_open'] ? 'checked' : '' ?>> เปิดรับสมัคร</label>
        <div class="actions"><button class="button">บันทึกหลักสูตร</button><a class="button secondary" href="<?= e(url('admin/courses.php')) ?>">ยกเลิก</a></div>
    </form>
</section>
<?php page_end();
