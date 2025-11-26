<?php
session_start();
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'student') {
        header("Location: student/dashboard.php");
    } else {
        header("Location: examiner/dashboard.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Exam Platform</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎓</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
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
                <h2><i class="fas fa-graduation-cap me-2"></i>Smart Test</h2>
                <p class="text-muted">Welcome back! Please login to continue.</p>
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
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <form action="auth/login.php" method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">USERNAME</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-user text-primary"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" name="username" placeholder="Enter your username" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">PASSWORD</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-lock text-primary"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" name="password" id="password" placeholder="Enter your password" required>
                        <button type="button" class="input-group-text bg-transparent border-start-0 toggle-password" data-target="#password">
                            <i class="fas fa-eye text-muted"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">ROLE</label>
                    <select class="form-select" name="role" required>
                        <option value="student">Student</option>
                        <option value="examiner">Examiner</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    Login <i class="fas fa-arrow-right ms-2"></i>
                </button>

                <div class="text-center">
                    <p class="text-muted mb-0">Don't have an account? <a href="auth/register.php" class="text-primary fw-bold">Sign Up</a></p>
                    <p class="text-muted mb-0 mt-2"><a href="auth/forgot_password.php" class="text-muted small">Forgot Password?</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/theme.js"></script>
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
