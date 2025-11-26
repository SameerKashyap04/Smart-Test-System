<?php
// Start a new session to store user data
session_start();
// Include database connection file
require_once '../config/db.php';

// Check if the form was submitted using POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and get form inputs
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Prepare SQL query to check user credentials
    // Using prepared statement to prevent SQL injection
    $sql = "SELECT * FROM users WHERE username = ? AND role = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $username, $role);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if user exists
    if ($row = mysqli_fetch_assoc($result)) {
        // Verify password using PHP's password_verify function
        if (password_verify($password, $row['password'])) {
            // Set session variables for logged-in user
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            // Redirect based on user role
            if ($role == 'student') {
                header("Location: ../student/dashboard.php");
            } else {
                header("Location: ../examiner/dashboard.php");
            }
            exit();
        } else {
            // Handle incorrect password
            $_SESSION['error'] = "Incorrect password. Please try again.";
            $_SESSION['error_type'] = "password";
            $_SESSION['error_role'] = $role;
        }
    } else {
        // Handle user not found or incorrect role
        $_SESSION['error'] = "Username not found or you don't have access as a " . ucfirst($role) . ".";
        $_SESSION['error_type'] = "username";
        $_SESSION['error_role'] = $role;
    }
    
    // Redirect back to login page with error message
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Exam Platform</title>
    <!-- Include Bootstrap CSS for responsive design -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom CSS for login page styling */
        body {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Login container styling */
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        /* Header styling */
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        /* Form input styling */
        .form-control {
            border-radius: 10px;
            padding: 0.8rem;
            margin-bottom: 1rem;
        }
        /* Login button styling */
        .btn-login {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            border: none;
            border-radius: 10px;
            padding: 0.8rem;
            width: 100%;
            color: white;
            font-weight: bold;
            margin-top: 1rem;
        }
        /* Alert message styling for different types of errors */
        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
            padding: 1rem;
            display: flex;
            align-items: center;
        }
        /* Role-specific alert styling */
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
    </style>
</head>
<body>
    <!-- Main login container -->
    <div class="login-container">
        <!-- Login header with icon -->
        <div class="login-header">
            <i class="fas fa-user-graduate"></i>
            <h2>Welcome Back</h2>
            <p class="text-muted">Please login to your account</p>
        </div>

        <!-- Display error messages if any -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert <?php 
                echo 'alert-' . $_SESSION['error_type']; 
                if (isset($_SESSION['error_role'])) {
                    echo ' alert-' . $_SESSION['error_role'];
                }
            ?>" role="alert">
                <i class="fas <?php 
                    if ($_SESSION['error_type'] == 'password') {
                        echo 'fa-lock';
                    } else {
                        echo 'fa-user';
                    }
                ?>"></i>
                <div>
                    <?php 
                    echo $_SESSION['error'];
                    // Clear error messages after displaying
                    unset($_SESSION['error']);
                    unset($_SESSION['error_type']);
                    unset($_SESSION['error_role']);
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Login form -->
        <form method="POST" action="login.php">
            <!-- Username input field -->
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" name="username" placeholder="Username" required>
                </div>
            </div>
			<!-- Password input field -->
			<div class="mb-3">
				<div class="input-group">
					<span class="input-group-text"><i class="fas fa-lock"></i></span>
					<input type="password" class="form-control" id="login-password" name="password" placeholder="Password" required>
					<button type="button" class="input-group-text toggle-password" data-target="#login-password" aria-label="Toggle password visibility">
						<i class="fas fa-eye"></i>
					</button>
				</div>
			</div>
            <!-- Role selection dropdown -->
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
            <!-- Submit button -->
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Login
            </button>
        </form>
        <!-- Registration link -->
        <div class="register-link">
            <p>Don't have an account? <a href="register.php" class="text-primary">Register here</a></p>
        </div>
    </div>

    <!-- Include Bootstrap JS for interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 