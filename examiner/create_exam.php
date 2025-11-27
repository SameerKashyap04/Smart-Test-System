<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an examiner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'examiner') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

// Check if subject column exists in exams table, if not add it
$check_column_sql = "SHOW COLUMNS FROM exams LIKE 'subject'";
$check_column_result = mysqli_query($conn, $check_column_sql);

if (mysqli_num_rows($check_column_result) == 0) {
    // Subject column doesn't exist, add it
    $alter_table_sql = "ALTER TABLE exams ADD COLUMN subject VARCHAR(100) NOT NULL AFTER description";
    if (!mysqli_query($conn, $alter_table_sql)) {
        $error = "Error adding subject column: " . mysqli_error($conn);
    } else {
        $message = "Database updated successfully. Please try creating the exam again.";
    }
}

// Check if max_tab_changes column exists in exams table, if not add it
$check_tab_changes_sql = "SHOW COLUMNS FROM exams LIKE 'max_tab_changes'";
$check_tab_changes_result = mysqli_query($conn, $check_tab_changes_sql);

if (mysqli_num_rows($check_tab_changes_result) == 0) {
    // max_tab_changes column doesn't exist, add it
    $alter_tab_changes_sql = "ALTER TABLE exams ADD COLUMN max_tab_changes INT DEFAULT 3 AFTER total_marks";
    if (!mysqli_query($conn, $alter_tab_changes_sql)) {
        $error = "Error adding max_tab_changes column: " . mysqli_error($conn);
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subject = trim($_POST['subject']);
    $duration = (int)$_POST['duration'];
    $total_marks = (int)$_POST['total_marks'];
    $max_tab_changes = isset($_POST['max_tab_changes']) ? (int)$_POST['max_tab_changes'] : 3;
    $college = $_SESSION['college']; // Auto-fill from session
    $branch = isset($_POST['branch']) ? $_POST['branch'] : 'All';
    $secret_key = trim($_POST['secret_key']);
    
    // Voice detection settings
    $voice_detection_enabled = isset($_POST['voice_detection_enabled']) ? 1 : 0;
    $microphone_required = isset($_POST['microphone_required']) ? 1 : 0;
    $voice_sensitivity = isset($_POST['voice_sensitivity']) ? (float)$_POST['voice_sensitivity'] : 0.3;
    $voice_violation_threshold = isset($_POST['voice_violation_threshold']) ? (int)$_POST['voice_violation_threshold'] : 2;
    $voice_max_violations = isset($_POST['voice_max_violations']) ? (int)$_POST['voice_max_violations'] : 5;
    
    // Validate inputs
    if (empty($title)) {
        $error = "Exam title is required";
    } elseif (empty($subject)) {
        $error = "Subject is required";
    } elseif ($duration <= 0) {
        $error = "Duration must be greater than 0";
    } elseif ($total_marks <= 0) {
        $error = "Total marks must be greater than 0";
    } elseif (empty($secret_key)) {
        $error = "Secret key is required for exam security";
    } elseif ($max_tab_changes < 0) {
        $error = "Maximum tab changes cannot be negative";
    } else {
        // Insert exam into database
        $sql = "INSERT INTO exams (examiner_id, title, description, subject, duration, total_marks, max_tab_changes, college, branch, secret_key, voice_detection_enabled, microphone_required, voice_sensitivity, voice_violation_threshold, voice_max_violations) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssiiisssiidii", $_SESSION['user_id'], $title, $description, $subject, $duration, $total_marks, $max_tab_changes, $college, $branch, $secret_key, $voice_detection_enabled, $microphone_required, $voice_sensitivity, $voice_violation_threshold, $voice_max_violations);
        
        if (mysqli_stmt_execute($stmt)) {
            $exam_id = mysqli_insert_id($conn);
            $message = "Exam created successfully!";
            
            // Redirect to upload questions page
            header("Location: upload_questions.php?exam_id=" . $exam_id);
            exit();
        } else {
            $error = "Error creating exam: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Exam</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-container {
            padding: 2rem;
        }
        .header {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .form-control:focus {
            border-color: #6B73FF;
            box-shadow: 0 0 0 0.2rem rgba(107, 115, 255, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            border: none;
            padding: 0.8rem 2rem;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #000DFF 0%, #6B73FF 100%);
        }
        .btn-light {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: white;
            transition: all 0.3s ease;
        }
        .btn-light:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <div class="dashboard-container">
        <div class="header d-flex justify-content-between align-items-center">
            <h1 class="mb-0">Create New Exam</h1>
            <div>
                <a href="dashboard.php" class="btn btn-light me-2"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <a href="../auth/logout.php" class="btn btn-light"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Exam Title</label>
                        <input type="text" class="form-control" id="title" name="title" required 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required 
                               value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="branch" class="form-label">Target Branch</label>
                        <select class="form-select" id="branch" name="branch" required>
                            <option value="All" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'All') ? 'selected' : ''; ?>>All Branches</option>
                            <option value="Computer Science" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                            <option value="Information Technology" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                            <option value="Electronics & Communication" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'Electronics & Communication') ? 'selected' : ''; ?>>Electronics & Communication</option>
                            <option value="Mechanical Engineering" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'Mechanical Engineering') ? 'selected' : ''; ?>>Mechanical Engineering</option>
                            <option value="Civil Engineering" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'Civil Engineering') ? 'selected' : ''; ?>>Civil Engineering</option>
                            <option value="Electrical Engineering" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'Electrical Engineering') ? 'selected' : ''; ?>>Electrical Engineering</option>
                        </select>
                        <small class="text-muted">Exam will be visible to students of this branch in your college.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="secret_key" class="form-label">Secret Key (Exam Password)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="text" class="form-control" id="secret_key" name="secret_key" required 
                                   placeholder="e.g. EXAM123"
                                   value="<?php echo isset($_POST['secret_key']) ? htmlspecialchars($_POST['secret_key']) : ''; ?>">
                        </div>
                        <small class="text-muted">Students must enter this key to start the exam.</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="duration" class="form-label">Duration (in minutes)</label>
                        <input type="number" class="form-control" id="duration" name="duration" min="1" required 
                               value="<?php echo isset($_POST['duration']) ? (int)$_POST['duration'] : 60; ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="total_marks" class="form-label">Total Marks</label>
                        <input type="number" class="form-control" id="total_marks" name="total_marks" min="1" required 
                               value="<?php echo isset($_POST['total_marks']) ? (int)$_POST['total_marks'] : 100; ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="max_tab_changes" class="form-label">
                            Max Tab Changes Allowed
                            <i class="fas fa-info-circle text-muted ms-1" title="Maximum number of times students can switch tabs before auto-submit"></i>
                        </label>
                        <input type="number" class="form-control" id="max_tab_changes" name="max_tab_changes" min="0" max="10" 
                               value="<?php echo isset($_POST['max_tab_changes']) ? (int)$_POST['max_tab_changes'] : 3; ?>">
                        <small class="form-text text-muted">Set to 0 to disable tab change detection</small>
                    </div>
                </div>
                
                <!-- Voice Detection Settings -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fas fa-microphone"></i> Voice Detection Settings</h5>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="voice_detection_enabled" name="voice_detection_enabled" 
                                   <?php echo (isset($_POST['voice_detection_enabled']) || !isset($_POST['title'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="voice_detection_enabled">
                                Enable Voice Detection
                            </label>
                        </div>
                        <small class="form-text text-muted">Monitor student voice activity during exam</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="microphone_required" name="microphone_required" 
                                   <?php echo (isset($_POST['microphone_required']) || !isset($_POST['title'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="microphone_required">
                                Require Microphone Access
                            </label>
                        </div>
                        <small class="form-text text-muted">Students must allow microphone access to take exam</small>
                    </div>
                </div>
                
                <div class="row" id="voice-settings" style="<?php echo (isset($_POST['voice_detection_enabled']) || !isset($_POST['title'])) ? '' : 'display: none;'; ?>">
                    <div class="col-md-6 mb-3">
                        <label for="voice_sensitivity" class="form-label">Voice Sensitivity</label>
                        <input type="range" class="form-range" id="voice_sensitivity" name="voice_sensitivity" 
                               min="0.1" max="1.0" step="0.1" 
                               value="<?php echo isset($_POST['voice_sensitivity']) ? $_POST['voice_sensitivity'] : '0.3'; ?>">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Less Sensitive</small>
                            <small class="text-muted">More Sensitive</small>
                        </div>
                        <small class="form-text text-muted">Current: <span id="sensitivity-value">0.3</span></small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="voice_violation_threshold" class="form-label">Violation Threshold</label>
                        <input type="number" class="form-control" id="voice_violation_threshold" name="voice_violation_threshold" 
                               min="1" max="10" 
                               value="<?php echo isset($_POST['voice_violation_threshold']) ? (int)$_POST['voice_violation_threshold'] : 2; ?>">
                        <small class="form-text text-muted">Number of voice detections before counting as violation</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="voice_max_violations" class="form-label">Max Violations Allowed</label>
                        <input type="number" class="form-control" id="voice_max_violations" name="voice_max_violations" 
                               min="1" max="20" 
                               value="<?php echo isset($_POST['voice_max_violations']) ? (int)$_POST['voice_max_violations'] : 5; ?>">
                        <small class="form-text text-muted">Auto-submit exam after this many violations</small>
                    </div>
                </div>
                
                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Create Exam
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Voice detection settings toggle
        document.getElementById('voice_detection_enabled').addEventListener('change', function() {
            const voiceSettings = document.getElementById('voice-settings');
            if (this.checked) {
                voiceSettings.style.display = '';
            } else {
                voiceSettings.style.display = 'none';
            }
        });
        
        // Voice sensitivity slider
        document.getElementById('voice_sensitivity').addEventListener('input', function() {
            document.getElementById('sensitivity-value').textContent = this.value;
        });
        
        // Initialize sensitivity value display
        document.getElementById('sensitivity-value').textContent = document.getElementById('voice_sensitivity').value;
    </script>
</body>
</html> 