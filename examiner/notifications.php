<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an examiner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'examiner') {
    header("Location: ../index.php");
    exit();
}

// Handle marking notifications as read
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    $mark_read_sql = "UPDATE exam_notifications SET is_read = 1 WHERE id = ? AND examiner_id = ?";
    $mark_read_stmt = mysqli_prepare($conn, $mark_read_sql);
    mysqli_stmt_bind_param($mark_read_stmt, "ii", $notification_id, $_SESSION['user_id']);
    mysqli_stmt_execute($mark_read_stmt);
}

// Handle marking all notifications as read
if (isset($_POST['mark_all_read'])) {
    $mark_all_sql = "UPDATE exam_notifications SET is_read = 1 WHERE examiner_id = ?";
    $mark_all_stmt = mysqli_prepare($conn, $mark_all_sql);
    mysqli_stmt_bind_param($mark_all_stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($mark_all_stmt);
}

// Fetch notifications for this examiner
$notifications_sql = "SELECT n.*, e.title as exam_title, u.username as student_name 
                      FROM exam_notifications n 
                      JOIN exams e ON n.exam_id = e.id 
                      JOIN users u ON n.student_id = u.id 
                      WHERE n.examiner_id = ? 
                      ORDER BY n.created_at DESC";
$notifications_stmt = mysqli_prepare($conn, $notifications_sql);
mysqli_stmt_bind_param($notifications_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($notifications_stmt);
$notifications = mysqli_stmt_get_result($notifications_stmt);

// Count unread notifications
$unread_sql = "SELECT COUNT(*) as unread_count FROM exam_notifications WHERE examiner_id = ? AND is_read = 0";
$unread_stmt = mysqli_prepare($conn, $unread_sql);
mysqli_stmt_bind_param($unread_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($unread_stmt);
$unread_data = mysqli_fetch_assoc(mysqli_stmt_get_result($unread_stmt));
$unread_count = $unread_data['unread_count'];

// Get recent activity stats
$stats_sql = "SELECT 
                COUNT(CASE WHEN notification_type = 'exam_started' THEN 1 END) as exams_started_today,
                COUNT(CASE WHEN notification_type = 'exam_submitted' THEN 1 END) as exams_submitted_today
              FROM exam_notifications 
              WHERE examiner_id = ? AND DATE(created_at) = CURDATE()";
$stats_stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stats_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stats_stmt);
$stats_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stats_stmt));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Notifications - Examiner Dashboard</title>
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
        .notification-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #4e54c8;
            transition: all 0.3s ease;
        }
        .notification-card.unread {
            border-left-color: #e74c3c;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
        }
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .notification-type {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        .notification-type.exam_started {
            background: linear-gradient(135deg, #3498db 0%, #5dade2 100%);
            color: white;
        }
        .notification-type.exam_submitted {
            background: linear-gradient(135deg, #2ecc71 0%, #4cd964 100%);
            color: white;
        }
        .notification-time {
            color: #6c757d;
            font-size: 0.9rem;
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
        .section-title {
            color: #4e54c8;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid #4e54c8;
            display: inline-block;
        }
        .no-notifications {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        .no-notifications i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ddd;
        }
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
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
                <h1 class="mb-0">Exam Notifications</h1>
                <p class="mb-0 mt-2">Track student exam activity in real-time</p>
            </div>
            <div class="d-flex gap-3">
                <a href="dashboard.php" class="logout-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <i class="fas fa-bell stats-icon"></i>
                    <div class="stats-number"><?php echo $unread_count; ?></div>
                    <div class="stats-label">Unread Notifications</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <i class="fas fa-play-circle stats-icon" style="color: #3498db;"></i>
                    <div class="stats-number" style="color: #3498db;"><?php echo $stats_data['exams_started_today']; ?></div>
                    <div class="stats-label">Exams Started Today</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <i class="fas fa-check-circle stats-icon" style="color: #2ecc71;"></i>
                    <div class="stats-number" style="color: #2ecc71;"><?php echo $stats_data['exams_submitted_today']; ?></div>
                    <div class="stats-label">Exams Submitted Today</div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-flex gap-3 mb-4">
            <?php if ($unread_count > 0): ?>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="mark_all_read" class="btn btn-success">
                        <i class="fas fa-check-double me-2"></i> Mark All as Read
                    </button>
                </form>
            <?php endif; ?>
            <button onclick="location.reload()" class="btn">
                <i class="fas fa-sync-alt me-2"></i> Refresh
            </button>
        </div>

        <!-- Notifications List -->
        <h2 class="section-title">
            <i class="fas fa-bell"></i> Recent Notifications
            <?php if ($unread_count > 0): ?>
                <span class="badge badge-danger ms-2"><?php echo $unread_count; ?> New</span>
            <?php endif; ?>
        </h2>

        <?php if (mysqli_num_rows($notifications) > 0): ?>
            <div class="notifications-list">
                <?php while ($notification = mysqli_fetch_assoc($notifications)): ?>
                    <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="notification-type <?php echo $notification['notification_type']; ?>">
                                    <i class="fas fa-<?php echo $notification['notification_type'] === 'exam_started' ? 'play' : 'check'; ?> me-1"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $notification['notification_type'])); ?>
                                </div>
                                <h5 class="mb-2"><?php echo htmlspecialchars($notification['message']); ?></h5>
                                <div class="notification-details">
                                    <p class="mb-1"><strong>Student:</strong> <?php echo htmlspecialchars($notification['student_name']); ?></p>
                                    <p class="mb-1"><strong>Exam:</strong> <?php echo htmlspecialchars($notification['exam_title']); ?></p>
                                    <p class="mb-0 notification-time">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('M j, Y \a\t g:i A', strtotime($notification['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" name="mark_read" class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i> Mark Read
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-notifications">
                <i class="fas fa-bell-slash"></i>
                <h3>No Notifications Yet</h3>
                <p>You'll receive notifications here when students start or submit your exams.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Auto-refresh every 30 seconds -->
    <script>
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
