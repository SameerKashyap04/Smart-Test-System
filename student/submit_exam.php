<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

// Check if exam ID is provided
if (!isset($_POST['exam_id'])) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = (int)$_POST['exam_id'];

// Check if student has already taken this exam
$check_sql = "SELECT * FROM exam_results WHERE exam_id = ? AND student_id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ii", $exam_id, $_SESSION['user_id']);
mysqli_stmt_execute($check_stmt);
if (mysqli_stmt_get_result($check_stmt)->num_rows > 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch exam details and questions
$exam_sql = "SELECT * FROM exams WHERE id = ?";
$exam_stmt = mysqli_prepare($conn, $exam_sql);
mysqli_stmt_bind_param($exam_stmt, "i", $exam_id);
mysqli_stmt_execute($exam_stmt);
$exam = mysqli_fetch_assoc(mysqli_stmt_get_result($exam_stmt));

if (!$exam) {
    header("Location: dashboard.php");
    exit();
}

// Fetch questions for this exam
$questions_sql = "SELECT * FROM questions WHERE exam_id = ?";
$questions_stmt = mysqli_prepare($conn, $questions_sql);
mysqli_stmt_bind_param($questions_stmt, "i", $exam_id);
mysqli_stmt_execute($questions_stmt);
$questions = mysqli_stmt_get_result($questions_stmt);

// Calculate score
$total_score = 0;
$total_marks = 0;
$answers = [];

while ($question = mysqli_fetch_assoc($questions)) {
    $total_marks += $question['marks'];
    
    if (isset($_POST['answers'][$question['id']])) {
        $student_answer = $_POST['answers'][$question['id']];
        $answers[] = [
            'question_id' => $question['id'],
            'student_answer' => $student_answer,
            'correct_answer' => $question['correct_answer'],
            'marks' => $question['marks']
        ];
        
        if ($student_answer === $question['correct_answer']) {
            $total_score += $question['marks'];
        }
    }
}

// Calculate percentage score
$percentage_score = ($total_score / $total_marks) * 100;

// Check if total_marks column exists in exam_results table
$check_column_sql = "SHOW COLUMNS FROM exam_results LIKE 'total_marks'";
$check_column_result = mysqli_query($conn, $check_column_sql);

if (mysqli_num_rows($check_column_result) == 0) {
    // total_marks column doesn't exist, add it
    $alter_table_sql = "ALTER TABLE exam_results ADD COLUMN total_marks INT NOT NULL AFTER score";
    mysqli_query($conn, $alter_table_sql);
}

// Check if student_answers table exists
$check_table_sql = "SHOW TABLES LIKE 'student_answers'";
$check_table_result = mysqli_query($conn, $check_table_sql);

if (mysqli_num_rows($check_table_result) == 0) {
    // student_answers table doesn't exist, create it
    $create_table_sql = "CREATE TABLE student_answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        student_id INT NOT NULL,
        question_id INT NOT NULL,
        student_answer VARCHAR(255) NOT NULL,
        is_correct TINYINT(1) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id),
        FOREIGN KEY (student_id) REFERENCES users(id),
        FOREIGN KEY (question_id) REFERENCES questions(id)
    )";
    mysqli_query($conn, $create_table_sql);
}

// Insert exam result
$result_sql = "INSERT INTO exam_results (exam_id, student_id, score, total_marks, completed_at) VALUES (?, ?, ?, ?, NOW())";
$result_stmt = mysqli_prepare($conn, $result_sql);
mysqli_stmt_bind_param($result_stmt, "iiid", $exam_id, $_SESSION['user_id'], $total_score, $total_marks);
mysqli_stmt_execute($result_stmt);

// Log exam submission notification
$examiner_sql = "SELECT examiner_id FROM exams WHERE id = ?";
$examiner_stmt = mysqli_prepare($conn, $examiner_sql);
mysqli_stmt_bind_param($examiner_stmt, "i", $exam_id);
mysqli_stmt_execute($examiner_stmt);
$examiner_data = mysqli_fetch_assoc(mysqli_stmt_get_result($examiner_stmt));
$examiner_id = $examiner_data['examiner_id'];

// Get student name
$student_sql = "SELECT username FROM users WHERE id = ?";
$student_stmt = mysqli_prepare($conn, $student_sql);
mysqli_stmt_bind_param($student_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($student_stmt);
$student_data = mysqli_fetch_assoc(mysqli_stmt_get_result($student_stmt));
$student_name = $student_data['username'];

$message = "Student '{$student_name}' has submitted the exam '{$exam['title']}' with score: {$total_score}/{$total_marks} (" . number_format($percentage_score, 2) . "%)";

// Check if exam_notifications table exists, if not create it
$check_table = "SHOW TABLES LIKE 'exam_notifications'";
$check_result = mysqli_query($conn, $check_table);
if (mysqli_num_rows($check_result) == 0) {
    $create_table = "CREATE TABLE exam_notifications (
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
    )";
    mysqli_query($conn, $create_table);
}

// Insert notification
$notification_sql = "INSERT INTO exam_notifications (exam_id, student_id, examiner_id, notification_type, message) VALUES (?, ?, ?, 'exam_submitted', ?)";
$notification_stmt = mysqli_prepare($conn, $notification_sql);
mysqli_stmt_bind_param($notification_stmt, "iiis", $exam_id, $_SESSION['user_id'], $examiner_id, $message);
mysqli_stmt_execute($notification_stmt);

// Insert individual answers
$answer_sql = "INSERT INTO student_answers (exam_id, student_id, question_id, student_answer, is_correct) VALUES (?, ?, ?, ?, ?)";
$answer_stmt = mysqli_prepare($conn, $answer_sql);

foreach ($answers as $answer) {
    $is_correct = ($answer['student_answer'] === $answer['correct_answer']) ? 1 : 0;
    mysqli_stmt_bind_param($answer_stmt, "iiisi", $exam_id, $_SESSION['user_id'], $answer['question_id'], $answer['student_answer'], $is_correct);
    mysqli_stmt_execute($answer_stmt);
}

// Redirect to dashboard with success message
$_SESSION['message'] = "Exam submitted successfully! Your score: $total_score/$total_marks (" . number_format($percentage_score, 2) . "%)";
header("Location: dashboard.php");
exit(); 