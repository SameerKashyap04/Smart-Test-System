<?php
/**
 * DANGER: This script deletes ALL user data from the system.
 * Use with extreme caution.
 */

session_start();
require_once 'config/db.php';

// Optional: Restrict to logged-in admins/examiners
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'examiner') {
//     die("Access Denied.");
// }

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset']) && $_POST['confirm_reset'] === 'yes') {
    
    mysqli_begin_transaction($conn);

    try {
        // 1. Delete Student Activity Data
        mysqli_query($conn, "DELETE FROM exam_notifications");
        mysqli_query($conn, "DELETE FROM exam_violations");
        mysqli_query($conn, "DELETE FROM student_answers");
        mysqli_query($conn, "DELETE FROM exam_results");
        
        // 2. Delete Exam Content
        mysqli_query($conn, "DELETE FROM questions");
        mysqli_query($conn, "DELETE FROM exams");
        
        // 3. Delete Users
        mysqli_query($conn, "DELETE FROM users");
        
        // 4. Reset Auto Increments (Optional, helps start IDs from 1)
        mysqli_query($conn, "ALTER TABLE users AUTO_INCREMENT = 1");
        mysqli_query($conn, "ALTER TABLE exams AUTO_INCREMENT = 1");
        mysqli_query($conn, "ALTER TABLE questions AUTO_INCREMENT = 1");
        
        mysqli_commit($conn);
        $message = "SYSTEM RESET SUCCESSFUL. All users and data have been deleted.";
        
        // Clear session and logout
        session_destroy();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "System Reset Failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Master Reset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
    <div class="container mt-5">
        <div class="card bg-danger text-white shadow-lg border-0" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header text-center">
                <h2 class="mb-0">⚠️ DANGER ZONE ⚠️</h2>
            </div>
            <div class="card-body text-center p-5">
                <?php if ($message): ?>
                    <div class="alert alert-success text-dark"><?php echo $message; ?></div>
                    <a href="index.php" class="btn btn-light mt-3">Go to Home</a>
                <?php else: ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-warning text-dark"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <h4 class="card-title">MASTER SYSTEM RESET</h4>
                    <p class="card-text mb-4">
                        This action will delete <strong>ALL USERS</strong>, <strong>ALL EXAMS</strong>, 
                        and <strong>ALL RESULTS</strong> from the database.
                        <br><br>
                        <strong>This cannot be undone.</strong>
                    </p>

                    <form method="POST">
                        <div class="mb-3 form-check d-inline-block text-start">
                            <input type="checkbox" class="form-check-input" id="confirm" name="confirm_reset" value="yes" required>
                            <label class="form-check-label" for="confirm">I understand that all data will be lost forever.</label>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-outline-light btn-lg fw-bold">
                                🗑️ DELETE EVERYTHING
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
