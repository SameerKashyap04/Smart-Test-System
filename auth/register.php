<?php
session_start();
require_once '../config/db.php';
require_once '../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if username or email already exists
    $check_sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['error'] = "Username or email already exists";
    } else {
        // Generate OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user with is_verified = 0
        $sql = "INSERT INTO users (username, password, role, email, is_verified, otp_code, otp_expiry) VALUES (?, ?, ?, ?, 0, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $username, $hashed_password, $role, $email, $otp, $otp_expiry);
        
        if (mysqli_stmt_execute($stmt)) {
            // Send OTP via Email
            $mail = getMailer();
            if ($mail) {
                try {
                    $mail->addAddress($email, $username);
                    $mail->isHTML(true);
                    $mail->Subject = 'Verify Your Email - Smart Test System';
                    $mail->Body    = "
                        <h2>Welcome to Smart Test System!</h2>
                        <p>Your verification code is: <strong>$otp</strong></p>
                        <p>This code will expire in 10 minutes.</p>
                    ";
                    
                    $mail->send();
                    
                    $_SESSION['verify_email'] = $email;
                    $_SESSION['success'] = "Registration successful! Please check your email for the verification code.";
                    header("Location: verify_email.php");
                    exit();
                    
                } catch (Exception $e) {
                    // If mail fails, we still allow them to verify (e.g. via DB lookup for localhost)
                    if (!$is_production) {
                         $_SESSION['success'] = "Registration successful! (Dev Mode: OTP is $otp)";
                         $_SESSION['verify_email'] = $email;
                         header("Location: verify_email.php");
                         exit();
                    }
                    $_SESSION['error'] = "Registration successful but failed to send email. Please contact support.";
                    $_SESSION['verify_email'] = $email;
                    header("Location: verify_email.php"); 
                    exit();
                }
            } else {
                 // Mailer not configured properly, but user registered
                 if (!$is_production) {
                    $_SESSION['success'] = "Registration successful! (Dev Mode: OTP is $otp)";
                    $_SESSION['verify_email'] = $email;
                    header("Location: verify_email.php");
                    exit();
                 }
                 $_SESSION['error'] = "Mailer configuration error. Check server logs.";
                 $_SESSION['verify_email'] = $email;
                 header("Location: verify_email.php");
                 exit();
            }
        } else {
            $_SESSION['error'] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Exam Platform</title>
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
                <h2><i class="fas fa-user-plus me-2"></i>Create Account</h2>
                <p class="text-muted">Join Smart Test System today</p>
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

            <form method="POST" action="register.php">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">USERNAME</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-user text-primary"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" name="username" placeholder="Choose a username" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">EMAIL</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-envelope text-primary"></i></span>
                        <input type="email" class="form-control border-start-0 ps-0" name="email" placeholder="Enter your email" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">PASSWORD</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-lock text-primary"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" id="signup-password" name="password" placeholder="Create a password" required>
                        <button type="button" class="input-group-text bg-transparent border-start-0 toggle-password" data-target="#signup-password">
                            <i class="fas fa-eye text-muted"></i>
                        </button>
                    </div>
                    <div class="password-requirements mt-2 small text-muted">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Strength:</span>
                            <span id="strength-text">None</span>
                        </div>
                        <div class="progress" style="height: 4px;">
                            <div id="strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">ROLE</label>
                    <select class="form-select" name="role" required>
                        <option value="">Select Role</option>
                        <option value="student">Student</option>
                        <option value="examiner">Examiner</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    Create Account <i class="fas fa-arrow-right ms-2"></i>
                </button>

                <div class="text-center">
                    <p class="text-muted mb-0">Already have an account? <a href="login.php" class="text-primary fw-bold">Login here</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script>
        // Password Toggle
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

        // Password Strength
        document.getElementById('signup-password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            
            let score = 0;
            if (password.length > 0) score += 10;
            if (password.length >= 8) score += 20;
            if (/[A-Z]/.test(password)) score += 20;
            if (/[0-9]/.test(password)) score += 20;
            if (/[^A-Za-z0-9]/.test(password)) score += 30;

            if (score > 100) score = 100;

            strengthBar.style.width = score + '%';
            
            if (score < 30) {
                strengthBar.className = 'progress-bar bg-danger';
                strengthText.textContent = 'Weak';
                strengthText.className = 'text-danger';
            } else if (score < 70) {
                strengthBar.className = 'progress-bar bg-warning';
                strengthText.textContent = 'Medium';
                strengthText.className = 'text-warning';
            } else {
                strengthBar.className = 'progress-bar bg-success';
                strengthText.textContent = 'Strong';
                strengthText.className = 'text-success';
            }
        });
    </script>
</body>
</html>
