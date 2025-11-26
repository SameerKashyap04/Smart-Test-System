<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'examiner') { header('Location: ../index.php'); exit(); }

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role ENUM('student','examiner') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

$success = null; $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($subject === '' || $message === '') { $error = 'Please enter subject and message.'; }
    else {
        $stmt = mysqli_prepare($conn, 'INSERT INTO support_tickets (user_id, role, subject, message) VALUES (?,\'examiner\',?,?)');
        mysqli_stmt_bind_param($stmt, 'iss', $_SESSION['user_id'], $subject, $message);
        if (mysqli_stmt_execute($stmt)) { $success = 'Your request has been submitted. Our team will contact you via email.'; }
        else { $error = 'Failed to submit request.'; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0"><i class="fas fa-life-ring me-2"></i>Help & Support</h1>
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
        </div>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <div class="card">
            <div class="card-header"><i class="fas fa-envelope me-2"></i> Submit a Request</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="5" required></textarea>
                    </div>
                    <button class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Send</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>


