<?php
/**
 * Utility script to safely delete a user and all their associated data.
 * Usage: Open in browser (e.g., localhost/Smart_test_system/delete_user_utility.php)
 */

session_start();
require_once 'config/db.php';

// Simple authentication check (optional, but recommended for safety)
// Remove or modify this if you want to run it without being logged in as an examiner/admin
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'examiner') {
//     die("Access Denied. Please log in as an Examiner.");
// }

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = mysqli_real_escape_string($conn, trim($_POST['username']));
    
    // 1. Find the user ID
    $sql = "SELECT id, role, username FROM users WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $username_or_email, $username_or_email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        $user_id = $user['id'];
        $role = $user['role'];
        $username = $user['username'];

        mysqli_begin_transaction($conn);

        try {
            if ($role === 'student') {
                // Delete Student Data
                
                // 1. Exam Notifications
                mysqli_query($conn, "DELETE FROM exam_notifications WHERE student_id = $user_id");
                
                // 2. Exam Violations
                mysqli_query($conn, "DELETE FROM exam_violations WHERE student_id = $user_id");
                
                // 3. Exam Results
                mysqli_query($conn, "DELETE FROM exam_results WHERE student_id = $user_id");

                // 4. Student Answers
                mysqli_query($conn, "DELETE FROM student_answers WHERE student_id = $user_id");
                
            } else if ($role === 'examiner') {
                // Delete Examiner Data (More complex, deletes exams and questions)
                
                // 1. Get all exam IDs created by this examiner
                $exam_ids = [];
                $exam_res = mysqli_query($conn, "SELECT id FROM exams WHERE examiner_id = $user_id");
                while ($row = mysqli_fetch_assoc($exam_res)) {
                    $exam_ids[] = $row['id'];
                }
                
                if (!empty($exam_ids)) {
                    $ids_str = implode(',', $exam_ids);
                    
                    // Delete associated student data for these exams (optional, but good for cleanup)
                    mysqli_query($conn, "DELETE FROM exam_notifications WHERE exam_id IN ($ids_str)");
                    mysqli_query($conn, "DELETE FROM exam_violations WHERE exam_id IN ($ids_str)");
                    mysqli_query($conn, "DELETE FROM exam_results WHERE exam_id IN ($ids_str)");
                    mysqli_query($conn, "DELETE FROM student_answers WHERE exam_id IN ($ids_str)");
                    
                    // Delete Questions
                    mysqli_query($conn, "DELETE FROM questions WHERE exam_id IN ($ids_str)");
                    
                    // Delete Exams
                    mysqli_query($conn, "DELETE FROM exams WHERE examiner_id = $user_id");
                }
            }

            // Finally, Delete User
            mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");

            mysqli_commit($conn);
            $message = "User '$username' (ID: $user_id) and all related data have been successfully deleted.";
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error deleting user: " . $e->getMessage();
        }
    } else {
        $error = "User not found with that Username or Email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User Utility</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm" style="max-width: 500px; margin: 0 auto;">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">Delete User & Related Data</h4>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <p class="text-muted">
                    This tool fixes the <code>#1451 - Foreign key constraint</code> error by safely deleting all linked data (results, violations, notifications) before deleting the user.
                </p>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username or Email to Delete</label>
                        <input type="text" name="username" class="form-control" required placeholder="Enter username">
                    </div>
                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure? This cannot be undone.');">
                        Delete User Forever
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
