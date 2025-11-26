<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an examiner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'examiner') {
    header("Location: ../index.php");
    exit();
}

// Check if exam_id is provided
if (!isset($_GET['exam_id'])) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = (int)$_GET['exam_id'];

// Verify that the exam belongs to the current examiner
$sql = "SELECT * FROM exams WHERE id = ? AND examiner_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $exam_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: dashboard.php");
    exit();
}

$exam = mysqli_fetch_assoc($result);
$message = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = trim($_POST['question_text']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_answer = $_POST['correct_answer'];
    $marks = (int)$_POST['marks'];
    $image_path = null;
    
    // Handle image upload
    if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/questions/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['question_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        } else {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['question_image']['tmp_name'], $upload_path)) {
                $image_path = 'uploads/questions/' . $new_filename;
            } else {
                $error = "Error uploading image.";
            }
        }
    }
    
    // Validate inputs
    if (empty($question_text)) {
        $error = "Question text is required";
    } elseif (empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d)) {
        $error = "All options are required";
    } elseif (!in_array($correct_answer, ['a', 'b', 'c', 'd'])) {
        $error = "Please select a valid correct answer";
    } elseif ($marks <= 0) {
        $error = "Marks must be greater than 0";
    } else {
        // Insert question into database
        $sql = "INSERT INTO questions (exam_id, question_text, image_path, option_a, option_b, option_c, option_d, correct_answer, marks) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssssssi", $exam_id, $question_text, $image_path, $option_a, $option_b, $option_c, $option_d, $correct_answer, $marks);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Question added successfully!";
            
            // Clear form data
            $_POST = array();
        } else {
            $error = "Error adding question: " . mysqli_error($conn);
        }
    }
}

// Fetch existing questions for this exam
$sql = "SELECT * FROM questions WHERE exam_id = ? ORDER BY id";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $exam_id);
mysqli_stmt_execute($stmt);
$questions = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Questions - <?php echo htmlspecialchars($exam['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .btn {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .questions-list {
            margin-top: 40px;
        }
        .question-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .options {
            margin-left: 20px;
        }
        .correct-option {
            font-weight: bold;
            color: #28a745;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
        }
        .tab.active {
            background: white;
            border-color: #ddd;
            border-bottom-color: white;
            margin-bottom: -1px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .image-preview {
            max-width: 300px;
            max-height: 300px;
            margin: 10px 0;
            display: none;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Add Questions - <?php echo htmlspecialchars($exam['title']); ?></h1>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" data-tab="add">Add Question</div>
            <div class="tab" data-tab="view">View Questions</div>
        </div>
        
        <div class="tab-content active" id="add-tab">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="question_text">Question Text</label>
                    <textarea id="question_text" name="question_text" required><?php echo isset($_POST['question_text']) ? htmlspecialchars($_POST['question_text']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="question_image">Question Image (Optional)</label>
                    <input type="file" id="question_image" name="question_image" accept="image/*" onchange="previewImage(this)">
                    <div class="image-preview" id="image-preview">
                        <img id="preview-img" src="#" alt="Preview">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="option_a">Option A</label>
                    <input type="text" id="option_a" name="option_a" required value="<?php echo isset($_POST['option_a']) ? htmlspecialchars($_POST['option_a']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="option_b">Option B</label>
                    <input type="text" id="option_b" name="option_b" required value="<?php echo isset($_POST['option_b']) ? htmlspecialchars($_POST['option_b']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="option_c">Option C</label>
                    <input type="text" id="option_c" name="option_c" required value="<?php echo isset($_POST['option_c']) ? htmlspecialchars($_POST['option_c']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="option_d">Option D</label>
                    <input type="text" id="option_d" name="option_d" required value="<?php echo isset($_POST['option_d']) ? htmlspecialchars($_POST['option_d']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="correct_answer">Correct Answer</label>
                    <select id="correct_answer" name="correct_answer" required>
                        <option value="">Select correct answer</option>
                        <option value="a" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] === 'a') ? 'selected' : ''; ?>>Option A</option>
                        <option value="b" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] === 'b') ? 'selected' : ''; ?>>Option B</option>
                        <option value="c" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] === 'c') ? 'selected' : ''; ?>>Option C</option>
                        <option value="d" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] === 'd') ? 'selected' : ''; ?>>Option D</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="marks">Marks</label>
                    <input type="number" id="marks" name="marks" min="1" required value="<?php echo isset($_POST['marks']) ? (int)$_POST['marks'] : 1; ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Add Question</button>
                </div>
            </form>
        </div>
        
        <div class="tab-content" id="view-tab">
            <div class="questions-list">
                <h2>Questions for <?php echo htmlspecialchars($exam['title']); ?></h2>
                
                <?php if (mysqli_num_rows($questions) === 0): ?>
                    <p>No questions added yet.</p>
                <?php else: ?>
                    <?php $question_number = 1; ?>
                    <?php while ($question = mysqli_fetch_assoc($questions)): ?>
                        <div class="question-item">
                            <h3>Question <?php echo $question_number; ?> (<?php echo $question['marks']; ?> marks)</h3>
                            <p><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                            <?php if ($question['image_path']): ?>
                                <div class="question-image">
                                    <img src="../<?php echo htmlspecialchars($question['image_path']); ?>" alt="Question Image" style="max-width: 100%; max-height: 300px;">
                                </div>
                            <?php endif; ?>
                            <div class="options">
                                <p class="<?php echo $question['correct_answer'] === 'a' ? 'correct-option' : ''; ?>">
                                    A. <?php echo htmlspecialchars($question['option_a']); ?>
                                </p>
                                <p class="<?php echo $question['correct_answer'] === 'b' ? 'correct-option' : ''; ?>">
                                    B. <?php echo htmlspecialchars($question['option_b']); ?>
                                </p>
                                <p class="<?php echo $question['correct_answer'] === 'c' ? 'correct-option' : ''; ?>">
                                    C. <?php echo htmlspecialchars($question['option_c']); ?>
                                </p>
                                <p class="<?php echo $question['correct_answer'] === 'd' ? 'correct-option' : ''; ?>">
                                    D. <?php echo htmlspecialchars($question['option_d']); ?>
                                </p>
                            </div>
                        </div>
                        <?php $question_number++; ?>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(tabId + '-tab').classList.add('active');
                });
            });
        });

        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            const previewImg = document.getElementById('preview-img');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
                previewImg.src = '#';
            }
        }
    </script>
</body>
</html>
