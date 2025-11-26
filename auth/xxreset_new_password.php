<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['allow_password_reset'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        // Hash new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear OTP
        $sql = "UPDATE users SET password = ?, otp_code = NULL, otp_expiry = NULL WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            // Cleanup session
            unset($_SESSION['reset_email']);
            unset($_SESSION['allow_password_reset']);
            
            $_SESSION['success'] = "Password reset successfully! You can now login.";
            header("Location: ../index.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Smart Test System</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎓</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="position-absolute top-0 end-0 p-3">
                <button class="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>

            <div class="auth-header">
                <h2><i class="fas fa-lock-open me-2"></i>Reset Password</h2>
                <p class="text-muted">Create a new strong password</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">NEW PASSWORD</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-lock text-primary"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" name="password" id="new-password" placeholder="Enter new password" required>
                        <button type="button" class="input-group-text bg-transparent border-start-0 toggle-password" data-target="#new-password">
                            <i class="fas fa-eye text-muted"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">CONFIRM PASSWORD</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-lock text-primary"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" name="confirm_password" id="confirm-password" placeholder="Confirm new password" required>
                        <button type="button" class="input-group-text bg-transparent border-start-0 toggle-password" data-target="#confirm-password">
                            <i class="fas fa-eye text-muted"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    Reset Password <i class="fas fa-check ms-2"></i>
                </button>
            </form>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script>
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', function() {
                const target = document.querySelector(this.getAttribute('data-target'));
                const icon = this.querySelector('i');
                if (target.type === 'password') {
                    target.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    target.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });
    </script>
</body>
</html>

