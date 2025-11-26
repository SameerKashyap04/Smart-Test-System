<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an examiner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'examiner') {
    header("Location: ../index.php");
    exit();
}

// Fetch exams created by this examiner
$sql = "SELECT * FROM exams WHERE examiner_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$exams = mysqli_stmt_get_result($stmt);
$total_exams = mysqli_num_rows($exams);

// Fetch total students
$students_sql = "SELECT COUNT(DISTINCT student_id) as total_students FROM exam_results er JOIN exams e ON er.exam_id = e.id WHERE e.examiner_id = ?";
$students_stmt = mysqli_prepare($conn, $students_sql);
mysqli_stmt_bind_param($students_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($students_stmt);
$students_data = mysqli_fetch_assoc(mysqli_stmt_get_result($students_stmt));
$total_students = $students_data['total_students'];

// Fetch total violations
$violations_sql = "SELECT COUNT(*) as total_violations FROM exam_violations ev JOIN exams e ON ev.exam_id = e.id WHERE e.examiner_id = ?";
$violations_stmt = mysqli_prepare($conn, $violations_sql);
mysqli_stmt_bind_param($violations_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($violations_stmt);
$violations_data = mysqli_fetch_assoc(mysqli_stmt_get_result($violations_stmt));
$total_violations = $violations_data['total_violations'] ?? 0;

// Fetch unread notifications
$unread_sql = "SELECT COUNT(*) as unread_count FROM exam_notifications WHERE examiner_id = ? AND is_read = 0";
$unread_stmt = mysqli_prepare($conn, $unread_sql);
mysqli_stmt_bind_param($unread_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($unread_stmt);
$unread_data = mysqli_fetch_assoc(mysqli_stmt_get_result($unread_stmt));
$unread_notifications = $unread_data['unread_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examiner Dashboard</title>
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
                <i class="fas fa-layer-group me-2"></i> SmartTest
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="sidebar-link active">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="create_exam.php" class="sidebar-link">
                        <i class="fas fa-plus-circle"></i> Create Exam
                    </a>
                </li>
                <li>
                    <a href="view_violations.php" class="sidebar-link">
                        <i class="fas fa-shield-alt"></i> Violations
                    </a>
                </li>
                <li>
                    <a href="notifications.php" class="sidebar-link">
                        <i class="fas fa-bell"></i> Notifications
                        <?php if ($unread_notifications > 0): ?>
                            <span class="badge bg-danger ms-auto"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="sidebar-link">
                        <i class="fas fa-cog"></i> Settings
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
                        <input type="text" placeholder="Search exams...">
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
                <!-- Welcome Section -->
                <div class="mb-4">
                    <h2 class="fw-bold text-main">Dashboard Overview</h2>
                    <p class="text-muted">Welcome back, here's what's happening with your exams today.</p>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success mb-4 shadow-sm border-0 border-start border-4 border-success">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_exams; ?></h3>
                            <p>Total Exams</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_students; ?></h3>
                            <p>Students</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_violations; ?></h3>
                            <p>Violations</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $unread_notifications; ?></h3>
                            <p>Notifications</p>
                        </div>
                    </div>
                </div>

                <!-- Exams Grid -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold h5 mb-0">Recent Exams</h3>
                    <a href="create_exam.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-2"></i> Create New
                    </a>
                </div>

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
                                        <span><i class="far fa-clock me-1"></i> <?php echo $exam['duration']; ?>m</span>
                                        <span><i class="far fa-star me-1"></i> <?php echo $exam['total_marks']; ?> pts</span>
                                    </div>
                                    <div class="exam-footer">
                                        <a href="view_questions.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">
                                            <i class="fas fa-eye"></i> Questions
                                        </a>
                                        <a href="view_exam_students.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
                                            <i class="fas fa-users"></i> Results
                                        </a>
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm border" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li><a class="dropdown-item" href="edit_exam.php?exam_id=<?php echo $exam['id']; ?>"><i class="fas fa-edit me-2"></i> Edit</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="delete_exam.php" method="POST">
                                                        <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Delete this exam?')">
                                                            <i class="fas fa-trash-alt me-2"></i> Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
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
