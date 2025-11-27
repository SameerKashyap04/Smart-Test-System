<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $exam_id = isset($data['exam_id']) ? (int)$data['exam_id'] : 0;
    $secret_key = isset($data['secret_key']) ? trim($data['secret_key']) : '';

    if ($exam_id <= 0 || empty($secret_key)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit();
    }

    // Fetch exam's secret key
    $sql = "SELECT secret_key FROM exams WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $exam_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['secret_key'] === $secret_key) {
            // Success! Set session flag
            $_SESSION['exam_unlocked_' . $exam_id] = true;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Secret Key']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Exam not found']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
