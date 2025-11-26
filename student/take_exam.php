<?php
// Start session to maintain user state
session_start();
require_once '../config/db.php';

// Verify user authentication and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

// Validate exam ID from URL
if (!isset($_GET['exam_id'])) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = (int)$_GET['exam_id'];

// Check if student has already taken this exam
$check_sql = "SELECT * FROM exam_results WHERE exam_id = ? AND student_id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ii", $exam_id, $_SESSION['user_id']);
mysqli_stmt_execute($check_stmt);
if (mysqli_stmt_get_result($check_stmt)->num_rows > 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch exam details from database
$exam_sql = "SELECT * FROM exams WHERE id = ?";
$exam_stmt = mysqli_prepare($conn, $exam_sql);
mysqli_stmt_bind_param($exam_stmt, "i", $exam_id);
mysqli_stmt_execute($exam_stmt);
$exam = mysqli_fetch_assoc(mysqli_stmt_get_result($exam_stmt));

if (!$exam) {
    header("Location: dashboard.php");
    exit();
}

// Fetch all questions for this exam
$questions_sql = "SELECT * FROM questions WHERE exam_id = ?";
$questions_stmt = mysqli_prepare($conn, $questions_sql);
mysqli_stmt_bind_param($questions_stmt, "i", $exam_id);
mysqli_stmt_execute($questions_stmt);
$questions_result = mysqli_stmt_get_result($questions_stmt);
$total_questions = mysqli_num_rows($questions_result);

// Verify exam has questions
if ($total_questions === 0) {
    $_SESSION['error'] = "No questions found for this exam.";
    header("Location: dashboard.php");
    exit();
}

$questions = [];
while ($row = mysqli_fetch_assoc($questions_result)) {
    $questions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taking Exam: <?php echo htmlspecialchars($exam['title']); ?></title>
    <!-- Include required CSS frameworks -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- MediaPipe Face Detection -->
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/control_utils/control_utils.js" crossorigin="anonymous"></script>
    <!-- Voice Detection -->
    <script src="../assets/js/voice-detection.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils/drawing_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/face_detection.js" crossorigin="anonymous"></script>
    <style>
        /* Custom styling for exam interface */
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
        }
        /* Main container styling */
        .exam-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        /* Header section styling */
        .header {
            background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        /* Timer display styling */
        .timer-container {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        /* Question card styling */
        .question-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            border-left: 4px solid #4e54c8;
            display: none; /* Hidden by default */
        }
        .question-card.active {
            display: block; /* Show only active question */
        }
        /* Exam top warning banner */
        .exam-warning {
            background: #fff3cd;
            color: #664d03;
            border: 1px solid #ffecb5;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-top: -0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .exam-warning i {
            color: #d99700;
        }
        /* Option styling */
        .option-item {
            padding: 0.8rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        /* Selected option styling */
        .option-item.selected {
            background-color: #e8eaff;
            border-color: #4e54c8;
        }
        
        /* Tab warning modal styling */
        .tab-warning-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }
        
        .warning-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .warning-icon {
            font-size: 3rem;
            color: #ff6b6b;
            margin-bottom: 1rem;
        }
        
        .warning-content h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .warning-content p {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        /* Timer warning styling */
        .timer-container.warning {
            background: rgba(255, 107, 107, 0.3);
            animation: pulse 1s infinite;
        }

		/* Fullscreen prompt overlay */
		.fullscreen-overlay {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.8);
			color: #fff;
			display: none;
			justify-content: center;
			align-items: center;
			z-index: 10001;
			padding: 1rem;
			text-align: center;
		}
		.fullscreen-box {
			background: #111827;
			border-radius: 12px;
			padding: 2rem;
			max-width: 560px;
			box-shadow: 0 10px 30px rgba(0,0,0,0.4);
		}
		.fullscreen-box h3 { margin-bottom: 0.75rem; }
		.fullscreen-btn {
			background: linear-gradient(135deg, #4e54c8, #8f94fb);
			border: none;
			color: #fff;
			padding: 0.75rem 1.25rem;
			border-radius: 8px;
			font-weight: 600;
			margin-top: 1rem;
			cursor: pointer;
		}
		
		/* Face monitoring styles */
		.face-monitor {
			position: fixed;
			top: 20px;
			right: 20px;
			width: 200px;
			height: 150px;
			border: 2px solid #4e54c8;
			border-radius: 8px;
			background: #000;
			z-index: 1000;
			overflow: hidden;
		}
		.face-monitor video {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}
		.face-status {
			position: absolute;
			bottom: 0;
			left: 0;
			right: 0;
			background: rgba(0,0,0,0.7);
			color: white;
			padding: 4px 8px;
			font-size: 12px;
			text-align: center;
		}
		.face-status.detected { background: rgba(46, 204, 113, 0.8); }
		.face-status.no-face { background: rgba(231, 76, 60, 0.8); }
		.face-status.camera-denied { background: rgba(231, 76, 60, 0.8); }
		
		.camera-permission-overlay {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.9);
			color: #fff;
			display: flex;
			justify-content: center;
			align-items: center;
			z-index: 10002;
			padding: 1rem;
			text-align: center;
		}
		.camera-permission-box {
			background: #111827;
			border-radius: 12px;
			padding: 2rem;
			max-width: 500px;
			box-shadow: 0 10px 30px rgba(0,0,0,0.4);
		}
		.camera-permission-box h3 { margin-bottom: 0.75rem; }
		.camera-btn {
			background: linear-gradient(135deg, #2ecc71, #4cd964);
			border: none;
			color: #fff;
			padding: 0.75rem 1.25rem;
			border-radius: 8px;
			font-weight: 600;
			margin: 0.5rem;
			cursor: pointer;
		}
		.camera-btn:hover {
			background: linear-gradient(135deg, #27ae60, #2ecc71);
		}
		.camera-btn.danger {
			background: linear-gradient(135deg, #e74c3c, #ff6b6b);
		}
		.camera-btn.danger:hover {
			background: linear-gradient(135deg, #c0392b, #e74c3c);
		}
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .nav-btn {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Main exam container -->
    <div class="exam-container">
        <!-- Exam header with title and timer -->
        <div class="header">
            <div>
                <h1 class="h3 mb-0"><?php echo htmlspecialchars($exam['title']); ?></h1>
                <p class="mb-0 mt-2">Subject: <?php echo htmlspecialchars($exam['subject']); ?></p>
            </div>

	<!-- Camera permission overlay -->
	<div id="camera-permission-overlay" class="camera-permission-overlay">
		<div class="camera-permission-box">
			<div class="warning-icon"><i class="fas fa-video"></i></div>
			<h3>Camera Access Required</h3>
			<p>This exam requires camera access for face monitoring to ensure academic integrity. Your face must be visible throughout the exam.</p>
			<p><strong>Note:</strong> Refusing camera access or covering your face will result in automatic exam submission.</p>
			<button id="allow-camera" class="camera-btn"><i class="fas fa-camera"></i> Allow Camera Access</button>
			<button id="deny-camera" class="camera-btn danger"><i class="fas fa-times"></i> Deny (Submit Exam)</button>
		</div>
	</div>

	<!-- Fullscreen overlay -->
	<div id="fullscreen-overlay" class="fullscreen-overlay">
		<div class="fullscreen-box">
			<div class="warning-icon"><i class="fas fa-expand"></i></div>
			<h3>Enter Fullscreen to Start</h3>
			<p>For a focused and fair test, this exam requires fullscreen mode. Exiting fullscreen will submit your exam.</p>
			<button id="enter-fullscreen" class="fullscreen-btn"><i class="fas fa-arrows-alt"></i> Enter Fullscreen</button>
		</div>
	</div>

	<!-- Face monitoring widget -->
	<div id="face-monitor" class="face-monitor" style="display: none;">
		<video id="camera-video" autoplay muted playsinline></video>
		<canvas id="face-canvas"></canvas>
		<div id="face-status" class="face-status">Initializing...</div>
	</div>
            <div class="timer-container">
                <i class="fas fa-clock timer-icon"></i>
                <span id="timer">00:00:00</span>
            </div>
        </div>
        <div class="exam-warning">
            <i class="fas fa-triangle-exclamation"></i>
            <div>
                <strong>Warning:</strong> Do not switch tabs or exit fullscreen. You will see a warning and cannot proceed until you return.
            </div>
        </div>
        
        <!-- Voice Detection Panel -->
        <div id="voice-detection-container" style="display:none;"></div>
        
        <!-- Exam form -->
        <form id="exam-form" action="submit_exam.php" method="POST">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
            
            <?php 
            $total = count($questions);
            foreach ($questions as $index => $question): 
                $q_num = $index + 1;
            ?>
                <!-- Question card -->
                <div class="question-card" id="question-<?php echo $q_num; ?>" data-index="<?php echo $q_num; ?>">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="h5 mb-0">Question <?php echo $q_num; ?> of <?php echo $total; ?></div>
                        <span class="badge bg-secondary"><?php echo $question['marks']; ?> Marks</span>
                    </div>
                    <p class="question-text"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                    
                    <!-- Display question image if exists -->
                    <?php if ($question['image_path']): ?>
                        <div class="question-image">
                            <img src="../<?php echo htmlspecialchars($question['image_path']); ?>" alt="Question Image" style="max-width: 100%; max-height: 300px; margin: 10px 0;">
                        </div>
                    <?php endif; ?>
                    
                    <!-- Options container -->
                    <div class="options-container">
                        <!-- Option A -->
                        <div class="option-item" data-option="a">
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="a" id="option_a_<?php echo $question['id']; ?>" class="d-none">
                            <label for="option_a_<?php echo $question['id']; ?>" class="mb-0 w-100">
                                A) <?php echo htmlspecialchars($question['option_a']); ?>
                            </label>
                        </div>
                        
                        <!-- Option B -->
                        <div class="option-item" data-option="b">
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="b" id="option_b_<?php echo $question['id']; ?>" class="d-none">
                            <label for="option_b_<?php echo $question['id']; ?>" class="mb-0 w-100">
                                B) <?php echo htmlspecialchars($question['option_b']); ?>
                            </label>
                        </div>
                        
                        <!-- Option C -->
                        <div class="option-item" data-option="c">
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="c" id="option_c_<?php echo $question['id']; ?>" class="d-none">
                            <label for="option_c_<?php echo $question['id']; ?>" class="mb-0 w-100">
                                C) <?php echo htmlspecialchars($question['option_c']); ?>
                            </label>
                        </div>
                        
                        <!-- Option D -->
                        <div class="option-item" data-option="d">
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="d" id="option_d_<?php echo $question['id']; ?>" class="d-none">
                            <label for="option_d_<?php echo $question['id']; ?>" class="mb-0 w-100">
                                D) <?php echo htmlspecialchars($question['option_d']); ?>
                            </label>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <?php if ($index > 0): ?>
                            <button type="button" class="btn btn-secondary nav-btn prev-btn" data-target="<?php echo $q_num - 1; ?>">
                                <i class="fas fa-arrow-left"></i> Previous
                            </button>
                        <?php else: ?>
                            <div></div> 
                        <?php endif; ?>

                        <?php if ($index < $total - 1): ?>
                            <button type="button" class="btn btn-primary nav-btn next-btn" data-target="<?php echo $q_num + 1; ?>">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-success nav-btn" id="finish-btn">
                                <i class="fas fa-check"></i> Finish
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Submit button (hidden initially) -->
            <button type="submit" class="submit-btn d-none" id="submit-btn">
                <i class="fas fa-paper-plane me-2"></i> Submit Exam
            </button>
        </form>
    </div>
    
    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Question Navigation Logic
        document.addEventListener('DOMContentLoaded', function() {
            const questions = document.querySelectorAll('.question-card');
            const totalQuestions = questions.length;
            let currentQuestion = 1;

            // Show first question
            if(questions.length > 0) {
                questions[0].classList.add('active');
            }

            // Handle Next Button
            document.querySelectorAll('.next-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const target = parseInt(this.getAttribute('data-target'));
                    showQuestion(target);
                });
            });

            // Handle Previous Button
            document.querySelectorAll('.prev-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const target = parseInt(this.getAttribute('data-target'));
                    showQuestion(target);
                });
            });

            // Handle Finish Button
            const finishBtn = document.getElementById('finish-btn');
            if(finishBtn) {
                finishBtn.addEventListener('click', function() {
                    if(confirm('Are you sure you want to finish and submit the exam?')) {
                        document.getElementById('submit-btn').click();
                    }
                });
            }

            function showQuestion(num) {
                // Hide all questions
                questions.forEach(q => q.classList.remove('active'));
                
                // Show target question
                const targetQuestion = document.getElementById('question-' + num);
                if(targetQuestion) {
                    targetQuestion.classList.add('active');
                    currentQuestion = num;
                    window.scrollTo(0, 0); // Scroll to top
                }
            }
        });

        // Voice Detection Setup
        let voiceDetection;
        // UI removed to prevent student from disabling or changing sensitivity
        
        // Initialize voice detection when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Get exam-specific voice detection settings
            const voiceDetectionEnabled = <?php echo $exam['voice_detection_enabled'] ? 'true' : 'false'; ?>;
            const microphoneRequired = <?php echo $exam['microphone_required'] ? 'true' : 'false'; ?>;
            const voiceSensitivity = <?php echo $exam['voice_sensitivity'] ?? 0.3; ?>;
            const voiceViolationThreshold = <?php echo $exam['voice_violation_threshold'] ?? 2; ?>;
            const voiceMaxViolations = <?php echo $exam['voice_max_violations'] ?? 5; ?>;
            
            // Check if microphone is required
            if (microphoneRequired) {
                // Show microphone permission overlay
                showMicrophonePermissionOverlay();
            }
            
            // Initialize voice detection with exam settings
            voiceDetection = new VoiceDetection({
                enabled: voiceDetectionEnabled,
                sensitivity: voiceSensitivity,
                detectionInterval: 1000, // Check every second
                violationThreshold: voiceViolationThreshold,
                maxViolations: voiceMaxViolations
            });
            
            // Do not render any voice detection controls/UI to avoid user changes
            const container = document.getElementById('voice-detection-container');
            if (container) { container.style.display = 'none'; }
            
            // Setup voice detection callbacks (only if voice detection is enabled)
            if (voiceDetectionEnabled) {
                voiceDetection.onViolation(function(violation) {
                    console.log('Voice violation detected:', violation);
                    // Log violation to server
                    logViolation('voice_detection', `Voice detected - Volume: ${Math.round(violation.volume * 100)}%`);
                });
                
                voiceDetection.onWarning(function(message) {
                    console.warn('Voice detection warning:', message);
                    // Show warning to user
                    showNotification(message, 'warning');
                });
                
                voiceDetection.onMaxViolations(function(message) {
                    console.error('Max voice violations reached:', message);
                    showNotification(message, 'error');
                    lockExam('Voice violations threshold reached. Exam is locked. Submit to finish.');
                });
            }
            
            // Start voice detection when exam starts (if enabled)
            if (voiceDetectionEnabled) {
                voiceDetection.startListening();
            }
        });
        
        // Function to show microphone permission overlay
        function showMicrophonePermissionOverlay() {
            const overlay = document.createElement('div');
            overlay.id = 'microphone-permission-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            `;
            
            overlay.innerHTML = `
                <div style="
                    background: white;
                    padding: 2rem;
                    border-radius: 10px;
                    text-align: center;
                    max-width: 500px;
                    margin: 1rem;
                ">
                    <i class="fas fa-microphone" style="font-size: 3rem; color: #007bff; margin-bottom: 1rem;"></i>
                    <h3>Microphone Access Required</h3>
                    <p>This exam requires microphone access for voice detection monitoring.</p>
                    <p class="text-muted">Please allow microphone access to continue with the exam.</p>
                    <div class="mt-3">
                        <button class="btn btn-primary" onclick="requestMicrophonePermission()">
                            <i class="fas fa-microphone me-2"></i>Allow Microphone Access
                        </button>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">You can disable this in your browser settings if needed.</small>
                    </div>
                </div>
            `;
            
            document.body.appendChild(overlay);
        }
        
        // Function to request microphone permission
        async function requestMicrophonePermission() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    } 
                });
                
                // Permission granted, remove overlay
                const overlay = document.getElementById('microphone-permission-overlay');
                if (overlay) {
                    overlay.remove();
                }
                
                // Start voice detection if enabled
                if (voiceDetection && voiceDetection.isEnabled) {
                    voiceDetection.startListening();
                }
                
                showNotification('Microphone access granted. Voice detection is now active.', 'success');
                
            } catch (error) {
                console.error('Microphone permission denied:', error);
                showNotification('Microphone access is required for this exam. Please refresh and allow microphone access.', 'error');
                
                // Optionally redirect back to dashboard
                setTimeout(() => {
                    if (confirm('Microphone access is required. Would you like to return to the dashboard?')) {
                        window.location.href = 'dashboard.php';
                    }
                }, 3000);
            }
        }
        
        // Function to log violations
        function logViolation(type, description) {
            const examId = <?php echo $exam_id; ?>;
            
            fetch('log_violation.php?exam_id=' + examId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: type,
                    description: description,
                    violation_number: voiceDetection ? voiceDetection.violationCount : 1,
                    timestamp: Date.now()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Violation logged successfully');
                } else {
                    console.error('Failed to log violation:', data.error);
                }
            })
            .catch(error => {
                console.error('Error logging violation:', error);
            });
        }
        
        // Function to show notifications
        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-circle' : 'info-circle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        }
        
        // Timer functionality
        let examDuration = <?php echo $exam['duration']; ?> * 60; // Convert minutes to seconds
        let timeLeft = examDuration;
        let timerInterval;
		let examStarted = false;
		let fullscreenRequired = true;
		let isAutoSubmitting = false;
		
		// Face monitoring variables
		let faceDetection = null;
		let camera = null;
		let noFaceCount = 0;
        let maxNoFaceTime = 5; // stricter: seconds without face before lock
		let faceCheckInterval = null;
		let lastFaceDetected = Date.now();
		// Setup gating flags
		let cameraReady = false;
		let fullscreenReady = false;
		let monitoringEnabled = false;
		// Proctor alert throttling
		let lastProctorAlertAt = 0;
		const PROCTOR_ALERT_COOLDOWN_MS = 30000; // 30s between alerts
		// Eye off-screen detection
		let eyeOffStartAt = null;
		const EYE_OFF_THRESHOLD_SEC = 5;
        
        // Tab change detection variables
        let tabChangeCount = 0;
        let maxTabChanges = <?php echo isset($exam['max_tab_changes']) ? (int)$exam['max_tab_changes'] : 3; ?>; // Maximum allowed tab changes from database
        let isTabActive = true;
        let tabChangeStartTime = null;
        let totalTabChangeTime = 0;
        let violationWarningShown = false;
        
        // Format time as HH:MM:SS
        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            return [
                hours.toString().padStart(2, '0'),
                minutes.toString().padStart(2, '0'),
                secs.toString().padStart(2, '0')
            ].join(':');
        }
        
        // Update timer display
        function updateTimer() {
            document.getElementById('timer').textContent = formatTime(timeLeft);
            
            // Add warning class when less than 5 minutes remaining
            if (timeLeft <= 300) {
                document.getElementById('timer').classList.add('warning');
            }
            
            // Auto-submit when time runs out
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                document.getElementById('exam-form').submit();
            }
            
            timeLeft--;
        }
        
        // Lock overlay to block exam until submission
        function lockExam(reason) {
            const existing = document.getElementById('exam-lock-overlay');
            if (existing) return;
            const overlay = document.createElement('div');
            overlay.id = 'exam-lock-overlay';
            overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:10003;display:flex;align-items:center;justify-content:center;color:#fff;padding:16px;text-align:center;';
            overlay.innerHTML = `
                <div style="max-width:560px;background:#111827;border-radius:12px;padding:24px;">
                    <div class="warning-icon" style="color:#ff6b6b;"><i class="fas fa-lock"></i></div>
                    <h3>Exam Locked</h3>
                    <p>${reason || 'A violation has been detected. You can only submit the exam now.'}</p>
                    <button id="lock-submit" class="btn btn-danger"><i class="fas fa-paper-plane me-1"></i> Submit Exam</button>
                </div>
            `;
            document.body.appendChild(overlay);
            // Disable all inputs
            document.querySelectorAll('input, button, select, textarea').forEach(el => {
                if (el.id === 'lock-submit') return;
                el.disabled = true;
            });
            const form = document.getElementById('exam-form');
            document.getElementById('lock-submit').addEventListener('click', () => {
                form.setAttribute('data-skip-confirm', '1');
                form.submit();
            });
        }

        // Tab change detection functions
        function handleTabChange() {
            // Only enforce after camera + fullscreen are active
            if (!monitoringEnabled) return;
            tabChangeCount++;
            try { logTabChangeViolation(); } catch (e) {}
            lockExam('Tab change or focus loss detected. You can only submit now.');
        }
        
        function showTabChangeWarning() {
            const warningModal = document.createElement('div');
            warningModal.className = 'tab-warning-modal';
            warningModal.innerHTML = `
                <div class="warning-content">
                    <div class="warning-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3>Tab Change Detected!</h3>
                    <p>You have switched tabs or minimized the browser window.</p>
                    <p><strong>Warning:</strong> Tab change is not allowed. This exam is locked. Submit to finish.</p>
                    <button onclick="document.getElementById('exam-form').setAttribute('data-skip-confirm','1');document.getElementById('exam-form').submit();" class="btn btn-danger">Submit Exam</button>
                </div>
            `;
            document.body.appendChild(warningModal);
        }
        
        function logTabChangeViolation() {
            // Send violation data to server
            fetch('log_violation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    exam_id: <?php echo $exam_id; ?>,
                    student_id: <?php echo $_SESSION['user_id']; ?>,
                    violation_type: 'tab_change',
                    violation_count: tabChangeCount,
                    timestamp: new Date().toISOString()
                })
            }).catch(error => {
                console.error('Error logging violation:', error);
            });
        }
        
		function logExamNotification(notificationType, message) {
			// Send notification data to server
			fetch('log_notification.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					exam_id: <?php echo $exam_id; ?>,
					notification_type: notificationType,
					message: message || null
				})
			}).catch(error => {
				console.error('Error logging notification:', error);
			});
		}
		
		// Face monitoring functions
		function logFaceViolation(violationType) {
			fetch('log_violation.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					exam_id: <?php echo $exam_id; ?>,
					student_id: <?php echo $_SESSION['user_id']; ?>,
					violation_type: violationType,
					violation_count: 1,
					timestamp: new Date().toISOString()
				})
			}).catch(error => {
				console.error('Error logging face violation:', error);
			});
		}
		
		function initializeFaceDetection() {
			const video = document.getElementById('camera-video');
			const canvas = document.getElementById('face-canvas');
			const status = document.getElementById('face-status');
			
			// Initialize MediaPipe Face Detection
			faceDetection = new FaceDetection({
				locateFile: (file) => {
					return `https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/${file}`;
				}
			});
			
			faceDetection.setOptions({
				model: 'short',
				minDetectionConfidence: 0.5,
			});
			
			faceDetection.onResults((results) => {
				canvas.width = video.videoWidth;
				canvas.height = video.videoHeight;
				const ctx = canvas.getContext('2d');
				ctx.save();
				ctx.clearRect(0, 0, canvas.width, canvas.height);
				
				if (results.detections && results.detections.length > 0) {
					// Multiple faces detected
					if (results.detections.length > 1) {
						const now = Date.now();
						if (now - lastProctorAlertAt > PROCTOR_ALERT_COOLDOWN_MS) {
							logFaceViolation('multiple_faces_detected');
							logExamNotification('proctor_alert', 'Multiple faces detected on camera');
							lastProctorAlertAt = now;
						}
					}
					// Face detected
					status.textContent = 'Face Detected';
					status.className = 'face-status detected';
					lastFaceDetected = Date.now();
					noFaceCount = 0;
					// Eye movement estimation via relative keypoints
					try {
						const det = results.detections[0];
						const bbox = det.locationData.relativeBoundingBox;
						const kps = det.locationData.relativeKeypoints || [];
						const leftEye = kps[0];
						const rightEye = kps[1];
						if (leftEye && rightEye && bbox) {
							const eyeDx = Math.abs(leftEye.x - rightEye.x);
							const normWidth = bbox.width;
							const eyeSpreadRatio = normWidth > 0 ? (eyeDx / normWidth) : 0;
							// If eyes appear narrowly spaced relative to bbox width, likely turned away
							if (eyeSpreadRatio < 0.22) {
								if (!eyeOffStartAt) eyeOffStartAt = Date.now();
								const offForSec = (Date.now() - eyeOffStartAt) / 1000;
								status.textContent = `Eyes off-screen (${offForSec.toFixed(0)}s)`;
								status.className = 'face-status no-face';
								if (offForSec >= EYE_OFF_THRESHOLD_SEC) {
									const now = Date.now();
									if (now - lastProctorAlertAt > PROCTOR_ALERT_COOLDOWN_MS) {
										logFaceViolation('eye_off_screen');
										logExamNotification('proctor_alert', 'Student looking away from screen');
										lastProctorAlertAt = now;
									}
								}
							} else {
								eyeOffStartAt = null;
							}
						} else {
							eyeOffStartAt = null;
						}
					} catch (e) {
						// ignore
					}
					
					// Draw face detection boxes
					results.detections.forEach(detection => {
						const bbox = detection.locationData.relativeBoundingBox;
						const x = bbox.xCenter * canvas.width - (bbox.width * canvas.width) / 2;
						const y = bbox.yCenter * canvas.height - (bbox.height * canvas.height) / 2;
						const width = bbox.width * canvas.width;
						const height = bbox.height * canvas.height;
						
						ctx.strokeStyle = '#00ff00';
						ctx.lineWidth = 2;
						ctx.strokeRect(x, y, width, height);
					});
				} else {
					// No face detected
					status.textContent = 'No Face Detected';
					status.className = 'face-status no-face';
					noFaceCount++;
					eyeOffStartAt = null;
					
					// Check if no face for too long
					const timeSinceLastFace = (Date.now() - lastFaceDetected) / 1000;
					if (timeSinceLastFace > maxNoFaceTime) {
						status.textContent = 'Auto-submitting...';
						logFaceViolation('no_face_detected');
						handleTabChange();
					}
				}
				ctx.restore();
			});
			
			// Start camera
			camera = new Camera(video, {
				onFrame: async () => {
					await faceDetection.send({image: video});
				},
				width: 200,
				height: 150
			});
			camera.start();
		}
		
		function requestCameraPermission() {
			const permissionOverlay = document.getElementById('camera-permission-overlay');
			const allowBtn = document.getElementById('allow-camera');
			const denyBtn = document.getElementById('deny-camera');
			
			permissionOverlay.style.display = 'flex';
			
			allowBtn.addEventListener('click', async () => {
				try {
					const stream = await navigator.mediaDevices.getUserMedia({ 
						video: { 
							width: 200, 
							height: 150,
							facingMode: 'user'
						} 
					});
					
					permissionOverlay.style.display = 'none';
					document.getElementById('face-monitor').style.display = 'block';
					cameraReady = true;
					monitoringEnabled = cameraReady && fullscreenReady;
					initializeFaceDetection();
				} catch (error) {
					console.error('Camera access denied:', error);
					document.getElementById('face-status').textContent = 'Camera Denied';
					document.getElementById('face-status').className = 'face-status camera-denied';
					logFaceViolation('camera_access_denied');
					handleTabChange();
				}
			});
			
			denyBtn.addEventListener('click', () => {
				logFaceViolation('camera_access_denied');
				handleTabChange();
			});
		}
        
		// Initialize exam interface
		document.addEventListener('DOMContentLoaded', function() {
			const optionItems = document.querySelectorAll('.option-item');
			const overlay = document.getElementById('fullscreen-overlay');
			const enterFsBtn = document.getElementById('enter-fullscreen');
			
			// Log exam start immediately on page load (attendance)
			logExamNotification('exam_started');
			
			// Request camera permission first
			requestCameraPermission();

			// Show fullscreen overlay until fullscreen is enabled
			function isFullscreen() {
				return document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement;
			}
			function requestFullscreen(el) {
				if (el.requestFullscreen) return el.requestFullscreen();
				if (el.webkitRequestFullscreen) return el.webkitRequestFullscreen();
				if (el.mozRequestFullScreen) return el.mozRequestFullScreen();
				if (el.msRequestFullscreen) return el.msRequestFullscreen();
			}
			function exitFullscreen() {
				if (document.exitFullscreen) return document.exitFullscreen();
				if (document.webkitExitFullscreen) return document.webkitExitFullscreen();
				if (document.mozCancelFullScreen) return document.mozCancelFullScreen();
				if (document.msExitFullscreen) return document.msExitFullscreen();
			}
			if (fullscreenRequired) {
				overlay.style.display = 'flex';
			}
			enterFsBtn.addEventListener('click', function() {
				requestFullscreen(document.documentElement).then(() => {
					fullscreenReady = true;
					monitoringEnabled = cameraReady && fullscreenReady;
					overlay.style.display = 'none';
				}).catch(() => {
					overlay.style.display = 'flex';
				});
			});
			document.addEventListener('fullscreenchange', function() {
				if (!isFullscreen()) {
					fullscreenReady = false;
					overlay.style.display = 'flex';
                    if (monitoringEnabled) {
                        handleTabChange();
                    }
				} else {
					fullscreenReady = true;
					monitoringEnabled = cameraReady && fullscreenReady;
					overlay.style.display = 'none';
				}
			});

            // Add click handlers for options
            optionItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Start timer on first interaction
                    if (!examStarted) {
                        examStarted = true;
                        timerInterval = setInterval(updateTimer, 1000);
                        updateTimer(); // Update immediately
                    }
                    
                    // Select the option
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    // Get the question container
                    const questionContainer = this.closest('.question-card');
                    
                    // Update UI - only remove selected class from options in the same question
                    questionContainer.querySelectorAll('.option-item').forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });
            
            // Confirm before submitting (skip if programmatic)
            document.getElementById('exam-form').addEventListener('submit', function(e) {
                if (!monitoringEnabled) {
                    e.preventDefault();
                    showTabChangeWarning();
                    return;
                }
                // Skip confirmation if data-skip-confirm is set
                if (this.getAttribute('data-skip-confirm') === '1') {
                    return;
                }
                if (!confirm('Are you sure you want to submit your exam? You cannot change your answers after submission.')) {
                    e.preventDefault();
                }
            });
            
            // Tab visibility change detection: only enforce when monitoring enabled
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    isTabActive = false;
                    tabChangeStartTime = Date.now();
                    if (monitoringEnabled) handleTabChange();
                } else {
                    isTabActive = true;
                    if (tabChangeStartTime) {
                        const timeAway = Date.now() - tabChangeStartTime;
                        totalTabChangeTime += timeAway;
                        tabChangeStartTime = null;
                    }
                }
            });
            
            // Window focus/blur detection: only enforce when monitoring enabled
            window.addEventListener('blur', function() {
                if (monitoringEnabled) { handleTabChange(); }
            });
            
            // Prevent right-click context menu
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
            
            // Prevent common keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Prevent F12, Ctrl+Shift+I, Ctrl+U, Ctrl+S, Ctrl+P, etc.
                if (e.key === 'F12' || 
                    (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                    (e.ctrlKey && e.key === 'u') ||
                    (e.ctrlKey && e.key === 's') ||
                    (e.ctrlKey && e.key === 'p') ||
                    (e.ctrlKey && e.key === 'a') ||
                    (e.ctrlKey && e.key === 'c') ||
                    (e.ctrlKey && e.key === 'v') ||
                    (e.ctrlKey && e.key === 'x')) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>