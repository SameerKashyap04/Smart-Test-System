<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['exam_id']) || !isset($input['notification_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$exam_id = (int)$input['exam_id'];
$student_id = $_SESSION['user_id'];
$notification_type = mysqli_real_escape_string($conn, $input['notification_type']);
$optional_message = isset($input['message']) ? mysqli_real_escape_string($conn, $input['message']) : null;

// Get examiner ID for this exam
$examiner_sql = "SELECT examiner_id, title FROM exams WHERE id = ?";
$examiner_stmt = mysqli_prepare($conn, $examiner_sql);
mysqli_stmt_bind_param($examiner_stmt, "i", $exam_id);
mysqli_stmt_execute($examiner_stmt);
$exam_data = mysqli_fetch_assoc(mysqli_stmt_get_result($examiner_stmt));

if (!$exam_data) {
    http_response_code(404);
    echo json_encode(['error' => 'Exam not found']);
    exit();
}

$examiner_id = $exam_data['examiner_id'];
$exam_title = $exam_data['title'];

// Get student name
$student_sql = "SELECT username FROM users WHERE id = ?";
$student_stmt = mysqli_prepare($conn, $student_sql);
mysqli_stmt_bind_param($student_stmt, "i", $student_id);
mysqli_stmt_execute($student_stmt);
$student_data = mysqli_fetch_assoc(mysqli_stmt_get_result($student_stmt));
$student_name = $student_data['username'];

// Create appropriate message based on notification type
$message = '';
if ($notification_type === 'exam_started') {
    $message = "Student '{$student_name}' has started the exam '{$exam_title}'";
} elseif ($notification_type === 'exam_submitted') {
    $message = "Student '{$student_name}' has submitted the exam '{$exam_title}'";
} elseif ($notification_type === 'proctor_alert') {
    $message = $optional_message ? $optional_message : "Proctor alert triggered during exam '{$exam_title}' for student '{$student_name}'";
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid notification type']);
    exit();
}

// Check if exam_notifications table exists, if not create it
$check_table = "SHOW TABLES LIKE 'exam_notifications'";
$result = mysqli_query($conn, $check_table);
if (mysqli_num_rows($result) == 0) {
    $create_table = "CREATE TABLE exam_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        student_id INT NOT NULL,
        examiner_id INT NOT NULL,
        notification_type ENUM('exam_started', 'exam_submitted', 'proctor_alert') NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id),
        FOREIGN KEY (student_id) REFERENCES users(id),
        FOREIGN KEY (examiner_id) REFERENCES users(id)
    )";
    
    if (!mysqli_query($conn, $create_table)) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
        exit();
    }
}

// Insert notification record
$sql = "INSERT INTO exam_notifications (exam_id, student_id, examiner_id, notification_type, message) 
        VALUES (?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}

mysqli_stmt_bind_param($stmt, "iiiss", $exam_id, $student_id, $examiner_id, $notification_type, $message);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Notification logged successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to log notification: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
