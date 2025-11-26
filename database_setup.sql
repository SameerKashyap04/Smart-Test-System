-- Smart Test System - Complete Database Setup
-- This is the ONLY SQL file you need for a fresh database setup

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS smart_test_system;
USE smart_test_system;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('student', 'examiner') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create exams table
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    examiner_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    subject VARCHAR(100) NOT NULL,
    duration INT NOT NULL,
    total_marks INT NOT NULL,
    login_id VARCHAR(20) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (examiner_id) REFERENCES users(id)
);

-- Create questions table
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    image_path VARCHAR(255),
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_answer ENUM('a', 'b', 'c', 'd') NOT NULL,
    marks INT NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id)
);

-- Create exam_results table
CREATE TABLE IF NOT EXISTS exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    score INT NOT NULL,
    total_marks INT NOT NULL DEFAULT 0,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (exam_id) REFERENCES exams(id)
);

-- Create exam_notifications table
CREATE TABLE IF NOT EXISTS exam_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    examiner_id INT NOT NULL,
    notification_type ENUM('exam_started', 'exam_submitted') NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (examiner_id) REFERENCES users(id)
);

-- Create exam_violations table
CREATE TABLE IF NOT EXISTS exam_violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    violation_type ENUM('tab_switch', 'copy_paste', 'right_click', 'fullscreen_exit', 'time_exceeded', 'other') NOT NULL,
    violation_description TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_resolved TINYINT(1) DEFAULT 0,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add performance indexes
CREATE INDEX IF NOT EXISTS idx_examiner_notifications ON exam_notifications(examiner_id, is_read, created_at);
CREATE INDEX IF NOT EXISTS idx_notification_type ON exam_notifications(notification_type);
CREATE INDEX IF NOT EXISTS idx_exam_violations_exam ON exam_violations(exam_id);
CREATE INDEX IF NOT EXISTS idx_exam_violations_student ON exam_violations(student_id);
CREATE INDEX IF NOT EXISTS idx_exam_violations_type ON exam_violations(violation_type);
CREATE INDEX IF NOT EXISTS idx_exam_violations_timestamp ON exam_violations(timestamp);

-- Success message
SELECT 'Smart Test System database setup completed successfully!' as message;
