CREATE DATABASE IF NOT EXISTS training_reg
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE training_reg;

-- ผู้ดูแลระบบ
CREATE TABLE IF NOT EXISTS admins (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,          -- password_hash() / bcrypt
  full_name     VARCHAR(100) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- หลักสูตรอบรม
CREATE TABLE IF NOT EXISTS courses (
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
CREATE TABLE IF NOT EXISTS registrations (
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

CREATE TABLE IF NOT EXISTS rate_limits (
  action        VARCHAR(16) CHARACTER SET ascii NOT NULL,
  identity_hash CHAR(64) CHARACTER SET ascii NOT NULL,
  attempts      INT UNSIGNED NOT NULL,
  expires_at    DATETIME NOT NULL,
  PRIMARY KEY (action, identity_hash),
  INDEX idx_rate_expiry (expires_at)
) ENGINE=InnoDB;
