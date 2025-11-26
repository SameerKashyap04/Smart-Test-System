<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/db.php';

echo "<h1>Database Setup</h1>";

// SQL queries to create tables
$queries = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        role ENUM('student', 'examiner') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_verified TINYINT(1) DEFAULT 0,
        otp_code VARCHAR(6) DEFAULT NULL,
        otp_expiry DATETIME DEFAULT NULL
    )",
    "CREATE TABLE IF NOT EXISTS exams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        examiner_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        description TEXT,
        subject VARCHAR(100) NOT NULL,
        duration INT NOT NULL,
        total_marks INT NOT NULL,
        login_id VARCHAR(20) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        voice_detection_enabled TINYINT(1) DEFAULT 1,
        voice_sensitivity DECIMAL(3,2) DEFAULT 0.30,
        voice_violation_threshold INT DEFAULT 2,
        voice_max_violations INT DEFAULT 5,
        microphone_required TINYINT(1) DEFAULT 1,
        FOREIGN KEY (examiner_id) REFERENCES users(id)
    )",
    "CREATE TABLE IF NOT EXISTS questions (
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
    )",
    "CREATE TABLE IF NOT EXISTS exam_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        exam_id INT NOT NULL,
        score INT NOT NULL,
        total_marks INT NOT NULL DEFAULT 0,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id),
        FOREIGN KEY (exam_id) REFERENCES exams(id)
    )",
    "CREATE TABLE IF NOT EXISTS exam_notifications (
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
    )",
    "CREATE TABLE IF NOT EXISTS exam_violations (
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
    )"
];

$success = true;
foreach ($queries as $query) {
    if (!mysqli_query($conn, $query)) {
        echo "<p style='color: red;'>Error: " . mysqli_error($conn) . "</p>";
        $success = false;
    }
}

if ($success) {
    echo "<p style='color: green;'>All tables created successfully!</p>";
    echo "<p>You can now <a href='auth/register.php'>Register</a>.</p>";
    // Delete this script after use for security
    // unlink(__FILE__); 
}
?>

