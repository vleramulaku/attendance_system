CREATE DATABASE IF NOT EXISTS attendance_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE attendance_system;

CREATE TABLE IF NOT EXISTS professors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    professor_name VARCHAR(100) NOT NULL,
    course VARCHAR(100) NOT NULL,
    huskylens_id INT UNSIGNED NOT NULL DEFAULT 0,
    semester_course_target INT UNSIGNED NOT NULL DEFAULT 14,
    UNIQUE KEY uq_professor_course (professor_name, course),
    KEY idx_professor_huskylens (huskylens_id),
    KEY idx_professor_course (course)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registered_students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    huskylens_id INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_student_name (student_name),
    KEY idx_student_huskylens (huskylens_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS course_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    professor_name VARCHAR(100) NOT NULL,
    course VARCHAR(100) NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NULL,
    status ENUM('ACTIVE', 'ENDED') NOT NULL DEFAULT 'ACTIVE',
    KEY idx_session_date (session_date),
    KEY idx_session_professor (professor_name),
    KEY idx_session_course (course),
    KEY idx_active_session (professor_name, course, session_date, end_time)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    course VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    entry_time TIME NOT NULL,
    exit_time TIME NULL,
    status ENUM('ENTER', 'EXIT') NOT NULL DEFAULT 'ENTER',
    KEY idx_attendance_date (date),
    KEY idx_attendance_student (student_name),
    KEY idx_attendance_course (course),
    KEY idx_open_attendance (student_name, course, date, exit_time)
) ENGINE=InnoDB;

