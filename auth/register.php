<?php
session_start();
require_once '../config/db.php';
require_once 'password_validator.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Initialize password validator
    $passwordValidator = new PasswordValidator(8);

    // Validate password strength
    $passwordValidation = $passwordValidator->validatePassword($password);
    if (!$passwordValidation['valid']) {
        $_SESSION['error'] = "Password requirements not met:<br>" . implode("<br>", $passwordValidation['errors']);
    } else {
        // Check if username or email already exists
        $check_sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($result) > 0) {
            $_SESSION['error'] = "Username or email already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssss", $username, $hashed_password, $role, $email);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = "Registration successful! Please login.";
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['error'] = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Exam Platform</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 500px;
        }
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-header i {
            font-size: 3rem;
            color: #000DFF;
            margin-bottom: 1rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.8rem;
            margin-bottom: 1rem;
        }
        .btn-register {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            border: none;
            border-radius: 10px;
            padding: 0.8rem;
            width: 100%;
            color: white;
            font-weight: bold;
            margin-top: 1rem;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #000DFF 0%, #6B73FF 100%);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <i class="fas fa-user-plus"></i>
            <h2>Create Account</h2>
            <p class="text-muted">Join our online exam platform</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" name="username" placeholder="Username" required>
                </div>
            </div>
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" name="email" placeholder="Email" required>
                </div>
            </div>
			<div class="mb-3">
				<div class="input-group">
					<span class="input-group-text"><i class="fas fa-lock"></i></span>
					<input type="password" class="form-control" id="signup-password" name="password" placeholder="Password" required>
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
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-users"></i></span>
                    <select class="form-select" name="role" required>
                        <option value="">Select Role</option>
                        <option value="student">Student</option>
                        <option value="examiner">Examiner</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-register">
                <i class="fas fa-user-plus me-2"></i> Register
            </button>
        </form>
        <div class="login-link">
            <p>Already have an account? <a href="login.php" class="text-primary">Login here</a></p>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password validation and strength indicator
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('signup-password');
            const toggleButton = document.querySelector('.toggle-password');
            
            // Toggle password visibility
            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    const target = document.querySelector(this.getAttribute('data-target'));
                    const icon = this.querySelector('i');
                    
                    if (target.type === 'password') {
                        target.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        target.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            }
            
            // Password validation
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                validatePassword(password);
            });
            
            function validatePassword(password) {
                // Check length
                const lengthValid = password.length >= 8;
                updateRequirement('req-length', lengthValid);
                
                // Check uppercase
                const uppercaseValid = /[A-Z]/.test(password);
                updateRequirement('req-uppercase', uppercaseValid);
                
                // Check lowercase
                const lowercaseValid = /[a-z]/.test(password);
                updateRequirement('req-lowercase', lowercaseValid);
                
                // Check number
                const numberValid = /[0-9]/.test(password);
                updateRequirement('req-number', numberValid);
                
                // Check special character
                const specialValid = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password);
                updateRequirement('req-special', specialValid);
                
                // Calculate strength
                updatePasswordStrength(password);
            }
            
            function updateRequirement(elementId, isValid) {
                const element = document.getElementById(elementId);
                const icon = element.querySelector('i');
                
                if (isValid) {
                    element.classList.remove('text-danger');
                    element.classList.add('text-success');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-check');
                } else {
                    element.classList.remove('text-success');
                    element.classList.add('text-danger');
                    icon.classList.remove('fa-check');
                    icon.classList.add('fa-times');
                }
            }
            
            function updatePasswordStrength(password) {
                const strengthBar = document.getElementById('strength-bar');
                const strengthText = document.getElementById('strength-text');
                
                let score = 0;
                let strength = 'Very Weak';
                let color = 'danger';
                
                if (password.length === 0) {
                    strength = 'Enter a password';
                    color = 'secondary';
                } else {
                    // Length score
                    if (password.length >= 8) score += 20;
                    if (password.length >= 12) score += 10;
                    if (password.length >= 16) score += 10;
                    
                    // Character variety score
                    if (/[a-z]/.test(password)) score += 10;
                    if (/[A-Z]/.test(password)) score += 10;
                    if (/[0-9]/.test(password)) score += 10;
                    if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password)) score += 10;
                    
                    // Complexity bonus
                    if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`].*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password)) {
                        score += 10; // Multiple special characters
                    }
                    
                    // Determine strength level
                    if (score < 30) {
                        strength = 'Very Weak';
                        color = 'danger';
                    } else if (score < 50) {
                        strength = 'Weak';
                        color = 'warning';
                    } else if (score < 70) {
                        strength = 'Fair';
                        color = 'info';
                    } else if (score < 90) {
                        strength = 'Good';
                        color = 'primary';
                    } else {
                        strength = 'Strong';
                        color = 'success';
                    }
                }
                
                // Update progress bar
                strengthBar.style.width = score + '%';
                strengthBar.className = `progress-bar bg-${color}`;
                
                // Update strength text
                strengthText.textContent = strength;
                strengthText.className = `text-${color}`;
            }
        });
    </script>
</body>
</html> 