# Spec: ระบบลงทะเบียนอบรม (Training Registration System)

เวอร์ชัน 1.0 | เทคโนโลยี: PHP 8.x + MySQL 8 / MariaDB 10.x

---

## 1. ภาพรวม

ระบบเว็บสำหรับรับสมัครผู้เข้าอบรมแบบเรียบง่าย

- ผู้สมัคร **ไม่ต้องสมัครสมาชิก / ไม่ต้องยืนยันตัวตน**
- **ไม่มีการชำระเงิน**
- ผู้ดูแลระบบ (Admin) login เพื่อจัดการหลักสูตรและรายชื่อผู้สมัคร
- รองรับการใช้งานบนมือถือ (Responsive)

### ขอบเขตที่ไม่ทำ (Out of Scope)

สมาชิกผู้ใช้ทั่วไป, การชำระเงิน/ใบเสร็จ, คูปอง, Waiting list, QR เช็คอิน, ใบรับรองอัตโนมัติ, หลายระดับสิทธิ์ผู้ดูแล

---

## 2. Tech Stack

| ส่วน | เทคโนโลยี |
|---|---|
| Backend | PHP 8.1+ (ไม่ใช้ Framework หรือใช้ PDO ล้วน) |
| Database | MySQL (charset `utf8mb4`) user=root , password="", port=3307| 
| Frontend | HTML5, CSS (Bootstrap 5 ผ่าน CDN), JavaScript เล็กน้อย |
| Web Server | Apache |
| Anti-spam | Cloudflare Turnstile หรือ Google reCAPTCHA v3 |
| Email (ไม่บังคับ) | PHPMailer ผ่าน SMTP |

---

## 3. Functional Requirements

### 3.1 ฝั่งผู้สมัคร (Public)

| รหัส | ความต้องการ |
|---|---|
| P-01 | แสดงรายการหลักสูตรที่เปิดรับสมัคร: ชื่อ วันเวลา สถานที่ วิทยากร ที่นั่งคงเหลือ |
| P-02 | หน้ารายละเอียดหลักสูตร พร้อมปุ่ม "สมัครอบรม" |
| P-03 | ฟอร์มสมัคร: คำนำหน้า, ชื่อ, นามสกุล, หน่วยงาน, ตำแหน่ง, เบอร์โทร, อีเมล, ข้อกำหนดอาหาร, หมายเหตุ |
| P-04 | Validate ฝั่ง Client และ Server (ช่องบังคับ, รูปแบบอีเมล, เบอร์โทร 9–10 หลัก) |
| P-05 | ป้องกันสมัครซ้ำในหลักสูตรเดียวกัน (ตรวจจากอีเมล **หรือ** เบอร์โทร) |
| P-06 | ปิดรับอัตโนมัติเมื่อที่นั่งเต็ม หรือเลยวันปิดรับสมัคร |
| P-07 | ช่องติ๊กยินยอม PDPA (บังคับ) พร้อมลิงก์นโยบายความเป็นส่วนตัว |
| P-08 | หน้า "สมัครสำเร็จ" แสดงสรุปข้อมูลที่กรอกและรายละเอียดหลักสูตร |
| P-09 | ส่งอีเมลแจ้งว่าได้รับใบสมัครแล้ว (ไม่ต้องกดยืนยัน, เปิด/ปิดได้ใน config) |

### 3.2 ฝั่งผู้ดูแล (Admin)

| รหัส | ความต้องการ |
|---|---|
| A-01 | Login / Logout ด้วย username + password |
| A-02 | Dashboard: สรุปจำนวนหลักสูตร, จำนวนผู้สมัครรวม, หลักสูตรที่ใกล้เต็ม |
| A-03 | เพิ่ม / แก้ไข / ปิด-เปิดรับสมัคร / ลบหลักสูตร |
| A-04 | ดูรายชื่อผู้สมัครรายหลักสูตร พร้อมค้นหาและแบ่งหน้า |
| A-05 | แก้ไข / ลบผู้สมัครรายคน |
| A-06 | เพิ่มผู้สมัครแทน (กรณีสมัครผ่านทางโทรศัพท์) |
| A-07 | Export รายชื่อเป็น CSV (UTF-8 BOM เพื่อเปิดใน Excel ได้) |
| A-08 | เปลี่ยนรหัสผ่านของตนเอง |

---

## 4. โครงสร้างฐานข้อมูล

```sql
CREATE DATABASE training_reg
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE training_reg;

-- ผู้ดูแลระบบ
CREATE TABLE admins (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,          -- password_hash() / bcrypt
  full_name     VARCHAR(100) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- หลักสูตรอบรม
CREATE TABLE courses (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title         VARCHAR(255) NOT NULL,
  description   TEXT NULL,
  speaker       VARCHAR(255) NULL,
  location      VARCHAR(255) NULL,
  start_at      DATETIME NOT NULL,
  end_at        DATETIME NOT NULL,
  reg_deadline  DATETIME NULL,                  -- วันปิดรับสมัคร
  capacity      INT UNSIGNED NOT NULL,          -- จำนวนที่นั่งทั้งหมด
  is_open       TINYINT(1) NOT NULL DEFAULT 1,  -- เปิด/ปิดรับสมัครด้วยมือ
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_start (start_at),
  INDEX idx_open (is_open)
) ENGINE=InnoDB;

-- ผู้สมัคร
CREATE TABLE registrations (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id     INT UNSIGNED NOT NULL,
  prefix        VARCHAR(20)  NULL,
  first_name    VARCHAR(100) NOT NULL,
  last_name     VARCHAR(100) NOT NULL,
  organization  VARCHAR(255) NULL,
  position      VARCHAR(255) NULL,
  phone         VARCHAR(20)  NOT NULL,
  email         VARCHAR(255) NOT NULL,
  food_type     VARCHAR(50)  NULL,              -- ทั่วไป / มังสวิรัติ / อิสลาม
  note          TEXT NULL,
  pdpa_consent  TINYINT(1) NOT NULL DEFAULT 0,
  ip_address    VARCHAR(45)  NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reg_course FOREIGN KEY (course_id)
    REFERENCES courses(id) ON DELETE CASCADE,
  UNIQUE KEY uq_course_email (course_id, email),
  UNIQUE KEY uq_course_phone (course_id, phone),
  INDEX idx_course (course_id)
) ENGINE=InnoDB;
```

**ที่นั่งคงเหลือ** = `courses.capacity - COUNT(registrations)` ของหลักสูตรนั้น

```sql
SELECT c.*,
       c.capacity - COUNT(r.id) AS seats_left
FROM courses c
LEFT JOIN registrations r ON r.course_id = c.id
WHERE c.is_open = 1
  AND (c.reg_deadline IS NULL OR c.reg_deadline > NOW())
GROUP BY c.id
ORDER BY c.start_at;
```

---

## 5. โครงสร้างไฟล์

```
/training-reg
├── config/
│   ├── config.php          # DB, SMTP, Turnstile key, base URL (อยู่นอก public ถ้าทำได้)
│   └── db.php              # สร้าง PDO connection
├── includes/
│   ├── functions.php       # helper: e(), csrf, validate, flash message
│   ├── auth.php            # require_admin(), login/logout
│   └── mailer.php          # ส่งอีเมล (ไม่บังคับ)
├── public/
│   ├── index.php           # รายการหลักสูตร
│   ├── course.php          # รายละเอียดหลักสูตร (?id=)
│   ├── register.php        # ฟอร์มสมัคร + บันทึก
│   ├── success.php         # หน้าสมัครสำเร็จ
│   ├── privacy.php         # นโยบายความเป็นส่วนตัว
│   └── assets/             # css, js, images
├── admin/
│   ├── login.php
│   ├── logout.php
│   ├── index.php           # Dashboard
│   ├── courses.php         # รายการหลักสูตร
│   ├── course_form.php     # เพิ่ม/แก้ไขหลักสูตร
│   ├── registrations.php   # รายชื่อผู้สมัครรายหลักสูตร
│   ├── registration_form.php
│   ├── export.php          # Export CSV
│   └── password.php
├── sql/
│   └── schema.sql
└── spec.md
```

---

## 6. Flow การสมัคร

1. ผู้ใช้เปิด `index.php` → เลือกหลักสูตร → `course.php?id=X`
2. กด "สมัครอบรม" → `register.php?course_id=X`
3. กรอกฟอร์ม + ผ่าน Turnstile + ติ๊ก PDPA → submit (POST)
4. Server ตรวจสอบตามลำดับ:
   1. CSRF token
   2. Turnstile / reCAPTCHA
   3. Validate ข้อมูลทุกช่อง
   4. หลักสูตรยังเปิดรับ และยังไม่เลยกำหนด
   5. เริ่ม Transaction → ล็อกแถวหลักสูตร → ตรวจที่นั่ง → ตรวจซ้ำ → `INSERT`
5. สำเร็จ → redirect ไป `success.php?id=...` (ใช้ Post/Redirect/Get)
6. (ถ้าเปิดใช้) ส่งอีเมลแจ้งว่าได้รับใบสมัคร

### การป้องกันที่นั่งเกิน (Race Condition)

```php
$pdo->beginTransaction();
try {
    // ล็อกแถวหลักสูตรกันสมัครพร้อมกัน
    $c = $pdo->prepare("SELECT capacity, is_open, reg_deadline
                        FROM courses WHERE id = ? FOR UPDATE");
    $c->execute([$courseId]);
    $course = $c->fetch();

    $n = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE course_id = ?");
    $n->execute([$courseId]);

    if ($n->fetchColumn() >= $course['capacity']) {
        throw new RuntimeException('FULL');
    }

    $ins = $pdo->prepare("INSERT INTO registrations (...) VALUES (...)");
    $ins->execute([...]);   // UNIQUE KEY จะกันสมัครซ้ำให้อีกชั้น (error 1062)

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    // จัดการ 'FULL' และ SQLSTATE 23000 (duplicate) แล้วแจ้งผู้ใช้
}
```

---

## 7. Validation Rules

| ช่อง | กฎ |
|---|---|
| ชื่อ / นามสกุล | บังคับ, ไม่เกิน 100 ตัวอักษร |
| หน่วยงาน / ตำแหน่ง | ไม่เกิน 255 ตัวอักษร (กำหนดบังคับหรือไม่ก็ได้) |
| เบอร์โทร | บังคับ, ตัวเลข 9–10 หลัก (ตัด `-` และช่องว่างก่อนบันทึก) |
| อีเมล | บังคับ, `filter_var(..., FILTER_VALIDATE_EMAIL)`, เก็บเป็นตัวพิมพ์เล็ก |
| PDPA | ต้องติ๊กยินยอม |
| Turnstile | ต้องผ่านการตรวจสอบฝั่ง Server |

---

## 8. Security Requirements

- ใช้ **PDO + Prepared Statements** ทุกคำสั่ง SQL (กัน SQL Injection)
- Escape ผลลัพธ์ทุกจุดด้วย `htmlspecialchars()` (กัน XSS)
- **CSRF Token** ทุกฟอร์ม (ทั้งฝั่งสาธารณะและ Admin)
- รหัสผ่าน Admin ใช้ `password_hash()` / `password_verify()`
- Session: `session_regenerate_id(true)` หลัง login, ตั้ง `HttpOnly`, `Secure`, `SameSite=Lax`
- จำกัดการ login ผิด (เช่น 5 ครั้ง/15 นาที) กัน Brute force
- Rate limit การสมัครต่อ IP แบบง่าย (เช่น ไม่เกิน 10 ครั้ง/ชั่วโมง)
- บังคับ HTTPS
- ปิด `display_errors` บน Production และเก็บ error ลง log
- CSV Export: ป้องกัน CSV Injection โดยนำหน้าค่าที่ขึ้นต้นด้วย `= + - @` ด้วย `'`
- ไฟล์ `config/` ห้ามเข้าถึงผ่านเว็บโดยตรง
- สำรองฐานข้อมูลสม่ำเสมอ (เช่น `mysqldump` ทุกวัน)

---

## 9. PDPA

- แสดงข้อความขอความยินยอมก่อนส่งฟอร์ม ระบุวัตถุประสงค์และระยะเวลาเก็บข้อมูล
- มีหน้านโยบายความเป็นส่วนตัว (`privacy.php`)
- Admin สามารถลบข้อมูลผู้สมัครตามคำร้องขอ
- แนะนำลบ/ทำให้ไม่ระบุตัวตนของข้อมูลผู้สมัครหลังสิ้นสุดอบรม (เช่น 1 ปี)

---

## 10. Non-functional Requirements

| หัวข้อ | เป้าหมาย |
|---|---|
| ประสิทธิภาพ | หน้ารายการโหลด < 2 วินาที รองรับสมัครพร้อมกัน ~50 คน |
| Responsive | ใช้งานได้ดีบนจอ 360px ขึ้นไป |
| Browser | Chrome, Safari, Edge, Firefox เวอร์ชันล่าสุด |
| ภาษา | ภาษาไทยเป็นหลัก (UTF-8 ทุกชั้น) |
| Timezone | `Asia/Bangkok` (ตั้งทั้ง PHP และ MySQL session) |
| การแสดงวันที่ | แสดงปี พ.ศ. ในหน้าผู้ใช้ |

---

## 11. Configuration (`config/config.php`)

```php
<?php
return [
    'db' => [
        'host'    => 'localhost',
        'name'    => 'training_reg',
        'user'    => 'db_user',
        'pass'    => 'change_me',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'base_url' => 'https://example.com',
        'timezone' => 'Asia/Bangkok',
        'debug'    => false,
    ],
    'mail' => [
        'enabled'   => false,
        'host'      => 'smtp.example.com',
        'port'      => 587,
        'user'      => '',
        'pass'      => '',
        'from'      => 'no-reply@example.com',
        'from_name' => 'ระบบลงทะเบียนอบรม',
    ],
    'turnstile' => [
        'site_key'   => '',
        'secret_key' => '',
    ],
];
```

---

## 12. เกณฑ์การตรวจรับ (Acceptance Criteria)

- [ ] ผู้ใช้สมัครอบรมได้โดยไม่ต้อง login และเห็นหน้าสมัครสำเร็จ
- [ ] สมัครซ้ำด้วยอีเมลหรือเบอร์โทรเดิมในหลักสูตรเดียวกันไม่ได้
- [ ] เมื่อที่นั่งเต็ม ปุ่มสมัครถูกปิดและแสดงข้อความ "เต็มแล้ว"
- [ ] ทดสอบสมัครพร้อมกันหลายคนแล้วจำนวนไม่เกินที่นั่ง
- [ ] หลังเลยวันปิดรับสมัคร ไม่สามารถสมัครได้
- [ ] Admin เพิ่ม/แก้ไข/ลบหลักสูตรได้
- [ ] Admin ดูรายชื่อ ค้นหา แก้ไข ลบผู้สมัครได้
- [ ] Export CSV เปิดใน Excel แล้วภาษาไทยไม่เพี้ยน
- [ ] ผู้ที่ไม่ได้ login เข้าหน้า `/admin/*` ไม่ได้
- [ ] ใช้งานบนมือถือได้ครบทุกหน้า
- [ ] ไม่พบช่องโหว่พื้นฐาน (SQL Injection, XSS, CSRF)

---

## 13. ขั้นตอนพัฒนาที่แนะนำ

1. สร้างฐานข้อมูลและข้อมูลตัวอย่าง (`sql/schema.sql`)
2. ทำ config, PDO connection และ helper functions
3. ทำหน้าสาธารณะ: รายการหลักสูตร → รายละเอียด → ฟอร์มสมัคร → สำเร็จ
4. ทำระบบ login Admin
5. ทำจัดการหลักสูตร และจัดการผู้สมัคร
6. ทำ Export CSV
7. เพิ่ม Turnstile, CSRF, Rate limit และอีเมลแจ้งเตือน
8. ทดสอบตามเกณฑ์ตรวจรับ แล้ว Deploy

---

## 14. การพัฒนาต่อในอนาคต (Optional)

Waiting list, QR Code เช็คอิน, ใบรับรองอัตโนมัติ (PDF), แบบประเมินความพึงพอใจ, แจ้งเตือนผ่าน LINE, Dashboard กราฟ
