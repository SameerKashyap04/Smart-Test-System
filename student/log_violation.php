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

// Validate input - support both old and new format
if (!isset($input['type']) && (!isset($input['exam_id']) || !isset($input['student_id']) || !isset($input['violation_type']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

// Handle new voice detection format
if (isset($input['type'])) {
    // New format from voice detection
    $exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
    $student_id = (int)$_SESSION['user_id'];
    $violation_type = mysqli_real_escape_string($conn, $input['type']);
    $violation_description = isset($input['description']) ? mysqli_real_escape_string($conn, $input['description']) : '';
    $violation_number = isset($input['violation_number']) ? (int)$input['violation_number'] : 1;
    $timestamp = isset($input['timestamp']) ? date('Y-m-d H:i:s', $input['timestamp'] / 1000) : date('Y-m-d H:i:s');
} else {
    // Old format
    $exam_id = (int)$input['exam_id'];
    $student_id = (int)$input['student_id'];
    $violation_type = mysqli_real_escape_string($conn, $input['violation_type']);
    $violation_description = isset($input['description']) ? mysqli_real_escape_string($conn, $input['description']) : '';
    $violation_number = isset($input['violation_count']) ? (int)$input['violation_count'] : 1;
    $timestamp = isset($input['timestamp']) ? $input['timestamp'] : date('Y-m-d H:i:s');
    
    // Verify the student is taking the correct exam
    if ($student_id != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit();
    }
}

// Check if exam_violations table exists, if not create it (with detailed description)
$check_table = "SHOW TABLES LIKE 'exam_violations'";
$result = mysqli_query($conn, $check_table);
if (mysqli_num_rows($result) == 0) {
    $create_table = "CREATE TABLE exam_violations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        student_id INT NOT NULL,
        violation_type VARCHAR(50) NOT NULL,
        violation_count INT DEFAULT 1,
        violation_description TEXT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id),
        FOREIGN KEY (student_id) REFERENCES users(id)
    )";
    
    if (!mysqli_query($conn, $create_table)) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
        exit();
    }
} else {
    // Ensure required columns exist
    $columns = [];
    $columnsResult = mysqli_query($conn, "SHOW COLUMNS FROM exam_violations");
    while ($row = mysqli_fetch_assoc($columnsResult)) {
        $columns[$row['Field']] = true;
    }
    if (!isset($columns['violation_description'])) {
        @mysqli_query($conn, "ALTER TABLE exam_violations ADD COLUMN violation_description TEXT AFTER violation_count");
    }
    if (!isset($columns['violation_count'])) {
        @mysqli_query($conn, "ALTER TABLE exam_violations ADD COLUMN violation_count INT DEFAULT 1 AFTER violation_type");
    }
}

// Insert violation record
$sql = "INSERT INTO exam_violations (exam_id, student_id, violation_type, violation_count, violation_description, timestamp) 
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}

mysqli_stmt_bind_param($stmt, "iisiss", $exam_id, $student_id, $violation_type, $violation_number, $violation_description, $timestamp);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Violation logged successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to log violation: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
