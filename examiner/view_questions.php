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

// Handle question deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $question_id = (int)$_GET['delete'];
    
    // Verify that the question belongs to this exam
    $sql = "SELECT * FROM questions WHERE id = ? AND exam_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $question_id, $exam_id);
    mysqli_stmt_execute($stmt);
    $question_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($question_result) > 0) {
        // Delete the question
        $sql = "DELETE FROM questions WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $question_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Question deleted successfully!";
        } else {
            $error = "Error deleting question: " . mysqli_error($conn);
        }
    } else {
        $error = "Question not found or does not belong to this exam.";
    }
}

// Fetch all questions for this exam
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
    <title>View Questions - <?php echo htmlspecialchars($exam['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn {
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-success {
            background: #28a745;
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
        .question-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .question-options {
            margin-left: 20px;
        }
        .correct-option {
            font-weight: bold;
            color: #28a745;
        }
        .actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .no-questions {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Questions for <?php echo htmlspecialchars($exam['title']); ?></h1>
            <div>
                <a href="upload_questions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-success">Add New Question</a>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (mysqli_num_rows($questions) === 0): ?>
            <div class="no-questions">
                <h2>No questions added yet</h2>
                <p>Click the "Add New Question" button to start adding questions to this exam.</p>
            </div>
        <?php else: ?>
            <?php $question_number = 1; ?>
            <?php while ($question = mysqli_fetch_assoc($questions)): ?>
                <div class="question-card">
                    <div class="question-header">
                        <h3>Question <?php echo $question_number; ?> (<?php echo $question['marks']; ?> marks)</h3>
                        <div class="actions">
                            <a href="edit_question.php?id=<?php echo $question['id']; ?>" class="btn">Edit</a>
                            <a href="view_questions.php?exam_id=<?php echo $exam_id; ?>&delete=<?php echo $question['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this question?');">Delete</a>
                        </div>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                    <div class="question-options">
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
</body>
</html> 