<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an examiner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'examiner') {
    header("Location: ../index.php");
    exit();
}

// Check if exam_id is provided
if (!isset($_POST['exam_id'])) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = (int)$_POST['exam_id'];

// Verify that the exam belongs to the current examiner
$sql = "SELECT * FROM exams WHERE id = ? AND examiner_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $exam_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Exam not found or you don't have permission to delete it.";
    header("Location: dashboard.php");
    exit();
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Check if student_answers table exists
    $check_table_sql = "SHOW TABLES LIKE 'student_answers'";
    $check_table_result = mysqli_query($conn, $check_table_sql);
    
    if (mysqli_num_rows($check_table_result) > 0) {
        // Delete student answers first
        $sql = "DELETE FROM student_answers WHERE exam_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $exam_id);
        mysqli_stmt_execute($stmt);
    }
    
    // Delete related records in correct order (child tables first)
    
    // Delete exam notifications
    $sql = "DELETE FROM exam_notifications WHERE exam_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $exam_id);
    mysqli_stmt_execute($stmt);
    
    // Delete exam violations
    $sql = "DELETE FROM exam_violations WHERE exam_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $exam_id);
    mysqli_stmt_execute($stmt);
    
    // Delete questions
    $sql = "DELETE FROM questions WHERE exam_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $exam_id);
    mysqli_stmt_execute($stmt);

    // Delete exam results
    $sql = "DELETE FROM exam_results WHERE exam_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $exam_id);
    mysqli_stmt_execute($stmt);

    // Finally delete the exam
    $sql = "DELETE FROM exams WHERE id = ? AND examiner_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $exam_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);

    // Commit transaction
    mysqli_commit($conn);
    $_SESSION['success'] = "Exam deleted successfully!";
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    $_SESSION['error'] = "Error deleting exam: " . $e->getMessage();
}

header("Location: dashboard.php");
exit();
?> 