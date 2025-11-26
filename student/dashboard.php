<?php
session_start();
require_once '../config/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

// Debug session variables
echo "<!-- Debug: User ID: " . $_SESSION['user_id'] . ", Role: " . $_SESSION['role'] . " -->";

// Fetch available exams with error handling
$sql = "SELECT e.*, u.username as examiner_name 
        FROM exams e 
        JOIN users u ON e.examiner_id = u.id 
        WHERE e.id NOT IN (
            SELECT exam_id 
            FROM exam_results 
            WHERE student_id = ?
        )";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("Error preparing statement: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
if (!mysqli_stmt_execute($stmt)) {
    die("Error executing statement: " . mysqli_stmt_error($stmt));
}
$exams = mysqli_stmt_get_result($stmt);
if (!$exams) {
    die("Error getting result: " . mysqli_error($conn));
}

// Fetch completed exams with error handling
$completed_sql = "SELECT e.*, er.score, u.username as examiner_name 
                 FROM exams e 
                 JOIN exam_results er ON e.id = er.exam_id 
                 JOIN users u ON e.examiner_id = u.id 
                 WHERE er.student_id = ?";
$completed_stmt = mysqli_prepare($conn, $completed_sql);
if (!$completed_stmt) {
    die("Error preparing completed exams statement: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($completed_stmt, "i", $_SESSION['user_id']);
if (!mysqli_stmt_execute($completed_stmt)) {
    die("Error executing completed exams statement: " . mysqli_stmt_error($completed_stmt));
}
$completed_exams = mysqli_stmt_get_result($completed_stmt);
if (!$completed_exams) {
    die("Error getting completed exams result: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            padding: 2rem;
        }
        .exam-card {
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 1.5rem;
            border: none;
            overflow: hidden;
            position: relative;
        }
        .exam-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        }
        .exam-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        .header::after {
            content: '';
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: 0;
        }
        .header::before {
            content: '';
            position: absolute;
            top: -50px;
            left: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: 0;
        }
        .header h1, .header p {
            position: relative;
            z-index: 1;
        }
        .exam-info {
            background-color: #f8f9fa;
            padding: 1.2rem;
            border-radius: 10px;
            margin: 1rem 0;
        }
        .exam-info p {
            margin: 0.5rem 0;
            display: flex;
            align-items: center;
        }
        .exam-info p i {
            margin-right: 0.75rem;
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }
        .exam-info strong {
            color: var(--text-color);
            font-weight: 600;
        }
        .section-title {
            margin: 2rem 0 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid rgba(0,0,0,0.1);
            color: var(--primary-color);
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .section-title i {
            margin-right: 0.75rem;
        }
        .take-exam-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .take-exam-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            color: white;
        }
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
        .score-badge {
            background: linear-gradient(135deg, var(--success-color), var(--info-color));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .empty-state i {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }
        .empty-state h3 {
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }
        .empty-state p {
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto 1.5rem;
        }
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            .header {
                padding: 1.5rem;
                text-align: center;
            }
            .header .d-flex {
                flex-direction: column;
                gap: 1rem;
            }
            .exam-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-graduation-cap me-2"></i>Student Dashboard</h1>
                    <p class="mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="settings.php" class="logout-btn" style="background: rgba(255,255,255,0.25);"><i class="fas fa-cog"></i> Settings</a>
                    <a href="help.php" class="logout-btn" style="background: rgba(255,255,255,0.25);"><i class="fas fa-life-ring"></i> Help</a>
                    <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <h2 class="section-title">
            <i class="fas fa-clipboard-list"></i> Available Exams
        </h2>
        
        <?php if (mysqli_num_rows($exams) === 0): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-check"></i>
                <h3>No Available Exams</h3>
                <p>There are no exams available for you to take at the moment. Check back later for new exams.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php while ($exam = mysqli_fetch_assoc($exams)): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="exam-card card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                <div class="exam-info">
                                    <p><i class="fas fa-book"></i> <strong>Subject:</strong> <?php echo htmlspecialchars($exam['subject']); ?></p>
                                    <p><i class="fas fa-user-tie"></i> <strong>Examiner:</strong> <?php echo htmlspecialchars($exam['examiner_name']); ?></p>
                                    <p><i class="fas fa-clock"></i> <strong>Duration:</strong> <?php echo $exam['duration']; ?> minutes</p>
                                    <p><i class="fas fa-star"></i> <strong>Total Marks:</strong> <?php echo $exam['total_marks']; ?></p>
                                </div>
                                <a href="take_exam.php?exam_id=<?php echo $exam['id']; ?>" class="take-exam-btn">
                                    <i class="fas fa-pen"></i> Take Exam
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
        
        <h2 class="section-title">
            <i class="fas fa-check-circle"></i> Completed Exams
        </h2>
        
        <?php if (mysqli_num_rows($completed_exams) === 0): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>No Completed Exams</h3>
                <p>You haven't completed any exams yet. Take an exam to see your results here.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php while ($exam = mysqli_fetch_assoc($completed_exams)): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="exam-card card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                <div class="exam-info">
                                    <p><i class="fas fa-book"></i> <strong>Subject:</strong> <?php echo htmlspecialchars($exam['subject']); ?></p>
                                    <p><i class="fas fa-user-tie"></i> <strong>Examiner:</strong> <?php echo htmlspecialchars($exam['examiner_name']); ?></p>
                                    <p><i class="fas fa-clock"></i> <strong>Duration:</strong> <?php echo $exam['duration']; ?> minutes</p>
                                    <p><i class="fas fa-star"></i> <strong>Total Marks:</strong> <?php echo $exam['total_marks']; ?></p>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="score-badge">
                                        <i class="fas fa-trophy"></i> Score: <?php echo $exam['score']; ?>/<?php echo $exam['total_marks']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 