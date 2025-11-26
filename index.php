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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Exam Platform</title>
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            position: relative;
            overflow: hidden;
        }
        .login-container::after {
            content: '';
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1) 0%, rgba(72, 149, 239, 0.1) 100%);
            border-radius: 50%;
            z-index: -1;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: -50px;
            left: -50px;
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(247, 37, 133, 0.1) 0%, rgba(181, 23, 158, 0.1) 100%);
            border-radius: 50%;
            z-index: -1;
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 1.5rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            animation: fadeIn 0.3s ease-out;
        }
        .alert i {
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        .alert-student {
            background-color: #e3f2fd;
            border-left: 5px solid #2196f3;
            color: #0d47a1;
        }
        .alert-examiner {
            background-color: #fce4ec;
            border-left: 5px solid #e91e63;
            color: #880e4f;
        }
        .alert-password {
            background-color: #fff3e0;
            border-left: 5px solid #ff9800;
            color: #e65100;
        }
        .alert-username {
            background-color: #f1f8e9;
            border-left: 5px solid #8bc34a;
            color: #33691e;
        }
        .tabs {
            margin-bottom: 2rem;
        }
        .tab-btn {
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .form-group {
            margin-bottom: 1.8rem;
        }
        .form-group label {
            margin-bottom: 0.7rem;
            font-weight: 500;
            color: var(--text-color);
        }
        .form-group input, .form-group select {
            padding: 0.9rem 1rem;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        .btn {
            padding: 0.9rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        .btn:hover::before {
            left: 100%;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h1><i class="fas fa-graduation-cap me-2"></i>Online Exam Platform</h1>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert <?php 
                    echo isset($_SESSION['error_type']) ? 'alert-' . $_SESSION['error_type'] : 'alert-danger'; 
                    if (isset($_SESSION['error_role'])) {
                        echo ' alert-' . $_SESSION['error_role'];
                    }
                ?>" role="alert">
                    <i class="fas <?php 
                        if (isset($_SESSION['error_type']) && $_SESSION['error_type'] == 'password') {
                            echo 'fa-lock';
                        } else {
                            echo 'fa-exclamation-triangle';
                        }
                    ?>"></i>
                    <div>
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        unset($_SESSION['error_type']);
                        unset($_SESSION['error_role']);
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab-btn active" data-tab="login"><i class="fas fa-sign-in-alt me-2"></i>Login</button>
                <button class="tab-btn" data-tab="signup"><i class="fas fa-user-plus me-2"></i>Sign Up</button>
            </div>
            
            <div class="tab-content" id="login">
                <form action="auth/login.php" method="POST">
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user me-2"></i>Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button type="button" class="input-group-text toggle-password" data-target="#password" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="role"><i class="fas fa-users me-2"></i>Role</label>
                        <select id="role" name="role" required>
                            <option value="student">Student</option>
                            <option value="examiner">Examiner</option>
                        </select>
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-sign-in-alt me-2"></i>Login</button>
                </form>
            </div>

            <div class="tab-content" id="signup" style="display: none;">
                <form action="auth/signup.php" method="POST">
                    <div class="form-group">
                        <label for="signup-username"><i class="fas fa-user me-2"></i>Username</label>
                        <input type="text" id="signup-username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope me-2"></i>Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="signup-password"><i class="fas fa-lock me-2"></i>Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="signup-password" name="password" required>
                            <button type="button" class="input-group-text toggle-password" data-target="#signup-password" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <!-- Password Requirements -->
                        <div class="password-requirements mt-2">
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">Password Requirements:</small>
                                    <ul class="list-unstyled mt-1">
                                        <li id="req-length" class="text-danger">
                                            <i class="fas fa-times"></i> At least 8 characters
                                        </li>
                                        <li id="req-uppercase" class="text-danger">
                                            <i class="fas fa-times"></i> One uppercase letter
                                        </li>
                                        <li id="req-lowercase" class="text-danger">
                                            <i class="fas fa-times"></i> One lowercase letter
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled mt-4">
                                        <li id="req-number" class="text-danger">
                                            <i class="fas fa-times"></i> One number
                                        </li>
                                        <li id="req-special" class="text-danger">
                                            <i class="fas fa-times"></i> One special character
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <!-- Password Strength Indicator -->
                            <div class="password-strength mt-2">
                                <small class="text-muted">Password Strength:</small>
                                <div class="progress mt-1" style="height: 5px;">
                                    <div id="strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="strength-text" class="text-muted">Enter a password</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm-password" name="confirm_password" required>
                            <button type="button" class="input-group-text toggle-password" data-target="#confirm-password" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="signup-role"><i class="fas fa-users me-2"></i>Role</label>
                        <select id="signup-role" name="role" required>
                            <option value="student">Student</option>
                            <option value="examiner">Examiner</option>
                        </select>
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-user-plus me-2"></i>Sign Up</button>
                </form>
            </div>
        </div>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html> 