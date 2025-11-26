<?php
session_start();
require_once '../config/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

// Fetch available exams
$sql = "SELECT e.*, u.username as examiner_name 
        FROM exams e 
        JOIN users u ON e.examiner_id = u.id 
        WHERE e.id NOT IN (
            SELECT exam_id 
            FROM exam_results 
            WHERE student_id = ?
        ) ORDER BY e.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$exams = mysqli_stmt_get_result($stmt);
$total_available = mysqli_num_rows($exams);

// Fetch completed exams
$completed_sql = "SELECT e.*, er.score, u.username as examiner_name 
                 FROM exams e 
                 JOIN exam_results er ON e.id = er.exam_id 
                 JOIN users u ON e.examiner_id = u.id 
                 WHERE er.student_id = ? ORDER BY er.completed_at DESC";
$completed_stmt = mysqli_prepare($conn, $completed_sql);
mysqli_stmt_bind_param($completed_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($completed_stmt);
$completed_exams = mysqli_stmt_get_result($completed_stmt);
$total_completed = mysqli_num_rows($completed_exams);

// Calculate average score
$avg_sql = "SELECT AVG((score/total_marks)*100) as avg_score FROM exam_results er JOIN exams e ON er.exam_id = e.id WHERE student_id = ?";
$avg_stmt = mysqli_prepare($conn, $avg_sql);
mysqli_stmt_bind_param($avg_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($avg_stmt);
$avg_result = mysqli_fetch_assoc(mysqli_stmt_get_result($avg_stmt));
$average_score = round($avg_result['avg_score'] ?? 0, 1);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="layout-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-graduation-cap me-2"></i> SmartTest
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="sidebar-link active">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="#available-exams" class="sidebar-link">
                        <i class="fas fa-clipboard-list"></i> Available Exams
                    </a>
                </li>
                <li>
                    <a href="#completed-exams" class="sidebar-link">
                        <i class="fas fa-history"></i> History
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="sidebar-link">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
                <li>
                    <a href="help.php" class="sidebar-link">
                        <i class="fas fa-question-circle"></i> Help
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="d-flex align-items-center">
                    <button class="btn btn-link text-muted d-md-none me-3" id="sidebarToggle">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                    <div class="header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search available exams...">
                    </div>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" title="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="dropdown">
                        <div class="user-profile" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                            </div>
                            <span class="d-none d-md-block fw-medium">
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                            <i class="fas fa-chevron-down small text-muted d-none d-md-block"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm mt-2">
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <div class="dashboard-padding">
                <!-- Stats Grid -->
                <div class="stats-grid mb-5">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_available; ?></h3>
                            <p>Available Exams</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_completed; ?></h3>
                            <p>Completed</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $average_score; ?>%</h3>
                            <p>Average Score</p>
                        </div>
                    </div>
                </div>

                <!-- Available Exams -->
                <div id="available-exams" class="mb-5">
                    <h3 class="fw-bold h5 mb-4 border-bottom pb-2">Available Exams</h3>
                    
                    <?php if ($total_available === 0): ?>
                        <div class="text-center p-5 bg-card rounded border">
                            <div class="stat-icon mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h5 class="fw-bold">All Caught Up!</h5>
                            <p class="text-muted">There are no new exams available for you at the moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php while ($exam = mysqli_fetch_assoc($exams)): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="exam-card-modern">
                                        <div class="exam-cover">
                                            <span class="exam-badge"><?php echo htmlspecialchars($exam['subject']); ?></span>
                                        </div>
                                        <div class="exam-content">
                                            <h4 class="exam-title"><?php echo htmlspecialchars($exam['title']); ?></h4>
                                            <div class="exam-meta">
                                                <span><i class="fas fa-user-tie me-1"></i> <?php echo htmlspecialchars($exam['examiner_name']); ?></span>
                                            </div>
                                            <div class="exam-meta mb-4">
                                                <span><i class="far fa-clock me-1"></i> <?php echo $exam['duration']; ?>m</span>
                                                <span><i class="far fa-star me-1"></i> <?php echo $exam['total_marks']; ?> pts</span>
                                            </div>
                                            <div class="exam-footer">
                                                <a href="take_exam.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-primary w-100">
                                                    <i class="fas fa-pen me-2"></i> Start Exam
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Completed Exams -->
                <div id="completed-exams">
                    <h3 class="fw-bold h5 mb-4 border-bottom pb-2">Exam History</h3>
                    
                    <?php if ($total_completed === 0): ?>
                        <div class="text-center p-5 bg-card rounded border">
                            <p class="text-muted">You haven't completed any exams yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Exam Title</th>
                                            <th>Subject</th>
                                            <th>Date</th>
                                            <th>Score</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($exam = mysqli_fetch_assoc($completed_exams)): ?>
                                            <?php 
                                                $percentage = ($exam['score'] / $exam['total_marks']) * 100;
                                                $statusClass = $percentage >= 50 ? 'success' : 'danger';
                                                $statusText = $percentage >= 50 ? 'Passed' : 'Failed';
                                            ?>
                                            <tr>
                                                <td class="ps-4 fw-medium"><?php echo htmlspecialchars($exam['title']); ?></td>
                                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($exam['subject']); ?></span></td>
                                                <td class="text-muted small"><?php echo date('M d, Y', strtotime($exam['created_at'])); ?></td>
                                                <td>
                                                    <span class="fw-bold"><?php echo $exam['score']; ?></span>
                                                    <span class="text-muted small">/ <?php echo $exam['total_marks']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $statusClass; ?>-subtle text-<?php echo $statusClass; ?>">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        // Mobile Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
