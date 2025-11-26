<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an examiner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'examiner') {
    header("Location: ../index.php");
    exit();
}

// Fetch violations for this examiner's exams
$violations_sql = "SELECT ev.*, e.title as exam_title, e.subject, u.username as student_name 
                   FROM exam_violations ev 
                   JOIN exams e ON ev.exam_id = e.id 
                   JOIN users u ON ev.student_id = u.id 
                   WHERE e.examiner_id = ? 
                   ORDER BY ev.timestamp DESC";

$stmt = mysqli_prepare($conn, $violations_sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$violations = mysqli_stmt_get_result($stmt);

// Get summary statistics
$stats_sql = "SELECT 
                COUNT(*) as total_violations,
                COUNT(DISTINCT ev.student_id) as students_with_violations,
                COUNT(DISTINCT ev.exam_id) as exams_with_violations,
                ROUND(COUNT(*) / COUNT(DISTINCT ev.student_id), 2) as avg_violations_per_student
              FROM exam_violations ev 
              JOIN exams e ON ev.exam_id = e.id 
              WHERE e.examiner_id = ?";

$stats_stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stats_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Violations - Examiner Dashboard</title>
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
        /* Custom Violation Badges */
        .badge-violation {
            padding: 0.6rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .bg-danger-soft { background: #ffe5e5; color: #e74c3c; border: 1px solid #e74c3c; }
        .bg-warning-soft { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .bg-info-soft { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .bg-dark-soft { background: #d6d8d9; color: #1b1e21; border: 1px solid #c6c8ca; }
        
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
            background: linear-gradient(135deg, #e74c3c 0%, #ff6b6b 100%);
            color: white;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .back-btn {
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
        .back-btn:hover {
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
            border-top: 4px solid #e74c3c;
        }
        .stats-icon {
            font-size: 2.5rem;
            color: #e74c3c;
            margin-bottom: 1rem;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #e74c3c;
        }
        .stats-label {
            color: #6c757d;
            font-weight: 600;
        }
        .violation-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #e74c3c;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .violation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .violation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .violation-type {
            background: linear-gradient(135deg, #e74c3c 0%, #ff6b6b 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .violation-count {
            background: #f8f9fa;
            color: #e74c3c;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 700;
            border: 2px solid #e74c3c;
        }
        .violation-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .detail-item {
            background: #f8f9fa;
            padding: 0.8rem;
            border-radius: 8px;
            border-left: 3px solid #e74c3c;
        }
        .detail-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
        }
        .detail-value {
            color: #333;
            font-weight: 500;
        }
        .no-violations {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        .no-violations i {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        .section-title {
            color: #e74c3c;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid #e74c3c;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <div>
                <h1 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Exam Violations Monitor</h1>
                <p class="mb-0 mt-2">Track and monitor student violations during exams</p>
            </div>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</a>
        </div>

        <!-- Statistics Section -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-exclamation-triangle stats-icon"></i>
                    <div class="stats-number"><?php echo $stats['total_violations'] ?? 0; ?></div>
                    <div class="stats-label">Total Violations</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-user-times stats-icon"></i>
                    <div class="stats-number"><?php echo $stats['students_with_violations'] ?? 0; ?></div>
                    <div class="stats-label">Students with Violations</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-file-alt stats-icon"></i>
                    <div class="stats-number"><?php echo $stats['exams_with_violations'] ?? 0; ?></div>
                    <div class="stats-label">Exams with Violations</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-chart-line stats-icon"></i>
                    <div class="stats-number"><?php echo number_format($stats['avg_violations_per_student'] ?? 0, 1); ?></div>
                    <div class="stats-label">Avg Violations per Student</div>
                </div>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-list"></i> Recent Violations</h2>

        <?php if (mysqli_num_rows($violations) > 0): ?>
            <div class="violations-list">
                <?php 
                function getViolationConfig($type) {
                    if (strpos($type, 'prohibited_object') !== false) {
                        $obj = str_replace('prohibited_object_', '', $type);
                        return ['icon' => 'fa-mobile-alt', 'label' => 'Object: ' . ucfirst($obj), 'class' => 'bg-danger-soft'];
                    }
                    if (strpos($type, 'suspicious_speech') !== false) {
                        $word = str_replace('suspicious_speech_', '', $type);
                        return ['icon' => 'fa-microphone-slash', 'label' => 'Speech: "' . ucfirst($word) . '"', 'class' => 'bg-danger-soft'];
                    }
                    
                    switch($type) {
                        case 'tab_change': return ['icon' => 'fa-window-restore', 'label' => 'Tab Switch', 'class' => 'bg-warning-soft'];
                        case 'multiple_faces_detected': return ['icon' => 'fa-users', 'label' => 'Multiple Faces', 'class' => 'bg-danger-soft'];
                        case 'no_face_detected': return ['icon' => 'fa-user-slash', 'label' => 'No Face', 'class' => 'bg-warning-soft'];
                        case 'liveness_failure_no_blink': return ['icon' => 'fa-image', 'label' => 'Fake Face / Photo', 'class' => 'bg-danger-soft'];
                        case 'impersonation_suspected': return ['icon' => 'fa-id-card-alt', 'label' => 'Impersonation', 'class' => 'bg-dark-soft'];
                        case 'environment_too_dark': return ['icon' => 'fa-lightbulb', 'label' => 'Low Light', 'class' => 'bg-info-soft'];
                        case 'high_background_noise': return ['icon' => 'fa-volume-up', 'label' => 'High Noise', 'class' => 'bg-warning-soft'];
                        case 'face_too_close': return ['icon' => 'fa-search-plus', 'label' => 'Face Too Close', 'class' => 'bg-info-soft'];
                        case 'face_too_far': return ['icon' => 'fa-search-minus', 'label' => 'Face Too Far', 'class' => 'bg-info-soft'];
                        case 'mouth_open_talking': return ['icon' => 'fa-comment-dots', 'label' => 'Talking / Whispering', 'class' => 'bg-danger-soft'];
                        case 'suspicious_emotion_fear': return ['icon' => 'fa-flushed', 'label' => 'High Stress/Fear', 'class' => 'bg-info-soft'];
                        case 'impossible_typing_speed': return ['icon' => 'fa-keyboard', 'label' => 'Bot Typing Speed', 'class' => 'bg-dark-soft'];
                        case 'vm_or_bot_detected': return ['icon' => 'fa-robot', 'label' => 'VM / Bot Detected', 'class' => 'bg-dark-soft'];
                        case 'devtools_debugger_detected': return ['icon' => 'fa-code', 'label' => 'DevTools Opened', 'class' => 'bg-dark-soft'];
                        case 'looking_left': return ['icon' => 'fa-arrow-left', 'label' => 'Looking Left', 'class' => 'bg-warning-soft'];
                        case 'looking_right': return ['icon' => 'fa-arrow-right', 'label' => 'Looking Right', 'class' => 'bg-warning-soft'];
                        case 'looking_up': return ['icon' => 'fa-arrow-up', 'label' => 'Looking Up', 'class' => 'bg-warning-soft'];
                        case 'looking_down': return ['icon' => 'fa-arrow-down', 'label' => 'Looking Down', 'class' => 'bg-warning-soft'];
                        default: return ['icon' => 'fa-exclamation-circle', 'label' => ucfirst(str_replace('_', ' ', $type)), 'class' => 'bg-secondary text-white'];
                    }
                }
                
                while ($violation = mysqli_fetch_assoc($violations)): 
                    $config = getViolationConfig($violation['violation_type']);
                ?>
                    <div class="violation-card">
                        <div class="violation-header">
                            <div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($violation['exam_title']); ?></h5>
                                <p class="mb-0 text-muted"><?php echo htmlspecialchars($violation['subject']); ?></p>
                            </div>
                            <div class="d-flex gap-2">
                                <span class="badge-violation <?php echo $config['class']; ?>">
                                    <i class="fas <?php echo $config['icon']; ?>"></i>
                                    <?php echo htmlspecialchars($config['label']); ?>
                                </span>
                            </div>
                            <?php if (!empty($violation['proof_image_path'])): ?>
                                <button class="btn btn-sm btn-outline-primary view-proof-btn" 
                                        data-img-src="../<?php echo htmlspecialchars($violation['proof_image_path']); ?>"
                                        data-bs-toggle="modal" data-bs-target="#proofModal">
                                    <i class="fas fa-image me-1"></i> View Proof
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="violation-details">
                            <div class="detail-item">
                                <div class="detail-label">Student</div>
                                <div class="detail-value"><?php echo htmlspecialchars($violation['student_name']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Violation Time</div>
                                <div class="detail-value"><?php echo date('M j, Y g:i A', strtotime($violation['timestamp'])); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Exam ID</div>
                                <div class="detail-value">#<?php echo $violation['exam_id']; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Student ID</div>
                                <div class="detail-value">#<?php echo $violation['student_id']; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-violations">
                <i class="fas fa-shield-check"></i>
                <h3>No Violations Detected</h3>
                <p>Great! No violations have been detected in your exams so far.</p>
                <p class="text-muted">Students are following the exam rules properly.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Proof Modal -->
    <div class="modal fade" id="proofModal" tabindex="-1" aria-labelledby="proofModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="proofModalLabel"><i class="fas fa-camera me-2"></i>Violation Evidence</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center bg-dark">
                    <img src="" id="proofImage" class="img-fluid" alt="Violation Proof">
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const proofModal = document.getElementById('proofModal');
            proofModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const imgSrc = button.getAttribute('data-img-src');
                const modalImage = proofModal.querySelector('#proofImage');
                modalImage.src = imgSrc;
            });
        });
    </script>
</body>
</html>
