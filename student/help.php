<?php
session_start();
require_once '../config/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

// Ensure support_tickets table exists
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

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if ($subject === '' || $message === '') {
        $error = 'Please enter subject and message.';
    } else {
        // 1. Save to Database
        $stmt = mysqli_prepare($conn, 'INSERT INTO support_tickets (user_id, role, subject, message) VALUES (?,\'student\',?,?)');
        mysqli_stmt_bind_param($stmt, 'iss', $_SESSION['user_id'], $subject, $message);
        
        if (mysqli_stmt_execute($stmt)) {
            // 2. Send Email
            // Fetch user email for Reply-To
            $user_query = mysqli_prepare($conn, "SELECT email, username FROM users WHERE id = ?");
            mysqli_stmt_bind_param($user_query, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($user_query);
            $user_result = mysqli_stmt_get_result($user_query);
            $user_data = mysqli_fetch_assoc($user_result);
            
            $user_email = $user_data['email'] ?? 'unknown@example.com';
            $user_name = $user_data['username'] ?? 'Unknown User';
            $college = $_SESSION['college'] ?? 'N/A';
            $branch = $_SESSION['branch'] ?? 'N/A';

            $to = 'noreply@devify.live'; // As requested
            $email_subject = "Support Request: " . $subject;
            $email_body = "New support request received.\n\n" .
                          "User Details:\n" .
                          "ID: " . $_SESSION['user_id'] . "\n" .
                          "Name: " . $user_name . "\n" .
                          "Role: Student\n" .
                          "Email: " . $user_email . "\n" .
                          "College: " . $college . "\n" .
                          "Branch: " . $branch . "\n\n" .
                          "Subject: " . $subject . "\n" .
                          "Message:\n" . $message . "\n\n" .
                          "Sent from Smart Test System";
            
            $headers = "From: noreply@devify.live\r\n";
            $headers .= "Reply-To: " . $user_email . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            // Send mail
            $mail_sent = @mail($to, $email_subject, $email_body, $headers);

            if ($mail_sent) {
                $success = 'Your request has been submitted and emailed to support.';
            } else {
                $success = 'Your request has been submitted (Email could not be sent, but ticket is logged).';
            }
        } else {
            $error = 'Failed to submit request to database.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - Student</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="layout-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-graduation-cap me-2"></i> SmartTest
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="sidebar-link">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="dashboard.php#available-exams" class="sidebar-link">
                        <i class="fas fa-clipboard-list"></i> Available Exams
                    </a>
                </li>
                <li>
                    <a href="dashboard.php#completed-exams" class="sidebar-link">
                        <i class="fas fa-history"></i> History
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="sidebar-link">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
                <li>
                    <a href="help.php" class="sidebar-link active">
                        <i class="fas fa-question-circle"></i> Help
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="d-flex align-items-center">
                    <button class="btn btn-link text-muted d-md-none me-3" id="sidebarToggle">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                    <h4 class="mb-0">Help & Support</h4>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" title="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="dropdown">
                        <div class="user-profile" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                            </div>
                            <span class="d-none d-md-block fw-medium">
                                <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                            </span>
                            <i class="fas fa-chevron-down small text-muted d-none d-md-block"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm mt-2">
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <div class="dashboard-padding">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Contact Support Card -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="icon-square bg-primary-subtle text-primary me-3 rounded">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <h5 class="mb-0 fw-bold">Submit a Request</h5>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <p class="text-muted mb-4">Have a question or facing an issue? Fill out the form below and our support team will get back to you at <strong>noreply@devify.live</strong>.</p>
                                
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">Subject</label>
                                        <input type="text" name="subject" class="form-control" placeholder="Briefly describe your issue" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-medium">Message</label>
                                        <textarea name="message" class="form-control" rows="6" placeholder="Provide detailed information about your request..." required></textarea>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i> Send Request
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- FAQ / Info Section (Optional) -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-info"></i> Quick Tips</h5>
                                <ul class="list-unstyled text-muted mb-0">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Ensure your webcam and microphone are working before starting an exam.</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Do not switch tabs or windows during an exam to avoid violations.</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Use a stable internet connection for the best experience.</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Update your profile information in Settings if it's incorrect.</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        // Mobile Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
