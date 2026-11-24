# ระบบลงทะเบียนอบรม

เว็บ PHP 8.1+ สำหรับเปิดรับสมัครอบรมและจัดการผู้สมัครตาม [spec.md](spec.md)

## เปิดบน XAMPP

1. เปิด Apache และ MySQL/MariaDB ให้ฐานข้อมูลฟังที่ `127.0.0.1:3307` ตามสเปก เมื่อใช้ XAMPP ที่มี MariaDB แยกใน `.runtime/mysql` ให้เปิดฐานข้อมูลด้วยคำสั่งด้านล่างหากปิดไปแล้ว
2. จากโฟลเดอร์โครงการ นำเข้าโครงสร้างและข้อมูลตัวอย่างตามต้องการ

   ```powershell
   & C:/xampp/mysql/bin/mysql.exe --protocol=TCP -h 127.0.0.1 -P 3307 -u root --default-character-set=utf8mb4 -e 'SOURCE sql/schema.sql'
   & C:/xampp/mysql/bin/mysql.exe --protocol=TCP -h 127.0.0.1 -P 3307 -u root --default-character-set=utf8mb4 -e 'SOURCE sql/demo.sql'
   ```

3. เปิด [หน้าหลักสูตร](http://localhost/new-register/) หรือ [หน้าผู้ดูแล](http://localhost/new-register/admin/) แล้วใช้บัญชีด้านล่าง

หากใช้ฐานข้อมูลที่ตั้งไว้ในโฟลเดอร์โครงการ ให้ใช้คำสั่งนี้เพื่อเปิดอีกครั้งหลังรีสตาร์ตเครื่อง

```powershell
$ini = (Resolve-Path .runtime/mysql/my.ini).Path
Start-Process C:/xampp/mysql/bin/mysqld.exe -ArgumentList "--defaults-file=`"$ini`"",'--bind-address=127.0.0.1' -WindowStyle Hidden
```

## บัญชีผู้ดูแลระบบบน XAMPP เครื่องนี้

สร้างบัญชีชื่อ `admin` แล้ว ใช้ที่ [หน้าเข้าสู่ระบบผู้ดูแล](http://localhost/new-register/admin/login.php) เปิด PowerShell ในโฟลเดอร์โครงการเพื่อดูรหัสผ่านเริ่มต้น

```powershell
Get-Content -LiteralPath .runtime/admin-initial-password.txt
```

หลังเข้าสู่ระบบ ให้เปลี่ยนรหัสผ่านจากเมนู **รหัสผ่าน** แล้วลบไฟล์ `.runtime/admin-initial-password.txt` ซึ่งเก็บรหัสผ่านเริ่มต้นและอยู่นอกการติดตามของ Git

เมื่อติดตั้งบนฐานข้อมูลใหม่ ให้สร้างบัญชีผู้ดูแลด้วยคำสั่งนี้ ระบบจะแสดงรหัสผ่านสุ่มให้หนึ่งครั้ง

```powershell
& C:/xampp/php/php.exe scripts/create_admin.php admin
```

## ตั้งค่าก่อนเปิดใช้งานจริง

- สร้าง `config/local.php` ที่คืนค่าอาร์เรย์ PHP สำหรับค่าที่ต้องการแก้จาก `config/config.php` ไฟล์นี้ถูกละเว้นโดย Git ตั้ง `app.environment` เป็น `production` และ `app.base_url` เป็น URL แบบ HTTPS รวม path หากติดตั้งในโฟลเดอร์ย่อย ใช้บัญชีฐานข้อมูลเฉพาะแอปที่มีรหัสผ่าน และตั้ง `app.privacy_contact` ให้เป็นช่องทางติดต่อผู้จัดอบรม
- เปิด `mod_rewrite` และให้ Apache ใช้ `.htaccess` ของโครงการ ตรวจว่าผ่าน URL ได้เฉพาะหน้าเว็บใน `public/` กับ `admin/` และหน้าเข้าเว็บไซต์ จัดเก็บฐานข้อมูลและข้อมูลสำรองนอก web root เปิด TLS และตั้ง HTTP virtual host ให้เปลี่ยนเส้นทางไป HTTPS ทั้งเว็บไซต์
- ตั้ง `turnstile.site_key` และ `turnstile.secret_key` ของโดเมนจริง ระบบตรวจ token กับ Cloudflare ทุกครั้งที่ส่งฟอร์ม เมื่อรันแบบ `local` และเปิดจาก loopback โดยเว้นคีย์ทั้งคู่ ระบบใช้ฟอร์มทดสอบได้ทันที [เอกสาร Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/)
- หากต้องส่งอีเมล ให้รัน `composer install --no-dev` จากโฟลเดอร์โครงการ ตั้งค่า SMTP ในกลุ่ม `mail` แล้วเปิด `mail.enabled` [เอกสาร PHPMailer](https://github.com/PHPMailer/PHPMailer)
- ตั้งงานรายวันให้รัน `php scripts/purge_expired.php` เพื่อลบข้อมูลผู้สมัครตามอายุในนโยบายความเป็นส่วนตัว รวมถึงข้อมูลจำกัดคำขอที่หมดอายุ ตั้งงานสำรองรายวันด้วย `mysqldump --single-transaction training_reg` โดยระบุการเชื่อมต่อฐานข้อมูลของระบบและจัดการอายุไฟล์สำรองให้สอดคล้องกับนโยบายเก็บข้อมูล

หากหลักสูตรไม่มีวันปิดรับสมัคร ระบบใช้เวลาสิ้นสุดการอบรมเป็นเวลาปิดรับ ผู้ดูแลยังเพิ่มผู้สมัครแทนได้เมื่อหลักสูตรปิดรับและยังมีที่นั่ง โดยต้องได้รับความยินยอมจากผู้สมัครก่อน

