<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an examiner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'examiner') {
    header("Location: ../index.php");
    exit();
}

// Fetch exams created by this examiner
$sql = "SELECT * FROM exams WHERE examiner_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$exams = mysqli_stmt_get_result($stmt);

// Count total exams
$total_exams = mysqli_num_rows($exams);

// Fetch total students who have taken exams
$students_sql = "SELECT COUNT(DISTINCT student_id) as total_students FROM exam_results er 
                 JOIN exams e ON er.exam_id = e.id 
                 WHERE e.examiner_id = ?";
$students_stmt = mysqli_prepare($conn, $students_sql);
mysqli_stmt_bind_param($students_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($students_stmt);
$students_result = mysqli_stmt_get_result($students_stmt);
$students_data = mysqli_fetch_assoc($students_result);
$total_students = $students_data['total_students'];

// Fetch total violations for this examiner's exams
$violations_sql = "SELECT COUNT(*) as total_violations FROM exam_violations ev 
                   JOIN exams e ON ev.exam_id = e.id 
                   WHERE e.examiner_id = ?";
$violations_stmt = mysqli_prepare($conn, $violations_sql);
mysqli_stmt_bind_param($violations_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($violations_stmt);
$violations_result = mysqli_stmt_get_result($violations_stmt);
$violations_data = mysqli_fetch_assoc($violations_result);
$total_violations = $violations_data['total_violations'] ?? 0;

// Fetch unread notifications count
$unread_sql = "SELECT COUNT(*) as unread_count FROM exam_notifications WHERE examiner_id = ? AND is_read = 0";
$unread_stmt = mysqli_prepare($conn, $unread_sql);
mysqli_stmt_bind_param($unread_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($unread_stmt);
$unread_data = mysqli_fetch_assoc(mysqli_stmt_get_result($unread_stmt));
$unread_notifications = $unread_data['unread_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examiner Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .exam-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .exam-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border-top: 4px solid #4e54c8;
            overflow: hidden;
        }
        .exam-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            padding: 2rem;
            border-radius: 15px;
            background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
            color: white;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .logout-btn {
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            color: white;
        }
        .create-exam-btn {
            padding: 0.85rem 1.5rem;
            background: linear-gradient(135deg, #2ecc71 0%, #4cd964 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1.5rem;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3);
            transition: all 0.3s ease;
        }
        .create-exam-btn:hover {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 204, 113, 0.4);
            color: white;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(78, 84, 200, 0.3);
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(78, 84, 200, 0.4);
            color: white;
        }
        .btn-success {
            background: linear-gradient(135deg, #2ecc71 0%, #4cd964 100%);
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3);
        }
        .btn-success:hover {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            box-shadow: 0 6px 15px rgba(46, 204, 113, 0.4);
        }
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #ff6b6b 100%);
            box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
        }
        .btn-danger:hover {
            background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%);
            box-shadow: 0 6px 15px rgba(231, 76, 60, 0.4);
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-left: 4px solid #2ecc71;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-left: 4px solid #e74c3c;
        }
        .section-title {
            color: #4e54c8;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid #4e54c8;
            display: inline-block;
        }
        .exam-info {
            background-color: #f8f9fa;
            padding: 1.2rem;
            border-radius: 10px;
            margin: 1rem 0;
            border-left: 4px solid #4e54c8;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-top: 4px solid #4e54c8;
        }
        .stats-icon {
            font-size: 2.5rem;
            color: #4e54c8;
            margin-bottom: 1rem;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #4e54c8;
        }
        .stats-label {
            color: #6c757d;
            font-weight: 600;
        }
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
        }
        .badge-primary {
            background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
        }
        .badge-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #ff6b6b 100%);
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <div>
                <h1 class="mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
                <p class="mb-0 mt-2">Manage your exams and track student performance</p>
            </div>
            <div class="d-flex gap-2">
                <a href="settings.php" class="logout-btn" style="background: rgba(255,255,255,0.25);"><i class="fas fa-cog"></i> Settings</a>
                <a href="help.php" class="logout-btn" style="background: rgba(255,255,255,0.25);"><i class="fas fa-life-ring"></i> Help</a>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Stats Section -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-file-alt stats-icon"></i>
                    <div class="stats-number"><?php echo $total_exams; ?></div>
                    <div class="stats-label">Total Exams Created</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-users stats-icon"></i>
                    <div class="stats-number"><?php echo $total_students; ?></div>
                    <div class="stats-label">Total Students Participated</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-exclamation-triangle stats-icon" style="color: #e74c3c;"></i>
                    <div class="stats-number" style="color: #e74c3c;"><?php echo $total_violations; ?></div>
                    <div class="stats-label">Total Violations Detected</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-bell stats-icon" style="color: #f39c12;"></i>
                    <div class="stats-number" style="color: #f39c12;"><?php echo $unread_notifications; ?></div>
                    <div class="stats-label">Unread Notifications</div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-3 mb-4">
            <a href="create_exam.php" class="create-exam-btn">
                <i class="fas fa-plus-circle me-2"></i> Create New Exam
            </a>
            <a href="notifications.php" class="create-exam-btn" style="background: linear-gradient(135deg, #f39c12 0%, #f1c40f 100%); box-shadow: 0 4px 10px rgba(243, 156, 18, 0.3);">
                <i class="fas fa-bell me-2"></i> View Notifications
                <?php if ($unread_notifications > 0): ?>
                    <span class="badge badge-danger ms-2"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </a>
            <a href="view_violations.php" class="create-exam-btn" style="background: linear-gradient(135deg, #e74c3c 0%, #ff6b6b 100%); box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);">
                <i class="fas fa-shield-alt me-2"></i> View Violations
            </a>
        </div>

        <h2 class="section-title"><i class="fas fa-list-alt"></i> Your Exams</h2>
        <div class="exam-grid">
            <?php while ($exam = mysqli_fetch_assoc($exams)): ?>
                <div class="exam-card">
                    <h3 class="h5 mb-3"><?php echo htmlspecialchars($exam['title']); ?></h3>
                    <span class="badge badge-primary mb-3"><?php echo htmlspecialchars($exam['subject']); ?></span>
                    <div class="exam-info">
                        <p class="mb-2"><i class="fas fa-clock"></i> <strong>Duration:</strong> <?php echo $exam['duration']; ?> minutes</p>
                        <p class="mb-2"><i class="fas fa-star"></i> <strong>Total Marks:</strong> <?php echo $exam['total_marks']; ?></p>
                    </div>
                    <div class="actions">
                        <a href="view_questions.php?exam_id=<?php echo $exam['id']; ?>" class="btn">
                            <i class="fas fa-eye me-1"></i> View Questions
                        </a>
                        <a href="view_exam_students.php?exam_id=<?php echo $exam['id']; ?>" class="btn" style="background: linear-gradient(135deg, #3498db 0%, #5dade2 100%); box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);">
                            <i class="fas fa-users me-1"></i> View Students
                        </a>
                        <a href="edit_exam.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-success">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <form action="delete_exam.php" method="POST" style="display: inline;">
                            <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this exam?')">
                                <i class="fas fa-trash-alt me-1"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 