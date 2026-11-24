USE training_reg;
SET time_zone = '+07:00';

INSERT INTO courses (title, description, speaker, location, start_at, end_at, reg_deadline, capacity)
SELECT title, description, speaker, location,
       TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL days_ahead DAY), '09:00:00'),
       TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL days_ahead DAY), '16:00:00'),
       TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL closes_in DAY), '17:00:00'), capacity
FROM (
    SELECT 'Excel สำหรับงานสำนักงาน' AS title,
           'ฝึกใช้สูตรพื้นฐาน จัดระเบียบตาราง และสรุปข้อมูลด้วย PivotTable จากโจทย์งานสำนักงาน' AS description,
           'ทีมวิทยากรทักษะดิจิทัล' AS speaker, 'ห้องอบรม A' AS location, 14 AS days_ahead, 12 AS closes_in, 30 AS capacity
    UNION ALL SELECT 'สื่อสารอย่างมืออาชีพ', 'ฝึกเขียนอีเมล ให้ข้อเสนอแนะ และสื่อสารกับทีมผ่านสถานการณ์จำลอง',
        'ทีมพัฒนาบุคลากร', 'ห้องอบรม B', 21, 19, 24
    UNION ALL SELECT 'การจัดการโครงการเบื้องต้น', 'กำหนดเป้าหมาย วางแผนงาน และติดตามความคืบหน้าด้วยเครื่องมือที่ใช้ได้จริง',
        'ทีมวางแผนโครงการ', 'ห้องอบรม A', 28, 26, 20
) AS demo
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE courses.title = demo.title);
