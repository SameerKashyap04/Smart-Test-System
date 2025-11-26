<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an examiner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'examiner') {
    header("Location: ../index.php");
    exit();
}

// Validate exam ID
if (!isset($_GET['exam_id'])) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = (int)$_GET['exam_id'];

// Ensure the exam belongs to this examiner
$exam_sql = "SELECT * FROM exams WHERE id = ? AND examiner_id = ?";
$exam_stmt = mysqli_prepare($conn, $exam_sql);
mysqli_stmt_bind_param($exam_stmt, "ii", $exam_id, $_SESSION['user_id']);
mysqli_stmt_execute($exam_stmt);
$exam = mysqli_fetch_assoc(mysqli_stmt_get_result($exam_stmt));

if (!$exam) {
    $_SESSION['error'] = "Exam not found or access denied.";
    header("Location: dashboard.php");
    exit();
}

// Fetch students who submitted the exam with their scores and submission time
$students_sql = "SELECT er.student_id, er.score, er.total_marks, er.completed_at, u.username 
                 FROM exam_results er 
                 JOIN users u ON er.student_id = u.id 
                 WHERE er.exam_id = ? 
                 ORDER BY er.completed_at DESC";
$students_stmt = mysqli_prepare($conn, $students_sql);
mysqli_stmt_bind_param($students_stmt, "i", $exam_id);
mysqli_stmt_execute($students_stmt);
$students = mysqli_stmt_get_result($students_stmt);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - <?php echo htmlspecialchars($exam['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
        }
        .container-xl { padding: 2rem; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            border-radius: 12px;
            background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
            color: white;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }
        .badge-score {
            background: linear-gradient(135deg, #2ecc71 0%, #4cd964 100%);
        }
        .btn {
            padding: 0.6rem 1rem;
            border-radius: 10px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container-xl">
        <div class="header">
            <div>
                <h1 class="h4 mb-1"><i class="fas fa-users me-2"></i>Students for: <?php echo htmlspecialchars($exam['title']); ?></h1>
                <div class="small">Subject: <?php echo htmlspecialchars($exam['subject']); ?> • Total Marks: <?php echo (int)$exam['total_marks']; ?></div>
            </div>
            <div class="d-flex gap-2">
                <a href="dashboard.php" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i> Back</a>
                <a href="notifications.php" class="btn btn-warning text-white"><i class="fas fa-bell me-1"></i> Notifications</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if (mysqli_num_rows($students) === 0): ?>
                    <div class="text-center p-5">
                        <i class="fas fa-user-clock" style="font-size: 2.5rem; color: #ccc;"></i>
                        <h5 class="mt-3 mb-1">No submissions yet</h5>
                        <p class="text-muted mb-0">Students who submit this exam will appear here with their scores and submission times.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Score</th>
                                    <th>Submitted At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; while ($row = mysqli_fetch_assoc($students)): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td>
                                            <span class="badge badge-score text-white">
                                                <?php echo (int)$row['score']; ?>/<?php echo (int)$row['total_marks']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($row['completed_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


