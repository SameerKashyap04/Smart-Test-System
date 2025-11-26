<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an examiner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'examiner') {
    header("Location: ../index.php");
    exit();
}

// Check if question ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$question_id = (int)$_GET['id'];

// Fetch the question details
$sql = "SELECT q.*, e.examiner_id FROM questions q 
        JOIN exams e ON q.exam_id = e.id 
        WHERE q.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $question_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: dashboard.php");
    exit();
}

$question = mysqli_fetch_assoc($result);

// Verify that the exam belongs to the current examiner
if ($question['examiner_id'] != $_SESSION['user_id']) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = $question['exam_id'];
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
    $image_path = $question['image_path']; // Keep existing image by default
    
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
                // Delete old image if exists
                if ($question['image_path'] && file_exists('../' . $question['image_path'])) {
                    unlink('../' . $question['image_path']);
                }
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
        // Update question in database
        $sql = "UPDATE questions SET 
                question_text = ?, 
                image_path = ?,
                option_a = ?, 
                option_b = ?, 
                option_c = ?, 
                option_d = ?, 
                correct_answer = ?, 
                marks = ? 
                WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssssii", 
            $question_text,
            $image_path,
            $option_a, 
            $option_b, 
            $option_c, 
            $option_d, 
            $correct_answer, 
            $marks, 
            $question_id
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Question updated successfully!";
            
            // Refresh question data
            $sql = "SELECT * FROM questions WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $question_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $question = mysqli_fetch_assoc($result);
        } else {
            $error = "Error updating question: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container {
            max-width: 800px;
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
        textarea,
        select {
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
            background: #007bff;
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
        .image-preview {
            max-width: 300px;
            max-height: 300px;
            margin: 10px 0;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .current-image {
            margin: 10px 0;
        }
        .current-image img {
            max-width: 300px;
            max-height: 300px;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Edit Question</h1>
            <a href="view_questions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-secondary">Back to Questions</a>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="question_text">Question Text</label>
                <textarea id="question_text" name="question_text" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="question_image">Question Image</label>
                <?php if ($question['image_path']): ?>
                    <div class="current-image">
                        <p>Current Image:</p>
                        <img src="../<?php echo htmlspecialchars($question['image_path']); ?>" alt="Current Question Image">
                    </div>
                <?php endif; ?>
                <input type="file" id="question_image" name="question_image" accept="image/*" onchange="previewImage(this)">
                <div class="image-preview" id="image-preview" style="display: none;">
                    <p>New Image Preview:</p>
                    <img id="preview-img" src="#" alt="Preview">
                </div>
            </div>
            
            <div class="form-group">
                <label for="option_a">Option A</label>
                <input type="text" id="option_a" name="option_a" required value="<?php echo htmlspecialchars($question['option_a']); ?>">
            </div>
            
            <div class="form-group">
                <label for="option_b">Option B</label>
                <input type="text" id="option_b" name="option_b" required value="<?php echo htmlspecialchars($question['option_b']); ?>">
            </div>
            
            <div class="form-group">
                <label for="option_c">Option C</label>
                <input type="text" id="option_c" name="option_c" required value="<?php echo htmlspecialchars($question['option_c']); ?>">
            </div>
            
            <div class="form-group">
                <label for="option_d">Option D</label>
                <input type="text" id="option_d" name="option_d" required value="<?php echo htmlspecialchars($question['option_d']); ?>">
            </div>
            
            <div class="form-group">
                <label for="correct_answer">Correct Answer</label>
                <select id="correct_answer" name="correct_answer" required>
                    <option value="">Select correct answer</option>
                    <option value="a" <?php echo $question['correct_answer'] === 'a' ? 'selected' : ''; ?>>Option A</option>
                    <option value="b" <?php echo $question['correct_answer'] === 'b' ? 'selected' : ''; ?>>Option B</option>
                    <option value="c" <?php echo $question['correct_answer'] === 'c' ? 'selected' : ''; ?>>Option C</option>
                    <option value="d" <?php echo $question['correct_answer'] === 'd' ? 'selected' : ''; ?>>Option D</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="marks">Marks</label>
                <input type="number" id="marks" name="marks" min="1" required value="<?php echo $question['marks']; ?>">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Update Question</button>
            </div>
        </form>
    </div>
    
    <script>
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