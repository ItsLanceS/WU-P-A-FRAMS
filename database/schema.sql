-- ============================================================
-- FRAMS - Facial Recognition Attendance Management System
-- Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS frams_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE frams_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS attendance_events;
DROP TABLE IF EXISTS face_data;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Users table (admin, teacher, student login accounts)
-- --------------------------------------------------------
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(100)  UNIQUE NOT NULL,
    password    VARCHAR(255)  NOT NULL,
    role        ENUM('admin','teacher','student') NOT NULL DEFAULT 'student',
    is_active   TINYINT(1)    DEFAULT 1,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Attendance events table (teacher/admin controlled schedule)
-- --------------------------------------------------------
CREATE TABLE attendance_events (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(120)  NOT NULL,
    event_date     DATE          NOT NULL,
    time_in_start  TIME          NOT NULL,
    time_out_end   TIME          NOT NULL,
    late_time      TIME          NOT NULL,
    is_active      TINYINT(1)    NOT NULL DEFAULT 1,
    created_by     INT           DEFAULT NULL,
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_date (event_date),
    INDEX idx_event_active (is_active),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Students table
-- --------------------------------------------------------
CREATE TABLE students (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  VARCHAR(20)   UNIQUE NOT NULL,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(100),
    course      VARCHAR(100),
    year        VARCHAR(10),
    section     VARCHAR(20),
    photo       VARCHAR(255),
    user_id     INT           DEFAULT NULL,
    is_active   TINYINT(1)    DEFAULT 1,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Face data table (images + encodings per student)
-- --------------------------------------------------------
CREATE TABLE face_data (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT           NOT NULL,
    image_path  VARCHAR(255)  NOT NULL,
    encoding    LONGTEXT,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Attendance table
-- --------------------------------------------------------
CREATE TABLE attendance (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT           NOT NULL,
    event_id    INT           DEFAULT NULL,
    date        DATE          NOT NULL,
    time_in     TIME          DEFAULT NULL,
    time_out    TIME          DEFAULT NULL,
    late_cutoff_time TIME     DEFAULT NULL,
    status      ENUM('present','absent','late','excused') NOT NULL DEFAULT 'present',
    marked_by   ENUM('facial_recognition','manual')       DEFAULT 'facial_recognition',
    confidence  DECIMAL(5,2)  DEFAULT NULL,
    notes       TEXT,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES attendance_events(id) ON DELETE SET NULL,
    UNIQUE KEY unique_attendance_event (student_id, date, event_id),
    INDEX idx_attendance_event (event_id)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Default seed data
-- Password for all accounts: password
-- Hash generated with: password_hash('password', PASSWORD_BCRYPT)
-- --------------------------------------------------------
INSERT INTO users (name, email, password, role) VALUES
('Administrator',  'admin@frams.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Demo Teacher',   'teacher@frams.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher');

-- Sample students
INSERT INTO students (student_id, name, email, course, year, section) VALUES
('2024-0001', 'Juan Dela Cruz',   'juan@student.com',  'BSCS', '2nd', 'A'),
('2024-0002', 'Maria Santos',     'maria@student.com', 'BSIT', '1st', 'B'),
('2024-0003', 'Pedro Reyes',      'pedro@student.com', 'BSCS', '3rd', 'A');
