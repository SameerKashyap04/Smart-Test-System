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
    <!-- MediaPipe Face Mesh -->
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/control_utils/control_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils/drawing_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js" crossorigin="anonymous"></script>
    <!-- TensorFlow.js for Object Detection -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd"></script>
    <!-- face-api.js for Impersonation Detection -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js/dist/face-api.min.js"></script>
    <!-- Voice Detection -->
    <script src="../assets/js/voice-detection.js"></script>
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
		
        /* Audio Visualizer */
        #audio-visualizer {
            width: 100%;
            height: 50px;
            background: #000;
            margin-top: 10px;
            border-radius: 5px;
        }
        
		/* Face monitoring styles */
		.face-monitor {
			position: fixed;
			top: 20px;
			right: 20px;
			width: 200px;
			height: auto; /* Allow expansion */
			border: 2px solid #4e54c8;
			border-radius: 8px;
			background: #000;
			z-index: 1000;
			overflow: hidden;
            padding-bottom: 5px;
		}
		.face-monitor video {
			width: 100%;
			height: 150px; /* Fixed video height */
			object-fit: cover;
            display: block;
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
    
    <!-- Global Overlays (Moved outside container to ensure clickability) -->
    
    <!-- Camera permission overlay -->
    <div id="camera-permission-overlay" class="camera-permission-overlay" style="z-index: 20000;">
        <div class="camera-permission-box">
            <div class="warning-icon"><i class="fas fa-video"></i></div>
            <h3>Camera Access Required</h3>
            <p>This exam requires camera access for face monitoring to ensure academic integrity. Your face must be visible throughout the exam.</p>
            <p><strong>Note:</strong> Refusing camera access or covering your face will result in automatic exam submission.</p>
            <button id="allow-camera" class="camera-btn" onclick="handleAllowCameraClick()"><i class="fas fa-camera"></i> Allow Camera Access</button>
            <button id="deny-camera" class="camera-btn danger" onclick="handleDenyCameraClick()"><i class="fas fa-times"></i> Deny (Submit Exam)</button>
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
		<canvas id="face-canvas" style="position: absolute; top: 0; left: 0; pointer-events: none;"></canvas>
        <canvas id="audio-visualizer"></canvas>
		<div id="face-status" class="face-status">Initializing...</div>
	</div>

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
		let faceMesh = null;
		let camera = null;
		let noFaceCount = 0;
        let maxNoFaceTime = 5; // stricter: seconds without face before lock
		let faceCheckInterval = null;
		let lastFaceDetected = Date.now();
        let lastBlinkTime = Date.now();
        const BLINK_TIMEOUT_MS = 60000; // 60 seconds without blink = violation
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

        // Head Pose Estimation Functions
        function calculateHeadPose(landmarks) {
            // Key landmarks
            const nose = landmarks[1];
            const leftCheek = landmarks[234];
            const rightCheek = landmarks[454];
            const chin = landmarks[152];
            const forehead = landmarks[10];
            
            // Calculate face width and height
            const faceWidth = Math.abs(rightCheek.x - leftCheek.x);
            const faceHeight = Math.abs(chin.y - forehead.y);
            
            // Yaw (Left/Right) Detection
            // Calculate nose position relative to cheeks (0.5 is center)
            // If nose is closer to right cheek (in image), user is looking left (in real life)
            // Note: landmarks x is normalized 0-1
            const noseRelX = (nose.x - rightCheek.x) / (leftCheek.x - rightCheek.x);
            
            // Pitch (Up/Down) Detection
            // Calculate nose position relative to forehead/chin
            const noseRelY = (nose.y - forehead.y) / (chin.y - forehead.y);
            
            let status = 'center';
            
            // Thresholds
            if (noseRelX < 0.25) status = 'looking_right';
            if (noseRelX > 0.75) status = 'looking_left';
            if (noseRelY < 0.35) status = 'looking_up';
            if (noseRelY > 0.65) status = 'looking_down';
            
            return status;
        }

        // Tab change detection variables
        let tabChangeCount = 0;
        let maxTabChanges = <?php echo isset($exam['max_tab_changes']) ? (int)$exam['max_tab_changes'] : 3; ?>; // Maximum allowed tab changes from database
        let isTabActive = true;
        let tabChangeStartTime = null;
        let totalTabChangeTime = 0;
        let violationWarningShown = false;
        
        // Snapshot capture function
        function captureSnapshot() {
            const video = document.getElementById('camera-video');
            if (!video || video.paused || video.ended) return null;
            
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            return canvas.toDataURL('image/jpeg', 0.5); // Compress to 50% quality
        }

        // EAR Calculation for Blink Detection
        function calculateEAR(eye) {
            const dist = (p1, p2) => Math.hypot(p1.x - p2.x, p1.y - p2.y);
            // Landmarks: 
            // Left Eye: 33, 160, 158, 133, 153, 144
            // Right Eye: 362, 385, 387, 263, 373, 380
            
            // Vertical distances
            const v1 = dist(eye[1], eye[5]);
            const v2 = dist(eye[2], eye[4]);
            
            // Horizontal distance
            const h = dist(eye[0], eye[3]);
            
            if (h === 0) return 0;
            return (v1 + v2) / (2.0 * h);
        }

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
            const now = Date.now();
            const type = 'tab_change';
            if (lastViolationLogTime[type] && (now - lastViolationLogTime[type] < VIOLATION_COOLDOWN_MS)) {
                return; // Skip if logged recently
            }
            lastViolationLogTime[type] = now;

            const snapshot = captureSnapshot();
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
                    timestamp: new Date().toISOString(),
                    proof_image: snapshot
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
		
		// Violation throttling
		let lastViolationLogTime = {};
		const VIOLATION_COOLDOWN_MS = 10000; // 10 seconds between same violation type logs

		// Face monitoring functions
        // --- Face Distance & Mouth Detection ---
        let baselineFaceWidth = 0;
        let mouthOpenStartTime = null;
        
        function calculateFaceWidth(landmarks) {
            // Cheek to Cheek distance
            // Left Cheek: 234, Right Cheek: 454
            const left = landmarks[234];
            const right = landmarks[454];
            return Math.hypot(left.x - right.x, left.y - right.y);
        }
        
        function checkFaceDistance(currentWidth) {
            if (baselineFaceWidth === 0) {
                // Initialize baseline (assuming first 5 seconds are correct)
                if(currentWidth > 0) baselineFaceWidth = currentWidth;
                return;
            }
            
            // Thresholds (percentage change)
            const change = (currentWidth - baselineFaceWidth) / baselineFaceWidth;
            
            const status = document.getElementById('face-status');
            
            if (change > 0.4) { // 40% larger = Too Close
                if(status) {
                    status.textContent = 'Too Close to Screen';
                    status.className = 'face-status no-face';
                }
                logFaceViolation('face_too_close');
            } else if (change < -0.4) { // 40% smaller = Too Far
                if(status) {
                    status.textContent = 'Too Far / Leaning Back';
                    status.className = 'face-status no-face';
                }
                logFaceViolation('face_too_far');
            }
        }
        
        function checkMouthStatus(landmarks) {
            // Upper Lip Bottom: 13
            // Lower Lip Top: 14
            const upper = landmarks[13];
            const lower = landmarks[14];
            
            // Calculate distance relative to face height (to be scale invariant)
            const faceHeight = Math.hypot(landmarks[10].x - landmarks[152].x, landmarks[10].y - landmarks[152].y);
            const mouthOpen = Math.hypot(upper.x - lower.x, upper.y - lower.y) / faceHeight;
            
            // Threshold for open mouth (experimentally ~0.05)
            if (mouthOpen > 0.05) {
                if (!mouthOpenStartTime) mouthOpenStartTime = Date.now();
                
                if (Date.now() - mouthOpenStartTime > 2000) { // > 2 seconds open
                    const status = document.getElementById('face-status');
                    if(status) {
                        status.textContent = 'Mouth Open (Talking?)';
                        status.className = 'face-status no-face';
                    }
                    logFaceViolation('mouth_open_talking');
                }
            } else {
                mouthOpenStartTime = null;
            }
        }

		function logFaceViolation(violationType) {
            const now = Date.now();
            if (lastViolationLogTime[violationType] && (now - lastViolationLogTime[violationType] < VIOLATION_COOLDOWN_MS)) {
                return; // Skip if logged recently
            }
            lastViolationLogTime[violationType] = now;

            const snapshot = captureSnapshot();
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
					timestamp: new Date().toISOString(),
                    proof_image: snapshot
				})
			}).catch(error => {
				console.error('Error logging face violation:', error);
			});
		}
		
		function initializeFaceDetection() {
			const video = document.getElementById('camera-video');
			const canvas = document.getElementById('face-canvas');
			const status = document.getElementById('face-status');
			
			// Initialize MediaPipe Face Mesh
			faceMesh = new FaceMesh({
				locateFile: (file) => {
					return `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`;
				}
			});
			
			faceMesh.setOptions({
				maxNumFaces: 2,
				refineLandmarks: true,
				minDetectionConfidence: 0.5,
				minTrackingConfidence: 0.5
			});
			
			faceMesh.onResults((results) => {
				canvas.width = video.videoWidth;
				canvas.height = video.videoHeight;
				const ctx = canvas.getContext('2d');
				ctx.save();
				ctx.clearRect(0, 0, canvas.width, canvas.height);
				ctx.drawImage(results.image, 0, 0, canvas.width, canvas.height);
				
				if (results.multiFaceLandmarks && results.multiFaceLandmarks.length > 0) {
					// Multiple faces detected
					if (results.multiFaceLandmarks.length > 1) {
						const now = Date.now();
						if (now - lastProctorAlertAt > PROCTOR_ALERT_COOLDOWN_MS) {
							logFaceViolation('multiple_faces_detected');
							logExamNotification('proctor_alert', 'Multiple faces detected on camera');
							lastProctorAlertAt = now;
						}
					}
					
                    // Liveness Detection via Blink Analysis
                    const landmarks = results.multiFaceLandmarks[0];
                    
                    // Head Pose Analysis
                    const poseStatus = calculateHeadPose(landmarks);
                    
                    // Face Distance Analysis (Z-depth estimation)
                    const faceWidth = calculateFaceWidth(landmarks);
                    checkFaceDistance(faceWidth);
                    
                    // Mouth Opening Analysis (Talking/Whispering)
                    checkMouthStatus(landmarks);

                    if (poseStatus !== 'center') {
                        if (!eyeOffStartAt) eyeOffStartAt = Date.now();
                        
                        const timeLookingAway = (Date.now() - eyeOffStartAt) / 1000;
                        
                        if (timeLookingAway > 2) { // Warning after 2 seconds
                            status.textContent = 'Warning: Look at Screen!';
                            status.className = 'face-status no-face';
                        }
                        
                        if (timeLookingAway > EYE_OFF_THRESHOLD_SEC) { // Violation after 5 seconds
                             logFaceViolation(poseStatus);
                             status.textContent = 'Violation: Looking Away';
                             eyeOffStartAt = Date.now(); // Reset to prevent rapid firing, will fire again in 5s if still looking away
                        }
                    } else {
                        // Reset if back to center
                        eyeOffStartAt = null;
                    }

                    // Eye landmarks (indices)
                    // Left Eye: 33, 160, 158, 133, 153, 144
                    const leftEyeIndices = [33, 160, 158, 133, 153, 144];
                    // Right Eye: 362, 385, 387, 263, 373, 380
                    const rightEyeIndices = [362, 385, 387, 263, 373, 380];
                    
                    const leftEye = leftEyeIndices.map(i => landmarks[i]);
                    const rightEye = rightEyeIndices.map(i => landmarks[i]);
                    
                    const leftEAR = calculateEAR(leftEye);
                    const rightEAR = calculateEAR(rightEye);
                    const avgEAR = (leftEAR + rightEAR) / 2.0;
                    
                    // Blink threshold (usually around 0.2 - 0.3)
                    if (avgEAR < 0.2) {
                        lastBlinkTime = Date.now();
                        // status.textContent = 'Blink Detected'; // removed to avoid flickering status
                    } else {
                        // Check for fake face / photo (no blink for too long)
                        if (Date.now() - lastBlinkTime > BLINK_TIMEOUT_MS) {
                            status.textContent = 'Liveness Check Failed';
                            status.className = 'face-status no-face';
                            logFaceViolation('liveness_failure_no_blink');
                            lastBlinkTime = Date.now(); // Reset to avoid spamming
                        } else {
                            // Only update status if not looking away
                            if (!eyeOffStartAt) {
                                status.textContent = 'Face Detected';
                                status.className = 'face-status detected';
                            }
                        }
                    }

					lastFaceDetected = Date.now();
					noFaceCount = 0;
                    
					// Eye movement estimation via relative keypoints (simplified for Face Mesh)
                    // Using pupil/iris landmarks would be better if available, but using face rotation for now
                    // Or repurpose the logic if applicable.
                    
                    // Draw mesh
                    if (results.multiFaceLandmarks) {
                        for (const landmarks of results.multiFaceLandmarks) {
                            drawConnectors(ctx, landmarks, FACEMESH_TESSELATION,
                                         {color: '#C0C0C070', lineWidth: 1});
                            drawConnectors(ctx, landmarks, FACEMESH_RIGHT_EYE, {color: '#FF3030'});
                            drawConnectors(ctx, landmarks, FACEMESH_RIGHT_EYEBROW, {color: '#FF3030'});
                            drawConnectors(ctx, landmarks, FACEMESH_LEFT_EYE, {color: '#30FF30'});
                            drawConnectors(ctx, landmarks, FACEMESH_LEFT_EYEBROW, {color: '#30FF30'});
                            drawConnectors(ctx, landmarks, FACEMESH_FACE_OVAL, {color: '#E0E0E0'});
                            drawConnectors(ctx, landmarks, FACEMESH_LIPS, {color: '#E0E0E0'});
                        }
                    }

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
					await faceMesh.send({image: video});
				},
				width: 200,
				height: 150
			});
            camera.start();
		}

        // --- Object Detection (Mobile Phone / Book) ---
        let objectModel = null;
        let isObjectDetectionRunning = false;

        async function initializeObjectDetection() {
            try {
                console.log("Loading Object Detection Model...");
                objectModel = await cocoSsd.load();
                console.log("Object Detection Model Loaded");
                
                // Run detection loop every 2 seconds
                setInterval(detectObjects, 2000);
            } catch (e) {
                console.error("Failed to load object detection model", e);
            }
        }

        async function detectObjects() {
            if (!objectModel || !cameraReady) return;
            
            const video = document.getElementById('camera-video');
            if (!video || video.paused || video.ended) return;

            try {
                const predictions = await objectModel.detect(video);
                
                predictions.forEach(prediction => {
                    const class_name = prediction.class.toLowerCase();
                    if ((class_name === 'cell phone' || class_name === 'book' || class_name === 'laptop') && prediction.score > 0.6) {
                        console.log('Prohibited Object Detected:', class_name);
                        
                        const status = document.getElementById('face-status');
                        if (status) {
                            status.textContent = 'Detected: ' + class_name;
                            status.className = 'face-status no-face';
                        }
                        
                        logFaceViolation('prohibited_object_' + class_name.replace(' ', '_'));
                    }
                });
            } catch (e) {
                console.error("Object detection error:", e);
            }
        }

        // --- Speech Keyword Detection ---
        function initializeSpeechDetection() {
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                console.warn("Speech Recognition API not supported");
                return;
            }

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognition = new SpeechRecognition();
            
            recognition.continuous = true;
            recognition.interimResults = false;
            recognition.lang = 'en-US'; // Default to English

            recognition.onresult = function(event) {
                const last = event.results.length - 1;
                const transcript = event.results[last][0].transcript.trim().toLowerCase();
                console.log("Speech Detected:", transcript);

                // Keywords to flag
                const suspiciousWords = ['hey google', 'alexa', 'siri', 'answer', 'what is', 'help', 'copy', 'paste', 'chatgpt'];
                
                const detectedWord = suspiciousWords.find(word => transcript.includes(word));
                
                if (detectedWord) {
                    console.warn("Suspicious speech detected:", detectedWord);
                    logFaceViolation('suspicious_speech_' + detectedWord.replace(' ', '_'));
                    
                    const status = document.getElementById('face-status');
                    if (status) {
                        status.textContent = 'Speech: ' + detectedWord;
                        status.className = 'face-status no-face';
                    }
                }
            };

            recognition.onerror = function(event) {
                console.error("Speech recognition error", event.error);
            };
            
            // Restart if it stops
            recognition.onend = function() {
                if (monitoringEnabled) {
                    try { recognition.start(); } catch(e) {}
                }
            };

            try {
                recognition.start();
                console.log("Speech Recognition Started");
            } catch (e) {
                console.error("Failed to start speech recognition", e);
            }
        }

        // --- Audio Visualizer ---
        function initializeAudioVisualizer(stream) {
            const canvas = document.getElementById('audio-visualizer');
            if(!canvas) return;
            
            // Fix canvas resolution
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            
            const ctx = canvas.getContext('2d');
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Resume context if suspended (browser policy)
            if (audioContext.state === 'suspended') {
                audioContext.resume();
            }
            
            const source = audioContext.createMediaStreamSource(stream);
            const analyser = audioContext.createAnalyser();
            
            analyser.fftSize = 64; // Increased slightly for better resolution
            source.connect(analyser);
            
            const bufferLength = analyser.frequencyBinCount;
            const dataArray = new Uint8Array(bufferLength);
            
            // Noise Detection Variables
            let noiseStartTime = null;
            const NOISE_THRESHOLD = 40; // Threshold (0-255)
            const NOISE_DURATION_MS = 2000; // Duration to trigger violation
            
            function draw() {
                if(!monitoringEnabled) return;
                requestAnimationFrame(draw);
                
                analyser.getByteFrequencyData(dataArray);
                
                // Dark gray background to show it's active
                ctx.fillStyle = '#222'; 
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                
                const barWidth = (canvas.width / bufferLength) * 2.5;
                let barHeight;
                let x = 0;
                let sum = 0; // Reset sum every frame!
                
                for(let i = 0; i < bufferLength; i++) {
                    const value = dataArray[i];
                    sum += value;
                    
                    // Scale bar height to fit canvas
                    barHeight = (value / 255) * canvas.height;
                    
                    // Visual feedback: Turn red if loud
                    if (value > NOISE_THRESHOLD * 2) {
                        ctx.fillStyle = 'rgb(255, 50, 50)';
                    } else {
                        // Gradient-like color
                        ctx.fillStyle = 'rgb(' + (value + 100) + ',50,200)';
                    }
                    
                    ctx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
                    
                    x += barWidth + 1;
                }
                
                const avgVolume = sum / bufferLength;
                
                if (avgVolume > NOISE_THRESHOLD) {
                    if (!noiseStartTime) {
                        noiseStartTime = Date.now();
                    } else if (Date.now() - noiseStartTime > NOISE_DURATION_MS) {
                        // Sustained noise detected
                        const status = document.getElementById('face-status');
                        if (status) {
                            status.textContent = 'High Background Noise';
                            status.className = 'face-status no-face';
                        }
                        
                        // Log violation (throttled by logFaceViolation)
                        logFaceViolation('high_background_noise');
                        
                        // Reset timer slightly to avoid spamming every frame, but keep checking
                        noiseStartTime = Date.now() - (NOISE_DURATION_MS - 1000); 
                    }
                } else {
                    noiseStartTime = null;
                }
            }
            draw();
        }

        // --- Lighting / Environment Check ---
        function startLightingCheck() {
            setInterval(() => {
                if (!cameraReady) return;
                const video = document.getElementById('camera-video');
                const canvas = document.createElement('canvas');
                canvas.width = 100; 
                canvas.height = 75;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                let r,g,b,avg;
                let colorSum = 0;
                
                for(let x = 0, len = data.length; x < len; x+=4) {
                    r = data[x];
                    g = data[x+1];
                    b = data[x+2];
                    avg = Math.floor((r+g+b)/3);
                    colorSum += avg;
                }
                
                const brightness = Math.floor(colorSum / (canvas.width * canvas.height));
                
                // Threshold for dark room
                if (brightness < 30) {
                    const status = document.getElementById('face-status');
                    if (status) {
                        status.textContent = 'Too Dark! Increase Light';
                        status.className = 'face-status no-face';
                    }
                    logFaceViolation('environment_too_dark');
                }
            }, 5000);
        }

        // --- System Integrity Check (VM/Bot Detection) ---
        function startSystemIntegrityCheck() {
            // Check for WebGL Renderer (common way to detect VMs)
            try {
                const canvas = document.createElement('canvas');
                const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
                if (gl) {
                    const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                    if (debugInfo) {
                        const renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL).toLowerCase();
                        console.log('Renderer:', renderer);
                        
                        // Keywords for VMs and Headless browsers
                        const suspiciousRenderers = ['swiftshader', 'llvmpipe', 'vmware', 'virtualbox', 'mesa', 'software rasterizer'];
                        
                        if (suspiciousRenderers.some(r => renderer.includes(r))) {
                            console.warn('Suspicious Renderer Detected:', renderer);
                            logFaceViolation('vm_or_bot_detected');
                            const status = document.getElementById('face-status');
                            if(status) {
                                status.textContent = 'Virtual Machine Detected';
                                status.className = 'face-status no-face';
                            }
                        }
                    }
                }
            } catch (e) {
                console.error('Integrity check failed:', e);
            }
            
            // Check for Headless User Agent properties
            if (navigator.webdriver || window.callPhantom || window._phantom) {
                logFaceViolation('automation_detected');
            }
        }

        // --- DevTools / Inspector Detection ---
        function startDevToolsDetection() {
            // Monitor window resize speed and difference (DevTools usually docks and resizes viewport)
            let lastWidth = window.innerWidth;
            let lastHeight = window.innerHeight;
            
            window.addEventListener('resize', () => {
                const widthDiff = window.outerWidth - window.innerWidth;
                const heightDiff = window.outerHeight - window.innerHeight;
                
                // If difference is significant (>200px), DevTools might be open docked
                if (widthDiff > 200 || heightDiff > 200) {
                    console.warn('DevTools might be open');
                    // We don't ban immediately, just log a warning for review
                    // logFaceViolation('devtools_suspected'); 
                }
            });
            
            // Debugger Trap (stops execution if DevTools is open)
            setInterval(() => {
                const startTime = Date.now();
                debugger; // This statement only pauses execution if DevTools is open!
                const endTime = Date.now();
                
                if (endTime - startTime > 100) { // Execution paused > 100ms
                    console.warn('Debugger detected!');
                    logFaceViolation('devtools_debugger_detected');
                    const status = document.getElementById('face-status');
                    if(status) {
                        status.textContent = 'Close Developer Tools!';
                        status.className = 'face-status no-face';
                    }
                }
            }, 2000);
        }

        // --- Whisper / Suspicious Noise Detection ---
        // Refine audio analysis to detect noise without speech
        // (Integrated into initializeAudioVisualizer update loop)
        // We'll add this logic inside the existing visualizer to save resources
        
        // --- Impersonation Check (Face Recognition) ---
        let referenceDescriptor = null;
        async function initializeImpersonationCheck() {
            try {
                console.log("Loading Face Recognition Models...");
                // Load models from a reliable CDN mirror for face-api.js models
                const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
                
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
                await faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL); // Load Emotion Model
                
                console.log("Face Recognition Models Loaded");
                
                // Capture Reference Face after a short delay (to allow camera to settle)
                setTimeout(captureReferenceFace, 3000);
                
                // Start periodic check
                setInterval(checkImpersonation, 30000); // Check every 30 seconds
                
            } catch (e) {
                console.error("Failed to load face recognition models", e);
            }
        }

        async function captureReferenceFace() {
            const video = document.getElementById('camera-video');
            if(video.paused || video.ended) return;
            
            const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                                           .withFaceLandmarks(true)
                                           .withFaceDescriptor();
                                           
            if (detection) {
                referenceDescriptor = detection.descriptor;
                console.log("Reference face captured.");
                const status = document.getElementById('face-status');
                if(status) status.textContent = 'Identity Verified';
            } else {
                console.warn("Could not capture reference face. Retrying...");
                setTimeout(captureReferenceFace, 2000);
            }
        }

        async function checkImpersonation() {
            if (!referenceDescriptor) return;
            
            const video = document.getElementById('camera-video');
            if(video.paused || video.ended) return;
            
            const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                                           .withFaceLandmarks(true)
                                           .withFaceDescriptor()
                                           .withFaceExpressions(); // Get Emotions
                                           
            if (detection) {
                const distance = faceapi.euclideanDistance(referenceDescriptor, detection.descriptor);
                // Distance < 0.6 is usually a match
                if (distance > 0.6) {
                    console.warn("Impersonation Detected! Distance:", distance);
                    logFaceViolation('impersonation_suspected');
                    const status = document.getElementById('face-status');
                    status.textContent = 'Identity Mismatch!';
                    status.className = 'face-status no-face';
                } else {
                    console.log("Identity confirmed. Distance:", distance);
                }
                
                // Emotion Check
                const emotions = detection.expressions;
                // Fear > 0.9 or Disgust > 0.9 are suspicious
                if (emotions.fear > 0.95) {
                    logFaceViolation('suspicious_emotion_fear');
                    const status = document.getElementById('face-status');
                    if(status) {
                        status.textContent = 'High Stress Detected';
                        status.className = 'face-status no-face';
                    }
                }
            }
        }

        // --- Keystroke Dynamics (Typing Speed Analysis) ---
        let keystrokes = [];
        const MAX_WPM_THRESHOLD = 150; // Impossible speed for normal student
        
        document.addEventListener('keydown', function(e) {
            // Ignore navigation keys
            if (e.key.length > 1 && e.key !== 'Backspace' && e.key !== 'Enter') return;
            
            const now = Date.now();
            keystrokes.push(now);
            
            // Keep only last 60 seconds of keystrokes
            keystrokes = keystrokes.filter(t => now - t < 60000);
            
            // Calculate WPM (approx 5 chars = 1 word)
            const charCount = keystrokes.length;
            const wpm = (charCount / 5); 
            
            if (wpm > MAX_WPM_THRESHOLD) {
                console.warn('Impossible typing speed detected:', wpm, 'WPM');
                logFaceViolation('impossible_typing_speed');
                const status = document.getElementById('face-status');
                if(status) {
                    status.textContent = 'Typing Too Fast (Macro?)';
                    status.className = 'face-status no-face';
                }
                // Clear history to prevent spamming
                keystrokes = [];
            }
        });
        
        // Detect Paste Bursts (Large text inserted instantly)
        document.addEventListener('paste', function(e) {
            // We rely on 'impossible_typing_speed' or the browser's native paste handling
            // But we can log large pastes explicitly
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            if (pastedText.length > 500) { // > 500 chars pasted at once
                 logFaceViolation('large_paste_detected');
                 console.warn('Large paste detected:', pastedText.length);
            }
        });
		
		// Global handlers for camera buttons (to ensure clickability)
        window.handleAllowCameraClick = async function() {
            const allowBtn = document.getElementById('allow-camera');
            const permissionOverlay = document.getElementById('camera-permission-overlay');
            const faceStatus = document.getElementById('face-status');
            
            allowBtn.disabled = true;
            allowBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Requesting...';
            
            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('Camera API not supported. Please use HTTPS and a modern browser (Chrome/Safari).');
                }

                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: 200, 
                        height: 150,
                        facingMode: 'user'
                    },
                    audio: true // Request audio for speech detection
                });
                
                permissionOverlay.style.display = 'none';
                document.getElementById('face-monitor').style.display = 'block';
                cameraReady = true;
                monitoringEnabled = cameraReady && fullscreenReady;
                
                // Start all AI modules
                initializeFaceDetection();
                initializeObjectDetection();
                initializeSpeechDetection();
                initializeAudioVisualizer(stream); // Start Audio Visualizer
                initializeImpersonationCheck(); // Start Impersonation Check
                startLightingCheck(); // Start Environment Check
                startSystemIntegrityCheck(); // Start VM/Bot Check
                startDevToolsDetection(); // Start Anti-Tamper
                
            } catch (error) {
                console.error('Camera/Audio access denied:', error);
                alert('Access Error: ' + error.message + '\n\nPlease ensure you are using HTTPS and have allowed camera/microphone permissions.');
                
                if(faceStatus) {
                    faceStatus.textContent = 'Access Denied';
                    faceStatus.className = 'face-status camera-denied';
                }
                
                // Reset button state to allow retry
                allowBtn.disabled = false;
                allowBtn.innerHTML = '<i class="fas fa-camera"></i> Allow Camera Access';
            }
        };

        window.handleDenyCameraClick = function() {
            if(confirm('Are you sure? Refusing camera access will submit your exam immediately.')) {
                logFaceViolation('camera_access_denied');
                handleTabChange(); // This locks/submits
            }
        };
		
		function requestCameraPermission() {
			const permissionOverlay = document.getElementById('camera-permission-overlay');
			permissionOverlay.style.display = 'flex';
            // Event listeners are now handled via inline onclick
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