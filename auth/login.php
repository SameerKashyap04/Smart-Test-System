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
            
            // Check if email is verified
            if (isset($row['is_verified']) && $row['is_verified'] == 0) {
                $_SESSION['verify_email'] = $row['email'];
                $_SESSION['error'] = "Please verify your email address first.";
                header("Location: verify_email.php");
                exit();
            }

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