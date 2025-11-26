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
$exam = null;

// Check if exam ID is provided
if (!isset($_GET['exam_id'])) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = (int)$_GET['exam_id'];

// Fetch exam details
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subject = trim($_POST['subject']);
    $duration = (int)$_POST['duration'];
    $total_marks = (int)$_POST['total_marks'];
    
    // Validate inputs
    if (empty($title)) {
        $error = "Exam title is required";
    } elseif (empty($subject)) {
        $error = "Subject is required";
    } elseif ($duration <= 0) {
        $error = "Duration must be greater than 0";
    } elseif ($total_marks <= 0) {
        $error = "Total marks must be greater than 0";
    } else {
        // Update exam in database
        $update_sql = "UPDATE exams SET title = ?, description = ?, subject = ?, duration = ?, total_marks = ? WHERE id = ? AND examiner_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "sssiiii", $title, $description, $subject, $duration, $total_marks, $exam_id, $_SESSION['user_id']);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $message = "Exam updated successfully!";
            
            // Refresh exam data
            $sql = "SELECT * FROM exams WHERE id = ? AND examiner_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $exam_id, $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $exam = mysqli_fetch_assoc($result);
        } else {
            $error = "Error updating exam: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Exam</title>
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
<body>
    <div class="dashboard-container">
        <div class="header d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-edit me-2"></i>Edit Exam</h1>
            <a href="dashboard.php" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="title" class="form-label">Exam Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($exam['title']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($exam['description']); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($exam['subject']); ?>" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="duration" class="form-label">Duration (minutes)</label>
                        <input type="number" class="form-control" id="duration" name="duration" value="<?php echo $exam['duration']; ?>" min="1" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="total_marks" class="form-label">Total Marks</label>
                        <input type="number" class="form-control" id="total_marks" name="total_marks" value="<?php echo $exam['total_marks']; ?>" min="1" required>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="view_questions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>View Questions
                    </a>
                    <div>
                        <a href="dashboard.php" class="btn btn-outline-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Exam</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 